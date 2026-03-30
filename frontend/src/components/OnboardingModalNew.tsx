import { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { 
  X, 
  ArrowRight, 
  ArrowLeft, 
  User, 
  Building2, 
  Upload,
  Check,
  Loader2,
  MapPin,
  Euro,
  FileText,
  Search,
  UserPlus,
  ChevronRight,
  Package,
  Truck,
  Plus
} from 'lucide-react'
import api from '../utils/api'
import CreateZoneModal from './CreateZoneModal'

// Types
interface Client {
  id: number
  ragione_sociale: string
  piva?: string
  email?: string
  indirizzo?: string
  citta?: string
  provincia?: string
  cap?: string
}

interface OwnerData {
  nome: string
  cognome: string
  email: string
  telefono: string
  ragione_sociale: string
  piva: string
  codice_fiscale: string
  indirizzo: string
  citta: string
  provincia: string
  cap: string
  nazione: string
  pec: string
  sdi_code: string
}

interface RestaurantData {
  nome: string
  category: string
  description: string
  telefono: string
  indirizzo: string
  citta: string
  provincia: string
  cap: string
  zone: string
  referent_nome: string
  referent_cognome: string
  referent_telefono: string
  referent_email: string
}

interface DeliveryZone {
  id: number
  name: string
  city: string
  description?: string
  postal_codes: string[]
  price_ranges: any[]
}

interface FeeClass {
  id: number
  name: string
  description: string
  delivery_type: 'autonomous' | 'managed'
  best_price: boolean
  monthly_fee: number
  order_fee_percentage: number
  order_fee_fixed: number
  delivery_base_fee: number
  delivery_km_fee: number
  payment_processing_fee: number
  platform_fee: number
}

interface CustomZone {
  zone_name: string
  price: string
}

type Step = 'owner' | 'restaurant' | 'cover' | 'delivery' | 'fees' | 'complete'

interface Props {
  isOpen: boolean
  onClose: () => void
  onComplete: () => void
}

export default function OnboardingModalNew({ isOpen, onClose, onComplete }: Props) {
  // Step management
  const [step, setStep] = useState<Step>('owner')
  const [sessionId, setSessionId] = useState<number | null>(null)
  const [clientId, setClientId] = useState<number | null>(null)
  const [restaurantId, setRestaurantId] = useState<number | null>(null)
  
  // Loading states
  const [loading, setLoading] = useState(false)
  const [loadingClients, setLoadingClients] = useState(false)
  const [loadingZones, setLoadingZones] = useState(false)
  
  // Error handling
  const [errors, setErrors] = useState<Record<string, string>>({})
  
  // Step 1: Owner mode
  const [ownerMode, setOwnerMode] = useState<'select' | 'create'>('select')
  const [clients, setClients] = useState<Client[]>([])
  const [selectedClient, setSelectedClient] = useState<Client | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [ownerData, setOwnerData] = useState<OwnerData>({
    nome: '',
    cognome: '',
    email: '',
    telefono: '',
    ragione_sociale: '',
    piva: '',
    codice_fiscale: '',
    indirizzo: '',
    citta: '',
    provincia: '',
    cap: '',
    nazione: 'IT',
    pec: '',
    sdi_code: ''
  })
  
  // Step 2: Restaurant
  const [restaurantData, setRestaurantData] = useState<RestaurantData>({
    nome: '',
    category: '',
    description: '',
    telefono: '',
    indirizzo: '',
    citta: '',
    provincia: '',
    cap: '',
    zone: '',
    referent_nome: '',
    referent_cognome: '',
    referent_telefono: '',
    referent_email: ''
  })
  
  // Step 3: Cover upload
  const [logoFile, setLogoFile] = useState<File | null>(null)
  const [fotoFile, setFotoFile] = useState<File | null>(null)
  const [logoPreview, setLogoPreview] = useState<string>('')
  const [fotoPreview, setFotoPreview] = useState<string>('')
  const [coverPreview, setCoverPreview] = useState<string>('')
  const [coverOpacity, setCoverOpacity] = useState(50)
  
  // Step 4: Delivery
  const [deliveryManagement, setDeliveryManagement] = useState<'autonomous' | 'managed'>('managed')
  const [deliveryZones, setDeliveryZones] = useState<DeliveryZone[]>([])
  const [selectedZones, setSelectedZones] = useState<number[]>([])
  const [customZones, setCustomZones] = useState<CustomZone[]>([{ zone_name: '', price: '' }])
  const [showCreateZoneModal, setShowCreateZoneModal] = useState(false)
  
  // Step 5: Fees
  const [bestPrice, setBestPrice] = useState<boolean>(true)
  const [selectedFeeClass, setSelectedFeeClass] = useState<FeeClass | null>(null)
  const [activationFee, setActivationFee] = useState<string>('150')
  
  // Load clients for selection
  useEffect(() => {
    if (isOpen && ownerMode === 'select') {
      loadClients()
    }
  }, [isOpen, ownerMode])
  
  // Load delivery zones
  useEffect(() => {
    if (step === 'delivery') {
      loadDeliveryZones()
    }
  }, [step])

  const loadClients = async () => {
    setLoadingClients(true)
    try {
      const response = await api.get('/clients')
      setClients(response.data.data || response.data)
    } catch (error) {
      console.error('Error loading clients:', error)
    } finally {
      setLoadingClients(false)
    }
  }

  const loadDeliveryZones = async () => {
    if (deliveryManagement !== 'managed') return

    setLoadingZones(true)
    try {
      const response = await api.get('/onboarding/delivery-zones')
      setDeliveryZones(response.data.data || [])
    } catch (error) {
      console.error('Error loading delivery zones:', error)
      setErrors({ zones: 'Errore nel caricamento delle zone. Sincronizzare con OPPLA?' })
    } finally {
      setLoadingZones(false)
    }
  }

  const handleZoneCreated = (newZone: any) => {
    // Add the new zone to the list
    setDeliveryZones(prev => [...prev, newZone])
    // Automatically select the newly created zone
    setSelectedZones(prev => [...prev, newZone.id])
    // Close the modal
    setShowCreateZoneModal(false)
  }

  const filteredClients = clients.filter(client => 
    client.ragione_sociale?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    client.piva?.includes(searchTerm) ||
    client.email?.toLowerCase().includes(searchTerm.toLowerCase())
  )

  // Step 1: Create owner
  const handleStep1Submit = async () => {
    setErrors({})
    
    if (ownerMode === 'select') {
      if (!selectedClient) {
        setErrors({ client: 'Seleziona un cliente esistente' })
        return
      }
      
      // Create session for existing client
      setLoading(true)
      try {
        const response = await api.post('/onboarding/step-1-owner', {
          existing_client_id: selectedClient.id
        })
        const { client_id, session_id } = response.data.data
        setClientId(client_id)
        setSessionId(session_id)
        setStep('restaurant')
      } catch (error: any) {
        setErrors({ api: error.response?.data?.message || 'Errore durante la creazione della sessione' })
      } finally {
        setLoading(false)
      }
      return
    }
    
    // Validate create mode
    if (!ownerData.nome || !ownerData.cognome || !ownerData.email || !ownerData.telefono) {
      setErrors({ form: 'Compila tutti i campi obbligatori: nome, cognome, email, telefono' })
      return
    }
    if (!ownerData.ragione_sociale || !ownerData.piva) {
      setErrors({ form: 'Ragione sociale e P.IVA sono obbligatori' })
      return
    }
    if (!ownerData.indirizzo || !ownerData.citta || !ownerData.provincia || !ownerData.cap) {
      setErrors({ form: 'Indirizzo completo obbligatorio (via, città, provincia, CAP)' })
      return
    }
    
    setLoading(true)
    try {
      const response = await api.post('/onboarding/step-1-owner', ownerData)
      const { client_id, session_id } = response.data.data
      setClientId(client_id)
      setSessionId(session_id)
      setStep('restaurant')
    } catch (error: any) {
      setErrors({ api: error.response?.data?.message || 'Errore durante la creazione del titolare' })
    } finally {
      setLoading(false)
    }
  }

  // Step 2: Create restaurant
  const handleStep2Submit = async () => {
    setErrors({})
    
    // Validate
    const required = ['nome', 'category', 'telefono', 'indirizzo', 'citta', 'provincia', 'cap', 'zone',
                      'referent_nome', 'referent_cognome', 'referent_telefono', 'referent_email']
    
    for (const field of required) {
      if (!restaurantData[field as keyof RestaurantData]) {
        setErrors({ form: `Campo obbligatorio mancante: ${field}` })
        return
      }
    }
    
    setLoading(true)
    try {
      const payload = {
        session_id: sessionId,
        ...restaurantData
      }
      const response = await api.post('/onboarding/step-2-restaurant', payload)
      const { restaurant_id } = response.data.data
      setRestaurantId(restaurant_id)
      setStep('cover')
    } catch (error: any) {
      setErrors({ api: error.response?.data?.message || 'Errore durante la creazione del ristorante' })
    } finally {
      setLoading(false)
    }
  }

  // Step 3: Upload cover
  const handleStep3Submit = async () => {
    setErrors({})
    
    if (!logoFile || !fotoFile) {
      setErrors({ form: 'Logo e foto sono obbligatori' })
      return
    }
    
    setLoading(true)
    try {
      const formData = new FormData()
      formData.append('session_id', String(sessionId))
      formData.append('restaurant_id', String(restaurantId))
      formData.append('logo', logoFile)
      formData.append('foto', fotoFile)
      formData.append('cover_opacity', String(coverOpacity))
      
      const response = await api.post('/onboarding/step-3-cover', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      
      const { cover_url } = response.data.data
      setCoverPreview(cover_url)
      setStep('delivery')
    } catch (error: any) {
      setErrors({ api: error.response?.data?.message || 'Errore durante l\'upload delle immagini' })
    } finally {
      setLoading(false)
    }
  }

  // Step 4: Configure delivery
  const handleStep4Submit = async () => {
    setErrors({})
    
    let payload: any = {
      session_id: sessionId,
      restaurant_id: restaurantId,
      delivery_management: deliveryManagement
    }
    
    if (deliveryManagement === 'managed') {
      if (selectedZones.length === 0) {
        setErrors({ form: 'Seleziona almeno una zona di consegna' })
        return
      }
      payload.delivery_zones = selectedZones
    } else {
      // Autonomous
      const validZones = customZones.filter(z => z.zone_name && z.price)
      if (validZones.length === 0) {
        setErrors({ form: 'Aggiungi almeno una zona custom con nome e prezzo' })
        return
      }
      payload.autonomous_zones = validZones
    }
    
    setLoading(true)
    try {
      await api.post('/onboarding/step-4-delivery', payload)
      setStep('fees')
    } catch (error: any) {
      setErrors({ api: error.response?.data?.message || 'Errore nella configurazione delle consegne' })
    } finally {
      setLoading(false)
    }
  }

  // Step 5: Configure fees
  const handleStep5Submit = async () => {
    setErrors({})
    
    setLoading(true)
    try {
      const response = await api.post('/onboarding/step-5-fees', {
        session_id: sessionId,
        restaurant_id: restaurantId,
        best_price: bestPrice,
        activation_fee: parseFloat(activationFee) || 150
      })
      
      const { fee_class } = response.data.data
      setSelectedFeeClass(fee_class)
      setStep('complete')
    } catch (error: any) {
      setErrors({ api: error.response?.data?.message || 'Errore nella configurazione delle fee' })
    } finally {
      setLoading(false)
    }
  }

  // Step 6: Finalize
  const handleFinalize = async () => {
    setLoading(true)
    try {
      await api.post('/onboarding/finalize', {
        session_id: sessionId
      })
      
      // Wait a moment before closing
      setTimeout(() => {
        onComplete()
        resetModal()
      }, 2000)
    } catch (error: any) {
      setErrors({ api: error.response?.data?.message || 'Errore nella finalizzazione' })
      setLoading(false)
    }
  }

  const resetModal = () => {
    setStep('owner')
    setSessionId(null)
    setClientId(null)
    setRestaurantId(null)
    setOwnerMode('select')
    setSelectedClient(null)
    setErrors({})
    setLoading(false)
    setBestPrice(true)
    setSelectedFeeClass(null)
    setActivationFee('150')
  }

  const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setLogoFile(file)
      const reader = new FileReader()
      reader.onloadend = () => setLogoPreview(reader.result as string)
      reader.readAsDataURL(file)
    }
  }

  const handleFotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setFotoFile(file)
      const reader = new FileReader()
      reader.onloadend = () => setFotoPreview(reader.result as string)
      reader.readAsDataURL(file)
    }
  }

  const addCustomZone = () => {
    setCustomZones([...customZones, { zone_name: '', price: '' }])
  }

  const removeCustomZone = (index: number) => {
    setCustomZones(customZones.filter((_, i) => i !== index))
  }

  const updateCustomZone = (index: number, field: 'zone_name' | 'price', value: string) => {
    const updated = [...customZones]
    updated[index][field] = value
    setCustomZones(updated)
  }

  const steps: Step[] = ['owner', 'restaurant', 'cover', 'delivery', 'fees', 'complete']
  const currentStepIndex = steps.indexOf(step)
  const progress = Math.round((currentStepIndex / (steps.length - 1)) * 100)

  if (!isOpen) return null

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm"
        >
          <motion.div
            initial={{ scale: 0.95, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={{ scale: 0.95, opacity: 0 }}
            className="w-full h-full bg-gray-900 flex flex-col overflow-hidden"
          >
            {/* Header */}
            <div className="flex-shrink-0 glass-card border-b border-gray-200">
              <div className="max-w-7xl mx-auto px-6 py-4">
                <div className="flex items-center justify-between mb-3">
                  <h2 className="text-2xl font-bold text-gradient">
                    Onboarding Nuovo Cliente
                  </h2>
                  {step !== 'complete' && (
                    <button
                      onClick={onClose}
                      className="glass-button p-2 hover:bg-red-500/20"
                    >
                      <X className="w-5 h-5" />
                    </button>
                  )}
                </div>
                
                {/* Progress bar */}
                {step !== 'complete' && (
                  <div className="space-y-2">
                    <div className="flex items-center gap-2 text-xs text-gray-500">
                      <span className={step === 'owner' ? 'text-primary-400 font-semibold' : ''}>
                        1. Titolare
                      </span>
                      <ChevronRight className="w-3 h-3" />
                      <span className={step === 'restaurant' ? 'text-primary-400 font-semibold' : ''}>
                        2. Ristorante
                      </span>
                      <ChevronRight className="w-3 h-3" />
                      <span className={step === 'cover' ? 'text-primary-400 font-semibold' : ''}>
                        3. Immagini
                      </span>
                      <ChevronRight className="w-3 h-3" />
                      <span className={step === 'delivery' ? 'text-primary-400 font-semibold' : ''}>
                        4. Consegne
                      </span>
                      <ChevronRight className="w-3 h-3" />
                      <span className={step === 'fees' ? 'text-primary-400 font-semibold' : ''}>
                        5. Tariffe
                      </span>
                    </div>
                    <div className="h-2 bg-gray-50 rounded-full overflow-hidden">
                      <motion.div
                        initial={{ width: 0 }}
                        animate={{ width: `${progress}%` }}
                        className="h-full bg-gradient-to-r from-primary-500 to-emerald-500"
                      />
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto">
              <div className="max-w-4xl mx-auto px-6 py-8">
                {/* Error display */}
                {Object.keys(errors).length > 0 && (
                  <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-lg"
                  >
                    <p className="text-red-400 text-sm">
                      {Object.values(errors)[0]}
                    </p>
                  </motion.div>
                )}

                {/* Step 1: Owner */}
                {step === 'owner' && (
                  <motion.div
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    className="space-y-6"
                  >
                    {/* Mode toggle */}
                    <div className="flex gap-2 p-1 bg-gray-50/50 rounded-lg">
                      <button
                        onClick={() => {
                          setOwnerMode('select')
                          setSelectedClient(null)
                        }}
                        className={`flex-1 px-4 py-3 rounded-md transition-all ${
                          ownerMode === 'select'
                            ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30'
                            : 'text-gray-500 hover:text-white'
                        }`}
                      >
                        <User className="w-4 h-4 inline mr-2" />
                        Seleziona Esistente
                      </button>
                      <button
                        onClick={() => {
                          setOwnerMode('create')
                          setSelectedClient(null)
                        }}
                        className={`flex-1 px-4 py-3 rounded-md transition-all ${
                          ownerMode === 'create'
                            ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30'
                            : 'text-gray-500 hover:text-white'
                        }`}
                      >
                        <UserPlus className="w-4 h-4 inline mr-2" />
                        Crea Nuovo
                      </button>
                    </div>

                    {ownerMode === 'select' ? (
                      /* Select existing client */
                      <div className="glass-card p-6">
                        <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
                          <User className="w-5 h-5 text-primary-400" />
                          Seleziona Titolare
                        </h3>

                        <div className="mb-4">
                          <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                            <input
                              type="text"
                              value={searchTerm}
                              onChange={(e) => setSearchTerm(e.target.value)}
                              placeholder="Cerca per ragione sociale, P.IVA o email..."
                              className="glass-input w-full pl-10"
                            />
                          </div>
                        </div>

                        {loadingClients ? (
                          <div className="flex items-center justify-center py-12">
                            <Loader2 className="w-8 h-8 text-primary-400 animate-spin" />
                          </div>
                        ) : filteredClients.length === 0 ? (
                          <div className="text-center py-12 text-gray-500">
                            <User className="w-12 h-12 mx-auto mb-3 opacity-50" />
                            <p>Nessun cliente trovato</p>
                          </div>
                        ) : (
                          <div className="space-y-2 max-h-96 overflow-y-auto">
                            {filteredClients.map((client) => (
                              <button
                                key={client.id}
                                onClick={() => setSelectedClient(client)}
                                className={`w-full text-left p-4 rounded-lg border-2 transition-all ${
                                  selectedClient?.id === client.id
                                    ? 'border-primary-500 bg-primary-500/10'
                                    : 'border-gray-200 bg-gray-50/30 hover:border-gray-300'
                                }`}
                              >
                                <div className="flex items-start justify-between">
                                  <div className="flex-1">
                                    <div className="font-semibold text-white">
                                      {client.ragione_sociale}
                                    </div>
                                    <div className="text-sm text-gray-500 mt-1 space-y-1">
                                      {client.piva && <div>P.IVA: {client.piva}</div>}
                                      {client.email && <div>{client.email}</div>}
                                    </div>
                                  </div>
                                  {selectedClient?.id === client.id && (
                                    <Check className="w-5 h-5 text-primary-400" />
                                  )}
                                </div>
                              </button>
                            ))}
                          </div>
                        )}
                      </div>
                    ) : (
                      /* Create new owner */
                      <div className="glass-card p-6">
                        <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
                          <UserPlus className="w-5 h-5 text-emerald-400" />
                          Dati Titolare
                        </h3>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-medium mb-2">Nome *</label>
                            <input
                              type="text"
                              value={ownerData.nome}
                              onChange={(e) => setOwnerData({ ...ownerData, nome: e.target.value })}
                              className="glass-input w-full"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Cognome *</label>
                            <input
                              type="text"
                              value={ownerData.cognome}
                              onChange={(e) => setOwnerData({ ...ownerData, cognome: e.target.value })}
                              className="glass-input w-full"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Email *</label>
                            <input
                              type="email"
                              value={ownerData.email}
                              onChange={(e) => setOwnerData({ ...ownerData, email: e.target.value })}
                              className="glass-input w-full"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Telefono *</label>
                            <input
                              type="tel"
                              value={ownerData.telefono}
                              onChange={(e) => setOwnerData({ ...ownerData, telefono: e.target.value })}
                              className="glass-input w-full"
                            />
                          </div>
                          <div className="md:col-span-2">
                            <label className="block text-sm font-medium mb-2">Ragione Sociale *</label>
                            <input
                              type="text"
                              value={ownerData.ragione_sociale}
                              onChange={(e) => setOwnerData({ ...ownerData, ragione_sociale: e.target.value })}
                              className="glass-input w-full"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">P.IVA *</label>
                            <input
                              type="text"
                              value={ownerData.piva}
                              onChange={(e) => setOwnerData({ ...ownerData, piva: e.target.value })}
                              className="glass-input w-full"
                              placeholder="IT12345678901"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Codice Fiscale</label>
                            <input
                              type="text"
                              value={ownerData.codice_fiscale}
                              onChange={(e) => setOwnerData({ ...ownerData, codice_fiscale: e.target.value })}
                              className="glass-input w-full"
                            />
                          </div>
                          <div className="md:col-span-2">
                            <label className="block text-sm font-medium mb-2">Indirizzo Fatturazione *</label>
                            <input
                              type="text"
                              value={ownerData.indirizzo}
                              onChange={(e) => setOwnerData({ ...ownerData, indirizzo: e.target.value })}
                              className="glass-input w-full"
                              placeholder="Via, Numero Civico"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Città *</label>
                            <input
                              type="text"
                              value={ownerData.citta}
                              onChange={(e) => setOwnerData({ ...ownerData, citta: e.target.value })}
                              className="glass-input w-full"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Provincia *</label>
                            <input
                              type="text"
                              value={ownerData.provincia}
                              onChange={(e) => setOwnerData({ ...ownerData, provincia: e.target.value.toUpperCase() })}
                              className="glass-input w-full"
                              placeholder="LI"
                              maxLength={2}
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">CAP *</label>
                            <input
                              type="text"
                              value={ownerData.cap}
                              onChange={(e) => setOwnerData({ ...ownerData, cap: e.target.value })}
                              className="glass-input w-full"
                              placeholder="57100"
                              maxLength={5}
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Nazione</label>
                            <input
                              type="text"
                              value={ownerData.nazione}
                              onChange={(e) => setOwnerData({ ...ownerData, nazione: e.target.value.toUpperCase() })}
                              className="glass-input w-full"
                              placeholder="IT"
                              maxLength={2}
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">PEC</label>
                            <input
                              type="email"
                              value={ownerData.pec}
                              onChange={(e) => setOwnerData({ ...ownerData, pec: e.target.value })}
                              className="glass-input w-full"
                              placeholder="azienda@pec.it"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium mb-2">Codice SDI</label>
                            <input
                              type="text"
                              value={ownerData.sdi_code}
                              onChange={(e) => setOwnerData({ ...ownerData, sdi_code: e.target.value.toUpperCase() })}
                              className="glass-input w-full"
                              placeholder="ABCDEFG"
                              maxLength={7}
                            />
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Actions */}
                    <div className="flex justify-end">
                      <motion.button
                        onClick={handleStep1Submit}
                        disabled={loading || (ownerMode === 'select' && !selectedClient)}
                        whileHover={{ scale: loading ? 1 : 1.02 }}
                        whileTap={{ scale: loading ? 1 : 0.98 }}
                        className="glass-button-primary flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {loading ? (
                          <>
                            <Loader2 className="w-5 h-5 animate-spin" />
                            Caricamento...
                          </>
                        ) : (
                          <>
                            Avanti
                            <ArrowRight className="w-5 h-5" />
                          </>
                        )}
                      </motion.button>
                    </div>
                  </motion.div>
                )}

                {/* Step 2: Restaurant - Will continue in next part */}
                {step === 'restaurant' && (
                  <motion.div
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    className="space-y-6"
                  >
                    <div className="glass-card p-6">
                      <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
                        <Building2 className="w-5 h-5 text-primary-400" />
                        Dati Ristorante
                      </h3>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="md:col-span-2">
                          <label className="block text-sm font-medium mb-2">Nome Ristorante *</label>
                          <input
                            type="text"
                            value={restaurantData.nome}
                            onChange={(e) => setRestaurantData({ ...restaurantData, nome: e.target.value })}
                            className="glass-input w-full"
                            placeholder="Pizzeria Da Mario"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Categoria *</label>
                          <select
                            value={restaurantData.category}
                            onChange={(e) => setRestaurantData({ ...restaurantData, category: e.target.value })}
                            className="glass-input w-full"
                          >
                            <option value="">Seleziona...</option>
                            <option value="Pizzeria">Pizzeria</option>
                            <option value="Ristorante">Ristorante</option>
                            <option value="Trattoria">Trattoria</option>
                            <option value="Fast Food">Fast Food</option>
                            <option value="Kebab">Kebab</option>
                            <option value="Sushi">Sushi</option>
                            <option value="Gelateria">Gelateria</option>
                            <option value="Bar">Bar</option>
                            <option value="Altro">Altro</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Telefono *</label>
                          <input
                            type="tel"
                            value={restaurantData.telefono}
                            onChange={(e) => setRestaurantData({ ...restaurantData, telefono: e.target.value })}
                            className="glass-input w-full"
                            placeholder="+39 0586 123456"
                          />
                        </div>
                        <div className="md:col-span-2">
                          <label className="block text-sm font-medium mb-2">Descrizione</label>
                          <textarea
                            value={restaurantData.description}
                            onChange={(e) => setRestaurantData({ ...restaurantData, description: e.target.value })}
                            className="glass-input w-full"
                            rows={3}
                            placeholder="Breve descrizione del locale..."
                          />
                        </div>
                        <div className="md:col-span-2">
                          <label className="block text-sm font-medium mb-2">Indirizzo *</label>
                          <input
                            type="text"
                            value={restaurantData.indirizzo}
                            onChange={(e) => setRestaurantData({ ...restaurantData, indirizzo: e.target.value })}
                            className="glass-input w-full"
                            placeholder="Via Garibaldi 10"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Città *</label>
                          <input
                            type="text"
                            value={restaurantData.citta}
                            onChange={(e) => setRestaurantData({ ...restaurantData, citta: e.target.value })}
                            className="glass-input w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Provincia *</label>
                          <input
                            type="text"
                            value={restaurantData.provincia}
                            onChange={(e) => setRestaurantData({ ...restaurantData, provincia: e.target.value.toUpperCase() })}
                            className="glass-input w-full"
                            placeholder="LI"
                            maxLength={2}
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">CAP *</label>
                          <input
                            type="text"
                            value={restaurantData.cap}
                            onChange={(e) => setRestaurantData({ ...restaurantData, cap: e.target.value })}
                            className="glass-input w-full"
                            placeholder="57100"
                            maxLength={5}
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Zona *</label>
                          <input
                            type="text"
                            value={restaurantData.zone}
                            onChange={(e) => setRestaurantData({ ...restaurantData, zone: e.target.value })}
                            className="glass-input w-full"
                            placeholder="Centro, Periferia Nord..."
                          />
                        </div>
                      </div>
                    </div>

                    {/* Referent section */}
                    <div className="glass-card p-6 bg-gray-50/50">
                      <h4 className="text-lg font-semibold mb-4 flex items-center gap-2">
                        <User className="w-5 h-5 text-emerald-400" />
                        Referente (Account Partner)
                      </h4>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label className="block text-sm font-medium mb-2">Nome *</label>
                          <input
                            type="text"
                            value={restaurantData.referent_nome}
                            onChange={(e) => setRestaurantData({ ...restaurantData, referent_nome: e.target.value })}
                            className="glass-input w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Cognome *</label>
                          <input
                            type="text"
                            value={restaurantData.referent_cognome}
                            onChange={(e) => setRestaurantData({ ...restaurantData, referent_cognome: e.target.value })}
                            className="glass-input w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Email *</label>
                          <input
                            type="email"
                            value={restaurantData.referent_email}
                            onChange={(e) => setRestaurantData({ ...restaurantData, referent_email: e.target.value })}
                            className="glass-input w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium mb-2">Telefono *</label>
                          <input
                            type="tel"
                            value={restaurantData.referent_telefono}
                            onChange={(e) => setRestaurantData({ ...restaurantData, referent_telefono: e.target.value })}
                            className="glass-input w-full"
                          />
                        </div>
                      </div>
                    </div>

                    {/* Actions */}
                    <div className="flex justify-between">
                      <motion.button
                        onClick={() => setStep('owner')}
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.98 }}
                        className="glass-button flex items-center gap-2"
                      >
                        <ArrowLeft className="w-5 h-5" />
                        Indietro
                      </motion.button>
                      <motion.button
                        onClick={handleStep2Submit}
                        disabled={loading}
                        whileHover={{ scale: loading ? 1 : 1.02 }}
                        whileTap={{ scale: loading ? 1 : 0.98 }}
                        className="glass-button-primary flex items-center gap-2 disabled:opacity-50"
                      >
                        {loading ? (
                          <>
                            <Loader2 className="w-5 h-5 animate-spin" />
                            Salvataggio...
                          </>
                        ) : (
                          <>
                            Avanti
                            <ArrowRight className="w-5 h-5" />
                          </>
                        )}
                      </motion.button>
                    </div>
                  </motion.div>
                )}

                {/* Step 3: Cover Upload */}
                {step === 'cover' && (
                  <motion.div
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    className="space-y-6"
                  >
                    <div className="glass-card p-6">
                      <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
                        <Upload className="w-5 h-5 text-primary-400" />
                        Immagini Ristorante
                      </h3>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Logo Upload */}
                        <div>
                          <label className="block text-sm font-medium mb-2">Logo *</label>
                          <div className="relative">
                            <input
                              type="file"
                              accept="image/*"
                              onChange={handleLogoChange}
                              className="hidden"
                              id="logo-upload"
                            />
                            <label
                              htmlFor="logo-upload"
                              className="glass-card p-4 border-2 border-dashed border-gray-300 hover:border-primary-500 cursor-pointer transition-colors flex flex-col items-center justify-center h-48"
                            >
                              {logoPreview ? (
                                <img src={logoPreview} alt="Logo preview" className="max-h-full max-w-full object-contain" />
                              ) : (
                                <>
                                  <Upload className="w-8 h-8 text-gray-500 mb-2" />
                                  <span className="text-sm text-gray-500">Clicca per caricare</span>
                                  <span className="text-xs text-gray-400 mt-1">PNG, JPG max 5MB</span>
                                </>
                              )}
                            </label>
                          </div>
                        </div>

                        {/* Foto Upload */}
                        <div>
                          <label className="block text-sm font-medium mb-2">Foto Locale *</label>
                          <div className="relative">
                            <input
                              type="file"
                              accept="image/*"
                              onChange={handleFotoChange}
                              className="hidden"
                              id="foto-upload"
                            />
                            <label
                              htmlFor="foto-upload"
                              className="glass-card p-4 border-2 border-dashed border-gray-300 hover:border-primary-500 cursor-pointer transition-colors flex flex-col items-center justify-center h-48"
                            >
                              {fotoPreview ? (
                                <img src={fotoPreview} alt="Foto preview" className="max-h-full max-w-full object-contain" />
                              ) : (
                                <>
                                  <Upload className="w-8 h-8 text-gray-500 mb-2" />
                                  <span className="text-sm text-gray-500">Clicca per caricare</span>
                                  <span className="text-xs text-gray-400 mt-1">PNG, JPG max 5MB</span>
                                </>
                              )}
                            </label>
                          </div>
                        </div>
                      </div>

                      {/* Cover Opacity Slider */}
                      <div className="mt-6">
                        <label className="block text-sm font-medium mb-2">
                          Opacità Cover: {coverOpacity}%
                        </label>
                        <input
                          type="range"
                          min="0"
                          max="100"
                          value={coverOpacity}
                          onChange={(e) => setCoverOpacity(Number(e.target.value))}
                          className="w-full"
                        />
                        <p className="text-xs text-gray-500 mt-1">
                          La cover verrà generata automaticamente dalla foto caricata
                        </p>
                      </div>
                    </div>

                    {/* Actions */}
                    <div className="flex justify-between">
                      <motion.button
                        onClick={() => setStep('restaurant')}
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.98 }}
                        className="glass-button flex items-center gap-2"
                      >
                        <ArrowLeft className="w-5 h-5" />
                        Indietro
                      </motion.button>
                      <motion.button
                        onClick={handleStep3Submit}
                        disabled={loading || !logoFile || !fotoFile}
                        whileHover={{ scale: loading ? 1 : 1.02 }}
                        whileTap={{ scale: loading ? 1 : 0.98 }}
                        className="glass-button-primary flex items-center gap-2 disabled:opacity-50"
                      >
                        {loading ? (
                          <>
                            <Loader2 className="w-5 h-5 animate-spin" />
                            Caricamento...
                          </>
                        ) : (
                          <>
                            Avanti
                            <ArrowRight className="w-5 h-5" />
                          </>
                        )}
                      </motion.button>
                    </div>
                  </motion.div>
                )}

                {/* Step 4: Delivery Configuration */}
                {step === 'delivery' && (
                  <motion.div
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    className="space-y-6"
                  >
                    <div className="glass-card p-6">
                      <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
                        <Truck className="w-5 h-5 text-primary-400" />
                        Gestione Consegne
                      </h3>

                      {/* Delivery Type Toggle */}
                      <div className="grid grid-cols-2 gap-4 mb-6">
                        <button
                          onClick={() => setDeliveryManagement('managed')}
                          className={`p-4 rounded-lg border-2 transition-all ${
                            deliveryManagement === 'managed'
                              ? 'border-primary-500 bg-primary-500/10'
                              : 'border-gray-200 bg-gray-50/30 hover:border-gray-300'
                          }`}
                        >
                          <Package className="w-8 h-8 mx-auto mb-2 text-primary-400" />
                          <div className="font-semibold">Gestita FLA</div>
                          <div className="text-xs text-gray-500 mt-1">Rider OPPLA</div>
                        </button>
                        <button
                          onClick={() => setDeliveryManagement('autonomous')}
                          className={`p-4 rounded-lg border-2 transition-all ${
                            deliveryManagement === 'autonomous'
                              ? 'border-emerald-500 bg-emerald-500/10'
                              : 'border-gray-200 bg-gray-50/30 hover:border-gray-300'
                          }`}
                        >
                          <Truck className="w-8 h-8 mx-auto mb-2 text-emerald-400" />
                          <div className="font-semibold">Autonoma</div>
                          <div className="text-xs text-gray-500 mt-1">Rider propri</div>
                        </button>
                      </div>

                      {/* Managed Delivery - Zone Selection */}
                      {deliveryManagement === 'managed' && (
                        <div>
                          <div className="flex items-center justify-between mb-2">
                            <label className="block text-sm font-medium">
                              Zone di Consegna FLA *
                            </label>
                            <button
                              onClick={() => setShowCreateZoneModal(true)}
                              className="flex items-center gap-1 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg transition-colors"
                            >
                              <Plus className="w-4 h-4" />
                              Crea Nuova Zona
                            </button>
                          </div>
                          {loadingZones ? (
                            <div className="flex items-center justify-center p-8">
                              <Loader2 className="w-6 h-6 text-primary-400 animate-spin" />
                            </div>
                          ) : deliveryZones.length === 0 ? (
                            <div className="glass-card p-6 text-center text-gray-500">
                              <MapPin className="w-12 h-12 mx-auto mb-3 opacity-50" />
                              <p className="mb-2">Nessuna zona disponibile</p>
                              <p className="text-xs mb-4">Crea la prima zona di consegna o sincronizza con OPPLA</p>
                              <div className="flex gap-2 justify-center">
                                <button
                                  onClick={() => setShowCreateZoneModal(true)}
                                  className="glass-button-primary text-sm flex items-center gap-2"
                                >
                                  <Plus className="w-4 h-4" />
                                  Crea Zona
                                </button>
                                <button
                                  onClick={loadDeliveryZones}
                                  className="glass-button text-sm"
                                >
                                  Ricarica Zone
                                </button>
                              </div>
                            </div>
                          ) : (
                            <div className="space-y-2">
                              <select
                                multiple
                                value={selectedZones.map(String)}
                                onChange={(e) => {
                                  const selected = Array.from(e.target.selectedOptions, opt => Number(opt.value))
                                  setSelectedZones(selected)
                                }}
                                className="glass-input w-full min-h-[200px]"
                                size={8}
                              >
                                {deliveryZones.map((zone) => (
                                  <option key={zone.id} value={zone.id}>
                                    {zone.name} - {zone.city}
                                  </option>
                                ))}
                              </select>
                              <p className="text-xs text-gray-500">
                                Tieni premuto Ctrl/Cmd per selezionare più zone
                              </p>
                              {selectedZones.length > 0 && (
                                <div className="flex flex-wrap gap-2 mt-2">
                                  {selectedZones.map((zoneId) => {
                                    const zone = deliveryZones.find(z => z.id === zoneId)
                                    return zone ? (
                                      <span key={zoneId} className="glass-badge bg-primary-500/20 text-primary-400 text-xs">
                                        {zone.name}
                                      </span>
                                    ) : null
                                  })}
                                </div>
                              )}
                            </div>
                          )}
                        </div>
                      )}

                      {/* Autonomous Delivery - Custom Zones */}
                      {deliveryManagement === 'autonomous' && (
                        <div>
                          <label className="block text-sm font-medium mb-2">
                            Zone Custom *
                          </label>
                          <div className="space-y-3">
                            {customZones.map((zone, index) => (
                              <div key={index} className="flex gap-2">
                                <input
                                  type="text"
                                  value={zone.zone_name}
                                  onChange={(e) => updateCustomZone(index, 'zone_name', e.target.value)}
                                  placeholder="Nome zona (es: Centro)"
                                  className="glass-input flex-1"
                                />
                                <div className="relative w-32">
                                  <Euro className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                                  <input
                                    type="number"
                                    step="0.01"
                                    value={zone.price}
                                    onChange={(e) => updateCustomZone(index, 'price', e.target.value)}
                                    placeholder="Prezzo"
                                    className="glass-input w-full pl-9"
                                  />
                                </div>
                                {customZones.length > 1 && (
                                  <button
                                    onClick={() => removeCustomZone(index)}
                                    className="glass-button p-2 hover:bg-red-500/20"
                                  >
                                    <X className="w-4 h-4" />
                                  </button>
                                )}
                              </div>
                            ))}
                            <button
                              onClick={addCustomZone}
                              className="glass-button text-sm w-full"
                            >
                              + Aggiungi Zona
                            </button>
                          </div>
                        </div>
                      )}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-between">
                      <motion.button
                        onClick={() => setStep('cover')}
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.98 }}
                        className="glass-button flex items-center gap-2"
                      >
                        <ArrowLeft className="w-5 h-5" />
                        Indietro
                      </motion.button>
                      <motion.button
                        onClick={handleStep4Submit}
                        disabled={loading}
                        whileHover={{ scale: loading ? 1 : 1.02 }}
                        whileTap={{ scale: loading ? 1 : 0.98 }}
                        className="glass-button-primary flex items-center gap-2 disabled:opacity-50"
                      >
                        {loading ? (
                          <>
                            <Loader2 className="w-5 h-5 animate-spin" />
                            Salvataggio...
                          </>
                        ) : (
                          <>
                            Avanti
                            <ArrowRight className="w-5 h-5" />
                          </>
                        )}
                      </motion.button>
                    </div>
                  </motion.div>
                )}

                {/* Step 5: Fee Selection */}
                {step === 'fees' && (
                  <motion.div
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    className="space-y-6"
                  >
                    <div className="glass-card p-6">
                      <h3 className="text-xl font-semibold mb-4 flex items-center gap-2">
                        <Euro className="w-5 h-5 text-primary-400" />
                        Piano Tariffario
                      </h3>

                      <p className="text-sm text-gray-500 mb-6">
                        Seleziona il piano tariffario per questo ristorante
                      </p>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Best Price Option */}
                        <button
                          onClick={() => setBestPrice(true)}
                          className={`p-6 rounded-lg border-2 transition-all text-left ${
                            bestPrice
                              ? 'border-primary-500 bg-primary-500/10'
                              : 'border-gray-200 bg-gray-50/30 hover:border-gray-300'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-3">
                            <FileText className="w-8 h-8 text-primary-400" />
                            {bestPrice && <Check className="w-6 h-6 text-primary-400" />}
                          </div>
                          <div className="font-semibold text-lg mb-2">Miglior Prezzo</div>
                          <div className="text-sm text-gray-500 mb-3">
                            Massima visibilità nei risultati di ricerca
                          </div>
                          <div className="text-xs text-gray-400 space-y-1">
                            <div>- Primo in lista</div>
                            <div>- Badge "Best Price"</div>
                            <div>- Commissioni ridotte</div>
                          </div>
                        </button>

                        {/* Standard Option */}
                        <button
                          onClick={() => setBestPrice(false)}
                          className={`p-6 rounded-lg border-2 transition-all text-left ${
                            !bestPrice
                              ? 'border-emerald-500 bg-emerald-500/10'
                              : 'border-gray-200 bg-gray-50/30 hover:border-gray-300'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-3">
                            <Package className="w-8 h-8 text-emerald-400" />
                            {!bestPrice && <Check className="w-6 h-6 text-emerald-400" />}
                          </div>
                          <div className="font-semibold text-lg mb-2">Standard</div>
                          <div className="text-sm text-gray-500 mb-3">
                            Visibilità normale, commissioni standard
                          </div>
                          <div className="text-xs text-gray-400 space-y-1">
                            <div>- Listato normale</div>
                            <div>- Nessun badge</div>
                            <div>- Commissioni standard</div>
                          </div>
                        </button>
                      </div>

                      {/* Fee di Attivazione */}
                      <div className="mt-6 p-4 bg-gray-50/50 rounded-lg">
                        <h4 className="font-semibold mb-3 flex items-center gap-2">
                          <Euro className="w-4 h-4 text-amber-400" />
                          Fee di Attivazione (una tantum)
                        </h4>
                        <div className="flex items-center gap-3">
                          <div className="relative flex-1 max-w-xs">
                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                            <input
                              type="number"
                              min="0"
                              step="0.01"
                              value={activationFee}
                              onChange={(e) => setActivationFee(e.target.value)}
                              className="w-full pl-8 pr-4 py-2 bg-gray-100/50 border border-gray-300 rounded-lg text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                              placeholder="150.00"
                            />
                          </div>
                          <span className="text-sm text-gray-500">+ IVA</span>
                        </div>
                        <p className="text-xs text-gray-400 mt-2">
                          Costo una tantum addebitato all'attivazione del partner
                        </p>
                      </div>

                      {/* Fee Class Preview */}
                      {selectedFeeClass && (
                        <motion.div
                          initial={{ opacity: 0, y: 10 }}
                          animate={{ opacity: 1, y: 0 }}
                          className="mt-6 p-4 bg-gray-50/50 rounded-lg"
                        >
                          <h4 className="font-semibold mb-3">{selectedFeeClass.name}</h4>
                          <div className="grid grid-cols-2 gap-3 text-sm">
                            <div>
                              <span className="text-gray-500">Canone mensile:</span>
                              <span className="ml-2 font-semibold">€{selectedFeeClass.monthly_fee}</span>
                            </div>
                            <div>
                              <span className="text-gray-500">Fee ordine:</span>
                              <span className="ml-2 font-semibold">
                                {selectedFeeClass.order_fee_percentage}% + €{selectedFeeClass.order_fee_fixed}
                              </span>
                            </div>
                            {deliveryManagement === 'managed' && (
                              <>
                                <div>
                                  <span className="text-gray-500">Base consegna:</span>
                                  <span className="ml-2 font-semibold">€{selectedFeeClass.delivery_base_fee}</span>
                                </div>
                                <div>
                                  <span className="text-gray-500">Per km:</span>
                                  <span className="ml-2 font-semibold">€{selectedFeeClass.delivery_km_fee}</span>
                                </div>
                              </>
                            )}
                          </div>
                        </motion.div>
                      )}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-between">
                      <motion.button
                        onClick={() => setStep('delivery')}
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.98 }}
                        className="glass-button flex items-center gap-2"
                      >
                        <ArrowLeft className="w-5 h-5" />
                        Indietro
                      </motion.button>
                      <motion.button
                        onClick={handleStep5Submit}
                        disabled={loading}
                        whileHover={{ scale: loading ? 1 : 1.02 }}
                        whileTap={{ scale: loading ? 1 : 0.98 }}
                        className="glass-button-primary flex items-center gap-2 disabled:opacity-50"
                      >
                        {loading ? (
                          <>
                            <Loader2 className="w-5 h-5 animate-spin" />
                            Completamento...
                          </>
                        ) : (
                          <>
                            Completa Onboarding
                            <Check className="w-5 h-5" />
                          </>
                        )}
                      </motion.button>
                    </div>
                  </motion.div>
                )}
                
                {/* Step Complete */}
                {step === 'complete' && (
                  <motion.div
                    initial={{ opacity: 0, scale: 0.9 }}
                    animate={{ opacity: 1, scale: 1 }}
                    className="flex flex-col items-center justify-center py-20"
                  >
                    <motion.div
                      initial={{ scale: 0 }}
                      animate={{ scale: 1 }}
                      transition={{ type: 'spring', stiffness: 200, damping: 15 }}
                      className="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mb-6"
                    >
                      <Check className="w-10 h-10 text-green-400" />
                    </motion.div>
                    <h2 className="text-3xl font-bold mb-3">Onboarding Completato!</h2>
                    <p className="text-gray-500 text-lg mb-8">
                      Cliente e ristorante attivati con successo
                    </p>
                    {!loading && (
                      <motion.button
                        onClick={handleFinalize}
                        whileHover={{ scale: 1.05 }}
                        whileTap={{ scale: 0.95 }}
                        className="glass-button-primary"
                      >
                        Chiudi
                      </motion.button>
                    )}
                  </motion.div>
                )}
              </div>
            </div>
          </motion.div>
        </motion.div>
      )}

      {/* Create Zone Modal */}
      <CreateZoneModal
        isOpen={showCreateZoneModal}
        onClose={() => setShowCreateZoneModal(false)}
        onZoneCreated={handleZoneCreated}
        initialCity={restaurantData.citta || 'Livorno'}
      />
    </AnimatePresence>
  )
}
