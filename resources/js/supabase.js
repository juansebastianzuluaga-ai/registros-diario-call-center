import { createClient } from '@supabase/supabase-js'

// Públicas por diseño: la seguridad real la da Row Level Security en Supabase,
// no ocultar esta clave (que de todas formas va empacada en el JS del navegador).
export const supabase = createClient(
  import.meta.env.VITE_SUPABASE_URL,
  import.meta.env.VITE_SUPABASE_ANON_KEY
)
