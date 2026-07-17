<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('slug', Role::ADMIN)->first();
        $sales = Role::where('slug', Role::SALES)->first();
        $storekeeper = Role::where('slug', Role::STOREKEEPER)->first();
        $production = Role::where('slug', Role::PRODUCTION)->first();
        $accountant = Role::where('slug', Role::ACCOUNTANT)->first();

        $users = [
            ['name' => 'مدير النظام', 'username' => 'admin', 'email' => 'admin@erp.local', 'role_id' => $admin->id],
            ['name' => 'موظف المبيعات', 'username' => 'sales', 'email' => 'sales@erp.local', 'role_id' => $sales->id],
            ['name' => 'أمين المخزن', 'username' => 'storekeeper', 'email' => 'storekeeper@erp.local', 'role_id' => $storekeeper->id],
            ['name' => 'مسؤول الإنتاج', 'username' => 'production', 'email' => 'production@erp.local', 'role_id' => $production->id],
            ['name' => 'المحاسب', 'username' => 'accountant', 'email' => 'accountant@erp.local', 'role_id' => $accountant->id],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                $userData + ['password' => Hash::make('password123'), 'is_active' => true]
            );
        }

        $rawWarehouse = Warehouse::updateOrCreate(['code' => 'WH-RAW'], ['name' => 'مخزن المواد الخام', 'type' => Warehouse::RAW_MATERIALS]);
        Warehouse::updateOrCreate(['code' => 'WH-WIP'], ['name' => 'مخزن تحت التشغيل', 'type' => Warehouse::WIP]);
        $finishedWarehouse = Warehouse::updateOrCreate(['code' => 'WH-FIN'], ['name' => 'مخزن المنتج التام', 'type' => Warehouse::FINISHED_GOODS]);

        $steel = Product::updateOrCreate(['sku' => 'RM-001'], [
            'name' => 'صاج حديد', 'type' => Product::RAW_MATERIAL, 'unit' => 'KG',
            'cost_price' => 25, 'selling_price' => 0, 'reorder_level' => 100,
        ]);
        $paint = Product::updateOrCreate(['sku' => 'RM-002'], [
            'name' => 'دهان', 'type' => Product::RAW_MATERIAL, 'unit' => 'LTR',
            'cost_price' => 40, 'selling_price' => 0, 'reorder_level' => 50,
        ]);
        $chair = Product::updateOrCreate(['sku' => 'FG-001'], [
            'name' => 'كرسي معدني', 'type' => Product::FINISHED_GOOD, 'unit' => 'PCS',
            'cost_price' => 0, 'selling_price' => 350, 'reorder_level' => 20,
        ]);

        $chair->bomLines()->updateOrCreate(['raw_material_id' => $steel->id], ['quantity_required' => 4, 'unit' => 'KG', 'scrap_percentage' => 5]);
        $chair->bomLines()->updateOrCreate(['raw_material_id' => $paint->id], ['quantity_required' => 0.5, 'unit' => 'LTR', 'scrap_percentage' => 2]);

        // Opening stock so the demo can create invoices/production orders immediately.
        $steel->stocks()->updateOrCreate(['warehouse_id' => $rawWarehouse->id], ['quantity' => 1000]);
        $paint->stocks()->updateOrCreate(['warehouse_id' => $rawWarehouse->id], ['quantity' => 200]);
        $chair->stocks()->updateOrCreate(['warehouse_id' => $finishedWarehouse->id], ['quantity' => 15]);
    }
}
