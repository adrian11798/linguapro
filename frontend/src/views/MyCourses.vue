<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { getCourses, getMyEnrollments, getLessonsByCourse, getMyProgress } from '../services/courses'
import CourseCard from '../components/CourseCard.vue'

const auth = useAuthStore()
const router = useRouter()

const myCourses = ref([])
const filter = ref('all') // all | in-progress | completed

onMounted(async () => {
  const [courses, enrollments, progress] = await Promise.all([
    getCourses(),
    getMyEnrollments(auth.user.id),
    getMyProgress(auth.user.id)
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
})

const filtered = computed(() => {
  if (filter.value === 'in-progress') return myCourses.value.filter((c) => c.percent < 100)
  if (filter.value === 'completed') return myCourses.value.filter((c) => c.percent >= 100)
  return myCourses.value
})

function openCourse(course) {
  router.push(`/courses/${course.id}`)
}
</script>

<template>
  <div class="page">
    <h1>Mis Cursos</h1>

    <div class="tabs">
      <button :class="['tab', { active: filter === 'all' }]" @click="filter = 'all'">Todos</button>
      <button :class="['tab', { active: filter === 'in-progress' }]" @click="filter = 'in-progress'">En curso</button>
      <button :class="['tab', { active: filter === 'completed' }]" @click="filter = 'completed'">Completados</button>
    </div>

    <div v-if="filtered.length" class="courses-grid">
      <CourseCard
        v-for="course in filtered"
        :key="course.id"
        :course="course"
        :percent="course.percent"
        action-label="Continuar lección"
        @action="openCourse"
      />
    </div>
    <p v-else class="muted">No tienes cursos en esta categoría todavía.</p>
  </div>
</template>

<style scoped>
.tabs {
  display: flex;
  gap: 0.6rem;
  margin: 1.25rem 0 1.75rem;
}
.tab {
  border: 1px solid var(--color-border);
  background: #fff;
  border-radius: 999px;
  padding: 0.45rem 1.1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-muted);
}
.tab.active {
  background: rgba(59, 130, 246, 0.12);
  color: var(--color-accent);
  border-color: transparent;
}
.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.5rem;
}
</style>
