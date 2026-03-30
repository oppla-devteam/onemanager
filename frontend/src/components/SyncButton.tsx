import { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { RefreshCw, Database, Check, X, Loader2, Clock } from 'lucide-react'
import { syncApi } from '../utils/api'

export default function SyncButton() {
  const [isSyncing, setIsSyncing] = useState(false)
  const [showStatus, setShowStatus] = useState(false)
  const [syncResult, setSyncResult] = useState<{
    success: boolean
    message: string
    data?: any
  } | null>(null)
  const [lastSyncTime, setLastSyncTime] = useState<number | null>(null)
  const [syncCooldown, setSyncCooldown] = useState<number>(0)
  const [showTooltip, setShowTooltip] = useState(false)

  // Load last sync time from localStorage
  useEffect(() => {
    const lastSync = localStorage.getItem('globalLastSync')
    if (lastSync) {
      setLastSyncTime(parseInt(lastSync))
    }
  }, [])

  // Update cooldown timer
  useEffect(() => {
    if (!lastSyncTime) return

    const updateCooldown = () => {
      const now = Date.now()
      const threeHours = 3 * 60 * 60 * 1000 // 3 ore in millisecondi
      const timePassed = now - lastSyncTime
      const remaining = Math.max(0, threeHours - timePassed)
      setSyncCooldown(remaining)
    }

    updateCooldown()
    const interval = setInterval(updateCooldown, 1000)
    return () => clearInterval(interval)
  }, [lastSyncTime])

  const isBlockedByTime = () => {
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    if (isLocalhost) return false
    
    const now = new Date()
    const hours = now.getHours()
    return (hours >= 11 && hours < 16) || (hours >= 18 && hours < 22)
  }

  const getTooltipMessage = () => {
    if (isSyncing) return 'Sincronizzazione in corso...'
    if (isBlockedByTime()) return 'Sincronizzazione bloccata durante orari di prenotazione (11:00-16:00 e 18:00-22:00)'
    if (syncCooldown > 0) {
      const h = Math.floor(syncCooldown / (60 * 60 * 1000))
      const m = Math.floor((syncCooldown % (60 * 60 * 1000)) / (60 * 1000))
      return `Attendi ancora ${h}h ${m}m prima di sincronizzare`
    }
    return 'Sincronizza database'
  }

  const isDisabled = isSyncing || syncCooldown > 0 || isBlockedByTime()

  const handleSync = async () => {
    console.log('🔄 SyncButton clicked!')
    
    // Verifica se siamo in localhost
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    
    // Verifica orari di blocco (11-16 e 18-22) - solo se NON siamo in localhost
    const now = new Date()
    const hours = now.getHours()
    const isBlockedTime = !isLocalhost && ((hours >= 11 && hours < 16) || (hours >= 18 && hours < 22))
    
    console.log('⏰ Current hour:', hours, 'Localhost:', isLocalhost, 'Blocked:', isBlockedTime)
    
    if (isBlockedTime) {
      console.warn('⛔ Blocked time - showing message')
      setSyncResult({
        success: false,
        message: 'Sincronizzazione bloccata durante gli orari di prenotazione (11:00-16:00 e 18:00-22:00)'
      })
      setShowStatus(true)
      setTimeout(() => {
        setShowStatus(false)
        setSyncResult(null)
      }, 5000)
      return
    }

    if (syncCooldown > 0) {
      console.warn('⏱️ Cooldown active:', syncCooldown)
      const h = Math.floor(syncCooldown / (60 * 60 * 1000))
      const m = Math.floor((syncCooldown % (60 * 60 * 1000)) / (60 * 1000))
      const s = Math.floor((syncCooldown % (60 * 1000)) / 1000)
      setSyncResult({
        success: false,
        message: `Devi aspettare ancora ${h}h ${m}m ${s}s prima di sincronizzare di nuovo`
      })
      setShowStatus(true)
      setTimeout(() => {
        setShowStatus(false)
        setSyncResult(null)
      }, 5000)
      return
    }

    console.log('Starting sync...')
    setIsSyncing(true)
    setShowStatus(true)
    setSyncResult(null)

    try {
      console.log('📡 Calling syncApi.syncAll()')
      // Chiama il nuovo endpoint di sincronizzazione completa
      const response = await syncApi.syncAll()
      console.log('📥 Response received:', response)
      const result = response.data
      
      setSyncResult({
        success: result.success,
        message: result.message,
        data: result.data
      })

      // Se successo, salva timestamp e ricarica
      if (result.success) {
        console.log('Sync successful!')
        const now = Date.now()
        localStorage.setItem('globalLastSync', now.toString())
        setLastSyncTime(now)
        
        setTimeout(() => {
          window.location.reload()
        }, 2000)
      }

    } catch (error: any) {
      console.error('❌ Sync error details:', error)
      
      const errorMessage = error.response?.data?.error 
        || error.response?.data?.message 
        || error.message 
        || 'Errore durante la sincronizzazione'
      
      const errorDetails = error.response?.data?.error_details
      
      setSyncResult({
        success: false,
        message: errorMessage,
        data: {
          ...error.response?.data,
          error_details: errorDetails
        }
      })
    } finally {
      setIsSyncing(false)
      
      // Nascondi status dopo 5 secondi
      setTimeout(() => {
        setShowStatus(false)
        setSyncResult(null)
      }, 5000)
    }
  }

  return (
    <div className="relative">
      {/* Pulsante Sincronizzazione */}
      <motion.button
        onClick={handleSync}
        disabled={isDisabled}
        onMouseEnter={() => setShowTooltip(true)}
        onMouseLeave={() => setShowTooltip(false)}
        className={`
          relative flex items-center gap-2 px-4 py-2 rounded-lg
          transition-all duration-300
          ${isSyncing
            ? 'bg-primary-500/20 cursor-not-allowed opacity-50'
            : isDisabled
              ? 'bg-gray-500/20 cursor-not-allowed opacity-50 border border-gray-500/30'
              : syncCooldown > 0
                ? 'bg-yellow-500/20 hover:bg-yellow-500/30 border border-yellow-500/30'
                : 'bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700'
          }
          text-white font-medium
          shadow-lg shadow-primary-500/20
        `}
        whileHover={!isDisabled ? { scale: 1.05 } : {}}
        whileTap={!isDisabled ? { scale: 0.95 } : {}}
      >
        <motion.div
          animate={isSyncing ? { rotate: 360 } : { rotate: 0 }}
          transition={isSyncing ? { duration: 1, repeat: Infinity, ease: 'linear' } : {}}
        >
          {isSyncing ? (
            <Loader2 className="w-5 h-5" />
          ) : syncCooldown > 0 ? (
            <Clock className="w-5 h-5" />
          ) : (
            <RefreshCw className="w-5 h-5" />
          )}
        </motion.div>
        
        <span className="hidden md:inline">
          {isSyncing ? 'Sincronizzazione...' : syncCooldown > 0 ? (
            `${Math.floor(syncCooldown / (60 * 60 * 1000))}h ${Math.floor((syncCooldown % (60 * 60 * 1000)) / (60 * 1000))}m`
          ) : 'Sincronizza'}
        </span>
      </motion.button>

      {/* Tooltip */}
      <AnimatePresence>
        {showTooltip && (
          <motion.div
            initial={{ opacity: 0, y: 5 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 5 }}
            transition={{ duration: 0.15 }}
            className="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg whitespace-nowrap shadow-xl z-50"
          >
            {getTooltipMessage()}
            <div className="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900" />
          </motion.div>
        )}
      </AnimatePresence>

      {/* Status Popup */}
      <AnimatePresence>
        {showStatus && syncResult && (
          <motion.div
            initial={{ opacity: 0, y: -10, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -10, scale: 0.9 }}
            className={`
              fixed top-20 right-6 w-80
              glass-card p-4 border
              ${syncResult.success 
                ? 'border-green-500/30 bg-green-500/5' 
                : 'border-red-500/30 bg-red-500/5'
              }
              z-[9999]
            `}
          >
            <div className="flex items-start gap-3">
              {/* Icona Status */}
              <div className={`
                w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                ${syncResult.success 
                  ? 'bg-green-500/20' 
                  : 'bg-red-500/20'
                }
              `}>
                {syncResult.success ? (
                  <Check className="w-5 h-5 text-green-400" />
                ) : (
                  <X className="w-5 h-5 text-red-400" />
                )}
              </div>

              {/* Messaggio */}
              <div className="flex-1">
                <h4 className={`
                  font-semibold mb-1
                  ${syncResult.success ? 'text-green-400' : 'text-red-400'}
                `}>
                  {syncResult.success ? 'Sincronizzazione Riuscita' : 'Sincronizzazione Fallita'}
                </h4>
                <p className="text-sm text-gray-600">
                  {syncResult.message}
                </p>
              </div>
            </div>

            {/* Progress bar animata */}
            {syncResult.success && (
              <motion.div
                className="h-1 bg-green-500/30 rounded-full mt-3 overflow-hidden"
              >
                <motion.div
                  className="h-full bg-green-400"
                  initial={{ width: '0%' }}
                  animate={{ width: '100%' }}
                  transition={{ duration: 2 }}
                />
              </motion.div>
            )}
          </motion.div>
        )}
      </AnimatePresence>

      {/* Tooltip Database Info */}
      <div className="absolute -bottom-2 right-0 pointer-events-none">
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="flex items-center gap-1 text-xs text-gray-400"
        >
       
         
        </motion.div>
      </div>
    </div>
  )
}
