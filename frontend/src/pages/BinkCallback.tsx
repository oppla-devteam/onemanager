import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { AlertCircle, Loader2 } from 'lucide-react'

export default function BinkCallback() {
  const [searchParams] = useSearchParams()
  const [error, setError] = useState<string | null>(null)
  const { loginWithBink } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    const code = searchParams.get('code')
    const errorParam = searchParams.get('error')

    if (errorParam) {
      setError('Autorizzazione Bink rifiutata.')
      return
    }

    if (!code) {
      setError('Codice di autorizzazione mancante.')
      return
    }

    const handleCallback = async () => {
      try {
        const redirectUri = `${window.location.origin}/auth/bink/callback`
        const result = await loginWithBink(code, redirectUri)

        if (result.success) {
          navigate('/', { replace: true })
        } else {
          setError(result.message || 'Errore durante il login con Bink.')
        }
      } catch (err) {
        setError('Errore di connessione durante il login.')
      }
    }

    handleCallback()
  }, [searchParams, loginWithBink, navigate])

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center p-4 bg-gray-50">
        <div className="w-full max-w-md">
          <div className="glass-card p-8 text-center">
            <div className="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <AlertCircle className="w-8 h-8 text-red-500" />
            </div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Accesso negato</h2>
            <p className="text-gray-500 mb-6">{error}</p>
            <button
              onClick={() => navigate('/login', { replace: true })}
              className="glass-button-primary w-full"
            >
              Torna al login
            </button>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-4 bg-gray-50">
      <div className="text-center">
        <Loader2 className="w-10 h-10 text-primary-600 animate-spin mx-auto mb-4" />
        <p className="text-gray-500">Autenticazione in corso...</p>
      </div>
    </div>
  )
}
