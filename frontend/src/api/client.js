const BASE = '/api'

async function request(path, options = {}) {
  const token = localStorage.getItem('token')

  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  }

  const res = await fetch(`${BASE}${path}`, { ...options, headers })

  // 204 No Content — no body to parse
  if (res.status === 204) return null

  const data = await res.json().catch(() => null)

  if (!res.ok) {
    // Laravel validation errors arrive as { errors: { field: ['msg'] } }
    const message =
      data?.message ||
      Object.values(data?.errors ?? {}).flat().join(' ') ||
      `HTTP ${res.status}`
    const err = new Error(message)
    err.status = res.status
    err.errors = data?.errors ?? {}
    throw err
  }

  return data
}

export const api = {
  get:    (path)        => request(path),
  post:   (path, body)  => request(path, { method: 'POST',   body: JSON.stringify(body) }),
  put:    (path, body)  => request(path, { method: 'PUT',    body: JSON.stringify(body) }),
  delete: (path)        => request(path, { method: 'DELETE' }),
}
