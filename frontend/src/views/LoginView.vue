<template>
  <div class="login-page">
    <form class="card login-card" @submit.prevent="handleSubmit">
      <h2>تسجيل الدخول - نظام ERP</h2>
      <p class="alert-error" v-if="error">{{ error }}</p>

      <label>اسم المستخدم</label>
      <input v-model="username" type="text" required autofocus />

      <label>كلمة المرور</label>
      <input v-model="password" type="password" required />

      <button type="submit" :disabled="loading">{{ loading ? '...جاري الدخول' : 'دخول' }}</button>

      <p class="hint">
        حسابات تجريبية: admin / sales / storekeeper / production / accountant — كلمة المرور: password123
      </p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const auth = useAuthStore()
const router = useRouter()

async function handleSubmit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(username.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر تسجيل الدخول.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}
.login-card {
  width: 360px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.hint {
  font-size: 12px;
  color: #6e7781;
}
</style>
