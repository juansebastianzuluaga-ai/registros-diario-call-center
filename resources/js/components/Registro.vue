<template>
  <div class="panel">
    <div class="panel-title-row">
      <div class="panel-title">Registro de sincronización</div>
      <div class="range-controls">
        <button class="btn-outline btn-sm" :disabled="loading" @click="loadRows">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Actualizar lista</span>
        </button>
      </div>
    </div>

    <div class="range-count" v-if="rows.length">{{ rows.length }} intento(s) registrados (últimos 200)</div>

    <div class="table-scroll table-scroll-tall" v-if="rows.length || (loading && !rows.length)">
      <table class="data-table">
        <thead>
          <tr>
            <th>Fecha y hora</th>
            <th>Día sincronizado</th>
            <th>Origen</th>
            <th>Estado</th>
            <th>Detalle</th>
          </tr>
        </thead>
        <tbody v-if="loading && !rows.length">
          <tr class="skeleton-row" v-for="n in 8" :key="n">
            <td v-for="c in 5" :key="c"><div class="skeleton-line"></div></td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr v-for="row in rows" :key="row.id">
            <td>{{ formatDateTime(row.created_at) }}</td>
            <td>{{ row.date.slice(0, 10) }}</td>
            <td>{{ sourceLabel(row.source) }}</td>
            <td>
              <span :class="['log-badge', row.status === 'success' ? 'log-ok' : 'log-error']">
                {{ row.status === 'success' ? 'Correcto' : 'Error' }}
              </span>
            </td>
            <td class="log-message">{{ row.message || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="empty-state" v-else-if="!loading">Todavía no hay intentos de sincronización registrados.</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import { useStore } from '../store'

const store = useStore()

const rows = ref([])
const loading = ref(false)

const SOURCE_LABELS = {
  manual: 'Actualización manual',
  heartbeat: 'Automático (cada 5 min)',
  scheduled_daily: 'Sincronización diaria',
  background_range: 'Actualización en segundo plano',
  bulk_import: 'Importación histórica',
}
const sourceLabel = (source) => SOURCE_LABELS[source] || source

const formatDateTime = (value) => new Date(value).toLocaleString('es-CO', {
  day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'
})

const loadRows = async () => {
  loading.value = true
  try {
    rows.value = await api.getSyncLogs()
  } catch (err) {
    store.showToast('Error al cargar el registro', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(loadRows)
</script>

<style>
.log-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
}

.log-ok {
  background: var(--verde-bg);
  color: var(--verde-texto);
}

.log-error {
  background: var(--rojo-bg);
  color: var(--rojo-texto);
}

.log-message {
  color: var(--text2);
  font-size: 0.78rem;
  max-width: 360px;
}
</style>
