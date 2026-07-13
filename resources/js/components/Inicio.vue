<template>
  <div class="inicio-page">
    <div class="greeting-banner">
      <div class="greeting-icon">
        <svg v-if="isNight" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </div>
      <div>
        <div class="greeting-title">{{ greeting }}, {{ userName }}</div>
        <div class="greeting-date">{{ todayLong }}</div>
      </div>

      <div class="greeting-controls">
        <button v-if="!showManualSync" class="manual-sync-toggle" @click="showManualSync = true">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Sincronizar manualmente
        </button>
        <template v-else>
          <div class="field-group">
            <label>Desde</label>
            <input type="date" v-model="dateFrom">
          </div>
          <div class="field-group">
            <label>Hasta</label>
            <input type="date" v-model="dateTo">
          </div>
          <button class="btn-outline btn-sm" :disabled="refreshing" @click="handleRefresh">
            <span v-if="refreshing" class="spinner"></span>
            <span v-else>Sincronizar</span>
          </button>
          <button class="manual-sync-close" :disabled="refreshing" @click="showManualSync = false" aria-label="Cerrar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </template>
      </div>
    </div>

    <div class="refresh-error" v-if="error">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ error }}
    </div>
    <div class="sync-progress" v-if="syncProgress" style="margin: -0.75rem 0 1.25rem;">
      Sincronizando en segundo plano: {{ syncProgress.processed + syncProgress.failed }} / {{ syncProgress.total }} días
      <div class="sync-progress-track">
        <div class="sync-progress-fill" :style="{ width: (((syncProgress.processed + syncProgress.failed) / syncProgress.total) * 100) + '%' }"></div>
      </div>
    </div>
    <div class="synced-at" v-if="today?.synced_at" style="margin: -0.75rem 0 1.25rem;">
      Última sincronización: {{ formatDateTime(today.synced_at) }}
    </div>

    <div class="kpi-grid" v-if="!loaded">
      <div class="kpi-card skeleton-card" v-for="n in 5" :key="n">
        <div class="kpi-num">000</div>
        <div class="kpi-label">Cargando</div>
      </div>
    </div>
    <div class="kpi-grid" v-else>
      <div class="kpi-card kpi-blue">
        <svg class="kpi-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <div class="kpi-num">{{ today?.total ?? '—' }}</div>
        <div class="kpi-label">Llamadas totales</div>
        <div class="kpi-sub">
          <span v-if="totalDelta">{{ totalDelta.label }}</span>
          <span v-else>hoy</span>
        </div>
      </div>

      <div class="kpi-card kpi-cyan">
        <svg class="kpi-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 3l5 5-5 5M21 8H9M8 21l-5-5 5-5M3 16h12"/></svg>
        <div class="kpi-num">{{ today?.entrantes_total ?? '—' }}</div>
        <div class="kpi-label">Llamadas entrantes</div>
        <div class="kpi-sub">hoy</div>
      </div>

      <div class="kpi-card kpi-green">
        <svg class="kpi-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div class="kpi-num">{{ today?.entrantes_atendidas ?? '—' }}</div>
        <div class="kpi-label">Llamadas atendidas</div>
        <div class="kpi-sub">{{ pctAtencion }} de nivel de atención</div>
      </div>

      <div class="kpi-card kpi-red">
        <svg class="kpi-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45c.86.32 1.76.55 2.67.68A2 2 0 0 1 22 16.92z"/><line x1="23" y1="1" x2="1" y2="23"/><path d="M4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91"/></svg>
        <div class="kpi-num">{{ noAtendidas }}</div>
        <div class="kpi-label">Llamadas no atendidas</div>
        <div class="kpi-sub">entrantes sin atender</div>
      </div>

      <div class="kpi-card kpi-purple">
        <svg class="kpi-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
        <div class="kpi-num">{{ salientesTotal }}</div>
        <div class="kpi-label">Llamadas salientes</div>
        <div class="kpi-sub">discador + manual + preview</div>
      </div>
    </div>

    <div class="panel breakdown-panel">
      <div class="panel-title">Detalle de llamadas (hoy) &middot; Nivel de atención {{ pctAtencion }}</div>
      <div class="breakdown-body">
        <div class="donut-wrap">
          <Doughnut :data="donutData" :options="donutOptions" />
          <div class="donut-center">
            <div class="donut-num">{{ today?.total ?? 0 }}</div>
            <div class="donut-lbl">llamadas</div>
          </div>
        </div>

        <div class="breakdown-legend">
          <div class="breakdown-row" v-for="row in breakdownRows" :key="row.label">
            <span class="dot" :style="{ background: row.color }"></span>
            <span class="breakdown-label">{{ row.label }}</span>
            <div class="breakdown-track">
              <div class="breakdown-fill" :style="{ width: row.pct + '%', background: row.color }"></div>
            </div>
            <span class="breakdown-count">{{ row.value }}</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Tooltip, ArcElement } from 'chart.js'
import { useStore } from '../store'
import { useCallStats } from '../composables/useCallStats'

