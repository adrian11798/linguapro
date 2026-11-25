<script setup>
import { ref, onMounted, reactive } from 'vue'
import { getAdminCourses, createCourse, updateCourse, deleteCourse } from '../../services/courses'
import { getUsers } from '../../services/users'

const courses = ref([])
const teachers = ref([])
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', description: '', language: '', level: '', image: '', status: 'draft', teacher_id: '' })

onMounted(async () => {
  await load()
  const users = await getUsers()
  teachers.value = users.filter((u) => u.role === 'teacher')
})

async function load() {
  courses.value = await getAdminCourses()
}

function openNew() {
  editingId.value = null
  Object.assign(form, { name: '', description: '', language: '', level: '', image: '', status: 'draft', teacher_id: '' })
  showForm.value = true
}

function openEdit(course) {
  editingId.value = course.id
  Object.assign(form, course, { teacher_id: course.teacher_id ?? '' })
  showForm.value = true
}

async function save() {
  if (!form.name.trim()) return
  const payload = { ...form, teacher_id: form.teacher_id || null }
  if (editingId.value) {
    await updateCourse(editingId.value, payload)
  } else {
    await createCourse(payload)
  }
  showForm.value = false
  await load()
}

async function remove(course) {
  if (!confirm(`¿Eliminar el curso "${course.name}"?`)) return
  await deleteCourse(course.id)
  await load()
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Gestión de Cursos</h1>
        <p class="muted">Crea, edita y publica los cursos de idiomas de LinguaPro.</p>
      </div>
      <button class="btn btn-primary" @click="openNew">+ Nuevo curso</button>
    </div>

    <div class="card table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Curso</th>
            <th>Idioma</th>
            <th>Nivel</th>
            <th>Profesor</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in courses" :key="c.id">
            <td>{{ c.name }}</td>
            <td>{{ c.language }}</td>
            <td>{{ c.level }}</td>
            <td>{{ c.teacher?.name || 'Sin asignar' }}</td>
            <td>
              <span :class="['badge', c.status === 'published' ? 'badge-success' : 'badge-warning']">
                {{ c.status === 'published' ? 'Publicado' : 'Borrador' }}
              </span>
            </td>
            <td class="actions">
              <button class="btn btn-outline btn-sm" @click="openEdit(c)">Editar</button>
              <button class="btn btn-outline btn-sm" @click="remove(c)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal card">
        <h3>{{ editingId ? 'Editar curso' : 'Nuevo curso' }}</h3>
        <div class="form-field">
          <label>Nombre</label>
          <input v-model="form.name" type="text" placeholder="Ej. Inglés Avanzado C1" />
        </div>
        <div class="form-field">
          <label>Descripción</label>
          <textarea v-model="form.description" rows="3"></textarea>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Idioma</label>
            <input v-model="form.language" type="text" placeholder="Inglés" />
          </div>
          <div class="form-field">
            <label>Nivel</label>
            <input v-model="form.level" type="text" placeholder="C1" />
          </div>
        </div>
        <div class="form-field">
          <label>Imagen (URL)</label>
          <input v-model="form.image" type="text" placeholder="https://..." />
        </div>
        <div class="form-field">
          <label>Profesor</label>
          <select v-model="form.teacher_id">
            <option value="">Sin asignar</option>
            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
        </div>
        <div class="form-field">
          <label>Estado</label>
          <select v-model="form.status">
            <option value="draft">Borrador</option>
            <option value="published">Publicado</option>
          </select>
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
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}
.table-card { padding: 1rem 1.4rem; }
.actions { display: flex; gap: 0.5rem; }

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(13,35,58,0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}
.modal {
  width: 460px;
  max-width: 92vw;
  padding: 1.75rem;
  max-height: 88vh;
  overflow-y: auto;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.7rem;
  margin-top: 0.5rem;
}
</style>
