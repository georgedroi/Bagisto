<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Kalnoy\Nestedset\NodeTrait;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Webkul\Category\Helpers\CategoryTreeOrder;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Core;
use Webkul\FPC\Hasher\DefaultHasher;

class OrganizerTestCategory extends Model
{
    use NodeTrait;

    public $timestamps = false;

    protected $table = 'categories';

    protected $guarded = [];
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->previousResolver = Model::getConnectionResolver();
    $this->previousDispatcher = Model::getEventDispatcher();
    $this->application = new Application;
    $capsule = new Capsule($this->application);
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $capsule->setEventDispatcher(new Dispatcher($this->application));
    $capsule->bootEloquent();
    $this->application->instance('db', $capsule->getDatabaseManager());
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->application);
    $capsule->getConnection()->getSchemaBuilder()->create('categories', function ($table) {
        $table->increments('id');
        $table->integer('parent_id')->nullable();
        $table->integer('position');
        $table->integer('status')->default(1);
        $table->string('name');
        $table->integer('_lft');
        $table->integer('_rgt');
    });
    $capsule->getConnection()->getSchemaBuilder()->create('category_translations', function ($table) {
        $table->integer('category_id');
        $table->string('locale');
    });
    $fixtures = [
        [1, null, 1, 'Root', 1, 18],
        [2, 1, 1, 'Main A', 2, 9],
        [3, 2, 1, 'Folder', 3, 6],
        [4, 3, 1, 'Deep child', 4, 5],
        [5, 2, 2, 'Leaf', 7, 8],
        [6, 1, 2, 'Main B', 10, 15],
        [7, 6, 1, 'First', 11, 12],
        [8, 6, 2, 'Second', 13, 14],
        [9, 1, 3, 'Hidden', 16, 17],
        [10, null, 1, 'Other root', 19, 22],
        [11, 10, 1, 'Other category', 20, 21],
    ];
    foreach ($fixtures as [$id, $parent, $position, $name, $left, $right]) {
        $capsule->getConnection()->table('categories')->insert(['id' => $id, 'parent_id' => $parent, 'position' => $position, 'name' => $name, 'status' => $id === 9 ? 0 : 1, '_lft' => $left, '_rgt' => $right]);
    }
    foreach ($fixtures as $fixture) {
        $capsule->getConnection()->table('category_translations')->insert(['category_id' => $fixture[0], 'locale' => 'en']);
    }
    $this->repository = (new ReflectionClass(CategoryRepository::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(CategoryRepository::class, 'model'))->setValue($this->repository, new OrganizerTestCategory);
    (new ReflectionProperty(CategoryRepository::class, 'criteria'))->setValue($this->repository, collect());
    $this->revision = CategoryTreeOrder::revision(OrganizerTestCategory::all());
});

afterEach(function () {
    if ($this->previousResolver) {
        Model::setConnectionResolver($this->previousResolver);
    } else {
        Model::unsetConnectionResolver();
    }
    if ($this->previousDispatcher) {
        Model::setEventDispatcher($this->previousDispatcher);
    } else {
        Model::unsetEventDispatcher();
    }
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->previousContainer);
    Container::setInstance($this->previousContainer);
});

it('reparents an entire subtree using the real nested-set transaction', function () {
    $this->repository->organizeTree(3, 6, 8, $this->revision, [1, 10]);

    expect((int) OrganizerTestCategory::find(3)->parent_id)->toBe(6)
        ->and((int) OrganizerTestCategory::find(4)->parent_id)->toBe(3)
        ->and(OrganizerTestCategory::find(4)->isDescendantOf(OrganizerTestCategory::find(6)))->toBeTrue()
        ->and(OrganizerTestCategory::where('parent_id', 6)->orderBy('position')->pluck('id')->all())->toBe([7, 3, 8])
        ->and((int) OrganizerTestCategory::find(5)->position)->toBe(1)
        ->and(OrganizerTestCategory::countErrors())->each->toBe(0)
        ->and(OrganizerTestCategory::find(9)->status)->toBe(0)
        ->and(OrganizerTestCategory::count())->toBe(11);
});

it('reorders siblings and appends without changing their parent', function () {
    $this->repository->organizeTree(8, 6, 7, $this->revision, [1, 10]);
    expect(OrganizerTestCategory::where('parent_id', 6)->orderBy('position')->pluck('id')->all())->toBe([8, 7]);
    $revision = CategoryTreeOrder::revision(OrganizerTestCategory::all());
    $this->repository->organizeTree(8, 6, null, $revision, [1, 10]);
    expect(OrganizerTestCategory::where('parent_id', 6)->orderBy('position')->pluck('id')->all())->toBe([7, 8])
        ->and(OrganizerTestCategory::countErrors())->each->toBe(0);
});

