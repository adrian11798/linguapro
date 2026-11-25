import { defineStore } from 'pinia'
import api from '../services/api'
import { mockUsers } from '../services/mockData'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('linguapro_user') || 'null'),
    token: localStorage.getItem('linguapro_token') || null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.user?.role || null,
    // Solo los alumnos pueden quedar deshabilitados.
    isSuspended: (state) => state.user?.role === 'student' && state.user?.is_enabled === false
  },

  actions: {
    // RF-01 / RF-02: registro e inicio de sesión.
    async login(email, password) {
      try {
        const { data } = await api.post('/login', { email, password })
        this._setSession(data.user, data.token)
        return data.user
      } catch (e) {
        // Si el backend respondió (aunque sea con un error), es un fallo
        // real de credenciales u otro problema del servidor: no lo
        // ocultes con el modo demo.
        if (e.response) {
          throw new Error(e.response.data?.message || 'Correo o contraseña incorrectos.')
        }
        // Solo si el backend es inalcanzable (sin respuesta) caemos al
        // modo demo con usuarios de ejemplo.
        const found = mockUsers.find((u) => u.email === email)
        if (!found) {
          throw new Error('Correo o contraseña incorrectos.')
        }
        const fakeToken = `demo-token-${found.id}`
        this._setSession(found, fakeToken)
        return found
      }
    },

    async register(payload) {
      try {
        const { data } = await api.post('/register', payload)
        this._setSession(data.user, data.token)
        return data.user
      } catch (e) {
        if (e.response) {
          throw new Error(e.response.data?.message || 'No se pudo crear la cuenta.')
        }
        // Modo demo: crea un estudiante nuevo localmente.
        const newUser = {
          id: Date.now(),
          name: payload.name,
          email: payload.email,
          role: 'student'
        }
        const fakeToken = `demo-token-${newUser.id}`
        this._setSession(newUser, fakeToken)
        return newUser
      }
    },

    // Vuelve a pedir los datos del usuario (por ejemplo, para saber si ya lo habilitaron).
    async refreshMe() {
      try {
        const { data } = await api.get('/me')
        this.user = data
        localStorage.setItem('linguapro_user', JSON.stringify(data))
      } catch (e) { /* sin conexión: se conserva lo que había */ }
      return this.user
    },

    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('linguapro_user')
      localStorage.removeItem('linguapro_token')
    },

    _setSession(user, token) {
      this.user = user
      this.token = token
      localStorage.setItem('linguapro_user', JSON.stringify(user))
      localStorage.setItem('linguapro_token', token)
    }
  }
})