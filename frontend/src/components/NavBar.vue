<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { useMessagesStore } from '../store/messages'

const auth = useAuthStore()
const router = useRouter()
const messages = useMessagesStore()
onMounted(() => messages.startPolling())
onBeforeUnmount(() => messages.stopPolling())

const studentLinks = [
  { to: '/dashboard', label: 'Inicio' },
  { to: '/my-courses', label: 'Mis Cursos' },
  { to: '/courses', label: 'Explorar' },
  { to: '/live-classes', label: 'Clases en Vivo' },
  { to: '/messages', label: 'Mensajes', badge: true },
  { to: '/progress', label: 'Mi Progreso' }
]

const teacherLinks = [{ to: '/teacher', label: 'Panel del Profesor' }]

const initials = computed(() =>
  (auth.user?.name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <header class="navbar">
    <div class="navbar-inner">
      <div class="brand-row">
        <span class="logo-mark">L</span>
        <span class="brand">LinguaPro</span>
      </div>
      <nav class="links">
        <router-link
          v-for="link in (auth.role === 'teacher' ? teacherLinks : studentLinks)"
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
        <span class="user-name">{{ auth.user?.name }}</span>
        <button class="btn btn-outline btn-sm" @click="handleLogout">Salir</button>
      </div>
    </div>
  </header>
</template>

<style scoped>
.navbar {
  position: sticky; top: 0; z-index: 20;
  background: rgba(255, 255, 255, 0.86);
  backdrop-filter: saturate(160%) blur(12px);
  border-bottom: 1px solid #e2e6eb;
}
.navbar-inner {
  max-width: 1180px; margin: 0 auto; padding: 0.8rem 1.5rem;
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

.links { display: flex; gap: 0.3rem; flex: 1; }
.nav-link {
  color: var(--color-text-muted); font-weight: 500; font-size: 0.92rem;
  padding: 0.5rem 0.9rem; border-radius: 999px;
  transition: background 0.15s ease, color 0.15s ease;
}
.nav-link:hover { background: var(--color-bg); color: var(--color-primary); }
.nav-link.active { background: rgba(59, 130, 246, 0.12); color: var(--color-accent); font-weight: 600; }

.badge-count { background: var(--color-accent); color: #fff; border-radius: 999px; font-size: 0.7rem; font-weight: 700; padding: 0.05rem 0.45rem; margin-left: 0.3rem; }
.user-area { display: flex; align-items: center; gap: 0.7rem; }
.user-name { font-size: 0.88rem; font-weight: 500; }

@media (max-width: 860px) {
  .navbar-inner { flex-wrap: wrap; gap: 0.8rem; }
  .links { order: 3; width: 100%; overflow-x: auto; }
  .user-name { display: none; }
}
</style>
