// Utilidades de fecha/hora para las clases en vivo (siempre en hora local del navegador).

const pad = (n) => String(n).padStart(2, '0')

/** "2026-11-05" -> "2026-11-05" (por si el servidor manda una fecha con hora). */
export const dayOf = (dateStr) => String(dateStr || '').slice(0, 10)

/** "16:30:00" -> "16:30" */
export const hhmm = (time) => String(time || '').slice(0, 5)

export function localToday() {
  const d = new Date()
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

/** Inicio de la clase como Date local. */
export function classStart(c) {
  const [y, m, d] = dayOf(c.date).split('-').map(Number)
  const [hh, mm] = hhmm(c.time).split(':').map(Number)
  return new Date(y, (m || 1) - 1, d || 1, hh || 0, mm || 0)
}

/** ¿Sigue vigente? (aún no empieza, o empezó hace menos de una hora) */
export function isUpcoming(c, durationMinutes = 60) {
  return classStart(c).getTime() + durationMinutes * 60000 > Date.now()
}

/** "Hoy", "Mañana" o "jueves 5 de noviembre". */
export function dayLabel(dateStr) {
  const day = dayOf(dateStr)
  const today = new Date()
  const tomorrow = new Date()
  tomorrow.setDate(today.getDate() + 1)
  const key = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
  if (day === key(today)) return 'Hoy'
  if (day === key(tomorrow)) return 'Mañana'
  const [y, m, d] = day.split('-').map(Number)
  const text = new Date(y, m - 1, d).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' })
  return text.charAt(0).toUpperCase() + text.slice(1)
}
