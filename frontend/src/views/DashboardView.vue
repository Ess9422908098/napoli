<template>
  <div class="dashboard">
    <h1>مرحباً، {{ auth.user?.name }}</h1>
    <p class="subtitle">دورك الحالي: <strong>{{ auth.user?.role_name }}</strong></p>

    <div class="stats-grid">
      <div class="card stat-card">
        <div class="label">الفواتير المعلقة</div>
        <div class="value">{{ summary.pending_invoices }}</div>
      </div>
      <div class="card stat-card">
        <div class="label">أوامر الإنتاج قيد التنفيذ</div>
        <div class="value">{{ summary.production_orders_in_progress }}</div>
      </div>
      <div class="card stat-card">
        <div class="label">عناصر منخفضة المخزون</div>
        <div class="value">{{ summary.low_stock_products }}</div>
      </div>
    </div>

    <div class="content-grid">
      <section class="card">
        <h2>آخر الحركات</h2>
        <ul v-if="recentMovements.length" class="list">
          <li v-for="movement in recentMovements" :key="movement.id">
            <span>{{ movement.product_name || 'عنصر' }}</span>
            <small>{{ movement.type }} • {{ movement.warehouse_name || '—' }}</small>
          </li>
        </ul>
        <p v-else class="empty">لا توجد حركات حديثة حالياً.</p>
      </section>

      <section class="card">
        <h2>أوامر الإنتاج</h2>
        <ul v-if="operations.length" class="list">
          <li v-for="order in operations" :key="order.id">
            <span>{{ order.finished_product?.name || order.order_number }}</span>
            <small>{{ order.quantity_to_produce }} • {{ order.status }}</small>
          </li>
        </ul>
        <p v-else class="empty">لا توجد أوامر حالياً.</p>
      </section>

      <section class="card">
        <h2>عناصر تحتاج متابعة</h2>
        <ul v-if="lowStockItems.length" class="list">
          <li v-for="item in lowStockItems" :key="`${item.product_name}-${item.warehouse_name}`">
            <span>{{ item.product_name }}</span>
            <small>{{ item.quantity }} قطعة • محجوزة {{ item.reserved_quantity }}</small>
          </li>
        </ul>
        <p v-else class="empty">لا توجد عناصر منخفضة المخزون.</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http from '../api/http'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const summary = ref({
  pending_invoices: 0,
  production_orders_in_progress: 0,
  low_stock_products: 0,
})
const recentMovements = ref([])
const lowStockItems = ref([])
const operations = ref([])

onMounted(async () => {
  try {
    const [dashboardResponse, operationsResponse] = await Promise.all([
      http.get('/dashboard'),
      http.get('/operations', { params: { status: 'in_progress' } }),
    ])

    summary.value = dashboardResponse.data.summary || summary.value
    recentMovements.value = dashboardResponse.data.recent_movements || []
    lowStockItems.value = dashboardResponse.data.low_stock_items || []
    operations.value = operationsResponse.data.data || []
  } catch (error) {
    console.error('Failed to load dashboard data', error)
  }
})
</script>

<style scoped>
.dashboard { display: grid; gap: 1rem; }
.subtitle { color: #64748b; margin-top: -0.5rem; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.stat-card { padding: 1rem; }
.stat-card .label { color: #64748b; font-size: 0.95rem; }
.stat-card .value { font-size: 2rem; font-weight: 700; margin-top: 0.4rem; }
.content-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.7rem; }
.list li { display: flex; justify-content: space-between; gap: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; }
.list small { color: #64748b; }
.empty { color: #64748b; }
</style>
