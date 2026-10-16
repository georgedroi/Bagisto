<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;
use Webkul\Admin\Http\Controllers\Catalog\CategoryController;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->application = new Application;
    $this->categories = Mockery::mock(CategoryRepository::class);
    $this->channels = Mockery::mock(ChannelRepository::class);
    $this->controller = new CategoryController(
        $this->channels,
        $this->categories,
        Mockery::mock(AttributeRepository::class),
    );
});

afterEach(function () {
    Mockery::close();
    Container::setInstance($this->previousContainer);
});

it('loads the full repository tree without filtering hidden categories or paginating', function () {
    $tree = collect([(object) ['id' => 1, 'status' => 1, 'children' => collect([(object) ['id' => 2, 'status' => 0]])]]);
    $snapshot = ['categories' => $tree, 'revision' => str_repeat('a', 64), 'channelRootIds' => [1]];
    $this->channels->shouldReceive('pluck')->once()->with('root_category_id')->andReturn(collect([1]));
    $this->categories->shouldReceive('getAdminTreeSnapshot')->once()->with([1])->andReturn($snapshot);
    $this->application->instance('request', Request::create('/admin/catalog/categories'));
    $view = Mockery::mock(View::class);
    $factory = Mockery::mock(ViewFactory::class);
    $factory->shouldReceive('make')->once()->with('admin::catalog.categories.index', $snapshot, [])->andReturn($view);
    $this->application->instance(ViewFactory::class, $factory);

    expect($this->controller->index())->toBe($view);
});

it('keeps table view available without fetching an unnecessary category tree', function () {
    $this->categories->shouldNotReceive('getAdminTreeSnapshot');
    $this->application->instance('request', Request::create('/admin/catalog/categories?view=list'));
    $view = Mockery::mock(View::class);
    $factory = Mockery::mock(ViewFactory::class);
    $factory->shouldReceive('make')->once()->with('admin::catalog.categories.index', Mockery::on(fn ($data) => $data['categories']->isEmpty()), [])->andReturn($view);
    $this->application->instance(ViewFactory::class, $factory);

    expect($this->controller->index())->toBe($view);
});

it('preserves the existing AJAX datagrid response and its action pipeline', function () {
    require_once dirname(__DIR__, 2).'/packages/Webkul/DataGrid/src/Http/helpers.php';

    $this->categories->shouldNotReceive('getAdminTreeSnapshot');
    $request = Request::create('/admin/catalog/categories');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $this->application->instance('request', $request);
    $response = new JsonResponse(['records' => []]);
    $grid = Mockery::mock(CategoryDataGrid::class);
    $grid->shouldReceive('process')->once()->withNoArgs()->andReturn($response);
    $this->application->instance(CategoryDataGrid::class, $grid);

    expect($this->controller->index())->toBe($response);
});

it('preselects the clicked folder in the native category creation form', function () {
    $this->categories->shouldReceive('getCategoryTree')->once()->andReturn(collect());
    $this->categories->shouldReceive('findOrFail')->once()->with(7)->andReturn((object) ['id' => 7]);
    $attributes = (new ReflectionProperty(CategoryController::class, 'attributeRepository'))->getValue($this->controller);
    $attributes->shouldReceive('findWhere')->once()->with(['is_filterable' => 1])->andReturn(collect());
    $this->application->instance('request', Request::create('/admin/catalog/categories/create?parent_id=7'));
    $view = Mockery::mock(View::class);
    $factory = Mockery::mock(ViewFactory::class);
    $factory->shouldReceive('make')->once()->with('admin::catalog.categories.create', Mockery::on(fn ($data) => $data['selectedParentId'] === 7), [])->andReturn($view);
    $this->application->instance(ViewFactory::class, $factory);
    expect($this->controller->create())->toBe($view);
});

it('defaults a new main category to the configured store root', function () {
    $this->categories->shouldReceive('getCategoryTree')->once()->andReturn(collect());
    $this->channels->shouldReceive('pluck')->once()->with('root_category_id')->andReturn(collect([1]));
    $attributes = (new ReflectionProperty(CategoryController::class, 'attributeRepository'))->getValue($this->controller);
    $attributes->shouldReceive('findWhere')->once()->andReturn(collect());
    $this->application->instance('request', Request::create('/admin/catalog/categories/create'));
    $view = Mockery::mock(View::class);
    $factory = Mockery::mock(ViewFactory::class);
    $factory->shouldReceive('make')->once()->with('admin::catalog.categories.create', Mockery::on(fn ($data) => $data['selectedParentId'] === 1), [])->andReturn($view);
    $this->application->instance(ViewFactory::class, $factory);
    expect($this->controller->create())->toBe($view);
});
