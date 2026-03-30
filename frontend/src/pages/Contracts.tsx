import { motion } from 'framer-motion'
import { useState, useEffect } from 'react'
import {
  Search,
  Plus,
  Briefcase,
  FileText,
  Calendar,
  AlertCircle,
  CheckCircle,
  Clock,
  Download,
  Upload,
  Eye,
  X,
  Send,
  AlertTriangle,
  Bell
} from 'lucide-react'
import { contractsApi } from '../utils/api'

interface Contract {
  id: number
  contract_number?: string
  subject?: string
  title: string
  client_id: number
  client?: { 
    id: number
    ragione_sociale?: string
    name?: string 
  }
  client_name?: string
  client_email?: string
  status: ContractStatus
  start_date: string
  end_date: string
  value?: number
  pdf_path?: string
  created_at: string
  // Dati contratto Oppla
  partner_ragione_sociale?: string
  partner_piva?: string
  partner_sede_legale?: string
  partner_iban?: string
  partner_legale_rappresentante?: string
  partner_email?: string
  periodo_mesi?: number
  territorio?: string
  costo_attivazione?: number
  signatures?: ContractSignature[]
}

interface ContractSignature {
  id: number
  signer_name: string
  signer_email: string
  status: 'pending' | 'sent' | 'signed' | 'declined'
  signed_at?: string
}

interface Client {
  id: number
  name: string
  ragione_sociale?: string
  email: string
  vat_number?: string
  piva?: string
  address?: string
  indirizzo?: string
  iban?: string
  legal_representative?: string
}

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

type ContractStatus = 'draft' | 'ready_to_sign' | 'pending_signature' | 'signed' | 'active' | 'expired' | 'terminated' | 'cancelled'

