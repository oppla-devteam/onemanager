import { useEffect, useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { CheckCircle, AlertCircle, Info, X } from 'lucide-react'
import { notificationService } from './NotificationBell'

interface Notification {
  id: number
  type: 'success' | 'error' | 'info'
  message: string
  timestamp: Date
  read: boolean
}

export default function ToastNotifications() {
  const [notifications, setNotifications] = useState<Notification[]>([])

  useEffect(() => {
    const unsubscribe = notificationService.subscribe(setNotifications)
    return () => {
      unsubscribe()
    }
  }, [])

  return (
    <div className="fixed top-4 right-4 z-50 space-y-2">
      <AnimatePresence>
        {notifications.slice(0, 3).map((notif) => (
          <motion.div
            key={notif.id}
            initial={{ opacity: 0, x: 50, scale: 0.9 }}
            animate={{ opacity: 1, x: 0, scale: 1 }}
            exit={{ opacity: 0, x: 50, scale: 0.9 }}
            transition={{ duration: 0.2 }}
            className={`glass-card p-4 min-w-[300px] max-w-[400px] shadow-xl border-l-4 ${
              notif.type === 'success' ? 'border-green-500 bg-green-900/20' :
              notif.type === 'error' ? 'border-red-500 bg-red-900/20' :
              'border-primary-500 bg-primary-900/20'
            }`}
          >
            <div className="flex items-start justify-between gap-3">
              <div className="flex items-start gap-2 flex-1">
                <div className={`mt-0.5 ${
                  notif.type === 'success' ? 'text-green-400' :
                  notif.type === 'error' ? 'text-red-400' :
                  'text-primary-400'
                }`}>
                  {notif.type === 'success' ? <CheckCircle className="w-4 h-4" /> :
                   notif.type === 'error' ? <AlertCircle className="w-4 h-4" /> :
                   <Info className="w-4 h-4" />}
                </div>
                <p className="text-sm text-white flex-1">{notif.message}</p>
              </div>
              <button
                onClick={() => notificationService.remove(notif.id)}
                className="text-gray-500 hover:text-white transition-colors flex-shrink-0"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          </motion.div>
        ))}
      </AnimatePresence>
    </div>
  )
}
