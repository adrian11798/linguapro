import { defineStore } from 'pinia'
import { getUnreadCount } from '../services/messages'

export const useMessagesStore = defineStore('messages', {
  state: () => ({ unread: 0, timer: null }),
  actions: {
    async refresh() {
      this.unread = await getUnreadCount()
    },
    // Revisa cada minuto si llegaron mensajes nuevos.
    startPolling() {
      this.refresh()
      if (!this.timer) this.timer = setInterval(() => this.refresh(), 60000)
    },
    stopPolling() {
      clearInterval(this.timer)
      this.timer = null
    }
  }
})
