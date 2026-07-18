<template>
  <div>
    <h1>تفاصيل الفاتورة</h1>
    <p v-if="error" class="alert-error">{{ error }}</p>
    <div v-if="invoice">
      <div class="card invoice-header">
        <div>
          <h2>{{ companyName }}</h2>
          <h3>فاتورة رقم {{ invoice.invoice_number }}</h3>
          <p>العميل: {{ invoice.customer?.name || '—' }}</p>
          <p>حالة الفاتورة: {{ statusLabel(invoice.status) }}</p>
          <p>حالة الاعتماد: {{ approvalLabel(invoice.approval_status) }}</p>
          <p>المحاسب المعتمد: {{ invoice.approver?.name || '—' }}</p>
        </div>
        <button class="secondary" @click="printInvoice">طباعة PDF</button>
      </div>

      <table>
        <thead>
          <tr>
            <th>المنتج</th>
            <th>المخزن</th>
            <th>الكمية</th>
            <th>سعر الوحدة</th>
            <th>الإجمالي</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in invoice.items" :key="item.id">
            <td>{{ item.product?.name || '—' }}</td>
            <td>{{ item.warehouse?.name || '—' }}</td>
            <td>{{ item.quantity }}</td>
            <td>{{ item.unit_price }}</td>
            <td>{{ item.line_total }}</td>
          </tr>
        </tbody>
      </table>

      <div class="card invoice-summary">
        <p>الإجمالي: {{ invoice.total_amount }}</p>
        <p>ملاحظات: {{ invoice.notes || 'لا توجد ملاحظات' }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import http from '../../api/http'

const companyName = import.meta.env.VITE_COMPANY_NAME || 'اسم الشركة'
const route = useRoute()
const invoice = ref(null)
const error = ref('')

function statusLabel(status) {
  return { pending_fulfillment: 'بانتظار التجهيز', fulfilled: 'تم التجهيز', cancelled: 'ملغاة' }[status] || status
}

function approvalLabel(value) {
  return { pending: 'بانتظار الاعتماد', approved: 'معتمدة', rejected: 'مرفوضة' }[value] || value
}

async function loadInvoice() {
  error.value = ''
  try {
    const { data } = await http.get(`/sales-invoices/${route.params.id}`)
    invoice.value = data
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر تحميل بيانات الفاتورة.'
  }
}

function printInvoice() {
  const doc = window.open('', '_blank', 'width=900,height=700')
  if (!doc) return

  const rows = invoice.value.items.map((item) => `
        <tr>
          <td>${item.product?.name || '—'}</td>
          <td>${item.warehouse?.name || '—'}</td>
          <td>${item.quantity}</td>
          <td>${item.unit_price}</td>
          <td>${item.line_total}</td>
        </tr>`).join('')

  doc.document.write(`
    <html>
      <head>
        <title>فاتورة ${invoice.value.invoice_number}</title>
        <style>
          body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
          th { background: #f3f4f6; }
        </style>
      </head>
      <body>
        <h1>فاتورة رقم ${invoice.value.invoice_number}</h1>
        <p>العميل: ${invoice.value.customer?.name || '—'}</p>
        <p>حالة الفاتورة: ${statusLabel(invoice.value.status)}</p>
        <p>حالة الاعتماد: ${approvalLabel(invoice.value.approval_status)}</p>
        <table>
          <thead>
            <tr><th>المنتج</th><th>المخزن</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
        <p>الإجمالي: ${invoice.value.total_amount}</p>
        <p>ملاحظات: ${invoice.value.notes || 'لا توجد ملاحظات'}</p>
      </body>
    </html>
  `)
  doc.document.close()
  doc.print()
}

onMounted(loadInvoice)
</script>

<style scoped>
.card { padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 16px; }
.invoice-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th, td { border: 1px solid #e2e8f0; padding: 10px; }
button.secondary { background: #f3f4f6; color: #111; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 8px; }
.alert-error { color: #b91c1c; margin-bottom: 16px; }
</style>
