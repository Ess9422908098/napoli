<template>
  <div>
    <h1>إدارة المخازن</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <h3>مخزن جديد</h3>
      <label>الكود</label>
      <input v-model="form.code" />
      <label>الاسم</label>
      <input v-model="form.name" />
      <label>النوع</label>
      <select v-model="form.type">
        <option value="raw_materials">مواد خام</option>
        <option value="wip">تحت التشغيل</option>
        <option value="finished_goods">منتج تام</option>
      </select>
      <button @click="createWarehouse" :disabled="loading">{{ loading ? '...جاري الحفظ' : 'إضافة المخزن' }}</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>الكود</th>
          <th>الاسم</th>
          <th>النوع</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="w in warehouses" :key="w.id">
          <td>{{ w.code }}</td>
          <td>{{ w.name }}</td>
          <td>{{ w.type }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const warehouses = ref([])
const error = ref('')
const success = ref('')
const loading = ref(false)

const form = ref({ code: '', name: '', type: 'raw_materials' })

async function load() {
  const { data } = await http.get('/warehouses')
  warehouses.value = data
}

async function createWarehouse() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/warehouses', form.value)
    success.value = 'تم إضافة المخزن بنجاح.'
    form.value = { code: '', name: '', type: 'raw_materials' }
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
