<script setup>
import { ref, computed, onMounted } from 'vue'
import { getAdminStudents, setStudentEnabled } from '../../services/people'
import { errorMessage } from '../../services/api'

const students = ref([])
const query = ref('')
const filter = ref('all') // all | enabled | disabled
const error = ref('')

onMounted(async () => {
  students.value = await getAdminStudents()
})

const normalize = (t) => (t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()
const initials = (name) => (name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')

const filtered = computed(() => {
  const q = normalize(query.value)
  return students.value.filter((s) => {
    if (filter.value === 'enabled' && !s.is_enabled) return false
    if (filter.value === 'disabled' && s.is_enabled) return false
    return !q || normalize(s.name).includes(q) || normalize(s.email).includes(q)
  })
})
const disabledCount = computed(() => students.value.filter((s) => !s.is_enabled).length)

async function changeStatus(student, event) {
  const wantsEnabled = event.target.value === 'enabled'
  error.value = ''

  if (!wantsEnabled) {
    const ok = confirm(
      `¿Deshabilitar a ${student.name}?\n\nDejará de ver sus cursos y le aparecerá el aviso: «Favor de ponerse al corriente en sus pagos».`
    )
    if (!ok) {
      event.target.value = 'enabled'
      return
    }
  }

  try {
    await setStudentEnabled(student.id, wantsEnabled)
    student.is_enabled = wantsEnabled
  } catch (e) {
    event.target.value = student.is_enabled ? 'enabled' : 'disabled'
    error.value = errorMessage(e, 'No se pudo cambiar el estado del alumno.')
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Alumnos</h1>
      <p class="muted">Consulta a tus alumnos y decide quién tiene acceso a los cursos.</p>
    </div>

    <div class="toolbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" class="search-icon">
          <circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" />
        </svg>
        <input v-model="query" type="search" placeholder="Buscar por nombre o correo" aria-label="Buscar alumno" />
        <button v-if="query" class="clear-btn" aria-label="Borrar búsqueda" @click="query = ''">×</button>
      </div>
      <div class="segmented" role="group" aria-label="Filtrar por acceso">
        <button :class="{ active: filter === 'all' }" @click="filter = 'all'">Todos</button>
        <button :class="{ active: filter === 'enabled' }" @click="filter = 'enabled'">Habilitados</button>
        <button :class="{ active: filter === 'disabled' }" @click="filter = 'disabled'">
          Deshabilitados <span v-if="disabledCount" class="pill">{{ disabledCount }}</span>
        </button>
      </div>
    </div>

    <p v-if="error" class="error-banner">{{ error }}</p>

    <div class="card table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Alumno</th>
            <th>Cursos inscritos</th>
            <th>Acceso</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in filtered" :key="s.id" :class="{ 'is-disabled': !s.is_enabled }">
            <td>
              <div class="person">
                <span class="avatar">{{ initials(s.name) }}</span>
                <div>
                  <b>{{ s.name }}</b>
                  <span class="muted email">{{ s.email }}</span>
                </div>
              </div>
            </td>
            <td>
              <div v-if="s.courses.length" class="chips">
                <span v-for="c in s.courses" :key="c" class="chip">{{ c }}</span>
              </div>
              <span v-else class="muted">Sin cursos</span>
            </td>
            <td>
              <div class="access">
                <select
                  :value="s.is_enabled ? 'enabled' : 'disabled'"
                  :class="['status-select', s.is_enabled ? 'on' : 'off']"
                  @change="changeStatus(s, $event)"
                >
                  <option value="enabled">Habilitado</option>
                  <option value="disabled">Deshabilitado</option>
                </select>
                <span v-if="!s.is_enabled" class="badge badge-warning">Pagos pendientes</span>
              </div>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="3" class="empty">
              {{ students.length ? 'Ningún alumno coincide con la búsqueda o el filtro.' : 'Todavía no hay alumnos registrados.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.page-header { margin-bottom: 1.25rem; }
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
.segmented { display: inline-flex; background: #eef0f3; border-radius: 999px; padding: 3px; }
.segmented button {
  border: none; background: transparent; padding: 0.42rem 0.95rem; border-radius: 999px;
  font-size: 0.84rem; font-weight: 600; color: var(--color-text-muted); cursor: pointer;
}
.segmented button.active { background: #fff; color: var(--color-primary); box-shadow: 0 1px 3px rgba(13, 35, 58, 0.15); }
.pill { background: #b45309; color: #fff; border-radius: 999px; font-size: 0.7rem; padding: 0.05rem 0.45rem; margin-left: 0.25rem; }

.error-banner { background: rgba(220, 38, 38, 0.08); color: var(--color-danger); border-radius: var(--radius-md); padding: 0.7rem 1rem; font-size: 0.9rem; margin: 0 0 1rem; }
.table-card { padding: 1rem 1.4rem; }
.person { display: flex; align-items: center; gap: 0.75rem; }
.person b { display: block; font-weight: 600; color: var(--color-primary); }
.email { font-size: 0.82rem; }
.chips { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.chip { background: rgba(59, 130, 246, 0.1); color: #1d4ed8; border-radius: 999px; padding: 0.18rem 0.65rem; font-size: 0.78rem; font-weight: 500; }
.access { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.status-select {
  padding: 0.42rem 0.7rem; border-radius: var(--radius-md); border: 1px solid var(--color-border);
  font-family: var(--font-body); font-size: 0.88rem; font-weight: 600; cursor: pointer; background: #fff;
}
.status-select.on { color: #15803d; border-color: rgba(22, 163, 74, 0.4); background: rgba(22, 163, 74, 0.06); }
.status-select.off { color: #b45309; border-color: rgba(217, 119, 6, 0.45); background: rgba(217, 119, 6, 0.08); }
tr.is-disabled td { background: #fffbf5; }
.empty { text-align: center; color: var(--color-text-muted); padding: 2rem 0.5rem !important; }
</style>
