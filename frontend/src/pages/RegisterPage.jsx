import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function RegisterPage() {
  const { register } = useAuth()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState({})
  const [loading, setLoading] = useState(false)

  const handleChange = (e) => {
    setForm((f) => ({ ...f, [e.target.name]: e.target.value }))
    setFieldErrors((fe) => ({ ...fe, [e.target.name]: undefined }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setFieldErrors({})
    setLoading(true)
    try {
      await register(form.name, form.email, form.password, form.password_confirmation)
      navigate('/dashboard', { replace: true })
    } catch (err) {
      setError(err.message)
      setFieldErrors(err.errors ?? {})
    } finally {
      setLoading(false)
    }
  }

  const fieldError = (name) =>
    fieldErrors[name]?.[0] ? (
      <span className="field-error">{fieldErrors[name][0]}</span>
    ) : null

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1 className="auth-title">Create account</h1>
        <p className="auth-subtitle">Shipping Label Manager</p>

        {error && !Object.keys(fieldErrors).length && (
          <div className="alert alert-error">{error}</div>
        )}

        <form onSubmit={handleSubmit} className="form">
          <div className="form-group">
            <label className="label" htmlFor="name">Full name</label>
            <input
              id="name"
              name="name"
              type="text"
              autoComplete="name"
              required
              className={`input${fieldErrors.name ? ' input-error' : ''}`}
              value={form.name}
              onChange={handleChange}
              placeholder="Jane Doe"
            />
            {fieldError('name')}
          </div>

          <div className="form-group">
            <label className="label" htmlFor="email">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="email"
              required
              className={`input${fieldErrors.email ? ' input-error' : ''}`}
              value={form.email}
              onChange={handleChange}
              placeholder="you@example.com"
            />
            {fieldError('email')}
          </div>

          <div className="form-group">
            <label className="label" htmlFor="password">Password</label>
            <input
              id="password"
              name="password"
              type="password"
              autoComplete="new-password"
              required
              minLength={8}
              className={`input${fieldErrors.password ? ' input-error' : ''}`}
              value={form.password}
              onChange={handleChange}
              placeholder="Min. 8 characters"
            />
            {fieldError('password')}
          </div>

          <div className="form-group">
            <label className="label" htmlFor="password_confirmation">Confirm password</label>
            <input
              id="password_confirmation"
              name="password_confirmation"
              type="password"
              autoComplete="new-password"
              required
              className="input"
              value={form.password_confirmation}
              onChange={handleChange}
              placeholder="••••••••"
            />
          </div>

          <button type="submit" className="btn btn-primary btn-full" disabled={loading}>
            {loading ? 'Creating account…' : 'Create account'}
          </button>
        </form>

        <p className="auth-switch">
          Already have an account?{' '}
          <Link to="/login" className="link">Sign in</Link>
        </p>
      </div>
    </div>
  )
}
