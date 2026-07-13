import { reactive, computed } from 'vue'

const state = reactive({
  user: null,
  token: localStorage.getItem('token'),
  currentView: 'login',
  today: null,
  history: [],
  loading: false,
  toast: null
})

export const useStore = () => {
  const setUser = (user) => { state.user = user }
  const setToken = (token) => {
    state.token = token
    if (token) localStorage.setItem('token', token)
    else localStorage.removeItem('token')
  }
  const setCurrentView = (view) => { state.currentView = view }
  const setToday = (today) => { state.today = today }
  const setHistory = (history) => { state.history = history }
  const setLoading = (loading) => { state.loading = loading }
  const showToast = (message, type = 'success') => {
    state.toast = { message, type }
    setTimeout(() => { state.toast = null }, 3000)
  }

  const isAuthenticated = computed(() => !!state.token)

  return {
    state,
    setUser,
    setToken,
    setCurrentView,
    setToday,
    setHistory,
    setLoading,
    showToast,
    isAuthenticated
  }
}
