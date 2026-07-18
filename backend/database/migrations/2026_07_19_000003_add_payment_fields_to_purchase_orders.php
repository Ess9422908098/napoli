<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending')->after('status');
            $table->decimal('paid_amount', 14, 2)->default(0)->after('total_amount');
            $table->timestamp('paid_at')->nullable()->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_amount', 'paid_at']);
        });
    }
};
