import api, { isOffline } from './api'

const offlineOr = (e, value) => {
  if (isOffline(e)) return value
  throw e
}

export async function getConversations() {
  try { return (await api.get('/messages')).data } catch (e) { return offlineOr(e, []) }
}

export async function getContacts() {
  try { return (await api.get('/messages/contacts')).data } catch (e) { return offlineOr(e, []) }
}

export async function getUnreadCount() {
  try { return (await api.get('/messages/unread-count')).data.count } catch (e) { return 0 }
}

export async function getThread(userId) {
  try { return (await api.get(`/messages/${userId}`)).data } catch (e) { return offlineOr(e, { user: null, messages: [] }) }
}

export async function sendMessage(recipientId, body) {
  const { data } = await api.post('/messages', { recipient_id: recipientId, body })
  return data
}
