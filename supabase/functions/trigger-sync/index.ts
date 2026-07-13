// Recibe {date, date_to} de un usuario autenticado, crea la fila de progreso
// en sync_jobs, y dispara el workflow de GitHub Actions que hace el trabajo
// real (ZenniaClient sigue viviendo en PHP, sin reescribir).
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

const GITHUB_REPO = 'juansebastianzuluaga-ai/registros-diario-call-center'
const CORS_HEADERS = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
}

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: CORS_HEADERS })
  }

  try {
    const authHeader = req.headers.get('Authorization')
    if (!authHeader) {
      return json({ message: 'No autenticado' }, 401)
    }

    // Confirma que quien llama es un usuario logueado de verdad, no solo
    // alguien que tiene la clave anon (que es pública por diseño).
    const supabaseAuth = createClient(
      Deno.env.get('SUPABASE_URL')!,
      Deno.env.get('SUPABASE_ANON_KEY')!,
      { global: { headers: { Authorization: authHeader } } }
    )
    const { data: userData, error: userError } = await supabaseAuth.auth.getUser()
    if (userError || !userData.user) {
      return json({ message: 'No autenticado' }, 401)
    }

    const { date, date_to } = await req.json()
    if (!date) {
      return json({ message: 'Falta la fecha' }, 422)
    }
    const dateTo = date_to || date

    // Cliente con la clave de servicio: crea la fila de progreso del lado
    // del servidor, saltándose RLS (el navegador nunca ve esta clave).
    const supabaseAdmin = createClient(
      Deno.env.get('SUPABASE_URL')!,
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
    )

    const dayCount = Math.floor((Date.parse(dateTo) - Date.parse(date)) / 86400000) + 1

    const { data: syncJob, error: insertError } = await supabaseAdmin
      .from('sync_jobs')
      .insert({ from_date: date, to_date: dateTo, total: dayCount })
      .select()
      .single()

    if (insertError) {
      return json({ message: insertError.message }, 500)
    }

    const dispatchResponse = await fetch(
      `https://api.github.com/repos/${GITHUB_REPO}/actions/workflows/zennia-manual-sync.yml/dispatches`,
      {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${Deno.env.get('GITHUB_PAT')}`,
          Accept: 'application/vnd.github+json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ref: 'main',
          inputs: { date, date_to: dateTo, sync_job_id: String(syncJob.id) },
        }),
      }
    )

    if (!dispatchResponse.ok) {
      const errText = await dispatchResponse.text()
      await supabaseAdmin
        .from('sync_jobs')
        .update({ status: 'failed', error: `No se pudo disparar GitHub Actions: ${errText}` })
        .eq('id', syncJob.id)
      return json({ message: 'No se pudo disparar la sincronización' }, 500)
    }

    return json({ sync_job: syncJob }, 202)
  } catch (e) {
    return json({ message: e instanceof Error ? e.message : String(e) }, 500)
  }
})

function json(body: unknown, status: number) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...CORS_HEADERS, 'Content-Type': 'application/json' },
  })
}
