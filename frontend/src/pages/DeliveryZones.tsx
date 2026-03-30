import React, { useState, useEffect } from 'react'
import { Truck, RefreshCw, MapPin, Clock, AlertCircle, CheckCircle2, Search, Filter, Map, List, Plus, Edit2, Trash2, X, Save, ArrowUpCircle } from 'lucide-react'
import api from '../utils/api'
import DeliveryZoneMap from '../components/DeliveryZoneMap'

interface DeliveryZone {
  id: number
  oppla_id?: string | null
  name: string
  city: string
  description?: string
  postal_codes?: string[]
  price_ranges?: Array<{
    from_km: number
    to_km: number
    price: number
  }>
  geometry?: any
  center_lat?: number
  center_lng?: number
  color?: string
  source?: string
  has_geometry?: boolean
  is_active?: boolean
  label?: string
}

interface DeliveryZonesResponse {
  success: boolean
  data: DeliveryZone[]
  last_sync?: string
  can_sync?: boolean
  message?: string
}

export default function DeliveryZones() {
  const [zones, setZones] = useState<DeliveryZone[]>([])
  const [loading, setLoading] = useState(true)
  const [syncing, setSyncing] = useState(false)
  const [lastSync, setLastSync] = useState<string | null>(null)
  const [canSync, setCanSync] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [filterCity, setFilterCity] = useState<string>('all')
  const [error, setError] = useState<string | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [viewMode, setViewMode] = useState<'list' | 'map'>('list')
  const [selectedZone, setSelectedZone] = useState<DeliveryZone | null>(null)
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [pendingGeometry, setPendingGeometry] = useState<any>(null)
  const [formData, setFormData] = useState({ name: '', city: '', description: '', color: '#3b82f6' })
  const [saving, setSaving] = useState(false)
  const [selectedZones, setSelectedZones] = useState<number[]>([])
  const [pushing, setPushing] = useState(false)

  useEffect(() => {
    loadDeliveryZones()
  }, [])

  const loadDeliveryZones = async () => {
    try {
      setLoading(true)
      setError(null)
      const response = await api.get<DeliveryZonesResponse>('/delivery-zones')
      
      if (response.data.success) {
        setZones(response.data.data)
        setLastSync(response.data.last_sync || null)
        setCanSync(response.data.can_sync !== false)
      }
    } catch (err: any) {
      console.error('Errore caricamento zone:', err)
      setError(err.response?.data?.message || 'Errore nel caricamento delle zone di consegna')
    } finally {
      setLoading(false)
    }
  }

  const handleSync = async () => {
    if (!canSync) {
      setError('Sincronizzazione disponibile tra 1 ora. Attendere per evitare sovraccarico del database.')
      return
    }

    try {
      setSyncing(true)
      setError(null)
      setSuccessMessage(null)

      const response = await api.post<DeliveryZonesResponse>('/delivery-zones/sync')

      if (response.data.success) {
        setSuccessMessage(response.data.message || 'Sincronizzazione completata con successo!')
        await loadDeliveryZones()
      }
    } catch (err: any) {
      console.error('Errore sincronizzazione:', err)
      setError(err.response?.data?.message || 'Errore durante la sincronizzazione')
    } finally {
      setSyncing(false)
    }
  }

  const handlePushToOppla = async () => {
    if (selectedZones.length === 0) {
      setError('Seleziona almeno una zona da sincronizzare verso OPPLA')
      return
    }

    if (!confirm(`Inviare ${selectedZones.length} zone verso OPPLA?`)) return

    try {
      setPushing(true)
      setError(null)
      setSuccessMessage(null)

      const response = await api.post('/delivery-zones/push-to-oppla', {
        zone_ids: selectedZones
      })

      if (response.data.success) {
        setSuccessMessage(response.data.message || 'Zone inviate con successo a OPPLA!')
        setSelectedZones([])
        await loadDeliveryZones()
      }
    } catch (err: any) {
      console.error('Errore push OPPLA:', err)
      setError(err.response?.data?.message || 'Errore durante l\'invio a OPPLA')
    } finally {
      setPushing(false)
    }
  }

  const toggleZoneSelection = (zoneId: number) => {
    setSelectedZones(prev =>
      prev.includes(zoneId)
        ? prev.filter(id => id !== zoneId)
        : [...prev, zoneId]
    )
  }

  // Filtra le zone
  const filteredZones = zones.filter(zone => {
    const matchesSearch = 
      zone.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      zone.city.toLowerCase().includes(searchTerm.toLowerCase()) ||
      zone.description?.toLowerCase().includes(searchTerm.toLowerCase())
    
    const matchesCity = filterCity === 'all' || zone.city === filterCity
    
    return matchesSearch && matchesCity
  })

  // Ottieni lista città uniche
  const cities = Array.from(new Set(zones.map(z => z.city))).sort()

  // Handle zone creation from map
  const handleZoneCreate = async (geometry: any) => {
    setPendingGeometry(geometry)
    setShowCreateModal(true)
  }

  // Save new zone
  const handleSaveNewZone = async () => {
    if (!formData.name || !formData.city) {
      setError('Nome e città sono obbligatori')
      return
    }

    try {
      setSaving(true)
      const response = await api.post('/delivery-zones', {
        name: formData.name,
        city: formData.city,
        description: formData.description,
        color: formData.color,
        geometry: pendingGeometry,
      })

      if (response.data.success) {
        setSuccessMessage('Zona creata con successo!')
        setShowCreateModal(false)
        setPendingGeometry(null)
        setFormData({ name: '', city: '', description: '', color: '#3b82f6' })
        await loadDeliveryZones()
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Errore nella creazione della zona')
    } finally {
      setSaving(false)
    }
  }

  // Update zone geometry
  const handleZoneUpdate = async (zoneId: number, geometry: any) => {
    try {
      const response = await api.put(`/delivery-zones/${zoneId}`, { geometry })
      if (response.data.success) {
        setSuccessMessage('Area della zona aggiornata!')
        await loadDeliveryZones()
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Errore nell\'aggiornamento della zona')
    }
  }

  // Delete zone
  const handleDeleteZone = async (zone: DeliveryZone) => {
    if (zone.oppla_id) {
      setError('Non è possibile eliminare zone sincronizzate da OPPLA')
      return
    }

    if (!confirm(`Sei sicuro di voler eliminare la zona "${zone.name}"?`)) return

    try {
      const response = await api.delete(`/delivery-zones/${zone.id}`)
      if (response.data.success) {
        setSuccessMessage('Zona eliminata con successo!')
        setSelectedZone(null)
        await loadDeliveryZones()
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Errore nell\'eliminazione della zona')
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
            <Truck className="w-8 h-8" />
            Zone di Consegna OPPLA
          </h1>
          <p className="text-gray-400 mt-1">
            Gestione zone di consegna sincronizzate dal database OPPLA
          </p>
        </div>

        <div className="flex flex-col gap-2 flex-shrink-0">
          <div className="flex flex-wrap items-center gap-2">
            {zones.filter(z => !z.oppla_id).length > 0 && (
              <button
                onClick={handlePushToOppla}
                disabled={pushing || selectedZones.length === 0}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                  !pushing && selectedZones.length > 0
                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                }`}
              >
                <ArrowUpCircle className={`w-4 h-4 ${pushing ? 'animate-spin' : ''}`} />
                {pushing ? 'Invio...' : `Invia a OPPLA (${selectedZones.length})`}
              </button>
            )}
            <button
              onClick={handleSync}
              disabled={!canSync || syncing}
              className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                canSync && !syncing
                  ? 'bg-primary-600 hover:bg-primary-700 text-white'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed'
              }`}
            >
              <RefreshCw className={`w-4 h-4 ${syncing ? 'animate-spin' : ''}`} />
              {syncing ? 'Sync...' : 'Sincronizza'}
            </button>
          </div>
          <div className="flex items-center gap-2 justify-end">
            <button
              onClick={() => {
                setFormData({ name: '', city: '', description: '', color: '#7c3aed' })
                setPendingGeometry(null)
                setShowCreateModal(true)
              }}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
            >
              <Plus className="w-4 h-4" />
              Nuova Zona
            </button>
          </div>
        </div>
      </div>

      {/* Status Bar */}
      <div className="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-6">
            <div className="flex items-center gap-2">
              <Clock className="w-5 h-5 text-gray-400" />
              <div>
                <p className="text-sm text-gray-400">Ultima sincronizzazione</p>
                <p className="text-gray-900 font-medium">{lastSync || 'Mai'}</p>
              </div>
            </div>
            
            <div className="flex items-center gap-2">
              <CheckCircle2 className="w-5 h-5 text-green-500" />
              <div>
                <p className="text-sm text-gray-400">Zone attive</p>
                <p className="text-gray-900 font-medium">{zones.filter(z => z.is_active).length}</p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <MapPin className="w-5 h-5 text-primary-600" />
              <div>
                <p className="text-sm text-gray-400">Città coperte</p>
                <p className="text-gray-900 font-medium">{cities.length}</p>
              </div>
            </div>
          </div>
          
          {!canSync && (
            <div className="flex items-center gap-2 text-amber-500">
              <AlertCircle className="w-5 h-5" />
              <span className="text-sm">Prossima sincronizzazione disponibile tra 1 ora</span>
            </div>
          )}
        </div>
      </div>

      {/* Messages */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
          <div className="flex-1">
            <p className="text-red-600 font-medium">Errore</p>
            <p className="text-red-500 text-sm mt-1">{error}</p>
          </div>
          <button
            onClick={() => setError(null)}
            className="text-red-500 hover:text-red-300"
          >
            ×
          </button>
        </div>
      )}

      {successMessage && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3">
          <CheckCircle2 className="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
          <div className="flex-1">
            <p className="text-green-600 font-medium">Successo</p>
            <p className="text-green-600 text-sm mt-1">{successMessage}</p>
          </div>
          <button
            onClick={() => setSuccessMessage(null)}
            className="text-green-600 hover:text-green-300"
          >
            ×
          </button>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
        <div className="flex gap-4 items-center">
          {/* View Mode Tabs */}
          <div className="flex bg-gray-100 rounded-lg p-1">
            <button
              onClick={() => setViewMode('list')}
              className={`flex items-center gap-2 px-4 py-2 rounded-md transition-colors ${
                viewMode === 'list' ? 'bg-primary-600 text-white' : 'text-gray-500 hover:text-gray-900'
              }`}
            >
              <List className="w-4 h-4" />
              Lista
            </button>
            <button
              onClick={() => setViewMode('map')}
              className={`flex items-center gap-2 px-4 py-2 rounded-md transition-colors ${
                viewMode === 'map' ? 'bg-primary-600 text-white' : 'text-gray-500 hover:text-gray-900'
              }`}
            >
              <Map className="w-4 h-4" />
              Mappa
            </button>
          </div>

          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              type="text"
              placeholder="Cerca per nome, città o descrizione..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
            />
          </div>
          
          <div className="relative">
            <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <select
              value={filterCity}
              onChange={(e) => setFilterCity(e.target.value)}
              className="pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 appearance-none cursor-pointer min-w-[200px]"
            >
              <option value="all">Tutte le città</option>
              {cities.map(city => (
                <option key={city} value={city}>{city}</option>
              ))}
            </select>
          </div>

          {/* Add Zone Button removed - moved to header */}
        </div>
      </div>

      {/* Map View */}
      {viewMode === 'map' && !loading && (
        <div className="space-y-4">
          <DeliveryZoneMap
            zones={filteredZones}
            selectedZoneId={selectedZone?.id}
            onZoneSelect={setSelectedZone}
            onZoneCreate={handleZoneCreate}
            onZoneUpdate={handleZoneUpdate}
            editable={true}
            height="600px"
          />

          {/* Selected Zone Details */}
          {selectedZone && (
            <div className="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-gray-900 font-semibold text-lg">{selectedZone.name}</h3>
                  <p className="text-gray-400 flex items-center gap-1 mt-1">
                    <MapPin className="w-4 h-4" />
                    {selectedZone.city}
                  </p>
                  {selectedZone.description && (
                    <p className="text-gray-500 text-sm mt-2">{selectedZone.description}</p>
                  )}
                  {selectedZone.source && (
                    <span className={`inline-block mt-2 text-xs px-2 py-1 rounded ${
                      selectedZone.source === 'oppla_sync' 
                        ? 'bg-primary-100 text-primary-700' 
                        : 'bg-emerald-100 text-emerald-700'
                    }`}>
                      {selectedZone.source === 'oppla_sync' ? 'Sincronizzata da OPPLA' : 'Creata manualmente'}
                    </span>
                  )}
                </div>
                <div className="flex gap-2">
                  {!selectedZone.oppla_id && (
                    <button
                      onClick={() => handleDeleteZone(selectedZone)}
                      className="p-2 text-red-500 hover:bg-red-500/20 rounded-lg transition-colors"
                      title="Elimina zona"
                    >
                      <Trash2 className="w-5 h-5" />
                    </button>
                  )}
                  <button
                    onClick={() => setSelectedZone(null)}
                    className="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* List View - Zones Grid */}
      {viewMode === 'list' && (loading ? (
        <div className="text-center py-12">
          <RefreshCw className="w-8 h-8 text-primary-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-400">Caricamento zone di consegna...</p>
        </div>
      ) : filteredZones.length === 0 ? (
        <div className="bg-white rounded-xl p-12 border border-gray-200 shadow-sm text-center">
          <Truck className="w-16 h-16 text-gray-600 mx-auto mb-4" />
          <p className="text-gray-400 text-lg mb-2">
            {searchTerm || filterCity !== 'all' 
              ? 'Nessuna zona trovata con i filtri selezionati'
              : 'Nessuna zona di consegna disponibile'
            }
          </p>
          {zones.length === 0 && (
            <button
              onClick={handleSync}
              disabled={!canSync || syncing}
              className="mt-4 bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400"
            >
              Sincronizza ora
            </button>
          )}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filteredZones.map((zone) => (
            <div
              key={zone.id}
              className="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-primary-500/50 transition-colors"
            >
              <div className="flex items-start justify-between mb-3">
                <div>
                  <h3 className="text-gray-900 font-semibold text-lg">{zone.name}</h3>
                  <div className="flex items-center gap-1 text-gray-400 text-sm mt-1">
                    <MapPin className="w-4 h-4" />
                    {zone.city}
                  </div>
                </div>
                {zone.oppla_id && (
                  <span className="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                    ID: {zone.oppla_id}
                  </span>
                )}
              </div>

              {zone.description && (
                <p className="text-gray-400 text-sm mb-3">{zone.description}</p>
              )}

              {zone.price_ranges && zone.price_ranges.length > 0 && (
                <div className="space-y-2">
                  <p className="text-sm font-medium text-gray-700">Fasce di prezzo</p>
                  {zone.price_ranges.map((range, idx) => (
                    <div key={idx} className="flex items-center justify-between text-sm">
                      <span className="text-gray-400">
                        {range.from_km} - {range.to_km} km
                      </span>
                      <span className="text-primary-600 font-medium">
                        €{range.price.toFixed(2)}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              {zone.postal_codes && zone.postal_codes.length > 0 && (
                <div className="mt-3 pt-3 border-t border-gray-200">
                  <p className="text-sm text-gray-400">
                    CAP: {zone.postal_codes.join(', ')}
                  </p>
                </div>
              )}

              {/* Zone source and geometry indicator */}
              <div className="mt-3 pt-3 border-t border-gray-200 flex items-center justify-between">
                <span className={`text-xs px-2 py-1 rounded ${
                  zone.source === 'oppla_sync' 
                    ? 'bg-primary-100 text-primary-700' 
                    : 'bg-emerald-100 text-emerald-700'
                }`}>
                  {zone.source === 'oppla_sync' ? 'OPPLA' : 'Manuale'}
                </span>
                {zone.has_geometry ? (
                  <span className="text-xs text-green-600 flex items-center gap-1">
                    <Map className="w-3 h-3" />
                    Area disegnata
                  </span>
                ) : (
                  <span className="text-xs text-amber-600 flex items-center gap-1">
                    <AlertCircle className="w-3 h-3" />
                    Senza area
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>
      ))}

      {/* Info Footer */}
      <div className="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
        <div className="flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" />
          <div className="text-sm text-gray-400">
            <p className="font-medium text-gray-900 mb-1">Zone di Consegna</p>
            <p>
              Le zone sincronizzate da OPPLA rappresentano le aree geografiche (es. "Livorno Centro", "Pisa Nord").
              Puoi creare nuove zone manualmente o disegnarle sulla mappa usando la vista Mappa.
            </p>
          </div>
        </div>
      </div>

      {/* Create Zone Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
          <div className="bg-white rounded-xl border border-gray-200 shadow-lg p-6 w-full max-w-md mx-4">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-bold text-gray-900">
                {pendingGeometry ? 'Salva Nuova Zona' : 'Crea Nuova Zona'}
              </h2>
              <button
                onClick={() => {
                  setShowCreateModal(false)
                  setPendingGeometry(null)
                }}
                className="text-gray-500 hover:text-gray-900"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Nome Zona *
                </label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="es. Livorno Centro"
                  className="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Città *
                </label>
                <input
                  type="text"
                  value={formData.city}
                  onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                  placeholder="es. Livorno"
                  className="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                  list="cities-list"
                />
                <datalist id="cities-list">
                  {cities.map(city => (
                    <option key={city} value={city} />
                  ))}
                </datalist>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Descrizione
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  placeholder="Descrizione opzionale..."
                  rows={3}
                  className="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 resize-none"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Colore
                </label>
                <div className="flex gap-2">
                  {['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'].map(color => (
                    <button
                      key={color}
                      onClick={() => setFormData({ ...formData, color })}
                      className={`w-8 h-8 rounded-full border-2 transition-all ${
                        formData.color === color ? 'border-white scale-110' : 'border-transparent'
                      }`}
                      style={{ backgroundColor: color }}
                    />
                  ))}
                </div>
              </div>

              {pendingGeometry && (
                <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                  <p className="text-sm text-emerald-400 flex items-center gap-2">
                    <Map className="w-4 h-4" />
                    Area disegnata sulla mappa
                  </p>
                </div>
              )}
            </div>

            <div className="flex gap-3 mt-6">
              <button
                onClick={() => {
                  setShowCreateModal(false)
                  setPendingGeometry(null)
                }}
                className="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors"
              >
                Annulla
              </button>
              <button
                onClick={handleSaveNewZone}
                disabled={saving || !formData.name || !formData.city}
                className="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400 flex items-center justify-center gap-2"
              >
                {saving ? (
                  <RefreshCw className="w-4 h-4 animate-spin" />
                ) : (
                  <Save className="w-4 h-4" />
                )}
                {saving ? 'Salvataggio...' : 'Salva Zona'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
