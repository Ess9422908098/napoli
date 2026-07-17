<template>
  <div>
    <h1>تجهيز الفواتير (بانتظار التجهيز)</h1>
    <table>
      <thead>
        <tr>
          <th>رقم الفاتورة</th>
          <th>العميل</th>
          <th>عدد الأصناف</th>
          <th>الإجمالي</th>
          <th>إجراء</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="invoice in invoices" :key="invoice.id">
          <td>{{ invoice.invoice_number }}</td>
          <td>{{ invoice.customer?.name || '-' }}</td>
          <td>{{ invoice.items.length }}</td>
          <td>{{ invoice.total_amount }}</td>
          <td><button @click="fulfill(invoice)">تأكيد التجهيز والتسليم</button></td>
        </tr>
      </tbody>
    </table>
    <p v-if="!invoices.length" class="card">لا توجد فواتير بانتظار التجهيز حالياً.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const invoices = ref([])

async function load() {
  const { data } = await http.get('/sales-invoices', { params: { status: 'pending_fulfillment' } })
  invoices.value = data.data
}

async function fulfill(invoice) {
  await http.patch(`/sales-invoices/${invoice.id}/fulfill`)
  await load()
}

onMounted(load)
</script>
