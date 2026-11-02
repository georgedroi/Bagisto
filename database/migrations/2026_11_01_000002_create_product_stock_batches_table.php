<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unsignedInteger('inventory_source_id');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('ship_from');
            $table->date('ship_to');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_batches');
    }
};
