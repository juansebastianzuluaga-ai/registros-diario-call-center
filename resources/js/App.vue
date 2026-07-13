<template>
  <div id="app">
    <Login v-if="currentView === 'login'" />
    <template v-else>
      <div class="app-layout">
        <div v-if="sidebarOpen" class="sidebar-backdrop" @click="sidebarOpen = false"></div>
        <nav :class="['sidebar', sidebarOpen ? 'sidebar-open' : '']">
          <div class="sidebar-logo">
            <img class="sidebar-logo-img" :src="logoUrl" alt="CAC Santa Bárbara">
          </div>
          <div class="sidebar-section">Menú</div>
          <div class="sidebar-nav">
            <div :class="['nav-item', currentView === 'inicio' ? 'nav-active' : '']" @click="goTo('inicio')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              Inicio
            </div>
            <div :class="['nav-item', currentView === 'dias' ? 'nav-active' : '']" @click="goTo('dias')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              Días
            </div>
            <div :class="['nav-item', currentView === 'campanas' ? 'nav-active' : '']" @click="goTo('campanas')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              Campañas
            </div>
            <div :class="['nav-item', currentView === 'estadisticas' ? 'nav-active' : '']" @click="goTo('estadisticas')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              Estadísticas
            </div>
            <div :class="['nav-item', currentView === 'registro' ? 'nav-active' : '']" @click="goTo('registro')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              Registro
            </div>
          </div>
          <div class="sidebar-footer">© 2026 CAC Santa Bárbara</div>
        </nav>

        <div class="app-main">
          <header class="app-header">
            <div class="app-header-left">
              <button class="menu-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
              </button>
              <div class="app-header-titles">
                <span class="app-header-title">{{ viewTitle }}</span>
                <span class="app-header-subtitle">CAC Santa Bárbara</span>
              </div>
            </div>
            <div class="app-header-user">
              <div class="app-header-avatar">{{ iniciales }}</div>
              <div class="app-header-info">
                <div class="app-header-name">{{ userName }}</div>
                <div class="app-header-email">{{ userEmail }}</div>
              </div>
              <button class="app-header-logout" @click="handleLogout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span class="logout-label">Cerrar sesión</span>
              </button>
            </div>
          </header>

          <div class="alerts-bar" v-if="alerts.length">
            <div v-for="alert in alerts" :key="alert.id" :class="['alert-item', alert.type === 'sync_error' ? 'alert-error' : 'alert-warning']">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span>{{ alert.message }}</span>
              <button @click="dismissAlert(alert.id)" aria-label="Descartar">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>

          <div :class="['app-content', currentView === 'inicio' ? 'no-scroll' : '']">
            <Inicio v-if="currentView === 'inicio'" />
            <Dias v-else-if="currentView === 'dias'" />
            <Campanas v-else-if="currentView === 'campanas'" />
            <Estadisticas v-else-if="currentView === 'estadisticas'" />
            <Registro v-else-if="currentView === 'registro'" />
          </div>
        </div>
      </div>
    </template>

    <Transition name="toast">
      <div v-if="store.state.toast" :class="['toast', store.state.toast.type]">
        {{ store.state.toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useStore } from './store'
import { api } from './api'
import { supabase } from './supabase'
import { useCallStats } from './composables/useCallStats'
import Login from './components/Login.vue'
import Inicio from './components/Inicio.vue'
import Dias from './components/Dias.vue'
import Campanas from './components/Campanas.vue'
import Estadisticas from './components/Estadisticas.vue'
import Registro from './components/Registro.vue'

const store = useStore()
const { loadData } = useCallStats()
const logoUrl = `${import.meta.env.BASE_URL}img/logo.png`

const currentView = computed(() => store.state.currentView)
const user = computed(() => store.state.user)

const userName = computed(() => user.value?.user_metadata?.name || 'Administrador')
const userEmail = computed(() => user.value?.email || '')
const iniciales = computed(() => {
  const parts = userName.value.split(' ')
  if (parts.length >= 2) return parts[0][0] + parts[parts.length - 1][0]
  return parts[0][0]
})

const viewTitles = { inicio: 'Inicio', dias: 'Días', campanas: 'Campañas', estadisticas: 'Estadísticas', registro: 'Registro' }
const viewTitle = computed(() => viewTitles[currentView.value] || 'Registros Diario Call Center')

const sidebarOpen = ref(false)
const goTo = (view) => {
  store.setCurrentView(view)
  sidebarOpen.value = false
}

const handleLogout = async () => {
  try {
    await api.logout()
  } catch (_) {}
  store.setUser(null)
  store.setToken(null)
  store.setCurrentView('login')
}

const alerts = ref([])
const loadAlerts = async () => {
  try {
    alerts.value = await api.getSystemAlerts()
  } catch (_) {}
}
const dismissAlert = async (id) => {
  alerts.value = alerts.value.filter(a => a.id !== id)
  try {
    await api.dismissAlert(id)
  } catch (_) {}
}

// Dispara la carga de datos apenas alguien queda autenticado, sin importar
// si fue por sesión restaurada al abrir la app o por el formulario de login.
watch(user, (newUser, oldUser) => {
  if (newUser && !oldUser) {
    loadData()
    loadAlerts()
  }
})

onMounted(async () => {
  const { data } = await supabase.auth.getSession()
  if (data.session) {
    store.setUser(data.session.user)
    store.setToken(data.session.access_token)
    store.setCurrentView('inicio')
  } else {
    store.setCurrentView('login')
  }

  supabase.auth.onAuthStateChange((event, session) => {
    if (event === 'SIGNED_OUT') {
      store.setUser(null)
      store.setToken(null)
      store.setCurrentView('login')
    } else if (session) {
      store.setUser(session.user)
      store.setToken(session.access_token)
    }
  })
})
</script>

<style>
.app-layout {
  display: flex;
  height: 100vh;
  background: var(--bg);
  overflow: hidden;
}

.sidebar {
  width: 230px;
  min-width: 230px;
  background: linear-gradient(180deg, #1e1b4b 0%, #2a2465 55%, #312e81 100%);
  color: #fff;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow-y: auto;
  box-shadow: 2px 0 20px rgba(30, 27, 75, 0.25);
}

.sidebar-logo {
  height: 64px;
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  position: relative;
}

.sidebar-logo::before {
  content: '';
  position: absolute;
  top: -20%;
  left: 50%;
  transform: translateX(-50%);
  width: 140px;
  height: 90px;
  background: radial-gradient(ellipse, rgba(129, 140, 248, 0.35) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}

.sidebar-logo-img {
  height: 30px;
  width: auto;
  max-width: 100%;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
  position: relative;
  z-index: 1;
}

.sidebar-logo-label {
  font-size: 10px;
  font-weight: 600;
  color: rgba(255,255,255,0.35);
  letter-spacing: 1.2px;
  text-transform: uppercase;
}

.sidebar-section {
  padding: 18px 16px 6px;
  font-size: 10px;
  font-weight: 700;
  color: rgba(255,255,255,0.25);
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

.sidebar-nav {
  flex: 1;
  padding: 6px 12px 20px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 10px 12px;
  border-radius: 9px;
  font-size: 13.5px;
  font-weight: 500;
  color: rgba(255,255,255,0.6);
  cursor: pointer;
  transition: all 0.18s ease;
  position: relative;
  letter-spacing: 0.1px;
}

.nav-item:hover {
  color: #fff;
  background: rgba(255,255,255,0.08);
}

.nav-active {
  color: #fff;
  background: rgba(129, 140, 248, 0.22);
  font-weight: 600;
}

.nav-active::before {
  content: '';
  position: absolute;
  left: -12px;
  top: 6px;
  bottom: 6px;
  width: 3px;
  background: linear-gradient(180deg, #a5b4fc, #818cf8);
  border-radius: 0 3px 3px 0;
}

.nav-item svg {
  width: 17px;
  height: 17px;
  flex-shrink: 0;
  opacity: 0.65;
  transition: opacity 0.15s;
}

.nav-item:hover svg,
.nav-active svg {
  opacity: 1;
}

.sidebar-footer {
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.07);
  font-size: 10px;
  color: rgba(255,255,255,0.2);
  text-align: center;
  letter-spacing: 0.3px;
}

.alerts-bar {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding: 0.7rem 1.5rem 0;
  flex-shrink: 0;
}

.alert-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 0.9rem;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 500;
  animation: fadeIn 0.2s ease;
}

.alert-item svg:first-child { flex-shrink: 0; }

.alert-item span { flex: 1; }

.alert-item button {
  border: none;
  background: transparent;
  cursor: pointer;
  opacity: 0.6;
  display: flex;
  color: inherit;
}

.alert-item button:hover { opacity: 1; }

.alert-warning {
  background: var(--amarillo-bg);
  color: var(--amarillo-texto);
}

.alert-error {
  background: var(--rojo-bg);
  color: var(--rojo-texto);
}

.app-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}

.app-content.no-scroll {
  overflow: hidden;
  display: flex;
  flex-direction: column;
  padding: 1.1rem 1.5rem 1rem;
}

.menu-toggle {
  display: none;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  margin-right: 0.5rem;
}

.menu-toggle svg { width: 20px; height: 20px; }

.menu-toggle:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}

.sidebar-backdrop {
  display: none;
}

@media (max-width: 860px) {
  .menu-toggle {
    display: inline-flex;
  }

  .sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 40;
    transform: translateX(-100%);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .sidebar.sidebar-open {
    transform: translateX(0);
  }

  .sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 39;
    animation: fadeIn 0.2s ease;
  }

  .app-header-info {
    display: none;
  }

  .logout-label {
    display: none;
  }

  .app-header-logout {
    padding: 8px;
  }
}

.toast {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.7rem 1.5rem;
  border-radius: var(--radius-md);
  font-size: 0.85rem;
  font-weight: 600;
  z-index: 9999;
  box-shadow: var(--shadow-lg);
  pointer-events: none;
  white-space: nowrap;
}
.toast.success { background: var(--verde-texto); color: #fff; }
.toast.error { background: var(--rojo-texto); color: #fff; }
.toast-enter-active { transition: all 0.3s cubic-bezier(0.16,1,0.3,1); }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from { opacity: 0; transform: translateX(-50%) translateY(20px); }
.toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(10px); }
</style>
