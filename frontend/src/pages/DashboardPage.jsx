import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import { useAuth } from '../context/AuthContext'

export default function DashboardPage() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [labels, setLabels] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    api.get('/labels')
      .then(setLabels)
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [])

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
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
            <h2 className="page-title">My Labels</h2>
            <p className="page-sub">{labels.length} label{labels.length !== 1 ? 's' : ''} created</p>
          </div>
          <Link to="/labels/new" className="btn btn-primary">
            + New Label
          </Link>
        </div>

        {error && <div className="alert alert-error">{error}</div>}

        {loading ? (
          <div className="empty-state">Loading…</div>
        ) : labels.length === 0 ? (
          <div className="empty-state">
            <p>No labels yet.</p>
            <Link to="/labels/new" className="btn btn-primary" style={{ marginTop: '1rem' }}>
              Create your first label
            </Link>
          </div>
        ) : (
          <div className="label-list">
            {labels.map((label) => (
              <LabelCard key={label.id} label={label} />
            ))}
          </div>
        )}
      </main>
    </div>
  )
}

function LabelCard({ label }) {
  return (
    <Link to={`/labels/${label.id}`} className="label-card">
      <div className="label-card-main">
        <div className="label-card-route">
          <span className="label-addr">{label.from_city}, {label.from_state}</span>
          <span className="label-arrow">→</span>
          <span className="label-addr">{label.to_city}, {label.to_state}</span>
        </div>
        <p className="label-to-name">To: {label.to_name}</p>
      </div>
      <div className="label-card-meta">
        <span className="badge">{label.carrier} {label.service}</span>
        <span className="label-rate">${Number(label.rate).toFixed(2)}</span>
        <span className="label-date">{new Date(label.created_at).toLocaleDateString()}</span>
      </div>
    </Link>
  )
}
