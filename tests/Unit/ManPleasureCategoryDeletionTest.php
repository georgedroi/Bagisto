<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Webkul\Admin\Http\Controllers\Catalog\CategoryController;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Core;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Helpers\Indexers\Flat;
use Webkul\Product\Listeners\Product;
use Webkul\Product\Repositories\ProductBundleOptionProductRepository;
use Webkul\Product\Repositories\ProductGroupedProductRepository;

require_once dirname(__DIR__).'/Fixtures/CategoryDeletionEnvironment.php';

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->previousResolver = Model::getConnectionResolver();
    $this->previousDispatcher = Model::getEventDispatcher();
    Model::clearBootedModels();
    $this->environment = categoryDeletionEnvironment();
});

afterEach(function () {
    Mockery::close();
    Model::clearBootedModels();
    $this->previousResolver ? Model::setConnectionResolver($this->previousResolver) : Model::unsetConnectionResolver();
    $this->previousDispatcher ? Model::setEventDispatcher($this->previousDispatcher) : Model::unsetEventDispatcher();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->previousContainer);
    Container::setInstance($this->previousContainer);
});

it('reviews unique products, extra variants and outside assignments without deleting', function () {
    extract($this->environment);
    $plan = $deletions->review(2, [1]);
    expect($plan['category_ids'])->toBe([4, 3, 2])
        ->and($plan['category_count'])->toBe(3)
        ->and($plan['product_count'])->toBe(6)
        ->and($plan['assigned_count'])->toBe(4)
        ->and($plan['variant_count'])->toBe(2)
        ->and($plan['shared_count'])->toBe(2)
        ->and(array_slice($plan['product_ids'], 0, 2))->toBe([14, 13])
        ->and(DeletionCategoryFixture::count())->toBe(5)
        ->and(DeletionProductFixture::count())->toBe(7)
        ->and($media->removed)->toBe([]);
});

it('keeps every product by default and removes only the deleted branch assignments', function () {
    extract($this->environment);
    $plan = $deletions->review(2, [1]);
    $result = $deletions->deleteReviewed(2, [1], $plan['revision'], false, $categories, $products);
    expect(DeletionCategoryFixture::pluck('id')->all())->toBe([1, 5])
        ->and(DeletionProductFixture::count())->toBe(7)
        ->and($connection->table('product_categories')->orderBy('product_id')->get()->map(fn ($row) => [$row->product_id, $row->category_id])->all())->toBe([[11, 5], [14, 5], [15, 5]])
        ->and($result['product_ids'])->toBe([])
        ->and($media->removed)->toBe(['category/4', 'category/3', 'category/2'])
        ->and(DeletionCategoryFixture::countErrors())->each->toBe(0);
});

it('deletes reviewed products and variants globally and preserves unrelated products and order history', function () {
    extract($this->environment);
    $plan = $deletions->review(2, [1]);
    $beforeIds = [];
    $events->listen('catalog.product.delete.before', function ($id) use (&$beforeIds) {
        $beforeIds[] = $id;
        expect(DeletionProductFixture::find($id))->not->toBeNull();
    });
    $deletions->deleteReviewed(2, [1], $plan['revision'], true, $categories, $products);
    expect(DeletionProductFixture::pluck('id')->all())->toBe([15])
        ->and($beforeIds)->toBe($plan['product_ids'])
        ->and($connection->table('orders')->count())->toBe(1)
        ->and($connection->table('order_items')->first()->name)->toBe('Historical item')
        ->and($connection->table('product_categories')->get()->map(fn ($row) => [$row->product_id, $row->category_id])->all())->toBe([[15, 5]])
        ->and($media->removed)->toHaveCount(9)
        ->and($media->removed)->toContain('product/14', 'product/13', 'product/12', 'product/11', 'category/2');
});

it('protects the root and branches containing configured channel roots', function ($id, $roots) {
    extract($this->environment);
    expect(fn () => $deletions->review($id, $roots))->toThrow(DomainException::class, 'root')
        ->and(DeletionCategoryFixture::count())->toBe(5)
        ->and(DeletionProductFixture::count())->toBe(7)
        ->and($media->removed)->toBe([]);
})->with([[1, [1]], [2, [1, 3]], [3, [1, 3]]]);

it('rejects stale names, product SKUs, variants and outside assignments', function ($change) {
    extract($this->environment);
    $plan = $deletions->review(2, [1]);
    if ($change === 'name') {
        DeletionCategoryFixture::find(3)->update(['name' => 'Renamed']);
    }
    if ($change === 'sku') {
        DeletionProductFixture::find(11)->update(['sku' => 'Changed SKU']);
    }
    if ($change === 'variant') {
        DeletionProductFixture::create(['id' => 17, 'parent_id' => 12, 'sku' => 'NEW VARIANT', 'type' => 'simple']);
    }
    if ($change === 'assignment') {
        $connection->table('product_categories')->insert(['product_id' => 10, 'category_id' => 5]);
    }
    expect(fn () => $deletions->deleteReviewed(2, [1], $plan['revision'], true, $categories, $products))->toThrow(DomainException::class, 'stale')
        ->and(DeletionCategoryFixture::count())->toBe(5)
        ->and(DeletionProductFixture::find(11))->not->toBeNull()
        ->and($media->removed)->toBe([]);
})->with(['name', 'sku', 'variant', 'assignment']);

