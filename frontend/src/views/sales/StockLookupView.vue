<template>
  <div>
    <h1>أرصدة المخزون (قراءة فقط)</h1>
    <div class="card">
      <label>نوع المخزن</label>
      <select v-model="warehouseType" @change="load">
        <option value="">الكل</option>
        <option value="raw_materials">مواد خام</option>
        <option value="wip">تحت التشغيل</option>
        <option value="finished_goods">منتج تام</option>
      </select>
    </div>

    <table>
      <thead>
        <tr>
          <th>المنتج</th>
          <th>المخزن</th>
          <th>الكمية الكلية</th>
          <th>المحجوز</th>
          <th>المتاح</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.product_id + '-' + row.warehouse_id">
          <td>{{ row.product_name }} ({{ row.sku }})</td>
          <td>{{ row.warehouse_name }}</td>
          <td>{{ row.quantity }}</td>
          <td>{{ row.reserved_quantity }}</td>
          <td>{{ row.available_quantity }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const rows = ref([])
const warehouseType = ref('')

async function load() {
  const { data } = await http.get('/stock/snapshot', {
    params: warehouseType.value ? { warehouse_type: warehouseType.value } : {},
  })
  rows.value = data
}

onMounted(load)
</script>
