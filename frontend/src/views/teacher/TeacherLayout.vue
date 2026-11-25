<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../store/auth'
import { useMessagesStore } from '../../store/messages'

const auth = useAuthStore()
const router = useRouter()
const messages = useMessagesStore()
onMounted(() => messages.startPolling())
onBeforeUnmount(() => messages.stopPolling())

const initials = (auth.user?.name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')

const links = [
  { to: '/teacher/students', label: 'Mis Alumnos' },
  { to: '/teacher/lessons', label: 'Mis Videos' },
  { to: '/teacher/assignments', label: 'Tareas' },
  { to: '/teacher/live-classes', label: 'Clases en Vivo' },
  { to: '/teacher/messages', label: 'Mensajes', badge: true }
]

function logout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div>
    <header class="topbar">
      <div class="brand-row">
        <span class="logo-mark">L</span>
        <span class="brand">LinguaPro</span>
      </div>
      <nav class="links">
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="nav-link"
          active-class="active"
        >
          {{ link.label }}
          <span v-if="link.badge && messages.unread" class="badge-count">{{ messages.unread }}</span>
        </router-link>
      </nav>
      <div class="user-area">
        <span class="avatar">{{ initials }}</span>
        <span>{{ auth.user?.name }}</span>
        <button class="btn btn-outline btn-sm" @click="logout">Salir</button>
      </div>
    </header>
    <div class="page">
      <router-view />
    </div>
  </div>
</template>

<style scoped>
.topbar {
  position: sticky; top: 0; z-index: 20;
  background: rgba(255, 255, 255, 0.86);
  backdrop-filter: saturate(160%) blur(12px);
  border-bottom: 1px solid #e2e6eb;
  padding: 0.8rem 1.5rem;
  display: flex; align-items: center; gap: 2rem;
}
.brand-row { display: flex; align-items: center; gap: 0.6rem; }
.logo-mark {
  width: 32px; height: 32px; border-radius: 9px;
  background: linear-gradient(135deg, #3b82f6, #0d233a);
  color: #fff; font-family: var(--font-heading); font-weight: 700; font-size: 1.05rem;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 3px 10px rgba(13, 35, 58, 0.25);
}
.brand { font-family: var(--font-heading); font-weight: 700; font-size: 1.15rem; color: var(--color-primary); }
.links { display: flex; gap: 0.3rem; flex: 1; flex-wrap: wrap; }
.nav-link {
  color: var(--color-text-muted); font-weight: 500; font-size: 0.92rem;
  padding: 0.5rem 0.9rem; border-radius: 999px;
  transition: background 0.15s ease, color 0.15s ease;
}
.nav-link:hover { background: var(--color-bg); color: var(--color-primary); }
.nav-link.active { background: rgba(59, 130, 246, 0.12); color: var(--color-accent); font-weight: 600; }
.badge-count { background: var(--color-accent); color: #fff; border-radius: 999px; font-size: 0.7rem; font-weight: 700; padding: 0.05rem 0.45rem; margin-left: 0.3rem; }
.user-area { display: flex; align-items: center; gap: 0.7rem; font-size: 0.88rem; font-weight: 500; }
</style>
