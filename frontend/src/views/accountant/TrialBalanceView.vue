<template>
  <div>
    <h1>ميزان المراجعة</h1>
    <table>
      <thead>
        <tr>
          <th>الكود</th>
          <th>الحساب</th>
          <th>النوع</th>
          <th>مدين</th>
          <th>دائن</th>
          <th>الرصيد</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.code">
          <td>{{ row.code }}</td>
          <td>{{ row.name }}</td>
          <td>{{ row.type }}</td>
          <td>{{ row.total_debit }}</td>
          <td>{{ row.total_credit }}</td>
          <td>{{ row.balance }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const rows = ref([])

async function load() {
  const { data } = await http.get('/accounts/trial-balance')
  rows.value = data
}

onMounted(load)
</script>
