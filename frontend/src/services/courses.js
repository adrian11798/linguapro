import api from './api'
import { mockCourses, mockLessons, mockEnrollments, mockProgress } from './mockData'

// Cada función intenta usar la API real de Laravel (backend/routes/api.php)
// y si no está disponible (proyecto aún en desarrollo), cae en datos mock.
// Esto permite avanzar el frontend en paralelo al backend, tal como lo
// sugiere el cronograma de 1 semana del documento de planeación.

export async function getCourses() {
  try {
    const { data } = await api.get('/courses')
    return data
  } catch (e) {
    return mockCourses
  }
}

// Admin: lista completa (incluye borradores y el nombre del profesor asignado)
export async function getAdminCourses() {
  try {
    const { data } = await api.get('/admin/courses')
    return data
  } catch (e) {
    return mockCourses
  }
}

export async function getCourse(id) {
  try {
    const { data } = await api.get(`/courses/${id}`)
    return data
  } catch (e) {
    return mockCourses.find((c) => c.id === Number(id))
  }
}

export async function getLessonsByCourse(courseId) {
  try {
    const { data } = await api.get(`/courses/${courseId}/lessons`)
    return data
  } catch (e) {
    return mockLessons
      .filter((l) => l.course_id === Number(courseId))
      .sort((a, b) => a.order - b.order)
  }
}

export async function getMyEnrollments(userId) {
  try {
    const { data } = await api.get('/enrollments/me')
    return data
  } catch (e) {
    return mockEnrollments.filter((en) => en.user_id === userId)
  }
}

export async function enrollInCourse(courseId) {
  try {
    const { data } = await api.post('/enrollments', { course_id: courseId })
    return data
  } catch (e) {
    // Simulación local para poder probar el flujo sin backend.
    return { id: Date.now(), course_id: courseId, status: 'active' }
  }
}

export async function getMyProgress(userId) {
  try {
    const { data } = await api.get('/progress/me')
    return data
  } catch (e) {
    return mockProgress.filter((p) => p.user_id === userId)
  }
}

export async function markLessonComplete(lessonId, userId) {
  try {
    const { data } = await api.post('/progress', { lesson_id: lessonId })
    return data
  } catch (e) {
    return { id: Date.now(), user_id: userId, lesson_id: lessonId, completed: true, completed_at: new Date().toISOString() }
  }
}

// Admin: CRUD de cursos
export async function createCourse(payload) {
  try {
    const { data } = await api.post('/admin/courses', payload)
    return data
  } catch (e) {
    return { id: Date.now(), ...payload }
  }
}

export async function updateCourse(id, payload) {
  try {
    const { data } = await api.put(`/admin/courses/${id}`, payload)
    return data
  } catch (e) {
    return { id, ...payload }
  }
}

export async function deleteCourse(id) {
  try {
    await api.delete(`/admin/courses/${id}`)
  } catch (e) {
    /* modo mock: no-op */
  }
  return true
}

// Admin: CRUD de lecciones
export async function createLesson(payload) {
  try {
    const { data } = await api.post('/admin/lessons', payload)
    return data
  } catch (e) {
    return { id: Date.now(), ...payload }
  }
}

export async function deleteLesson(id) {
  try {
    await api.delete(`/admin/lessons/${id}`)
  } catch (e) {
    /* modo mock: no-op */
  }
  return true
}
