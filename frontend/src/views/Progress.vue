<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../store/auth'
import { getCourses, getMyEnrollments, getLessonsByCourse, getMyProgress } from '../services/courses'
import ProgressBar from '../components/ProgressBar.vue'

const auth = useAuthStore()
const rows = ref([])

onMounted(async () => {
  const [courses, enrollments, progress] = await Promise.all([
    getCourses(),
    getMyEnrollments(auth.user.id),
    getMyProgress(auth.user.id)
  ])
  const enrolledIds = enrollments.map((e) => e.course_id)
  const active = courses.filter((c) => enrolledIds.includes(c.id))

  rows.value = await Promise.all(
    active.map(async (course) => {
      const lessons = await getLessonsByCourse(course.id)
      const completedLessons = lessons.filter((l) =>
        progress.some((p) => p.lesson_id === l.id && p.completed)
      )
      const percent = lessons.length ? Math.round((completedLessons.length / lessons.length) * 100) : 0
      return {
        course,
        percent,
        completed: completedLessons.length,
        total: lessons.length
      }
    })
  )
})
</script>

<template>
  <div class="page">
    <h1>Tu Rendimiento</h1>
    <p class="muted">Aquí puedes ver tu avance detallado por curso.</p>

    <div class="progress-list">
      <div v-for="row in rows" :key="row.course.id" class="card progress-row">
        <div class="info">
          <h3>{{ row.course.name }}</h3>
          <p class="muted">{{ row.completed }} de {{ row.total }} lecciones completadas</p>
        </div>
        <ProgressBar :percent="row.percent" />
      </div>
      <p v-if="!rows.length" class="muted">Aún no tienes cursos con progreso registrado.</p>
    </div>
  </div>
</template>

<style scoped>
.progress-list {
  margin-top: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.progress-row {
  padding: 1.2rem 1.4rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}
.progress-row h3 { font-size: 1rem; margin-bottom: 0.2rem; }
.info { flex: 1; }
.progress-row :deep(.progress-wrapper) { width: 220px; }
</style>
