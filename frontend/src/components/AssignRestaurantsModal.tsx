import { motion } from 'framer-motion'
import { useState, useEffect } from 'react'
import { X, Store, User, Building2, Check, AlertCircle } from 'lucide-react'
import Modal from './Modal'

interface UnassignedRestaurant {
  id: number
  oppla_external_id: number
  nome: string
  indirizzo?: string
  citta?: string
  telefono?: string
  email?: string
  partner?: {
    id: number
    nome: string
    cognome: string
    email: string
    telefono?: string
  }
  oppla_sync_at: string
}

interface AssignRestaurantsModalProps {
  isOpen: boolean
  onClose: () => void
  onAssigned: () => void
}

export default function AssignRestaurantsModal({ isOpen, onClose, onAssigned }: AssignRestaurantsModalProps) {
  const [unassignedRestaurants, setUnassignedRestaurants] = useState<UnassignedRestaurant[]>([])
  const [clients, setClients] = useState<any[]>([])
  const [selectedRestaurants, setSelectedRestaurants] = useState<Set<number>>(new Set())
  const [selectedClientId, setSelectedClientId] = useState<string>('')
  const [loading, setLoading] = useState(true)
  const [assigning, setAssigning] = useState(false)

  useEffect(() => {
    if (isOpen) {
      loadData()
    }
  }, [isOpen])

  const loadData = async () => {
    setLoading(true)
    try {
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      
      // Carica ristoranti non assegnati
      const restaurantsRes = await fetch(`${API_URL}/restaurants/unassigned`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json',
        }
      })
      const restaurantsData = await restaurantsRes.json()
      
      // Carica clienti/titolari
      const clientsRes = await fetch(`${API_URL}/clients`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json',
        }
      })
      const clientsData = await clientsRes.json()

      if (restaurantsData.success) {
        setUnassignedRestaurants(restaurantsData.data)
      }
      
      if (clientsData.data) {
        setClients(clientsData.data)
      }
    } catch (error) {
      console.error('Errore caricamento dati:', error)
    } finally {
      setLoading(false)
    }
  }

  const toggleRestaurant = (id: number) => {
    const newSelected = new Set(selectedRestaurants)
    if (newSelected.has(id)) {
      newSelected.delete(id)
    } else {
      newSelected.add(id)
    }
    setSelectedRestaurants(newSelected)
  }

  const handleAssign = async () => {
    if (!selectedClientId || selectedRestaurants.size === 0) {
      alert('Seleziona un titolare e almeno un ristorante')
      return
    }

    setAssigning(true)
    try {
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      
      const response = await fetch(`${API_URL}/restaurants/assign`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          client_id: selectedClientId,
          restaurant_ids: Array.from(selectedRestaurants),
        })
      })

      const data = await response.json()

      if (data.success) {
        alert(data.message)
        setSelectedRestaurants(new Set())
        setSelectedClientId('')
        loadData()
        onAssigned()
      } else {
        alert(data.message || 'Errore durante l\'assegnazione')
      }
    } catch (error: any) {
      alert('Errore: ' + error.message)
    } finally {
      setAssigning(false)
    }
  }

  if (!isOpen) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Assegna Ristoranti a Titolare">
      <div className="space-y-6">
        {loading ? (
          <div className="text-center py-12">
            <div className="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full mx-auto mb-4"></div>
            <p className="text-gray-500">Caricamento...</p>
          </div>
        ) : unassignedRestaurants.length === 0 ? (
          <div className="text-center py-12">
            <Check className="w-16 h-16 text-green-400 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-white mb-2">Tutti i ristoranti sono assegnati!</h3>
            <p className="text-gray-500">Non ci sono ristoranti da assegnare ai titolari.</p>
          </div>
        ) : (
          <>
            {/* Alert info */}
            <div className="glass-card p-4 bg-primary-500/10 border border-primary-500/20">
              <div className="flex items-start gap-3">
                <AlertCircle className="w-5 h-5 text-primary-400 mt-0.5" />
                <div className="text-sm">
                  <p className="text-white font-medium mb-1">Ristoranti sincronizzati da Oppla</p>
                  <p className="text-gray-600">
                    Questi ristoranti sono stati importati dal database Oppla e devono essere assegnati a un titolare locale.
                    Seleziona i ristoranti e il titolare a cui assegnarli.
                  </p>
                </div>
              </div>
            </div>

            {/* Selezione Titolare */}
            <div>
              <label className="block text-sm font-medium mb-2 flex items-center gap-2">
                <Building2 className="w-4 h-4 text-primary-400" />
                Seleziona Titolare
              </label>
              <select
                value={selectedClientId}
                onChange={(e) => setSelectedClientId(e.target.value)}
                className="glass-input w-full"
              >
                <option value="">-- Seleziona un titolare --</option>
                {clients.map((client) => (
                  <option key={client.id} value={client.id}>
                    {client.ragione_sociale} {client.email ? `(${client.email})` : ''}
                  </option>
                ))}
              </select>
            </div>

            {/* Lista Ristoranti */}
            <div>
              <label className="block text-sm font-medium mb-3 flex items-center gap-2">
                <Store className="w-4 h-4 text-primary-400" />
                Seleziona Ristoranti ({selectedRestaurants.size}/{unassignedRestaurants.length})
              </label>
              
              <div className="space-y-2 max-h-96 overflow-y-auto">
                {unassignedRestaurants.map((restaurant) => (
                  <motion.div
                    key={restaurant.id}
                    whileHover={{ scale: 1.01 }}
                    className={`glass-card p-4 cursor-pointer transition-all ${
                      selectedRestaurants.has(restaurant.id)
                        ? 'bg-primary-500/20 border-primary-500'
                        : 'hover:bg-white/5'
                    }`}
                    onClick={() => toggleRestaurant(restaurant.id)}
                  >
                    <div className="flex items-start gap-3">
                      <div className={`w-5 h-5 rounded border-2 flex items-center justify-center mt-0.5 ${
                        selectedRestaurants.has(restaurant.id)
                          ? 'bg-primary-500 border-primary-500'
                          : 'border-gray-300'
                      }`}>
                        {selectedRestaurants.has(restaurant.id) && (
                          <Check className="w-3 h-3 text-white" />
                        )}
                      </div>
                      
                      <div className="flex-1">
                        <div className="flex items-start justify-between gap-2">
                          <div>
                            <h4 className="font-semibold text-white">{restaurant.nome}</h4>
                            {restaurant.indirizzo && (
                              <p className="text-sm text-gray-500">
                                {restaurant.indirizzo}
                                {restaurant.citta && `, ${restaurant.citta}`}
                              </p>
                            )}
                          </div>
                          <span className="text-xs text-gray-400 font-mono">
                            ID: {restaurant.oppla_external_id}
                          </span>
                        </div>
                        
                        {restaurant.partner && (
                          <div className="mt-2 pt-2 border-t border-gray-200">
                            <div className="flex items-center gap-2 text-sm">
                              <User className="w-3 h-3 text-purple-400" />
                              <span className="text-purple-400 font-medium">
                                {restaurant.partner.nome} {restaurant.partner.cognome}
                              </span>
                              <span className="text-gray-400">•</span>
                              <span className="text-gray-500">{restaurant.partner.email}</span>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                  </motion.div>
                ))}
              </div>
            </div>

            {/* Buttons */}
            <div className="flex gap-3 justify-end pt-4 border-t border-gray-200">
              <button
                type="button"
                onClick={onClose}
                className="glass-button"
                disabled={assigning}
              >
                Annulla
              </button>
              <button
                type="button"
                onClick={handleAssign}
                disabled={!selectedClientId || selectedRestaurants.size === 0 || assigning}
                className="glass-button-primary disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {assigning ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2 inline-block" />
                    Assegnazione...
                  </>
                ) : (
                  <>
                    <Check className="w-4 h-4 mr-2 inline" />
                    Assegna {selectedRestaurants.size} Ristorante{selectedRestaurants.size !== 1 ? 'i' : ''}
                  </>
                )}
              </button>
            </div>
          </>
        )}
      </div>
    </Modal>
  )
}
