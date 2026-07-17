<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [Account::CASH, 'الصندوق / البنك', Account::ASSET],
            [Account::ACCOUNTS_RECEIVABLE, 'عملاء (ذمم مدينة)', Account::ASSET],
            [Account::RAW_MATERIALS_INVENTORY, 'مخزون المواد الخام', Account::ASSET],
            [Account::FINISHED_GOODS_INVENTORY, 'مخزون المنتج التام', Account::ASSET],
            [Account::ACCOUNTS_PAYABLE, 'موردون (ذمم دائنة)', Account::LIABILITY],
            [Account::SALES_REVENUE, 'إيرادات المبيعات', Account::REVENUE],
            [Account::COST_OF_GOODS_SOLD, 'تكلفة البضاعة المباعة', Account::EXPENSE],
            [Account::PAYROLL_EXPENSE, 'مصروف الرواتب', Account::EXPENSE],
        ];

        foreach ($accounts as [$code, $name, $type]) {
            Account::updateOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
        }
    }
}
