import { renderHook, waitFor } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '../api/client'
import { useLabels } from './useLabels'

vi.mock('../api/client')

const mockLabels = [
  { id: 1, from_city: 'New York', from_state: 'NY', to_city: 'Los Angeles', to_state: 'CA' },
  { id: 2, from_city: 'Chicago', from_state: 'IL', to_city: 'Houston', to_state: 'TX' },
]

describe('useLabels', () => {
  beforeEach(() => vi.clearAllMocks())

  it('returns labels on successful fetch', async () => {
    api.get = vi.fn().mockResolvedValue(mockLabels)

    const { result } = renderHook(() => useLabels())

    expect(result.current.loading).toBe(true)

    await waitFor(() => expect(result.current.loading).toBe(false))

    expect(result.current.labels).toEqual(mockLabels)
    expect(result.current.error).toBe('')
    expect(api.get).toHaveBeenCalledWith('/labels')
  })

  it('sets error on fetch failure', async () => {
    api.get = vi.fn().mockRejectedValue(new Error('Unauthorized'))

    const { result } = renderHook(() => useLabels())

    await waitFor(() => expect(result.current.loading).toBe(false))

    expect(result.current.labels).toEqual([])
    expect(result.current.error).toBe('Unauthorized')
  })
})
