<template>
  <div>
    <h1>أوامر الشراء والمصاريف</h1>
    <p class="subtitle">عرض أوامر الشراء، حالة الدفع، وتسديد الموردين من دون مغادرة النظام.</p>

    <div class="toolbar">
      <button class="primary" @click="loadOrders">تحديث</button>
      <button class="secondary" @click="filterReceived = !filterReceived">
        {{ filterReceived ? 'إظهار الكل' : 'عرض المستلمة فقط' }}
      </button>
    </div>

    <p v-if="error" class="alert-error">{{ error }}</p>
    <p v-if="loading">جاري التحميل...</p>

    <table v-if="orders.length">
      <thead>
        <tr>
          <th>رقم الطلب</th>
          <th>المورد</th>
          <th>المخزن</th>
          <th>الحالة</th>
          <th>حالة الدفع</th>
          <th>الإجمالي</th>
          <th>المدفوع</th>
          <th>المتبقي</th>
          <th>تاريخ الاستلام</th>
          <th>إجراء</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in visibleOrders" :key="order.id">
          <td>{{ order.order_number }}</td>
          <td>{{ order.supplier?.name || '—' }}</td>
          <td>{{ order.warehouse?.name || '—' }}</td>
          <td>{{ statusLabel(order.status) }}</td>
          <td>{{ paymentStatusLabel(order.payment_status) }}</td>
          <td>{{ order.total_amount }}</td>
          <td>{{ order.paid_amount ?? 0 }}</td>
          <td :class="balanceClass(order)">{{ remainingAmount(order) }}</td>
          <td>{{ order.received_at ? new Date(order.received_at).toLocaleDateString('ar-EG') : '—' }}</td>
          <td>
            <button
              class="primary"
              v-if="order.status === 'received' && remainingAmount(order) > 0"
              @click="pay(order)"
            >تسديد</button>
            <span v-else>—</span>
          </td>
        </tr>
      </tbody>
    </table>

    <p v-else class="empty">لا توجد أوامر شراء حالياً.</p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import http from '../../api/http'

const orders = ref([])
const loading = ref(false)
const error = ref('')
const filterReceived = ref(false)

const visibleOrders = computed(() => {
  if (!filterReceived.value) {
    return orders.value
  }
  return orders.value.filter((order) => order.status === 'received')
})

function statusLabel(status) {
  return {
    draft: 'مسودة',
    submitted: 'مُرسلة',
    received: 'تم الاستلام',
    cancelled: 'ملغاة',
  }[status] || status
}

function paymentStatusLabel(status) {
  return {
    pending: 'قيد الانتظار',
    partial: 'مدفوعة جزئياً',
    paid: 'مدفوعة',
  }[status] || status
}

function remainingAmount(order) {
  return Number(order.total_amount || 0) - Number(order.paid_amount || 0)
}

function balanceClass(order) {
  const value = remainingAmount(order)
  if (value > 0) return 'balance-negative'
  if (value === 0) return 'balance-positive'
  return 'balance-neutral'
}

async function loadOrders() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await http.get('/purchase-orders')
    orders.value = data.data || []
  } catch (err) {
    error.value = err.response?.data?.message || 'تعذر تحميل أوامر الشراء.'
  } finally {
    loading.value = false
  }
}

async function pay(order) {
  const amount = parseFloat(window.prompt(`أدخل مبلغ الدفع لأمر الشراء ${order.order_number}`, remainingAmount(order)))
  if (Number.isNaN(amount) || amount <= 0) {
    return
  }

  try {
    await http.patch(`/purchase-orders/${order.id}/pay`, { amount })
    await loadOrders()
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل تسجيل الدفع.'
  }
}

onMounted(loadOrders)
</script>

<style scoped>
.subtitle { color: #64748b; margin-top: -0.4rem; }
.toolbar { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem; }
.primary, .secondary { border: none; border-radius: 6px; padding: 0.55rem 0.9rem; cursor: pointer; }
.primary { background: #2563eb; color: white; }
.secondary { background: #f1f5f9; color: #0f172a; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: right; }
th { background: #f8fafc; }
.alert-error { color: #b91c1c; margin-bottom: 12px; }
.empty { color: #64748b; }
.balance-positive { color: #15803d; font-weight: 700; }
.balance-negative { color: #b91c1c; font-weight: 700; }
.balance-neutral { color: #475569; }
</style>
