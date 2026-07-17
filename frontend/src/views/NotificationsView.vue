<template>
  <div>
    <h1>الإشعارات</h1>
    <div class="card" v-for="n in notifications" :key="n.id">
      <strong>{{ n.title }}</strong>
      <p>{{ n.body }}</p>
      <small>{{ new Date(n.created_at).toLocaleString('ar-EG') }}</small>
      <div>
        <button v-if="!n.read_at" @click="markRead(n)">تمييز كمقروء</button>
      </div>
    </div>
    <p v-if="!notifications.length" class="card">لا توجد إشعارات حالياً.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../api/http'

const notifications = ref([])

async function load() {
  const { data } = await http.get('/notifications')
  notifications.value = data
}

async function markRead(n) {
  await http.patch(`/notifications/${n.id}/read`)
  await load()
}

onMounted(load)
</script>
