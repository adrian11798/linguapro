<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  getTeacherCourses,
  getTeacherLessons,
  createTeacherLesson,
  updateTeacherLesson,
  deleteTeacherLesson
} from '../../services/teacher'
import { getEmbedUrl } from '../../utils/video'

const courses = ref([])
const selectedCourseId = ref(null)
const lessons = ref([])
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ title: '', description: '', video_url: '', order: 1 })

onMounted(async () => {
  courses.value = await getTeacherCourses()
  if (courses.value.length) {
    selectedCourseId.value = courses.value[0].id
    await loadLessons()
  }
})

async function loadLessons() {
  if (!selectedCourseId.value) return
  lessons.value = await getTeacherLessons(selectedCourseId.value)
}

function openNew() {
  editingId.value = null
  Object.assign(form, { title: '', description: '', video_url: '', order: lessons.value.length + 1 })
  showForm.value = true
}

function openEdit(lesson) {
  editingId.value = lesson.id
  Object.assign(form, lesson)
  showForm.value = true
}

async function save() {
  if (!form.title.trim()) return
  if (editingId.value) {
    await updateTeacherLesson(editingId.value, { ...form })
  } else {
    await createTeacherLesson({ ...form, course_id: selectedCourseId.value })
  }
  showForm.value = false
  await loadLessons()
}

async function remove(lesson) {
  if (!confirm(`¿Eliminar la lección "${lesson.title}"?`)) return
  await deleteTeacherLesson(lesson.id)
  await loadLessons()
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Mis Videos</h1>
        <p class="muted">
          Pega el enlace de YouTube, Vimeo o Google Drive de cada video — no necesitas subir el
          archivo, solo compartir el link (puede ser "no listado" para que no sea público).
        </p>
      </div>
      <button class="btn btn-primary" :disabled="!selectedCourseId" @click="openNew">+ Nuevo video</button>
    </div>

    <div v-if="!courses.length" class="card empty-card">
      <p class="muted">Todavía no tienes cursos asignados. Pide al administrador que te asigne uno.</p>
    </div>

    <template v-else>
      <div class="form-field course-select">
        <label>Curso</label>
        <select v-model="selectedCourseId" @change="loadLessons">
          <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <div class="lessons-grid">
        <div v-for="l in lessons" :key="l.id" class="card lesson-card">
          <div class="video-thumb">
            <iframe
              v-if="getEmbedUrl(l.video_url)"
              :src="getEmbedUrl(l.video_url)"
              frameborder="0"
              allowfullscreen
            />
            <span v-else class="muted">Sin video todavía</span>
          </div>
          <div class="lesson-body">
            <h3>{{ l.order }}. {{ l.title }}</h3>
            <p class="muted">{{ l.description }}</p>
            <div class="lesson-actions">
              <button class="btn btn-outline btn-sm" @click="openEdit(l)">Editar enlace</button>
              <button class="btn btn-outline btn-sm" @click="remove(l)">Eliminar</button>
            </div>
          </div>
        </div>
        <p v-if="!lessons.length" class="muted">Este curso todavía no tiene lecciones.</p>
      </div>
    </template>

    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal card">
        <h3>{{ editingId ? 'Editar video' : 'Nuevo video' }}</h3>
        <div class="form-field">
          <label>Título de la lección</label>
          <input v-model="form.title" type="text" placeholder="Ej. Pronunciación avanzada" />
        </div>
        <div class="form-field">
          <label>Descripción</label>
          <textarea v-model="form.description" rows="2"></textarea>
        </div>
        <div class="form-field">
          <label>Enlace del video (YouTube, Vimeo o Google Drive)</label>
          <input v-model="form.video_url" type="text" placeholder="https://youtu.be/..." />
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
.page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; margin-bottom: 1.5rem; }
.page-header p { max-width: 520px; font-size: 0.88rem; }
.empty-card { padding: 1.4rem; }
.course-select { max-width: 320px; margin-bottom: 1.25rem; }

.lessons-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}
.lesson-card { overflow: hidden; }
.video-thumb {
  height: 160px;
  background: #000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.video-thumb iframe { width: 100%; height: 100%; }
.lesson-body { padding: 1rem 1.1rem 1.2rem; }
.lesson-body h3 { font-size: 1rem; margin-bottom: 0.3rem; }
.lesson-actions { display: flex; gap: 0.5rem; margin-top: 0.8rem; }

.modal-backdrop {
  position: fixed; inset: 0; background: rgba(13,35,58,0.35);
  display: flex; align-items: center; justify-content: center; z-index: 50;
}
.modal { width: 460px; max-width: 92vw; padding: 1.75rem; max-height: 88vh; overflow-y: auto; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.7rem; margin-top: 0.5rem; }
</style>