it('rolls back products, category bounds and assignments and preserves media on failure', function () {
    extract($this->environment);
    $plan = $deletions->review(2, [1]);
    $snapshot = DeletionCategoryFixture::orderBy('id')->get()->toArray();
    $events->listen('catalog.category.delete.before', function ($id) {
        if ($id === 3) {
            throw new RuntimeException('Simulated failure');
        }
    });
    expect(fn () => $deletions->deleteReviewed(2, [1], $plan['revision'], true, $categories, $products))->toThrow(RuntimeException::class, 'Simulated failure')
        ->and(DeletionCategoryFixture::orderBy('id')->get()->toArray())->toBe($snapshot)
        ->and(DeletionProductFixture::count())->toBe(7)
        ->and($connection->table('product_categories')->count())->toBe(7)
        ->and($media->removed)->toBe([]);
});

it('blocks older single and bulk endpoints from bypassing the reviewed password flow', function () {
    $categories = Mockery::mock(CategoryRepository::class);
    $categories->shouldNotReceive('delete');
    $controller = new CategoryController(Mockery::mock(ChannelRepository::class), $categories, Mockery::mock(AttributeRepository::class));
    $this->environment['app']->instance('translator', new Translator(new ArrayLoader, 'en'));
    expect($controller->destroy(2)->getStatusCode())->toBe(409)
        ->and($controller->massDestroy(new MassDestroyRequest)->getStatusCode())->toBe(409)
        ->and(DeletionCategoryFixture::count())->toBe(5);
});

it('checks fresh active full-access roles and accepts any matching administrator identity', function () {
    extract($this->environment);
    expect($approvers->findActiveFullAccess('SUPERADMIN@example.test')->id)->toBe(2)
        ->and($approvers->findActiveFullAccess('second@example.test')->id)->toBe(5)
        ->and($approvers->findActiveFullAccess('inactive@example.test'))->toBeNull()
        ->and($approvers->findActiveFullAccess('limited@example.test'))->toBeNull()
        ->and($approvers->findActiveFullAccess('missing@example.test'))->toBeNull();
    $connection->table('admins')->where('id', 2)->update(['status' => 0]);
    expect($approvers->findActiveFullAccess('superadmin@example.test'))->toBeNull();
    $connection->table('roles')->where('id', 1)->update(['permission_type' => 'custom']);
    expect($approvers->findActiveFullAccess('second@example.test'))->toBeNull();
});

it('marks native Elasticsearch delete and surviving-parent reindex jobs for dispatch after commit', function () {
    require_once dirname(__DIR__, 2).'/packages/Webkul/Core/src/Http/helpers.php';
    extract($this->environment);
    $core = Mockery::mock(Core::class);
    $core->shouldReceive('getConfigData')->with('catalog.products.search.engine')->andReturn('elastic');
    $app->instance(Core::class, $core);
    $jobs = [];
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('dispatch')->andReturnUsing(function ($job) use (&$jobs) {
        $jobs[] = $job;
    });
    $app->instance(Dispatcher::class, $dispatcher);
    $bundles = Mockery::mock(ProductBundleOptionProductRepository::class);
    $bundles->shouldReceive('findWhere')->andReturn(collect());
    $groups = Mockery::mock(ProductGroupedProductRepository::class);
    $groups->shouldReceive('findWhere')->andReturn(collect());
    $listener = new Product($products, $bundles, $groups, Mockery::mock(Flat::class));
    $listener->beforeDelete(14);
    expect($jobs)->toHaveCount(2)
        ->and($jobs[0]->afterCommit)->toBeTrue()
        ->and($jobs[1]->afterCommit)->toBeTrue();
});

it('rate limits repeated approval attempts on the deletion endpoint', function () {
    $limiter = new RateLimiter(new Repository(new ArrayStore));
    $middleware = new ThrottleRequests($limiter);
    $request = Request::create('https://fixture.test/admin/catalog/categories/delete-tree', 'POST');
    $request->setRouteResolver(fn () => new Route('POST', '/admin/catalog/categories/delete-tree', fn () => null));
    $request->setUserResolver(fn () => new GenericUser(['id' => 1]));
    $calls = 0;
    $next = function () use (&$calls) {
        $calls++;

        return new JsonResponse(['message' => 'Invalid approval'], 422);
    };
    for ($attempt = 0; $attempt < 6; $attempt++) {
        expect($middleware->handle($request, $next, 6, 1)->getStatusCode())->toBe(422);
    }
    expect(fn () => $middleware->handle($request, $next, 6, 1))->toThrow(ThrottleRequestsException::class)
        ->and($calls)->toBe(6);
});
