import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import Navbar from '../components/Navbar'

const EMPTY_ADDRESS = {
  name: '', company: '', street1: '', street2: '', city: '', state: '', zip: '',
}

const EMPTY_PARCEL = { weight: '', length: '', width: '', height: '' }

export default function CreateLabelPage() {
  const navigate = useNavigate()

  const [from, setFrom] = useState(EMPTY_ADDRESS)
  const [to, setTo] = useState(EMPTY_ADDRESS)
  const [parcel, setParcel] = useState(EMPTY_PARCEL)
  const [fieldErrors, setFieldErrors] = useState({})
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const patch = (setter) => (e) =>
    setter((prev) => ({ ...prev, [e.target.name]: e.target.value }))

  const fe = (name) =>
    fieldErrors[name]?.[0] ? (
      <span className="field-error">{fieldErrors[name][0]}</span>
    ) : null

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setFieldErrors({})
    setLoading(true)

    const payload = {
      from_name:    from.name,
      from_company: from.company || undefined,
      from_street1: from.street1,
      from_street2: from.street2 || undefined,
      from_city:    from.city,
      from_state:   from.state.toUpperCase(),
      from_zip:     from.zip,

      to_name:    to.name,
      to_company: to.company || undefined,
      to_street1: to.street1,
      to_street2: to.street2 || undefined,
      to_city:    to.city,
      to_state:   to.state.toUpperCase(),
      to_zip:     to.zip,

      weight: parseFloat(parcel.weight),
      length: parseFloat(parcel.length),
      width:  parseFloat(parcel.width),
      height: parseFloat(parcel.height),
    }

    try {
      const label = await api.post('/labels', payload)
      navigate(`/labels/${label.id}`, { replace: true })
    } catch (err) {
      setError(err.message)
      setFieldErrors(err.errors ?? {})
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="layout">
      <Navbar />

      <main className="page">
        <div className="page-header">
          <div>
            <Link to="/dashboard" className="back-link">← Back to labels</Link>
            <h2 className="page-title">New Shipping Label</h2>
            <p className="page-sub">US addresses only · USPS carrier</p>
          </div>
        </div>

        {error && <div className="alert alert-error">{error}</div>}

        <form onSubmit={handleSubmit}>
          <section className="form-section">
            <h3 className="section-title">From Address</h3>
            <div className="form-grid">
              <div className="form-group">
                <label className="label">Full Name *</label>
                <input name="name" required className="input" value={from.name}
                  onChange={patch(setFrom)} placeholder="Jane Doe" />
                {fe('from_name')}
              </div>
              <div className="form-group">
                <label className="label">Company</label>
                <input name="company" className="input" value={from.company}
                  onChange={patch(setFrom)} placeholder="Acme Co. (optional)" />
              </div>
              <div className="form-group form-group-full">
                <label className="label">Street Address *</label>
                <input name="street1" required className="input" value={from.street1}
                  onChange={patch(setFrom)} placeholder="123 Main St" />
                {fe('from_street1')}
              </div>
              <div className="form-group form-group-full">
                <label className="label">Apt / Suite</label>
                <input name="street2" className="input" value={from.street2}
                  onChange={patch(setFrom)} placeholder="Suite 100 (optional)" />
              </div>
              <div className="form-group">
                <label className="label">City *</label>
                <input name="city" required className="input" value={from.city}
                  onChange={patch(setFrom)} placeholder="New York" />
                {fe('from_city')}
              </div>
              <div className="form-group form-group-sm">
                <label className="label">State *</label>
                <input name="state" required maxLength={2} className="input input-upper"
                  value={from.state} onChange={patch(setFrom)} placeholder="NY" />
                {fe('from_state')}
              </div>
              <div className="form-group form-group-sm">
                <label className="label">ZIP *</label>
                <input name="zip" required className="input" value={from.zip}
                  onChange={patch(setFrom)} placeholder="10001" />
                {fe('from_zip')}
              </div>
            </div>
          </section>

          <section className="form-section">
            <h3 className="section-title">To Address</h3>
            <div className="form-grid">
              <div className="form-group">
                <label className="label">Full Name *</label>
                <input name="name" required className="input" value={to.name}
                  onChange={patch(setTo)} placeholder="John Smith" />
                {fe('to_name')}
              </div>
              <div className="form-group">
                <label className="label">Company</label>
                <input name="company" className="input" value={to.company}
                  onChange={patch(setTo)} placeholder="Acme Co. (optional)" />
              </div>
              <div className="form-group form-group-full">
                <label className="label">Street Address *</label>
                <input name="street1" required className="input" value={to.street1}
                  onChange={patch(setTo)} placeholder="456 Oak Ave" />
                {fe('to_street1')}
              </div>
              <div className="form-group form-group-full">
                <label className="label">Apt / Suite</label>
                <input name="street2" className="input" value={to.street2}
                  onChange={patch(setTo)} placeholder="Apt 2B (optional)" />
              </div>
              <div className="form-group">
                <label className="label">City *</label>
                <input name="city" required className="input" value={to.city}
                  onChange={patch(setTo)} placeholder="Los Angeles" />
                {fe('to_city')}
              </div>
              <div className="form-group form-group-sm">
                <label className="label">State *</label>
                <input name="state" required maxLength={2} className="input input-upper"
                  value={to.state} onChange={patch(setTo)} placeholder="CA" />
                {fe('to_state')}
              </div>
              <div className="form-group form-group-sm">
                <label className="label">ZIP *</label>
                <input name="zip" required className="input" value={to.zip}
                  onChange={patch(setTo)} placeholder="90001" />
                {fe('to_zip')}
              </div>
            </div>
          </section>

          <section className="form-section">
            <h3 className="section-title">Package</h3>
            <div className="form-grid form-grid-4">
              <div className="form-group">
                <label className="label">Weight (oz) *</label>
                <input name="weight" type="number" step="0.1" min="0.1" required
                  className="input" value={parcel.weight} onChange={patch(setParcel)} placeholder="16" />
                {fe('weight')}
              </div>
              <div className="form-group">
                <label className="label">Length (in) *</label>
                <input name="length" type="number" step="0.1" min="0.1" required
                  className="input" value={parcel.length} onChange={patch(setParcel)} placeholder="12" />
                {fe('length')}
              </div>
              <div className="form-group">
                <label className="label">Width (in) *</label>
                <input name="width" type="number" step="0.1" min="0.1" required
                  className="input" value={parcel.width} onChange={patch(setParcel)} placeholder="8" />
                {fe('width')}
              </div>
              <div className="form-group">
                <label className="label">Height (in) *</label>
                <input name="height" type="number" step="0.1" min="0.1" required
                  className="input" value={parcel.height} onChange={patch(setParcel)} placeholder="4" />
                {fe('height')}
              </div>
            </div>
          </section>

          <div className="form-actions">
            <Link to="/dashboard" className="btn btn-ghost">Cancel</Link>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Generating label…' : 'Generate Label'}
            </button>
          </div>
        </form>
      </main>
    </div>
  )
}
