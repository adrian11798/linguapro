<script setup>
defineProps({
  percent: { type: Number, required: true },
  showLabel: { type: Boolean, default: true }
})
</script>

<template>
  <div class="progress-wrapper">
    <div class="progress-track">
      <div class="progress-fill" :style="{ width: Math.min(100, Math.max(0, percent)) + '%' }" />
    </div>
    <span v-if="showLabel" class="progress-label">{{ percent }}%</span>
  </div>
</template>

<style scoped>
.progress-wrapper {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.progress-wrapper .progress-track {
  flex: 1;
}
.progress-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-muted);
  min-width: 2.4rem;
  text-align: right;
}
</style>
