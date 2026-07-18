<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'products.manage', 'name' => 'إدارة المنتجات وقوائم المواد', 'module' => 'inventory'],
            ['slug' => 'warehouses.manage', 'name' => 'إدارة المخازن', 'module' => 'inventory'],
            ['slug' => 'customers.manage', 'name' => 'إدارة العملاء', 'module' => 'sales'],
            ['slug' => 'suppliers.manage', 'name' => 'إدارة الموردين', 'module' => 'purchases'],

            ['slug' => 'invoices.create', 'name' => 'إرسال طلب فاتورة', 'module' => 'sales'],
            ['slug' => 'invoices.view', 'name' => 'عرض طلبات الفواتير', 'module' => 'sales'],
            ['slug' => 'invoices.cancel', 'name' => 'إلغاء طلب فاتورة', 'module' => 'sales'],
            ['slug' => 'invoices.approve', 'name' => 'اعتماد الفاتورة من المحاسب', 'module' => 'accounting'],

            ['slug' => 'stock.view_readonly', 'name' => 'عرض أرصدة المخزون (قراءة فقط)', 'module' => 'inventory'],
            ['slug' => 'stock.manage', 'name' => 'إدارة حركات المخزون (وارد/صادر/جرد)', 'module' => 'inventory'],
            ['slug' => 'purchases.manage', 'name' => 'إدارة أوامر الشراء', 'module' => 'purchases'],

            ['slug' => 'production.manage', 'name' => 'إدارة أوامر التصنيع', 'module' => 'production'],
            ['slug' => 'production.view_raw_stock', 'name' => 'عرض مخزون المواد الخام', 'module' => 'production'],

            ['slug' => 'accounting.view', 'name' => 'عرض القيود المحاسبية', 'module' => 'accounting'],
            ['slug' => 'accounting.audit', 'name' => 'عرض سجل النشاط والتعديلات', 'module' => 'accounting'],
            ['slug' => 'payroll.manage', 'name' => 'إدارة الرواتب', 'module' => 'accounting'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        $roles = [
            [
                'slug' => Role::ADMIN,
                'name' => 'مدير النظام',
                'description' => 'صلاحيات كاملة على كل أقسام النظام.',
                'permissions' => array_column($permissions, 'slug'),
            ],
            [
                'slug' => Role::SALES,
                'name' => 'موظف المبيعات',
                'description' => 'إنشاء فواتير البيع ورؤية المخزون بدون تعديله.',
                'permissions' => ['invoices.create', 'invoices.view', 'invoices.cancel', 'stock.view_readonly', 'customers.manage'],
            ],
            [
                'slug' => Role::STOREKEEPER,
                'name' => 'أمين المخزن',
                'description' => 'إدارة الوارد والصادر والجرد وتجهيز الفواتير واستلام المشتريات.',
                'permissions' => ['stock.manage', 'stock.view_readonly', 'purchases.manage', 'suppliers.manage'],
            ],
            [
                'slug' => Role::PRODUCTION,
                'name' => 'مسؤول الإنتاج',
                'description' => 'إدارة أوامر التصنيع ورؤية مخزون المواد الخام.',
                'permissions' => ['production.manage', 'production.view_raw_stock'],
            ],
            [
                'slug' => Role::ACCOUNTANT,
                'name' => 'المحاسب',
                'description' => 'رؤية القيود المالية الناتجة تلقائياً عن المبيعات والمشتريات والتصنيع والرواتب.',
                'permissions' => ['accounting.view', 'accounting.audit', 'payroll.manage', 'stock.view_readonly', 'products.manage', 'invoices.approve', 'invoices.view'],
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                ['name' => $roleData['name'], 'description' => $roleData['description']]
            );

            $permissionIds = Permission::whereIn('slug', $roleData['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
