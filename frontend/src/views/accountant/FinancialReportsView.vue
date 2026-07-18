<template>
  <div class="reports-page">
    <h1>التقارير المالية</h1>
    <p class="subtitle">تقارير شهرية، تحليل إيرادات ومصروفات، وتصدير سريع</p>

    <div class="toolbar">
      <label>
        من
        <input v-model="from" type="date" />
      </label>
      <label>
        إلى
        <input v-model="to" type="date" />
      </label>
      <button class="primary" @click="loadAll">تحديث</button>
      <button class="secondary" @click="exportCsv">تصدير CSV</button>
      <button class="secondary" @click="printReport">PDF</button>
    </div>

    <div class="alerts card" v-if="alerts.length">
      <h2>تنبيهات مالية</h2>
      <div v-for="alert in alerts" :key="alert.title" class="alert-item">
        <strong>{{ alert.title }}</strong>
        <p>{{ alert.message }}</p>
      </div>
    </div>

    <div class="stats-grid">
      <div class="card stat-card">
        <div class="label">الإيرادات</div>
        <div class="value">{{ summary.revenue }}</div>
      </div>
      <div class="card stat-card">
        <div class="label">المصروفات</div>
        <div class="value">{{ summary.expenses }}</div>
      </div>
      <div class="card stat-card">
        <div class="label">الصافي</div>
        <div class="value">{{ summary.net }}</div>
      </div>
    </div>

    <div class="card">
      <h2>التقارير الشهرية</h2>
      <table>
        <thead>
          <tr>
            <th>الشهر</th>
            <th>المدين</th>
            <th>الدائن</th>
            <th>عدد القيود</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in monthly" :key="row.month">
            <td>{{ row.month }}</td>
            <td>{{ row.debit }}</td>
            <td>{{ row.credit }}</td>
            <td>{{ row.entries }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http from '../../api/http'

const from = ref('')
const to = ref('')
const monthly = ref([])
const summary = ref({ revenue: 0, expenses: 0, net: 0 })
const alerts = ref([])

async function loadAll() {
  const [monthlyResponse, summaryResponse, alertsResponse] = await Promise.all([
    http.get('/financial-reports/monthly', { params: { from: from.value, to: to.value } }),
    http.get('/financial-reports/summary', { params: { from: from.value, to: to.value } }),
    http.get('/financial-reports/alerts'),
  ])

  monthly.value = monthlyResponse.data || []
  summary.value = summaryResponse.data || { revenue: 0, expenses: 0, net: 0 }
  alerts.value = alertsResponse.data || []
}

function exportCsv() {
  const rows = [['الشهر', 'المدين', 'الدائن', 'عدد القيود']]
  monthly.value.forEach((row) => rows.push([row.month, row.debit, row.credit, row.entries]))
  const csv = rows.map((r) => r.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'financial-report.csv'
  link.click()
  URL.revokeObjectURL(url)
}

function printReport() {
  window.print()
}

onMounted(loadAll)
</script>

<style scoped>
.reports-page { display: grid; gap: 1rem; }
.subtitle { color: #64748b; margin-top: -0.4rem; }
.toolbar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
.toolbar label { display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.95rem; }
.toolbar input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.45rem 0.6rem; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.stat-card { padding: 1rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
table { width: 100%; border-collapse: collapse; }
th, td { border-bottom: 1px solid #e2e8f0; padding: 0.65rem; text-align: start; }
.alert-item { border: 1px solid #fde68a; background: #fffbeb; padding: 0.7rem; border-radius: 8px; margin-top: 0.5rem; }
.primary { background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 0.55rem 0.85rem; }
.secondary { background: #f1f5f9; color: #0f172a; border: none; border-radius: 6px; padding: 0.55rem 0.85rem; }
@media print { .toolbar, .alerts { display: none; } }
</style>
