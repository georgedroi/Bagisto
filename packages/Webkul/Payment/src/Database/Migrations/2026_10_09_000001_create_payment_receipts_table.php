<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_receipts')) {
            $this->validateExistingTable();

            return;
        }

        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id')->unsigned();
            $table->string('receipt_path');
            $table->string('original_name')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Payment receipts require a reviewed data-preserving rollback; this migration never drops an adopted table.');
    }

    /**
     * Adopt a compatible legacy receipt table without changing its records or constraints.
     */
    protected function validateExistingTable(): void
    {
        $columns = collect(Schema::getColumns('payment_receipts'))->keyBy('name');

        $requiredTypes = [
            'id' => ['int', 'bigint'],
            'order_id' => ['int', 'bigint'],
            'receipt_path' => ['varchar'],
            'original_name' => ['varchar'],
            'status' => ['varchar'],
            'admin_notes' => ['text', 'mediumtext', 'longtext'],
            'created_at' => ['timestamp', 'datetime'],
            'updated_at' => ['timestamp', 'datetime'],
        ];

        foreach ($requiredTypes as $name => $types) {
            $column = $columns->get($name);

            if (! $column || ! in_array(strtolower($column['type_name']), $types, true)) {
                throw new RuntimeException('Existing payment_receipts has an incompatible '.$name.' column; no receipt schema or data was changed.');
            }
        }

        foreach (['id', 'order_id'] as $name) {
            $column = $columns->get($name);

            if ($column['nullable'] || ! str_contains(strtolower($column['type']), 'unsigned')) {
                throw new RuntimeException('Existing payment_receipts requires a non-null unsigned '.$name.'; no receipt schema or data was changed.');
            }
        }

        $primary = collect(Schema::getIndexes('payment_receipts'))->firstWhere('primary', true);

        if (! $columns->get('id')['auto_increment'] || ($primary['columns'] ?? []) !== ['id']) {
            throw new RuntimeException('Existing payment_receipts requires an auto-increment id primary key; no receipt schema or data was changed.');
        }

        foreach (['receipt_path', 'status'] as $name) {
            if ($columns->get($name)['nullable']) {
                throw new RuntimeException('Existing payment_receipts requires a non-null '.$name.'; no receipt schema or data was changed.');
            }
        }

        foreach (['original_name', 'admin_notes', 'created_at', 'updated_at'] as $name) {
            if (! $columns->get($name)['nullable']) {
                throw new RuntimeException('Existing payment_receipts requires a nullable '.$name.'; no receipt schema or data was changed.');
            }
        }
    }
};
