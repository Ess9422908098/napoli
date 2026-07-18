<template>
  <div>
    <h1>طلبات الفواتير المعلقة لاعتماد المحاسب</h1>

    <div class="toolbar">
      <label>
        الحالة
        <select v-model="approvalFilter" @change="loadInvoices">
          <option value="pending">الكل</option>
          <option value="pending">بانتظار الاعتماد</option>
          <option value="approved">معتمدة</option>
          <option value="rejected">مرفوضة</option>
        </select>
      </label>
      <button class="secondary" @click="printPage">طباعة PDF</button>
    </div>

    <p v-if="loading">جاري التحميل...</p>
    <p class="alert-error" v-if="error">{{ error }}</p>

    <table v-if="invoices.length">
      <thead>
        <tr>
          <th>رقم الفاتورة</th>
          <th>العميل</th>
          <th>الإجمالي</th>
          <th>الحالة</th>
          <th>الاعتماد</th>
          <th>الموظف</th>
          <th>الإجراء</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="invoice in invoices" :key="invoice.id">
          <td>{{ invoice.invoice_number }}</td>
          <td>{{ invoice.customer?.name || '—' }}</td>
          <td>{{ invoice.total_amount }}</td>
          <td>{{ statusLabel(invoice.status) }}</td>
          <td>{{ approvalLabel(invoice.approval_status) }}</td>
          <td>{{ invoice.approver?.name || '—' }}</td>
          <td>
            <button class="primary" v-if="invoice.approval_status === 'pending'" @click="approve(invoice)">اعتماد</button>
            <button class="secondary" @click="viewInvoice(invoice)">عرض</button>
          </td>
        </tr>
      </tbody>
    </table>

    <p v-else class="empty">لا توجد طلبات فواتير في الوقت الحالي.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import http from '../../api/http'

const router = useRouter()
const invoices = ref([])
const approvalFilter = ref('pending')
const loading = ref(false)
const error = ref('')

function statusLabel(status) {
  return { pending_fulfillment: 'بانتظار التجهيز', fulfilled: 'تم التجهيز', cancelled: 'ملغاة' }[status] || status
}

function approvalLabel(value) {
  return { pending: 'بانتظار الاعتماد', approved: 'معتمدة', rejected: 'مرفوضة' }[value] || value
}

async function loadInvoices() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/sales-invoices', { params: { approval_status: approvalFilter.value } })
    invoices.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || 'خطأ في تحميل الطلبات.'
  } finally {
    loading.value = false
  }
}

async function approve(invoice) {
  try {
    await http.post(`/sales-invoices/${invoice.id}/approve`)
    await loadInvoices()
  } catch (e) {
    error.value = e.response?.data?.message || 'خطأ في اعتماد الفاتورة.'
  }
}

function viewInvoice(invoice) {
  router.push({ name: 'sales-invoice-detail', params: { id: invoice.id } })
}

function printPage() {
  window.print()
}

onMounted(loadInvoices)
</script>

<style scoped>
.toolbar { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
.toolbar label { display: flex; flex-direction: column; gap: 6px; font-size: 14px; }
.toolbar button { padding: 10px 14px; }
button.primary { background: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; }
button.secondary { background: #f3f4f6; color: #111; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 6px; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th, td { border: 1px solid #e2e8f0; padding: 12px 10px; text-align: right; }
th { background: #f8fafc; }
.empty { color: #64748b; }
@media print { .toolbar, button { display: none; } table { page-break-inside: avoid; } }
</style>