it('rejects unsafe moves without altering the database', function ($id, $parent, $before, $roots, $reason) {
    $original = OrganizerTestCategory::orderBy('id')->get()->toArray();
    expect(fn () => $this->repository->organizeTree($id, $parent, $before, $this->revision, $roots))->toThrow(DomainException::class, $reason);
    expect(OrganizerTestCategory::orderBy('id')->get()->toArray())->toBe($original);
})->with([
    [1, 2, null, [1, 10], 'root'],
    [6, 2, null, [1, 6, 10], 'root'],
    [2, 6, null, [1, 4, 10], 'root'],
    [3, 3, null, [1, 10], 'cycle'],
    [3, 4, null, [1, 10], 'cycle'],
    [3, 10, null, [1, 10], 'scope'],
    [3, 999, null, [1, 10], 'invalid'],
    [3, 6, 5, [1, 10], 'invalid'],
    [3, 6, 3, [1, 10], 'invalid'],
]);

it('rejects an outdated hierarchy revision rather than overwriting a newer order', function () {
    $this->repository->organizeTree(8, 6, 7, $this->revision, [1]);
    expect(fn () => $this->repository->organizeTree(3, 6, null, $this->revision, [1]))->toThrow(DomainException::class, 'stale')
        ->and((int) OrganizerTestCategory::find(3)->parent_id)->toBe(2);
});

it('rolls back both nested-set bounds and sibling positions if persistence fails', function () {
    $original = OrganizerTestCategory::orderBy('id')->get()->toArray();
    $events = OrganizerTestCategory::getEventDispatcher();
    $listener = function ($category) {
        if ((int) $category->id === 3 && $category->isDirty('position')) {
            throw new RuntimeException('Simulated write failure');
        }
    };
    $events->listen('eloquent.saving: '.OrganizerTestCategory::class, $listener);
    try {
        expect(fn () => $this->repository->organizeTree(3, 6, null, $this->revision, [1]))->toThrow(RuntimeException::class, 'Simulated write failure');
        expect(OrganizerTestCategory::orderBy('id')->get()->toArray())->toBe($original);
    } finally {
        $events->forget('eloquent.saving: '.OrganizerTestCategory::class);
        OrganizerTestCategory::clearBootedModels();
    }
});

it('produces one stable revision regardless of input enumeration order', function () {
    expect(CategoryTreeOrder::revision(OrganizerTestCategory::orderByDesc('id')->get()))->toBe($this->revision);
    $snapshot = $this->repository->getAdminTreeSnapshot([1, 10]);
    expect($snapshot['revision'])->toBe($this->revision)
        ->and($snapshot['channelRootIds'])->toBe([1, 10])
        ->and($snapshot['categories'])->toHaveCount(2);
});

it('uses saved sibling positions for storefront category carousels and explicit descending order', function () {
    $this->repository->organizeTree(6, 1, 2, $this->revision, [1, 10]);
    $visible = $this->repository->getVisibleCategoryTree(1);
    expect($visible->pluck('id')->all())->toBe([6, 2]);
    $items = $this->repository->getAll(['parent_id' => '1', 'status' => 1, 'locale' => 'en']);
    expect($items->pluck('id')->all())->toBe([6, 2]);
    $reversed = $this->repository->getAll(['parent_id' => '1', 'status' => 1, 'locale' => 'en', 'sort' => 'desc']);
    expect($reversed->pluck('id')->all())->toBe([2, 6]);
});

it('isolates cached HTML between releases while keeping targeted cache invalidation compatible', function () {
    require_once dirname(__DIR__, 2).'/packages/Webkul/Core/src/Http/helpers.php';
    $core = Mockery::mock(Core::class);
    $core->shouldReceive('getCurrentChannel')->andReturn((object) ['code' => 'default']);
    $core->shouldReceive('getCurrentLocale')->andReturn((object) ['code' => 'en']);
    $core->shouldReceive('getCurrentCurrency')->andReturn((object) ['code' => 'MYR']);
    $this->application->instance(Core::class, $core);
    $profile = Mockery::mock(CacheProfile::class);
    $profile->shouldReceive('useCacheNameSuffix')->andReturn('');
    $hasher = new DefaultHasher($profile);
    $request = Request::create('https://fixture.test/example');
    $this->application->setBasePath('/srv/releases/one');
    $first = $hasher->getHashFor($request);
    $selection = Request::create('https://fixture.test/example');
    $selection->attributes->set('responsecache.cacheNameSuffix', 'default-en-MYR-');
    expect($hasher->getHashFor($selection))->toBe($first);
    $this->application->setBasePath('/srv/releases/two');
    expect($hasher->getHashFor($request))->not->toBe($first)
        ->and($hasher->getHashFor($selection))->toBe($hasher->getHashFor($request));
    Mockery::close();
});
