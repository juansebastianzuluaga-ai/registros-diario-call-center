<template>
  <div class="panel">
    <div class="panel-title-row">
      <div class="panel-title">Detalle por día</div>
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
        <button class="btn-outline btn-sm" :disabled="!rows.length" @click="exportRows">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Exportar CSV
        </button>
      </div>
    </div>

    <div class="range-count" v-if="rows.length">{{ rows.length }} día(s) encontrados</div>

    <div class="table-scroll table-scroll-tall" v-if="rows.length || (loading && !rows.length)">
      <table class="data-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Atendidas</th>
            <th>Expiradas</th>
            <th>Abandonadas</th>
            <th>Abandonadas durante anuncio</th>
            <th>Transferidas Atendidas</th>
            <th>Transferidas No Atendidas</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody v-if="loading && !rows.length">
          <tr class="skeleton-row" v-for="n in 8" :key="n">
            <td v-for="c in 8" :key="c"><div class="skeleton-line"></div></td>
          </tr>
        </tbody>
        <tbody v-else :class="{ 'table-fade': true, 'is-loading': loading }">
          <tr v-for="row in [...rows].reverse()" :key="row.id">
            <td>{{ row.date.slice(0, 10) }}</td>
            <td class="cell-verde">{{ row.entrantes_atendidas }}</td>
            <td>{{ row.entrantes_expiradas }}</td>
            <td class="cell-rojo">{{ row.entrantes_abandonadas }}</td>
            <td class="cell-rojo">{{ row.entrantes_abandonadas_anuncio }}</td>
            <td>{{ row.entrantes_transferidas_atendidas }}</td>
            <td>{{ row.entrantes_transferidas_no_atendidas }}</td>
            <td>{{ row.total }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="empty-state" v-else-if="!loading">Sin registros para este rango. Ve a "Inicio" y usa "Actualizar".</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { useStore } from '../store'
import { downloadCsv } from '../utils/csv'

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

const exportRows = () => {
  downloadCsv(
    `dias_${from.value}_a_${to.value}.csv`,
    ['Fecha', 'Atendidas', 'Expiradas', 'Abandonadas', 'Abandonadas durante anuncio', 'Transferidas Atendidas', 'Transferidas No Atendidas', 'Total'],
    [...rows.value].reverse().map(r => [
      r.date.slice(0, 10), r.entrantes_atendidas, r.entrantes_expiradas, r.entrantes_abandonadas,
      r.entrantes_abandonadas_anuncio, r.entrantes_transferidas_atendidas, r.entrantes_transferidas_no_atendidas, r.total
    ])
  )
}

onMounted(loadRows)
</script>

<style>
.range-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.range-preset {
  padding: 0.4rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--surface3);
  color: var(--text2);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.range-preset:hover { background: var(--azul-bg); color: var(--azul); }

.range-preset.active {
  background: var(--azul);
  color: #fff;
  border-color: var(--azul);
}

.range-controls input[type="date"] {
  padding: 0.4rem 0.6rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  color: var(--text);
  background: var(--surface3);
}

.range-sep {
  font-size: 0.78rem;
  color: var(--text2);
}

.btn-sm {
  padding: 0.4rem 0.9rem;
  font-size: 0.78rem;
}

.range-count {
  font-size: 0.75rem;
  color: var(--text2);
  margin: -0.5rem 0 0.75rem;
}

.table-scroll-tall {
  max-height: 60vh;
  overflow-y: auto;
}

.table-scroll-tall thead th {
  position: sticky;
  top: 0;
  z-index: 1;
}
</style>
