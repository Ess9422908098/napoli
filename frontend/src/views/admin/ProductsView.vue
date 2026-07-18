<template>
  <div>
    <h1>إدارة المنتجات</h1>
    <p class="alert-error" v-if="error">{{ error }}</p>
    <p class="alert-success" v-if="success">{{ success }}</p>

    <div class="card form">
      <h3>{{ editingId ? 'تعديل المنتج' : 'منتج جديد' }}</h3>
      <label>الكود (SKU)</label>
      <input v-model="form.sku" />
      <label>الاسم</label>
      <input v-model="form.name" />
      <label>النوع</label>
      <select v-model="form.type">
        <option value="raw_material">مادة خام</option>
        <option value="finished_good">منتج تام</option>
      </select>
      <label>وحدة القياس</label>
      <input v-model="form.unit" placeholder="PCS / KG / LTR" />
      <label>تكلفة الوحدة</label>
      <input type="number" min="0" step="0.01" v-model.number="form.cost_price" />
      <label>سعر البيع</label>
      <input type="number" min="0" step="0.01" v-model.number="form.selling_price" />

      <div class="actions">
        <button @click="submitProduct" :disabled="loading">{{ loading ? '...جاري الحفظ' : editingId ? 'حفظ التعديل' : 'إضافة المنتج' }}</button>
        <button v-if="editingId" class="secondary" @click="cancelEdit">إلغاء</button>
      </div>
    </div>

    <div class="toolbar">
      <input v-model="search" placeholder="ابحث بالاسم أو الكود" />
      <select v-model="typeFilter">
        <option value="">الكل</option>
        <option value="raw_material">مادة خام</option>
        <option value="finished_good">منتج تام</option>
      </select>
      <select v-model="warehouseFilter">
        <option value="">كل المخازن</option>
        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
      </select>
    </div>

    <table>
      <thead>
        <tr>
          <th>الكود</th>
          <th>الاسم</th>
          <th>النوع</th>
          <th>الوحدة</th>
          <th>التكلفة</th>
          <th>سعر البيع</th>
          <th>المخازن</th>
          <th>الكمية الحالية</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in filteredProducts" :key="p.id">
          <template v-if="editingRowId === p.id">
            <td><input v-model="inlineForm.sku" /></td>
            <td><input v-model="inlineForm.name" /></td>
            <td>
              <select v-model="inlineForm.type">
                <option value="raw_material">مادة خام</option>
                <option value="finished_good">منتج تام</option>
              </select>
            </td>
            <td><input v-model="inlineForm.unit" /></td>
            <td><input type="number" min="0" step="0.01" v-model.number="inlineForm.cost_price" /></td>
            <td><input type="number" min="0" step="0.01" v-model.number="inlineForm.selling_price" /></td>
            <td>{{ p.warehouse_names }}</td>
            <td>{{ p.current_quantity }}</td>
            <td>
              <button @click="saveInlineEdit" :disabled="loading">حفظ</button>
              <button class="secondary" @click="cancelInlineEdit">إلغاء</button>
            </td>
          </template>
          <template v-else-if="editingQuantityId === p.id">
            <td>{{ p.sku }}</td>
            <td>{{ p.name }}</td>
            <td>{{ typeLabel(p.type) }}</td>
            <td>{{ p.unit }}</td>
            <td>{{ p.cost_price }}</td>
            <td>{{ p.selling_price }}</td>
            <td>{{ p.warehouse_names }}</td>
            <td><input type="number" min="0" step="0.01" v-model.number="quantityForm.quantity" /></td>
            <td>
              <button @click="saveQuantityEdit" :disabled="loading">حفظ الكمية</button>
              <button class="secondary" @click="cancelQuantityEdit">إلغاء</button>
            </td>
          </template>
          <template v-else>
            <td>{{ p.sku }}</td>
            <td>{{ p.name }}</td>
            <td>{{ typeLabel(p.type) }}</td>
            <td>{{ p.unit }}</td>
            <td>{{ p.cost_price }}</td>
            <td>{{ p.selling_price }}</td>
            <td>{{ p.warehouse_names }}</td>
            <td>{{ p.current_quantity }}</td>
            <td>
              <button class="secondary" @click="startInlineEdit(p)">تعديل مباشر</button>
              <button class="secondary" @click="startQuantityEdit(p)">تعديل الكمية</button>
              <button class="danger" @click="deleteProduct(p.id)">حذف</button>
            </td>
          </template>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import http from '../../api/http'

const products = ref([])
const stockRows = ref([])
const warehouses = ref([])
const error = ref('')
const success = ref('')
const loading = ref(false)
const editingId = ref(null)
const editingRowId = ref(null)
const editingQuantityId = ref(null)
const search = ref('')
const typeFilter = ref('')
const warehouseFilter = ref('')

