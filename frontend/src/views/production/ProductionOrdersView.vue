<template>
  <div>
    <h1>أوامر التصنيع</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <h3>أمر تصنيع جديد</h3>
      <label>المنتج التام</label>
      <select v-model="form.finished_product_id">
        <option v-for="p in finishedProducts" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
      </select>

      <label>الكمية المطلوب إنتاجها</label>
      <input type="number" min="0.0001" step="0.0001" v-model.number="form.quantity_to_produce" />

      <label>مخزن المواد الخام</label>
      <select v-model="form.raw_warehouse_id">
        <option v-for="w in rawWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>

      <label>مخزن المنتج التام</label>
      <select v-model="form.finished_warehouse_id">
        <option v-for="w in finishedWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>

      <button @click="createOrder" :disabled="loading">{{ loading ? '...جاري الإنشاء' : 'إنشاء أمر التصنيع' }}</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>رقم الأمر</th>
          <th>المنتج</th>
          <th>الكمية</th>
          <th>الحالة</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in orders" :key="order.id">
          <td>{{ order.order_number }}</td>
          <td>{{ order.finished_product?.name }}</td>
          <td>{{ order.quantity_to_produce }}</td>
          <td>{{ statusLabel(order.status) }}</td>
          <td>
            <button v-if="order.status === 'draft'" @click="start(order)">بدء التصنيع</button>
            <button v-if="order.status !== 'completed' && order.status !== 'cancelled'" @click="complete(order)">
              إنهاء وتسليم المنتج التام
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const finishedProducts = ref([])
const rawWarehouses = ref([])
const finishedWarehouses = ref([])
const orders = ref([])
const error = ref('')
const success = ref('')
const loading = ref(false)

const form = ref({ finished_product_id: null, quantity_to_produce: null, raw_warehouse_id: null, finished_warehouse_id: null })

function statusLabel(status) {
  return { draft: 'مسودة', in_progress: 'جاري التصنيع', completed: 'مكتمل', cancelled: 'ملغي' }[status] || status
}

async function loadData() {
  const [{ data: products }, { data: warehouses }, { data: ordersData }] = await Promise.all([
    http.get('/products', { params: { type: 'finished_good' } }),
    http.get('/warehouses'),
    http.get('/production-orders'),
  ])
  finishedProducts.value = products
  rawWarehouses.value = warehouses.filter((w) => w.type === 'raw_materials')
  finishedWarehouses.value = warehouses.filter((w) => w.type === 'finished_goods')
  orders.value = ordersData.data
}

async function createOrder() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/production-orders', form.value)
    success.value = 'تم إنشاء أمر التصنيع بنجاح.'
    await loadData()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    loading.value = false
  }
}

async function start(order) {
  await http.patch(`/production-orders/${order.id}/start`)
  await loadData()
}

async function complete(order) {
  error.value = ''
  try {
    await http.patch(`/production-orders/${order.id}/complete`)
    success.value = 'تم إنهاء أمر التصنيع: تم خصم المواد الخام وزيادة رصيد المنتج التام تلقائياً.'
    await loadData()
  } catch (e) {
    error.value = e.response?.data?.message || 'لا توجد كمية كافية من المواد الخام.'
  }
}

onMounted(loadData)
</script>

<style scoped>
.form {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-width: 420px;
}
</style>
