<script setup>
import { ref, onMounted, reactive } from 'vue'
import { getAdminLiveClasses, createLiveClass, deleteLiveClass } from '../../services/liveClasses'
import { getAdminCourses } from '../../services/courses'
import { errorMessage } from '../../services/api'
import { dayOf, hhmm, dayLabel } from '../../utils/dates'

const classes = ref([])
const courses = ref([])
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const pageError = ref('')
const form = reactive({ course_id: '', title: '', date: '', time: '', meeting_url: '' })

onMounted(async () => {
  courses.value = await getAdminCourses()
  classes.value = await getAdminLiveClasses()
})

function courseName(c) {
  return c.course?.name || courses.value.find((x) => x.id === c.course_id)?.name || '—'
}

function sortClasses() {
  classes.value.sort((a, b) => (dayOf(a.date) + hhmm(a.time)).localeCompare(dayOf(b.date) + hhmm(b.time)))
}

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
    const created = await createLiveClass({ ...form })
    classes.value.push(created)
    sortClasses()
    showForm.value = false
  } catch (e) {
    // Aquí aparece, por ejemplo, el aviso de que el horario se empalma.
    formError.value = errorMessage(e, 'No se pudo guardar la clase.')
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!confirm(`¿Eliminar la clase "${item.title}"?`)) return
  pageError.value = ''
  try {
    await deleteLiveClass(item.id)
    classes.value = classes.value.filter((c) => c.id !== item.id)
  } catch (e) {
    pageError.value = errorMessage(e, 'No se pudo eliminar la clase.')
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Gestión de Clases en Vivo</h1>
        <p class="muted">Programa clases y su enlace de videoconferencia. Cada clase se considera de 1 hora y no puede empalmarse con otra del mismo curso, del mismo profesor o de alumnos en común.</p>
      </div>
      <button class="btn btn-primary" @click="openNew">+ Nueva clase</button>
    </div>

    <p v-if="pageError" class="error-banner">{{ pageError }}</p>

    <div class="card table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Clase</th>
            <th>Curso</th>
            <th>Día</th>
            <th>Hora</th>
            <th>Enlace</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in classes" :key="c.id">
            <td>{{ c.title }}</td>
            <td>{{ courseName(c) }}</td>
            <td>{{ dayLabel(c.date) }}</td>
            <td><b>{{ hhmm(c.time) }}</b></td>
            <td><a :href="c.meeting_url" target="_blank" rel="noopener" class="link">Abrir</a></td>
            <td>
              <div class="actions">
                <button class="btn btn-outline btn-sm" @click="remove(c)">Eliminar</button>
              </div>
            </td>
          </tr>
          <tr v-if="!classes.length">
            <td colspan="6" class="empty">Todavía no hay clases programadas.</td>
          </tr>
        </tbody>
      </table>
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
          <input v-model="form.title" type="text" placeholder="Ej. Clase de Francés A2 - Pronunciación" />
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
.page-header p { max-width: 62ch; font-size: 0.9rem; line-height: 1.5; }
.table-card { padding: 1rem 1.4rem; }
.actions { display: flex; gap: 0.5rem; }
.link { color: var(--color-accent); font-weight: 600; }
.empty { text-align: center; color: var(--color-text-muted); padding: 2rem 0.5rem !important; }
.error-banner { background: rgba(220, 38, 38, 0.08); color: var(--color-danger); border-radius: var(--radius-md); padding: 0.7rem 1rem; font-size: 0.88rem; margin: 0 0 1rem; line-height: 1.45; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(13, 35, 58, 0.35); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { width: 480px; max-width: 92vw; padding: 1.75rem; max-height: 88vh; overflow-y: auto; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.7rem; margin-top: 0.5rem; }
</style>
