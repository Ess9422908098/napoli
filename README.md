# نظام ERP متكامل - شركة ومصنع

نظام إدارة موارد شامل (ERP) لإدارة شركة ومصنع في آن واحد، مبني على:

- **Backend:** Laravel 10 (PHP) + Laravel Sanctum للمصادقة عبر API tokens
- **Frontend:** Vue 3 (Composition API) + Vue Router + Pinia + Axios
- **Database:** MySQL

النظام يعتمد على **نظام صلاحيات صارم (RBAC)**: كل الأقسام مترابطة برمجياً من خلال خدمات
مشتركة (Services)، لكنها **منفصلة تماماً في واجهة المستخدم** بحيث لا يرى أي موظف إلا الشاشات
والوظائف الخاصة بدوره فقط.

## البنية العامة

```
erp-system/
├── backend/   (Laravel API)
│   ├── app/Models/           كل جداول قاعدة البيانات (Eloquent)
│   ├── app/Services/         منطق العمل الأساسي (InventoryService, SalesService, ProductionService, PurchaseService, AccountingService, NotificationService)
│   ├── app/Http/Controllers/Api  نقاط النهاية (API endpoints)
│   ├── app/Http/Middleware/  EnsureUserHasRole و EnsureUserHasPermission (RBAC)
│   ├── database/migrations/  تصميم قاعدة البيانات كاملاً
│   └── database/seeders/     أدوار افتراضية + مستخدمين تجريبيين + بيانات تجريبية
└── frontend/  (Vue 3 SPA)
    ├── src/stores/auth.js         حالة تسجيل الدخول + الصلاحيات
    ├── src/router/index.js        حراسة المسارات (route guards) حسب الدور/الصلاحية
    └── src/views/{sales,storekeeper,production,accountant,admin}  شاشات كل قسم منفصلة تماماً
```

## 1. نظام المستخدمين والصلاحيات (Users & Roles)

الجداول: `roles`, `permissions`, `permission_role` (many-to-many), `users` (يحمل `role_id`).

الأدوار الافتراضية ([RolePermissionSeeder](backend/database/seeders/RolePermissionSeeder.php)):

| الدور | الصلاحيات |
|---|---|
| `admin` مدير النظام | كل الصلاحيات (وصول كامل تلقائياً، متجاوز أي فحص) |
| `sales` موظف المبيعات | `invoices.create`, `invoices.view`, `invoices.cancel`, `stock.view_readonly` (قراءة فقط) |
| `storekeeper` أمين المخزن | `stock.manage` (وارد/صادر/جرد/تجهيز الفواتير/استلام المشتريات), `stock.view_readonly` |
| `production` مسؤول الإنتاج | `production.manage`, `production.view_raw_stock` |
| `accountant` المحاسب | `accounting.view` (قراءة فقط للقيود), `payroll.manage` |

**آلية منع التداخل:** كل route محمي بـ middleware باسم `permission:xxx`
([EnsureUserHasPermission](backend/app/Http/Middleware/EnsureUserHasPermission.php)).
موظف المبيعات مثلاً لا يملك صلاحية `stock.manage`، لذلك لو حاول استدعاء أي endpoint خاص
بأمين المخزن سيحصل على `403 Forbidden` حتى لو عرف رابط الـ API مباشرة. الواجهة الأمامية
تُخفي أيضاً أي رابط/قائمة لا تملك الصلاحية عليها ([AppNavBar.vue](frontend/src/components/AppNavBar.vue)
و [router/index.js](frontend/src/router/index.js)) — أي حماية مزدوجة (Backend + Frontend).

## 2. الربط البرمجي بين الأقسام (Business Logic)

### أ. المبيعات ↔ المخزن ([SalesService](backend/app/Services/SalesService.php))
1. عند إنشاء فاتورة، يتم أولاً التحقق من الكمية المتاحة (`quantity - reserved_quantity`) لكل صنف.
2. إذا كانت الكمية غير كافية تُرفض الفاتورة بالكامل مع تفاصيل النقص (لا يتم حجز جزء والباقي لا).
3. إذا كانت كافية: تُحجز الكمية (`reserved_quantity += qty`) دون خصمها فعلياً من الرصيد.
4. يُنشأ إشعار تلقائي لكل مستخدمي دور "أمين المخزن" ([NotificationService](backend/app/Services/NotificationService.php)).
5. عندما يقوم أمين المخزن بـ "تجهيز" الفاتورة (`PATCH /sales-invoices/{id}/fulfill`)، يتم عندها
   فقط خصم الكمية فعلياً من المخزون وتحرير الحجز، وتُرحّل تكلفة البضاعة المباعة (COGS) محاسبياً.

