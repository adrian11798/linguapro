// Datos de ejemplo alineados al modelo de base de datos del
// "Documento Base LinguaPro" (tablas: users, courses, lessons,
// enrollments, progress, live_classes). Se usan como respaldo
// mientras el backend Laravel se conecta, y como datos de prueba.

export const mockUsers = [
  { id: 1, name: 'Evelyn Guzmán', email: 'evelyn@linguapro.mx', role: 'student', avatar: null },
  { id: 2, name: 'Prof. Laura R.', email: 'laura@linguapro.mx', role: 'teacher', avatar: null },
  { id: 3, name: 'Admin LinguaPro', email: 'admin@linguapro.mx', role: 'admin', avatar: null },
  { id: 4, name: 'Ana Gómez', email: 'ana@linguapro.mx', role: 'student', avatar: null },
  { id: 5, name: 'Carlos Díaz', email: 'carlos@linguapro.mx', role: 'student', avatar: null }
]

export const mockCourses = [
  {
    id: 1,
    name: 'Inglés Avanzado C1',
    description: 'Perfecciona fluidez, vocabulario de negocios y comprensión auditiva a nivel avanzado.',
    language: 'Inglés',
    level: 'C1',
    image: 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=600&q=80',
    status: 'published'
  },
  {
    id: 2,
    name: 'Francés Básico A2',
    description: 'Conversación cotidiana, gramática esencial y pronunciación para principiantes.',
    language: 'Francés',
    level: 'A2',
    image: 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&q=80',
    status: 'published'
  },
  {
    id: 3,
    name: 'Francés Intermedio B2',
    description: 'Consolida estructuras gramaticales y amplía tu vocabulario para contextos profesionales.',
    language: 'Francés',
    level: 'B2',
    image: 'https://images.unsplash.com/photo-1431274172761-fca41d930114?w=600&q=80',
    status: 'published'
  },
  {
    id: 4,
    name: 'Alemán Básico A1',
    description: 'Tus primeros pasos en alemán: saludos, presentaciones y frases básicas.',
    language: 'Alemán',
    level: 'A1',
    image: 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=600&q=80',
    status: 'published'
  }
]

export const mockLessons = [
  { id: 1, course_id: 1, title: 'Vocabulario de negocios', description: 'Términos y expresiones para reuniones y correos.', video_url: '', material_url: '', order: 1 },
  { id: 2, course_id: 1, title: 'Comprensión auditiva avanzada', description: 'Práctica con audios reales de noticias y podcasts.', video_url: '', material_url: '', order: 2 },
  { id: 3, course_id: 1, title: 'Debate y argumentación', description: 'Estructura tus ideas para defender un punto de vista.', video_url: '', material_url: '', order: 3 },
  { id: 4, course_id: 2, title: 'Saludos y presentaciones', description: 'Cómo presentarte y saludar en distintos contextos.', video_url: '', material_url: '', order: 1 },
  { id: 5, course_id: 2, title: 'Pronunciación avanzada', description: 'Sonidos difíciles del francés y cómo dominarlos.', video_url: '', material_url: '', order: 2 },
  { id: 6, course_id: 3, title: 'Tiempos verbales compuestos', description: 'Passé composé e imparfait en contexto.', video_url: '', material_url: '', order: 1 },
  { id: 7, course_id: 3, title: 'Francés para el trabajo', description: 'Vocabulario y frases para el entorno profesional.', video_url: '', material_url: '', order: 2 }
]

export const mockEnrollments = [
  { id: 1, user_id: 1, course_id: 1, enrolled_at: '2026-06-01', status: 'active' },
  { id: 2, user_id: 1, course_id: 2, enrolled_at: '2026-06-03', status: 'active' }
]

export const mockProgress = [
  { id: 1, user_id: 1, lesson_id: 1, completed: true, completed_at: '2026-06-05' },
  { id: 2, user_id: 1, lesson_id: 2, completed: false, completed_at: null },
  { id: 3, user_id: 1, lesson_id: 3, completed: false, completed_at: null },
  { id: 4, user_id: 1, lesson_id: 4, completed: true, completed_at: '2026-06-02' }
]

const daysFromToday = (n) => {
  const d = new Date()
  d.setDate(d.getDate() + n)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

export const mockLiveClasses = [
  { id: 1, course_id: 2, course: { id: 2, name: 'Francés Básico A2' }, title: 'Clase de Francés A2 - Pronunciación Avanzada', date: daysFromToday(0), time: '23:00', meeting_url: 'https://meet.google.com/example-1', status: 'scheduled' },
  { id: 2, course_id: 1, course: { id: 1, name: 'Inglés Avanzado C1' }, title: 'Clase de Inglés C1 - Conversación de Negocios', date: daysFromToday(1), time: '18:00', meeting_url: 'https://meet.google.com/example-2', status: 'scheduled' },
  { id: 3, course_id: 3, course: { id: 3, name: 'Francés Intermedio B2' }, title: 'Clase de Francés B2 - Taller de Escritura', date: daysFromToday(3), time: '17:00', meeting_url: 'https://meet.google.com/example-3', status: 'scheduled' }
]

// Alumnos de un profesor, con su progreso general por curso (ver mockup "Panel del Profesor").
export const mockTeacherStudents = [
  { id: 4, name: 'Ana Gómez', course: 'Inglés C1 Avanzado', progress: 78 },
  { id: 5, name: 'Carlos Díaz', course: 'Francés B2 Intermedio', progress: 45 },
  { id: 6, name: 'Sofia García', course: 'Francés C1 Avanzado', progress: 60 },
  { id: 7, name: 'Anna Dvan', course: 'Francés B2 Intermedio', progress: 78 }
]

// Maestros y alumnos para el panel administrativo en modo demo (sin backend).
export const mockAdminTeachers = [
  { id: 2, name: 'Prof. Laura R.', email: 'laura@linguapro.mx', courses: [{ id: 1, name: 'Inglés Avanzado C1' }, { id: 2, name: 'Francés Básico A2' }], students_count: 3 }
]
export const mockAdminStudents = [
  { id: 1, name: 'Evelyn Guzmán', email: 'evelyn@linguapro.mx', is_enabled: true, courses: ['Inglés Avanzado C1', 'Francés Básico A2'] },
  { id: 4, name: 'Ana Gómez', email: 'ana@linguapro.mx', is_enabled: true, courses: ['Inglés Avanzado C1'] },
  { id: 5, name: 'Carlos Díaz', email: 'carlos@linguapro.mx', is_enabled: false, courses: [] }
]
