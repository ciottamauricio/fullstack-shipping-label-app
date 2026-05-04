import { Link } from 'react-router-dom'

export default function LabelCard({ label }) {
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
