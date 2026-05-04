import { Link } from 'react-router-dom'
import LabelCard from '../components/LabelCard'
import Navbar from '../components/Navbar'
import { useLabels } from '../hooks/useLabels'

export default function DashboardPage() {
  const { labels, loading, error } = useLabels()

  return (
    <div className="layout">
      <Navbar />

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