ChartJS.register(Tooltip, ArcElement)

const store = useStore()
const { dateFrom, dateTo, refreshing, error, loaded, syncProgress, handleRefresh } = useCallStats()

const showManualSync = ref(false)

const today = computed(() => store.state.today)
const history = computed(() => store.state.history)
const userName = computed(() => store.state.user?.name || 'Admin')

const now = new Date()
const isNight = now.getHours() < 6 || now.getHours() >= 19
const greeting = now.getHours() < 12 ? 'Buenos días' : now.getHours() < 19 ? 'Buenas tardes' : 'Buenas noches'
const todayLong = now.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

const pctAtencion = computed(() => {
  const t = today.value
  if (!t || !t.entrantes_total) return '0%'
  return Math.round((t.entrantes_atendidas / t.entrantes_total) * 100) + '%'
})

const noAtendidas = computed(() => {
  const t = today.value
  if (!t) return 0
  return t.entrantes_total - t.entrantes_atendidas
})

const salientesAtendidas = computed(() => {
  const t = today.value
  if (!t) return 0
  return t.salientes_discador_atendidas + t.salientes_manuales_conectadas + t.salientes_preview_conectadas
})

const salientesNoAtendidas = computed(() => {
  const t = today.value
  if (!t) return 0
  return t.salientes_discador_no_atendidas + t.salientes_discador_perdidas
    + t.salientes_manuales_no_conectadas + t.salientes_preview_no_conectadas
})

const salientesTotal = computed(() => salientesAtendidas.value + salientesNoAtendidas.value)

const totalDelta = computed(() => {
  const rows = history.value
  const t = today.value
  if (!t || rows.length < 2) return null

  const sorted = [...rows].sort((a, b) => a.date.localeCompare(b.date))
  const yesterday = sorted[sorted.length - 2]
  if (!yesterday || !yesterday.total) return null

  const diff = t.total - yesterday.total
  const pct = Math.round((diff / yesterday.total) * 100)
  if (pct === 0) return { direction: 'flat', label: 'Igual que ayer' }
  return {
    direction: pct > 0 ? 'up' : 'down',
    label: `${pct > 0 ? '+' : ''}${pct}% vs ayer`
  }
})

const formatDateTime = (value) => new Date(value).toLocaleString('es-CO', {
  day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
})

const breakdownRows = computed(() => {
  const t = today.value
  const total = t?.total || 0
  const items = [
    { label: 'Entrantes atendidas', value: t?.entrantes_atendidas ?? 0, color: '#3b82f6' },
    { label: 'Entrantes no atendidas', value: noAtendidas.value, color: '#ef4444' },
    { label: 'Salientes atendidas', value: salientesAtendidas.value, color: '#22c55e' },
    { label: 'Salientes no atendidas', value: salientesNoAtendidas.value, color: '#a855f7' },
  ]
  return items.map(i => ({ ...i, pct: total ? (i.value / total) * 100 : 0 }))
})

const donutData = computed(() => ({
  labels: breakdownRows.value.map(r => r.label),
  datasets: [{
    data: breakdownRows.value.map(r => r.value),
    backgroundColor: breakdownRows.value.map(r => r.color),
    borderWidth: 2,
    borderColor: '#fff',
  }]
}))

const donutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '72%',
  plugins: { legend: { display: false } }
}
</script>

<style>
.inicio-page {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
}

.manual-sync-toggle {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.7rem;
  border: none;
  background: transparent;
  color: var(--text2);
  font-size: 0.78rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: all 0.15s ease;
}

.manual-sync-toggle:hover {
  background: var(--azul-bg);
  color: var(--azul);
}

.manual-sync-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: none;
  background: transparent;
  color: var(--text2);
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: all 0.15s ease;
}

.manual-sync-close:hover {
  background: var(--surface3);
  color: var(--text);
}

.inicio-page .greeting-banner {
  padding: 0.85rem 1.25rem;
  margin-bottom: 0.85rem;
}

.inicio-page .greeting-icon {
  width: 42px;
  height: 42px;
  min-width: 42px;
}

.inicio-page .greeting-icon svg { width: 20px; height: 20px; }

.inicio-page .greeting-title { font-size: 1.05rem; }

.inicio-page .kpi-grid { margin-bottom: 0.85rem; }

.inicio-page .kpi-card {
  padding: 0.85rem 1rem;
  min-height: 100px;
}

.inicio-page .kpi-num { font-size: 1.6rem; }

.inicio-page .kpi-watermark { width: 28px; height: 28px; }

.inicio-page .breakdown-panel {
  padding: 1rem 1.25rem;
  margin-bottom: 0;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.inicio-page .breakdown-panel .panel-title { margin-bottom: 0.75rem; }

.inicio-page .breakdown-body { flex: 1; min-height: 0; }

.inicio-page .donut-wrap {
  width: 150px;
  height: 150px;
}

.inicio-page .breakdown-legend { gap: 0.9rem; }
</style>
