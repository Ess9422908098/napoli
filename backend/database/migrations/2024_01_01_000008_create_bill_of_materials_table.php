<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_required', 14, 4);
            $table->string('unit', 20);
            $table->decimal('scrap_percentage', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['finished_product_id', 'raw_material_id'], 'uq_bom_finished_raw');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_of_materials');
    }
};
