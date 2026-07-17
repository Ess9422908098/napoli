<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('finished_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_to_produce', 14, 4);
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('raw_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('wip_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('finished_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