export default function Contracts() {
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<ContractStatus | 'all'>('all')
  const [contracts, setContracts] = useState<Contract[]>([])
  const [clients, setClients] = useState<Client[]>([])
  const [loading, setLoading] = useState(true)
  const [stats, setStats] = useState({
    total: 0,
    active: 0,
    pending_signature: 0,
    expired: 0,
    expiring_soon: 0
  })
  const [exporting, setExporting] = useState(false)
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [clientSearchTerm, setClientSearchTerm] = useState('')
  const [showClientSuggestions, setShowClientSuggestions] = useState(false)
  const [selectedClient, setSelectedClient] = useState<Client | null>(null)
  const [formData, setFormData] = useState({
    title: '',
    client_id: '',
    contract_type: 'servizio',
    start_date: '',
    end_date: '',
    value: '',
    // Informazioni Partner
    partner_ragione_sociale: '',
    partner_piva: '',
    partner_sede_legale: '',
    partner_iban: '',
    partner_legale_rappresentante: '',
    partner_email: '',
    // Durata e zona
    periodo_mesi: '12',
    territorio: 'Italia',
    // Sito
    site_name: '',
    site_address: '',
    // Costi
    costo_attivazione: '150',
    // Servizi
    servizio_ritiro: '12.00',
    servizio_principale: '2.98',
    ordine_rifiutato: '1.49',
    abbonamento_consegne: '24.00',
    inserimento_manuale: '1.49',
    // Attrezzature
    attrezzatura_fornita: true,
    // Miglior prezzo garantito
    miglior_prezzo_garantito: false
  })

  // Contratti in scadenza (entro 30 giorni)
  const [expiringContracts, setExpiringContracts] = useState<Contract[]>([])
  const [showExpiringAlert, setShowExpiringAlert] = useState(false)

  useEffect(() => {
    fetchContracts()
    fetchClients()
    fetchStats()
  }, [])

  // Check for expiring contracts
  useEffect(() => {
    const expiring = contracts.filter(contract => {
      if (!contract.end_date || contract.status === 'expired' || contract.status === 'terminated' || contract.status === 'cancelled') {
        return false
      }
      const endDate = new Date(contract.end_date)
      const today = new Date()
      const thirtyDaysFromNow = new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000)
      return endDate <= thirtyDaysFromNow && endDate >= today
    })
    setExpiringContracts(expiring)
    if (expiring.length > 0) {
      setShowExpiringAlert(true)
    }
  }, [contracts])

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      const target = e.target as HTMLElement
      if (!target.closest('.client-search-container')) {
        setShowClientSuggestions(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const fetchContracts = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const result = await response.json()
        const contractsData = result.data || result
        setContracts(Array.isArray(contractsData) ? contractsData : [])
      }
    } catch (error) {
      console.error('Error fetching contracts:', error)
    } finally {
      setLoading(false)
    }
  }

  const fetchClients = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts/clients`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const clientsData = await response.json()
        setClients(clientsData || [])
      }
    } catch (error) {
      console.error('Error fetching clients:', error)
    }
  }

  const fetchStats = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts/statistics`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const data = await response.json()
        setStats(data)
      }
    } catch (error) {
      console.error('Error fetching stats:', error)
    }
  }

  const handleClientSelect = (client: Client) => {
    setSelectedClient(client)
    setClientSearchTerm(client.ragione_sociale || client.name || '')
    setShowClientSuggestions(false)
    setFormData({
      ...formData,
      client_id: client.id.toString(),
      partner_ragione_sociale: client.ragione_sociale || client.name || '',
      partner_piva: client.piva || client.vat_number || '',
      partner_sede_legale: client.indirizzo || client.address || '',
      partner_iban: client.iban || '',
      partner_email: client.email || '',
      partner_legale_rappresentante: client.legal_representative || ''
    })
  }

  const handleClientSearchChange = (value: string) => {
    setClientSearchTerm(value)
    setShowClientSuggestions(value.length > 0)
    if (value.length === 0) {
      setSelectedClient(null)
      setFormData({
        ...formData,
        client_id: '',
        partner_ragione_sociale: '',
        partner_piva: '',
        partner_sede_legale: '',
        partner_iban: '',
        partner_email: '',
        partner_legale_rappresentante: ''
      })
    }
  }

  const filteredClients = clients.filter(client => {
    const name = (client.ragione_sociale || client.name || '').toLowerCase()
    const search = clientSearchTerm.toLowerCase()
    return name.includes(search)
  })

  const handleCreateContract = async () => {
    if (!formData.client_id || !formData.title || !formData.start_date) {
      alert('Per favore compila tutti i campi obbligatori')
      return
    }

    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          title: formData.title,
          client_id: parseInt(formData.client_id),
          start_date: formData.start_date,
          end_date: formData.end_date,
          value: formData.value ? parseFloat(formData.value) : null,
          partner_ragione_sociale: formData.partner_ragione_sociale,
          partner_piva: formData.partner_piva,
          partner_sede_legale: formData.partner_sede_legale,
          partner_iban: formData.partner_iban,
          partner_legale_rappresentante: formData.partner_legale_rappresentante,
          partner_email: formData.partner_email,
          periodo_mesi: parseInt(formData.periodo_mesi),
          territorio: formData.territorio,
          site_name: formData.site_name,
          site_address: formData.site_address,
          costo_attivazione: parseFloat(formData.costo_attivazione),
          servizio_ritiro: parseFloat(formData.servizio_ritiro),
          servizio_principale: parseFloat(formData.servizio_principale),
          ordine_rifiutato: parseFloat(formData.ordine_rifiutato),
          abbonamento_consegne: parseFloat(formData.abbonamento_consegne),
          inserimento_manuale: parseFloat(formData.inserimento_manuale),
          attrezzatura_fornita: formData.attrezzatura_fornita,
          miglior_prezzo_garantito: formData.miglior_prezzo_garantito
        })
      })
      
      const data = await response.json()
      
      if (response.ok && data.success) {
        alert('✅ Contratto creato con successo!')
        setShowCreateModal(false)
        resetForm()
        fetchContracts()
        fetchStats()
      } else {
        alert('Errore: ' + (data.message || 'Impossibile creare il contratto'))
      }
    } catch (error) {
      console.error('Error creating contract:', error)
      alert('Errore durante la creazione del contratto')
    }
  }

  const resetForm = () => {
    setClientSearchTerm('')
    setSelectedClient(null)
    setShowClientSuggestions(false)
    setFormData({
      title: '',
      client_id: '',
      contract_type: 'servizio',
      start_date: '',
      end_date: '',
      value: '',
      partner_ragione_sociale: '',
      partner_piva: '',
      partner_sede_legale: '',
      partner_iban: '',
      partner_legale_rappresentante: '',
      partner_email: '',
      periodo_mesi: '12',
      territorio: 'Italia',
      site_name: '',
      site_address: '',
      costo_attivazione: '150',
      servizio_ritiro: '12.00',
      servizio_principale: '2.98',
      ordine_rifiutato: '1.49',
      abbonamento_consegne: '24.00',
      inserimento_manuale: '1.49',
      attrezzatura_fornita: true,
      miglior_prezzo_garantito: false
    })
  }

  const handleDownloadPdf = async (contractId: number) => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts/${contractId}/pdf/download`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf'
        }
      })
      
      if (response.ok) {
        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `contratto_${contractId}.pdf`
        document.body.appendChild(a)
        a.click()
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)
      } else {
        alert('Errore durante il download del PDF')
      }
    } catch (error) {
      console.error('Error downloading PDF:', error)
      alert('Errore durante il download')
    }
  }

  const handleViewPdf = (contractId: number) => {
    const token = localStorage.getItem('token')
    const url = `${API_URL}/contracts/${contractId}/pdf/view?token=${token}`
    window.open(url, '_blank')
  }

  const handleSendForSignature = async (contractId: number) => {
    if (!confirm('Inviare il contratto via email per la firma?')) return
    
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts/${contractId}/send-for-signature`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      
      const data = await response.json()
      
      if (response.ok && data.success) {
        alert('✅ Inviti per firma inviati con successo!')
        fetchContracts()
      } else {
        alert('Errore: ' + (data.message || 'Impossibile inviare gli inviti'))
      }
    } catch (error) {
      console.error('Error sending contract:', error)
      alert('Errore durante l\'invio del contratto')
    }
  }

  const getStatusColor = (status: ContractStatus) => {
    switch (status) {
      case 'active': return 'bg-green-500/20 text-green-400 border-green-500/30'
      case 'signed': return 'bg-primary-500/20 text-primary-400 border-primary-500/30'
      case 'pending_signature': 
      case 'ready_to_sign': return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
      case 'expired': 
      case 'terminated': return 'bg-red-500/20 text-red-400 border-red-500/30'
      case 'draft': return 'bg-slate-500/20 text-gray-500 border-gray-300/30'
      case 'cancelled': return 'bg-gray-500/20 text-gray-400 border-gray-500/30'
      default: return 'bg-slate-500/20 text-gray-500 border-gray-300/30'
    }
  }

  const getStatusIcon = (status: ContractStatus) => {
    switch (status) {
      case 'active': return CheckCircle
      case 'signed': return CheckCircle
      case 'pending_signature': 
      case 'ready_to_sign': return AlertCircle
      case 'expired': 
      case 'terminated': return AlertCircle
      case 'draft': return Clock
      case 'cancelled': return AlertCircle
      default: return Clock
    }
  }

  const getStatusLabel = (status: ContractStatus) => {
    switch (status) {
      case 'active': return 'Attivo'
      case 'signed': return 'Firmato'
      case 'pending_signature': return 'In Attesa Firma'
      case 'ready_to_sign': return 'Pronto per Firma'
      case 'expired': return 'Scaduto'
      case 'terminated': return 'Terminato'
      case 'draft': return 'Bozza'
      case 'cancelled': return 'Annullato'
      default: return status
    }
  }

  const formatDate = (dateString: string) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('it-IT')
  }

  const formatCurrency = (value?: number) => {
    if (value === undefined || value === null) return '-'
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value)
  }

  const getDaysUntilExpiration = (endDate: string) => {
    const end = new Date(endDate)
    const today = new Date()
    const diffTime = end.getTime() - today.getTime()
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  }

  const filteredContracts = contracts
    .filter(contract => statusFilter === 'all' || contract.status === statusFilter)
    .filter(contract =>
      searchTerm === '' ||
      (contract.contract_number && contract.contract_number.toLowerCase().includes(searchTerm.toLowerCase())) ||
      (contract.title && contract.title.toLowerCase().includes(searchTerm.toLowerCase())) ||
      (contract.client?.ragione_sociale && contract.client.ragione_sociale.toLowerCase().includes(searchTerm.toLowerCase())) ||
      (contract.client?.name && contract.client.name.toLowerCase().includes(searchTerm.toLowerCase()))
    )

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const response = await contractsApi.export()
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `contratti_${new Date().toISOString().split('T')[0]}.csv`)
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

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap justify-between items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold">
            <span className="text-gradient">Contratti</span>
          </h1>
          <p className="text-gray-500 mt-1">Gestione contratti e documenti legali</p>
        </div>
        <div className="flex flex-wrap gap-3">
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleExportCSV}
            disabled={exporting}
            className="glass-button flex items-center gap-2 disabled:opacity-50"
          >
            <Download className={`w-4 h-4 ${exporting ? 'animate-pulse' : ''}`} />
            {exporting ? 'Esportazione...' : 'Esporta CSV'}
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            className="glass-button"
          >
            <Upload className="w-5 h-5" />
            Carica
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            className="glass-button-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <Plus className="w-5 h-5" />
            Nuovo Contratto
          </motion.button>
        </div>
      </div>

      {/* Expiring Contracts Alert */}
      {showExpiringAlert && expiringContracts.length > 0 && (
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card p-4 border-l-4 border-yellow-500 bg-yellow-500/10"
        >
          <div className="flex items-start gap-3">
            <AlertTriangle className="w-6 h-6 text-yellow-400 flex-shrink-0 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-semibold text-yellow-400">Contratti in Scadenza</h3>
              <p className="text-gray-500 text-sm mt-1">
                {expiringContracts.length} contratt{expiringContracts.length === 1 ? 'o' : 'i'} in scadenza nei prossimi 30 giorni:
              </p>
              <ul className="mt-2 space-y-1">
                {expiringContracts.slice(0, 5).map(contract => (
                  <li key={contract.id} className="text-sm text-gray-600 flex items-center gap-2">
                    <Bell className="w-4 h-4 text-yellow-400" />
                    <span className="font-medium">{contract.client?.ragione_sociale || contract.client?.name || 'N/A'}</span>
                    <span className="text-gray-400">-</span>
                    <span>{contract.title}</span>
                    <span className="text-gray-400">-</span>
                    <span className="text-yellow-400">{getDaysUntilExpiration(contract.end_date)} giorni</span>
                  </li>
                ))}
                {expiringContracts.length > 5 && (
                  <li className="text-sm text-gray-500">...e altri {expiringContracts.length - 5}</li>
                )}
              </ul>
            </div>
            <button 
              onClick={() => setShowExpiringAlert(false)}
              className="text-gray-500 hover:text-gray-600"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </motion.div>
      )}

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-6">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="glass-card p-6"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Contratti Attivi</p>
              <p className="text-2xl font-bold mt-1 text-green-400">{stats.active}</p>
            </div>
            <CheckCircle className="w-8 h-8 text-green-400" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="glass-card p-6"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">In Attesa Firma</p>
              <p className="text-2xl font-bold mt-1 text-yellow-400">{stats.pending_signature}</p>
            </div>
            <AlertCircle className="w-8 h-8 text-yellow-400" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="glass-card p-6 cursor-pointer hover:border-yellow-500/50"
          onClick={() => setStatusFilter('active')}
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">In Scadenza (30gg)</p>
              <p className="text-2xl font-bold mt-1 text-orange-400">{expiringContracts.length}</p>
            </div>
            <AlertTriangle className="w-8 h-8 text-orange-400" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="glass-card p-6"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Scaduti</p>
              <p className="text-2xl font-bold mt-1 text-red-400">{stats.expired}</p>
            </div>
            <AlertCircle className="w-8 h-8 text-red-400" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.5 }}
          className="glass-card p-6"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">Totale</p>
              <p className="text-2xl font-bold mt-1">{stats.total}</p>
            </div>
            <Briefcase className="w-8 h-8 text-primary-400" />
          </div>
        </motion.div>
      </div>

      {/* Filters */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.6 }}
        className="glass-card p-6"
      >
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5" />
              <input
                type="text"
                placeholder="Cerca per titolo, cliente, numero contratto..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="glass-input pl-10"
              />
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value as ContractStatus | 'all')}
              className="glass-input min-w-[180px]"
            >
              <option value="all">Tutti gli stati</option>
              <option value="active">Attivo</option>
              <option value="pending_signature">In Attesa Firma</option>
              <option value="ready_to_sign">Pronto per Firma</option>
              <option value="signed">Firmato</option>
              <option value="draft">Bozza</option>
              <option value="expired">Scaduto</option>
              <option value="terminated">Terminato</option>
              <option value="cancelled">Annullato</option>
            </select>
          </div>
        </div>
      </motion.div>

      {/* Loading State */}
      {loading && (
        <div className="flex justify-center items-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
        </div>
      )}

      {/* Empty State */}
      {!loading && contracts.length === 0 && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.7 }}
          className="glass-card p-12 text-center"
        >
          <Briefcase className="w-16 h-16 text-gray-500 mx-auto mb-4" />
          <h3 className="text-xl font-semibold mb-2">Nessun Contratto</h3>
          <p className="text-gray-500 mb-6">
            Carica o crea il tuo primo contratto per iniziare
          </p>
          <div className="flex gap-3 justify-center">
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className="glass-button"
            >
              <Upload className="w-5 h-5" />
              Carica Documento
            </motion.button>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className="glass-button-primary"
              onClick={() => setShowCreateModal(true)}
            >
              <Plus className="w-5 h-5" />
              Crea da Template
            </motion.button>
          </div>
        </motion.div>
      )}

      {/* Contracts List */}
      {!loading && contracts.length > 0 && (
        <div className="space-y-4">
          {filteredContracts.length === 0 ? (
            <div className="glass-card p-8 text-center">
              <Search className="w-12 h-12 text-gray-500 mx-auto mb-4" />
              <h3 className="text-lg font-semibold mb-2">Nessun risultato</h3>
              <p className="text-gray-500">Prova a modificare i filtri di ricerca</p>
            </div>
          ) : (
            filteredContracts.map((contract) => {
              const StatusIcon = getStatusIcon(contract.status)
              const daysUntilExp = contract.end_date ? getDaysUntilExpiration(contract.end_date) : null
              const isExpiringSoon = daysUntilExp !== null && daysUntilExp > 0 && daysUntilExp <= 30
              
              return (
                <motion.div
                  key={contract.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  whileHover={{ scale: 1.01 }}
                  className={`glass-card p-6 cursor-pointer ${isExpiringSoon ? 'border-yellow-500/30' : ''}`}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex items-start gap-4">
                      <div className={`p-3 rounded-lg ${getStatusColor(contract.status)}`}>
                        <FileText className="w-6 h-6" />
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <h3 className="font-semibold text-lg">{contract.title}</h3>
                          {contract.contract_number && (
                            <span className="text-xs text-gray-400">#{contract.contract_number}</span>
                          )}
                        </div>
                        <p className="text-gray-500 text-sm mt-1">
                          {contract.client?.ragione_sociale || contract.client?.name || 'Cliente non associato'}
                        </p>
                        <div className="flex items-center gap-4 mt-2 text-sm text-gray-400">
                          <span className="flex items-center gap-1">
                            <Calendar className="w-4 h-4" />
                            {formatDate(contract.start_date)} - {formatDate(contract.end_date)}
                          </span>
                          {contract.value && (
                            <span className="text-primary-400 font-medium">
                              {formatCurrency(contract.value)}
                            </span>
                          )}
                          {isExpiringSoon && (
                            <span className="flex items-center gap-1 text-yellow-400">
                              <AlertTriangle className="w-4 h-4" />
                              Scade tra {daysUntilExp} giorni
                            </span>
                          )}
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className={`px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(contract.status)}`}>
                        <StatusIcon className="w-3 h-3 inline-block mr-1" />
                        {getStatusLabel(contract.status)}
                      </span>
                      <div className="flex gap-2">
                        {contract.pdf_path && (
                          <>
                            <button
                              onClick={() => handleViewPdf(contract.id)}
                              className="p-2 glass-button-small"
                              title="Visualizza PDF"
                            >
                              <Eye className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => handleDownloadPdf(contract.id)}
                              className="p-2 glass-button-small"
                              title="Scarica PDF"
                            >
                              <Download className="w-4 h-4" />
                            </button>
                          </>
                        )}
                        {(contract.status === 'draft' || contract.status === 'ready_to_sign') && (
                          <button
                            onClick={() => handleSendForSignature(contract.id)}
                            className="p-2 glass-button-small text-primary-400"
                            title="Invia per Firma"
                          >
                            <Send className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                  
                  {/* Signatures Section */}
                  {contract.signatures && contract.signatures.length > 0 && (
                    <div className="mt-4 pt-4 border-t border-gray-200/50">
                      <p className="text-sm text-gray-500 mb-2">Firme richieste:</p>
                      <div className="flex flex-wrap gap-2">
                        {contract.signatures.map((sig) => (
                          <span 
                            key={sig.id}
                            className={`px-2 py-1 rounded text-xs ${
                              sig.status === 'signed' 
                                ? 'bg-green-500/20 text-green-400' 
                                : sig.status === 'declined'
                                ? 'bg-red-500/20 text-red-400'
                                : 'bg-slate-500/20 text-gray-500'
                            }`}
                          >
                            {sig.signer_name}: {sig.status === 'signed' ? 'Firmato' : sig.status === 'declined' ? 'Rifiutato' : 'In attesa'}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}
                </motion.div>
              )
            })
          )}
        </div>
      )}

      {/* Create Contract Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="glass-card p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto"
          >
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-xl font-bold">Nuovo Contratto</h2>
              <button 
                onClick={() => {
                  setShowCreateModal(false)
                  resetForm()
                }}
                className="text-gray-500 hover:text-gray-900 dark:hover:text-white"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="space-y-6">
              {/* Client Search */}
              <div className="client-search-container relative">
                <label className="block text-sm font-medium mb-2">Cliente *</label>
                <input
                  type="text"
                  placeholder="Cerca cliente..."
                  value={clientSearchTerm}
                  onChange={(e) => handleClientSearchChange(e.target.value)}
                  className="glass-input w-full"
                />
                {showClientSuggestions && filteredClients.length > 0 && (
                  <div className="absolute z-10 w-full mt-1 glass-card max-h-48 overflow-y-auto">
                    {filteredClients.slice(0, 10).map((client) => (
                      <div
                        key={client.id}
                        onClick={() => handleClientSelect(client)}
                        className="p-3 hover:bg-gray-100/50 cursor-pointer border-b border-gray-200/50 last:border-b-0"
                      >
                        <p className="font-medium">{client.ragione_sociale || client.name}</p>
                        <p className="text-sm text-gray-500">{client.email}</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Title */}
              <div>
                <label className="block text-sm font-medium mb-2">Titolo Contratto *</label>
                <input
                  type="text"
                  placeholder="Es: Contratto di Servizio Oppla"
                  value={formData.title}
                  onChange={(e) => setFormData({...formData, title: e.target.value})}
                  className="glass-input w-full"
                />
              </div>

              {/* Dates */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-2">Data Inizio *</label>
                  <input
                    type="date"
                    value={formData.start_date}
                    onChange={(e) => setFormData({...formData, start_date: e.target.value})}
                    className="glass-input w-full"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-2">Data Fine</label>
                  <input
                    type="date"
                    value={formData.end_date}
                    onChange={(e) => setFormData({...formData, end_date: e.target.value})}
                    className="glass-input w-full"
                  />
                </div>
              </div>

              {/* Value and Duration */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-2">Valore (€)</label>
                  <input
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    value={formData.value}
                    onChange={(e) => setFormData({...formData, value: e.target.value})}
                    className="glass-input w-full"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-2">Durata (mesi)</label>
                  <select
                    value={formData.periodo_mesi}
                    onChange={(e) => setFormData({...formData, periodo_mesi: e.target.value})}
                    className="glass-input w-full"
                  >
                    <option value="6">6 mesi</option>
                    <option value="12">12 mesi</option>
                    <option value="24">24 mesi</option>
                    <option value="36">36 mesi</option>
                  </select>
                </div>
              </div>

              {/* Partner Info (auto-filled from client) */}
              {selectedClient && (
                <div className="p-4 bg-gray-50/50 rounded-lg">
                  <h4 className="font-medium mb-3 text-primary-400">Dati Partner (da cliente)</h4>
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="text-gray-500">Ragione Sociale:</span>
                      <p className="font-medium">{formData.partner_ragione_sociale || '-'}</p>
                    </div>
                    <div>
                      <span className="text-gray-500">P.IVA:</span>
                      <p className="font-medium">{formData.partner_piva || '-'}</p>
                    </div>
                    <div>
                      <span className="text-gray-500">Email:</span>
                      <p className="font-medium">{formData.partner_email || '-'}</p>
                    </div>
                    <div>
                      <span className="text-gray-500">Sede:</span>
                      <p className="font-medium">{formData.partner_sede_legale || '-'}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* Action Buttons */}
              <div className="flex gap-3 justify-end pt-4 border-t border-gray-200">
                <button
                  onClick={() => {
                    setShowCreateModal(false)
                    resetForm()
                  }}
                  className="glass-button"
                >
                  Annulla
                </button>
                <button
                  onClick={handleCreateContract}
                  className="glass-button-primary"
                  disabled={!formData.client_id || !formData.title || !formData.start_date}
                >
                  <Plus className="w-5 h-5" />
                  Crea Contratto
                </button>
              </div>
            </div>
          </motion.div>
        </div>
      )}
    </div>
  )
}
