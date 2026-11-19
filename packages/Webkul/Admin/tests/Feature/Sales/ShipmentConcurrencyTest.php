<?php

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Repositories\OrderAddressRepository;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Create committed synthetic fixtures and inspect competing database sessions.
 */
class ShipmentConcurrencyFixtureRepository extends OrderRepository
{
    /**
     * Refuse committed fixture writes outside the explicitly isolated race database.
     */
    public function requireIsolatedDatabase(): void
    {
        if (! preg_match('/\Abagisto_[a-z0-9_]*concurrency_qa_[a-z0-9_]+\z/', $this->model->getConnection()->getDatabaseName())) {
            throw new RuntimeException('A dedicated shipment concurrency QA database is required.');
        }
    }

    /**
     * Create one verified unit with managed stock visible to both worker connections.
     */
    public function fixture(string $method, int $quantity): array
    {
        $this->requireIsolatedDatabase();
        $channel = core()->getDefaultChannel();
        $source = $channel->inventory_sources->firstOrFail();
        $products = app(ProductRepository::class);
        $product = $products->create([
            'type' => 'simple',
            'attribute_family_id' => app(AttributeFamilyRepository::class)->firstOrFail()->id,
            'sku' => 'SHIPMENT-RACE-'.bin2hex(random_bytes(6)),
        ]);
        $product = $products->update([
            'channel' => $channel->code,
            'locale' => 'en',
            'sku' => $product->sku,
            'name' => 'Synthetic shipment race item',
            'url_key' => strtolower($product->sku),
            'price' => 10,
            'status' => 1,
            'visible_individually' => 1,
            'manage_stock' => 1,
            'weight' => 1,
            'inventories' => [$source->id => 100],
        ], $product->id);
        $order = $this->model->create([
            'increment_id' => 'SHIPMENT-RACE-'.bin2hex(random_bytes(8)),
            'status' => 'processing',
            'workflow_status' => 'confirmed',
            'is_guest' => 1,
            'customer_email' => 'shipment-race@example.test',
            'customer_first_name' => 'Synthetic',
            'customer_last_name' => 'Race',
            'channel_id' => $channel->id,
            'channel_type' => get_class($channel),
            'channel_name' => 'Synthetic race channel',
            'total_item_count' => 1,
            'total_qty_ordered' => $quantity,
            'base_currency_code' => core()->getBaseCurrencyCode(),
            'channel_currency_code' => core()->getBaseCurrencyCode(),
            'order_currency_code' => core()->getBaseCurrencyCode(),
            'grand_total' => 10 * $quantity,
            'base_grand_total' => 10 * $quantity,
            'grand_total_invoiced' => 10 * $quantity,
            'base_grand_total_invoiced' => 10 * $quantity,
            'sub_total' => 10 * $quantity,
            'base_sub_total' => 10 * $quantity,
            'sub_total_incl_tax' => 10 * $quantity,
            'base_sub_total_incl_tax' => 10 * $quantity,
            'shipping_method' => 'free_free',
            'shipping_title' => 'Synthetic race shipping',
        ]);
        $order->payment()->create(['method' => $method, 'method_title' => 'Synthetic manual payment']);

        foreach ([OrderAddress::ADDRESS_TYPE_BILLING, OrderAddress::ADDRESS_TYPE_SHIPPING] as $type) {
            app(OrderAddressRepository::class)->create([
                'order_id' => $order->id,
                'address_type' => $type,
                'first_name' => 'Synthetic',
                'last_name' => 'Race',
                'address' => 'Synthetic race address',
                'city' => 'QA City',
                'state' => 'Selangor',
                'country' => 'MY',
                'postcode' => '00000',
                'email' => 'shipment-race@example.test',
                'phone' => '0000000000',
            ]);
        }

        $item = $this->orderItemRepository->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => get_class($product),
            'name' => 'Synthetic shipment race item',
            'sku' => $product->sku,
            'type' => 'simple',
            'qty_ordered' => $quantity,
            'qty_invoiced' => $quantity,
            'weight' => 1,
            'price' => 10,
            'base_price' => 10,
            'price_incl_tax' => 10,
            'base_price_incl_tax' => 10,
            'total' => 10 * $quantity,
            'base_total' => 10 * $quantity,
            'total_incl_tax' => 10 * $quantity,
            'base_total_incl_tax' => 10 * $quantity,
            'additional' => [],
        ]);
        $this->orderItemRepository->manageInventory($item);

        return ['order' => $order->id, 'item' => $item->id, 'product' => $product->id, 'source' => $source->id];
    }

    /**
     * Observe the second connection waiting for the first order row lock.
     */
    public function hasWaitingShipmentLock(): bool
    {
        foreach ($this->model->getConnection()->select('SHOW PROCESSLIST') as $process) {
            if ($process->db === $this->model->getConnection()->getDatabaseName()
                && str_contains(strtolower((string) $process->Info), 'orders')
                && str_contains(strtolower((string) $process->Info), 'for update')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read shipment totals and both physical and reserved inventory after the race.
     */
    public function snapshot(array $fixture): array
    {
        $order = $this->findOrFail($fixture['order']);
        $product = app(ProductRepository::class)->findOrFail($fixture['product']);

        return [
            'shipments' => $order->shipments()->count(),
            'shipment_quantity' => (int) $order->shipments()->sum('total_qty'),
            'shipped' => (int) app(OrderItemRepository::class)->findOrFail($fixture['item'])->qty_shipped,
            'physical' => (int) $product->inventories()->sum('qty'),
            'reserved' => (int) $product->ordered_inventories()->sum('qty'),
        ];
    }

    /**
     * Remove only this test's committed synthetic order and product.
     */
    public function removeFixture(array $fixture): void
    {
        $this->requireIsolatedDatabase();
        $order = $this->findOrFail($fixture['order']);
        $product = app(ProductRepository::class)->findOrFail($fixture['product']);

        if (! str_starts_with($order->increment_id, 'SHIPMENT-RACE-') || ! str_starts_with($product->sku, 'SHIPMENT-RACE-')) {
            throw new RuntimeException('Only synthetic shipment race fixtures can be removed.');
        }

        $this->delete($order->id);
        app(ProductRepository::class)->delete($product->id);
    }
}

/**
 * Reproduce two independently connected shipment requests against one remaining unit.
 */
class ShipmentConcurrencyTest extends AdminTestCase
{
    /**
     * Keep dedicated synthetic fixtures visible to the independently booted worker processes.
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    /**
     * Exercise both manual methods that share the guarded fulfillment flow.
     */
    public static function manualMethods(): array
    {
        return [
            'Bank duplicate' => ['banktransfer', 1, 'refused'],
            'DuitNow duplicate' => ['duitnowqr_manual', 1, 'refused'],
            'Bank valid partial shipments' => ['banktransfer', 2, 'created'],
            'DuitNow valid partial shipments' => ['duitnowqr_manual', 2, 'created'],
        ];
    }

    /**
     * Force both requests to compete before accepting exactly one shipment.
     */
    #[Test]
    #[DataProvider('manualMethods')]
    public function it_serializes_competing_shipments_without_exceeding_the_ordered_quantity(string $method, int $quantity, string $secondResult): void
    {
        $fixtures = app(ShipmentConcurrencyFixtureRepository::class);

        if (! preg_match('/\Abagisto_[a-z0-9_]*concurrency_qa_[a-z0-9_]+\z/', config('database.connections.mysql.database'))) {
            $this->markTestSkipped('Run committed concurrency fixtures only on their dedicated disposable database.');
        }

        $fixtures->requireIsolatedDatabase();
        Mail::fake();
        $fixture = $fixtures->fixture($method, $quantity);
        $directory = storage_path('framework/testing/shipment-race-'.bin2hex(random_bytes(6)));
        mkdir($directory, 0700, true);
        $workers = [];

        try {
            $workers[] = $this->startWorker('first', $fixture, $directory);
            $this->await(fn () => is_file($directory.'/first-entered'));
            $workers[] = $this->startWorker('second', $fixture, $directory);
            $this->await(fn () => is_file($directory.'/second-started'));
            $this->await(fn () => is_file($directory.'/second-entered') || $fixtures->hasWaitingShipmentLock());
            $secondWaited = ! is_file($directory.'/second-entered');
            touch($directory.'/first-release');
            $this->await(fn () => is_file($directory.'/first-result.json'));
            touch($directory.'/second-release');
            $this->await(fn () => is_file($directory.'/second-result.json'));

            $first = json_decode(file_get_contents($directory.'/first-result.json'), true, flags: JSON_THROW_ON_ERROR);
            $second = json_decode(file_get_contents($directory.'/second-result.json'), true, flags: JSON_THROW_ON_ERROR);

            $this->assertSame('created', $first['result'], json_encode($first));
            $this->assertSame($secondResult, $second['result'], json_encode($second));
            $this->assertTrue($secondWaited, 'The second request must wait for the first shipment transaction to release the order.');
            $this->assertSame(['shipments' => $quantity, 'shipment_quantity' => $quantity, 'shipped' => $quantity, 'physical' => 100 - $quantity, 'reserved' => 0], $fixtures->snapshot($fixture));
        } finally {
            touch($directory.'/first-release');
            touch($directory.'/second-release');

            foreach ($workers as $worker) {
                if (proc_get_status($worker)['running']) {
                    proc_terminate($worker);
                }

                proc_close($worker);
            }

            $fixtures->removeFixture($fixture);

            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }

    /**
     * Start an independently booted application whose pre-save event is controlled by a barrier.
     */
    protected function startWorker(string $name, array $fixture, string $directory): mixed
    {
        $code = <<<'PHP'
$root = $argv[1];
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! preg_match('/\Abagisto_[a-z0-9_]*concurrency_qa_[a-z0-9_]+\z/', config('database.connections.mysql.database'))) {
    throw new RuntimeException('Dedicated concurrency QA database required.');
}
Illuminate\Support\Facades\Mail::fake();
[$name, $directory, $json] = array_slice($argv, 2);
$fixture = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
Illuminate\Support\Facades\Event::listen('sales.shipment.save.before', function () use ($directory, $name): void {
    touch($directory.'/'.$name.'-entered');
    $deadline = microtime(true) + 30;
    while (! is_file($directory.'/'.$name.'-release')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Shipment race barrier timed out.');
        }
        usleep(10000);
    }
});
touch($directory.'/'.$name.'-started');
try {
    app(Webkul\Sales\Repositories\ShipmentRepository::class)->create(['order_id' => $fixture['order'], 'shipment' => ['source' => $fixture['source'], 'carrier_title' => 'Synthetic race carrier', 'track_number' => 'SYNTHETIC-RACE', 'items' => [$fixture['item'] => [$fixture['source'] => 1]]]]);
    $result = ['result' => 'created'];
} catch (Illuminate\Validation\ValidationException $exception) {
    $result = ['result' => 'refused'];
} catch (Throwable $exception) {
    $result = ['result' => 'error', 'exception' => get_class($exception), 'message' => $exception->getMessage()];
}
file_put_contents($directory.'/'.$name.'-result.json', json_encode($result, JSON_THROW_ON_ERROR));
PHP;

        $worker = proc_open([PHP_BINARY, '-r', $code, '--', base_path(), $name, $directory, json_encode($fixture, JSON_THROW_ON_ERROR)], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $directory.'/'.$name.'.log', 'w'], 2 => ['file', $directory.'/'.$name.'.error.log', 'w']], $pipes, base_path());

        if (! is_resource($worker)) {
            throw new RuntimeException('Could not start the shipment race worker.');
        }

        return $worker;
    }

    /**
     * Wait for a concrete process or database synchronization condition with a bounded deadline.
     */
    protected function await(Closure $condition): void
    {
        $deadline = microtime(true) + 25;

        while (! $condition()) {
            if (microtime(true) > $deadline) {
                $this->fail('Shipment race synchronization timed out.');
            }

            usleep(10000);
        }
    }
}
