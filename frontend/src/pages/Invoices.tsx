import { motion } from 'framer-motion'
import { useState, useEffect, useRef } from 'react'
import { 
  Search, 
  Plus, 
  Filter,
  FileText,
  Download,
  Send,
  CheckCircle,
  Clock,
  AlertCircle,
  Euro,
  Edit,
  Trash2,
  Save,
  Link as LinkIcon,
  RefreshCw
} from 'lucide-react'
import Modal from '../components/Modal'
import { invoicesApi, clientsApi, api } from '../utils/api'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

type InvoiceStatus = 'bozza' | 'emessa' | 'pagata' | 'scaduta'
type InvoiceType = 'ordinaria' | 'differita'
type InvoiceDirection = 'attiva' | 'passiva' | 'all'

interface Invoice {
  id: number
  numero_fattura: string
  numero_fattura_completo?: string
  anno?: number
  data_emissione: string
  client_id: number
  client_name: string
  importo_imponibile: number
  importo_iva: number
  importo_totale: number
  status: InvoiceStatus
  type: 'attiva' | 'passiva'  // Direzione fattura
  invoice_type: InvoiceType  // Tipo fattura (ordinaria/differita)
  fic_document_id?: number | null
  sdi_sent_at?: string | null
  payment_status?: string
}

const generateInvoiceNumber = () => {
  const year = new Date().getFullYear()
  const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0')
  return `FT${year}${random}`
}

type SortColumn = 'numero' | 'cliente' | 'data' | 'importo' | 'tipo' | 'stato'
type SortDirection = 'asc' | 'desc'

