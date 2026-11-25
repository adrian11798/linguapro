<script setup>
import { ref } from 'vue'
import { requestPasswordReset } from '../services/password'
import { errorMessage } from '../services/api'

const email = ref('')
const loading = ref(false)
const error = ref('')
const sentMessage = ref('')

async function submit() {
  error.value = ''
  if (!email.value.trim()) {
    error.value = 'Escribe tu correo.'
    return
  }
  loading.value = true
  try {
    sentMessage.value = await requestPasswordReset(email.value.trim())
  } catch (e) {
    error.value = errorMessage(e, 'No se pudo enviar el correo.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-screen">
    <div class="auth-panel card">
      <template v-if="!sentMessage">
        <h2>¿Olvidaste tu contraseña?</h2>
        <p class="muted subtitle">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.</p>
        <form @submit.prevent="submit">
          <div class="form-field">
            <label>Correo electrónico</label>
            <input v-model="email" type="email" placeholder="tucorreo@ejemplo.com" autocomplete="email" />
          </div>
          <p v-if="error" class="error-text">{{ error }}</p>
          <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
            {{ loading ? 'Enviando...' : 'Enviar enlace' }}
          </button>
        </form>
      </template>

      <template v-else>
        <div class="sent-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />
          </svg>
        </div>
        <h2>Revisa tu correo</h2>
        <p class="muted subtitle">{{ sentMessage }}</p>
        <p class="muted hint">El enlace vence en 60 minutos. Si no lo ves, revisa la carpeta de spam.</p>
      </template>

      <p class="switch-auth"><router-link to="/login">← Volver a iniciar sesión</router-link></p>
    </div>
  </div>
</template>

<style scoped>
.auth-screen { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
.auth-panel { max-width: 440px; width: 100%; padding: 2.5rem 2.25rem; }
.subtitle { margin-bottom: 1.5rem; line-height: 1.5; }
.hint { font-size: 0.85rem; }
.sent-icon {
  width: 60px; height: 60px; border-radius: 50%; margin-bottom: 1rem;
  background: rgba(59, 130, 246, 0.12); color: var(--color-accent);
  display: flex; align-items: center; justify-content: center;
}
.sent-icon svg { width: 28px; height: 28px; }
.switch-auth { margin-top: 1.4rem; font-size: 0.9rem; text-align: center; }
.switch-auth a { color: var(--color-accent); font-weight: 600; }
</style>
