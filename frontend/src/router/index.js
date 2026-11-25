import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { public: true } },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('../views/ForgotPassword.vue'), meta: { public: true } },
  { path: '/reset-password', name: 'reset-password', component: () => import('../views/ResetPassword.vue'), meta: { public: true } },
  { path: '/register', name: 'register', component: () => import('../views/Register.vue'), meta: { public: true } },

  // Aviso para alumnos deshabilitados (pagos pendientes)
  { path: '/suspended', name: 'suspended', component: () => import('../views/Suspended.vue'), meta: { roles: ['student'], hideNav: true } },

  // Rutas de estudiante
  { path: '/dashboard', name: 'dashboard', component: () => import('../views/Dashboard.vue'), meta: { roles: ['student'] } },
  { path: '/courses', name: 'courses', component: () => import('../views/Courses.vue'), meta: { roles: ['student'] } },
  { path: '/my-courses', name: 'my-courses', component: () => import('../views/MyCourses.vue'), meta: { roles: ['student'] } },
  { path: '/courses/:id', name: 'course-detail', component: () => import('../views/CourseDetail.vue'), meta: { roles: ['student'] } },
  { path: '/progress', name: 'progress', component: () => import('../views/Progress.vue'), meta: { roles: ['student'] } },
  { path: '/messages', name: 'messages', component: () => import('../views/StudentMessages.vue'), meta: { roles: ['student'] } },
  { path: '/live-classes', name: 'live-classes', component: () => import('../views/LiveClasses.vue'), meta: { roles: ['student'] } },

  // Panel del profesor
  {
    path: '/teacher',
    component: () => import('../views/teacher/TeacherLayout.vue'),
    meta: { roles: ['teacher'] },
    children: [
      { path: '', redirect: '/teacher/students' },
      { path: 'students', name: 'teacher-students', component: () => import('../views/teacher/TeacherDashboard.vue') },
      { path: 'lessons', name: 'teacher-lessons', component: () => import('../views/teacher/TeacherLessons.vue') },
      { path: 'assignments', name: 'teacher-assignments', component: () => import('../views/teacher/TeacherAssignments.vue') },
      { path: 'messages', name: 'teacher-messages', component: () => import('../views/Messages.vue') },
      { path: 'live-classes', name: 'teacher-live-classes', component: () => import('../views/teacher/TeacherLiveClasses.vue') }
    ]
  },

  // Panel administrativo
  {
    path: '/admin',
    component: () => import('../views/admin/AdminLayout.vue'),
    meta: { roles: ['admin'] },
    children: [
      { path: '', redirect: '/admin/courses' },
      { path: 'courses', name: 'admin-courses', component: () => import('../views/admin/AdminCourses.vue') },
      { path: 'lessons', name: 'admin-lessons', component: () => import('../views/admin/AdminLessons.vue') },
      { path: 'teachers', name: 'admin-teachers', component: () => import('../views/admin/AdminTeachers.vue') },
      { path: 'students', name: 'admin-students', component: () => import('../views/admin/AdminStudents.vue') },
      { path: 'users', name: 'admin-users', component: () => import('../views/admin/AdminUsers.vue') },
      { path: 'live-classes', name: 'admin-live-classes', component: () => import('../views/admin/AdminLiveClasses.vue') }
    ]
  },

  { path: '/:pathMatch(.*)*', redirect: '/login' }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Redirige según rol y protege rutas privadas (RF-03: control de acceso por roles).
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    if (auth.isAuthenticated && (to.name === 'login' || to.name === 'register')) {
      return roleHome(auth.role)
    }
    return true
  }

  if (!auth.isAuthenticated) {
    return { name: 'login' }
  }

  // Alumno deshabilitado: solo puede ver el aviso de pagos.
  if (auth.isSuspended) {
    return to.name === 'suspended' ? true : { name: 'suspended' }
  }
  if (to.name === 'suspended') {
    return roleHome(auth.role)
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
    return roleHome(auth.role)
  }

  return true
})

function roleHome(role) {
  if (role === 'admin') return { path: '/admin/courses' }
  if (role === 'teacher') return { path: '/teacher' }
  return { path: '/dashboard' }
}

export default router
