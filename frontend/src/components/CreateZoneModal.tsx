import { useState } from 'react'
import { X, Save, Loader2, MapPin } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'
import DeliveryZoneMap from './DeliveryZoneMap'
import axios from 'axios'

interface CreateZoneModalProps {
  isOpen: boolean
  onClose: () => void
  onZoneCreated: (zone: any) => void
  initialCity?: string
}

export default function CreateZoneModal({
  isOpen,
  onClose,
  onZoneCreated,
  initialCity = 'Livorno',
}: CreateZoneModalProps) {
  const [zoneName, setZoneName] = useState('')
  const [city, setCity] = useState(initialCity)
  const [geometry, setGeometry] = useState<any>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleGeometryCreated = (newGeometry: any) => {
    setGeometry(newGeometry)
    console.log('Geometry created:', newGeometry)
  }

  const handleSave = async () => {
    // Validation
    if (!zoneName.trim()) {
      setError('Il nome della zona è obbligatorio')
      return
    }

    if (!city.trim()) {
      setError('La città è obbligatoria')
      return
    }

    if (!geometry) {
      setError('Disegna l\'area della zona sulla mappa')
      return
    }

    setLoading(true)
    setError(null)

    try {
      const response = await axios.post('/api/delivery-zones', {
        name: zoneName,
        city: city,
        description: `Zona creata manualmente da onboarding`,
        geometry: geometry,
        color: '#3b82f6',
      })

      if (response.data.success) {
        onZoneCreated(response.data.data)
        handleClose()
      } else {
        setError(response.data.message || 'Errore durante la creazione della zona')
      }
    } catch (err: any) {
      console.error('Error creating zone:', err)
      setError(err.response?.data?.message || 'Errore durante la creazione della zona')
    } finally {
      setLoading(false)
    }
  }

  const handleClose = () => {
    setZoneName('')
    setCity(initialCity)
    setGeometry(null)
    setError(null)
    onClose()
  }

  if (!isOpen) return null

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0, scale: 0.95 }}
          className="bg-gray-900 rounded-xl border border-gray-200 shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto"
        >
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-primary-500/20 flex items-center justify-center">
                <MapPin className="w-5 h-5 text-primary-400" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-white">Crea Nuova Zona di Consegna</h2>
                <p className="text-sm text-gray-500">Definisci nome, città e area geografica</p>
              </div>
            </div>
            <button
              onClick={handleClose}
              className="p-2 hover:bg-gray-50 rounded-lg transition-colors"
            >
              <X className="w-5 h-5 text-gray-500" />
            </button>
          </div>

          {/* Body */}
          <div className="p-6 space-y-6">
            {/* Error message */}
            {error && (
              <div className="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
                {error}
              </div>
            )}

            {/* Form fields */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Nome Zona *
                </label>
                <input
                  type="text"
                  value={zoneName}
                  onChange={(e) => setZoneName(e.target.value)}
                  placeholder="es: Livorno Centro, Pisa Nord..."
                  className="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-primary-500 transition-colors"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Città *
                </label>
                <input
                  type="text"
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  placeholder="es: Livorno, Pisa..."
                  className="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-primary-500 transition-colors"
                />
              </div>
            </div>

            {/* Instructions */}
            <div className="p-4 bg-primary-500/10 border border-primary-500/20 rounded-lg">
              <div className="flex items-start gap-3">
                <MapPin className="w-5 h-5 text-primary-400 flex-shrink-0 mt-0.5" />
                <div className="text-sm text-primary-300">
                  <p className="font-medium mb-1">Come disegnare la zona:</p>
                  <ol className="list-decimal list-inside space-y-1 text-primary-400/80">
                    <li>Clicca su "Disegna zona" sulla mappa</li>
                    <li>Clicca sulla mappa per aggiungere i punti del poligono</li>
                    <li>Chiudi il poligono cliccando sul primo punto</li>
                    <li>La geometria verrà salvata automaticamente</li>
                  </ol>
                </div>
              </div>
            </div>

            {/* Map */}
            <div>
              <label className="block text-sm font-medium text-gray-600 mb-2">
                Area Geografica *
              </label>
              <DeliveryZoneMap
                zones={[]}
                editable={true}
                height="400px"
                onZoneCreate={handleGeometryCreated}
                initialCenter={[10.4017, 43.7228]} // Livorno default
                initialZoom={12}
              />
              {geometry && (
                <div className="mt-2 p-2 bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-400 text-sm">
                  Area disegnata correttamente
                </div>
              )}
            </div>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
            <button
              onClick={handleClose}
              disabled={loading}
              className="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-lg transition-colors disabled:opacity-50"
            >
              Annulla
            </button>
            <button
              onClick={handleSave}
              disabled={loading || !zoneName || !city || !geometry}
              className="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Salvataggio...
                </>
              ) : (
                <>
                  <Save className="w-4 h-4" />
                  Salva Zona
                </>
              )}
            </button>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  )
}
