<script setup>
import { useRoute } from 'vue-router'
import { useAuthStore } from './store/auth'
import NavBar from './components/NavBar.vue'

const route = useRoute()
const auth = useAuthStore()
</script>

<template>
  <div id="app-root">
    <NavBar v-if="auth.isAuthenticated && !route.meta.public && !route.meta.hideNav && auth.role === 'student'" />
    <router-view />
  </div>
</template>

<style>
#app-root {
  min-height: 100vh;
}
</style>
