import { useStore } from './store'

const API_BASE = '/api'

const getHeaders = () => {
  const store = useStore()
  const headers = { 'Content-Type': 'application/json' }
  if (store.state.token) {
    headers['Authorization'] = `Bearer ${store.state.token}`
  }
  return headers
}

const request = async (url, options = {}) => {
  const response = await fetch(`${API_BASE}${url}`, {
    ...options,
    headers: {
      ...getHeaders(),
      ...options.headers
    }
  })

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}))
    throw new Error(errorData.message || `Error ${response.status}`)
  }

  if (response.status === 204) return null
  return response.json()
}

export const api = {
  login: (credentials) => request('/login', {
    method: 'POST',
    body: JSON.stringify(credentials)
  }),
  logout: () => request('/logout', { method: 'POST' }),
  getUser: () => request('/user'),

  getToday: () => request('/call-stats/today'),
  getHistory: (from, to) => request(`/call-stats?from=${from}&to=${to}`),
  getCampaignsRange: (from, to) => request(`/call-stats/campaigns/range?from=${from}&to=${to}`),
  refresh: (date, dateTo) => request('/call-stats/refresh', {
    method: 'POST',
    body: JSON.stringify({ date, date_to: dateTo })
  }),
  getSyncJob: (id) => request(`/sync-jobs/${id}`),
  getSyncLogs: () => request('/sync-logs'),

  getSystemAlerts: () => request('/system-alerts'),
  dismissAlert: (id) => request(`/system-alerts/${id}/dismiss`, { method: 'POST' }),
}
