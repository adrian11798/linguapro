<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const router = useRouter()
const checking = ref(false)
const stillSuspended = ref(false)

async function recheck() {
  checking.value = true
  stillSuspended.value = false
  await auth.refreshMe()
  checking.value = false
  if (auth.isSuspended) {
    stillSuspended.value = true
  } else {
    router.push('/dashboard')
  }
}

function logout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="suspended-screen">
    <div class="notice card">
      <div class="icon-wrap" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="10" rx="2" />
          <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
      </div>
      <p class="hello">Hola, {{ auth.user?.name?.split(' ')[0] }}</p>
      <h1>Favor de ponerse al corriente en sus pagos</h1>
      <p class="muted detail">
        Por ahora tu acceso a los cursos, tareas y clases en vivo está deshabilitado.
        En cuanto tu pago quede registrado, la escuela volverá a habilitar tu cuenta.
      </p>

      <div class="actions">
        <button class="btn btn-primary" :disabled="checking" @click="recheck">
          {{ checking ? 'Verificando...' : 'Ya realicé mi pago, verificar acceso' }}
        </button>
        <button class="btn btn-outline" @click="logout">Cerrar sesión</button>
      </div>
      <p v-if="stillSuspended" class="still">Tu cuenta sigue deshabilitada. Contacta a la escuela si ya realizaste tu pago.</p>
    </div>
  </div>
</template>

<style scoped>
.suspended-screen {
  min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
}
.notice { max-width: 520px; width: 100%; padding: 2.5rem 2.25rem; text-align: center; }
.icon-wrap {
  width: 68px; height: 68px; border-radius: 50%; margin: 0 auto 1.1rem;
  background: rgba(217, 119, 6, 0.12); color: #b45309;
  display: flex; align-items: center; justify-content: center;
}
.icon-wrap svg { width: 30px; height: 30px; }
.hello { margin: 0 0 0.3rem; color: var(--color-text-muted); font-size: 0.95rem; }
h1 { font-size: 1.65rem; line-height: 1.25; margin-bottom: 0.9rem; }
.detail { line-height: 1.55; margin: 0 auto 1.6rem; max-width: 42ch; }
.actions { display: flex; flex-direction: column; gap: 0.6rem; }
.still { margin: 1rem 0 0; font-size: 0.88rem; color: #b45309; }
</style>
