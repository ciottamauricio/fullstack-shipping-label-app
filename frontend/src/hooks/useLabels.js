import { useEffect, useState } from 'react'
import { api } from '../api/client'

export function useLabels() {
  const [labels, setLabels] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    api.get('/labels')
      .then(setLabels)
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [])

  return { labels, loading, error }
}
