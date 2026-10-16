<?php

use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Facade;
use Kalnoy\Nestedset\NodeTrait;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Observers\CategoryObserver;
use Webkul\Category\Repositories\CategoryDeletionRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Observers\ProductObserver;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\User\Contracts\Admin;
use Webkul\User\Repositories\AdministratorApprovalRepository;

class DeletionCategoryFixture extends Model implements Category
{
    use NodeTrait;

    public $timestamps = false;

    protected $table = 'categories';

    protected $guarded = [];
}

class DeletionProductFixture extends Model implements Product
{
    public $timestamps = false;

    protected $table = 'products';

    protected $guarded = [];

    /**
     * Resolve native product descendants for cascade checks.
     */
    public function variants()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}

class ApprovalRoleFixture extends Model
{
    public $timestamps = false;

    protected $table = 'roles';

    protected $guarded = [];
}

class ApprovalAdminFixture extends User implements Admin
{
    public $timestamps = false;

    protected $table = 'admins';

    protected $guarded = [];

    protected $hidden = ['password'];

    /**
     * Resolve the current role used for approval.
     */
    public function role()
    {
        return $this->belongsTo(ApprovalRoleFixture::class, 'role_id');
    }
}

/**
 * Build disposable catalog data with real foreign keys and commit-aware media observers.
 */
function categoryDeletionEnvironment(?Application $app = null): array
{
    $app ??= new Application;
    $events = new Dispatcher($app);
    $app->instance('events', $events);
    $app->instance('config', new Repository(['app' => ['locale' => 'en', 'fallback_locale' => 'en'], 'repository' => ['cache' => ['enabled' => false, 'clean' => ['enabled' => false]]]]));
    $capsule = new Capsule($app);
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]);
    $capsule->setEventDispatcher($events);
    $capsule->bootEloquent();
    $connection = $capsule->getConnection();
    $connection->setTransactionManager(new DatabaseTransactionsManager);
    $app->instance('db', $capsule->getDatabaseManager());
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($app);
    $schema = $connection->getSchemaBuilder();
    $schema->create('categories', function ($table) {
        $table->increments('id');
        $table->integer('parent_id')->nullable();
        $table->integer('position');
        $table->string('name');
        $table->integer('status')->default(1);
        $table->integer('_lft');
        $table->integer('_rgt');
    });
    $schema->create('products', function ($table) {
        $table->increments('id');
        $table->integer('parent_id')->nullable();
        $table->string('sku');
        $table->string('type');
        $table->timestamp('updated_at')->nullable();
        $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
    });
    $schema->create('product_categories', function ($table) {
        $table->integer('product_id');
        $table->integer('category_id');
        $table->unique(['product_id', 'category_id']);
        $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
    });
    $schema->create('roles', function ($table) {
        $table->increments('id');
        $table->string('permission_type');
    });
    $schema->create('admins', function ($table) {
        $table->increments('id');
        $table->string('email');
        $table->string('password');
        $table->integer('status');
        $table->integer('role_id');
    });
    $connection->table('roles')->insert([['id' => 1, 'permission_type' => 'all'], ['id' => 2, 'permission_type' => 'custom']]);
    $hasher = new BcryptHasher(['rounds' => 4]);
    foreach ([[1, 'staff@example.test', 'staff-password', 1, 2], [2, 'superadmin@example.test', 'fixture-superadmin-password', 1, 1], [3, 'inactive@example.test', 'inactive-password', 0, 1], [4, 'limited@example.test', 'limited-password', 1, 2], [5, 'second@example.test', 'second-superadmin-password', 1, 1]] as [$id, $email, $password, $status, $role]) {
        $connection->table('admins')->insert(['id' => $id, 'email' => $email, 'password' => $hasher->make($password), 'status' => $status, 'role_id' => $role]);
    }
    $app->bind(Admin::class, ApprovalAdminFixture::class);
    $app->instance('hash', $hasher);
    $schema->create('orders', function ($table) {
        $table->increments('id');
        $table->string('customer_name');
        $table->integer('total');
    });
    $schema->create('order_items', function ($table) {
        $table->increments('id');
        $table->integer('order_id');
        $table->integer('product_id');
        $table->string('name');
        $table->integer('total');
    });
    foreach ([[1, null, 'Root', 1, 10], [2, 1, 'Delete branch', 2, 7], [3, 2, 'Child category', 3, 4], [4, 2, 'Another child', 5, 6], [5, 1, 'Other category', 8, 9]] as [$id, $parent, $name, $left, $right]) {
        $connection->table('categories')->insert(['id' => $id, 'parent_id' => $parent, 'name' => $name, 'position' => $id, '_lft' => $left, '_rgt' => $right]);
    }
    foreach ([[10, null, 'ONLY', 'simple'], [11, null, 'SHARED', 'simple'], [12, null, 'CONFIGURABLE', 'configurable'], [13, 12, 'VARIANT-A', 'simple'], [14, 12, 'VARIANT-B', 'simple'], [15, null, 'UNRELATED', 'simple'], [16, null, '<img src=x onerror=alert(1)> {{ 7*7 }}', 'simple']] as [$id, $parent, $sku, $type]) {
        $connection->table('products')->insert(['id' => $id, 'parent_id' => $parent, 'sku' => $sku, 'type' => $type, 'updated_at' => '2026-10-17 00:00:00']);
    }
    foreach ([[10, 2], [11, 3], [11, 5], [12, 2], [14, 5], [15, 5], [16, 4]] as [$product, $category]) {
        $connection->table('product_categories')->insert(['product_id' => $product, 'category_id' => $category]);
    }
    $connection->table('orders')->insert(['id' => 1, 'customer_name' => 'Fixture customer', 'total' => 100]);
    $connection->table('order_items')->insert(['id' => 1, 'order_id' => 1, 'product_id' => 11, 'name' => 'Historical item', 'total' => 100]);
    $app->bind(Category::class, DeletionCategoryFixture::class);
    $app->bind(Product::class, DeletionProductFixture::class);
    $makeRepo = function (string $class, $model) use ($app) {
        $repository = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        foreach (['app' => $app, 'model' => $model, 'criteria' => collect()] as $property => $value) {
            (new ReflectionProperty($class, $property))->setValue($repository, $value);
        }

        return $repository;
    };
    $deletions = $makeRepo(CategoryDeletionRepository::class, new DeletionCategoryFixture);
    $categories = $makeRepo(CategoryRepository::class, new DeletionCategoryFixture);
    $products = $makeRepo(ProductRepository::class, new DeletionProductFixture);
    $approvers = $makeRepo(AdministratorApprovalRepository::class, new ApprovalAdminFixture);
    $app->instance(AdministratorApprovalRepository::class, $approvers);
    $media = (object) ['removed' => []];
    $filesystem = Mockery::mock(FilesystemManager::class);
    $filesystem->shouldReceive('deleteDirectory')->andReturnUsing(function ($path) use ($media) {
        $media->removed[] = $path;

        return true;
    });
    $app->instance('filesystem', $filesystem);
    DeletionCategoryFixture::observe(new CategoryObserver);
    DeletionProductFixture::observe(new ProductObserver);

    return compact('app', 'events', 'connection', 'deletions', 'categories', 'products', 'media', 'approvers');
}
