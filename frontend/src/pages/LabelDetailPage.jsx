import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { api } from '../api/client'
import { useAuth } from '../context/AuthContext'

export default function LabelDetailPage() {
  const { id } = useParams()
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [label, setLabel] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    api.get(`/labels/${id}`)
      .then(setLabel)
      .catch((err) => setError(err.status === 403 ? 'Label not found.' : err.message))
      .finally(() => setLoading(false))
  }, [id])

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
  }

  const handlePrint = () => {
    window.open(label.label_url, '_blank', 'noopener,noreferrer')
  }

  return (
    <div className="layout">
      <nav className="nav">
        <span className="nav-brand">ShipLabel</span>
        <div className="nav-right">
          <span className="nav-user">{user?.name}</span>
          <button className="btn btn-ghost btn-sm" onClick={handleLogout}>
            Sign out
          </button>
        </div>
      </nav>

      <main className="page">
        <div className="page-header">
          <div>
            <Link to="/dashboard" className="back-link">← Back to labels</Link>
            <h2 className="page-title">Label #{id}</h2>
          </div>
          {label && (
            <button className="btn btn-primary" onClick={handlePrint}>
              Print / Download Label
            </button>
          )}
        </div>

        {error && <div className="alert alert-error">{error}</div>}
        {loading && <div className="empty-state">Loading…</div>}

        {label && (
          <div className="detail-grid">
            {/* Tracking */}
            <div className="detail-card detail-card-highlight">
              <p className="detail-card-label">Tracking Number</p>
              <p className="detail-tracking">
                {label.tracking_code ?? 'Pending'}
              </p>
              <div className="detail-badges">
                <span className="badge">{label.carrier}</span>
                <span className="badge badge-blue">{label.service}</span>
                <span className="badge badge-green">{label.status}</span>
              </div>
            </div>

            {/* Cost */}
            <div className="detail-card">
              <p className="detail-card-label">Postage Cost</p>
              <p className="detail-value-lg">${Number(label.rate).toFixed(2)}</p>
              <p className="detail-meta">
                Created {new Date(label.created_at).toLocaleString()}
              </p>
            </div>

            {/* Addresses */}
            <div className="detail-card">
              <p className="detail-card-label">From</p>
              <AddressBlock
                name={label.from_name}
                company={label.from_company}
                street1={label.from_street1}
                street2={label.from_street2}
                city={label.from_city}
                state={label.from_state}
                zip={label.from_zip}
              />
            </div>

            <div className="detail-card">
              <p className="detail-card-label">To</p>
              <AddressBlock
                name={label.to_name}
                company={label.to_company}
                street1={label.to_street1}
                street2={label.to_street2}
                city={label.to_city}
                state={label.to_state}
                zip={label.to_zip}
              />
            </div>

            {/* Package */}
            <div className="detail-card">
              <p className="detail-card-label">Package</p>
              <div className="detail-specs">
                <div className="detail-spec">
                  <span className="detail-spec-label">Weight</span>
                  <span className="detail-spec-value">{label.weight} oz</span>
                </div>
                <div className="detail-spec">
                  <span className="detail-spec-label">Dimensions</span>
                  <span className="detail-spec-value">
                    {label.length}" × {label.width}" × {label.height}"
                  </span>
                </div>
              </div>
            </div>

            {/* EasyPost ID */}
            <div className="detail-card">
              <p className="detail-card-label">EasyPost Shipment ID</p>
              <p className="detail-mono">{label.easypost_shipment_id}</p>
            </div>
          </div>
        )}
      </main>
    </div>
  )
}

function AddressBlock({ name, company, street1, street2, city, state, zip }) {
  return (
    <address className="address-block">
      <strong>{name}</strong>
      {company && <span>{company}</span>}
      <span>{street1}</span>
      {street2 && <span>{street2}</span>}
      <span>{city}, {state} {zip}</span>
      <span>United States</span>
    </address>
  )
}
