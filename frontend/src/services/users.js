import api from './api'
import { mockUsers } from './mockData'

export async function getUsers() {
  try {
    const { data } = await api.get('/admin/users')
    return data
  } catch (e) {
    return mockUsers
  }
}

export async function updateUserRole(id, role) {
  try {
    const { data } = await api.put(`/admin/users/${id}`, { role })
    return data
  } catch (e) {
    return { id, role }
  }
}

export async function deleteUser(id) {
  try {
    await api.delete(`/admin/users/${id}`)
  } catch (e) {
    /* modo mock: no-op */
  }
  return true
}
