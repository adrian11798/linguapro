<script setup>
import { computed } from 'vue'
import ProgressBar from './ProgressBar.vue'

const props = defineProps({
  course: { type: Object, required: true },
  percent: { type: Number, default: null },
  actionLabel: { type: String, default: 'Continuar lección' }
})
defineEmits(['action'])

// Si el curso no tiene imagen, se genera un degradado con la inicial del idioma.
const gradients = [
  'linear-gradient(135deg, #0d233a, #3b82f6)',
  'linear-gradient(135deg, #14304f, #6366f1)',
  'linear-gradient(135deg, #0f3d5e, #14b8a6)',
  'linear-gradient(135deg, #1e293b, #3b82f6)',
  'linear-gradient(135deg, #312e81, #0d233a)'
]
const seed = computed(() => {
  const t = props.course.language || props.course.name || ''
  return [...t].reduce((a, c) => a + c.charCodeAt(0), 0)
})
// El degradado va siempre debajo: si la foto falla o tarda, no queda un hueco blanco.
const thumbStyle = computed(() => {
  const g = gradients[seed.value % gradients.length]
  return { backgroundImage: props.course.image ? `url(${props.course.image}), ${g}` : g }
})
const initial = computed(() => (props.course.language || props.course.name || '?')[0].toUpperCase())
</script>

<template>
  <div class="course-card card">
    <div class="thumb" :style="thumbStyle">
      <span class="thumb-initial">{{ initial }}</span>
      <span class="level-badge">{{ course.level }}</span>
    </div>
    <div class="body">
      <h3>{{ course.name }}</h3>
      <p class="muted description">{{ course.description }}</p>
      <ProgressBar v-if="percent !== null" :percent="percent" />
      <button class="btn btn-primary btn-block" @click="$emit('action', course)">
        {{ actionLabel }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.course-card {
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.course-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
.thumb-initial {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-family: var(--font-heading); font-size: 4.5rem; font-weight: 700;
  color: rgba(255, 255, 255, 0.28);
}
.thumb {
  height: 150px;
  background-size: cover;
  background-position: center;
  position: relative;
}
.level-badge {
  position: absolute;
  top: 0.7rem;
  left: 0.7rem;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
  color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(13, 35, 58, 0.15);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}
.body {
  padding: 1.1rem 1.2rem 1.3rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  flex: 1;
}
.body .btn { margin-top: auto; }
.body h3 {
  font-size: 1.15rem;
  margin: 0;
}
.description {
  font-size: 0.85rem;
  margin: 0 0 0.2rem 0;
  line-height: 1.4;
}
</style>
