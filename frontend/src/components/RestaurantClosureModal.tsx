import { motion, AnimatePresence } from 'framer-motion'
import { X, Calendar, Clock, AlertTriangle, Loader2, CheckCircle2, XCircle, RotateCcw, List } from 'lucide-react'
import { useState, useEffect } from 'react'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

interface ClosureModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess?: () => void
}

interface ClosureJob {
  job_id: string
  batch_id?: string
  status: 'running' | 'completed' | 'failed' | 'error'
  stats?: {
    total: number
    success: number
    failed: number
  }
  error?: string
  completed_at?: string
}

interface ClosureBatch {
  batch_id: string
  start_date: string
  end_date: string
  reason: string
  status: string
  total_restaurants: number
  successful_closures: number
  failed_closures: number
  holiday_count: number
  created_at: string
}

export default function RestaurantClosureModal({ isOpen, onClose, onSuccess }: ClosureModalProps) {
  const [startDate, setStartDate] = useState('')
  const [endDate, setEndDate] = useState('')
  const [reason, setReason] = useState('Chiusura programmata')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [job, setJob] = useState<ClosureJob | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [batches, setBatches] = useState<ClosureBatch[]>([])
  const [loadingBatches, setLoadingBatches] = useState(false)
  const [reopeningBatchId, setReopeningBatchId] = useState<string | null>(null)
  const [showBatches, setShowBatches] = useState(false)

  // Carica i batch recenti quando il modal si apre
  useEffect(() => {
    if (isOpen) {
      loadBatches()
    }
  }, [isOpen])

  const loadBatches = async () => {
    setLoadingBatches(true)
    try {
      const token = localStorage.getItem('token')
      const response = await axios.get(`${API_URL}/restaurants/closure-batches?limit=10`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      setBatches(response.data.data || [])
    } catch (err: any) {
      console.error('Errore caricamento batches:', err)
    } finally {
      setLoadingBatches(false)
    }
  }

  // Helper per formattare date in formato datetime-local
  const formatDateForInput = (date: Date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    return `${year}-${month}-${day}T${hours}:${minutes}`
  }

  // Shortcut: Stasera (oggi 18:00 - 23:59)
  const setStasera = () => {
    const now = new Date()
    const start = new Date(now)
    start.setHours(18, 0, 0, 0)

    const end = new Date(now)
    end.setHours(23, 59, 0, 0)

    setStartDate(formatDateForInput(start))
    setEndDate(formatDateForInput(end))
  }

  // Shortcut: Oggi tutto il giorno (now + 30 min - 23:59)
  const setOggi = () => {
    const now = new Date()
    const start = new Date(now.getTime() + 30 * 60000) // +30 minuti

    const end = new Date(now)
    end.setHours(23, 59, 0, 0)

    setStartDate(formatDateForInput(start))
    setEndDate(formatDateForInput(end))
  }

  // Shortcut: Domani sera (domani 18:00 - 23:59)
  const setDomaniSera = () => {
    const now = new Date()
    const tomorrow = new Date(now)
    tomorrow.setDate(tomorrow.getDate() + 1)

    const start = new Date(tomorrow)
    start.setHours(18, 0, 0, 0)

    const end = new Date(tomorrow)
    end.setHours(23, 59, 0, 0)

    setStartDate(formatDateForInput(start))
    setEndDate(formatDateForInput(end))
  }

  // Shortcut: Domani tutto il giorno (domani now + 30 min - 23:59)
  const setDomaniTuttoGiorno = () => {
    const now = new Date()
    const tomorrow = new Date(now)
    tomorrow.setDate(tomorrow.getDate() + 1)

    // Start: domani alla stessa ora di adesso + 30 minuti
    const start = new Date(tomorrow.getTime() + 30 * 60000)

    const end = new Date(tomorrow)
    end.setHours(23, 59, 0, 0)

    setStartDate(formatDateForInput(start))
    setEndDate(formatDateForInput(end))
  }

  // Polling per controllare stato job chiusura
  const pollJobStatus = async (jobId: string) => {
    const maxAttempts = 120 // 10 minuti (120 * 5 secondi)
    let attempts = 0

    const poll = async () => {
      try {
        const token = localStorage.getItem('token')
        const response = await axios.get(`${API_URL}/restaurants/close-status/${jobId}`, {
          headers: { Authorization: `Bearer ${token}` }
        })

        const jobStatus = response.data.data
        setJob(jobStatus)

        if (jobStatus.status === 'completed' || jobStatus.status === 'failed' || jobStatus.status === 'error') {
          // Job completato - mostra notifica
          setIsSubmitting(false)

          if (jobStatus.status === 'completed') {
            if (onSuccess) onSuccess()
            loadBatches()
          }
          return
        }

        // Continua polling
        attempts++
        if (attempts < maxAttempts) {
          setTimeout(poll, 5000) // Ogni 5 secondi
        } else {
          setError('Timeout: operazione troppo lunga')
          setIsSubmitting(false)
        }
      } catch (err: any) {
        console.error('Errore polling status:', err)
        setError(err.response?.data?.message || 'Errore durante il controllo dello stato')
        setIsSubmitting(false)
      }
    }

    poll()
  }

  // Polling per controllare stato job riapertura
  const pollReopenStatus = async (jobId: string, batchId: string) => {
    const maxAttempts = 120
    let attempts = 0

    const poll = async () => {
      try {
        const token = localStorage.getItem('token')
        const response = await axios.get(`${API_URL}/restaurants/reopen-status/${jobId}`, {
          headers: { Authorization: `Bearer ${token}` }
        })

        const jobStatus = response.data.data

        if (jobStatus.status === 'completed' || jobStatus.status === 'failed' || jobStatus.status === 'error') {
          setReopeningBatchId(null)

          if (jobStatus.status === 'completed') {
            loadBatches()
            if (onSuccess) onSuccess()
          }
          return
        }

        // Continua polling
        attempts++
        if (attempts < maxAttempts) {
          setTimeout(poll, 5000)
        } else {
          setReopeningBatchId(null)
        }
      } catch (err: any) {
        console.error('Errore polling reopen status:', err)
        setReopeningBatchId(null)
      }
    }

    poll()
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setJob(null)

    // Validazione
    if (!startDate || !endDate) {
      setError('Seleziona entrambe le date')
      return
    }

    const start = new Date(startDate)
    const end = new Date(endDate)

    if (end <= start) {
      setError('La data di fine deve essere successiva alla data di inizio')
      return
    }

    // Conferma utente
    const totalHours = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60))
    const totalDays = Math.ceil(totalHours / 24)

    const confirmMessage = `ATTENZIONE!\n\nStai per chiudere TUTTI i ristoranti OPPLA per:\n\n${totalDays} giorni (${totalHours} ore)\nDal: ${start.toLocaleString('it-IT')}\nAl: ${end.toLocaleString('it-IT')}\n\nQuesta operazione:\n- Creera una chiusura per ogni ristorante\n- Potrebbe richiedere diversi minuti\n- NON puo essere annullata una volta avviata\n\nConfermi?`

    if (!window.confirm(confirmMessage)) {
      return
    }

    setIsSubmitting(true)

    try {
      const token = localStorage.getItem('token')

      const response = await axios.post(
        `${API_URL}/restaurants/close-period`,
        {
          start_date: startDate,
          end_date: endDate,
          reason: reason.trim() || 'Chiusura programmata',
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        }
      )

      const jobId = response.data.data.job_id
      const batchId = response.data.data.batch_id

      setJob({
        job_id: jobId,
        batch_id: batchId,
        status: 'running',
      })

      // Avvia polling stato
      pollJobStatus(jobId)
    } catch (err: any) {
      console.error('Errore:', err)
      setError(err.response?.data?.message || 'Errore durante l\'avvio della chiusura')
      setIsSubmitting(false)
    }
  }

  const handleReopen = async (batchId: string) => {
    if (!window.confirm('Sei sicuro di voler riaprire tutti i ristoranti di questo batch?')) {
      return
    }

    setReopeningBatchId(batchId)

    try {
      const token = localStorage.getItem('token')
      const response = await axios.post(
        `${API_URL}/restaurants/reopen-batch/${batchId}`,
        {},
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      )

      const jobId = response.data.data.job_id
      pollReopenStatus(jobId, batchId)
    } catch (err: any) {
      console.error('Errore riapertura:', err)
      setReopeningBatchId(null)
    }
  }

  const resetAndClose = () => {
    setStartDate('')
    setEndDate('')
    setReason('Chiusura programmata')
    setJob(null)
    setError(null)
    setIsSubmitting(false)
    setShowBatches(false)
    onClose()
  }

  const formatDate = (isoDate: string) => {
    return new Date(isoDate).toLocaleString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={resetAndClose}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
          />

          {/* Modal */}
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-2xl shadow-2xl border border-gray-200 w-full max-w-4xl max-h-[90vh] overflow-hidden"
            >
              {/* Header */}
              <div className="flex items-center justify-between p-6 border-b border-gray-200">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-lg bg-orange-500/20">
                    <AlertTriangle className="w-6 h-6 text-orange-400" />
                  </div>
                  <div>
                    <h2 className="text-2xl font-bold text-white">Chiusura Massiva Ristoranti</h2>
                    <p className="text-sm text-gray-500 mt-1">
                      Gestisci le chiusure per TUTTI i ristoranti
                    </p>
                  </div>
                </div>
                <button
                  onClick={resetAndClose}
                  disabled={isSubmitting}
                  className="p-2 rounded-lg hover:bg-white/10 transition-colors disabled:opacity-50"
                >
                  <X className="w-5 h-5 text-gray-500" />
                </button>
              </div>

              {/* Tabs */}
              <div className="flex border-b border-gray-200">
                <button
                  onClick={() => setShowBatches(false)}
                  className={`flex-1 px-6 py-3 text-sm font-medium transition-colors ${
                    !showBatches
                      ? 'text-white border-b-2 border-orange-500 bg-white/5'
                      : 'text-gray-500 hover:text-white hover:bg-white/5'
                  }`}
                >
                  <AlertTriangle className="w-4 h-4 inline mr-2" />
                  Nuova Chiusura
                </button>
                <button
                  onClick={() => setShowBatches(true)}
                  className={`flex-1 px-6 py-3 text-sm font-medium transition-colors ${
                    showBatches
                      ? 'text-white border-b-2 border-orange-500 bg-white/5'
                      : 'text-gray-500 hover:text-white hover:bg-white/5'
                  }`}
                >
                  <List className="w-4 h-4 inline mr-2" />
                  Chiusure Recenti ({batches.length})
                </button>
              </div>

              {/* Content */}
              <div className="p-6 overflow-y-auto max-h-[calc(90vh-220px)]">
                {!showBatches ? (
                  /* Form Chiusura */
                  <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Data Inizio */}
                    <div>
                      <label className="block text-sm font-medium text-gray-600 mb-2">
                        <Calendar className="w-4 h-4 inline mr-2" />
                        Data e Ora Inizio Chiusura
                      </label>
                      <input
                        type="datetime-local"
                        value={startDate}
                        onChange={(e) => setStartDate(e.target.value)}
                        disabled={isSubmitting}
                        required
                        className="w-full px-4 py-3 bg-white/5 border border-gray-200 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                      />
                    </div>

                    {/* Data Fine */}
                    <div>
                      <label className="block text-sm font-medium text-gray-600 mb-2">
                        <Clock className="w-4 h-4 inline mr-2" />
                        Data e Ora Fine Chiusura
                      </label>
                      <input
                        type="datetime-local"
                        value={endDate}
                        onChange={(e) => setEndDate(e.target.value)}
                        disabled={isSubmitting}
                        required
                        className="w-full px-4 py-3 bg-white/5 border border-gray-200 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                      />
                    </div>

                    {/* Quick Presets */}
                    <div className="flex gap-2 flex-wrap">
                      <button
                        type="button"
                        onClick={setStasera}
                        disabled={isSubmitting}
                        className="px-3 py-1.5 text-xs bg-orange-500/20 text-orange-300 rounded-lg hover:bg-orange-500/30 transition-colors disabled:opacity-50"
                      >
                        Stasera
                      </button>
                      <button
                        type="button"
                        onClick={setOggi}
                        disabled={isSubmitting}
                        className="px-3 py-1.5 text-xs bg-red-500/20 text-red-300 rounded-lg hover:bg-red-500/30 transition-colors disabled:opacity-50"
                      >
                        Oggi
                      </button>
                      <button
                        type="button"
                        onClick={setDomaniSera}
                        disabled={isSubmitting}
                        className="px-3 py-1.5 text-xs bg-purple-500/20 text-purple-300 rounded-lg hover:bg-purple-500/30 transition-colors disabled:opacity-50"
                      >
                        Domani sera
                      </button>
                      <button
                        type="button"
                        onClick={setDomaniTuttoGiorno}
                        disabled={isSubmitting}
                        className="px-3 py-1.5 text-xs bg-primary-500/20 text-primary-300 rounded-lg hover:bg-primary-500/30 transition-colors disabled:opacity-50"
                      >
                        Domani tutto giorno
                      </button>
                    </div>

                    {/* Motivazione */}
                    <div>
                      <label className="block text-sm font-medium text-gray-600 mb-2">
                        Motivazione Chiusura
                      </label>
                      <input
                        type="text"
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        disabled={isSubmitting}
                        maxLength={255}
                        placeholder="Es: Ferie estive, Chiusura natalizia, Manutenzione..."
                        className="w-full px-4 py-3 bg-white/5 border border-gray-200 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                      />
                    </div>

                    {/* Error Message */}
                    {error && (
                      <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
                        <div className="flex items-start gap-3">
                          <XCircle className="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" />
                          <p className="text-sm text-red-200">{error}</p>
                        </div>
                      </div>
                    )}

                    {/* Job Status */}
                    {job && job.status === 'running' && (
                      <div className="bg-primary-500/10 border border-primary-500/30 rounded-lg p-4">
                        <div className="flex items-start gap-3">
                          <Loader2 className="w-5 h-5 text-primary-400 animate-spin flex-shrink-0 mt-0.5" />
                          <div className="text-sm text-primary-200">
                            <p className="font-semibold mb-1">Operazione in corso...</p>
                            <p className="text-primary-300/90">
                              Lo script Python sta chiudendo i ristoranti. Puoi chiudere il modal.
                            </p>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Actions */}
                    <div className="flex gap-3 pt-4">
                      <button
                        type="button"
                        onClick={resetAndClose}
                        disabled={isSubmitting}
                        className="flex-1 px-6 py-3 bg-white/5 hover:bg-white/10 border border-gray-200 rounded-lg text-white font-medium transition-colors disabled:opacity-50"
                      >
                        Chiudi
                      </button>
                      <button
                        type="submit"
                        disabled={isSubmitting || !startDate || !endDate}
                        className="flex-1 px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 rounded-lg text-white font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                      >
                        {isSubmitting ? (
                          <>
                            <Loader2 className="w-5 h-5 animate-spin" />
                            Elaborazione...
                          </>
                        ) : (
                          <>
                            <AlertTriangle className="w-5 h-5" />
                            Chiudi Tutti i Ristoranti
                          </>
                        )}
                      </button>
                    </div>
                  </form>
                ) : (
                  /* Lista Batch */
                  <div className="space-y-4">
                    {loadingBatches ? (
                      <div className="flex items-center justify-center py-12">
                        <Loader2 className="w-8 h-8 animate-spin text-gray-500" />
                      </div>
                    ) : batches.length === 0 ? (
                      <div className="text-center py-12">
                        <p className="text-gray-500">Nessuna chiusura recente</p>
                      </div>
                    ) : (
                      batches.map((batch) => (
                        <div
                          key={batch.batch_id}
                          className="bg-white/5 border border-gray-200 rounded-lg p-4 hover:bg-white/10 transition-colors"
                        >
                          <div className="flex items-start justify-between gap-4">
                            <div className="flex-1">
                              <div className="flex items-center gap-2 mb-2">
                                <span className={`px-2 py-1 rounded text-xs font-medium ${
                                  batch.status === 'completed' ? 'bg-green-500/20 text-green-300' :
                                  batch.status === 'running' ? 'bg-primary-500/20 text-primary-300' :
                                  'bg-red-500/20 text-red-300'
                                }`}>
                                  {batch.status === 'completed' ? 'Completato' :
                                   batch.status === 'running' ? 'In corso' : 'Errore'}
                                </span>
                                <span className="text-xs text-gray-400">
                                  {formatDate(batch.created_at)}
                                </span>
                              </div>
                              <p className="text-white font-medium mb-1">{batch.reason}</p>
                              <p className="text-sm text-gray-500 mb-2">
                                {formatDate(batch.start_date)} → {formatDate(batch.end_date)}
                              </p>
                              <div className="flex items-center gap-4 text-xs text-gray-500">
                                <span>Ristoranti: {batch.total_restaurants}</span>
                                <span className="text-green-400 flex items-center gap-1"><CheckCircle2 className="w-3 h-3" /> {batch.successful_closures}</span>
                                <span className="text-red-400 flex items-center gap-1"><XCircle className="w-3 h-3" /> {batch.failed_closures}</span>
                                <span>Chiusure attive: {batch.holiday_count}</span>
                              </div>
                            </div>
                            {batch.holiday_count > 0 && (
                              <button
                                onClick={() => handleReopen(batch.batch_id)}
                                disabled={reopeningBatchId === batch.batch_id}
                                className="px-4 py-2 bg-green-500/20 text-green-300 rounded-lg hover:bg-green-500/30 transition-colors disabled:opacity-50 flex items-center gap-2 text-sm font-medium"
                              >
                                {reopeningBatchId === batch.batch_id ? (
                                  <>
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                    Riapertura...
                                  </>
                                ) : (
                                  <>
                                    <RotateCcw className="w-4 h-4" />
                                    Riapri Tutti
                                  </>
                                )}
                              </button>
                            )}
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                )}
              </div>
            </motion.div>
          </div>
        </>
      )}
    </AnimatePresence>
  )
}
