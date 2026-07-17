<template>
  <div>
    <h1>إدارة العملاء</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <h3>عميل جديد</h3>
      <label>الاسم</label>
      <input v-model="form.name" placeholder="اسم العميل" />
      <label>الهاتف</label>
      <input v-model="form.phone" placeholder="رقم الهاتف" />
      <label>البريد الإلكتروني</label>
      <input v-model="form.email" type="email" placeholder="example@mail.com" />
      <label>العنوان</label>
      <input v-model="form.address" placeholder="العنوان" />
      <button @click="createCustomer" :disabled="loading">{{ loading ? '...جاري الحفظ' : 'إضافة العميل' }}</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>الاسم</th>
          <th>الهاتف</th>
          <th>البريد الإلكتروني</th>
          <th>العنوان</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="c in customers" :key="c.id">
          <td>{{ c.name }}</td>
          <td>{{ c.phone || '—' }}</td>
          <td>{{ c.email || '—' }}</td>
          <td>{{ c.address || '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const customers = ref([])
const error = ref('')
const success = ref('')
const loading = ref(false)

const form = ref({ name: '', phone: '', email: '', address: '' })

async function load() {
  const { data } = await http.get('/customers')
  customers.value = data
}

async function createCustomer() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/customers', form.value)
    success.value = 'تم إضافة العميل بنجاح.'
    form.value = { name: '', phone: '', email: '', address: '' }
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ أثناء إضافة العميل.'
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
