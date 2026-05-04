import { useEffect, useState } from 'react'
import { api } from '../api/client'

export function useLabel(id) {
  const [label, setLabel] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    api.get(`/labels/${id}`)
      .then(setLabel)
      .catch((err) => setError(err.status === 403 ? 'Label not found.' : err.message))
      .finally(() => setLoading(false))
  }, [id])

  return { label, loading, error }
}
