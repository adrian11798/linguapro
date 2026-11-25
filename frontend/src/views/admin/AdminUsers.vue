<script setup>
import { ref, computed, onMounted } from 'vue'
import { getUsers, updateUserRole, deleteUser } from '../../services/users'

const users = ref([])
const query = ref('')

onMounted(load)

async function load() {
  users.value = await getUsers()
}

// Quita acentos y mayúsculas para que "jose" encuentre a "José".
const normalize = (text) =>
  (text || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()

const filteredUsers = computed(() => {
  const q = normalize(query.value)
  if (!q) return users.value
  return users.value.filter(
    (u) => normalize(u.name).includes(q) || normalize(u.email).includes(q)
  )
})

async function changeRole(user, role) {
  await updateUserRole(user.id, role)
  user.role = role
}

async function remove(user) {
  if (!confirm(`¿Eliminar al usuario "${user.name}"?`)) return
  await deleteUser(user.id)
  await load()
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Gestión de Usuarios</h1>
        <p class="muted">Administra estudiantes, profesores y administradores.</p>
      </div>
    </div>

    <div class="toolbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" class="search-icon">
          <circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" />
        </svg>
        <input
          v-model="query"
          type="search"
          placeholder="Buscar por nombre o correo"
          aria-label="Buscar usuario"
        />
        <button v-if="query" class="clear-btn" aria-label="Borrar búsqueda" @click="query = ''">×</button>
      </div>
      <span class="muted count">
        {{ query ? `${filteredUsers.length} de ${users.length}` : `${users.length}` }}
        {{ users.length === 1 ? 'usuario' : 'usuarios' }}
      </span>
    </div>

    <div class="card table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in filteredUsers" :key="u.id">
            <td>{{ u.name }}</td>
            <td class="muted">{{ u.email }}</td>
            <td>
              <select :value="u.role" @change="changeRole(u, $event.target.value)">
                <option value="student">Estudiante</option>
                <option value="teacher">Profesor</option>
                <option value="admin">Administrador</option>
              </select>
            </td>
            <td>
              <div class="actions">
                <button class="btn btn-outline btn-sm" @click="remove(u)">Eliminar</button>
              </div>
            </td>
          </tr>
          <tr v-if="!filteredUsers.length">
            <td colspan="4" class="empty">
              No se encontró a nadie con «{{ query }}».
              <button class="link-btn" @click="query = ''">Ver todos</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.page-header { margin-bottom: 1.25rem; }

.toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;
}
.search-box { position: relative; flex: 1; max-width: 380px; }
.search-icon {
  position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
  width: 18px; height: 18px; color: var(--color-text-muted); pointer-events: none;
}
.search-box input {
  width: 100%;
  padding: 0.7rem 2.2rem 0.7rem 2.5rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: #fff;
  font-family: var(--font-body); font-size: 0.92rem;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.search-box input:focus { border-color: var(--color-accent); box-shadow: var(--ring); }
.search-box input::-webkit-search-cancel-button { display: none; }
.clear-btn {
  position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%);
  width: 24px; height: 24px; border-radius: 50%; border: none;
  background: #eef0f3; color: var(--color-text-muted);
  font-size: 1.1rem; line-height: 1; cursor: pointer;
}
.clear-btn:hover { background: #e2e6eb; color: var(--color-primary); }
.count { font-size: 0.85rem; }

.table-card { padding: 1rem 1.4rem; }
.actions { display: flex; gap: 0.5rem; }
.empty { text-align: center; color: var(--color-text-muted); padding: 2rem 0.5rem !important; }
.link-btn {
  background: none; border: none; color: var(--color-accent);
  font-weight: 600; cursor: pointer; margin-left: 0.4rem;
}
select {
  padding: 0.4rem 0.6rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  font-family: var(--font-body);
}
</style>
