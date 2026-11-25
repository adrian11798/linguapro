<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { getCourse, getLessonsByCourse, getMyProgress, markLessonComplete } from '../services/courses'
import { getAssignments, submitAssignment } from '../services/assignments'
import { fileUrl } from '../services/api'
import { getEmbedUrl, isDirectVideoFile } from '../utils/video'

const route = useRoute()
const auth = useAuthStore()

const view = ref('lessons') // 'lessons' | 'assignments'
const course = ref(null)
const lessons = ref([])
const progress = ref([])
const activeLesson = ref(null)
const saving = ref(false)

const assignments = ref([])
const pendingFiles = reactive({}) // { [assignmentId]: File }
const uploading = reactive({}) // { [assignmentId]: boolean }

onMounted(async () => {
  const id = route.params.id
  const [c, l, p, a] = await Promise.all([
    getCourse(id),
    getLessonsByCourse(id),
    getMyProgress(auth.user.id),
    getAssignments(id)
  ])
  course.value = c
  lessons.value = l
  progress.value = p
  activeLesson.value = l[0] || null
  assignments.value = a
})

function onFileChange(assignmentId, e) {
  pendingFiles[assignmentId] = e.target.files[0] || null
}

async function submit(assignment) {
  const file = pendingFiles[assignment.id]
  if (!file) return
  uploading[assignment.id] = true
  const submission = await submitAssignment(assignment.id, file)
  assignment.submission = submission
  pendingFiles[assignment.id] = null
  uploading[assignment.id] = false
}

function isCompleted(lessonId) {
  return progress.value.some((p) => p.lesson_id === lessonId && p.completed)
}

const percent = computed(() => {
  if (!lessons.value.length) return 0
  const done = lessons.value.filter((l) => isCompleted(l.id)).length
  return Math.round((done / lessons.value.length) * 100)
})

const embedUrl = computed(() => (activeLesson.value ? getEmbedUrl(activeLesson.value.video_url) : null))
const isDirectVideo = computed(() => isDirectVideoFile(activeLesson.value?.video_url))

async function completeLesson(lesson) {
  if (isCompleted(lesson.id)) return
  saving.value = true
  const record = await markLessonComplete(lesson.id, auth.user.id)
  progress.value.push(record)
  saving.value = false
}
</script>

<template>
  <div class="page" v-if="course">
    <router-link to="/my-courses" class="back-link">← Volver a Mis Cursos</router-link>
    <h1>{{ course.name }}</h1>
    <p class="muted">{{ course.description }}</p>

    <div class="course-progress">
      <span class="muted">Progreso del curso</span>
      <div class="progress-track">
        <div class="progress-fill" :style="{ width: percent + '%' }" />
      </div>
      <span class="percent-label">{{ percent }}%</span>
    </div>

    <div class="tabs">
      <button :class="['tab', { active: view === 'lessons' }]" @click="view = 'lessons'">Lecciones</button>
      <button :class="['tab', { active: view === 'assignments' }]" @click="view = 'assignments'">
        Tareas <span v-if="assignments.length" class="tab-count">{{ assignments.length }}</span>
      </button>
    </div>

    <div class="content-grid" v-if="view === 'lessons'">
      <div class="player card">
        <div class="video-box" v-if="activeLesson && embedUrl">
          <video v-if="isDirectVideo" :src="embedUrl" controls class="video-el" />
          <iframe
            v-else
            :src="embedUrl"
            class="video-el"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          />
        </div>
        <div class="video-placeholder" v-else>
          <span v-if="activeLesson">El profesor aún no agregó un video para esta lección.</span>
          <span v-else>Selecciona una lección</span>
        </div>
        <div class="player-body" v-if="activeLesson">
          <h3>{{ activeLesson.title }}</h3>
          <p class="muted">{{ activeLesson.description }}</p>
          <button
            class="btn btn-primary"
            :disabled="isCompleted(activeLesson.id) || saving"
            @click="completeLesson(activeLesson)"
          >
            {{ isCompleted(activeLesson.id) ? '✓ Lección completada' : 'Marcar como completada' }}
          </button>
        </div>
      </div>

      <aside class="lesson-list card">
        <h3>Contenido del curso</h3>
        <ul>
          <li
            v-for="lesson in lessons"
            :key="lesson.id"
            :class="{ active: activeLesson?.id === lesson.id }"
            @click="activeLesson = lesson"
          >
            <span class="check" :class="{ done: isCompleted(lesson.id) }">
              {{ isCompleted(lesson.id) ? '✓' : lesson.order }}
            </span>
            <span>{{ lesson.title }}</span>
          </li>
        </ul>
      </aside>
    </div>

    <div class="assignments-panel" v-else>
      <div v-for="a in assignments" :key="a.id" class="card assignment-card">
        <h3>{{ a.title }}</h3>
        <p class="muted" v-if="a.description">{{ a.description }}</p>
        <p class="muted meta">
          <span v-if="a.due_date">Entrega: {{ a.due_date }}</span>
          <a v-if="a.file_url" :href="fileUrl(a.file_url)" target="_blank" rel="noopener">Ver material del profesor</a>
        </p>

        <div class="submission-box">
          <template v-if="a.submission">
            <p class="submitted-note">
              ✓ Entregaste el {{ new Date(a.submission.submitted_at).toLocaleDateString() }} —
              <a :href="fileUrl(a.submission.file_url)" target="_blank" rel="noopener">ver tu archivo</a>
            </p>
            <p v-if="a.submission.grade !== null && a.submission.grade !== undefined" class="grade-note">
              Calificación: <b>{{ a.submission.grade }}/100</b>
              <span v-if="a.submission.feedback"> — {{ a.submission.feedback }}</span>
            </p>
            <p v-else class="muted">Aún sin calificar.</p>
            <details class="resubmit">
              <summary>Volver a entregar</summary>
              <div class="upload-row">
                <input type="file" @change="onFileChange(a.id, $event)" />
                <button class="btn btn-outline btn-sm" :disabled="uploading[a.id]" @click="submit(a)">
                  {{ uploading[a.id] ? 'Subiendo...' : 'Reemplazar entrega' }}
                </button>
              </div>
            </details>
          </template>
          <template v-else>
            <div class="upload-row">
              <input type="file" @change="onFileChange(a.id, $event)" />
              <button class="btn btn-primary btn-sm" :disabled="uploading[a.id]" @click="submit(a)">
                {{ uploading[a.id] ? 'Subiendo...' : 'Entregar tarea' }}
              </button>
            </div>
          </template>
        </div>
      </div>
      <p v-if="!assignments.length" class="muted">Este curso todavía no tiene tareas asignadas.</p>
    </div>
  </div>
