<template>
  <div>
    <h1>إدارة المنتجات</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <h3>منتج جديد</h3>
      <label>الكود (SKU)</label>
      <input v-model="form.sku" />
      <label>الاسم</label>
      <input v-model="form.name" />
      <label>النوع</label>
      <select v-model="form.type">
        <option value="raw_material">مادة خام</option>
        <option value="finished_good">منتج تام</option>
      </select>
      <label>وحدة القياس</label>
      <input v-model="form.unit" placeholder="PCS / KG / LTR" />
      <label>تكلفة الوحدة</label>
      <input type="number" min="0" step="0.01" v-model.number="form.cost_price" />
      <label>سعر البيع</label>
      <input type="number" min="0" step="0.01" v-model.number="form.selling_price" />
      <button @click="createProduct" :disabled="loading">{{ loading ? '...جاري الحفظ' : 'إضافة المنتج' }}</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>الكود</th>
          <th>الاسم</th>
          <th>النوع</th>
          <th>الوحدة</th>
          <th>تكلفة</th>
          <th>سعر البيع</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in products" :key="p.id">
          <td>{{ p.sku }}</td>
          <td>{{ p.name }}</td>
          <td>{{ p.type === 'raw_material' ? 'مادة خام' : 'منتج تام' }}</td>
          <td>{{ p.unit }}</td>
          <td>{{ p.cost_price }}</td>
          <td>{{ p.selling_price }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const products = ref([])
const error = ref('')
const success = ref('')
const loading = ref(false)

const form = ref({ sku: '', name: '', type: 'raw_material', unit: '', cost_price: 0, selling_price: 0 })

async function load() {
  const { data } = await http.get('/products')
  products.value = data
}

async function createProduct() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/products', form.value)
    success.value = 'تم إضافة المنتج بنجاح.'
    form.value = { sku: '', name: '', type: 'raw_material', unit: '', cost_price: 0, selling_price: 0 }
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.form {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-width: 420px;
}
</style>
