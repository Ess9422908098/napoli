<template>
  <div>
    <h1>إدارة المستخدمين</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <h3>مستخدم جديد</h3>
      <label>الاسم</label>
      <input v-model="form.name" />
      <label>اسم المستخدم</label>
      <input v-model="form.username" />
      <label>البريد الإلكتروني</label>
      <input v-model="form.email" type="email" />
      <label>كلمة المرور</label>
      <input v-model="form.password" type="password" />
      <label>الدور الوظيفي</label>
      <select v-model="form.role_id">
        <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <button @click="createUser" :disabled="loading">{{ loading ? '...جاري الحفظ' : 'إضافة المستخدم' }}</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>الاسم</th>
          <th>اسم المستخدم</th>
          <th>الدور</th>
          <th>مفعل</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td>{{ user.name }}</td>
          <td>{{ user.username }}</td>
          <td>{{ user.role.name }}</td>
          <td>{{ user.is_active ? 'نعم' : 'لا' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const users = ref([])
const roles = ref([])
const error = ref('')
const success = ref('')
const loading = ref(false)

const form = ref({ name: '', username: '', email: '', password: '', role_id: null })

async function loadData() {
  const [{ data: usersData }, { data: rolesData }] = await Promise.all([http.get('/users'), http.get('/roles')])
  users.value = usersData
  roles.value = rolesData
}

async function createUser() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.post('/users', form.value)
    success.value = 'تم إنشاء المستخدم بنجاح.'
    form.value = { name: '', username: '', email: '', password: '', role_id: null }
    await loadData()
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
