<template>
  <div>
    <NuxtPage />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'

let timer = null

const checkThemeByTime = () => {
  if (typeof document === 'undefined') return
  const currentHour = new Date().getHours()
  const isNight = currentHour < 6 || currentHour >= 18

  if (isNight) {
    document.documentElement.classList.add('auto-dark-mode')
  } else {
    document.documentElement.classList.remove('auto-dark-mode')
  }
}

const handlePageShow = (event) => {
  if (event.persisted) {
    checkThemeByTime()
  }
}

onMounted(() => {
  checkThemeByTime()
  timer = setInterval(checkThemeByTime, 60000)
  window.addEventListener('pageshow', handlePageShow)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  window.removeEventListener('pageshow', handlePageShow)
})
</script>

<style>
/* CSS Otomatis Dark Mode */
html.auto-dark-mode {
  filter: invert(0.92) hue-rotate(180deg);
  background-color: #121212 !important;
}

html.auto-dark-mode img,
html.auto-dark-mode video,
html.auto-dark-mode canvas,
html.auto-dark-mode #reader,
html.auto-dark-mode aside {
  filter: invert(1) hue-rotate(180deg);
}

html {
  transition: filter 0.4s ease-in-out;
}
</style>