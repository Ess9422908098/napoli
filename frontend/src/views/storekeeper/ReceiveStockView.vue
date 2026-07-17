<template>
  <div>
    <h1>تسجيل وارد للمخزن</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <label>المنتج</label>
      <select v-model="form.product_id">
        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
      </select>

      <label>المخزن</label>
      <select v-model="form.warehouse_id">
        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>

      <label>الكمية</label>
      <input type="number" min="0.0001" step="0.0001" v-model.number="form.quantity" />

      <label>تكلفة الوحدة (اختياري)</label>
      <input type="number" min="0" step="0.01" v-model.number="form.unit_cost" />

      <label>رقم الإذن / المرجع</label>
      <input type="text" v-model="form.reference_number" required />

      <label>ملاحظات</label>
      <textarea v-model="form.notes"></textarea>

      <button @click="submit" :disabled="loading">{{ loading ? '...جاري الحفظ' : 'تسجيل الوارد' }}</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const products = ref([])
const warehouses = ref([])
const loading = ref(false)
const error = ref('')
const success = ref('')

const form = ref({ product_id: null, warehouse_id: null, quantity: null, unit_cost: null, reference_number: '', notes: '' })

async function loadData() {
  const [{ data: p }, { data: w }] = await Promise.all([http.get('/products'), http.get('/warehouses')])
  products.value = p
  warehouses.value = w
}

async function submit() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/stock/receive', form.value)
    success.value = 'تم تسجيل الوارد بنجاح.'
    form.value = { product_id: null, warehouse_id: null, quantity: null, unit_cost: null, reference_number: '', notes: '' }
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    loading.value = false
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
