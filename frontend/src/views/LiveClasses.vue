<script setup>
import { ref, computed, onMounted } from 'vue'
import { getLiveClasses } from '../services/liveClasses'
import { dayOf, hhmm, dayLabel, isUpcoming } from '../utils/dates'

const classes = ref([])
const loaded = ref(false)

onMounted(async () => {
  classes.value = await getLiveClasses()
  loaded.value = true
})

// Clases vigentes agrupadas por día y ordenadas por horario.
const days = computed(() => {
  const sorted = classes.value
    .filter((c) => isUpcoming(c))
    .sort((a, b) => (dayOf(a.date) + hhmm(a.time)).localeCompare(dayOf(b.date) + hhmm(b.time)))

  const groups = []
  for (const c of sorted) {
    const key = dayOf(c.date)
    let g = groups.find((x) => x.key === key)
    if (!g) groups.push((g = { key, label: dayLabel(c.date), items: [] }))
    g.items.push(c)
  }
  return groups
})
</script>

<template>
  <div class="page">
    <h1>Tu Agenda de Clases en Vivo</h1>
    <p class="muted">Las clases de tus cursos, ordenadas por horario.</p>

    <div class="agenda">
      <section v-for="g in days" :key="g.key" class="day-group">
        <h2 class="day-title">{{ g.label }}</h2>
        <div v-for="c in g.items" :key="c.id" class="card class-item">
          <div class="hour">{{ hhmm(c.time) }}<small>h</small></div>
          <div class="info">
            <h3>{{ c.title }}</h3>
            <p class="muted course-name">{{ c.course?.name }}</p>
          </div>
          <a class="btn btn-primary btn-sm" :href="c.meeting_url" target="_blank" rel="noopener">Unirse a la clase</a>
        </div>
      </section>
      <p v-if="loaded && !days.length" class="muted">No tienes clases en vivo programadas por ahora.</p>
    </div>
  </div>
</template>

<style scoped>
.agenda { margin-top: 1.5rem; max-width: 680px; display: flex; flex-direction: column; gap: 1.6rem; }
.day-title { font-family: var(--font-body); font-size: 0.82rem; font-weight: 700; color: var(--color-accent); margin: 0 0 0.6rem; }
.class-item { padding: 1rem 1.3rem; display: flex; align-items: center; gap: 1.1rem; margin-bottom: 0.7rem; flex-wrap: wrap; }
.hour { font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; color: var(--color-primary); min-width: 72px; }
.hour small { font-size: 0.8rem; font-weight: 500; margin-left: 0.15rem; color: var(--color-text-muted); }
.info { flex: 1; min-width: 180px; }
.info h3 { margin: 0; font-size: 1.02rem; }
.course-name { margin: 0.15rem 0 0; font-size: 0.85rem; }
</style>
