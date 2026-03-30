import { motion } from 'framer-motion'
import { AlertCircle, ExternalLink } from 'lucide-react'
import { useState, useEffect } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { useNavigate, useLocation } from 'react-router-dom'

const BINK_CLIENT_ID = import.meta.env.VITE_BINK_CLIENT_ID || 'your_bink_client_id'

export default function Login() {
  const [error, setError] = useState('')
  const { isAuthenticated } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  const from = location.state?.from?.pathname || '/'

  useEffect(() => {
    if (isAuthenticated) {
      navigate(from, { replace: true })
    }
  }, [isAuthenticated, navigate, from])

  // Check for error from callback
  useEffect(() => {
    const params = new URLSearchParams(location.search)
    const errorMsg = params.get('error')
    if (errorMsg) {
      setError(decodeURIComponent(errorMsg))
    }
  }, [location.search])

  const handleBinkLogin = () => {
    const redirectUri = `${window.location.origin}/auth/bink/callback`
    const authUrl = `https://binatomy.link/api/oauth/authorize?client_id=${BINK_CLIENT_ID}&redirect_uri=${encodeURIComponent(redirectUri)}&response_type=code`
    window.location.href = authUrl
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-gray-50">
      {/* Background decorative elements */}
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute top-1/4 right-1/4 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
        <div className="absolute bottom-1/4 left-1/4 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
      </div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md relative z-10"
      >
        {/* Logo Section */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold">
            <span className="text-primary-600">Oppla One</span>
            <span className="ml-2 text-gray-400 text-lg font-normal">Manager</span>
          </h1>
        </div>

        {/* Login Card */}
        <div className="glass-card p-8">
          <div className="text-center mb-6">
            <h2 className="text-xl font-semibold text-gray-900">Accedi</h2>
            <p className="text-gray-500 text-sm mt-1">Usa il tuo account Bink per accedere</p>
          </div>

          {error && (
            <div className="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-600">
              <AlertCircle className="w-4 h-4 flex-shrink-0" />
              <span className="text-sm">{error}</span>
            </div>
          )}

          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleBinkLogin}
            className="w-full flex items-center justify-center gap-3 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-all shadow-sm"
          >
            <ExternalLink className="w-5 h-5" />
            Accedi con Bink
          </motion.button>
        </div>

        {/* Info */}
        <div className="mt-6 text-center text-sm text-gray-400">
          <p>Sistema di gestione Oppla One Manager</p>
        </div>
      </motion.div>
    </div>
  )
}
