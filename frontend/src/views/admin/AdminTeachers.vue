<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { getAdminTeachers, createAdminTeacher } from '../../services/people'
import { errorMessage } from '../../services/api'

const teachers = ref([])
const query = ref('')
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const form = reactive({ name: '', email: '', password: '' })

onMounted(async () => {
  teachers.value = await getAdminTeachers()
})

const normalize = (t) => (t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()
const initials = (name) => (name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')

const filtered = computed(() => {
  const q = normalize(query.value)
  if (!q) return teachers.value
  return teachers.value.filter((t) => normalize(t.name).includes(q) || normalize(t.email).includes(q))
})

function openNew() {
  Object.assign(form, { name: '', email: '', password: '' })
  formError.value = ''
  showForm.value = true
}

async function save() {
  formError.value = ''
  if (!form.name.trim() || !form.email.trim() || !form.password) {
    formError.value = 'Completa nombre, correo y contraseña.'
    return
  }
  saving.value = true
  try {
    const teacher = await createAdminTeacher({ ...form })
    teachers.value.push(teacher)
    teachers.value.sort((a, b) => a.name.localeCompare(b.name))
    showForm.value = false
  } catch (e) {
    formError.value = errorMessage(e, 'No se pudo crear al maestro.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Maestros</h1>
        <p class="muted">Quién imparte cada curso y cuántos alumnos atiende.</p>
      </div>
      <button class="btn btn-primary" @click="openNew">+ Nuevo maestro</button>
    </div>

    <div class="toolbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" class="search-icon">
          <circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" />
        </svg>
        <input v-model="query" type="search" placeholder="Buscar por nombre o correo" aria-label="Buscar maestro" />
        <button v-if="query" class="clear-btn" aria-label="Borrar búsqueda" @click="query = ''">×</button>
      </div>
      <span class="muted count">{{ filtered.length }} {{ filtered.length === 1 ? 'maestro' : 'maestros' }}</span>
    </div>

    <div class="card table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Maestro</th>
            <th>Cursos que imparte</th>
            <th>Alumnos</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in filtered" :key="t.id">
            <td>
              <div class="person">
                <span class="avatar">{{ initials(t.name) }}</span>
                <div>
                  <b>{{ t.name }}</b>
                  <span class="muted email">{{ t.email }}</span>
                </div>
              </div>
            </td>
            <td>
              <div v-if="t.courses.length" class="chips">
                <span v-for="c in t.courses" :key="c.id" class="chip">{{ c.name }}</span>
              </div>
              <span v-else class="muted">
                Sin cursos — asígnalos desde <router-link to="/admin/courses" class="link">Cursos</router-link>
              </span>
            </td>
            <td><b>{{ t.students_count }}</b></td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="3" class="empty">
              {{ teachers.length ? 'Ningún maestro coincide con la búsqueda.' : 'Todavía no hay maestros. Agrega el primero con «Nuevo maestro».' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal card">
        <h3>Nuevo maestro</h3>
        <div class="form-field">
          <label>Nombre completo</label>
          <input v-model="form.name" type="text" placeholder="Ej. Laura Ramírez" />
        </div>
        <div class="form-field">
          <label>Correo</label>
          <input v-model="form.email" type="email" placeholder="maestro@escuela.com" />
        </div>
        <div class="form-field">
          <label>Contraseña inicial</label>
          <input v-model="form.password" type="text" placeholder="Mínimo 8 caracteres" />
        </div>
        <p v-if="formError" class="error-banner">{{ formError }}</p>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showForm = false">Cancelar</button>
          <button class="btn btn-primary" :disabled="saving" @click="save">{{ saving ? 'Guardando...' : 'Crear maestro' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; }
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
.search-box { position: relative; flex: 1; max-width: 380px; min-width: 220px; }
.search-icon { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--color-text-muted); pointer-events: none; }
.search-box input {
  width: 100%; padding: 0.7rem 2.2rem 0.7rem 2.5rem; border: 1px solid var(--color-border);
  border-radius: 999px; background: #fff; font-family: var(--font-body); font-size: 0.92rem; outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.search-box input:focus { border-color: var(--color-accent); box-shadow: var(--ring); }
.search-box input::-webkit-search-cancel-button { display: none; }
.clear-btn {
  position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%); width: 24px; height: 24px;
  border-radius: 50%; border: none; background: #eef0f3; color: var(--color-text-muted); font-size: 1.1rem; line-height: 1; cursor: pointer;
}
.count { font-size: 0.85rem; }
.table-card { padding: 1rem 1.4rem; }
.person { display: flex; align-items: center; gap: 0.75rem; }
.person b { display: block; font-weight: 600; color: var(--color-primary); }
.email { font-size: 0.82rem; }
.chips { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.chip { background: rgba(59, 130, 246, 0.1); color: #1d4ed8; border-radius: 999px; padding: 0.18rem 0.65rem; font-size: 0.78rem; font-weight: 500; }
.link { color: var(--color-accent); font-weight: 600; }
.empty { text-align: center; color: var(--color-text-muted); padding: 2rem 0.5rem !important; }

.error-banner { background: rgba(220, 38, 38, 0.08); color: var(--color-danger); border-radius: var(--radius-md); padding: 0.7rem 1rem; font-size: 0.88rem; margin: 0 0 0.8rem; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(13, 35, 58, 0.35); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { width: 440px; max-width: 92vw; padding: 1.75rem; max-height: 88vh; overflow-y: auto; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.7rem; margin-top: 0.5rem; }
</style>