const form = ref({ sku: '', name: '', type: 'raw_material', unit: '', cost_price: 0, selling_price: 0 })
const inlineForm = ref({ sku: '', name: '', type: 'raw_material', unit: '', cost_price: 0, selling_price: 0 })
const quantityForm = ref({ quantity: 0 })

const quantityByProduct = computed(() => {
  const map = {}
  stockRows.value.forEach((row) => {
    const id = row.product_id
    const quantity = Number(row.quantity || 0)
    map[id] = (map[id] || 0) + quantity
  })
  return map
})

const productsWithStock = computed(() =>
  products.value.map((product) => {
    const matchingRows = stockRows.value.filter((row) => row.product_id === product.id)
    return {
      ...product,
      current_quantity: quantityByProduct.value[product.id] || 0,
      warehouse_names: matchingRows.map((row) => row.warehouse_name).join(' - ') || '—',
      warehouse_ids: matchingRows.map((row) => row.warehouse_id),
    }
  })
)

const filteredProducts = computed(() => {
  const q = search.value.trim().toLowerCase()
  return productsWithStock.value.filter((product) => {
    const matchesQuery = !q || product.name?.toLowerCase().includes(q) || product.sku?.toLowerCase().includes(q)
    const matchesType = !typeFilter.value || product.type === typeFilter.value
    const matchesWarehouse = !warehouseFilter.value || product.warehouse_ids.includes(Number(warehouseFilter.value))
    return matchesQuery && matchesType && matchesWarehouse
  })
})

function resetForm() {
  form.value = { sku: '', name: '', type: 'raw_material', unit: '', cost_price: 0, selling_price: 0 }
  editingId.value = null
}

function typeLabel(type) {
  return type === 'raw_material' ? 'مادة خام' : 'منتج تام'
}

async function load() {
  const [{ data: productsData }, { data: stockData }, { data: warehouseData }] = await Promise.all([
    http.get('/products'),
    http.get('/stock/snapshot'),
    http.get('/warehouses'),
  ])
  products.value = productsData
  stockRows.value = stockData
  warehouses.value = warehouseData
}

async function submitProduct() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    if (editingId.value) {
      await http.put(`/products/${editingId.value}`, form.value)
      success.value = 'تم تعديل المنتج بنجاح.'
    } else {
      await http.post('/products', form.value)
      success.value = 'تم إضافة المنتج بنجاح.'
    }

    resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    loading.value = false
  }
}

function editProduct(product) {
  editingId.value = product.id
  form.value = {
    sku: product.sku,
    name: product.name,
    type: product.type,
    unit: product.unit,
    cost_price: product.cost_price,
    selling_price: product.selling_price,
  }
}

function startInlineEdit(product) {
  editingRowId.value = product.id
  inlineForm.value = {
    sku: product.sku,
    name: product.name,
    type: product.type,
    unit: product.unit,
    cost_price: product.cost_price,
    selling_price: product.selling_price,
  }
}

function cancelEdit() {
  resetForm()
}

function cancelInlineEdit() {
  editingRowId.value = null
}

function startQuantityEdit(product) {
  editingQuantityId.value = product.id
  const existing = stockRows.value.filter((row) => row.product_id === product.id)
  const total = existing.reduce((sum, row) => sum + Number(row.quantity || 0), 0)
  quantityForm.value = { quantity: total }
}

function cancelQuantityEdit() {
  editingQuantityId.value = null
}

async function saveInlineEdit() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.put(`/products/${editingRowId.value}`, inlineForm.value)
    success.value = 'تم تعديل المنتج بنجاح.'
    editingRowId.value = null
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    loading.value = false
  }
}

async function saveQuantityEdit() {
  if (!editingQuantityId.value) return

  error.value = ''
  success.value = ''
  loading.value = true
  try {
    const warehouse = stockRows.value.find((row) => row.product_id === editingQuantityId.value)
    await http.post('/stock/count', {
      product_id: editingQuantityId.value,
      warehouse_id: warehouse?.warehouse_id || null,
      counted_quantity: Number(quantityForm.value.quantity),
      reference_number: `manual-${Date.now()}`,
      notes: 'تعديل كمية مباشر من قائمة المنتجات',
    })
    success.value = 'تم تعديل الكمية بنجاح.'
    editingQuantityId.value = null
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    loading.value = false
  }
}

async function deleteProduct(id) {
  if (!window.confirm('هل تريد حذف هذا المنتج؟')) return

  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await http.delete(`/products/${id}`)
    success.value = 'تم حذف المنتج بنجاح.'
    if (editingId.value === id) {
      resetForm()
    }
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
.actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}
.toolbar {
  display: flex;
  gap: 10px;
  margin: 16px 0 10px;
  flex-wrap: wrap;
}
.toolbar input,
.toolbar select {
  padding: 8px;
  min-width: 180px;
}
button.secondary {
  background: #f2f2f2;
  color: #111;
}
button.danger {
  background: #b42318;
  color: white;
  margin-right: 4px;
}
</style>
