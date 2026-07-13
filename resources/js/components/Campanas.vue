<template>
  <div class="panel">
    <div class="panel-title-row">
      <div class="panel-title">Llamadas por campaña</div>
      <div class="range-controls">
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

    <div class="table-scroll" v-if="rows.length || (loading && !rows.length)">
      <table class="data-table">
        <thead>
          <tr>
            <th>Campaña</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody v-if="loading && !rows.length">
          <tr class="skeleton-row" v-for="n in 6" :key="n">
            <td v-for="c in 2" :key="c"><div class="skeleton-line"></div></td>
          </tr>
        </tbody>
        <tbody v-else class="table-fade" :class="{ 'is-loading': loading }">
          <tr v-for="c in rows" :key="c.campaign_id">
            <td>{{ c.nombre }}</td>
            <td>{{ c.total }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="empty-state" v-else-if="!loading">Sin datos para este rango todavía. Ve a "Inicio" y usa "Actualizar".</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { useStore } from '../store'
import { downloadCsv } from '../utils/csv'

const store = useStore()

const todayStr = () => new Date().toISOString().slice(0, 10)

const now0 = new Date()
const from = ref(new Date(now0.getFullYear(), now0.getMonth(), 1).toISOString().slice(0, 10))
const to = ref(todayStr())
const preset = ref('month')
const rows = ref([])
const loading = ref(false)

const applyPreset = (p) => {
  preset.value = p
  if (p === 'month') {
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
    rows.value = await api.getCampaignsRange(from.value, to.value)
  } catch (err) {
    store.showToast('Error al cargar las campañas', 'error')
  } finally {
    loading.value = false
  }
}

const exportRows = () => {
  downloadCsv(
    `campanas_${from.value}_a_${to.value}.csv`,
    ['Campaña', 'Total'],
    rows.value.map(r => [r.nombre, r.total])
  )
}

onMounted(loadRows)
</script>
