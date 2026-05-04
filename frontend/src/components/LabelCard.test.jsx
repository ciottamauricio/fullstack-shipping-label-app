import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { describe, expect, it } from 'vitest'
import LabelCard from './LabelCard'

const mockLabel = {
  id: 1,
  from_city: 'San Francisco',
  from_state: 'CA',
  to_city: 'Washington',
  to_state: 'DC',
  to_name: 'John Smith',
  carrier: 'USPS',
  service: 'Priority',
  rate: '7.50',
  created_at: '2024-01-15T12:00:00Z',
}

const renderCard = (label = mockLabel) =>
  render(<MemoryRouter><LabelCard label={label} /></MemoryRouter>)

describe('LabelCard', () => {
  it('renders from and to cities', () => {
    renderCard()
    expect(screen.getByText(/San Francisco, CA/)).toBeInTheDocument()
    expect(screen.getByText(/Washington, DC/)).toBeInTheDocument()
  })

  it('renders recipient name', () => {
    renderCard()
    expect(screen.getByText('To: John Smith')).toBeInTheDocument()
  })

  it('renders carrier, service, and rate', () => {
    renderCard()
    expect(screen.getByText('USPS Priority')).toBeInTheDocument()
    expect(screen.getByText('$7.50')).toBeInTheDocument()
  })

  it('links to the label detail page', () => {
    renderCard()
    expect(screen.getByRole('link')).toHaveAttribute('href', '/labels/1')
  })
})
