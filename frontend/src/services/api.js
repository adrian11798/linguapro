import axios from 'axios'

// Apunta al backend Laravel. Configúralo en un archivo .env como:
// VITE_API_URL=http://localhost:8000/api
const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

// Adjunta el token guardado (Sanctum / JWT) a cada petición.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('linguapro_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Si el backend responde 401, cerramos la sesión local.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      localStorage.removeItem('linguapro_token')
      localStorage.removeItem('linguapro_user')
    }

    // El administrador deshabilitó a este alumno: marcamos la sesión y lo
    // llevamos a la pantalla de aviso de pagos.
    if (error?.response?.status === 403 && error.response.data?.code === 'account_disabled') {
      try {
        const user = JSON.parse(localStorage.getItem('linguapro_user') || 'null')
        if (user) {
          user.is_enabled = false
          localStorage.setItem('linguapro_user', JSON.stringify(user))
        }
      } catch (e) { /* sin datos de sesión */ }
      if (window.location.pathname !== '/suspended') window.location.assign('/suspended')
    }
    return Promise.reject(error)
  }
)

export default api

// Texto de error listo para mostrar al usuario (usa el mensaje del servidor).
export function errorMessage(e, fallback = 'Ocurrió un error. Intenta de nuevo.') {
  return e?.response?.data?.message || fallback
}

// true si el servidor nunca respondió (sin conexión): solo ahí se usan datos de ejemplo.
export function isOffline(e) {
  return !e?.response
}

// Convierte una ruta relativa del backend ("/storage/...") en una URL
// completa, usando el mismo origen configurado en VITE_API_URL (sin el
// sufijo /api). Sirve para descargar archivos de tareas/entregas.
const backendOrigin = baseURL.replace(/\/api\/?$/, '')
export function fileUrl(path) {
  if (!path) return null
  if (/^https?:\/\//i.test(path)) return path
  return backendOrigin + path
}

// Sube un archivo (FormData) sin fijar el Content-Type manualmente, para
// que el navegador agregue el boundary del multipart automáticamente.
export function uploadFile(url, formData) {
  return api.post(url, formData, { headers: { 'Content-Type': undefined } })
}
