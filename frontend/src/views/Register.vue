<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const error = ref('')
const loading = ref(false)

const auth = useAuthStore()
const router = useRouter()

async function handleSubmit() {
  error.value = ''
  if (!name.value || !email.value || !password.value) {
    error.value = 'Completa todos los campos.'
    return
  }
  if (password.value !== confirmPassword.value) {
    error.value = 'Las contraseñas no coinciden.'
    return
  }
  loading.value = true
  try {
    await auth.register({ name: name.value, email: email.value, password: password.value })
    router.push('/dashboard')
  } catch (e) {
    error.value = e.message || 'No se pudo crear la cuenta.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-screen">
    <div class="auth-panel card">
      <h2>Únete como Alumno</h2>
      <p class="muted subtitle">Crea tu cuenta para empezar a aprender sin límites.</p>
      <form @submit.prevent="handleSubmit">
        <div class="form-field">
          <label>Nombre completo</label>
          <input v-model="name" type="text" placeholder="Tu nombre" />
        </div>
        <div class="form-field">
          <label>Correo electrónico</label>
          <input v-model="email" type="email" placeholder="tucorreo@ejemplo.com" />
        </div>
        <div class="form-field">
          <label>Contraseña</label>
          <input v-model="password" type="password" placeholder="Mínimo 8 caracteres" />
        </div>
        <div class="form-field">
          <label>Confirmar contraseña</label>
          <input v-model="confirmPassword" type="password" placeholder="Repite tu contraseña" />
        </div>
        <p v-if="error" class="error-text">{{ error }}</p>
        <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
          {{ loading ? 'Creando cuenta...' : 'Crear cuenta' }}
        </button>
      </form>
      <p class="switch-auth">
        ¿Ya tienes cuenta? <router-link to="/login">Inicia sesión</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-screen {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
  padding: 1.5rem;
}
.auth-panel {
  max-width: 440px;
  width: 100%;
  padding: 2.75rem 2.5rem;
}
.subtitle {
  margin-bottom: 1.5rem;
}
.switch-auth {
  margin-top: 1.2rem;
  font-size: 0.9rem;
  text-align: center;
}
.switch-auth a {
  color: var(--color-accent);
  font-weight: 600;
}
</style>
