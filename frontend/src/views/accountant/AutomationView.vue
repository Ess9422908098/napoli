<template>
  <div class="automation-page">
    <h1>الأتمتة التشغيلية</h1>
    <p class="subtitle">مؤشرات تلقائية للقيود والمخزون والإشعارات</p>

    <div class="cards-grid">
      <section class="card">
        <h2>حالة الأتمتة</h2>
        <ul>
          <li>إنشاء القيود المحاسبية تلقائيًا: <strong>مفعل</strong></li>
          <li>تحديث المخزون عند البيع والشراء: <strong>مفعل</strong></li>
          <li>إرسال الإشعارات الداخلية: <strong>مفعل</strong></li>
        </ul>
      </section>

      <section class="card">
        <h2>مؤشرات سريعة</h2>
        <ul>
          <li>الفواتير المعلقة: <strong>{{ summary.pending_invoices }}</strong></li>
          <li>عناصر منخفضة المخزون: <strong>{{ summary.low_stock_items }}</strong></li>
          <li>أوامر إنتاج قيد التنفيذ: <strong>{{ summary.late_production_orders }}</strong></li>
          <li>طلبات شراء مسودة: <strong>{{ summary.pending_purchases }}</strong></li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http from '../../api/http'

const summary = ref({
  pending_invoices: 0,
  low_stock_items: 0,
  late_production_orders: 0,
  pending_purchases: 0,
})

onMounted(async () => {
  try {
    const { data } = await http.get('/automation/health')
    summary.value = data.summary || summary.value
  } catch (error) {
    console.error('Failed to load automation health', error)
  }
})
</script>

<style scoped>
.automation-page { display: grid; gap: 1rem; }
.subtitle { color: #64748b; margin-top: -0.4rem; }
.cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
ul { display: grid; gap: 0.5rem; padding-inline-start: 1.2rem; }
</style>