### ب. الإنتاج ↔ المخزن ([ProductionService](backend/app/Services/ProductionService.php))
1. عند إنشاء أمر تصنيع، يُحسب تلقائياً احتياج المواد الخام من قائمة المواد (BOM) مضروبة في الكمية
   المطلوب إنتاجها (مع نسبة الهالك `scrap_percentage`).
2. عند "إنهاء" أمر التصنيع (`PATCH /production-orders/{id}/complete`):
   - يتم التحقق من توفر كل المواد الخام المطلوبة.
   - تُخصم المواد الخام تلقائياً من مخزن المواد الخام.
   - تُضاف كمية "المنتج التام" تلقائياً إلى مخزن المنتج التام (بتكلفة = إجمالي تكلفة المواد
     الخام المستهلكة ÷ الكمية المنتجة).
   - يُرحّل قيد محاسبي تلقائي: من حـ/ مخزون المنتج التام، إلى حـ/ مخزون المواد الخام.

### ج. الحسابات (Accounting) — [AccountingService](backend/app/Services/AccountingService.php)
كل حركة تجارية تُنشئ قيداً محاسبياً مزدوجاً (Double-Entry) تلقائياً ولا يمكن إدخاله يدوياً:

| الحدث | القيد |
|---|---|
| إنشاء فاتورة بيع | مدين: عملاء — دائن: إيرادات المبيعات |
| تجهيز الفاتورة (تسليم فعلي) | مدين: تكلفة البضاعة المباعة — دائن: مخزون المنتج التام |
| استلام أمر شراء | مدين: مخزون المواد الخام — دائن: موردون |
| إنهاء أمر تصنيع | مدين: مخزون المنتج التام — دائن: مخزون المواد الخام |
| ترحيل مرتب | مدين: مصروف الرواتب — دائن: الصندوق |

المحاسب يرى هذه القيود فقط للقراءة عبر `GET /journal-entries` و `GET /accounts/trial-balance`،
ولا يوجد له أي endpoint لإنشاء قيد يدوي.

## 3. تشغيل المشروع

### Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# أنشئ قاعدة بيانات MySQL باسم erp_system وعدّل بيانات الاتصال في .env عند الحاجة
php artisan migrate --seed
php artisan serve
```
بعد التشغيل، ستتوفر حسابات تجريبية (كلمة المرور للجميع: `password123`):
`admin`, `sales`, `storekeeper`, `production`, `accountant`.

### Frontend (Vue 3)
```bash
cd frontend
npm install
npm run dev
```
الواجهة تعمل على `http://localhost:5173` وتُوجّه طلبات `/api` تلقائياً إلى `http://localhost:8000`
(انظر `vite.config.js`).

## 4. أهم الجداول في قاعدة البيانات

- `users`, `roles`, `permissions`, `permission_role`
- `warehouses`, `products`, `bill_of_materials`, `product_stocks`, `stock_movements`
- `production_orders`, `production_order_materials`
- `customers`, `sales_invoices`, `sales_invoice_items`
- `suppliers`, `purchase_orders`, `purchase_order_items`
- `accounts`, `journal_entries`, `journal_entry_lines`
- `notifications`, `payrolls`

راجع مجلد [backend/database/migrations](backend/database/migrations) للتفاصيل الكاملة لكل جدول
والعلاقات (Foreign Keys) بينها.

## ملاحظة حول المشروع القديم

يحتوي مجلد [inventory-production-system](../inventory-production-system) على نسخة سابقة من
النظام مبنية بتقنية مختلفة تماماً (WPF / .NET 8 / EF Core) وهي مشروع مستقل لم يُعدَّل ضمن هذا
الطلب. النظام الجديد في `erp-system/` هو تنفيذ كامل بتقنيات الويب (Laravel + Vue + MySQL) كما
طلبت.
