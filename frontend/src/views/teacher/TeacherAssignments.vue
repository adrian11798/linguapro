<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  getTeacherCourses,
  getTeacherAssignments,
  createTeacherAssignment,
  deleteTeacherAssignment,
  getSubmissions,
  gradeSubmission
} from '../../services/teacher'
import { fileUrl } from '../../services/api'

const courses = ref([])
const selectedCourseId = ref(null)
const assignments = ref([])
const showForm = ref(false)
const form = reactive({ title: '', description: '', dueDate: '', file: null })

const openId = ref(null) // qué tarea tiene las entregas desplegadas
const submissions = ref([])
const gradeDrafts = reactive({}) // { [submissionId]: { grade, feedback } }

onMounted(async () => {
  courses.value = await getTeacherCourses()
  if (courses.value.length) {
    selectedCourseId.value = courses.value[0].id
    await loadAssignments()
  }
})

async function loadAssignments() {
  if (!selectedCourseId.value) return
  openId.value = null
  assignments.value = await getTeacherAssignments(selectedCourseId.value)
}

function openNew() {
  Object.assign(form, { title: '', description: '', dueDate: '', file: null })
  showForm.value = true
}

function onFileChange(e) {
  form.file = e.target.files[0] || null
}

async function save() {
  if (!form.title.trim()) return
  await createTeacherAssignment({
    courseId: selectedCourseId.value,
    title: form.title,
    description: form.description,
    dueDate: form.dueDate,
    file: form.file
  })
  showForm.value = false
  await loadAssignments()
}

async function remove(assignment) {
  if (!confirm(`¿Eliminar la tarea "${assignment.title}"? También se borrarán las entregas.`)) return
  await deleteTeacherAssignment(assignment.id)
  await loadAssignments()
}

async function toggleSubmissions(assignment) {
  if (openId.value === assignment.id) {
    openId.value = null
    return
  }
  openId.value = assignment.id
  submissions.value = await getSubmissions(assignment.id)
  submissions.value.forEach((s) => {
    gradeDrafts[s.id] = { grade: s.grade ?? '', feedback: s.feedback ?? '' }
  })
}

async function saveGrade(submission) {
  const draft = gradeDrafts[submission.id]
  if (draft.grade === '' || draft.grade === null) return
  const updated = await gradeSubmission(submission.id, { grade: Number(draft.grade), feedback: draft.feedback })
  Object.assign(submission, updated)
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Tareas</h1>
        <p class="muted">Deja material para tus alumnos y califica lo que te entreguen.</p>
      </div>
      <button class="btn btn-primary" :disabled="!selectedCourseId" @click="openNew">+ Nueva tarea</button>
    </div>

    <div v-if="!courses.length" class="card empty-card">
      <p class="muted">Todavía no tienes cursos asignados.</p>
    </div>

    <template v-else>
      <div class="form-field course-select">
        <label>Curso</label>
        <select v-model="selectedCourseId" @change="loadAssignments">
          <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <div class="assignments-list">
        <div v-for="a in assignments" :key="a.id" class="card assignment-card">
          <div class="assignment-header" @click="toggleSubmissions(a)">
            <div>
              <h3>{{ a.title }}</h3>
              <p class="muted" v-if="a.description">{{ a.description }}</p>
              <p class="muted meta">
                <span v-if="a.due_date">Entrega: {{ a.due_date }} · </span>
                {{ a.submissions_count }} entrega(s)
                <a v-if="a.file_url" :href="fileUrl(a.file_url)" target="_blank" rel="noopener" @click.stop>· Ver material</a>
              </p>
            </div>
            <div class="header-actions">
              <button class="btn btn-outline btn-sm" @click.stop="remove(a)">Eliminar</button>
              <button class="btn btn-outline btn-sm">{{ openId === a.id ? 'Ocultar' : 'Ver entregas' }}</button>
            </div>
          </div>

          <div v-if="openId === a.id" class="submissions">
            <p v-if="!submissions.length" class="muted">Todavía nadie ha entregado esta tarea.</p>
            <div v-for="s in submissions" :key="s.id" class="submission-row">
              <div class="student-info">
                <b>{{ s.student_name }}</b>
                <span class="muted">{{ s.student_email }}</span>
                <a :href="fileUrl(s.file_url)" target="_blank" rel="noopener">Ver archivo entregado</a>
              </div>
              <div class="grade-form">
                <input
                  v-model="gradeDrafts[s.id].grade"
                  type="number" min="0" max="100" placeholder="Calif."
                  class="grade-input"
                />
                <input
                  v-model="gradeDrafts[s.id].feedback"
                  type="text" placeholder="Comentario (opcional)"
                  class="feedback-input"
                />
                <button class="btn btn-primary btn-sm" @click="saveGrade(s)">Guardar</button>
              </div>
              <p v-if="s.grade !== null && s.grade !== undefined" class="muted graded-note">
                Calificación actual: {{ s.grade }}/100
              </p>
            </div>
          </div>
        </div>
        <p v-if="!assignments.length" class="muted">Este curso todavía no tiene tareas.</p>
      </div>
    </template>

    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal card">
        <h3>Nueva tarea</h3>
        <div class="form-field">
          <label>Título</label>
          <input v-model="form.title" type="text" placeholder="Ej. Ensayo sobre el subjuntivo" />
        </div>
        <div class="form-field">
          <label>Instrucciones</label>
          <textarea v-model="form.description" rows="3"></textarea>
        </div>
        <div class="form-field">
          <label>Fecha límite (opcional)</label>
          <input v-model="form.dueDate" type="date" />
        </div>
        <div class="form-field">
          <label>Material adjunto (opcional)</label>
          <input type="file" @change="onFileChange" />
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
.empty-card { padding: 1.4rem; }
.course-select { max-width: 320px; margin-bottom: 1.25rem; }

.assignments-list { display: flex; flex-direction: column; gap: 1rem; }
.assignment-card { padding: 0; overflow: hidden; }
.assignment-header {
  padding: 1.1rem 1.3rem; display: flex; justify-content: space-between; align-items: flex-start;
  gap: 1rem; cursor: pointer;
}
.assignment-header h3 { margin: 0 0 0.2rem; font-size: 1.05rem; }
.assignment-header p { margin: 0.1rem 0 0; font-size: 0.88rem; }
.meta a { color: var(--color-accent); }
.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

.submissions { border-top: 1px solid var(--color-border); padding: 1rem 1.3rem 1.3rem; background: var(--color-bg); }
.submission-row { padding: 0.8rem 0; border-bottom: 1px solid var(--color-border); }
.submission-row:last-child { border-bottom: none; }
.student-info { display: flex; align-items: center; gap: 0.7rem; font-size: 0.9rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
.student-info a { color: var(--color-accent); font-size: 0.85rem; }
.grade-form { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; }
.grade-input { width: 80px; padding: 0.45rem 0.6rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); }
.feedback-input { flex: 1; min-width: 180px; padding: 0.45rem 0.6rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); }
.graded-note { margin: 0.4rem 0 0; font-size: 0.82rem; }

.modal-backdrop {
  position: fixed; inset: 0; background: rgba(13,35,58,0.35);
  display: flex; align-items: center; justify-content: center; z-index: 50;
}
.modal { width: 460px; max-width: 92vw; padding: 1.75rem; max-height: 88vh; overflow-y: auto; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.7rem; margin-top: 0.5rem; }
</style>
