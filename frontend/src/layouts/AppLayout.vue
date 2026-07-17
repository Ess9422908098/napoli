<template>
  <div class="app-shell">
    <AppNavBar />
    <main class="app-content">
      <div class="app-content-inner">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import AppNavBar from '../components/AppNavBar.vue'
</script>

<style scoped>
.app-content {
  flex: 1;
  min-width: 0;
  padding: 28px 32px;
}
.app-content-inner {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
