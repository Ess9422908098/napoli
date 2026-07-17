<template>
  <div>
    <h1>القيود المحاسبية (تلقائية)</h1>
    <table>
      <thead>
        <tr>
          <th>رقم القيد</th>
          <th>التاريخ</th>
          <th>الوصف</th>
          <th>البنود</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="entry in entries" :key="entry.id">
          <td>{{ entry.entry_number }}</td>
          <td>{{ new Date(entry.entry_date).toLocaleDateString('ar-EG') }}</td>
          <td>{{ entry.description }}</td>
          <td>
            <div v-for="line in entry.lines" :key="line.id">
              {{ line.account.name }} — مدين: {{ line.debit }} / دائن: {{ line.credit }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const entries = ref([])

async function load() {
  const { data } = await http.get('/journal-entries')
  entries.value = data.data
}

onMounted(load)
</script>
