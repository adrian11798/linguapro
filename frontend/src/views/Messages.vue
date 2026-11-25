<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { getConversations, getContacts, getThread, sendMessage } from '../services/messages'
import { useMessagesStore } from '../store/messages'
import { errorMessage } from '../services/api'

const route = useRoute()
const store = useMessagesStore()

const conversations = ref([])
const contacts = ref([])
const active = ref(null) // { id, name, role }
const messages = ref([])
const draft = ref('')
const sending = ref(false)
const error = ref('')
const loaded = ref(false)
const scroller = ref(null)
let timer = null

const roleLabel = (r) => (r === 'teacher' ? 'Maestro' : 'Alumno')
const initials = (name) => (name || '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')

function when(iso) {
  const d = new Date(iso)
  const sameDay = d.toDateString() === new Date().toDateString()
  return sameDay
    ? d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
    : d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }) + ' ' + d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}

// Personas con las que aún no hay conversación.
const newContacts = computed(() =>
  contacts.value.filter((c) => !conversations.value.some((v) => v.user.id === c.id))
)

async function scrollToEnd() {
  await nextTick()
  if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight
}

async function loadThread(user) {
  const data = await getThread(user.id)
  messages.value = data.messages
  await scrollToEnd()
  conversations.value = await getConversations()
  store.refresh()
}

async function open(user) {
  error.value = ''
  active.value = user
  await loadThread(user)
}

async function send() {
  const body = draft.value.trim()
  if (!body || !active.value || sending.value) return
  sending.value = true
  error.value = ''
  try {
    const msg = await sendMessage(active.value.id, body)
    messages.value.push(msg)
    draft.value = ''
    await scrollToEnd()
    conversations.value = await getConversations()
  } catch (e) {
    error.value = errorMessage(e, 'No se pudo enviar el mensaje.')
  } finally {
    sending.value = false
  }
}

function onKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    send()
  }
}

async function refreshQuietly() {
  conversations.value = await getConversations()
  if (active.value) {
    const data = await getThread(active.value.id)
    if (data.messages.length !== messages.value.length) {
      messages.value = data.messages
      scrollToEnd()
    }
    store.refresh()
  }
}

