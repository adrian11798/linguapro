import api, { uploadFile, isOffline } from './api'
import { mockCourses, mockLessons, mockTeacherStudents, mockLiveClasses } from './mockData'

export async function getTeacherStudents() {
  try {
    const { data } = await api.get('/teacher/students')
    return data
  } catch (e) {
    return mockTeacherStudents
  }
}

export async function getTeacherCourses() {
  try {
    const { data } = await api.get('/teacher/courses')
    return data
  } catch (e) {
    return mockCourses
  }
}

export async function getTeacherLessons(courseId) {
  try {
    const { data } = await api.get(`/teacher/courses/${courseId}/lessons`)
    return data
  } catch (e) {
    return mockLessons.filter((l) => l.course_id === Number(courseId))
  }
}

export async function createTeacherLesson(payload) {
  try {
    const { data } = await api.post('/teacher/lessons', payload)
    return data
  } catch (e) {
    return { id: Date.now(), ...payload }
  }
}

export async function updateTeacherLesson(id, payload) {
  try {
    const { data } = await api.put(`/teacher/lessons/${id}`, payload)
    return data
  } catch (e) {
    return { id, ...payload }
  }
}

export async function deleteTeacherLesson(id) {
  try {
    await api.delete(`/teacher/lessons/${id}`)
  } catch (e) {
    /* modo mock: no-op */
  }
  return true
}

export async function getTeacherLiveClasses() {
  try {
    const { data } = await api.get('/teacher/live-classes')
    return data
  } catch (e) {
    return isOffline(e) ? mockLiveClasses : []
  }
}

// Si el servidor rechaza la clase (horario empalmado, falta el enlace...)
// el error se propaga para mostrárselo al profesor.
export async function createTeacherLiveClass(payload) {
  try {
    const { data } = await api.post('/teacher/live-classes', payload)
    return data
  } catch (e) {
    if (isOffline(e)) return { id: Date.now(), status: 'scheduled', ...payload }
    throw e
  }
}

export async function deleteTeacherLiveClass(id) {
  try {
    await api.delete(`/teacher/live-classes/${id}`)
  } catch (e) {
    if (!isOffline(e)) throw e
  }
  return true
}

export async function getTeacherAssignments(courseId) {
  try {
    const { data } = await api.get(`/teacher/courses/${courseId}/assignments`)
    return data
  } catch (e) {
    return []
  }
}

export async function createTeacherAssignment({ courseId, title, description, dueDate, file }) {
  const formData = new FormData()
  formData.append('course_id', courseId)
  formData.append('title', title)
  if (description) formData.append('description', description)
  if (dueDate) formData.append('due_date', dueDate)
  if (file) formData.append('file', file)
  const { data } = await uploadFile('/teacher/assignments', formData)
  return data
}

export async function deleteTeacherAssignment(id) {
  try {
    await api.delete(`/teacher/assignments/${id}`)
  } catch (e) {
    /* no-op */
  }
  return true
}

export async function getSubmissions(assignmentId) {
  try {
    const { data } = await api.get(`/teacher/assignments/${assignmentId}/submissions`)
    return data
  } catch (e) {
    return []
  }
}

export async function gradeSubmission(submissionId, { grade, feedback }) {
  const { data } = await api.post(`/teacher/submissions/${submissionId}/grade`, { grade, feedback })
  return data
}

// Buscar alumnos para inscribirlos en un curso del profesor.
export async function searchStudents(q) {
  try {
    const { data } = await api.get('/teacher/students/search', { params: { q } })
    return data
  } catch (e) {
    if (isOffline(e)) return []
    throw e
  }
}

export async function enrollStudent(courseId, userId) {
  try {
    const { data } = await api.post('/teacher/enrollments', { course_id: courseId, user_id: userId })
    return data
  } catch (e) {
    if (isOffline(e)) return { message: 'Alumno inscrito (modo demo).', already_enrolled: false }
    throw e
  }
}

// Avance, lecciones, tareas y calificaciones de un alumno en un curso.
export async function getStudentDetail(courseId, userId) {
  const { data } = await api.get(`/teacher/courses/${courseId}/students/${userId}`)
  return data
}
