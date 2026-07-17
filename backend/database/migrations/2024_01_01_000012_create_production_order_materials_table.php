<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('products')->restrictOnDelete();
            $table->decimal('planned_quantity', 14, 4);
            $table->decimal('consumed_quantity', 14, 4)->default(0);
            $table->string('unit', 20);
            $table->timestamps();

            $table->unique(['production_order_id', 'raw_material_id'], 'uq_order_material');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_materials');
    }
};
