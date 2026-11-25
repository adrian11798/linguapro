import api, { uploadFile } from './api'

export async function getAssignments(courseId) {
  try {
    const { data } = await api.get(`/courses/${courseId}/assignments`)
    return data
  } catch (e) {
    return []
  }
}

export async function submitAssignment(assignmentId, file) {
  const formData = new FormData()
  formData.append('file', file)
  const { data } = await uploadFile(`/assignments/${assignmentId}/submit`, formData)
  return data
}
