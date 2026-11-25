<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getCourses, getMyEnrollments, enrollInCourse } from '../services/courses'
import { useAuthStore } from '../store/auth'
import CourseCard from '../components/CourseCard.vue'

const auth = useAuthStore()
const router = useRouter()
const courses = ref([])
const enrolledIds = ref([])
const enrolling = ref(null)

onMounted(async () => {
  const [allCourses, enrollments] = await Promise.all([getCourses(), getMyEnrollments(auth.user.id)])
  courses.value = allCourses
  enrolledIds.value = enrollments.map((e) => e.course_id)
})

async function handleAction(course) {
  if (enrolledIds.value.includes(course.id)) {
    router.push(`/courses/${course.id}`)
    return
  }
  enrolling.value = course.id
  await enrollInCourse(course.id)
  enrolledIds.value.push(course.id)
  enrolling.value = null
}
</script>

<template>
  <div class="page">
    <h1>Explorar Cursos</h1>
    <p class="muted">Descubre todos los cursos de idiomas disponibles en LinguaPro.</p>

    <div class="courses-grid">
      <CourseCard
        v-for="course in courses"
        :key="course.id"
        :course="course"
        :action-label="enrolledIds.includes(course.id) ? 'Ir al curso' : (enrolling === course.id ? 'Inscribiendo...' : 'Inscribirme')"
        @action="handleAction"
      />
    </div>
  </div>
</template>

<style scoped>
.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}
</style>
