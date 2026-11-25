<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { resetPassword } from '../services/password'
import { errorMessage } from '../services/api'

const route = useRoute()
const router = useRouter()

const token = computed(() => String(route.query.token || ''))
const email = computed(() => String(route.query.email || ''))
const linkIsValid = computed(() => !!token.value && !!email.value)

const password = ref('')
const confirm = ref('')
const loading = ref(false)
const error = ref('')
const done = ref('')

async function submit() {
  error.value = ''
  if (password.value.length < 8) {
    error.value = 'La contraseña debe tener al menos 8 caracteres.'
    return
  }
  if (password.value !== confirm.value) {
    error.value = 'Las contraseñas no coinciden.'
    return
  }
  loading.value = true
  try {
    done.value = await resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: confirm.value
    })
    setTimeout(() => router.push('/login'), 2500)
  } catch (e) {
    error.value = errorMessage(e, 'No se pudo cambiar la contraseña.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-screen">
    <div class="auth-panel card">
      <template v-if="!linkIsValid">
        <h2>Enlace incompleto</h2>
        <p class="muted subtitle">Este enlace no es válido. Solicita uno nuevo para cambiar tu contraseña.</p>
        <router-link to="/forgot-password" class="btn btn-primary btn-block">Solicitar nuevo enlace</router-link>
      </template>

      <template v-else-if="done">
        <h2>¡Listo!</h2>
        <p class="muted subtitle">{{ done }}</p>
        <router-link to="/login" class="btn btn-primary btn-block">Ir a iniciar sesión</router-link>
      </template>

      <template v-else>
        <h2>Crea tu nueva contraseña</h2>
        <p class="muted subtitle">Cuenta: <b>{{ email }}</b></p>
        <form @submit.prevent="submit">
          <div class="form-field">
            <label>Nueva contraseña</label>
            <input v-model="password" type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
          </div>
          <div class="form-field">
            <label>Confirmar contraseña</label>
            <input v-model="confirm" type="password" placeholder="Repite tu contraseña" autocomplete="new-password" />
          </div>
          <p v-if="error" class="error-text">{{ error }}</p>
          <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
            {{ loading ? 'Guardando...' : 'Guardar contraseña' }}
          </button>
          <p v-if="error.includes('venció')" class="retry">
            <router-link to="/forgot-password">Solicitar un nuevo enlace</router-link>
          </p>
        </form>
      </template>
    </div>
  </div>
</template>

<style scoped>
.auth-screen { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
.auth-panel { max-width: 440px; width: 100%; padding: 2.5rem 2.25rem; }
.subtitle { margin-bottom: 1.5rem; line-height: 1.5; word-break: break-word; }
.retry { margin-top: 1rem; text-align: center; font-size: 0.9rem; }
.retry a { color: var(--color-accent); font-weight: 600; }
</style>
