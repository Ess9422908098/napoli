<template>
  <div>
    <h1>سجل النشاط والتعديلات</h1>

    <div class="toolbar">
      <label>
        المستخدم
        <select v-model="selectedUser">
          <option value="">الكل</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
        </select>
      </label>

      <label>
        نوع العملية
        <select v-model="selectedAction">
          <option value="">الكل</option>
          <option value="created">إضافة</option>
          <option value="updated">تعديل</option>
          <option value="deleted">حذف</option>
          <option value="stock_received">وارد</option>
          <option value="stock_issued">صادر</option>
          <option value="stock_counted">جرد</option>
        </select>
      </label>

      <label>
        من تاريخ
        <input type="date" v-model="fromDate" />
      </label>

      <label>
        إلى تاريخ
        <input type="date" v-model="toDate" />
      </label>

      <button @click="applyFilters">تصفية</button>
      <button class="secondary" @click="exportCsv">تصدير CSV</button>
      <button class="secondary" @click="exportPdf">تصدير PDF</button>
    </div>

    <p v-if="loading">جاري التحميل...</p>
    <table>
      <thead>
        <tr>
          <th>المستخدم</th>
          <th>الإجراء</th>
          <th>العنصر</th>
          <th>البيانات</th>
          <th>الوصف</th>
          <th>الوقت</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="log in filteredLogs" :key="log.id">
          <td>{{ log.user?.name || '—' }}</td>
          <td>{{ translateAction(log.action) }}</td>
          <td>{{ log.subject_name || '—' }}</td>
          <td>{{ formatMeta(log.meta) }}</td>
          <td>{{ log.description }}</td>
          <td>{{ formatDate(log.created_at) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import http from '../../api/http'

const logs = ref([])
const users = ref([])
const loading = ref(false)
const selectedUser = ref('')
const selectedAction = ref('')
const fromDate = ref('')
const toDate = ref('')

async function load() {
  loading.value = true
  try {
    const [{ data: logsData }, { data: usersData }] = await Promise.all([
      http.get('/activity-logs'),
      http.get('/users'),
    ])
    logs.value = logsData.data || []
    users.value = usersData || []
  } finally {
    loading.value = false
  }
}

const filteredLogs = computed(() => {
  return logs.value.filter((log) => {
    const matchesUser = !selectedUser.value || String(log.user_id) === String(selectedUser.value)
    const matchesAction = !selectedAction.value || log.action === selectedAction.value
    const logDate = log.created_at ? new Date(log.created_at) : null
    const from = fromDate.value ? new Date(fromDate.value) : null
    const to = toDate.value ? new Date(toDate.value) : null

    const matchesFrom = !from || !logDate || logDate >= from
    const matchesTo = !to || !logDate || logDate <= to
    return matchesUser && matchesAction && matchesFrom && matchesTo
  })
})

function applyFilters() {
  return filteredLogs.value
}

function exportCsv() {
  const rows = filteredLogs.value.map((log) => ({
    المستخدم: log.user?.name || '—',
    الإجراء: translateAction(log.action),
    العنصر: log.subject_name || '—',
    البيانات: formatMeta(log.meta),
    الوصف: log.description || '',
    الوقت: formatDate(log.created_at),
  }))

  const headers = ['المستخدم', 'الإجراء', 'العنصر', 'البيانات', 'الوصف', 'الوقت']
  const csv = [headers.join(','), ...rows.map((row) => headers.map((key) => `"${String(row[key] || '').replace(/"/g, '""')}"`).join(','))].join('\n')

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'activity-log.csv'
  link.click()
  URL.revokeObjectURL(url)
}

function exportPdf() {
  const printWindow = window.open('', '_blank', 'width=900,height=700')
  if (!printWindow) return

  const rows = filteredLogs.value.map((log) => `
    <tr>
      <td>${(log.user?.name || '—').replace(/</g, '&lt;')}</td>
      <td>${translateAction(log.action).replace(/</g, '&lt;')}</td>
      <td>${(log.subject_name || '—').replace(/</g, '&lt;')}</td>
      <td>${(formatMeta(log.meta)).replace(/</g, '&lt;')}</td>
      <td>${(log.description || '').replace(/</g, '&lt;')}</td>
      <td>${formatDate(log.created_at)}</td>
    </tr>`).join('')

  printWindow.document.write(`
    <html>
      <head>
        <title>سجل النشاط</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 20px; direction: rtl; }
          table { width: 100%; border-collapse: collapse; font-size: 12px; }
          th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
          th { background: #f2f2f2; }
        </style>
      </head>
      <body>
        <h2>سجل النشاط والتعديلات</h2>
        <table>
          <thead>
            <tr>
              <th>المستخدم</th>
              <th>الإجراء</th>
              <th>العنصر</th>
              <th>البيانات</th>
              <th>الوصف</th>
              <th>الوقت</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </body>
    </html>
  `)
  printWindow.document.close()
  printWindow.focus()
  printWindow.print()
}

function translateAction(action) {
  const map = {
    created: 'إضافة',
    updated: 'تعديل',
    deleted: 'حذف',
    stock_received: 'وارد',
    stock_issued: 'صادر',
    stock_counted: 'جرد',
  }
  return map[action] || action
}

function formatMeta(meta) {
  if (!meta) return '—'
  if (typeof meta === 'string') return meta
  return Object.entries(meta)
    .map(([key, value]) => `${key}: ${value}`)
    .join(' | ')
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('ar-EG')
}

onMounted(load)
</script>

<style scoped>
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: end;
  margin: 12px 0 16px;
}
.toolbar label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}
.toolbar input,
.toolbar select,
.toolbar button {
  padding: 8px;
}
button.secondary {
  background: #f2f2f2;
  color: #111;
}
</style>
