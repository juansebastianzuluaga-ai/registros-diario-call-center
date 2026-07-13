<template>
  <div>
    <div class="panel">
      <div class="panel-title">Este mes vs. mes anterior</div>
      <div v-if="comparing" class="skeleton" style="height: 90px; width: 100%;"></div>
      <div v-else class="compare-grid">
        <div class="compare-row" v-for="c in compareRows" :key="c.label">
          <div class="compare-label">{{ c.label }}</div>
          <div class="compare-values">
            <span class="compare-current">{{ c.current }}</span>
            <span class="compare-prev">vs {{ c.previous }} mes anterior</span>
          </div>
          <span v-if="c.delta !== null" :class="['stat-delta', c.sentiment]">
            <svg v-if="c.delta > 0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
            <svg v-else-if="c.delta < 0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
            {{ c.delta > 0 ? '+' : '' }}{{ c.delta }}%
          </span>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-title-row">
        <div class="panel-title">Histórico</div>
        <div class="range-controls">
          <button class="range-preset" :class="{ active: preset === '30' }" @click="applyPreset('30')">Últimos 30 días</button>
          <button class="range-preset" :class="{ active: preset === 'month' }" @click="applyPreset('month')">Este mes</button>
          <button class="range-preset" :class="{ active: preset === 'all' }" @click="applyPreset('all')">Todo el historial</button>
          <input type="date" v-model="from" @change="preset = null">
          <span class="range-sep">a</span>
          <input type="date" v-model="to" @change="preset = null">
          <button class="btn-primary btn-sm" :disabled="loading" @click="loadRows">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Buscar</span>
          </button>
        </div>
      </div>
      <div v-if="loading && !rows.length" class="skeleton" style="height: 320px; width: 100%;"></div>
      <Bar v-else-if="rows.length" :data="chartData" :options="chartOptions" style="max-height: 320px" :class="{ 'table-fade': true, 'is-loading': loading }" />
      <div class="empty-state" v-else-if="!loading">Todavía no hay datos históricos. Ve a "Inicio" y usa "Actualizar" para traer el primer día.</div>
    </div>

    <div class="quick-actions">
      <div class="action-card" @click="store.setCurrentView('dias')">
        <div class="action-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div>
          <div class="action-title">Días</div>
          <div class="action-sub">Detalle diario de llamadas</div>
        </div>
        <svg class="action-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </div>

      <div class="action-card" @click="store.setCurrentView('campanas')">
        <div class="action-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
        <div>
          <div class="action-title">Campañas</div>
          <div class="action-sub">Resumen mensual por campaña</div>
        </div>
        <svg class="action-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </div>

      <div class="action-card" @click="store.setCurrentView('inicio')">
        <div class="action-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div>
          <div class="action-title">Ir a Inicio</div>
          <div class="action-sub">Resumen de hoy y actualizar datos</div>
        </div>
        <svg class="action-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { api } from '../api'
import { useStore } from '../store'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const store = useStore()

const todayStr = () => new Date().toISOString().slice(0, 10)
const daysAgo = (n) => new Date(Date.now() - n * 86400000).toISOString().slice(0, 10)

const from = ref(daysAgo(30))
const to = ref(todayStr())
const preset = ref('30')
const rows = ref([])
const loading = ref(false)

const applyPreset = (p) => {
  preset.value = p
  if (p === '30') {
    from.value = daysAgo(30)
    to.value = todayStr()
  } else if (p === 'month') {
    const now = new Date()
    from.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
    to.value = todayStr()
  } else if (p === 'all') {
    from.value = '2000-01-01'
    to.value = todayStr()
  }
  loadRows()
}

const loadRows = async () => {
  loading.value = true
  try {
    rows.value = await api.getHistory(from.value, to.value)
  } catch (err) {
    store.showToast('Error al cargar el histórico', 'error')
  } finally {
    loading.value = false
  }
}

const chartData = computed(() => ({
  labels: rows.value.map(h => h.date.slice(5, 10)),
  datasets: [
    { label: 'Atendidas', backgroundColor: '#22c55e', borderRadius: 4, data: rows.value.map(h => h.entrantes_atendidas) },
    { label: 'Abandonadas', backgroundColor: '#ef4444', borderRadius: 4, data: rows.value.map(h => h.entrantes_abandonadas) },
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: { beginAtZero: true, grid: { color: '#eef2f7' } },
    x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0, maxTicksLimit: 20 } }
  },
  plugins: {
    legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true } }
  }
}

const comparing = ref(true)
const compareRows = ref([])

const sumFields = (list) => ({
  total: list.reduce((s, r) => s + r.total, 0),
  entrantes_atendidas: list.reduce((s, r) => s + r.entrantes_atendidas, 0),
  entrantes_abandonadas: list.reduce((s, r) => s + r.entrantes_abandonadas, 0),
})

const pctDelta = (current, previous, lowerIsBetter = false) => {
  if (!previous) return { delta: null, sentiment: 'flat' }
  const pct = Math.round(((current - previous) / previous) * 100)
  const rose = pct > 0
  const sentiment = pct === 0 ? 'flat' : (rose !== lowerIsBetter ? 'good' : 'bad')
  return { delta: pct, sentiment }
}

const loadComparison = async () => {
  comparing.value = true
  try {
    const now = new Date()
    const startThis = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
    const endThis = todayStr()
    const startPrev = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString().slice(0, 10)
    const endPrev = new Date(now.getFullYear(), now.getMonth(), 0).toISOString().slice(0, 10)

    const [thisMonth, prevMonth] = await Promise.all([
      api.getHistory(startThis, endThis),
      api.getHistory(startPrev, endPrev),
    ])

    const cur = sumFields(thisMonth)
    const prev = sumFields(prevMonth)

    compareRows.value = [
      { label: 'Llamadas totales', current: cur.total, previous: prev.total, ...pctDelta(cur.total, prev.total) },
      { label: 'Entrantes atendidas', current: cur.entrantes_atendidas, previous: prev.entrantes_atendidas, ...pctDelta(cur.entrantes_atendidas, prev.entrantes_atendidas) },
      { label: 'Entrantes abandonadas', current: cur.entrantes_abandonadas, previous: prev.entrantes_abandonadas, ...pctDelta(cur.entrantes_abandonadas, prev.entrantes_abandonadas, true) },
    ]
  } catch (err) {
    store.showToast('Error al cargar la comparativa mensual', 'error')
  } finally {
    comparing.value = false
  }
}

onMounted(() => {
  loadRows()
  loadComparison()
})
</script>

<style>
.compare-grid {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.compare-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.compare-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text);
  min-width: 170px;
}

.compare-values {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  flex: 1;
}

.compare-current {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--text);
}

.compare-prev {
  font-size: 0.72rem;
  color: var(--text2);
}
</style>
