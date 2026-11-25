import api, { isOffline } from './api'
import { mockAdminTeachers, mockAdminStudents } from './mockData'

export async function getAdminTeachers() {
  try {
    const { data } = await api.get('/admin/teachers')
    return data
  } catch (e) {
    return isOffline(e) ? mockAdminTeachers : []
  }
}

export async function createAdminTeacher(payload) {
  try {
    const { data } = await api.post('/admin/teachers', payload)
    return data
  } catch (e) {
    if (isOffline(e)) return { id: Date.now(), name: payload.name, email: payload.email, courses: [], students_count: 0 }
    throw e
  }
}

export async function getAdminStudents() {
  try {
    const { data } = await api.get('/admin/students')
    return data
  } catch (e) {
    return isOffline(e) ? mockAdminStudents : []
  }
}

// Habilita (true) o deshabilita (false) el acceso de un alumno.
export async function setStudentEnabled(id, isEnabled) {
  try {
    const { data } = await api.put(`/admin/students/${id}/status`, { is_enabled: isEnabled })
    return data
  } catch (e) {
    if (isOffline(e)) return { id, is_enabled: isEnabled }
    throw e
  }
}
