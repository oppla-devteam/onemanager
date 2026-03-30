import { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Bell, X, CheckCircle, AlertCircle, Info } from 'lucide-react'

interface Notification {
  id: number
  type: 'success' | 'error' | 'info'
  message: string
  timestamp: Date
  read: boolean
}

// Stato globale delle notifiche (condiviso tra componenti)
let globalNotifications: Notification[] = []
const listeners: Set<(notifications: Notification[]) => void> = new Set()

export const notificationService = {
  add: (type: 'success' | 'error' | 'info', message: string) => {
    const notification: Notification = {
      id: Date.now(),
      type,
      message,
      timestamp: new Date(),
      read: false
    }
    globalNotifications = [notification, ...globalNotifications]
    listeners.forEach(listener => listener(globalNotifications))
  },

  remove: (id: number) => {
    globalNotifications = globalNotifications.filter(n => n.id !== id)
    listeners.forEach(listener => listener(globalNotifications))
  },

  clear: () => {
    globalNotifications = []
    listeners.forEach(listener => listener(globalNotifications))
  },

  markAllRead: () => {
    globalNotifications = globalNotifications.map(n => ({ ...n, read: true }))
    listeners.forEach(listener => listener(globalNotifications))
  },

  subscribe: (listener: (notifications: Notification[]) => void) => {
    listeners.add(listener)
    listener(globalNotifications)
    return () => listeners.delete(listener)
  }
}

export default function NotificationBell() {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [showPanel, setShowPanel] = useState(false)

  useEffect(() => {
    const unsubscribe = notificationService.subscribe(setNotifications)
    return () => {
      unsubscribe()
    }
  }, [])

  // Chiudi pannello quando clicco fuori
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as HTMLElement
      if (showPanel && !target.closest('.notification-bell-wrapper')) {
        setShowPanel(false)
      }
    }

    if (showPanel) {
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [showPanel])

  const unreadCount = notifications.filter(n => !n.read).length

  return (
    <div className="relative notification-bell-wrapper">
      <button
        onClick={() => {
          setShowPanel(!showPanel)
          if (!showPanel) {
            notificationService.markAllRead()
          }
        }}
        className="relative p-2 text-gray-500 hover:text-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all duration-200"
      >
        <Bell className="w-5 h-5" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
            {unreadCount}
          </span>
        )}
      </button>

      {/* Pannello Notifiche */}
      <AnimatePresence>
        {showPanel && (
          <motion.div
            initial={{ opacity: 0, y: -10, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -10, scale: 0.95 }}
            className="absolute right-0 top-14 w-80 sm:w-96 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg z-50 max-h-[500px] overflow-hidden flex flex-col"
          >
            <div className="p-4 border-b border-gray-200 flex justify-between items-center">
              <h3 className="font-semibold text-gray-900 dark:text-white">Notifiche</h3>
              {notifications.length > 0 && (
                <button
                  onClick={() => notificationService.clear()}
                  className="text-xs text-gray-500 hover:text-gray-900 transition-colors"
                >
                  Cancella tutto
                </button>
              )}
            </div>
            <div className="overflow-y-auto flex-1">
              {notifications.length === 0 ? (
                <div className="p-8 text-center">
                  <Bell className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                  <p className="text-gray-500 text-sm">Nessuna notifica</p>
                </div>
              ) : (
                <div className="divide-y divide-gray-100">
                  {notifications.map((notif) => (
                    <div
                      key={notif.id}
                      className={`p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors ${
                        !notif.read ? 'bg-primary-50/50' : ''
                      }`}
                    >
                      <div className="flex items-start gap-3">
                        <div className={`mt-1 ${
                          notif.type === 'success' ? 'text-green-500' :
                          notif.type === 'error' ? 'text-red-500' :
                          'text-primary-500'
                        }`}>
                          {notif.type === 'success' ? <CheckCircle className="w-5 h-5" /> :
                           notif.type === 'error' ? <AlertCircle className="w-5 h-5" /> :
                           <Info className="w-5 h-5" />}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm text-gray-900 break-words">{notif.message}</p>
                          <p className="text-xs text-gray-500 mt-1">
                            {new Date(notif.timestamp).toLocaleString('it-IT')}
                          </p>
                        </div>
                        <button
                          onClick={(e) => {
                            e.stopPropagation()
                            notificationService.remove(notif.id)
                          }}
                          className="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
