<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { getCourses, getMyEnrollments, getMyProgress, getLessonsByCourse } from '../services/courses'
import { getLiveClasses } from '../services/liveClasses'
import ProgressBar from '../components/ProgressBar.vue'
import { dayLabel, hhmm, dayOf, isUpcoming } from '../utils/dates'

const auth = useAuthStore()
const router = useRouter()

const myCourses = ref([])
const nextClass = ref(null)
const weeklyPoints = ref([20, 35, 30, 45, 40, 60, 72]) // demo: % avance acumulado por día

onMounted(async () => {
  const [courses, enrollments, progress, liveClasses] = await Promise.all([
    getCourses(),
    getMyEnrollments(auth.user.id),
    getMyProgress(auth.user.id),
    getLiveClasses()
  ])

  const enrolledIds = enrollments.map((e) => e.course_id)
  const active = courses.filter((c) => enrolledIds.includes(c.id))

  myCourses.value = await Promise.all(
    active.map(async (course) => {
      const lessons = await getLessonsByCourse(course.id)
      const completed = progress.filter(
        (p) => lessons.some((l) => l.id === p.lesson_id) && p.completed
      ).length
      const percent = lessons.length ? Math.round((completed / lessons.length) * 100) : 0
      return { ...course, percent }
    })
  )

  nextClass.value =
    [...liveClasses]
      .filter((c) => isUpcoming(c))
      .sort((a, b) => (dayOf(a.date) + hhmm(a.time)).localeCompare(dayOf(b.date) + hhmm(b.time)))[0] || null
})

const chartPath = computed(() => {
  const max = Math.max(...weeklyPoints.value, 1)
  const stepX = 100 / (weeklyPoints.value.length - 1)
  return weeklyPoints.value
    .map((v, i) => `${i === 0 ? 'M' : 'L'} ${i * stepX},${100 - (v / max) * 100}`)
    .join(' ')
})
</script>

<template>
  <div class="page">
    <h1>¡Bienvenido de nuevo, {{ auth.user?.name?.split(' ')[0] }}!</h1>
    <p class="muted">Aquí está tu resumen de hoy:</p>

    <div class="summary-grid">
      <div class="card summary-card">
        <h3>Próxima Clase</h3>
        <template v-if="nextClass">
          <p class="class-title">{{ nextClass.title }}</p>
          <p class="muted">{{ dayLabel(nextClass.date) }} · {{ hhmm(nextClass.time) }} h</p>
          <a class="btn btn-primary btn-block" :href="nextClass.meeting_url" target="_blank" rel="noopener">
            Unirse a la Clase
          </a>
        </template>
        <p v-else class="muted">No tienes clases programadas por ahora.</p>
      </div>

      <div class="card summary-card">
        <h3>Mis Cursos Activos</h3>
        <div v-if="myCourses.length" class="mini-course" v-for="c in myCourses" :key="c.id">
          <p>{{ c.name }}</p>
          <ProgressBar :percent="c.percent" />
        </div>
        <p v-else class="muted">Aún no te has inscrito a ningún curso.</p>
        <router-link to="/courses" class="btn btn-outline btn-block btn-sm">Explorar cursos</router-link>
      </div>

      <div class="card summary-card">
        <h3>Progreso Semanal</h3>
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="chart">
          <defs>
            <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.28" />
              <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
            </linearGradient>
          </defs>
          <path :d="chartPath + ' L 100,100 L 0,100 Z'" fill="url(#areaFill)" />
          <path :d="chartPath" fill="none" stroke="#3b82f6" stroke-width="2.5" vector-effect="non-scaling-stroke" />
        </svg>
      </div>
    </div>
  </div>
</template>

<style scoped>
.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  margin-top: 1.75rem;
}
.summary-card {
  padding: 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.summary-card h3 {
  font-size: 1.05rem;
}
.class-title {
  font-weight: 600;
  margin: 0;
}
.mini-course {
  margin-bottom: 0.5rem;
}
.mini-course p {
  margin: 0 0 0.3rem 0;
  font-size: 0.9rem;
  font-weight: 500;
}
.chart {
  width: 100%;
  height: 90px;
}
@media (max-width: 900px) {
  .summary-grid { grid-template-columns: 1fr; }
}
</style>
