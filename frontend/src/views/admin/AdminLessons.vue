<script setup>
import { ref, onMounted, reactive, watch } from 'vue'
import { getAdminCourses, getLessonsByCourse, createLesson, deleteLesson } from '../../services/courses'

const courses = ref([])
const selectedCourseId = ref(null)
const lessons = ref([])
const showForm = ref(false)
const form = reactive({ title: '', description: '', video_url: '', material_url: '', order: 1 })

onMounted(async () => {
  courses.value = await getAdminCourses()
  if (courses.value.length) selectedCourseId.value = courses.value[0].id
})

watch(selectedCourseId, loadLessons, { immediate: false })

async function loadLessons() {
  if (!selectedCourseId.value) return
  lessons.value = await getLessonsByCourse(selectedCourseId.value)
}

function openNew() {
  Object.assign(form, { title: '', description: '', video_url: '', material_url: '', order: lessons.value.length + 1 })
  showForm.value = true
}

async function save() {
  if (!form.title.trim()) return
  await createLesson({ ...form, course_id: selectedCourseId.value })
  showForm.value = false
  await loadLessons()
}

async function remove(lesson) {
  if (!confirm(`¿Eliminar la lección "${lesson.title}"?`)) return
  await deleteLesson(lesson.id)
  await loadLessons()
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Gestión de Contenidos</h1>
        <p class="muted">Organiza las lecciones de cada curso.</p>
      </div>
      <button class="btn btn-primary" :disabled="!selectedCourseId" @click="openNew">+ Nueva lección</button>
    </div>

    <div class="form-field course-select">
      <label>Curso</label>
      <select v-model="selectedCourseId" @change="loadLessons">
        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <div class="card table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Lección</th>
            <th>Descripción</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in lessons" :key="l.id">
            <td>{{ l.order }}</td>
            <td>{{ l.title }}</td>
            <td class="muted">{{ l.description }}</td>
            <td class="actions">
              <button class="btn btn-outline btn-sm" @click="remove(l)">Eliminar</button>
            </td>
          </tr>
          <tr v-if="!lessons.length">
            <td colspan="4" class="muted">Este curso aún no tiene lecciones.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal card">
        <h3>Nueva lección</h3>
        <div class="form-field">
          <label>Título</label>
          <input v-model="form.title" type="text" />
        </div>
        <div class="form-field">
          <label>Descripción</label>
          <textarea v-model="form.description" rows="3"></textarea>
        </div>
        <div class="form-field">
          <label>Video (URL)</label>
          <input v-model="form.video_url" type="text" placeholder="https://..." />
        </div>
        <div class="form-field">
          <label>Material adicional (URL)</label>
          <input v-model="form.material_url" type="text" placeholder="https://..." />
        </div>
        <div class="form-field">
          <label>Orden</label>
          <input v-model.number="form.order" type="number" min="1" />
        </div>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showForm = false">Cancelar</button>
          <button class="btn btn-primary" @click="save">Guardar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; }
.course-select { max-width: 320px; margin-bottom: 1.25rem; }
.table-card { padding: 1rem 1.4rem; }
.actions { display: flex; gap: 0.5rem; }
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(13,35,58,0.35);
  display: flex; align-items: center; justify-content: center; z-index: 50;
}
.modal { width: 460px; max-width: 92vw; padding: 1.75rem; max-height: 88vh; overflow-y: auto; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.7rem; margin-top: 0.5rem; }
</style>