export default function Invoices() {
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<InvoiceStatus | 'all'>('all')
  const [typeFilter, setTypeFilter] = useState<InvoiceType | 'all'>('all')
  const [directionFilter, setDirectionFilter] = useState<InvoiceDirection>('all')
  const [yearFilter, setYearFilter] = useState<number | 'all'>(2026)
  const [sortColumn, setSortColumn] = useState<SortColumn>('numero')
  const [sortDirection, setSortDirection] = useState<SortDirection>('desc')
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [syncing, setSyncing] = useState(false)
  const [exporting, setExporting] = useState(false)
  const [editingInvoice, setEditingInvoice] = useState<Invoice | null>(null)
  const [invoices, setInvoices] = useState<Invoice[]>([])
  const [clients, setClients] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [ficConnected, setFicConnected] = useState(false)
  const [ficError, setFicError] = useState<string | null>(null)
  const [ficSuccess, setFicSuccess] = useState<string | null>(null)
  const [showSyncWarning, setShowSyncWarning] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [itemsPerPage, setItemsPerPage] = useState(20)
  const [selectedInvoices, setSelectedInvoices] = useState<number[]>([])
  const [bulkDateModalOpen, setBulkDateModalOpen] = useState(false)
  const [bulkDate, setBulkDate] = useState(new Date().toISOString().split('T')[0])
  const [deferredModalOpen, setDeferredModalOpen] = useState(false)
  const [deferredMonth, setDeferredMonth] = useState(new Date().getMonth() === 0 ? 12 : new Date().getMonth())
  const [deferredYear, setDeferredYear] = useState(new Date().getMonth() === 0 ? new Date().getFullYear() - 1 : new Date().getFullYear())
  const [generatingDeferred, setGeneratingDeferred] = useState(false)
  const [clientSearchTerm, setClientSearchTerm] = useState('')
  const [showClientDropdown, setShowClientDropdown] = useState(false)
  const [formData, setFormData] = useState({
    numero_fattura: generateInvoiceNumber(),
    data_emissione: new Date().toISOString().split('T')[0],
    client_id: '',
    client_name: '',
    importo_imponibile: '',
    importo_iva: '',
    type: 'attiva' as InvoiceDirection,
    invoice_type: 'ordinaria' as InvoiceType,
  })

  const clientDropdownRef = useRef<HTMLDivElement>(null)

  // Carica fatture dal backend Laravel
  useEffect(() => {
    const loadData = async () => {
      setLoading(true)
      try {
        // Carica fatture dal backend
        const invoicesResponse = await invoicesApi.getAll()
        setInvoices(invoicesResponse.data.data || [])
        
        // Carica clienti
        const clientsResponse = await clientsApi.getAll()
        setClients(clientsResponse.data.data || [])
        
        // Verifica connessione Fatture in Cloud
        try {
          const ficResponse = await api.get('/fatture-in-cloud/status')
          setFicConnected(ficResponse.data.connected)
        } catch (error) {
          console.error('Error checking FIC status:', error)
          setFicConnected(false)
        }
        
        // Check for FIC OAuth callback results in URL
        const urlParams = new URLSearchParams(window.location.search)
        if (urlParams.has('fic_connected')) {
          setFicSuccess('Fatture in Cloud connesso con successo!')
          setFicConnected(true)
          // Remove query params from URL
          window.history.replaceState({}, '', window.location.pathname)
          setTimeout(() => setFicSuccess(null), 5000)
        } else if (urlParams.has('fic_error')) {
          const errorCode = urlParams.get('fic_error')
          const errorMessage = urlParams.get('message') || ''
          const debugData = urlParams.get('debug') || ''
          const errorMessages: Record<string, string> = {
            'not_authenticated': 'Autenticazione fallita. Effettua il login e riprova.',
            'authorization_failed': 'Autorizzazione fallita. Riprova.',
            'missing_state': 'Errore di sicurezza (state mancante). Riprova.',
            'invalid_state': 'Sessione scaduta. Riprova la connessione.',
            'no_code': 'Nessun codice di autorizzazione ricevuto.',
            'token_exchange_failed': 'Impossibile ottenere il token di accesso.',
            'no_companies': 'Nessuna azienda trovata su Fatture in Cloud.',
            'invalid_companies_format': 'Formato risposta aziende non valido.',
            'invalid_company_structure': 'Struttura dati azienda non valida.',
            'invalid_company_data': debugData 
              ? `Dati azienda incompleti. L'API ha restituito: ${debugData}. Contatta il supporto con questi dettagli.`
              : 'Dati azienda incompleti. ID non trovato nella risposta API.',
            'callback_failed': `Errore durante il callback OAuth: ${errorMessage}`,
          }
          setFicError(errorMessages[errorCode || ''] || `Errore OAuth: ${errorCode}`)
          // Remove query params from URL
          window.history.replaceState({}, '', window.location.pathname)
          setTimeout(() => setFicError(null), 10000)
        }
        
      } catch (error) {
        console.error('Error loading data:', error)
        // Fallback a array vuoto in caso di errore
        setInvoices([])
        setClients([])
      } finally {
        setLoading(false)
      }
    }
    
    loadData()
  }, [])

  // Aggiorna nome cliente quando viene selezionato
  useEffect(() => {
    if (formData.client_id) {
      const selectedClient = clients.find(c => c.id.toString() === formData.client_id)
      if (selectedClient) {
        setFormData(prev => ({ ...prev, client_name: selectedClient.ragione_sociale }))
      }
    }
  }, [formData.client_id, clients])

  // Filtra clienti per autocomplete
  const filteredClients = clients.filter(client =>
    client.ragione_sociale?.toLowerCase().includes(clientSearchTerm.toLowerCase()) ||
    client.email?.toLowerCase().includes(clientSearchTerm.toLowerCase())
  ).slice(0, 10) // Mostra max 10 risultati

  // Chiudi dropdown quando si clicca fuori
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (clientDropdownRef.current && !clientDropdownRef.current.contains(event.target as Node)) {
        setShowClientDropdown(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    
    try {
      if (editingInvoice) {
        await invoicesApi.update(editingInvoice.id, formData)
      } else {
        await invoicesApi.create(formData)
      }
      
      // Ricarica le fatture
      const invoicesResponse = await invoicesApi.getAll()
      setInvoices(invoicesResponse.data.data || [])
      
      setIsModalOpen(false)
      resetForm()
    } catch (error) {
      console.error('Error saving invoice:', error)
      alert('Errore nel salvare la fattura')
    } finally {
      setSubmitting(false)
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm('Sei sicuro di voler eliminare questa fattura?')) return

    try {
      await invoicesApi.delete(id)

      // Ricarica le fatture
      const invoicesResponse = await invoicesApi.getAll()
      setInvoices(invoicesResponse.data.data || [])
    } catch (error) {
      console.error('Error deleting invoice:', error)
      alert('Errore nell\'eliminare la fattura')
    }
  }

  const handleBulkDelete = async () => {
    if (selectedInvoices.length === 0) return

    // Filtra fatture che non possono essere eliminate (già inviate a FIC/SDI o pagate)
    const deletableInvoices = selectedInvoices.filter(id => {
      const inv = invoices.find(i => i.id === id)
      return inv && !inv.fic_document_id && !inv.sdi_sent_at && inv.payment_status !== 'pagata'
    })

    if (deletableInvoices.length === 0) {
      alert('Nessuna delle fatture selezionate può essere eliminata (già inviate a FIC/SDI o pagate)')
      return
    }

    const skipped = selectedInvoices.length - deletableInvoices.length
    const msg = skipped > 0
      ? `Eliminare ${deletableInvoices.length} fatture? (${skipped} saltate perché già inviate/pagate)`
      : `Eliminare ${deletableInvoices.length} fatture selezionate?`

    if (!confirm(msg)) return

    try {
      await Promise.all(deletableInvoices.map(id => invoicesApi.delete(id)))
      setSelectedInvoices([])
      const invoicesResponse = await invoicesApi.getAll()
      setInvoices(invoicesResponse.data.data || [])
    } catch (error) {
      console.error('Error bulk deleting invoices:', error)
      alert('Errore durante l\'eliminazione')
    }
  }

  const resetForm = () => {
    setFormData({
      numero_fattura: generateInvoiceNumber(),
      data_emissione: new Date().toISOString().split('T')[0],
      client_id: '',
      client_name: '',
      importo_imponibile: '',
      importo_iva: '',
      type: 'attiva',
      invoice_type: 'ordinaria',
    })
    setEditingInvoice(null)
    setClientSearchTerm('')
    setShowClientDropdown(false)
  }

  // Handler per modifica data in bulk
  const handleBulkDateUpdate = async () => {
    if (selectedInvoices.length === 0) {
      alert('Seleziona almeno una fattura')
      return
    }

    if (!confirm(`Aggiornare la data di ${selectedInvoices.length} fatture a ${new Date(bulkDate).toLocaleDateString('it-IT')}?`)) {
      return
    }

    setSubmitting(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/invoices/bulk-update-dates`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          invoice_ids: selectedInvoices,
          new_date: bulkDate
        })
      })

      const data = await response.json()

      if (data.success) {
        alert(`✅ Aggiornate ${data.data.updated_count} fatture`)
        // Ricarica fatture
        const invoicesResponse = await invoicesApi.getAll()
        setInvoices(invoicesResponse.data.data || [])
        setSelectedInvoices([])
        setBulkDateModalOpen(false)
      } else {
        alert(`Errore: ${data.message}`)
      }
    } catch (error) {
      console.error('Errore bulk update:', error)
      alert('Errore durante l\'aggiornamento delle date')
    } finally {
      setSubmitting(false)
    }
  }

  // Handler per selezione/deselezione tutte le fatture
  const toggleSelectAll = () => {
    if (selectedInvoices.length === paginatedInvoices.length) {
      setSelectedInvoices([])
    } else {
      setSelectedInvoices(paginatedInvoices.map(inv => inv.id))
    }
  }

  // Sync con Fatture in Cloud - Mostra warning prima
  const handleSyncFIC = async () => {
    setShowSyncWarning(true)
  }

  // Conferma sincronizzazione FIC dopo warning
  const confirmSyncFIC = async () => {
    setShowSyncWarning(false)
    setSyncing(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/invoices/sync-fic`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      })
      
      const data = await response.json()
      
      if (data.success) {
        const message = `✅ Sincronizzazione completata!
        
📊 Statistiche:
• Fatture create: ${data.data.created}
• Fatture aggiornate: ${data.data.updated}
• Fatture saltate: ${data.data.skipped} (cliente non trovato)
        
📋 Da Fatture in Cloud:
• Fatture attive: ${data.data.active_invoices}
• Fatture passive: ${data.data.passive_invoices}

${data.data.skipped > 0 ? '\n⚠️ Alcune fatture sono state saltate perché il cliente non è stato trovato nel database locale. Controlla i log per i dettagli.' : ''}`
        
        alert(message)
        
        // Ricarica fatture
        const invoicesResponse = await invoicesApi.getAll()
        setInvoices(invoicesResponse.data.data || [])
      } else {
        alert('Errore durante la sincronizzazione: ' + data.message)
      }
    } catch (error) {
      console.error('Errore sync FIC:', error)
      alert('Errore durante la sincronizzazione con Fatture in Cloud')
    } finally {
      setSyncing(false)
    }
  }

  // Genera fatture differite mensili
  const handleGenerateDeferred = async () => {
    setGeneratingDeferred(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/invoices/generate-monthly-deferred`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ month: deferredMonth, year: deferredYear })
      })

      const data = await response.json()

      if (data.success) {
        setDeferredModalOpen(false)
        alert(data.message)
        // Ricarica fatture
        const invoicesResponse = await invoicesApi.getAll()
        setInvoices(invoicesResponse.data.data || [])
      } else {
        alert('Errore: ' + data.message)
      }
    } catch (error) {
      console.error('Errore generazione fatture differite:', error)
      alert('Errore durante la generazione delle fatture differite')
    } finally {
      setGeneratingDeferred(false)
    }
  }

  // Handler per ordinamento
  const handleSort = (column: SortColumn) => {
    if (sortColumn === column) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortColumn(column)
      setSortDirection('asc')
    }
  }

  // Estrai anni disponibili dalle fatture
  const availableYears = Array.from(new Set(invoices.map(inv => inv.anno).filter(Boolean))).sort((a, b) => (b || 0) - (a || 0))

  // Filtra e ordina fatture
  const filteredInvoices = invoices
    .filter(invoice => {
      const matchesSearch = 
        invoice.numero_fattura?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        invoice.client_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (invoice.importo_totale || 0).toString().includes(searchTerm)
      
      const matchesStatus = statusFilter === 'all' || invoice.status === statusFilter
      const matchesType = typeFilter === 'all' || invoice.invoice_type === typeFilter
      const matchesDirection = directionFilter === 'all' || invoice.type === directionFilter
      const matchesYear = yearFilter === 'all' || invoice.anno === yearFilter
      
      return matchesSearch && matchesStatus && matchesType && matchesDirection && matchesYear
    })
    .sort((a, b) => {
      let comparison = 0
      
      switch (sortColumn) {
        case 'numero':
          // Prima ordina per anno (decrescente), poi per numero progressivo
          const yearA = a.anno || 0
          const yearB = b.anno || 0
          if (yearA !== yearB) {
            comparison = yearB - yearA  // Anni più recenti prima
          } else {
            // Stesso anno: ordina per numero progressivo
            const numA = parseInt(a.numero_fattura?.split('/')[0] || '0')
            const numB = parseInt(b.numero_fattura?.split('/')[0] || '0')
            comparison = numA - numB
          }
          break
        case 'cliente':
          comparison = (a.client_name || '').localeCompare(b.client_name || '')
          break
        case 'data':
          comparison = new Date(a.data_emissione).getTime() - new Date(b.data_emissione).getTime()
          break
        case 'importo':
          comparison = (a.importo_totale || 0) - (b.importo_totale || 0)
          break
        case 'tipo':
          comparison = (a.invoice_type || '').localeCompare(b.invoice_type || '')
          break
        case 'stato':
          const statusA = getDisplayStatus(a)
          const statusB = getDisplayStatus(b)
          comparison = statusA.localeCompare(statusB)
          break
      }
      
      return sortDirection === 'asc' ? comparison : -comparison
    })

  // Paginazione
  const totalPages = Math.ceil(filteredInvoices.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const paginatedInvoices = filteredInvoices.slice(startIndex, endIndex)

  // Reset alla pagina 1 quando cambiano i filtri
  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, statusFilter, typeFilter, directionFilter, yearFilter])

  const getDisplayStatus = (invoice: Invoice): InvoiceStatus | 'inviata' => {
    // Se ha sdi_sent_at o fic_document_id, mostra "Inviata"
    if (invoice.sdi_sent_at || invoice.fic_document_id) {
      return 'inviata'
    }
    return invoice.status
  }

  const getStatusColor = (status: InvoiceStatus | 'inviata') => {
    switch (status) {
      case 'bozza': return 'bg-slate-500/20 text-gray-500 border-gray-300/30'
      case 'inviata': return 'bg-purple-500/20 text-purple-400 border-purple-500/30'
      case 'emessa': return 'bg-primary-500/20 text-primary-400 border-primary-500/30'
      case 'pagata': return 'bg-green-500/20 text-green-400 border-green-500/30'
      case 'scaduta': return 'bg-red-500/20 text-red-400 border-red-500/30'
    }
  }

  const getStatusIcon = (status: InvoiceStatus | 'inviata') => {
    switch (status) {
      case 'bozza': return Clock
      case 'inviata': return CheckCircle
      case 'emessa': return Send
      case 'pagata': return CheckCircle
      case 'scaduta': return AlertCircle
    }
  }

  const getStatusLabel = (status: InvoiceStatus | 'inviata') => {
    switch (status) {
      case 'bozza': return 'Bozza'
      case 'inviata': return 'Inviata'
      case 'emessa': return 'Emessa'
      case 'pagata': return 'Pagata'
      case 'scaduta': return 'Scaduta'
    }
  }

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const params: any = {}
      if (statusFilter !== 'all') params.status = statusFilter
      if (typeFilter !== 'all') params.type = typeFilter
      if (directionFilter !== 'all') params.direction = directionFilter
      if (yearFilter !== 'all') params.year = yearFilter

      const response = await invoicesApi.export(params)
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `fatture_${new Date().toISOString().split('T')[0]}.csv`)
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
      {/* FIC Connection Status Messages */}
      {ficSuccess && (
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0 }}
          className="glass-card p-4 bg-green-500/20 border-green-500/30"
        >
          <div className="flex items-center gap-3">
            <CheckCircle className="w-5 h-5 text-green-400" />
            <p className="text-green-400 font-medium">{ficSuccess}</p>
          </div>
        </motion.div>
      )}
      
      {ficError && (
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0 }}
          className="glass-card p-4 bg-red-500/20 border-red-500/30"
        >
          <div className="flex items-center gap-3">
            <AlertCircle className="w-5 h-5 text-red-400" />
            <div>
              <p className="text-red-400 font-medium">Errore connessione Fatture in Cloud</p>
              <p className="text-red-300 text-sm mt-1">{ficError}</p>
            </div>
          </div>
        </motion.div>
      )}

      {/* Header Actions */}
      <div className="flex flex-wrap gap-3">
        {selectedInvoices.length > 0 && (
          <>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={() => setBulkDateModalOpen(true)}
              className="glass-button bg-purple-500/20 border-purple-500/30 hover:bg-purple-500/30 text-purple-400"
            >
              <Edit className="w-5 h-5" />
              Modifica Date ({selectedInvoices.length})
            </motion.button>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={handleBulkDelete}
              className="glass-button bg-red-500/20 border-red-500/30 hover:bg-red-500/30 text-red-400"
            >
              <Trash2 className="w-5 h-5" />
              Elimina ({selectedInvoices.length})
            </motion.button>
          </>
        )}

        {!ficConnected && (
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => {
              // Aggiungi token come query param per autenticazione via redirect
              const token = localStorage.getItem('token')
              const backendUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'
              window.location.href = `${backendUrl}/api/fatture-in-cloud/authorize?token=${token}`
            }}
            className="glass-button bg-orange-500/20 border-orange-500/30 hover:bg-orange-500/30 text-orange-400"
          >
            <LinkIcon className="w-5 h-5" />
            Connetti Fatture in Cloud
          </motion.button>
        )}
        
        {ficConnected && (
          <>
            {/* Invia e Verifica Tutte */}
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={async () => {
                const unprocessed = invoices.filter(inv => !inv.sdi_sent_at && !inv.fic_document_id && inv.status === 'bozza')
                if (unprocessed.length === 0) {
                  alert('Nessuna fattura da inviare e verificare')
                  return
                }
                
                if (!confirm(`Inviare e verificare ${unprocessed.length} fatture?`)) return
                
                setSubmitting(true)
                const token = localStorage.getItem('token')
                let successSend = 0
                let successConfirm = 0
                let failed = 0
                const errors: string[] = []
                
                for (const invoice of unprocessed) {
                  try {
                    // Step 1: Invia a FIC/SDI
                    const sendResponse = await fetch(`${API_URL}/invoices/${invoice.id}/send-sdi`, {
                      method: 'POST',
                      headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                      }
                    })
                    const sendData = await sendResponse.json()
                    if (sendData.success) {
                      successSend++
                      
                      // Step 2: Verifica in FIC (solo se ha fic_document_id e non già inviato a SDI)
                      if (sendData.invoice?.fic_document_id && !sendData.invoice?.sdi_sent_at) {
                        const confirmResponse = await fetch(`${API_URL}/invoices/${invoice.id}/confirm`, {
                          method: 'POST',
                          headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`
                          }
                        })
                        const confirmData = await confirmResponse.json()
                        if (confirmData.success) {
                          successConfirm++
                        }
                      }
                    } else {
                      failed++
                      errors.push(`${invoice.numero_fattura}: ${sendData.message || 'Errore sconosciuto'}`)
                    }
                  } catch (error) {
                    failed++
                    errors.push(`${invoice.numero_fattura}: Errore di rete`)
                  }
                }
                
                setSubmitting(false)
                
                // Ricarica fatture
                const invoicesResponse = await invoicesApi.getAll()
                setInvoices(invoicesResponse.data.data || [])
                
                // Mostra risultato
                if (failed === 0) {
                  alert(`Inviate: ${successSend}\nVerificate: ${successConfirm}`)
                } else {
                  alert(`Inviate: ${successSend}\nVerificate: ${successConfirm}\nFallite: ${failed}\n\nErrori:\n${errors.join('\n')}`)
                }
              }}
              disabled={submitting || invoices.filter(inv => !inv.sdi_sent_at && !inv.fic_document_id && inv.status === 'bozza').length === 0}
              className="glass-button bg-green-500/20 border-green-500/30 hover:bg-green-500/30 text-green-400 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {submitting ? (
                <>
                  <div className="animate-spin w-5 h-5 border-2 border-green-400 border-t-transparent rounded-full"></div>
                  Elaborazione...
                </>
              ) : (
                <>
                  <CheckCircle className="w-5 h-5" />
                  Invia e Verifica Tutte ({invoices.filter(inv => !inv.sdi_sent_at && !inv.fic_document_id && inv.status === 'bozza').length})
                </>
              )}
            </motion.button>
          </>
        )}
        
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
          onClick={() => setDeferredModalOpen(true)}
          className="glass-button bg-purple-500/20 border-purple-500/30 hover:bg-purple-500/30 text-purple-400"
        >
          <FileText className="w-5 h-5" />
          Genera Differite
        </motion.button>

        <motion.button
          whileHover={{ scale: 1.02 }}
          whileTap={{ scale: 0.98 }}
          onClick={() => {
            resetForm()
            setIsModalOpen(true)
          }}
          className="glass-button-primary"
        >
          <Plus className="w-5 h-5" />
          Nuova Fattura
        </motion.button>

        {/* Sync FIC Button */}
        {ficConnected && (
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleSyncFIC}
            disabled={syncing}
            className="glass-button bg-orange-500/20 border-orange-500/30 hover:bg-orange-500/30 text-orange-400 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {syncing ? (
              <>
                <div className="animate-spin w-5 h-5 border-2 border-orange-400 border-t-transparent rounded-full"></div>
                Sincronizzazione...
              </>
            ) : (
              <>
                <RefreshCw className="w-5 h-5" />
                Sync FIC
              </>
            )}
          </motion.button>
        )}
      </div>

      {/* Filters */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className="glass-card p-6"
      >
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5" />
              <input
                type="text"
                placeholder="Cerca per numero, cliente, importo..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="glass-input pl-10"
              />
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            <select
              value={yearFilter}
              onChange={(e) => setYearFilter(e.target.value === 'all' ? 'all' : parseInt(e.target.value))}
              className="glass-input min-w-[140px]"
            >
              <option value="all">Tutti gli anni</option>
              {availableYears.map(year => (
                <option key={year} value={year}>{year}</option>
              ))}
            </select>
            <select
              value={directionFilter}
              onChange={(e) => setDirectionFilter(e.target.value as InvoiceDirection)}
              className="glass-input min-w-[140px]"
            >
              <option value="all">Tutte</option>
              <option value="attiva">Attive (Emesse)</option>
              <option value="passiva">Passive (Ricevute)</option>
            </select>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value as InvoiceStatus | 'all')}
              className="glass-input min-w-[140px]"
            >
              <option value="all">Tutti gli stati</option>
              <option value="bozza">Bozza</option>
              <option value="emessa">Emessa</option>
              <option value="pagata">Pagata</option>
              <option value="scaduta">Scaduta</option>
            </select>
            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value as InvoiceType | 'all')}
              className="glass-input min-w-[140px]"
            >
              <option value="all">Tutti i tipi</option>
              <option value="ordinaria">Ordinaria</option>
              <option value="differita">Differita</option>
            </select>
          </div>
        </div>
      </motion.div>

      {/* Invoices List or Empty State */}
      {loading ? (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.6 }}
          className="glass-card p-12 text-center"
        >
          <div className="flex items-center justify-center gap-3">
            <div className="animate-spin w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full"></div>
            <span className="text-gray-500">Caricamento fatture...</span>
          </div>
        </motion.div>
      ) : filteredInvoices.length === 0 ? (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.6 }}
          className="glass-card p-12 text-center"
        >
          <FileText className="w-16 h-16 text-gray-500 mx-auto mb-4" />
          <h3 className="text-xl font-semibold mb-2">
            {invoices.length === 0 ? 'Nessuna Fattura' : 'Nessun Risultato'}
          </h3>
          <p className="text-gray-500 mb-6">
            {invoices.length === 0 
              ? 'Inizia creando la tua prima fattura per i clienti'
              : 'Nessuna fattura trovata con i criteri di ricerca.'
            }
          </p>
          {invoices.length === 0 && (
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={() => setIsModalOpen(true)}
              className="glass-button-primary"
            >
              <Plus className="w-5 h-5" />
              Crea Prima Fattura
            </motion.button>
          )}
        </motion.div>
      ) : (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.6 }}
          className="glass-card overflow-hidden"
        >
          {/* Paginazione superiore */}
          {totalPages > 1 && (
            <div className="px-4 py-4 border-b border-gray-200 flex items-center justify-between">
              <div className="flex items-center gap-4">
                <span className="text-sm text-gray-500">
                  Mostrando {startIndex + 1}-{Math.min(endIndex, filteredInvoices.length)} di {filteredInvoices.length} fatture
                </span>
                <select
                  value={itemsPerPage}
                  onChange={(e) => {
                    setItemsPerPage(Number(e.target.value))
                    setCurrentPage(1)
                  }}
                  className="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white text-sm"
                >
                  <option value={10}>10 per pagina</option>
                  <option value={20}>20 per pagina</option>
                  <option value={50}>50 per pagina</option>
                  <option value={100}>100 per pagina</option>
                </select>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => setCurrentPage(1)}
                  disabled={currentPage === 1}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  «
                </button>
                <button
                  onClick={() => setCurrentPage(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  ‹
                </button>
                
                {[...Array(totalPages)].map((_, i) => {
                  const page = i + 1
                  if (
                    page === 1 ||
                    page === totalPages ||
                    (page >= currentPage - 2 && page <= currentPage + 2)
                  ) {
                    return (
                      <button
                        key={page}
                        onClick={() => setCurrentPage(page)}
                        className={`px-3 py-1 rounded-lg transition-colors ${
                          currentPage === page
                            ? 'bg-primary-600 text-white'
                            : 'glass-button hover:bg-gray-200'
                        }`}
                      >
                        {page}
                      </button>
                    )
                  } else if (page === currentPage - 3 || page === currentPage + 3) {
                    return <span key={page} className="text-gray-400">...</span>
                  }
                  return null
                })}

                <button
                  onClick={() => setCurrentPage(currentPage + 1)}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  ›
                </button>
                <button
                  onClick={() => setCurrentPage(totalPages)}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  »
                </button>
              </div>
            </div>
          )}

          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="border-b border-gray-200">
                <tr>
                  <th className="px-4 py-4 text-left">
                    <input
                      type="checkbox"
                      checked={paginatedInvoices.length > 0 && selectedInvoices.length === paginatedInvoices.length}
                      onChange={toggleSelectAll}
                      className="w-4 h-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-primary-500 focus:ring-offset-slate-800"
                    />
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold">
                    <button
                      onClick={() => handleSort('numero')}
                      className="flex items-center gap-2 hover:text-primary-400 transition-colors"
                    >
                      Numero
                      {sortColumn === 'numero' && (
                        <span>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </button>
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold">
                    <button
                      onClick={() => handleSort('cliente')}
                      className="flex items-center gap-2 hover:text-primary-400 transition-colors"
                    >
                      Cliente
                      {sortColumn === 'cliente' && (
                        <span>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </button>
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold">
                    <button
                      onClick={() => handleSort('data')}
                      className="flex items-center gap-2 hover:text-primary-400 transition-colors"
                    >
                      Data
                      {sortColumn === 'data' && (
                        <span>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </button>
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold">
                    <button
                      onClick={() => handleSort('importo')}
                      className="flex items-center gap-2 hover:text-primary-400 transition-colors"
                    >
                      Importo
                      {sortColumn === 'importo' && (
                        <span>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </button>
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold">
                    <button
                      onClick={() => handleSort('tipo')}
                      className="flex items-center gap-2 hover:text-primary-400 transition-colors"
                    >
                      Tipo
                      {sortColumn === 'tipo' && (
                        <span>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </button>
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold">
                    <button
                      onClick={() => handleSort('stato')}
                      className="flex items-center gap-2 hover:text-primary-400 transition-colors"
                    >
                      Stato
                      {sortColumn === 'stato' && (
                        <span>{sortDirection === 'asc' ? '↑' : '↓'}</span>
                      )}
                    </button>
                  </th>
                  <th className="px-6 py-4 text-right text-sm font-semibold">Azioni</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {paginatedInvoices.map((invoice) => {
                  const displayStatus = getDisplayStatus(invoice)
                  const StatusIcon = getStatusIcon(displayStatus)
                  const isSelected = selectedInvoices.includes(invoice.id)
                  return (
                    <tr key={invoice.id} className="hover:bg-white/5 transition-colors">
                      <td className="px-4 py-4">
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => {
                            if (isSelected) {
                              setSelectedInvoices(selectedInvoices.filter(id => id !== invoice.id))
                            } else {
                              setSelectedInvoices([...selectedInvoices, invoice.id])
                            }
                          }}
                          className="w-4 h-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-primary-500 focus:ring-offset-slate-800"
                        />
                      </td>
                      <td className="px-6 py-4 font-semibold">{invoice.numero_fattura_completo || invoice.numero_fattura}</td>
                      <td className="px-6 py-4">{invoice.client_name}</td>
                      <td className="px-6 py-4 text-gray-500">
                        {new Date(invoice.data_emissione).toLocaleDateString('it-IT')}
                      </td>
                      <td className="px-6 py-4 font-semibold">€ {(invoice.importo_totale || 0).toLocaleString('it-IT', { minimumFractionDigits: 2 })}</td>
                      <td className="px-6 py-4">
                        <span className="glass-badge">
                          {invoice.invoice_type === 'ordinaria' ? 'Ordinaria' : 'Differita'}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <span className={`glass-badge ${getStatusColor(displayStatus)}`}>
                          <StatusIcon className="w-3 h-3 mr-1 inline" />
                          {getStatusLabel(displayStatus)}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => {
                              setEditingInvoice(invoice)
                              setFormData({
                                numero_fattura: invoice.numero_fattura,
                                data_emissione: invoice.data_emissione,
                                client_id: invoice.client_id.toString(),
                                client_name: invoice.client_name,
                                importo_imponibile: invoice.importo_imponibile.toString(),
                                importo_iva: invoice.importo_iva.toString(),
                                type: invoice.type,
                                invoice_type: invoice.invoice_type,
                              })
                              setClientSearchTerm(invoice.client_name)
                              setIsModalOpen(true)
                            }}
                            className="glass-button p-2"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button 
                            onClick={async () => {
                              try {
                                const token = localStorage.getItem('token')
                                const response = await fetch(`${API_URL}/invoices/${invoice.id}/download-pdf-fic`, {
                                  headers: { 'Authorization': `Bearer ${token}` }
                                })
                                
                                if (!response.ok) {
                                  const errorData = await response.json().catch(() => ({}))
                                  throw new Error(errorData.message || 'Errore nel download del PDF da Fatture in Cloud')
                                }
                                
                                const blob = await response.blob()
                                const url = window.URL.createObjectURL(blob)
                                const link = document.createElement('a')
                                link.href = url
                                link.download = `fattura_${invoice.numero_fattura.replace(/\//g, '_')}.pdf`
                                document.body.appendChild(link)
                                link.click()
                                
                                // Cleanup
                                setTimeout(() => {
                                  document.body.removeChild(link)
                                  window.URL.revokeObjectURL(url)
                                }, 100)
                              } catch (error) {
                                console.error('Errore download:', error)
                                alert(`Errore durante il download: ${error instanceof Error ? error.message : 'Errore sconosciuto'}`)
                              }
                            }}
                            disabled={!invoice.fic_document_id}
                            className="glass-button p-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            title={invoice.fic_document_id ? 'Scarica PDF da Fatture in Cloud' : 'Disponibile solo dopo invio a FIC'}
                          >
                            <Download className="w-4 h-4" />
                          </button>
                          <button 
                            onClick={async () => {
                              if (!confirm('Confermare questa fattura in Fatture in Cloud?')) return
                              try {
                                const token = localStorage.getItem('token')
                                const response = await fetch(`${API_URL}/invoices/${invoice.id}/confirm`, {
                                  method: 'POST',
                                  headers: {
                                    'Content-Type': 'application/json',
                                    'Authorization': `Bearer ${token}`
                                  }
                                })
                                const data = await response.json()
                                if (data.success) {
                                  alert('Fattura confermata con successo!')
                                  // Ricarica fatture
                                  const invoicesResponse = await invoicesApi.getAll()
                                  setInvoices(invoicesResponse.data.data || [])
                                } else {
                                  alert('Errore: ' + (data.message || 'Conferma fallita'))
                                }
                              } catch (error) {
                                console.error('Errore conferma:', error)
                                alert('Errore durante la conferma della fattura')
                              }
                            }}
                            disabled={!invoice.fic_document_id || !!invoice.sdi_sent_at}
                            className="glass-button p-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            title={
                              !invoice.fic_document_id 
                                ? 'Crea prima la fattura in FIC' 
                                : invoice.sdi_sent_at 
                                ? 'Fattura già inviata al SDI' 
                                : 'Conferma fattura in Fatture in Cloud'
                            }
                          >
                            <CheckCircle className="w-4 h-4" />
                          </button>
                          <button 
                            onClick={async () => {
                              if (!confirm('Inviare questa fattura a Fatture in Cloud? (Senza invio SDI)')) return
                              try {
                                const token = localStorage.getItem('token')
                                const response = await fetch(`${API_URL}/invoices/${invoice.id}/send-to-fic`, {
                                  method: 'POST',
                                  headers: {
                                    'Content-Type': 'application/json',
                                    'Authorization': `Bearer ${token}`
                                  }
                                })
                                const data = await response.json()
                                if (data.success) {
                                  alert('Fattura creata in Fatture in Cloud!\n\nUsa "Verifica" per inviarla al SDI.')
                                  // Ricarica fatture
                                  const invoicesResponse = await invoicesApi.getAll()
                                  setInvoices(invoicesResponse.data.data || [])
                                } else {
                                  alert('Errore: ' + (data.message || 'Invio fallito'))
                                }
                              } catch (error) {
                                console.error('Errore invio:', error)
                                alert('Errore durante l\'invio della fattura a Fatture in Cloud')
                              }
                            }}
                            disabled={!!invoice.fic_document_id}
                            className="glass-button p-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            title={invoice.fic_document_id ? 'Fattura già presente in FIC' : 'Invia fattura a Fatture in Cloud'}
                          >
                            <Send className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleDelete(invoice.id)}
                            disabled={!!(invoice.fic_document_id || invoice.sdi_sent_at || invoice.payment_status === 'pagata')}
                            className="glass-button p-2 hover:bg-red-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
                            title={
                              invoice.fic_document_id || invoice.sdi_sent_at || invoice.payment_status === 'pagata'
                                ? 'Impossibile eliminare: fattura già inviata a FIC/SDI o pagata'
                                : 'Elimina fattura'
                            }
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>

          {/* Paginazione */}
          {totalPages > 1 && (
            <div className="mt-6 flex items-center justify-between px-4">
              <div className="flex items-center gap-4">
                <span className="text-sm text-gray-500">
                  Mostrando {startIndex + 1}-{Math.min(endIndex, filteredInvoices.length)} di {filteredInvoices.length} fatture
                </span>
                <select
                  value={itemsPerPage}
                  onChange={(e) => {
                    setItemsPerPage(Number(e.target.value))
                    setCurrentPage(1)
                  }}
                  className="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white text-sm"
                >
                  <option value={10}>10 per pagina</option>
                  <option value={20}>20 per pagina</option>
                  <option value={50}>50 per pagina</option>
                  <option value={100}>100 per pagina</option>
                </select>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => setCurrentPage(1)}
                  disabled={currentPage === 1}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  «
                </button>
                <button
                  onClick={() => setCurrentPage(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  ‹
                </button>
                
                {[...Array(totalPages)].map((_, i) => {
                  const page = i + 1
                  // Mostra solo le pagine vicine a quella corrente
                  if (
                    page === 1 ||
                    page === totalPages ||
                    (page >= currentPage - 2 && page <= currentPage + 2)
                  ) {
                    return (
                      <button
                        key={page}
                        onClick={() => setCurrentPage(page)}
                        className={`px-3 py-1 rounded-lg transition-colors ${
                          currentPage === page
                            ? 'bg-primary-600 text-white'
                            : 'glass-button hover:bg-gray-200'
                        }`}
                      >
                        {page}
                      </button>
                    )
                  } else if (page === currentPage - 3 || page === currentPage + 3) {
                    return <span key={page} className="text-gray-400">...</span>
                  }
                  return null
                })}

                <button
                  onClick={() => setCurrentPage(currentPage + 1)}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  ›
                </button>
                <button
                  onClick={() => setCurrentPage(totalPages)}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1 glass-button disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  »
                </button>
              </div>
            </div>
          )}
        </motion.div>
      )}

      {/* Create/Edit Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingInvoice ? 'Modifica Fattura' : 'Nuova Fattura'}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-2">Numero Fattura *</label>
              <input
                type="text"
                required
                value={formData.numero_fattura}
                onChange={(e) => setFormData({ ...formData, numero_fattura: e.target.value })}
                className="glass-input w-full"
                placeholder="2024/001"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Data Emissione *</label>
              <input
                type="date"
                required
                value={formData.data_emissione}
                onChange={(e) => setFormData({ ...formData, data_emissione: e.target.value })}
                className="glass-input w-full"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Tipo Fattura *</label>
              <select
                required
                value={formData.invoice_type}
                onChange={(e) => setFormData({ ...formData, invoice_type: e.target.value as InvoiceType })}
                className="glass-input w-full"
              >
                <option value="ordinaria">Ordinaria</option>
                <option value="differita">Differita</option>
              </select>
            </div>
            <div className="relative" ref={clientDropdownRef}>
              <label className="block text-sm font-medium mb-2">Cliente *</label>
              <input
                type="text"
                required
                value={clientSearchTerm}
                onChange={(e) => {
                  setClientSearchTerm(e.target.value)
                  setShowClientDropdown(true)
                  // Clear client_id when user types (must select from dropdown)
                  if (formData.client_id) {
                    setFormData({ ...formData, client_id: '', client_name: '' })
                  }
                }}
                onFocus={() => setShowClientDropdown(true)}
                placeholder="Cerca cliente per nome o email..."
                className="glass-input w-full"
              />
              {showClientDropdown && filteredClients.length > 0 && (
                <div className="absolute z-50 w-full mt-1 glass-card border border-gray-300 max-h-60 overflow-y-auto">
                  {filteredClients.map((client) => (
                    <button
                      key={client.id}
                      type="button"
                      onClick={() => {
                        setFormData({ ...formData, client_id: client.id.toString(), client_name: client.ragione_sociale })
                        setClientSearchTerm(client.ragione_sociale)
                        setShowClientDropdown(false)
                      }}
                      className="w-full px-4 py-2 text-left hover:bg-white/10 transition-colors border-b border-white/5 last:border-b-0"
                    >
                      <div className="font-medium">{client.ragione_sociale}</div>
                      <div className="text-sm text-gray-500">{client.email}</div>
                    </button>
                  ))}
                </div>
              )}
              {formData.client_id && (
                <input type="hidden" name="client_id" value={formData.client_id} />
              )}
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Imponibile * (€)</label>
              <input
                type="number"
                step="0.01"
                required
                value={formData.importo_imponibile}
                onChange={(e) => {
                  const imponibile = parseFloat(e.target.value) || 0
                  const iva = (imponibile * 0.22).toFixed(2) // IVA 22%
                  setFormData({ 
                    ...formData, 
                    importo_imponibile: e.target.value,
                    importo_iva: iva
                  })
                }}
                className="glass-input w-full"
                placeholder="1000.00"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">IVA * (€)</label>
              <input
                type="number"
                step="0.01"
                required
                value={formData.importo_iva}
                onChange={(e) => setFormData({ ...formData, importo_iva: e.target.value })}
                className="glass-input w-full"
                placeholder="220.00"
              />
            </div>
          </div>
          
          {/* Totale */}
          <div className="glass-card p-4 border border-primary-500/20">
            <div className="flex justify-between items-center">
              <span className="font-medium">Totale Fattura:</span>
              <span className="text-xl font-bold text-primary-400">
                € {((parseFloat(formData.importo_imponibile) || 0) + (parseFloat(formData.importo_iva) || 0)).toLocaleString('it-IT', { minimumFractionDigits: 2 })}
              </span>
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="glass-button"
              disabled={submitting}
            >
              Annulla
            </button>
            <button
              type="submit"
              disabled={submitting || !formData.client_id || !formData.importo_imponibile}
              className="glass-button-primary"
            >
              {submitting ? (
                <div className="flex items-center gap-2">
                  <div className="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                  {editingInvoice ? 'Aggiornamento...' : 'Creazione...'}
                </div>
              ) : (
                <>
                  <Save className="w-4 h-4" />
                  {editingInvoice ? 'Aggiorna Fattura' : 'Crea Fattura'}
                </>
              )}
            </button>
          </div>
        </form>
      </Modal>

      {/* Modal di Warning per Sync FIC */}
      <Modal
        isOpen={showSyncWarning}
        onClose={() => setShowSyncWarning(false)}
        title="⚠️ Attenzione: Sincronizzazione Fatture in Cloud"
      >
        <div className="space-y-4">
          <div className="glass-card border-2 border-amber-500/30 bg-amber-500/10 p-4 rounded-lg">
            <div className="flex items-start gap-3">
              <AlertCircle className="w-6 h-6 text-amber-400 flex-shrink-0 mt-1" />
              <div className="space-y-2">
                <h3 className="font-bold text-amber-400">Operazione Pericolosa</h3>
                <p className="text-sm text-gray-600">
                  La sincronizzazione con Fatture in Cloud <strong>sovrascriverà o eliminerà</strong> tutte le fatture locali presenti nel database.
                </p>
                <p className="text-sm text-gray-600">
                  Le fatture verranno sostituite con quelle presenti su Fatture in Cloud, perdendo eventuali dati locali non sincronizzati.
                </p>
              </div>
            </div>
          </div>

          <div className="glass-card p-4 space-y-2">
            <div className="flex justify-between items-center">
              <span className="text-gray-500">Fatture locali attuali:</span>
              <span className="font-bold text-primary-400">{invoices.length}</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-500">Stato connessione FIC:</span>
              <span className={`font-bold ${ficConnected ? 'text-green-400' : 'text-red-400'}`}>
                {ficConnected ? 'Connesso' : 'Non connesso'}
              </span>
            </div>
          </div>

          <div className="bg-gray-50/50 border border-gray-200 rounded-lg p-3">
            <p className="text-xs text-gray-500">
              <strong>Nota:</strong> Questa operazione è irreversibile. Assicurati di avere un backup prima di procedere.
            </p>
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={() => setShowSyncWarning(false)}
              className="glass-button px-6"
            >
              Annulla
            </button>
            <button
              type="button"
              onClick={confirmSyncFIC}
              disabled={!ficConnected}
              className="bg-amber-500 hover:bg-amber-600 text-white px-6 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <AlertCircle className="w-4 h-4" />
              Procedi con la Sincronizzazione
            </button>
          </div>
        </div>
      </Modal>

      {/* Modal per Modifica Date in Bulk */}
      <Modal
        isOpen={bulkDateModalOpen}
        onClose={() => setBulkDateModalOpen(false)}
        title="Modifica Date Fatture"
      >
        <div className="space-y-4">
          <div className="glass-card p-4">
            <p className="text-gray-600">
              Stai per modificare la data di <strong className="text-primary-400">{selectedInvoices.length}</strong> fatture selezionate.
            </p>
            <p className="text-sm text-gray-500 mt-2">
              I numeri fattura verranno rigenerati automaticamente in base alla nuova data.
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Nuova Data Emissione *</label>
            <input
              type="date"
              required
              value={bulkDate}
              onChange={(e) => setBulkDate(e.target.value)}
              className="glass-input w-full"
            />
          </div>

          <div className="glass-card border-2 border-amber-500/30 bg-amber-500/10 p-3">
            <div className="flex items-start gap-2">
              <AlertCircle className="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
              <p className="text-sm text-amber-200">
                Le fatture già inviate a FIC o SDI non possono essere modificate e verranno saltate.
              </p>
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={() => setBulkDateModalOpen(false)}
              className="glass-button px-6"
              disabled={submitting}
            >
              Annulla
            </button>
            <button
              type="button"
              onClick={handleBulkDateUpdate}
              disabled={submitting || !bulkDate}
              className="glass-button-primary px-6"
            >
              {submitting ? (
                <div className="flex items-center gap-2">
                  <div className="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                  Aggiornamento...
                </div>
              ) : (
                <>
                  <Save className="w-4 h-4" />
                  Aggiorna Date
                </>
              )}
            </button>
          </div>
        </div>
      </Modal>

      {/* Modal Genera Fatture Differite */}
      <Modal
        isOpen={deferredModalOpen}
        onClose={() => setDeferredModalOpen(false)}
        title="Genera Fatture Differite"
      >
        <div className="space-y-4">
          <div className="glass-card p-4">
            <p className="text-gray-600">
              Genera fatture differite riepilogative per tutte le consegne con pagamento in contanti del mese selezionato.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-2">Mese *</label>
              <select
                value={deferredMonth}
                onChange={(e) => setDeferredMonth(Number(e.target.value))}
                className="glass-input w-full"
              >
                {Array.from({ length: 12 }, (_, i) => (
                  <option key={i + 1} value={i + 1}>
                    {new Date(2000, i).toLocaleString('it-IT', { month: 'long' })}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Anno *</label>
              <input
                type="number"
                value={deferredYear}
                onChange={(e) => setDeferredYear(Number(e.target.value))}
                min={2020}
                max={2100}
                className="glass-input w-full"
              />
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={() => setDeferredModalOpen(false)}
              className="glass-button px-6"
              disabled={generatingDeferred}
            >
              Annulla
            </button>
            <button
              type="button"
              onClick={handleGenerateDeferred}
              disabled={generatingDeferred}
              className="glass-button-primary px-6"
            >
              {generatingDeferred ? (
                <div className="flex items-center gap-2">
                  <div className="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                  Generazione...
                </div>
              ) : (
                <>
                  <FileText className="w-4 h-4" />
                  Genera Fatture
                </>
              )}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  )
}
