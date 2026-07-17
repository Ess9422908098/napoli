<template>
  <div>
    <h1>إدارة الموردين</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <h3>مورد جديد</h3>
      <label>الاسم</label>
      <input v-model="form.name" placeholder="اسم المورد" />
      <label>الشخص المسؤول</label>
      <input v-model="form.contact_person" placeholder="اسم الشخص المسؤول" />
      <label>الهاتف</label>
      <input v-model="form.phone" placeholder="رقم الهاتف" />
      <label>البريد الإلكتروني</label>
      <input v-model="form.email" type="email" placeholder="example@mail.com" />
      <button @click="createSupplier" :disabled="loading">{{ loading ? '...جاري الحفظ' : 'إضافة المورد' }}</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>الاسم</th>
          <th>الشخص المسؤول</th>
          <th>الهاتف</th>
          <th>البريد الإلكتروني</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="s in suppliers" :key="s.id">
          <td>{{ s.name }}</td>
          <td>{{ s.contact_person || '—' }}</td>
          <td>{{ s.phone || '—' }}</td>
          <td>{{ s.email || '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const suppliers = ref([])
const error = ref('')
const success = ref('')
const loading = ref(false)

const form = ref({ name: '', contact_person: '', phone: '', email: '' })

async function load() {
  const { data } = await http.get('/suppliers')
  suppliers.value = data
}

async function createSupplier() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/suppliers', form.value)
    success.value = 'تم إضافة المورد بنجاح.'
    form.value = { name: '', contact_person: '', phone: '', email: '' }
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ أثناء إضافة المورد.'
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
