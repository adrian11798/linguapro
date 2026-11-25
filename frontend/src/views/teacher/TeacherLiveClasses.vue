<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import {
  getTeacherCourses,
  getTeacherLiveClasses,
  createTeacherLiveClass,
  deleteTeacherLiveClass
} from '../../services/teacher'
import { errorMessage } from '../../services/api'
import { dayOf, hhmm, dayLabel, isUpcoming } from '../../utils/dates'

const classes = ref([])
const courses = ref([])
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const pageError = ref('')
const form = reactive({ course_id: '', title: '', date: '', time: '', meeting_url: '' })

onMounted(async () => {
  courses.value = await getTeacherCourses()
  classes.value = await getTeacherLiveClasses()
})

// Solo las que siguen vigentes, ordenadas por horario.
const upcoming = computed(() =>
  classes.value
    .filter((c) => isUpcoming(c))
    .sort((a, b) => (dayOf(a.date) + hhmm(a.time)).localeCompare(dayOf(b.date) + hhmm(b.time)))
)

function openNew() {
  Object.assign(form, { course_id: courses.value[0]?.id || '', title: '', date: '', time: '', meeting_url: '' })
  formError.value = ''
  showForm.value = true
}

async function save() {
  formError.value = ''
  if (!form.title.trim() || !form.date || !form.time || !form.meeting_url.trim()) {
    formError.value = 'Completa el título, la fecha, la hora y el enlace de la videoconferencia.'
    return
  }
  saving.value = true
  try {
    const created = await createTeacherLiveClass({ ...form })
    classes.value.push(created)
    showForm.value = false
  } catch (e) {
    formError.value = errorMessage(e, 'No se pudo guardar la clase.')
  } finally {
    saving.value = false
  }
}

async function remove(c) {
  if (!confirm(`¿Eliminar la clase "${c.title}"?`)) return
  pageError.value = ''
  try {
    await deleteTeacherLiveClass(c.id)
    classes.value = classes.value.filter((x) => x.id !== c.id)
  } catch (e) {
    pageError.value = errorMessage(e, 'No se pudo eliminar la clase.')
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Tus Clases en Vivo</h1>
        <p class="muted">Programa clases para tus cursos y entra a darlas con un clic. No se pueden empalmar dos clases a la misma hora.</p>
      </div>
      <button class="btn btn-primary" :disabled="!courses.length" @click="openNew">+ Nueva clase</button>
    </div>

    <p v-if="pageError" class="error-banner">{{ pageError }}</p>

    <div class="classes-list">
      <div v-for="c in upcoming" :key="c.id" class="card class-item">
        <div class="when">
          <span class="day">{{ dayLabel(c.date) }}</span>
          <span class="hour">{{ hhmm(c.time) }} h</span>
        </div>
        <div class="info">
          <h3>{{ c.title }}</h3>
          <p class="muted course-name">{{ c.course?.name }}</p>
        </div>
        <div class="buttons">
          <a class="btn btn-primary btn-sm" :href="c.meeting_url" target="_blank" rel="noopener">Entrar a dar la clase</a>
          <button class="btn btn-outline btn-sm" @click="remove(c)">Eliminar</button>
        </div>
      </div>
      <p v-if="!upcoming.length" class="muted">No tienes clases en vivo programadas todavía.</p>
    </div>

    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal card">
        <h3>Nueva clase en vivo</h3>
        <div class="form-field">
          <label>Curso</label>
          <select v-model="form.course_id">
            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="form-field">
          <label>Título</label>
          <input v-model="form.title" type="text" placeholder="Ej. Conversación de negocios" />
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Fecha</label>
            <input v-model="form.date" type="date" />
          </div>
          <div class="form-field">
            <label>Hora</label>
            <input v-model="form.time" type="time" />
          </div>
        </div>
        <div class="form-field">
          <label>Enlace de videoconferencia</label>
          <input v-model="form.meeting_url" type="text" placeholder="https://meet.google.com/..." />
        </div>
        <p v-if="formError" class="error-banner">{{ formError }}</p>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showForm = false">Cancelar</button>
          <button class="btn btn-primary" :disabled="saving" @click="save">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; margin-bottom: 1.5rem; }
.page-header p { max-width: 58ch; font-size: 0.9rem; }
.classes-list { display: flex; flex-direction: column; gap: 0.9rem; max-width: 760px; }
.class-item { padding: 1.1rem 1.3rem; display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap; }
.when { min-width: 110px; display: flex; flex-direction: column; }
.day { font-size: 0.82rem; color: var(--color-text-muted); }
.hour { font-family: var(--font-heading); font-size: 1.4rem; font-weight: 700; color: var(--color-primary); }
.info { flex: 1; min-width: 180px; }
.info h3 { margin: 0; font-size: 1.02rem; }
.course-name { margin: 0.15rem 0 0; font-size: 0.85rem; }
.buttons { display: flex; gap: 0.5rem; }
.error-banner { background: rgba(220, 38, 38, 0.08); color: var(--color-danger); border-radius: var(--radius-md); padding: 0.7rem 1rem; font-size: 0.88rem; margin: 0 0 1rem; line-height: 1.45; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(13, 35, 58, 0.35); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { width: 480px; max-width: 92vw; padding: 1.75rem; max-height: 88vh; overflow-y: auto; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.7rem; margin-top: 0.5rem; }
</style>
