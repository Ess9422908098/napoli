<template>
  <div>
    <h1>مرحباً، {{ auth.user?.name }}</h1>
    <p class="card">دورك الحالي: <strong>{{ auth.user?.role_name }}</strong></p>
    <p class="card">
      استخدم القائمة الجانبية للوصول إلى الأقسام المتاحة لصلاحيتك فقط. كل قسم مستقل تماماً عن
      الأقسام الأخرى في الواجهة، رغم أن البيانات مترابطة برمجياً في الخلفية.
    </p>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth'
const auth = useAuthStore()
</script>
