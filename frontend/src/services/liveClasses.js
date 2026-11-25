import api, { isOffline } from './api'
import { mockLiveClasses } from './mockData'

// Alumno: sus clases en vivo. El servidor ya las manda filtradas por sus
// cursos, ordenadas por horario y sin dos clases a la misma hora.
export async function getLiveClasses() {
  try {
    const { data } = await api.get('/live-classes')
    return data
  } catch (e) {
    // Datos de ejemplo solo si NO hay servidor; un error real no se disfraza.
    return isOffline(e) ? mockLiveClasses : []
  }
}

// Administrador: todas las clases (pasadas y futuras).
export async function getAdminLiveClasses() {
  try {
    const { data } = await api.get('/admin/live-classes')
    return data
  } catch (e) {
    return isOffline(e) ? mockLiveClasses : []
  }
}

// Si el servidor rechaza la clase (horario empalmado, falta el enlace...)
// el error se propaga para mostrárselo al usuario, en vez de fingir que se guardó.
export async function createLiveClass(payload) {
  try {
    const { data } = await api.post('/admin/live-classes', payload)
    return data
  } catch (e) {
    if (isOffline(e)) return { id: Date.now(), status: 'scheduled', ...payload }
    throw e
  }
}

export async function deleteLiveClass(id) {
  try {
    await api.delete(`/admin/live-classes/${id}`)
  } catch (e) {
    if (!isOffline(e)) throw e
  }
  return true
}
