import { renderHook, waitFor } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '../api/client'
import { useLabel } from './useLabel'

vi.mock('../api/client')

const mockLabel = {
  id: 1,
  carrier: 'USPS',
  service: 'Priority',
  tracking_code: '9400111899223397711668',
  label_url: 'https://example.com/label.pdf',
}

describe('useLabel', () => {
  beforeEach(() => vi.clearAllMocks())

  it('returns label on successful fetch', async () => {
    api.get = vi.fn().mockResolvedValue(mockLabel)

    const { result } = renderHook(() => useLabel(1))

    expect(result.current.loading).toBe(true)

    await waitFor(() => expect(result.current.loading).toBe(false))

    expect(result.current.label).toEqual(mockLabel)
    expect(result.current.error).toBe('')
    expect(api.get).toHaveBeenCalledWith('/labels/1')
  })

  it('sets generic error message on failure', async () => {
    api.get = vi.fn().mockRejectedValue({ message: 'Not found' })

    const { result } = renderHook(() => useLabel(99))

    await waitFor(() => expect(result.current.loading).toBe(false))

    expect(result.current.label).toBeNull()
    expect(result.current.error).toBe('Not found')
  })

  it('sets "Label not found" on 403', async () => {
    api.get = vi.fn().mockRejectedValue({ status: 403, message: 'Forbidden' })

    const { result } = renderHook(() => useLabel(2))

    await waitFor(() => expect(result.current.loading).toBe(false))

    expect(result.current.error).toBe('Label not found.')
  })
})
