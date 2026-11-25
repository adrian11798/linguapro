<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  getTeacherStudents,
  getTeacherCourses,
  searchStudents,
  enrollStudent,
  getStudentDetail
} from '../../services/teacher'
import { errorMessage, fileUrl } from '../../services/api'
import ProgressBar from '../../components/ProgressBar.vue'

const router = useRouter()
const students = ref([])
const courses = ref([])

const initials = (name) => (name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')

async function load() {
  students.value = await getTeacherStudents()
}
onMounted(async () => {
  await load()
  courses.value = await getTeacherCourses()
})

// ---------- Mensaje: abre la conversación con ese alumno
function message(s) {
  router.push({ path: '/teacher/messages', query: { to: s.student_id } })
}

// ---------- Añadir estudiante
const showAdd = ref(false)
const add = reactive({ courseId: '', query: '', results: [], searching: false, message: '', error: '' })
let searchTimer = null

function openAdd() {
  Object.assign(add, { courseId: courses.value[0]?.id || '', query: '', results: [], message: '', error: '' })
  showAdd.value = true
}

watch(() => add.query, (q) => {
  clearTimeout(searchTimer)
  add.message = ''
  if (q.trim().length < 2) {
    add.results = []
    return
  }
  searchTimer = setTimeout(async () => {
    add.searching = true
    try {
      add.results = await searchStudents(q.trim())
    } catch (e) {
      add.error = errorMessage(e, 'No se pudo buscar.')
    } finally {
      add.searching = false
    }
  }, 300)
})

async function enroll(student) {
  add.error = ''
  add.message = ''
  try {
    const res = await enrollStudent(add.courseId, student.id)
    add.message = res.message
    await load()
  } catch (e) {
    add.error = errorMessage(e, 'No se pudo inscribir al alumno.')
  }
}

// ---------- Ver: avance, lecciones y tareas del alumno
const detail = ref(null)
const detailLoading = ref(false)
const detailError = ref('')

async function view(s) {
  detail.value = null
  detailError.value = ''
  detailLoading.value = true
  try {
    detail.value = await getStudentDetail(s.course_id, s.student_id)
  } catch (e) {
    detailError.value = errorMessage(e, 'No se pudo cargar el detalle del alumno.')
  } finally {
    detailLoading.value = false
  }
}
const closeDetail = () => { detail.value = null; detailError.value = ''; detailLoading.value = false }
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Panel del Profesor</h1>
      <button class="btn btn-primary" :disabled="!courses.length" @click="openAdd">+ Añadir Estudiante</button>
    </div>

    <div class="card table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Curso</th>
            <th>Progreso</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in students" :key="`${s.student_id}-${s.course_id}`">
            <td>
              <div class="person">
                <span class="avatar">{{ initials(s.student_name) }}</span>
                <b>{{ s.student_name }}</b>
              </div>
            </td>
            <td>{{ s.course_name }}</td>
            <td style="width: 200px;"><ProgressBar :percent="s.progress" /></td>
            <td>
              <div class="actions">
                <button class="btn btn-outline btn-sm" @click="view(s)">Ver</button>
                <button class="btn btn-outline btn-sm" @click="message(s)">Mensaje</button>
              </div>
            </td>
          </tr>
          <tr v-if="!students.length">
            <td colspan="4" class="empty">Aún no tienes alumnos inscritos en tus cursos. Usa «Añadir Estudiante».</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Añadir estudiante -->
    <div v-if="showAdd" class="modal-backdrop" @click.self="showAdd = false">
      <div class="modal card">
        <h3>Añadir estudiante a un curso</h3>
        <div class="form-field">
          <label>Curso</label>
          <select v-model="add.courseId">
            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="form-field">
          <label>Buscar alumno (nombre o correo)</label>
          <input v-model="add.query" type="text" placeholder="Escribe al menos 2 letras" autocomplete="off" />
        </div>

        <ul v-if="add.results.length" class="results">
          <li v-for="r in add.results" :key="r.id">
            <span class="avatar">{{ initials(r.name) }}</span>
            <span class="r-text"><b>{{ r.name }}</b><span class="muted">{{ r.email }}</span></span>
            <button class="btn btn-primary btn-sm" @click="enroll(r)">Inscribir</button>
          </li>
        </ul>
        <p v-else-if="add.query.trim().length >= 2 && !add.searching" class="muted none">
          No hay alumnos con esa búsqueda. Si aún no tiene cuenta, pídele que se registre primero.
        </p>

        <p v-if="add.message" class="ok-banner">{{ add.message }}</p>
        <p v-if="add.error" class="error-banner">{{ add.error }}</p>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showAdd = false">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- Ver alumno -->
    <div v-if="detail || detailLoading || detailError" class="modal-backdrop" @click.self="closeDetail">
      <div class="modal wide card">
        <p v-if="detailLoading" class="muted">Cargando…</p>
        <p v-else-if="detailError" class="error-banner">{{ detailError }}</p>
        <template v-else-if="detail">
          <div class="d-head">
            <span class="avatar big">{{ initials(detail.student.name) }}</span>
            <div>
              <h3>{{ detail.student.name }}</h3>
              <p class="muted d-sub">{{ detail.student.email }} · {{ detail.course.name }}</p>
            </div>
          </div>

          <div class="d-stats">
            <div class="stat"><span class="muted">Avance del curso</span><ProgressBar :percent="detail.progress" /></div>
            <div class="stat">
              <span class="muted">Promedio de tareas</span>
              <b class="avg">{{ detail.average_grade ?? '—' }}<small v-if="detail.average_grade !== null">/100</small></b>
            </div>
          </div>

          <h4>Lecciones</h4>
          <ul class="lessons">
            <li v-for="l in detail.lessons" :key="l.id" :class="{ done: l.completed }">
              <span class="tick">{{ l.completed ? '✓' : '' }}</span>{{ l.title }}
            </li>
            <li v-if="!detail.lessons.length" class="muted">Este curso aún no tiene lecciones.</li>
          </ul>

          <h4>Tareas</h4>
          <table class="data-table small">
            <tbody>
              <tr v-for="a in detail.assignments" :key="a.id">
                <td>{{ a.title }}</td>
                <td>
                  <a v-if="a.file_url" :href="fileUrl(a.file_url)" target="_blank" rel="noopener" class="link">Ver entrega</a>
                  <span v-else class="muted">Sin entregar</span>
                </td>
                <td class="grade">{{ a.grade !== null ? a.grade + '/100' : (a.file_url ? 'Sin calificar' : '') }}</td>
              </tr>
              <tr v-if="!detail.assignments.length"><td class="muted">Este curso aún no tiene tareas.</td></tr>
            </tbody>
          </table>
        </template>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="closeDetail">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.table-card { padding: 1rem 1.4rem; }
.person { display: flex; align-items: center; gap: 0.7rem; }
.person b { font-weight: 600; color: var(--color-primary); }
.actions { display: flex; gap: 0.5rem; }
.empty { text-align: center; color: var(--color-text-muted); padding: 2rem 0.5rem !important; }

.modal-backdrop { position: fixed; inset: 0; background: rgba(13, 35, 58, 0.35); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { width: 480px; max-width: 92vw; padding: 1.75rem; max-height: 88vh; overflow-y: auto; }
.modal.wide { width: 620px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.7rem; margin-top: 1rem; }
.results { list-style: none; margin: 0 0 0.5rem; padding: 0; border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden; }
.results li { display: flex; align-items: center; gap: 0.7rem; padding: 0.6rem 0.8rem; border-bottom: 1px solid var(--color-border); }
.results li:last-child { border-bottom: none; }
.r-text { flex: 1; display: flex; flex-direction: column; min-width: 0; font-size: 0.9rem; }
.r-text .muted { font-size: 0.8rem; }
.none { font-size: 0.88rem; margin: 0.2rem 0 0.5rem; }
.ok-banner { background: rgba(22, 163, 74, 0.1); color: #15803d; border-radius: var(--radius-md); padding: 0.65rem 0.9rem; font-size: 0.88rem; margin: 0.6rem 0 0; }
.error-banner { background: rgba(220, 38, 38, 0.08); color: var(--color-danger); border-radius: var(--radius-md); padding: 0.65rem 0.9rem; font-size: 0.88rem; margin: 0.6rem 0 0; }

.d-head { display: flex; align-items: center; gap: 0.9rem; margin-bottom: 1.1rem; }
.d-head h3 { margin: 0; }
.d-sub { margin: 0.15rem 0 0; font-size: 0.85rem; }
.avatar.big { width: 48px; height: 48px; font-size: 1rem; }
.d-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem; }
.stat { background: var(--color-bg); border-radius: var(--radius-md); padding: 0.8rem 1rem; display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.85rem; }
.avg { font-family: var(--font-heading); font-size: 1.6rem; color: var(--color-primary); line-height: 1; }
.avg small { font-size: 0.8rem; color: var(--color-text-muted); font-family: var(--font-body); }
h4 { font-family: var(--font-body); font-size: 0.82rem; color: var(--color-accent); margin: 1rem 0 0.5rem; }
.lessons { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem; }
.lessons li { display: flex; align-items: center; gap: 0.6rem; color: var(--color-text-muted); }
.lessons li.done { color: var(--color-text); }
.tick { width: 20px; height: 20px; border-radius: 50%; border: 1px solid var(--color-border); display: inline-flex; align-items: center; justify-content: center; font-size: 0.72rem; flex-shrink: 0; }
.done .tick { background: var(--color-accent); border-color: var(--color-accent); color: #fff; }
.data-table.small td { font-size: 0.88rem; }
.grade { font-weight: 600; white-space: nowrap; }
.link { color: var(--color-accent); font-weight: 600; }
</style>
