import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'dashboard', component: () => import('../views/DashboardView.vue') },
      { path: 'notifications', name: 'notifications', component: () => import('../views/NotificationsView.vue') },

      // Sales
      {
        path: 'sales/invoices/new',
        name: 'sales-invoice-new',
        component: () => import('../views/sales/CreateInvoiceView.vue'),
        meta: { permission: 'invoices.create' },
      },
      {
        path: 'sales/invoices',
        name: 'sales-invoices',
        component: () => import('../views/sales/InvoicesListView.vue'),
        meta: { permission: 'invoices.view' },
      },
      {
        path: 'sales/invoices/:id',
        name: 'sales-invoice-detail',
        component: () => import('../views/sales/InvoiceDetailView.vue'),
        meta: { permission: 'invoices.view' },
      },
      {
        path: 'stock/lookup',
        name: 'stock-lookup',
        component: () => import('../views/sales/StockLookupView.vue'),
        meta: { anyPermission: ['stock.view_readonly', 'stock.manage', 'production.view_raw_stock'] },
      },
      {
        path: 'sales/customers',
        name: 'sales-customers',
        component: () => import('../views/sales/CustomersView.vue'),
        meta: { permission: 'customers.manage' },
      },

      // Storekeeper
      {
        path: 'storekeeper/receive',
        name: 'storekeeper-receive',
        component: () => import('../views/storekeeper/ReceiveStockView.vue'),
        meta: { permission: 'stock.manage' },
      },
      {
        path: 'storekeeper/issue',
        name: 'storekeeper-issue',
        component: () => import('../views/storekeeper/IssueStockView.vue'),
        meta: { permission: 'stock.manage' },
      },
      {
        path: 'storekeeper/count',
        name: 'storekeeper-count',
        component: () => import('../views/storekeeper/StockCountView.vue'),
        meta: { permission: 'stock.manage' },
      },
      {
        path: 'storekeeper/products',
        name: 'storekeeper-products',
        component: () => import('../views/admin/ProductsView.vue'),
        meta: { anyPermission: ['stock.manage', 'products.manage'] },
      },
      {
        path: 'storekeeper/fulfill',
        name: 'storekeeper-fulfill',
        component: () => import('../views/storekeeper/FulfillInvoicesView.vue'),
        meta: { permission: 'stock.manage' },
      },
      {
        path: 'storekeeper/suppliers',
        name: 'storekeeper-suppliers',
        component: () => import('../views/storekeeper/SuppliersView.vue'),
        meta: { permission: 'suppliers.manage' },
      },

      // Production
      {
        path: 'production/orders',
        name: 'production-orders',
        component: () => import('../views/production/ProductionOrdersView.vue'),
        meta: { permission: 'production.manage' },
      },

      // Accounting
      {
        path: 'accounting',
        name: 'accounting-dashboard',
        component: () => import('../views/accountant/AccountingDashboardView.vue'),
        meta: { permission: 'accounting.view' },
      },
      {
        path: 'accounting/invoices',
        name: 'accounting-invoices',
        component: () => import('../views/accountant/InvoiceApprovalsView.vue'),
        meta: { permission: 'invoices.approve' },
      },
      {
        path: 'accounting/journal',
        name: 'accounting-journal',
        component: () => import('../views/accountant/JournalEntriesView.vue'),
        meta: { permission: 'accounting.view' },
      },
      {
        path: 'accounting/reports',
        name: 'accounting-reports',
        component: () => import('../views/accountant/FinancialReportsView.vue'),
        meta: { permission: 'accounting.view' },
      },
      {
        path: 'accounting/receivables',
        name: 'accounting-receivables',
        component: () => import('../views/accountant/ReceivablesView.vue'),
        meta: { permission: 'accounting.view' },
      },
      {
        path: 'accounting/automation',
        name: 'accounting-automation',
        component: () => import('../views/accountant/AutomationView.vue'),
        meta: { permission: 'accounting.view' },
      },
      {
        path: 'accounting/trial-balance',
        name: 'accounting-trial-balance',
        component: () => import('../views/accountant/TrialBalanceView.vue'),
        meta: { permission: 'accounting.view' },
      },
      {
        path: 'admin/activity-logs',
        name: 'admin-activity-logs',
        component: () => import('../views/accountant/ActivityLogsView.vue'),
        meta: { role: 'admin' },
      },

      // Admin
      {
        path: 'admin/users',
        name: 'admin-users',
        component: () => import('../views/admin/UsersView.vue'),
        meta: { role: 'admin' },
      },
      {
        path: 'admin/products',
        name: 'admin-products',
        component: () => import('../views/admin/ProductsView.vue'),
        meta: { role: 'admin' },
      },
      {
        path: 'admin/warehouses',
        name: 'admin-warehouses',
        component: () => import('../views/admin/WarehousesView.vue'),
        meta: { role: 'admin' },
      },
    ],
  },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundView.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.role && !auth.hasRole(to.meta.role)) {
    return { name: 'dashboard' }
  }

  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return { name: 'dashboard' }
  }

  if (to.meta.anyPermission && !to.meta.anyPermission.some((p) => auth.hasPermission(p))) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
