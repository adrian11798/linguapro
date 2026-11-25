<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../store/auth'

const auth = useAuthStore()
const router = useRouter()

// Íconos de trazo simple (24x24)
const links = [
  {
    to: '/admin/courses', label: 'Cursos',
    icon: '<path d="M2 4h6a4 4 0 0 1 4 4v12a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v12a3 3 0 0 1 3-3h7z"/>'
  },
  {
    to: '/admin/lessons', label: 'Contenidos',
    icon: '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>'
  },
  {
    to: '/admin/teachers', label: 'Maestros',
    icon: '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/>'
  },
  {
    to: '/admin/students', label: 'Alumnos',
    icon: '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>'
  },
  {
    to: '/admin/users', label: 'Usuarios',
    icon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'
  },
  {
    to: '/admin/live-classes', label: 'Clases en Vivo',
    icon: '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/>'
  }
]

const initials = computed(() =>
  (auth.user?.name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)

function logout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="admin-shell">
    <aside class="sidebar">
      <div class="brand-row">
        <span class="logo-mark">L</span>
        <div>
          <div class="brand">LinguaPro</div>
          <p class="panel-label">Panel Administrativo</p>
        </div>
      </div>

      <nav>
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="side-link"
          active-class="active"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
               stroke-linecap="round" stroke-linejoin="round" v-html="link.icon" />
          <span>{{ link.label }}</span>
        </router-link>
      </nav>

      <div class="user-chip">
        <span class="avatar">{{ initials }}</span>
        <div class="user-meta">
          <b>{{ auth.user?.name }}</b>
          <span>Administrador</span>
        </div>
      </div>
      <button class="btn btn-outline btn-sm logout-btn" @click="logout">Cerrar sesión</button>
    </aside>

    <main class="admin-content">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.admin-shell { display: flex; min-height: 100vh; }

.sidebar {
  width: 250px;
  position: sticky;
  top: 0;
  height: 100vh;
  background: var(--color-surface);
  border-right: 1px solid #e2e6eb;
  padding: 1.5rem 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  flex-shrink: 0;
}

.brand-row { display: flex; align-items: center; gap: 0.7rem; padding: 0 0.4rem 1.4rem; }
.logo-mark {
  width: 38px; height: 38px; border-radius: 11px;
  background: linear-gradient(135deg, #3b82f6, #0d233a);
  color: #fff; font-family: var(--font-heading); font-weight: 700; font-size: 1.2rem;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 12px rgba(13, 35, 58, 0.25);
}
.brand { font-family: var(--font-heading); font-weight: 700; font-size: 1.15rem; color: var(--color-primary); line-height: 1.1; }
.panel-label { font-size: 0.75rem; margin: 0.15rem 0 0; color: var(--color-text-muted); }

nav { display: flex; flex-direction: column; gap: 0.25rem; }
.side-link {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.68rem 0.85rem;
  border-radius: var(--radius-md);
  font-weight: 500; font-size: 0.92rem;
  color: var(--color-text-muted);
  transition: background 0.15s ease, color 0.15s ease;
}
.side-link svg { width: 19px; height: 19px; flex-shrink: 0; }
.side-link:hover { background: var(--color-bg); color: var(--color-primary); }
.side-link.active {
  background: linear-gradient(180deg, #14304f, #0d233a);
  color: #fff; font-weight: 600;
  box-shadow: 0 6px 14px rgba(13, 35, 58, 0.22);
}

.user-chip {
  margin-top: auto;
  display: flex; align-items: center; gap: 0.7rem;
  padding: 0.75rem; border-radius: var(--radius-md);
  background: var(--color-bg);
}
.user-meta { display: flex; flex-direction: column; min-width: 0; line-height: 1.25; }
.user-meta b { font-size: 0.85rem; color: var(--color-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-meta span { font-size: 0.74rem; color: var(--color-text-muted); }
.logout-btn { margin-top: 0.5rem; }

.admin-content { flex: 1; padding: 2.25rem 2.75rem 3rem; min-width: 0; }

@media (max-width: 860px) {
  .admin-shell { flex-direction: column; }
  .sidebar { width: 100%; height: auto; position: static; flex-direction: row; flex-wrap: wrap; align-items: center; }
  .brand-row { padding-bottom: 0; }
  nav { flex-direction: row; flex-wrap: wrap; }
  .user-chip { display: none; }
  .logout-btn { margin-top: 0; margin-left: auto; }
  .admin-content { padding: 1.5rem 1.25rem; }
}
</style>
