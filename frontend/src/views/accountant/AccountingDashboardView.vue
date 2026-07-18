<template>
  <div class="accounting-dashboard">
    <h1>لوحة المحاسب</h1>
    <p class="subtitle">ملخص سريع للعمليات المالية والتقارير الأخيرة</p>

    <div class="stats-grid">
      <div class="card stat-card">
        <div class="label">الفواتير المعلقة</div>
        <div class="value">{{ summary.pending_invoices }}</div>
      </div>
      <div class="card stat-card">
        <div class="label">الفواتير المنفذة</div>
        <div class="value">{{ summary.fulfilled_invoices }}</div>
      </div>
      <div class="card stat-card">
        <div class="label">إجمالي مدين</div>
        <div class="value">{{ summary.total_debit }}</div>
      </div>
      <div class="card stat-card">
        <div class="label">إجمالي دائن</div>
        <div class="value">{{ summary.total_credit }}</div>
      </div>
    </div>

    <div class="content-grid">
      <section class="card">
        <h2>أحدث القيود</h2>
        <ul v-if="entries.length" class="list">
          <li v-for="entry in entries" :key="entry.id">
            <span>{{ entry.description }}</span>
            <small>{{ entry.entry_number }} • {{ entry.entry_date }}</small>
          </li>
        </ul>
        <p v-else class="empty">لا توجد قيود حديثة.</p>
      </section>

      <section class="card">
        <h2>روابط سريعة</h2>
        <div class="links">
          <router-link to="/accounting/journal">القيود المحاسبية</router-link>
          <router-link to="/accounting/trial-balance">ميزان المراجعة</router-link>
          <router-link to="/accounting/activity-logs">سجل النشاط والتعديلات</router-link>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http from '../../api/http'

const summary = ref({
  pending_invoices: 0,
  fulfilled_invoices: 0,
  recent_entries: 0,
  total_debit: 0,
  total_credit: 0,
})
const entries = ref([])

onMounted(async () => {
  try {
    const { data } = await http.get('/accounting/dashboard')
    summary.value = data.summary || summary.value
    entries.value = data.entries || []
  } catch (error) {
    console.error('Failed to load accounting dashboard data', error)
  }
})
</script>

<style scoped>
.accounting-dashboard { display: grid; gap: 1rem; }
.subtitle { color: #64748b; margin-top: -0.4rem; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.stat-card { padding: 1rem; }
.stat-card .label { color: #64748b; font-size: 0.95rem; }
.stat-card .value { font-size: 1.8rem; font-weight: 700; margin-top: 0.4rem; }
.content-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.7rem; }
.list li { display: flex; justify-content: space-between; gap: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; }
.list small { color: #64748b; }
.links { display: grid; gap: 0.75rem; }
.links a { color: #2563eb; text-decoration: none; font-weight: 600; }
.empty { color: #64748b; }
</style>
