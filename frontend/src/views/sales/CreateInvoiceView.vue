<template>
  <div>
    <h1>فاتورة بيع جديدة</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card">
      <label>العميل (اختياري)</label>
      <select v-model="customerId">
        <option :value="null">بدون عميل محدد</option>
        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <div class="card" v-for="(item, index) in items" :key="index">
      <div class="row">
        <div>
          <label>المنتج (منتج تام)</label>
          <select v-model="item.product_id">
            <option v-for="p in finishedProducts" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
          </select>
        </div>
        <div>
          <label>المخزن</label>
          <select v-model="item.warehouse_id">
            <option v-for="w in finishedWarehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>
        <div>
          <label>الكمية</label>
          <input type="number" min="0.01" step="0.01" v-model.number="item.quantity" />
        </div>
        <div>
          <label>سعر الوحدة</label>
          <input type="number" min="0" step="0.01" v-model.number="item.unit_price" />
        </div>
        <button class="danger" type="button" @click="items.splice(index, 1)" v-if="items.length > 1">حذف</button>
      </div>
    </div>

    <button type="button" class="secondary" @click="addItem">+ إضافة صنف</button>
    <br /><br />
    <button type="button" @click="submit" :disabled="loading">{{ loading ? '...جاري الإرسال' : 'إنشاء الفاتورة' }}</button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../../api/http'

const customers = ref([])
const finishedProducts = ref([])
const finishedWarehouses = ref([])
const customerId = ref(null)
const items = ref([{ product_id: null, warehouse_id: null, quantity: 1, unit_price: 0 }])
const error = ref('')
const success = ref('')
const loading = ref(false)

function addItem() {
  items.value.push({ product_id: null, warehouse_id: null, quantity: 1, unit_price: 0 })
}

async function loadData() {
  const [{ data: customersData }, { data: productsData }, { data: warehousesData }] = await Promise.all([
    http.get('/customers'),
    http.get('/products', { params: { type: 'finished_good', active_only: true } }),
    http.get('/warehouses'),
  ])
  customers.value = customersData
  finishedProducts.value = productsData
  finishedWarehouses.value = warehousesData.filter((w) => w.type === 'finished_goods')
}

async function submit() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    const { data } = await http.post('/sales-invoices', {
      customer_id: customerId.value,
      items: items.value,
    })
    success.value = `تم إنشاء الفاتورة رقم ${data.invoice_number} بنجاح، وتم إشعار أمين المخزن لتجهيزها.`
    items.value = [{ product_id: null, warehouse_id: null, quantity: 1, unit_price: 0 }]
  } catch (e) {
    if (e.response?.status === 422 && e.response.data.shortages) {
      error.value = e.response.data.message + ' - الكميات المتاحة غير كافية لبعض الأصناف.'
    } else {
      error.value = e.response?.data?.message || 'حدث خطأ أثناء إنشاء الفاتورة.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  flex-wrap: wrap;
}
.row > div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
</style>
