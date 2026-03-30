import { motion } from 'framer-motion'
import { useState, useEffect, useRef } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { FileText, Check, X, Mail, Loader2, Download, Eye } from 'lucide-react'

interface SignatureData {
  signature: {
    id: number
    signer_name: string
    signer_email: string
    signer_role: string
    status: string
  }
  contract: {
    id: number
    contract_number: string
    subject: string
    client_name: string
    pdf_path?: string
  }
  signer: {
    name: string
    email: string
    role: string
  }
}

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export default function ContractSignature() {
  const { token } = useParams<{ token: string }>()
  const navigate = useNavigate()
  const canvasRef = useRef<HTMLCanvasElement>(null)
  
  const [data, setData] = useState<SignatureData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [step, setStep] = useState<'review' | 'otp' | 'sign' | 'success'>('review')
  const [otp, setOtp] = useState('')
  const [otpSent, setOtpSent] = useState(false)
  const [signatureType, setSignatureType] = useState<'drawn' | 'typed'>('drawn')
  const [typedSignature, setTypedSignature] = useState('')
  const [isDrawing, setIsDrawing] = useState(false)
  const [processing, setProcessing] = useState(false)

  useEffect(() => {
    fetchSignatureData()
  }, [token])

  const fetchSignatureData = async () => {
    try {
      const response = await fetch(`${API_URL}/contracts/sign/${token}`)
      if (response.ok) {
        const result = await response.json()
        setData(result)
      } else {
        setError('Token non valido o scaduto')
      }
    } catch (err) {
      setError('Errore di connessione')
    } finally {
      setLoading(false)
    }
  }

  const requestOtp = async () => {
    try {
      setProcessing(true)
      const response = await fetch(`${API_URL}/contracts/sign/${token}/request-otp`, {
        method: 'POST'
      })
      if (response.ok) {
        setOtpSent(true)
        alert('Codice OTP inviato alla tua email!')
      } else {
        const error = await response.json()
        alert(`Errore: ${error.message}`)
      }
    } catch (err) {
      alert('Errore durante l\'invio del codice OTP')
    } finally {
      setProcessing(false)
    }
  }

  const handleSign = async () => {
    if (!otp || otp.length !== 6) {
      alert('Inserisci il codice OTP a 6 cifre')
      return
    }

    let signatureData = ''
    
    if (signatureType === 'drawn') {
      const canvas = canvasRef.current
      if (!canvas) return
      signatureData = canvas.toDataURL()
    } else {
      signatureData = typedSignature
    }

    if (!signatureData) {
      alert('Inserisci la tua firma')
      return
    }

    try {
      setProcessing(true)
      const response = await fetch(`${API_URL}/contracts/sign/${token}/sign`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          signature_data: signatureData,
          signature_type: signatureType,
          otp: otp
        })
      })

      if (response.ok) {
        setStep('success')
      } else {
        const error = await response.json()
        alert(`Errore: ${error.message}`)
      }
    } catch (err) {
      alert('Errore durante la firma')
    } finally {
      setProcessing(false)
    }
  }

  const handleDecline = async () => {
    const reason = prompt('Motivo del rifiuto:')
    if (!reason) return

    try {
      const response = await fetch(`${API_URL}/contracts/sign/${token}/decline`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ reason })
      })

      if (response.ok) {
        alert('Firma rifiutata')
        navigate('/')
      }
    } catch (err) {
      alert('Errore durante il rifiuto')
    }
  }

  // Canvas Drawing
  const startDrawing = (e: React.MouseEvent<HTMLCanvasElement>) => {
    setIsDrawing(true)
    const canvas = canvasRef.current
    if (!canvas) return
    const ctx = canvas.getContext('2d')
    if (!ctx) return
    const rect = canvas.getBoundingClientRect()
    ctx.beginPath()
    ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top)
  }

  const draw = (e: React.MouseEvent<HTMLCanvasElement>) => {
    if (!isDrawing) return
    const canvas = canvasRef.current
    if (!canvas) return
    const ctx = canvas.getContext('2d')
    if (!ctx) return
    const rect = canvas.getBoundingClientRect()
    ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top)
    ctx.stroke()
  }

  const stopDrawing = () => {
    setIsDrawing(false)
  }

  const clearCanvas = () => {
    const canvas = canvasRef.current
    if (!canvas) return
    const ctx = canvas.getContext('2d')
    if (!ctx) return
    ctx.clearRect(0, 0, canvas.width, canvas.height)
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-primary-400" />
      </div>
    )
  }

  if (error || !data) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="glass-card p-8 text-center max-w-md">
          <X className="w-16 h-16 text-red-400 mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Errore</h1>
          <p className="text-gray-500">{error || 'Dati non disponibili'}</p>
        </div>
      </div>
    )
  }

  if (step === 'success') {
    return (
      <div className="min-h-screen flex items-center justify-center p-4">
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          className="glass-card p-8 text-center max-w-md"
        >
          <motion.div
            initial={{ scale: 0 }}
            animate={{ scale: 1 }}
            transition={{ delay: 0.2, type: 'spring' }}
          >
            <Check className="w-20 h-20 text-green-400 mx-auto mb-4" />
          </motion.div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Firma Completata!
          </h1>
          <p className="text-gray-500 mb-6">
            Il contratto è stato firmato con successo. Riceverai una copia via email.
          </p>
          <button
            onClick={() => navigate('/')}
            className="glass-button-primary w-full"
          >
            Torna alla Home
          </button>
        </motion.div>
      </div>
    )
  }

  return (
    <div className="min-h-screen p-4 py-12">
      <div className="max-w-4xl mx-auto space-y-6">
        {/* Header */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card p-6"
        >
          <div className="flex items-center gap-4">
            <div className="p-3 rounded-lg bg-primary-500/20">
              <FileText className="w-8 h-8 text-primary-400" />
            </div>
            <div className="flex-1">
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                Firma Contratto
              </h1>
              <p className="text-gray-500">
                {data.contract.contract_number} - {data.contract.subject}
              </p>
            </div>
          </div>
        </motion.div>

        {/* Signer Info */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="glass-card p-6"
        >
          <h2 className="font-semibold text-gray-900 dark:text-white mb-4">Firmatario</h2>
          <div className="space-y-2 text-sm">
            <div><strong className="text-gray-500">Nome:</strong> {data.signer.name}</div>
            <div><strong className="text-gray-500">Email:</strong> {data.signer.email}</div>
            <div><strong className="text-gray-500">Ruolo:</strong> {data.signer.role}</div>
          </div>
        </motion.div>

        {/* Contract Content Preview */}
        {step === 'review' && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="glass-card p-6"
          >
            <h2 className="font-semibold text-gray-900 dark:text-white mb-4">Contratto</h2>
            <p className="text-gray-500 mb-4">
              Clicca qui sotto per visualizzare il documento completo
            </p>
            {data.contract.pdf_path && (
              <a
                href={`${API_URL}/contracts/${data.contract.id}/pdf/view`}
                target="_blank"
                rel="noopener noreferrer"
                className="glass-button inline-flex items-center gap-2"
              >
                <Eye className="w-4 h-4" />
                Visualizza PDF
              </a>
            )}
          </motion.div>
        )}

        {/* OTP Section */}
        {step === 'otp' && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass-card p-6"
          >
            <h2 className="font-semibold text-gray-900 dark:text-white mb-4">Codice di Verifica</h2>
            {!otpSent ? (
              <div>
                <p className="text-gray-500 mb-4">
                  Per firmare il contratto, richiedi il codice OTP che verrà inviato alla tua email
                </p>
                <button
                  onClick={requestOtp}
                  disabled={processing}
                  className="glass-button-primary flex items-center gap-2"
                >
                  <Mail className="w-4 h-4" />
                  {processing ? 'Invio in corso...' : 'Richiedi Codice OTP'}
                </button>
              </div>
            ) : (
              <div>
                <p className="text-green-400 mb-4">
                  Codice OTP inviato alla tua email
                </p>
                <label className="block text-sm font-medium mb-2">
                  Inserisci il codice OTP a 6 cifre
                </label>
                <input
                  type="text"
                  value={otp}
                  onChange={(e) => setOtp(e.target.value.replace(/\D/g, '').slice(0, 6))}
                  maxLength={6}
                  className="glass-input text-center text-2xl tracking-widest mb-4"
                  placeholder="000000"
                />
                <button
                  onClick={() => setStep('sign')}
                  disabled={otp.length !== 6}
                  className="glass-button-primary w-full"
                >
                  Continua
                </button>
              </div>
            )}
          </motion.div>
        )}

        {/* Signature Section */}
        {step === 'sign' && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass-card p-6"
          >
            <h2 className="font-semibold text-gray-900 dark:text-white mb-4">Apponi la tua Firma</h2>
            
            <div className="mb-4">
              <div className="flex gap-2 mb-4">
                <button
                  onClick={() => setSignatureType('drawn')}
                  className={`glass-button flex-1 ${signatureType === 'drawn' ? 'bg-primary-500/20' : ''}`}
                >
                  Disegna Firma
                </button>
                <button
                  onClick={() => setSignatureType('typed')}
                  className={`glass-button flex-1 ${signatureType === 'typed' ? 'bg-primary-500/20' : ''}`}
                >
                  Digita Firma
                </button>
              </div>

              {signatureType === 'drawn' ? (
                <div>
                  <canvas
                    ref={canvasRef}
                    width={600}
                    height={200}
                    onMouseDown={startDrawing}
                    onMouseMove={draw}
                    onMouseUp={stopDrawing}
                    onMouseLeave={stopDrawing}
                    className="w-full border-2 border-white/20 rounded-lg bg-white cursor-crosshair"
                  />
                  <button
                    onClick={clearCanvas}
                    className="glass-button mt-2"
                  >
                    Cancella
                  </button>
                </div>
              ) : (
                <input
                  type="text"
                  value={typedSignature}
                  onChange={(e) => setTypedSignature(e.target.value)}
                  placeholder="Inserisci il tuo nome completo"
                  className="glass-input w-full text-2xl italic"
                  style={{ fontFamily: 'cursive' }}
                />
              )}
            </div>

            <button
              onClick={handleSign}
              disabled={processing}
              className="glass-button-primary w-full flex items-center justify-center gap-2"
            >
              {processing ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Firma in corso...
                </>
              ) : (
                <>
                  <Check className="w-4 h-4" />
                  Firma il Contratto
                </>
              )}
            </button>
          </motion.div>
        )}

        {/* Actions */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="flex gap-4"
        >
          {step === 'review' && (
            <>
              <button
                onClick={() => setStep('otp')}
                className="glass-button-primary flex-1"
              >
                Accetta e Continua
              </button>
              <button
                onClick={handleDecline}
                className="glass-button flex-1 border-red-500/30 hover:bg-red-500/10"
              >
                Rifiuta
              </button>
            </>
          )}
          {step === 'otp' && (
            <button
              onClick={() => setStep('review')}
              className="glass-button"
            >
              Indietro
            </button>
          )}
          {step === 'sign' && (
            <button
              onClick={() => setStep('otp')}
              className="glass-button"
            >
              Indietro
            </button>
          )}
        </motion.div>
      </div>
    </div>
  )
}
