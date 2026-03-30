import { motion } from 'framer-motion'
import { useState, useEffect } from 'react'
import { Plus, Search, Edit, Trash2, Users, Store, Phone, Mail, User, UserPlus, Building2, Upload, FileText, XCircle, RefreshCw, Eye, UserMinus, BarChart3, Send, Calendar, Loader2, Download } from 'lucide-react'
import Modal from '../components/Modal'
import OnboardingModalNew from '../components/OnboardingModalNew'
import RestaurantClosureModal from '../components/RestaurantClosureModal'
import { Client } from '../utils/csvImport'
import { api, clientsApi, partnersApi } from '../utils/api'
import axios from 'axios'

interface Restaurant {
  id: number
  nome: string
  indirizzo?: string
  citta?: string
  provincia?: string
  cap?: string
  telefono?: string
  email?: string
  is_active?: boolean
  client_id?: number | null
  client?: Client | null
}

interface Partner {
  id: number
  nome: string
  cognome: string
  email: string
  telefono?: string
  is_active?: boolean
  restaurant_id?: number | null
  restaurant?: Restaurant | null
}

export default function Clients() {
  const [isOnboardingOpen, setIsOnboardingOpen] = useState(false)
  const [isAssignClientOpen, setIsAssignClientOpen] = useState(false)
  const [isManageClientsOpen, setIsManageClientsOpen] = useState(false)
  const [isImportOpen, setIsImportOpen] = useState(false)
  const [isClosureModalOpen, setIsClosureModalOpen] = useState(false)
  const [isViewDetailsOpen, setIsViewDetailsOpen] = useState(false)
  const [selectedPartner, setSelectedPartner] = useState<Partner | null>(null)
  const [selectedClient, setSelectedClient] = useState<Client | null>(null)
  const [partners, setPartners] = useState<Partner[]>([])
  const [clients, setClients] = useState<Client[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [filterType, setFilterType] = useState<'all' | 'with_client' | 'without_client'>('all')
  const [clientSearchTerm, setClientSearchTerm] = useState('')
  const [importFile, setImportFile] = useState<File | null>(null)
  const [isImporting, setIsImporting] = useState(false)
  const [importResults, setImportResults] = useState<any>(null)
  const [assignSearchTerm, setAssignSearchTerm] = useState('')
  const [showClientSuggestions, setShowClientSuggestions] = useState(false)
  const [syncingOppla, setSyncingOppla] = useState(false)
  const [exporting, setExporting] = useState(false)
  const [sortColumn, setSortColumn] = useState<'partner' | 'email' | 'telefono' | 'ristorante' | 'titolare'>('partner')
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc')
  const [suggestedClient, setSuggestedClient] = useState<Client | null>(null)

  // Email actions state
  const [sendingReport, setSendingReport] = useState(false)
  const [sendingRecap, setSendingRecap] = useState(false)
  const [recapNotes, setRecapNotes] = useState('')
  const [showRecapForm, setShowRecapForm] = useState(false)
  const [reportMonth, setReportMonth] = useState(() => {
    const d = new Date()
    d.setMonth(d.getMonth() - 1)
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  })
  const [emailSuccess, setEmailSuccess] = useState<string | null>(null)

  // Modal mode: 'select' to choose existing client, 'create' to create new one
  const [modalMode, setModalMode] = useState<'select' | 'create'>('select')
  
  // Stato per modale gestione titolari - crea nuovo
  const [showCreateFormInManage, setShowCreateFormInManage] = useState(false)
  
  // Form data for creating new client
  const [newClientData, setNewClientData] = useState({
    type: 'partner_oppla' as 'partner_oppla' | 'cliente_extra' | 'consumatore',
    tipo_societa: 'societa' as 'societa' | 'ditta_individuale',
    ragione_sociale: '',
    piva: '',
    codice_fiscale: '',
    codice_fiscale_titolare: '',
    indirizzo: '',
    citta: '',
    provincia: '',
    cap: '',
    telefono: '',
    email: ''
  })

  // Load partners and clients from database
  useEffect(() => {
    const loadData = async () => {
      setLoading(true)
      try {
        const partnersResponse = await partnersApi.getAll()
        const partnersList = partnersResponse.data.data || []
        
        // Request all clients without pagination limit
        const clientsResponse = await clientsApi.getAll({ per_page: 1000 })
        const clientsList = clientsResponse.data.data || []
        
        setPartners(partnersList)
        setClients(clientsList)
        
        console.log(` ${partnersList.length} partners e ${clientsList.length} titolari caricati`)
      } catch (error) {
        console.error(' Errore caricamento dati:', error)
        setPartners([])
        setClients([])
        alert('Errore: impossibile caricare i dati. Verifica che il backend sia avviato.')
      } finally {
        setLoading(false)
      }
    }
    
    loadData()
  }, [])

  // Trova suggerimento quando si seleziona un partner
  useEffect(() => {
    if (selectedPartner && isAssignClientOpen) {
      const suggestion = findBestClientMatch(selectedPartner)
      setSuggestedClient(suggestion)
      if (suggestion) {
        setAssignSearchTerm(suggestion.ragione_sociale || '')
      }
    }
  }, [selectedPartner, isAssignClientOpen])

  // Smart client suggester - trova il cliente più probabile per un partner
  const findBestClientMatch = (partner: Partner): Client | null => {
    if (!partner.restaurant || clients.length === 0) return null
    
    const restaurant = partner.restaurant
    const partnerEmail = partner.email?.toLowerCase() || ''
    const partnerPhone = partner.telefono || ''
    const restaurantName = restaurant.nome?.toLowerCase() || ''
    
    // Calcola score di similarità per ogni cliente
    const scoredClients = clients.map(client => {
      let score = 0
      const clientName = client.ragione_sociale?.toLowerCase() || ''
      const clientEmail = client.email?.toLowerCase() || ''
      const clientPhone = client.telefono || ''
      
      // Match esatto nome ristorante con ragione sociale (+50 punti)
      if (restaurantName && clientName && restaurantName.includes(clientName.split(' ')[0])) {
        score += 50
      }
      
      // Match parziale nome (+20 punti)
      const restaurantWords = restaurantName.split(' ').filter(w => w.length > 3)
      const clientWords = clientName.split(' ').filter(w => w.length > 3)
      const matchingWords = restaurantWords.filter(rw => 
        clientWords.some(cw => rw.includes(cw) || cw.includes(rw))
      )
      score += matchingWords.length * 20
      
      // Match email domain (+30 punti)
      if (partnerEmail && clientEmail) {
        const partnerDomain = partnerEmail.split('@')[1]
        const clientDomain = clientEmail.split('@')[1]
        if (partnerDomain && clientDomain && partnerDomain === clientDomain) {
          score += 30
        }
      }
      
      // Match telefono (+40 punti)
      if (partnerPhone && clientPhone) {
        const cleanPartnerPhone = partnerPhone.replace(/\D/g, '')
        const cleanClientPhone = clientPhone.replace(/\D/g, '')
        if (cleanPartnerPhone && cleanClientPhone && cleanPartnerPhone === cleanClientPhone) {
          score += 40
        }
      }
      
      // Match P.IVA nel nome ristorante (+60 punti)
      if (client.piva && restaurantName.includes(client.piva)) {
        score += 60
      }
      
      return { client, score }
    })
    
    // Ordina per score decrescente
    scoredClients.sort((a, b) => b.score - a.score)
    
    // Ritorna solo se lo score è > 20 (almeno un match parziale)
    return scoredClients[0] && scoredClients[0].score > 20 ? scoredClients[0].client : null
  }

  // Sort handler
  const handleSort = (column: typeof sortColumn) => {
    if (sortColumn === column) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortColumn(column)
      setSortDirection('asc')
    }
  }

  // Filter and sort partners
  const filteredPartners = partners.filter(partner => {
    const matchesSearch = 
      `${partner.nome} ${partner.cognome}`.toLowerCase().includes(searchTerm.toLowerCase()) ||
      partner.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (partner.telefono && partner.telefono.includes(searchTerm)) ||
      (partner.restaurant?.nome && partner.restaurant.nome.toLowerCase().includes(searchTerm.toLowerCase()))
    
    const hasClient = partner.restaurant?.client_id != null
    const matchesFilter = 
      filterType === 'all' ||
      (filterType === 'with_client' && hasClient) ||
      (filterType === 'without_client' && !hasClient)
    
    return matchesSearch && matchesFilter && partner.is_active
  }).sort((a, b) => {
    let aVal: any, bVal: any
    
    switch(sortColumn) {
      case 'partner':
        aVal = `${a.nome} ${a.cognome}`.toLowerCase()
        bVal = `${b.nome} ${b.cognome}`.toLowerCase()
        break
      case 'email':
        aVal = a.email?.toLowerCase() || ''
        bVal = b.email?.toLowerCase() || ''
        break
      case 'telefono':
        aVal = a.telefono || ''
        bVal = b.telefono || ''
        break
      case 'ristorante':
        aVal = a.restaurant?.nome?.toLowerCase() || ''
        bVal = b.restaurant?.nome?.toLowerCase() || ''
        break
      case 'titolare':
        aVal = a.restaurant?.client?.ragione_sociale?.toLowerCase() || ''
        bVal = b.restaurant?.client?.ragione_sociale?.toLowerCase() || ''
        break
      default:
        return 0
    }
    
    if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1
    if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1
    return 0
  })

  const handleAssignClient = async (partnerId: number, clientId: number) => {
    if (!clientId) {
      alert('Seleziona un titolare prima di assegnarlo')
      return
    }
    
    if (!partnerId) {
      alert('Partner non valido')
      return
    }
    
    try {
      await partnersApi.assignClient(partnerId, clientId)
      const response = await partnersApi.getAll()
      setPartners(response.data.data || [])
      alert('Titolare assegnato con successo!')
      setIsAssignClientOpen(false)
      setSelectedPartner(null)
    } catch (error: any) {
      console.error('Errore assegnazione titolare:', error)
      const errorMsg = error.response?.data?.message || error.message || 'Errore durante l\'assegnazione del titolare'
      alert(`Errore: ${errorMsg}`)
    }
  }

  const handleUnassignClient = async (partnerId: number) => {
    if (!confirm('Sei sicuro di voler rimuovere il titolare da questo partner?')) return

    try {
      await partnersApi.unassignClient(partnerId)
      const response = await partnersApi.getAll()
      setPartners(response.data.data || [])
      alert('Titolare rimosso con successo!')
    } catch (error) {
      console.error('Errore rimozione titolare:', error)
      alert('Errore durante la rimozione del titolare')
    }
  }

  const handleDeletePartner = async (partnerId: number, partnerName: string) => {
    if (!confirm(`Sei sicuro di voler eliminare il partner "${partnerName}"? Questa azione non può essere annullata.`)) return

    try {
      await partnersApi.delete(partnerId)
      const response = await partnersApi.getAll()
      setPartners(response.data.data || [])
      alert('Partner eliminato con successo!')
    } catch (error) {
      console.error('Errore eliminazione partner:', error)
      alert('Errore durante l\'eliminazione del partner')
    }
  }

  const handleCreateClient = async () => {
    if (!newClientData.ragione_sociale || !newClientData.email) {
      alert('Ragione sociale ed Email sono obbligatori')
      return
    }

    try {
      const response = await clientsApi.create(newClientData)
      const newClient = response.data.data
      
      setClients([...clients, newClient])
      
      // Se c'è un partner selezionato, assegnalo
      if (selectedPartner && selectedPartner.id) {
        await handleAssignClient(selectedPartner.id, newClient.id)
      }
      
      // Reset form
      setNewClientData({
        type: 'partner_oppla',
        tipo_societa: 'societa',
        ragione_sociale: '',
        piva: '',
        codice_fiscale: '',
        codice_fiscale_titolare: '',
        indirizzo: '',
        citta: '',
        provincia: '',
        cap: '',
        telefono: '',
        email: ''
      })
      
      // Chiudi il form di creazione se è aperto nel modale gestione
      setShowCreateFormInManage(false)
      
      alert('Titolare creato con successo!')
      
    } catch (error) {
      console.error('Errore creazione titolare:', error)
      alert('Errore durante la creazione del titolare')
    }
  }

  const handleUpdateClient = async (clientId: string | number, data: Partial<Client>) => {
    try {
      await clientsApi.update(Number(clientId), data)
      const response = await clientsApi.getAll()
      setClients(response.data.data || [])
      alert('Titolare aggiornato con successo!')
      setSelectedClient(null)
    } catch (error) {
      console.error('Errore aggiornamento titolare:', error)
      alert('Errore durante l\'aggiornamento del titolare')
    }
  }

  const handleDeleteClient = async (clientId: string | number) => {
    if (!confirm('Sei sicuro di voler eliminare questo titolare? Questa azione è irreversibile.')) return
    
    try {
      await clientsApi.delete(Number(clientId))
      setClients(clients.filter(c => String(c.id) !== String(clientId)))
      alert('Titolare eliminato con successo!')
    } catch (error) {
      console.error('Errore eliminazione titolare:', error)
      alert('Errore durante l\'eliminazione del titolare')
    }
  }

  const handleOnboardingComplete = async () => {
    setIsOnboardingOpen(false)
    const partnersResponse = await partnersApi.getAll()
    const clientsResponse = await clientsApi.getAll()
    setPartners(partnersResponse.data.data || [])
    setClients(clientsResponse.data.data || [])
  }

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const response = await clientsApi.export()
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `clienti_${new Date().toISOString().split('T')[0]}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error: any) {
      console.error('Errore esportazione:', error)
      alert('Errore durante l\'esportazione: ' + (error.response?.data?.message || error.message))
    } finally {
      setExporting(false)
    }
  }

  const handleImportCsv = async () => {
    if (!importFile) {
      alert('Seleziona un file CSV')
      return
    }

    setIsImporting(true)
    setImportResults(null)

    try {
      const formData = new FormData()
      formData.append('file', importFile)

      const response = await axios.post('/api/clients/import/csv', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      setImportResults(response.data.results)
      
      // Ricarica clienti e partners
      const partnersResponse = await partnersApi.getAll()
      const clientsResponse = await clientsApi.getAll()
      setPartners(partnersResponse.data.data || [])
      setClients(clientsResponse.data.data || [])

      alert('Importazione completata!')
    } catch (error: any) {
      console.error('Errore importazione:', error)
      alert('Errore durante l\'importazione: ' + (error.response?.data?.message || error.message))
    } finally {
      setIsImporting(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-400 mx-auto mb-4"></div>
          <p className="text-gray-500">Caricamento dati...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">Gestione Partners e Titolari</h1>
          <p className="text-gray-500">
            {filteredPartners.length} partners trovati
            {filterType === 'with_client' && ' con titolare'}
            {filterType === 'without_client' && ' senza titolare'}
          </p>
        </div>
        <div className="flex flex-wrap gap-3">
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={async () => {
              if (!confirm('Sincronizzare ristoranti e partner dal database OPPLA Admin?')) return
              
              setSyncingOppla(true)
              try {
                const token = localStorage.getItem('token')
                const response = await axios.post('/api/oppla/sync/all', {}, {
                  headers: { Authorization: `Bearer ${token}` }
                })
                
                if (response.data.success) {
                  alert(`✅ Sincronizzazione completata!\n\nRistoranti: ${response.data.restaurants || 0}\nPartner: ${response.data.partners || 0}`)
                  // Ricarica dati
                  const partnersResponse = await partnersApi.getAll()
                  setPartners(partnersResponse.data.data || [])
                } else {
                  alert('Errore durante la sincronizzazione: ' + (response.data.message || 'Errore sconosciuto'))
                }
              } catch (error: any) {
                console.error('Errore sync OPPLA:', error)
                alert('Errore durante la sincronizzazione: ' + (error.response?.data?.message || error.message))
              } finally {
                setSyncingOppla(false)
              }
            }}
            disabled={syncingOppla}
            className="glass-button bg-primary-500/10 border-primary-500/30 hover:bg-primary-500/20 text-primary-300 disabled:opacity-50"
          >
            {syncingOppla ? (
              <>
                <div className="animate-spin w-5 h-5 border-2 border-primary-300 border-t-transparent rounded-full mr-2 inline-block"></div>
                Sincronizzazione...
              </>
            ) : (
              <>
                <RefreshCw className="w-5 h-5 mr-2 inline" />
                Sync OPPLA
              </>
            )}
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => setIsClosureModalOpen(true)}
            className="glass-button bg-orange-500/10 border-orange-500/30 hover:bg-orange-500/20 text-orange-300"
          >
            <XCircle className="w-5 h-5 mr-2 inline" />
            Chiusura Massiva
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleExportCSV}
            disabled={exporting}
            className="glass-button bg-green-500/10 border-green-500/30 hover:bg-green-500/20 text-green-300 disabled:opacity-50"
          >
            <Download className={`w-5 h-5 mr-2 inline ${exporting ? 'animate-pulse' : ''}`} />
            {exporting ? 'Esportazione...' : 'Esporta CSV'}
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => setIsImportOpen(true)}
            className="glass-button"
          >
            <Upload className="w-5 h-5 mr-2 inline" />
            Importa CSV
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => setIsManageClientsOpen(true)}
            className="glass-button"
          >
            <Building2 className="w-5 h-5 mr-2 inline" />
            Gestisci Titolari
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => setIsOnboardingOpen(true)}
            className="glass-button-primary"
          >
            <Plus className="w-5 h-5 mr-2 inline" />
            Nuovo Onboarding
          </motion.button>
        </div>
      </div>

      {/* Search and Filter Bar */}
      <div className="glass-card p-4">
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
            <input
              type="search"
              placeholder="Cerca per nome partner, ristorante, email, telefono..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="glass-input pl-10 w-full"
            />
          </div>
          <select
            value={filterType}
            onChange={(e) => setFilterType(e.target.value as any)}
            className="glass-input w-full sm:w-64"
          >
            <option value="all">Tutti i partners</option>
            <option value="with_client">Con titolare assegnato</option>
            <option value="without_client">Senza titolare</option>
          </select>
        </div>
      </div>

      {/* Partners Table */}
      <div className="glass-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="border-b border-gray-200">
              <tr>
                <th 
                  className="text-left p-4 text-gray-600 font-semibold cursor-pointer hover:bg-white/5 transition-colors select-none"
                  onClick={() => handleSort('partner')}
                >
                  <User className="w-4 h-4 inline mr-2" />
                  Partner
                  {sortColumn === 'partner' && (
                    <span className="ml-1">{sortDirection === 'asc' ? '↑' : '↓'}</span>
                  )}
                </th>
                <th 
                  className="text-left p-4 text-gray-600 font-semibold cursor-pointer hover:bg-white/5 transition-colors select-none"
                  onClick={() => handleSort('email')}
                >
                  <Mail className="w-4 h-4 inline mr-2" />
                  Email
                  {sortColumn === 'email' && (
                    <span className="ml-1">{sortDirection === 'asc' ? '↑' : '↓'}</span>
                  )}
                </th>
                <th 
                  className="text-left p-4 text-gray-600 font-semibold cursor-pointer hover:bg-white/5 transition-colors select-none"
                  onClick={() => handleSort('telefono')}
                >
                  <Phone className="w-4 h-4 inline mr-2" />
                  Telefono
                  {sortColumn === 'telefono' && (
                    <span className="ml-1">{sortDirection === 'asc' ? '↑' : '↓'}</span>
                  )}
                </th>
                <th 
                  className="text-left p-4 text-gray-600 font-semibold cursor-pointer hover:bg-white/5 transition-colors select-none"
                  onClick={() => handleSort('ristorante')}
                >
                  <Store className="w-4 h-4 inline mr-2" />
                  Ristorante
                  {sortColumn === 'ristorante' && (
                    <span className="ml-1">{sortDirection === 'asc' ? '↑' : '↓'}</span>
                  )}
                </th>
                <th 
                  className="text-left p-4 text-gray-600 font-semibold cursor-pointer hover:bg-white/5 transition-colors select-none"
                  onClick={() => handleSort('titolare')}
                >
                  <Building2 className="w-4 h-4 inline mr-2" />
                  Titolare
                  {sortColumn === 'titolare' && (
                    <span className="ml-1">{sortDirection === 'asc' ? '↑' : '↓'}</span>
                  )}
                </th>
                <th className="text-left p-4 text-gray-600 font-semibold">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {filteredPartners.map((partner) => (
                <motion.tr
                  key={partner.id}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  className="border-b border-white/5 hover:bg-white/5 transition-colors"
                >
                  <td className="p-4">
                    <div className="font-medium text-gray-900 dark:text-white">
                      {partner.nome} {partner.cognome}
                    </div>
                  </td>
                  <td className="p-4">
                    <div className="text-gray-600">{partner.email}</div>
                  </td>
                  <td className="p-4">
                    <div className="text-gray-600">{partner.telefono || '-'}</div>
                  </td>
                  <td className="p-4">
                    {partner.restaurant ? (
                      <div>
                        <div className="text-primary-400 font-medium">{partner.restaurant.nome}</div>
                        {partner.restaurant.citta && (
                          <div className="text-xs text-gray-400">{partner.restaurant.citta}</div>
                        )}
                      </div>
                    ) : (
                      <span className="text-gray-400">Nessun ristorante</span>
                    )}
                  </td>
                  <td className="p-4">
                    {partner.restaurant?.client ? (
                      <div>
                        <div className="text-emerald-400 font-medium">
                          {partner.restaurant.client.ragione_sociale}
                        </div>
                        {partner.restaurant.client.piva && (
                          <div className="text-xs text-gray-400">
                            P.IVA: {partner.restaurant.client.piva}
                          </div>
                        )}
                      </div>
                    ) : (
                      <span className="text-amber-500">Non assegnato</span>
                    )}
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <motion.button
                        whileHover={{ scale: 1.05 }}
                        whileTap={{ scale: 0.95 }}
                        onClick={() => {
                          setSelectedPartner(partner)
                          setIsViewDetailsOpen(true)
                        }}
                        className="px-3 py-1.5 bg-primary-500/20 hover:bg-primary-500/30 text-primary-300 rounded-lg transition-colors text-sm flex items-center gap-1"
                        title="Visualizza dettagli completi"
                      >
                        <Eye className="w-4 h-4" />
                        Dettagli
                      </motion.button>
                      {partner.restaurant?.client ? (
                        <button
                          onClick={() => handleUnassignClient(partner.id)}
                          className="px-3 py-1 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm transition-colors"
                        >
                          Rimuovi
                        </button>
                      ) : (
                        <button
                          onClick={() => {
                            setSelectedPartner(partner)
                            setModalMode('select')
                            setIsAssignClientOpen(true)
                          }}
                          className="px-3 py-1 bg-primary-500/20 hover:bg-primary-500/30 text-primary-400 rounded-lg text-sm transition-colors"
                        >
                          Assegna Titolare
                        </button>
                      )}
                      <motion.button
                        whileHover={{ scale: 1.05 }}
                        whileTap={{ scale: 0.95 }}
                        onClick={() => handleDeletePartner(partner.id, `${partner.nome} ${partner.cognome}`)}
                        className="px-3 py-1.5 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-colors text-sm flex items-center gap-1"
                        title="Elimina partner"
                      >
                        <Trash2 className="w-4 h-4" />
                      </motion.button>
                    </div>
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Assegna/Crea Titolare */}
      <Modal
        isOpen={isAssignClientOpen}
        onClose={() => {
          setIsAssignClientOpen(false)
          setSelectedPartner(null)
          setModalMode('select')
          setAssignSearchTerm('')
          setShowClientSuggestions(false)
          setSuggestedClient(null)
          setNewClientData({
            type: 'partner_oppla',
            tipo_societa: 'societa',
            ragione_sociale: '',
            piva: '',
            codice_fiscale: '',
            codice_fiscale_titolare: '',
            indirizzo: '',
            citta: '',
            provincia: '',
            cap: '',
            telefono: '',
            email: ''
          })
        }}
        title="Gestione Titolare"
      >
        {selectedPartner && (
          <div className="space-y-4">
            <div className="p-4 bg-gray-50/50 rounded-lg">
              <div className="font-semibold">{selectedPartner.nome} {selectedPartner.cognome}</div>
              {selectedPartner.restaurant && (
                <div className="text-sm text-gray-500 mt-1">
                  <Store className="w-3 h-3 inline mr-1" />
                  <span className="text-primary-400">{selectedPartner.restaurant.nome}</span>
                </div>
              )}
            </div>

            {/* Pulsante Crea Nuovo in evidenza */}
            <button
              onClick={() => setModalMode('create')}
              className={`w-full px-6 py-3 rounded-lg font-semibold transition-all shadow-lg ${
                modalMode === 'create'
                  ? 'bg-emerald-600 text-white ring-2 ring-emerald-400'
                  : 'bg-emerald-500 hover:bg-emerald-600 text-white'
              }`}
            >
              <UserPlus className="w-5 h-5 inline mr-2" />
              Crea Nuovo Titolare
            </button>

            {/* Divisore */}
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-200"></div>
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-gray-900 text-gray-500">oppure</span>
              </div>
            </div>

            {/* Toggle per selezionare esistente */}
            <button
              onClick={() => setModalMode('select')}
              className={`w-full px-6 py-3 rounded-lg font-semibold transition-all ${
                modalMode === 'select'
                  ? 'bg-primary-600 text-white ring-2 ring-primary-400'
                  : 'bg-gray-50 hover:bg-gray-100 text-gray-600 border border-gray-200'
              }`}
            >
              Seleziona Titolare Esistente
            </button>

            {modalMode === 'select' ? (
              <div className="relative">
                {suggestedClient && (
                  <div className="mb-3 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg">
                    <div className="flex items-start gap-2">
                      <div className="text-emerald-400 text-sm font-medium">💡 Suggerimento:</div>
                      <div className="flex-1">
                        <div className="text-gray-900 dark:text-white font-medium">{suggestedClient.ragione_sociale}</div>
                        {suggestedClient.piva && (
                          <div className="text-xs text-gray-500 mt-0.5">P.IVA: {suggestedClient.piva}</div>
                        )}
                        <button
                          onClick={() => {
                            if (selectedPartner?.id && suggestedClient?.id) {
                              handleAssignClient(selectedPartner.id, Number(suggestedClient.id))
                            }
                          }}
                          className="mt-2 px-3 py-1 bg-emerald-500 hover:bg-emerald-600 text-white text-sm rounded transition-colors"
                        >
                          Assegna questo titolare
                        </button>
                      </div>
                    </div>
                  </div>
                )}
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Cerca Titolare
                </label>
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                  <input
                    type="text"
                    placeholder="Cerca per ragione sociale, P.IVA..."
                    value={assignSearchTerm}
                    onChange={(e) => {
                      setAssignSearchTerm(e.target.value)
                      setShowClientSuggestions(true)
                    }}
                    onFocus={() => setShowClientSuggestions(true)}
                    className="glass-input w-full pl-10"
                  />
                </div>

                {/* Suggestions dropdown */}
                {showClientSuggestions && assignSearchTerm && (
                  <div className="absolute z-50 w-full mt-1 bg-gray-50 border border-white/20 rounded-lg shadow-xl max-h-80 overflow-y-auto">
                    {clients
                      .filter(client => {
                        const search = assignSearchTerm.toLowerCase()
                        return client.ragione_sociale?.toLowerCase().includes(search) ||
                               client.piva?.includes(search) ||
                               client.email?.toLowerCase().includes(search)
                      })
                      .slice(0, 10)
                      .map((client) => {
                        const isSuggested = suggestedClient?.id === client.id
                        return (
                        <button
                          key={client.id}
                          onClick={() => {
                            if (selectedPartner.id) {
                              handleAssignClient(selectedPartner.id, parseInt(client.id))
                              setAssignSearchTerm('')
                              setShowClientSuggestions(false)
                            }
                          }}
                          className={`w-full text-left px-4 py-3 hover:bg-primary-500/20 transition-colors border-b border-white/5 last:border-b-0 ${
                            isSuggested ? 'bg-emerald-500/10 border-l-4 border-l-emerald-500' : ''
                          }`}
                        >
                          {isSuggested && (
                            <div className="text-xs text-emerald-400 font-semibold mb-1 flex items-center gap-1">
                              💡 CONSIGLIATO
                            </div>
                          )}
                          <div className="font-medium text-gray-900 dark:text-white">{client.ragione_sociale}</div>
                          {client.piva && (
                            <div className="text-xs text-gray-500">P.IVA: {client.piva}</div>
                          )}
                          {client.email && (
                            <div className="text-xs text-gray-400">{client.email}</div>
                          )}
                        </button>
                      )})}
                    {clients.filter(client => {
                      const search = assignSearchTerm.toLowerCase()
                      return client.ragione_sociale?.toLowerCase().includes(search) ||
                             client.piva?.includes(search) ||
                             client.email?.toLowerCase().includes(search)
                    }).length === 0 && (
                      <div className="px-4 py-3 text-gray-500 text-sm">
                        Nessun titolare trovato
                      </div>
                    )}
                  </div>
                )}

                {clients.length === 0 && (
                  <p className="text-sm text-amber-400 mt-2">
                    Nessun titolare presente. Crea un nuovo titolare.
                  </p>
                )}
              </div>
            ) : (
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Tipo Cliente *
                  </label>
                  <select
                    value={newClientData.type}
                    onChange={(e) => setNewClientData({ ...newClientData, type: e.target.value as 'partner_oppla' | 'cliente_extra' | 'consumatore' })}
                    className="glass-input w-full"
                  >
                    <option value="partner_oppla">Partner OPPLA</option>
                    <option value="cliente_extra">Cliente Extra</option>
                    <option value="consumatore">Consumatore</option>
                  </select>
                </div>

                <div className="flex items-center gap-3 p-3 bg-gray-50/50 rounded-lg">
                  <input
                    type="checkbox"
                    id="ditta-individuale-create"
                    checked={newClientData.tipo_societa === 'ditta_individuale'}
                    onChange={(e) => setNewClientData({ 
                      ...newClientData, 
                      tipo_societa: e.target.checked ? 'ditta_individuale' : 'societa' 
                    })}
                    className="w-4 h-4 rounded border-gray-300 bg-gray-100 text-primary-500 focus:ring-primary-500"
                  />
                  <label htmlFor="ditta-individuale-create" className="text-sm font-medium text-gray-600 cursor-pointer">
                    È una ditta individuale (non società)
                  </label>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Ragione Sociale *
                  </label>
                  <input
                    type="text"
                    value={newClientData.ragione_sociale}
                    onChange={(e) => setNewClientData({ ...newClientData, ragione_sociale: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Es: Ristorante Da Mario S.r.l."
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-600 mb-2">
                      P.IVA
                    </label>
                    <input
                      type="text"
                      value={newClientData.piva}
                      onChange={(e) => setNewClientData({ ...newClientData, piva: e.target.value })}
                      className="glass-input w-full"
                      placeholder="12345678901"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-600 mb-2">
                      Codice Fiscale {newClientData.tipo_societa === 'societa' ? 'Azienda' : ''}
                    </label>
                    <input
                      type="text"
                      value={newClientData.codice_fiscale}
                      onChange={(e) => setNewClientData({ ...newClientData, codice_fiscale: e.target.value })}
                      className="glass-input w-full"
                      placeholder={newClientData.tipo_societa === 'societa' ? '12345678901' : 'RSSMRA80A01H501Z'}
                    />
                  </div>
                </div>

                {newClientData.tipo_societa === 'ditta_individuale' && (
                  <div className="p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                    <label className="block text-sm font-medium text-amber-300 mb-2">
                      <span className="flex items-center gap-2">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Codice Fiscale Titolare *
                      </span>
                    </label>
                    <input
                      type="text"
                      value={newClientData.codice_fiscale_titolare}
                      onChange={(e) => setNewClientData({ ...newClientData, codice_fiscale_titolare: e.target.value })}
                      className="glass-input w-full border-amber-500/30"
                      placeholder="RSSMRA80A01H501U"
                    />
                    <p className="text-xs text-amber-300 mt-1">
                      Questo codice fiscale sarà inviato a Fatture in Cloud per le ditte individuali
                    </p>
                  </div>
                )}

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Indirizzo
                  </label>
                  <input
                    type="text"
                    value={newClientData.indirizzo}
                    onChange={(e) => setNewClientData({ ...newClientData, indirizzo: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Via Roma 123"
                  />
                </div>

                <div className="grid grid-cols-3 gap-4">
                  <div className="col-span-2">
                    <label className="block text-sm font-medium text-gray-600 mb-2">
                      Città
                    </label>
                    <input
                      type="text"
                      value={newClientData.citta}
                      onChange={(e) => setNewClientData({ ...newClientData, citta: e.target.value })}
                      className="glass-input w-full"
                      placeholder="Livorno"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-600 mb-2">
                      Provincia
                    </label>
                    <input
                      type="text"
                      value={newClientData.provincia}
                      onChange={(e) => setNewClientData({ ...newClientData, provincia: e.target.value.toUpperCase() })}
                      className="glass-input w-full"
                      placeholder="LI"
                      maxLength={2}
                    />
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-600 mb-2">
                      CAP
                    </label>
                    <input
                      type="text"
                      value={newClientData.cap}
                      onChange={(e) => setNewClientData({ ...newClientData, cap: e.target.value })}
                      className="glass-input w-full"
                      placeholder="57100"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-600 mb-2">
                      Telefono
                    </label>
                    <input
                      type="tel"
                      value={newClientData.telefono}
                      onChange={(e) => setNewClientData({ ...newClientData, telefono: e.target.value })}
                      className="glass-input w-full"
                      placeholder="+39 0586 123456"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Email *
                  </label>
                  <input
                    type="email"
                    value={newClientData.email}
                    onChange={(e) => setNewClientData({ ...newClientData, email: e.target.value })}
                    className="glass-input w-full"
                    placeholder="info@ristorante.it"
                    required
                  />
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <button
                    onClick={() => {
                      setModalMode('select')
                      setNewClientData({
                        type: 'partner_oppla',
                        tipo_societa: 'societa',
                        ragione_sociale: '',
                        piva: '',
                        codice_fiscale: '',
                        codice_fiscale_titolare: '',
                        indirizzo: '',
                        citta: '',
                        provincia: '',
                        cap: '',
                        telefono: '',
                        email: ''
                      })
                    }}
                    className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                  >
                    Annulla
                  </button>
                  <button
                    onClick={handleCreateClient}
                    className="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-colors"
                  >
                    Crea Titolare
                  </button>
                </div>
              </div>
            )}
          </div>
        )}
      </Modal>

      {/* Onboarding Modal */}
      <OnboardingModalNew
        isOpen={isOnboardingOpen}
        onClose={() => setIsOnboardingOpen(false)}
        onComplete={handleOnboardingComplete}
      />

      {/* Modal Importazione CSV */}
      <Modal
        isOpen={isImportOpen}
        onClose={() => {
          setIsImportOpen(false)
          setImportFile(null)
          setImportResults(null)
        }}
        title="Importa Titolari da CSV"
      >
        <div className="space-y-4">
          <div className="p-4 bg-primary-500/10 border border-primary-500/20 rounded-lg">
            <div className="flex items-start gap-3">
              <FileText className="w-5 h-5 text-primary-400 mt-0.5" />
              <div className="text-sm text-primary-200">
                <p className="font-semibold mb-1">Formato CSV richiesto:</p>
                <ul className="list-disc list-inside space-y-1 text-xs">
                  <li>Ragione sociale (obbligatorio)</li>
                  <li>P. IVA (obbligatorio)</li>
                  <li>Nome proprietario, Email proprietario, Telefono proprietario</li>
                  <li>Indirizzo legale, PEC, IBAN, Codice destinatario</li>
                </ul>
              </div>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-600 mb-2">
              Seleziona file CSV
            </label>
            <input
              type="file"
              accept=".csv"
              onChange={(e) => setImportFile(e.target.files?.[0] || null)}
              className="glass-input w-full"
            />
            {importFile && (
              <p className="text-xs text-gray-500 mt-2">
                File selezionato: {importFile.name} ({(importFile.size / 1024).toFixed(2)} KB)
              </p>
            )}
          </div>

          {importResults && (
            <div className="p-4 bg-gray-50/50 rounded-lg space-y-3">
              <h4 className="font-semibold text-gray-900 dark:text-white">Risultati Importazione</h4>
              
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="p-2 bg-emerald-500/10 border border-emerald-500/20 rounded">
                  <div className="text-emerald-400 font-semibold">Creati</div>
                  <div className="text-2xl font-bold text-gray-900 dark:text-white">{importResults.created}</div>
                </div>
                <div className="p-2 bg-primary-500/10 border border-primary-500/20 rounded">
                  <div className="text-primary-400 font-semibold">🔄 Aggiornati</div>
                  <div className="text-2xl font-bold text-gray-900 dark:text-white">{importResults.updated}</div>
                </div>
                <div className="p-2 bg-amber-500/10 border border-amber-500/20 rounded">
                  <div className="text-amber-400 font-semibold">⏭️ Saltati</div>
                  <div className="text-2xl font-bold text-gray-900 dark:text-white">{importResults.skipped}</div>
                </div>
                <div className="p-2 bg-red-500/10 border border-red-500/20 rounded">
                  <div className="text-red-400 font-semibold">Errori</div>
                  <div className="text-2xl font-bold text-gray-900 dark:text-white">{importResults.errors?.length || 0}</div>
                </div>
              </div>

              {importResults.assignments && (
                <div className="border-t border-gray-200 pt-3">
                  <div className="font-semibold text-gray-900 dark:text-white mb-2">Assegnazioni Partner</div>
                  <div className="grid grid-cols-2 gap-2 text-sm">
                    <div className="text-emerald-400">
                      Successo: {importResults.assignments.successful}
                    </div>
                    <div className="text-red-400">
                      Fallite: {importResults.assignments.failed}
                    </div>
                  </div>
                </div>
              )}

              {importResults.errors && importResults.errors.length > 0 && (
                <div className="border-t border-gray-200 pt-3">
                  <div className="font-semibold text-red-400 mb-2">Errori:</div>
                  <div className="max-h-40 overflow-y-auto space-y-1 text-xs">
                    {importResults.errors.map((err: any, idx: number) => (
                      <div key={idx} className="text-red-300">
                        Riga {err.row}: {err.error}
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4">
            <button
              onClick={() => {
                setIsImportOpen(false)
                setImportFile(null)
                setImportResults(null)
              }}
              className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
              disabled={isImporting}
            >
              {importResults ? 'Chiudi' : 'Annulla'}
            </button>
            {!importResults && (
              <button
                onClick={handleImportCsv}
                disabled={!importFile || isImporting}
                className="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isImporting ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white inline mr-2"></div>
                    Importazione...
                  </>
                ) : (
                  <>
                    <Upload className="w-4 h-4 inline mr-2" />
                    Importa
                  </>
                )}
              </button>
            )}
          </div>
        </div>
      </Modal>

      {/* Modal Gestisci Titolari */}
      <Modal
        isOpen={isManageClientsOpen}
        onClose={() => {
          setIsManageClientsOpen(false)
          setSelectedClient(null)
          setClientSearchTerm('')
          setShowCreateFormInManage(false)
        }}
        title="Gestione Titolari"
      >
        <div className="space-y-4">
          {/* Pulsante Crea Nuovo Titolare in evidenza */}
          <button
            onClick={() => setShowCreateFormInManage(!showCreateFormInManage)}
            className={`w-full px-6 py-3 rounded-lg font-semibold transition-all shadow-lg ${
              showCreateFormInManage
                ? 'bg-emerald-600 text-white ring-2 ring-emerald-400'
                : 'bg-emerald-500 hover:bg-emerald-600 text-white'
            }`}
          >
            <UserPlus className="w-5 h-5 inline mr-2" />
            {showCreateFormInManage ? 'Nascondi Form' : 'Crea Nuovo Titolare'}
          </button>

          {/* Form creazione nuovo titolare */}
          {showCreateFormInManage && (
            <div className="glass-card p-4 space-y-4">
              <h4 className="font-semibold text-gray-900 dark:text-white">Nuovo Titolare</h4>
              
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Tipo Cliente *
                </label>
                <select
                  value={newClientData.type}
                  onChange={(e) => setNewClientData({ ...newClientData, type: e.target.value as 'partner_oppla' | 'cliente_extra' | 'consumatore' })}
                  className="glass-input w-full"
                >
                  <option value="partner_oppla">Partner OPPLA</option>
                  <option value="cliente_extra">Cliente Extra</option>
                  <option value="consumatore">Consumatore</option>
                </select>
              </div>

              <div className="flex items-center gap-3 p-3 bg-gray-50/50 rounded-lg">
                <input
                  type="checkbox"
                  id="ditta-individuale-manage"
                  checked={newClientData.tipo_societa === 'ditta_individuale'}
                  onChange={(e) => setNewClientData({ 
                    ...newClientData, 
                    tipo_societa: e.target.checked ? 'ditta_individuale' : 'societa' 
                  })}
                  className="w-4 h-4 rounded border-gray-300 bg-gray-100 text-primary-500 focus:ring-primary-500"
                />
                <label htmlFor="ditta-individuale-manage" className="text-sm font-medium text-gray-600 cursor-pointer">
                  È una ditta individuale (non società)
                </label>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Ragione Sociale *
                </label>
                <input
                  type="text"
                  value={newClientData.ragione_sociale}
                  onChange={(e) => setNewClientData({ ...newClientData, ragione_sociale: e.target.value })}
                  className="glass-input w-full"
                  placeholder="Es: Ristorante Da Mario S.r.l."
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    P.IVA
                  </label>
                  <input
                    type="text"
                    value={newClientData.piva}
                    onChange={(e) => setNewClientData({ ...newClientData, piva: e.target.value })}
                    className="glass-input w-full"
                    placeholder="12345678901"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Codice Fiscale {newClientData.tipo_societa === 'societa' ? 'Azienda' : ''}
                  </label>
                  <input
                    type="text"
                    value={newClientData.codice_fiscale}
                    onChange={(e) => setNewClientData({ ...newClientData, codice_fiscale: e.target.value })}
                    className="glass-input w-full"
                    placeholder={newClientData.tipo_societa === 'societa' ? '12345678901' : 'RSSMRA80A01H501Z'}
                  />
                </div>
              </div>

              {newClientData.tipo_societa === 'ditta_individuale' && (
                <div className="p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                  <label className="block text-sm font-medium text-amber-300 mb-2">
                    <span className="flex items-center gap-2">
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      Codice Fiscale Titolare *
                    </span>
                  </label>
                  <input
                    type="text"
                    value={newClientData.codice_fiscale_titolare}
                    onChange={(e) => setNewClientData({ ...newClientData, codice_fiscale_titolare: e.target.value })}
                    className="glass-input w-full border-amber-500/30"
                    placeholder="RSSMRA80A01H501U"
                  />
                  <p className="text-xs text-amber-300 mt-1">
                    Questo codice fiscale sarà inviato a Fatture in Cloud per le ditte individuali
                  </p>
                </div>
              )}

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Telefono
                  </label>
                  <input
                    type="tel"
                    value={newClientData.telefono}
                    onChange={(e) => setNewClientData({ ...newClientData, telefono: e.target.value })}
                    className="glass-input w-full"
                    placeholder="+39 0586 123456"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Email *
                  </label>
                  <input
                    type="email"
                    value={newClientData.email}
                    onChange={(e) => setNewClientData({ ...newClientData, email: e.target.value })}
                    className="glass-input w-full"
                    placeholder="info@ristorante.it"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Codice Fiscale
                  </label>
                  <input
                    type="text"
                    value={newClientData.codice_fiscale}
                    onChange={(e) => setNewClientData({ ...newClientData, codice_fiscale: e.target.value })}
                    className="glass-input w-full"
                    placeholder="RSSMRA80A01H501Z"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4">
                <button
                  onClick={() => {
                    setShowCreateFormInManage(false)
                    setNewClientData({
                      type: 'partner_oppla',
                      tipo_societa: 'societa',
                      ragione_sociale: '',
                      piva: '',
                      codice_fiscale: '',
                      codice_fiscale_titolare: '',
                      indirizzo: '',
                      citta: '',
                      provincia: '',
                      cap: '',
                      telefono: '',
                      email: ''
                    })
                  }}
                  className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                >
                  Annulla
                </button>
                <button
                  onClick={handleCreateClient}
                  className="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-colors"
                >
                  Crea Titolare
                </button>
              </div>
            </div>
          )}

          {/* Divisore */}
          {!showCreateFormInManage && (
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-200"></div>
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-gray-900 text-gray-500">oppure gestisci esistenti</span>
              </div>
            </div>
          )}

          {/* Search */}
          {!showCreateFormInManage && (
            <>
              <div className="space-y-2">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                  <input
                    type="search"
                    placeholder="Cerca per ragione sociale, P.IVA, email..."
                    value={clientSearchTerm}
                    onChange={(e) => setClientSearchTerm(e.target.value)}
                    className="glass-input pl-10 w-full"
                  />
                </div>
                <div className="text-sm text-gray-500">
                  Mostrando {clients.filter(client => {
                    const search = clientSearchTerm.toLowerCase()
                    return !clientSearchTerm ||
                      client.ragione_sociale?.toLowerCase().includes(search) ||
                      client.piva?.includes(search) ||
                      client.email?.toLowerCase().includes(search)
                  }).length} di {clients.length} titolari
                </div>
              </div>

              {/* Lista Titolari */}
              <div className="max-h-[600px] overflow-y-auto border border-gray-200 rounded-lg">
            <table className="w-full">
              <thead className="sticky top-0 bg-gray-50 z-10">
                <tr className="border-b border-gray-200">
                  <th className="text-left p-3 text-sm text-gray-600">Ragione Sociale</th>
                  <th className="text-left p-3 text-sm text-gray-600">P.IVA</th>
                  <th className="text-left p-3 text-sm text-gray-600">Email</th>
                  <th className="text-left p-3 text-sm text-gray-600">Origine</th>
                  <th className="text-center p-3 text-sm text-gray-600">Azioni</th>
                </tr>
              </thead>
              <tbody>
                {clients
                  .filter(client => {
                    const search = clientSearchTerm.toLowerCase()
                    return !clientSearchTerm ||
                      client.ragione_sociale?.toLowerCase().includes(search) ||
                      client.piva?.includes(search) ||
                      client.email?.toLowerCase().includes(search)
                  })
                  .map((client) => (
                    <tr key={client.id} className="border-b border-white/5 hover:bg-white/5">
                      <td className="p-3">
                        <div>
                          <div className="font-medium text-gray-900 dark:text-white">{client.ragione_sociale}</div>
                          {client.ragione_sociale?.startsWith('Da completare') && (
                            <span className="text-xs text-amber-400">⚠️ Incompleto</span>
                          )}
                        </div>
                      </td>
                      <td className="p-3 text-gray-600">{client.piva || '-'}</td>
                      <td className="p-3 text-gray-600">{client.email || '-'}</td>
                      <td className="p-3">
                        {client.source === 'stripe_auto' ? (
                          <span className="text-xs px-2 py-1 bg-primary-500/20 text-primary-400 rounded">
                            Stripe Auto
                          </span>
                        ) : (
                          <span className="text-xs px-2 py-1 bg-gray-100 text-gray-500 rounded">
                            Manuale
                          </span>
                        )}
                      </td>
                      <td className="p-3">
                        <div className="flex justify-center gap-2">
                          <button
                            onClick={() => setSelectedClient(client)}
                            className="p-2 hover:bg-primary-500/20 text-primary-400 rounded transition-colors"
                            title="Modifica"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleDeleteClient(client.id)}
                            className="p-2 hover:bg-red-500/20 text-red-400 rounded transition-colors"
                            title="Elimina"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
              </tbody>
            </table>
            {clients.length === 0 && (
              <div className="text-center py-8 text-gray-500">
                Nessun titolare trovato
                </div>
              )}
            </div>
            </>
          )}

          {/* Edit Form (se selected) */}
          {selectedClient && !showCreateFormInManage && (
            <div className="mt-4 p-4 bg-gray-50/50 rounded-lg space-y-4">
              <h4 className="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Edit className="w-4 h-4" />
                Modifica Titolare
              </h4>

              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Tipo Cliente *
                </label>
                <select
                  value={selectedClient.type || 'partner_oppla'}
                  onChange={(e) => setSelectedClient({ ...selectedClient, type: e.target.value as any })}
                  className="glass-input w-full"
                >
                  <option value="partner_oppla">Partner OPPLA</option>
                  <option value="cliente_extra">Cliente Extra</option>
                  <option value="consumatore">Consumatore</option>
                </select>
              </div>

              <div className="flex items-center gap-3 p-3 bg-gray-50/50 rounded-lg border border-gray-200">
                <input
                  type="checkbox"
                  id="ditta-individuale-edit"
                  checked={selectedClient.tipo_societa === 'ditta_individuale'}
                  onChange={(e) => setSelectedClient({ 
                    ...selectedClient, 
                    tipo_societa: e.target.checked ? 'ditta_individuale' : 'societa' 
                  })}
                  className="w-4 h-4 rounded border-gray-300 bg-gray-100 text-primary-500 focus:ring-primary-500"
                />
                <label htmlFor="ditta-individuale-edit" className="text-sm font-medium text-gray-600 cursor-pointer">
                  È una ditta individuale (non società)
                </label>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Ragione Sociale *
                </label>
                <input
                  type="text"
                  value={selectedClient.ragione_sociale}
                  onChange={(e) => setSelectedClient({ ...selectedClient, ragione_sociale: e.target.value })}
                  className="glass-input w-full"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    P.IVA
                  </label>
                  <input
                    type="text"
                    value={selectedClient.piva || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, piva: e.target.value })}
                    className="glass-input w-full"
                    placeholder="12345678901"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Codice Fiscale {selectedClient.tipo_societa === 'societa' ? 'Azienda' : ''}
                  </label>
                  <input
                    type="text"
                    value={selectedClient.codice_fiscale || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, codice_fiscale: e.target.value })}
                    className="glass-input w-full"
                    placeholder={selectedClient.tipo_societa === 'societa' ? '12345678901' : 'RSSMRA80A01H501Z'}
                  />
                </div>
              </div>

              {selectedClient.tipo_societa === 'ditta_individuale' && (
                <div className="p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                  <label className="block text-sm font-medium text-amber-300 mb-2">
                    <span className="flex items-center gap-2">
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      Codice Fiscale Titolare *
                    </span>
                  </label>
                  <input
                    type="text"
                    value={selectedClient.codice_fiscale_titolare || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, codice_fiscale_titolare: e.target.value })}
                    className="glass-input w-full border-amber-500/30"
                    placeholder="RSSMRA80A01H501U"
                  />
                  <p className="text-xs text-amber-300 mt-1">
                    Questo codice fiscale sarà inviato a Fatture in Cloud per le ditte individuali
                  </p>
                </div>
              )}

              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Indirizzo
                </label>
                <input
                  type="text"
                  value={selectedClient.indirizzo || ''}
                  onChange={(e) => setSelectedClient({ ...selectedClient, indirizzo: e.target.value })}
                  className="glass-input w-full"
                  placeholder="Via Roma 123"
                />
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Città
                  </label>
                  <input
                    type="text"
                    value={selectedClient.citta || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, citta: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Milano"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Provincia
                  </label>
                  <input
                    type="text"
                    value={selectedClient.provincia || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, provincia: e.target.value })}
                    className="glass-input w-full"
                    placeholder="MI"
                    maxLength={2}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    CAP
                  </label>
                  <input
                    type="text"
                    value={selectedClient.cap || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, cap: e.target.value })}
                    className="glass-input w-full"
                    placeholder="20100"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Telefono
                  </label>
                  <input
                    type="tel"
                    value={selectedClient.telefono || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, telefono: e.target.value })}
                    className="glass-input w-full"
                    placeholder="+39 0586 123456"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Email
                  </label>
                  <input
                    type="email"
                    value={selectedClient.email || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, email: e.target.value })}
                    className="glass-input w-full"
                    placeholder="info@ristorante.it"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    PEC
                  </label>
                  <input
                    type="email"
                    value={selectedClient.pec || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, pec: e.target.value })}
                    className="glass-input w-full"
                    placeholder="ristorante@pec.it"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Codice SDI
                  </label>
                  <input
                    type="text"
                    value={selectedClient.sdi_code || ''}
                    onChange={(e) => setSelectedClient({ ...selectedClient, sdi_code: e.target.value })}
                    className="glass-input w-full"
                    placeholder="XXXXXXX"
                    maxLength={7}
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button
                  onClick={() => setSelectedClient(null)}
                  className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                >
                  Annulla
                </button>
                <button
                  onClick={() => handleUpdateClient(selectedClient.id, selectedClient)}
                  className="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors"
                >
                  Salva Modifiche
                </button>
              </div>
            </div>
          )}
        </div>
      </Modal>

      {/* Modal Visualizzazione Dettagli */}
      <Modal
        isOpen={isViewDetailsOpen}
        onClose={() => {
          setIsViewDetailsOpen(false)
          setSelectedPartner(null)
        }}
        title="Dettagli Completi"
      >
        {selectedPartner && (
          <div className="space-y-6">
            {/* Dati Partner */}
            <div className="glass-card p-4">
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <User className="w-5 h-5 text-primary-400" />
                Dati Partner
              </h3>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-xs text-gray-500">Nome Completo</label>
                  <div className="text-gray-900 dark:text-white font-medium">{selectedPartner.nome} {selectedPartner.cognome}</div>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Email</label>
                  <div className="text-gray-900 dark:text-white">{selectedPartner.email}</div>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Telefono</label>
                  <div className="text-gray-900 dark:text-white">{selectedPartner.telefono || 'N/D'}</div>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Stato</label>
                  <div>
                    <span className={`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${
                      selectedPartner.is_active 
                        ? 'bg-emerald-500/20 text-emerald-300' 
                        : 'bg-red-500/20 text-red-300'
                    }`}>
                      {selectedPartner.is_active ? 'Attivo' : 'Non attivo'}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {/* Dati Ristorante */}
            {selectedPartner.restaurant && (
              <div className="glass-card p-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <Store className="w-5 h-5 text-orange-400" />
                  Dati Ristorante
                </h3>
                <div className="grid grid-cols-2 gap-4">
                  <div className="col-span-2">
                    <label className="text-xs text-gray-500">Nome Ristorante</label>
                    <div className="text-gray-900 dark:text-white font-medium text-lg">{selectedPartner.restaurant.nome}</div>
                  </div>
                  <div className="col-span-2">
                    <label className="text-xs text-gray-500">Indirizzo Completo</label>
                    <div className="text-gray-900 dark:text-white">
                      {selectedPartner.restaurant.indirizzo || 'N/D'}
                      {selectedPartner.restaurant.citta && `, ${selectedPartner.restaurant.citta}`}
                      {selectedPartner.restaurant.provincia && ` (${selectedPartner.restaurant.provincia})`}
                      {selectedPartner.restaurant.cap && ` - ${selectedPartner.restaurant.cap}`}
                    </div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Email</label>
                    <div className="text-gray-900 dark:text-white">{selectedPartner.restaurant.email || 'N/D'}</div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Telefono</label>
                    <div className="text-gray-900 dark:text-white">{selectedPartner.restaurant.telefono || 'N/D'}</div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Stato</label>
                    <div>
                      <span className={`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${
                        selectedPartner.restaurant.is_active 
                          ? 'bg-emerald-500/20 text-emerald-300' 
                          : 'bg-red-500/20 text-red-300'
                      }`}>
                        {selectedPartner.restaurant.is_active ? 'Attivo' : 'Non attivo'}
                      </span>
                    </div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">ID Ristorante</label>
                    <div className="text-gray-600 font-mono text-sm">#{selectedPartner.restaurant.id}</div>
                  </div>
                </div>
              </div>
            )}

            {/* Dati Titolare */}
            {selectedPartner.restaurant?.client ? (
              <div className="glass-card p-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <Building2 className="w-5 h-5 text-emerald-400" />
                  Dati Titolare
                </h3>
                <div className="grid grid-cols-2 gap-4">
                  <div className="col-span-2">
                    <label className="text-xs text-gray-500">Ragione Sociale</label>
                    <div className="text-gray-900 dark:text-white font-medium text-lg">{selectedPartner.restaurant.client.ragione_sociale}</div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Tipo</label>
                    <div>
                      <span className="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-primary-500/20 text-primary-300">
                        {selectedPartner.restaurant.client.type === 'partner_oppla' && 'Partner OPPLA'}
                        {selectedPartner.restaurant.client.type === 'cliente_extra' && 'Cliente Extra'}
                        {selectedPartner.restaurant.client.type === 'consumatore' && 'Consumatore'}
                      </span>
                    </div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Tipo Società</label>
                    <div className="text-gray-900 dark:text-white">
                      {selectedPartner.restaurant.client.tipo_societa === 'ditta_individuale' ? 'Ditta Individuale' : 'Società'}
                    </div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Partita IVA</label>
                    <div className="text-gray-900 dark:text-white font-mono">{selectedPartner.restaurant.client.piva || 'N/D'}</div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Codice Fiscale</label>
                    <div className="text-gray-900 dark:text-white font-mono">{selectedPartner.restaurant.client.codice_fiscale || 'N/D'}</div>
                  </div>
                  {selectedPartner.restaurant.client.tipo_societa === 'ditta_individuale' && selectedPartner.restaurant.client.codice_fiscale_titolare && (
                    <div className="col-span-2">
                      <label className="text-xs text-amber-400">Codice Fiscale Titolare</label>
                      <div className="text-gray-900 dark:text-white font-mono bg-amber-500/10 px-2 py-1 rounded">
                        {selectedPartner.restaurant.client.codice_fiscale_titolare}
                      </div>
                    </div>
                  )}
                  <div className="col-span-2">
                    <label className="text-xs text-gray-500">Indirizzo Completo</label>
                    <div className="text-gray-900 dark:text-white">
                      {selectedPartner.restaurant.client.indirizzo || 'N/D'}
                      {selectedPartner.restaurant.client.citta && `, ${selectedPartner.restaurant.client.citta}`}
                      {selectedPartner.restaurant.client.provincia && ` (${selectedPartner.restaurant.client.provincia})`}
                      {selectedPartner.restaurant.client.cap && ` - ${selectedPartner.restaurant.client.cap}`}
                    </div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Email</label>
                    <div className="text-gray-900 dark:text-white">{selectedPartner.restaurant.client.email || 'N/D'}</div>
                  </div>
                  <div>
                    <label className="text-xs text-gray-500">Telefono</label>
                    <div className="text-gray-900 dark:text-white">{selectedPartner.restaurant.client.telefono || 'N/D'}</div>
                  </div>
                  {selectedPartner.restaurant.client.pec && (
                    <div>
                      <label className="text-xs text-gray-500">PEC</label>
                      <div className="text-gray-900 dark:text-white">{selectedPartner.restaurant.client.pec}</div>
                    </div>
                  )}
                  {selectedPartner.restaurant.client.sdi_code && (
                    <div>
                      <label className="text-xs text-gray-500">Codice SDI</label>
                      <div className="text-gray-900 dark:text-white font-mono">{selectedPartner.restaurant.client.sdi_code}</div>
                    </div>
                  )}
                  <div>
                    <label className="text-xs text-gray-500">ID Cliente</label>
                    <div className="text-gray-600 font-mono text-sm">#{selectedPartner.restaurant.client.id}</div>
                  </div>
                </div>
              </div>
            ) : (
              <div className="glass-card p-4 text-center">
                <Building2 className="w-12 h-12 text-gray-500 mx-auto mb-3" />
                <p className="text-gray-500">Nessun titolare assegnato</p>
                <button
                  onClick={() => {
                    setIsViewDetailsOpen(false)
                    setIsAssignClientOpen(true)
                  }}
                  className="mt-4 px-4 py-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 rounded-lg transition-colors inline-flex items-center gap-2"
                >
                  <UserPlus className="w-4 h-4" />
                  Assegna Titolare
                </button>
              </div>
            )}

            {/* Azioni Email */}
            {selectedPartner?.restaurant?.client && selectedPartner.restaurant.client.email && (
              <div className="glass-card p-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <Mail className="w-5 h-5 text-primary-400" />
                  Azioni Email
                </h3>

                {emailSuccess && (
                  <div className="mb-4 p-3 bg-emerald-500/20 text-emerald-300 rounded-lg text-sm">
                    {emailSuccess}
                  </div>
                )}

                <div className="space-y-3">
                  {/* Invia Report Mensile */}
                  <div className="flex items-center gap-3">
                    <input
                      type="month"
                      value={reportMonth}
                      onChange={(e) => setReportMonth(e.target.value)}
                      className="flex-shrink-0 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                    <button
                      disabled={sendingReport}
                      onClick={async () => {
                        const clientId = selectedPartner?.restaurant?.client?.id
                        if (!clientId) return
                        setSendingReport(true)
                        setEmailSuccess(null)
                        try {
                          const [year, month] = reportMonth.split('-')
                          const res = await api.post(`/clients/${clientId}/monthly-report/send`, { year: parseInt(year), month: parseInt(month) })
                          setEmailSuccess(res.data.message)
                        } catch (err: any) {
                          setEmailSuccess(err.response?.data?.message || 'Errore nell\'invio del report')
                        } finally {
                          setSendingReport(false)
                        }
                      }}
                      className="flex-1 px-4 py-2 bg-primary-500/20 hover:bg-primary-500/30 text-primary-300 rounded-lg transition-colors inline-flex items-center justify-center gap-2 text-sm disabled:opacity-50"
                    >
                      {sendingReport ? <Loader2 className="w-4 h-4 animate-spin" /> : <BarChart3 className="w-4 h-4" />}
                      Invia Report Mensile
                    </button>
                  </div>

                  {/* Invia Recap Appuntamento */}
                  {!showRecapForm ? (
                    <button
                      onClick={() => setShowRecapForm(true)}
                      className="w-full px-4 py-2 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 rounded-lg transition-colors inline-flex items-center justify-center gap-2 text-sm"
                    >
                      <Calendar className="w-4 h-4" />
                      Invia Recap Appuntamento
                    </button>
                  ) : (
                    <div className="space-y-3 p-3 bg-gray-50/50 rounded-lg border border-white/5">
                      <label className="text-xs text-gray-500">Note aggiuntive (opzionale)</label>
                      <textarea
                        value={recapNotes}
                        onChange={(e) => setRecapNotes(e.target.value)}
                        rows={3}
                        placeholder="Es. Abbiamo concordato attivazione entro fine mese..."
                        className="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                      />
                      <div className="flex gap-2">
                        <button
                          onClick={() => { setShowRecapForm(false); setRecapNotes('') }}
                          className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors text-sm"
                        >
                          Annulla
                        </button>
                        <button
                          disabled={sendingRecap}
                          onClick={async () => {
                            const clientId = selectedPartner?.restaurant?.client?.id
                            if (!clientId) return
                            setSendingRecap(true)
                            setEmailSuccess(null)
                            try {
                              const res = await api.post(`/clients/${clientId}/send-appointment-recap`, {
                                notes: recapNotes || null,
                              })
                              setEmailSuccess(res.data.message)
                              setShowRecapForm(false)
                              setRecapNotes('')
                            } catch (err: any) {
                              setEmailSuccess(err.response?.data?.message || 'Errore nell\'invio del recap')
                            } finally {
                              setSendingRecap(false)
                            }
                          }}
                          className="flex-1 px-4 py-2 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 rounded-lg transition-colors inline-flex items-center justify-center gap-2 text-sm disabled:opacity-50"
                        >
                          {sendingRecap ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                          Invia Recap
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}

            <div className="flex justify-end pt-4 border-t border-gray-200">
              <button
                onClick={() => {
                  setIsViewDetailsOpen(false)
                  setSelectedPartner(null)
                  setEmailSuccess(null)
                  setShowRecapForm(false)
                  setRecapNotes('')
                }}
                className="px-6 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
              >
                Chiudi
              </button>
            </div>
          </div>
        )}
      </Modal>

      {/* Restaurant Mass Closure Modal */}
      <RestaurantClosureModal
        isOpen={isClosureModalOpen}
        onClose={() => setIsClosureModalOpen(false)}
        onSuccess={() => {
          console.log('Chiusura massiva completata!')
          setIsClosureModalOpen(false)
        }}
      />
    </div>
  )
}
