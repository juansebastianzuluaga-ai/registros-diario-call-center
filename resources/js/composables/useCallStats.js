import { reactive, toRefs } from 'vue'
import { api } from '../api'
import { useStore } from '../store'

const todayStr = () => new Date().toISOString().slice(0, 10)

const state = reactive({
  dateFrom: todayStr(),
  dateTo: todayStr(),
  refreshing: false,
  error: '',
  loaded: false,
  syncProgress: null
})

const JOB_POLL_MS = 1500

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

export const useCallStats = () => {
  const store = useStore()

  const loadData = async () => {
    try {
      const [todayData, historyData] = await Promise.all([
        api.getToday(),
        api.getHistory(new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10), todayStr())
      ])
      store.setToday(todayData)
      store.setHistory(historyData)
      state.loaded = true
    } catch (err) {
      store.showToast('Error al cargar los datos', 'error')
    }
  }

  const waitForSyncJob = async (jobId) => {
    while (true) {
      const job = await api.getSyncJob(jobId)
      state.syncProgress = job
      if (job.status === 'done' || job.status === 'failed') {
        return job
      }
      await sleep(JOB_POLL_MS)
    }
  }

  const handleRefresh = async () => {
    state.error = ''
    state.refreshing = true
    state.syncProgress = null
    try {
      const result = await api.refresh(state.dateFrom, state.dateTo)
      if (result && result.sync_job) {
        const job = await waitForSyncJob(result.sync_job.id)
        if (job.status === 'failed' && job.processed === 0) {
          throw new Error(job.error || 'La sincronización en segundo plano falló')
        }
      }
      await loadData()
      store.showToast('Datos actualizados desde Zennia')
    } catch (err) {
      state.error = err.message || 'No se pudo actualizar desde Zennia'
    } finally {
      state.refreshing = false
      state.syncProgress = null
    }
  }

  return {
    ...toRefs(state),
    loadData,
    handleRefresh
  }
}
