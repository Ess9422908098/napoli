<template>
  <aside class="nav">
    <h2>ERP النظام</h2>
    <p class="role-badge">{{ auth.user?.role_name }}</p>

    <nav>
      <router-link to="/dashboard">الرئيسية</router-link>
      <router-link to="/notifications">الإشعارات</router-link>

      <template v-if="auth.hasPermission('invoices.create') || auth.hasPermission('invoices.view') || auth.hasPermission('customers.manage')">
        <h4>المبيعات</h4>
        <router-link v-if="auth.hasPermission('invoices.create')" to="/sales/invoices/new">فاتورة بيع جديدة</router-link>
        <router-link v-if="auth.hasPermission('invoices.view')" to="/sales/invoices">فواتير البيع</router-link>
        <router-link v-if="auth.hasPermission('customers.manage')" to="/sales/customers">إدارة العملاء</router-link>
      </template>

      <template v-if="auth.hasPermission('stock.view_readonly') || auth.hasPermission('stock.manage') || auth.hasPermission('production.view_raw_stock')">
        <router-link to="/stock/lookup">عرض أرصدة المخزون</router-link>
      </template>

      <template v-if="auth.hasPermission('stock.manage')">
        <h4>أمين المخزن</h4>
        <router-link to="/storekeeper/fulfill">تجهيز الفواتير</router-link>
        <router-link to="/storekeeper/receive">تسجيل وارد</router-link>
        <router-link to="/storekeeper/issue">تسجيل صادر</router-link>
        <router-link to="/storekeeper/count">الجرد</router-link>
        <router-link v-if="auth.hasPermission('suppliers.manage')" to="/storekeeper/suppliers">إدارة الموردين</router-link>
      </template>

      <template v-if="auth.hasPermission('production.manage')">
        <h4>الإنتاج</h4>
        <router-link to="/production/orders">أوامر التصنيع</router-link>
      </template>

      <template v-if="auth.hasPermission('accounting.view')">
        <h4>الحسابات</h4>
        <router-link to="/accounting/journal">القيود المحاسبية</router-link>
        <router-link to="/accounting/trial-balance">ميزان المراجعة</router-link>
      </template>

      <template v-if="auth.hasRole('admin')">
        <h4>الإدارة</h4>
        <router-link to="/admin/users">المستخدمون</router-link>
        <router-link to="/admin/products">المنتجات</router-link>
        <router-link to="/admin/warehouses">المخازن</router-link>
      </template>
    </nav>

    <button class="secondary" @click="handleLogout">تسجيل الخروج</button>
  </aside>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.nav {
  width: 260px;
  flex-shrink: 0;
  background: #0d1117;
  color: #fff;
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}
.nav h2 {
  font-size: 19px;
  margin: 0 0 4px;
  letter-spacing: 0.3px;
}
.role-badge {
  display: inline-block;
  color: #9ea7b3;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 12px;
  margin: 0 0 20px;
  width: fit-content;
}
.nav nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}
.nav h4 {
  margin: 18px 0 6px;
  color: #6e7681;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.6px;
  text-transform: uppercase;
}
.nav h4:first-child {
  margin-top: 4px;
}
.nav a {
  display: block;
  color: #c9d1d9;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 14px;
  line-height: 1.4;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.nav a:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}
.nav a.router-link-active {
  background: #1f6feb;
  color: #fff;
  font-weight: 600;
}
.nav button {
  margin-top: 16px;
  width: 100%;
}
</style>