onMounted(async () => {
  ;[conversations.value, contacts.value] = await Promise.all([getConversations(), getContacts()])
  loaded.value = true

  const to = Number(route.query.to)
  if (to) {
    const target = contacts.value.find((c) => c.id === to) || conversations.value.find((v) => v.user.id === to)?.user
    if (target) await open(target)
  } else if (conversations.value.length) {
    await open(conversations.value[0].user)
  }
  timer = setInterval(refreshQuietly, 20000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div>
    <h1>Mensajes</h1>
    <p class="muted intro">Conversa con los maestros y alumnos de tus cursos.</p>

    <div class="chat card">
      <aside class="side">
        <div v-if="newContacts.length" class="new-wrap">
          <select class="new-select" @change="(e) => { const c = contacts.find((x) => x.id === Number(e.target.value)); if (c) open(c); e.target.value = '' }">
            <option value="">+ Nuevo mensaje a…</option>
            <option v-for="c in newContacts" :key="c.id" :value="c.id">{{ c.name }} ({{ roleLabel(c.role) }})</option>
          </select>
        </div>

        <button
          v-for="v in conversations"
          :key="v.user.id"
          :class="['conv', { active: active?.id === v.user.id }]"
          @click="open(v.user)"
        >
          <span class="avatar">{{ initials(v.user.name) }}</span>
          <span class="conv-text">
            <b>{{ v.user.name }}</b>
            <span class="preview">{{ v.last_message }}</span>
          </span>
          <span v-if="v.unread" class="unread">{{ v.unread }}</span>
        </button>

        <p v-if="loaded && !conversations.length && !newContacts.length" class="muted empty-side">
          Aún no tienes con quién conversar: aparecerán aquí las personas de tus cursos.
        </p>
      </aside>

      <section class="thread">
        <template v-if="active">
          <header class="thread-head">
            <span class="avatar">{{ initials(active.name) }}</span>
            <div>
              <b>{{ active.name }}</b>
              <span class="muted role">{{ roleLabel(active.role) }}</span>
            </div>
          </header>

          <div ref="scroller" class="messages">
            <p v-if="!messages.length" class="muted start">Escribe el primer mensaje para {{ active.name.split(' ')[0] }}.</p>
            <div v-for="m in messages" :key="m.id" :class="['bubble-row', m.mine ? 'mine' : 'theirs']">
              <div class="bubble">
                <p>{{ m.body }}</p>
                <span class="time">{{ when(m.created_at) }}</span>
              </div>
            </div>
          </div>

          <p v-if="error" class="error-text composer-error">{{ error }}</p>
          <form class="composer" @submit.prevent="send">
            <textarea v-model="draft" rows="2" placeholder="Escribe un mensaje… (Enter envía, Shift+Enter salta de línea)" maxlength="2000" @keydown="onKey" />
            <button class="btn btn-primary" type="submit" :disabled="sending || !draft.trim()">Enviar</button>
          </form>
        </template>

        <div v-else class="placeholder muted">
          <p>Selecciona una conversación para verla.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.intro { margin-bottom: 1.2rem; }
.chat { display: grid; grid-template-columns: 300px 1fr; height: 560px; overflow: hidden; padding: 0; }
.side { border-right: 1px solid var(--color-border); overflow-y: auto; padding: 0.7rem; display: flex; flex-direction: column; gap: 0.2rem; }
.new-wrap { padding-bottom: 0.5rem; }
.new-select { width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); font-family: var(--font-body); font-size: 0.88rem; background: #fff; }
.conv { display: flex; align-items: center; gap: 0.7rem; width: 100%; text-align: left; padding: 0.65rem 0.6rem; border: none; background: transparent; border-radius: var(--radius-md); cursor: pointer; font-family: var(--font-body); }
.conv:hover { background: var(--color-bg); }
.conv.active { background: rgba(59, 130, 246, 0.1); }
.conv-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.conv-text b { font-size: 0.9rem; color: var(--color-primary); }
.preview { font-size: 0.8rem; color: var(--color-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.unread { background: var(--color-accent); color: #fff; border-radius: 999px; font-size: 0.72rem; font-weight: 700; padding: 0.1rem 0.5rem; }
.empty-side { font-size: 0.85rem; padding: 0.8rem; line-height: 1.45; }

.thread { display: flex; flex-direction: column; min-width: 0; }
.thread-head { display: flex; align-items: center; gap: 0.7rem; padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--color-border); }
.thread-head b { display: block; color: var(--color-primary); }
.role { font-size: 0.8rem; }
.messages { flex: 1; overflow-y: auto; padding: 1.1rem; display: flex; flex-direction: column; gap: 0.5rem; background: var(--color-bg); }
.start { text-align: center; margin: auto; }
.bubble-row { display: flex; }
.bubble-row.mine { justify-content: flex-end; }
.bubble { max-width: 70%; padding: 0.55rem 0.85rem 0.4rem; border-radius: 14px; font-size: 0.92rem; line-height: 1.4; }
.bubble p { margin: 0; white-space: pre-wrap; word-break: break-word; }
.mine .bubble { background: linear-gradient(180deg, #14304f, #0d233a); color: #fff; border-bottom-right-radius: 4px; }
.theirs .bubble { background: #fff; border: 1px solid var(--color-border); border-bottom-left-radius: 4px; }
.time { display: block; font-size: 0.7rem; margin-top: 0.2rem; opacity: 0.65; text-align: right; }
.composer-error { margin: 0.4rem 1.1rem 0; }
.composer { display: flex; gap: 0.7rem; padding: 0.8rem 1.1rem; border-top: 1px solid var(--color-border); align-items: flex-end; }
.composer textarea { flex: 1; resize: none; padding: 0.6rem 0.8rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); font-family: var(--font-body); font-size: 0.92rem; outline: none; }
.composer textarea:focus { border-color: var(--color-accent); box-shadow: var(--ring); }
.placeholder { margin: auto; }

@media (max-width: 760px) {
  .chat { grid-template-columns: 1fr; height: auto; }
  .side { max-height: 220px; border-right: none; border-bottom: 1px solid var(--color-border); }
  .thread { height: 460px; }
}
</style>