</template>

<style scoped>
.back-link {
  display: inline-block;
  margin-bottom: 1rem;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}
.course-progress {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  margin: 1rem 0 1.75rem;
  max-width: 420px;
}
.course-progress .progress-track { flex: 1; }
.percent-label { font-size: 0.85rem; font-weight: 600; }

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  align-items: start;
}
.player { padding: 0; overflow: hidden; }
.video-placeholder {
  height: 300px;
  background: var(--color-primary);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  text-align: center;
  padding: 1rem;
}
.video-box {
  position: relative;
  width: 100%;
  height: 300px;
  background: #000;
}
.video-el {
  width: 100%;
  height: 100%;
  display: block;
}
.player-body { padding: 1.4rem; }
.lesson-list { padding: 1.2rem; }
.lesson-list ul { list-style: none; margin: 1rem 0 0; padding: 0; display: flex; flex-direction: column; gap: 0.3rem; }
.lesson-list li {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.6rem 0.5rem;
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  cursor: pointer;
}
.lesson-list li:hover { background: var(--color-bg); }
.lesson-list li.active { background: rgba(59,130,246,0.1); font-weight: 600; }
.check {
  width: 22px; height: 22px;
  border-radius: 50%;
  border: 1px solid var(--color-border);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem;
  flex-shrink: 0;
}
.check.done { background: var(--color-accent); color: #fff; border-color: var(--color-accent); }

.tabs { display: flex; gap: 0.6rem; margin: 1.25rem 0 1.5rem; }
.tab {
  border: 1px solid var(--color-border);
  background: #fff;
  border-radius: 999px;
  padding: 0.45rem 1.1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-muted);
}
.tab.active { background: rgba(59, 130, 246, 0.12); color: var(--color-accent); border-color: transparent; }
.tab-count {
  display: inline-block; margin-left: 0.35rem; background: var(--color-accent); color: #fff;
  border-radius: 999px; font-size: 0.72rem; padding: 0.05rem 0.45rem;
}

.assignments-panel { display: flex; flex-direction: column; gap: 1rem; max-width: 760px; }
.assignment-card { padding: 1.2rem 1.4rem; }
.assignment-card h3 { margin: 0 0 0.3rem; font-size: 1.05rem; }
.meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.85rem; }
.meta a { color: var(--color-accent); }
.submission-box { margin-top: 0.9rem; padding-top: 0.9rem; border-top: 1px solid var(--color-border); }
.upload-row { display: flex; align-items: center; gap: 0.7rem; flex-wrap: wrap; }
.submitted-note { margin: 0 0 0.3rem; font-size: 0.9rem; }
.submitted-note a { color: var(--color-accent); }
.grade-note { margin: 0 0 0.5rem; font-size: 0.9rem; }
.resubmit { margin-top: 0.6rem; font-size: 0.85rem; color: var(--color-text-muted); cursor: pointer; }

@media (max-width: 860px) {
  .content-grid { grid-template-columns: 1fr; }
}
</style>
