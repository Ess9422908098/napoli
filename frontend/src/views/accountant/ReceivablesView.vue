<template>
  <div class="receivables-page">
    <h1>الذمم المدينة والدائنة</h1>
    <p class="subtitle">متابعة ديون العملاء والموردين وكشوف الحسابات</p>

    <div class="toolbar">
      <button class="primary" @click="loadAll">تحديث</button>
      <button class="secondary" @click="exportExcel">تصدير Excel</button>
      <button class="secondary" @click="printPage">طباعة PDF</button>
    </div>

    <div class="cards-grid">
      <section class="card">
        <h2>العملاء</h2>
        <table>
          <thead>
            <tr><th>الاسم</th><th>الإجمالي</th><th>الرصيد</th></tr>
          </thead>
          <tbody>
            <tr v-for="item in customers" :key="item.id" @click="openCustomerStatement(item.id)" class="clickable-row">
              <td>{{ item.name }}</td>
              <td>{{ item.total_invoiced }}</td>
              <td>{{ item.balance }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="card">
        <h2>الموردين</h2>
        <table>
          <thead>
            <tr><th>الاسم</th><th>الإجمالي</th><th>الرصيد</th></tr>
          </thead>
          <tbody>
            <tr v-for="item in suppliers" :key="item.id" @click="openSupplierStatement(item.id)" class="clickable-row">
              <td>{{ item.name }}</td>
              <td>{{ item.total_purchased }}</td>
              <td>{{ item.balance }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>

    <section v-if="selectedStatement" class="card statement-card">
      <h2>{{ selectedStatement.title }}</h2>
      <p>{{ selectedStatement.subtitle }}</p>
      <div class="summary-box">
        <strong>إجمالي الرصيد:</strong> {{ totalStatementBalance }}
      </div>

      <div class="filters">
        <label>
          من
          <input v-model="fromDate" type="date" />
        </label>
        <label>
          إلى
          <input v-model="toDate" type="date" />
        </label>
        <button class="primary" @click="applyDateFilter">تطبيق</button>
        <button class="secondary" @click="resetDateFilter">إعادة تعيين</button>
        <button class="secondary" @click="exportStatementExcel">تصدير Excel</button>
        <button class="secondary" @click="printStatement">PDF</button>
      </div>

      <table>
        <thead>
          <tr>
            <th>الرقم</th>
            <th>التاريخ</th>
            <th>الحالة</th>
            <th>المبلغ</th>
            <th>الرصيد المتبقي</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filteredStatementRows" :key="row.id">
            <td>{{ row.reference }}</td>
            <td>{{ row.date }}</td>
            <td>{{ row.status }}</td>
            <td>{{ row.amount }}</td>
            <td :class="getBalanceClass(row.remaining)">{{ row.remaining }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import http from '../../api/http'

const customers = ref([])
const suppliers = ref([])
const selectedStatement = ref(null)
const fromDate = ref('')
const toDate = ref('')

const filteredStatementRows = computed(() => {
  if (!selectedStatement.value?.rows) return []

  const from = fromDate.value ? new Date(`${fromDate.value}T00:00:00`) : null
  const to = toDate.value ? new Date(`${toDate.value}T23:59:59`) : null

  return selectedStatement.value.rows.filter((row) => {
    const rowDate = new Date(row.date)
    if (Number.isNaN(rowDate.getTime())) return true
    if (from && rowDate < from) return false
    if (to && rowDate > to) return false
    return true
  })
})

const totalStatementBalance = computed(() => {
  if (!filteredStatementRows.value.length) return 0
  return filteredStatementRows.value.reduce((sum, row) => sum + Number(row.remaining || 0), 0)
})

async function loadAll() {
  const [customersResponse, suppliersResponse] = await Promise.all([
    http.get('/receivables/customers'),
    http.get('/receivables/suppliers'),
  ])
  customers.value = customersResponse.data || []
  suppliers.value = suppliersResponse.data || []
}

async function openCustomerStatement(customerId) {
  const { data } = await http.get(`/receivables/customers/${customerId}`)
  selectedStatement.value = {
    title: `كشف حساب ${data.customer?.name || 'العميل'}`,
    subtitle: 'سجل المعاملات مع التواريخ والحالات',
    rows: (data.invoices || [])
      .slice()
      .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
      .map((invoice, index, list) => ({
        id: invoice.id,
        reference: invoice.invoice_number,
        date: invoice.created_at,
        status: invoice.status,
        amount: invoice.total_amount,
        remaining: list.slice(index).reduce((sum, row) => sum + Number(row.total_amount || 0), 0),
      })),
  }
  fromDate.value = ''
  toDate.value = ''
}

async function openSupplierStatement(supplierId) {
  const { data } = await http.get(`/receivables/suppliers/${supplierId}`)
  selectedStatement.value = {
    title: `كشف حساب ${data.supplier?.name || 'المورد'}`,
    subtitle: 'سجل الطلبات مع التواريخ والحالات',
    rows: (data.orders || [])
      .slice()
      .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
      .map((order, index, list) => ({
        id: order.id,
        reference: order.order_number,
        date: order.created_at,
        status: order.status,
        amount: order.total_amount,
        remaining: list.slice(index).reduce((sum, row) => sum + Number(row.total_amount || 0), 0),
      })),
  }
  fromDate.value = ''
  toDate.value = ''
}

function applyDateFilter() {
  return true
}

function resetDateFilter() {
  fromDate.value = ''
  toDate.value = ''
}

function getBalanceClass(value) {
  const numeric = Number(value || 0)
  if (numeric > 0) return 'balance-positive'
  if (numeric < 0) return 'balance-negative'
  return 'balance-neutral'
}

function exportExcel() {
  const rows = [['النوع', 'الاسم', 'الإجمالي', 'الرصيد']]
  customers.value.forEach((item) => rows.push(['عميل', item.name, item.total_invoiced, item.balance]))
  suppliers.value.forEach((item) => rows.push(['مورد', item.name, item.total_purchased, item.balance]))
  const csv = rows.map((r) => r.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'receivables.csv'
  link.click()
  URL.revokeObjectURL(url)
}

function exportStatementExcel() {
  if (!selectedStatement.value?.rows) return
  const rows = [['الرقم', 'التاريخ', 'الحالة', 'المبلغ', 'الرصيد المتبقي']]
  filteredStatementRows.value.forEach((row) => rows.push([row.reference, row.date, row.status, row.amount, row.remaining]))
  const csv = rows.map((r) => r.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${selectedStatement.value.title || 'statement'}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

function printStatement() {
  window.print()
}

function printPage() {
  window.print()
}

onMounted(loadAll)
</script>

<style scoped>
.receivables-page { display: grid; gap: 1rem; }
.subtitle { color: #64748b; margin-top: -0.4rem; }
.toolbar { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.filters { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: end; margin-bottom: 0.75rem; }
.filters label { display: flex; flex-direction: column; gap: 0.25rem; }
.filters input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.45rem 0.6rem; }
.cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
table { width: 100%; border-collapse: collapse; }
th, td { border-bottom: 1px solid #e2e8f0; padding: 0.6rem; text-align: start; }
.clickable-row { cursor: pointer; }
.statement-card { margin-top: 0.5rem; }
.summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.6rem 0.8rem; margin: 0.5rem 0 0.8rem; font-size: 0.95rem; }
.balance-positive { color: #15803d; font-weight: 700; }
.balance-negative { color: #dc2626; font-weight: 700; }
.balance-neutral { color: #475569; }
.primary { background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 0.55rem 0.85rem; }
.secondary { background: #f1f5f9; color: #0f172a; border: none; border-radius: 6px; padding: 0.55rem 0.85rem; }
@media print { .toolbar { display: none; } }
</style>
