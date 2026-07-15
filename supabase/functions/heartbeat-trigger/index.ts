// Endpoint sin autenticación de usuario (lo llama cron-job.org cada 5 min,
// no un navegador con sesión) — protegido en cambio por un secreto propio en
// la URL, para que nadie más pueda gastar minutos de GitHub Actions llamándolo.
// Dispara el workflow zennia-heartbeat.yml (que ya existe y sí funciona bien,
// el problema es que el "schedule" nativo de GitHub Actions no es confiable
// para intervalos de 5 minutos).
const GITHUB_REPO = 'juansebastianzuluaga-ai/registros-diario-call-center'

Deno.serve(async (req) => {
  const url = new URL(req.url)
  const secret = url.searchParams.get('secret')

  if (secret !== Deno.env.get('HEARTBEAT_SECRET')) {
    return new Response(JSON.stringify({ message: 'No autorizado' }), {
      status: 401,
      headers: { 'Content-Type': 'application/json' },
    })
  }

  const dispatchResponse = await fetch(
    `https://api.github.com/repos/${GITHUB_REPO}/actions/workflows/zennia-heartbeat.yml/dispatches`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${Deno.env.get('GITHUB_PAT')}`,
        Accept: 'application/vnd.github+json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ ref: 'main' }),
    }
  )

  if (!dispatchResponse.ok) {
    const errText = await dispatchResponse.text()
    return new Response(JSON.stringify({ message: `No se pudo disparar: ${errText}` }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' },
    })
  }

  return new Response(JSON.stringify({ ok: true }), {
    status: 200,
    headers: { 'Content-Type': 'application/json' },
  })
})
