<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

const auth = useAuthStore()
const router = useRouter()

async function handleSubmit() {
  error.value = ''
  if (!email.value || !password.value) {
    error.value = 'Ingresa tu correo y contraseña.'
    return
  }
  loading.value = true
  try {
    const user = await auth.login(email.value, password.value)
    if (user.role === 'admin') router.push('/admin/courses')
    else if (user.role === 'teacher') router.push('/teacher')
    else router.push('/dashboard')
  } catch (e) {
    error.value = e.message || 'No se pudo iniciar sesión.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-screen">
    <div class="auth-panel card">
      <div class="hero-side">
        <span class="eyebrow">BIENVENIDO A TU PRÓXIMO LOGRO</span>
        <h1>Aprende sin límites</h1>
        <div class="hero-image" />
      </div>

      <div class="form-side">
        <h2>Iniciar Sesión</h2>
        <form @submit.prevent="handleSubmit">
          <div class="form-field">
            <input v-model="email" type="email" placeholder="Correo electrónico" autocomplete="username" />
          </div>
          <div class="form-field password-field">
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Contraseña"
              autocomplete="current-password"
            />
            <button type="button" class="toggle-visibility" @click="showPassword = !showPassword">
              {{ showPassword ? 'Ocultar' : 'Ver' }}
            </button>
          </div>
          <router-link to="/forgot-password" class="forgot-link">¿Olvidaste tu contraseña?</router-link>
          <p v-if="error" class="error-text">{{ error }}</p>
          <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
            {{ loading ? 'Ingresando...' : 'Iniciar Sesión' }}
          </button>
        </form>
        <p class="switch-auth">
          ¿No tienes cuenta? <router-link to="/register">Regístrate</router-link>
        </p>
        <p class="demo-hint muted">
          Demo: evelyn@linguapro.mx (estudiante), laura@linguapro.mx (profesor),
          admin@linguapro.mx (admin) — cualquier contraseña.
        </p>
      </div>
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
  display: grid;
  grid-template-columns: 1fr 1fr;
  max-width: 960px;
  width: 100%;
  overflow: hidden;
}
.hero-side {
  background: var(--color-bg);
  padding: 3rem 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.eyebrow {
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  color: var(--color-text-muted);
  font-weight: 600;
}
.hero-side h1 {
  font-size: 2.2rem;
  line-height: 1.15;
}
.hero-image {
  margin-top: 0.5rem;
  border-radius: var(--radius-lg);
  height: 260px;
  background: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=700&q=80') center/cover;
}
.form-side {
  padding: 3rem 2.5rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.form-side h2 {
  margin-bottom: 1.5rem;
}
.password-field {
  position: relative;
}
.toggle-visibility {
  position: absolute;
  right: 0.7rem;
  top: 0.65rem;
  background: none;
  border: none;
  font-size: 0.8rem;
  color: var(--color-text-muted);
}
.forgot-link {
  display: block;
  text-align: right;
  font-size: 0.85rem;
  color: var(--color-primary);
  margin-bottom: 1.2rem;
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
.demo-hint {
  margin-top: 1rem;
  font-size: 0.75rem;
  text-align: center;
}
@media (max-width: 760px) {
  .auth-panel { grid-template-columns: 1fr; }
  .hero-side { display: none; }
}
</style>
