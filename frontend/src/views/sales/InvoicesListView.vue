<template>
  <div>
    <h1>فواتير البيع</h1>
    <table>
      <thead>
        <tr>
          <th>رقم الفاتورة</th>
          <th>العميل</th>
          <th>الإجمالي</th>
          <th>الحالة</th>
          <th>تاريخ الإنشاء</th>
          <th v-if="auth.hasPermission('invoices.cancel')">إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="invoice in invoices" :key="invoice.id">
          <td>{{ invoice.invoice_number }}</td>
          <td>{{ invoice.customer?.name || '-' }}</td>
          <td>{{ invoice.total_amount }}</td>
          <td>{{ statusLabel(invoice.status) }}</td>
          <td>{{ new Date(invoice.created_at).toLocaleString('ar-EG') }}</td>
          <td v-if="auth.hasPermission('invoices.cancel')">
            <button class="danger" v-if="invoice.status === 'pending_fulfillment'" @click="cancel(invoice)">إلغاء</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const invoices = ref([])

function statusLabel(status) {
  return { pending_fulfillment: 'بانتظار التجهيز', fulfilled: 'تم التجهيز', cancelled: 'ملغاة' }[status] || status
}

async function load() {
  const { data } = await http.get('/sales-invoices')
  invoices.value = data.data
}

async function cancel(invoice) {
  await http.post(`/sales-invoices/${invoice.id}/cancel`)
  await load()
}

onMounted(load)
</script>
