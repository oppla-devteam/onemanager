import { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { X, User, Store, Check, Loader2 } from 'lucide-react'
import { api } from '../utils/api'

interface OpplaUser {
  id: number
  name: string
  email: string
  phone: string | null
  restaurants_count: number
  restaurants: OpplaRestaurant[]
}

interface OpplaRestaurant {
  id: number
  name: string
  slug: string
  address: string | null
  phone: string | null
  email: string | null
}

interface AssociateOpplaModalProps {
  isOpen: boolean
  onClose: () => void
  clientId: string
  clientName: string
  currentOpplaUserId?: number | null
  currentRestaurantIds?: number[]
  onSave: (userId: number, restaurantIds: number[]) => void
}

export default function AssociateOpplaModal({
  isOpen,
  onClose,
  clientId,
  clientName,
  currentOpplaUserId,
  currentRestaurantIds = [],
  onSave
}: AssociateOpplaModalProps) {
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [users, setUsers] = useState<OpplaUser[]>([])
  const [selectedUserId, setSelectedUserId] = useState<number | null>(currentOpplaUserId || null)
  const [selectedRestaurantIds, setSelectedRestaurantIds] = useState<number[]>(currentRestaurantIds)

  // Blocca lo scroll del body quando il modale è aperto
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = 'unset'
    }
    
    return () => {
      document.body.style.overflow = 'unset'
    }
  }, [isOpen])

  useEffect(() => {
    if (isOpen) {
      loadUsersWithRestaurants()
    }
  }, [isOpen])

  const loadUsersWithRestaurants = async () => {
    setLoading(true)
    try {
      const response = await api.get('/oppla/users-with-restaurants')
      setUsers(response.data.data || [])
    } catch (error) {
      console.error('Errore caricamento utenti Oppla:', error)
      alert('Errore nel caricamento dei dati da Oppla')
    } finally {
      setLoading(false)
    }
  }

  const selectedUser = users.find(u => u.id === selectedUserId)

  const toggleRestaurant = (restaurantId: number) => {
    if (selectedRestaurantIds.includes(restaurantId)) {
      setSelectedRestaurantIds(selectedRestaurantIds.filter(id => id !== restaurantId))
    } else {
      setSelectedRestaurantIds([...selectedRestaurantIds, restaurantId])
    }
  }

  const handleSave = async () => {
    if (!selectedUserId) {
      alert('Seleziona un utente Oppla')
      return
    }

    setSaving(true)
    try {
      await api.put(`/clients/${clientId}`, {
        oppla_user_id: selectedUserId,
        oppla_restaurant_ids: selectedRestaurantIds
      })

      onSave(selectedUserId, selectedRestaurantIds)
      onClose()
    } catch (error) {
      console.error('Errore salvataggio associazione:', error)
      alert('Errore nel salvataggio')
    } finally {
      setSaving(false)
    }
  }

  if (!isOpen) return null

  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm"
        onClick={onClose}
      >
        <motion.div
          initial={{ scale: 0.95, opacity: 0 }}
          animate={{ scale: 1, opacity: 1 }}
          exit={{ scale: 0.95, opacity: 0 }}
          className="glass-card w-full h-full flex flex-col overflow-hidden"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Header - Fixed */}
          <div className="p-6 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <div>
              <h2 className="text-2xl font-bold text-gradient">Associa Utente Oppla</h2>
              <p className="text-gray-500 mt-1">Cliente: {clientName}</p>
            </div>
            <button
              onClick={onClose}
              className="glass-button p-2 hover:bg-red-500/20"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto p-6">
            {loading ? (
              <div className="flex items-center justify-center py-12">
                <Loader2 className="w-8 h-8 text-primary-400 animate-spin" />
              </div>
            ) : (
              <div className="space-y-6">
                {/* Selezione Utente */}
                <div>
                  <label className="block text-sm font-medium mb-3">
                    Seleziona Utente Oppla *
                  </label>
                  <div className="grid grid-cols-1 gap-3">
                    {users.map(user => (
                      <button
                        key={user.id}
                        onClick={() => setSelectedUserId(user.id)}
                        className={`glass-card p-4 text-left transition-all ${
                          selectedUserId === user.id
                            ? 'ring-2 ring-primary-500 bg-primary-500/10'
                            : 'hover:bg-white/5'
                        }`}
                      >
                        <div className="flex items-start justify-between">
                          <div className="flex items-start gap-3">
                            <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${
                              selectedUserId === user.id ? 'bg-primary-500/20' : 'bg-white/5'
                            }`}>
                              <User className={`w-5 h-5 ${
                                selectedUserId === user.id ? 'text-primary-400' : 'text-gray-500'
                              }`} />
                            </div>
                            <div>
                              <div className="font-semibold">{user.name}</div>
                              <div className="text-sm text-gray-500">{user.email}</div>
                              {user.phone && (
                                <div className="text-sm text-gray-400">{user.phone}</div>
                              )}
                              <div className="text-xs text-primary-400 mt-1">
                                {user.restaurants_count} {user.restaurants_count === 1 ? 'ristorante' : 'ristoranti'}
                              </div>
                            </div>
                          </div>
                          {selectedUserId === user.id && (
                            <Check className="w-5 h-5 text-primary-400" />
                          )}
                        </div>
                      </button>
                    ))}
                  </div>
                </div>

                {/* Selezione Ristoranti */}
                {selectedUser && selectedUser.restaurants.length > 0 && (
                  <div>
                    <label className="block text-sm font-medium mb-3">
                      Seleziona Ristoranti (opzionale)
                    </label>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                      {selectedUser.restaurants.map(restaurant => (
                        <button
                          key={restaurant.id}
                          onClick={() => toggleRestaurant(restaurant.id)}
                          className={`glass-card p-3 text-left transition-all ${
                            selectedRestaurantIds.includes(restaurant.id)
                              ? 'ring-2 ring-green-500 bg-green-500/10'
                              : 'hover:bg-white/5'
                          }`}
                        >
                          <div className="flex items-start justify-between">
                            <div className="flex items-start gap-2">
                              <Store className={`w-4 h-4 mt-0.5 ${
                                selectedRestaurantIds.includes(restaurant.id) ? 'text-green-400' : 'text-gray-500'
                              }`} />
                              <div>
                                <div className="font-medium text-sm">{restaurant.name}</div>
                                {restaurant.slug && (
                                  <div className="text-xs text-gray-400 font-mono">{restaurant.slug}</div>
                                )}
                                {restaurant.address && (
                                  <div className="text-xs text-gray-400 mt-1">{restaurant.address}</div>
                                )}
                              </div>
                            </div>
                            {selectedRestaurantIds.includes(restaurant.id) && (
                              <Check className="w-4 h-4 text-green-400" />
                            )}
                          </div>
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button
              onClick={onClose}
              className="glass-button"
              disabled={saving}
            >
              Annulla
            </button>
            <button
              onClick={handleSave}
              disabled={saving || !selectedUserId}
              className="glass-button-primary flex items-center gap-2"
            >
              {saving ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Salvataggio...
                </>
              ) : (
                <>
                  <Check className="w-4 h-4" />
                  Salva Associazione
                </>
              )}
            </button>
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  )
}
