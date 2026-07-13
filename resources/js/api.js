import { supabase } from './supabase'

const throwIfError = (error) => {
  if (error) throw new Error(error.message)
}

const todayStr = () => new Date().toISOString().slice(0, 10)

export const api = {
  login: async ({ email, password }) => {
    const { data, error } = await supabase.auth.signInWithPassword({ email, password })
    if (error) throw new Error(error.message === 'Invalid login credentials' ? 'Las credenciales son incorrectas.' : error.message)
    return { user: data.user, token: data.session.access_token }
  },
  logout: async () => {
    const { error } = await supabase.auth.signOut()
    throwIfError(error)
  },
  getUser: async () => {
    const { data, error } = await supabase.auth.getUser()
    throwIfError(error)
    return data.user
  },

  getToday: async () => {
    const { data, error } = await supabase.from('call_stats').select('*').eq('date', todayStr()).maybeSingle()
    throwIfError(error)
    return data
  },
  getHistory: async (from, to) => {
    const { data, error } = await supabase.from('call_stats').select('*').gte('date', from).lte('date', to).order('date')
    throwIfError(error)
    return data
  },
  getCampaignsRange: async (from, to) => {
    const { data, error } = await supabase.rpc('campaigns_range', { from_date: from, to_date: to })
    throwIfError(error)
    return data
  },
  refresh: async (date, dateTo) => {
    const { data, error } = await supabase.functions.invoke('trigger-sync', { body: { date, date_to: dateTo } })
    throwIfError(error)
    return data
  },
  getSyncJob: async (id) => {
    const { data, error } = await supabase.from('sync_jobs').select('*').eq('id', id).single()
    throwIfError(error)
    return data
  },
  getSyncLogs: async () => {
    const { data, error } = await supabase.from('sync_logs').select('*').order('created_at', { ascending: false }).limit(200)
    throwIfError(error)
    return data
  },

  getSystemAlerts: async () => {
    const { data, error } = await supabase.from('system_alerts').select('*').is('resolved_at', null).order('created_at', { ascending: false })
    throwIfError(error)
    return data
  },
  dismissAlert: async (id) => {
    const { error } = await supabase.from('system_alerts').update({ resolved_at: new Date().toISOString() }).eq('id', id)
    throwIfError(error)
  },
}
