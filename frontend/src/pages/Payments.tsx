import { motion, AnimatePresence } from 'framer-motion'
import { useState, useEffect } from 'react'
import React from 'react'
import { 
  Search,
  CreditCard,
  TrendingUp,
  TrendingDown,
  CheckCircle,
  Clock,
  XCircle,
  Loader2,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  Upload,
  Users,
  List,
  DollarSign,
  FileText,
  RefreshCw,
  X,
  BarChart3,
  Download,
  Send,
  AlertTriangle,
  Info,
  Eye,
  AlertCircle,
  Link2,
  Copy
} from 'lucide-react'
import { notificationService } from '../components/NotificationBell'
import { paymentsApi } from '../utils/api'

type PaymentType = 'income' | 'expense' | 'withdrawal' | 'fee'
type PaymentStatus = 'completed' | 'pending' | 'failed'

interface PaymentStats {
  income: number
  expenses: number
  pending: number
  failed: number
  income_change: number
  last_sync: string | null
}

interface Payment {
  id: number
  transaction_id: string
  source?: string
  source_transaction_id?: string
  source_data?: string
  transaction_date: string
  value_date?: string
  description: string
  descrizione?: string
  beneficiario?: string
  amount: number
  type: PaymentType
  display_type?: PaymentType  // Type convertito per frontend
  status: PaymentStatus
  fee?: number
  net_amount?: number
  gross_amount?: number
  currency: string
  category?: string
  causale?: string
  note?: string
  is_reconciled?: boolean
  client_id?: number
  invoice_id?: number
}

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export default function Payments() {
  const [searchTerm, setSearchTerm] = useState('')
  const [typeFilter, setTypeFilter] = useState<PaymentType | 'all'>('all')
  const [statusFilter, setStatusFilter] = useState<PaymentStatus | 'all'>('all')
  const [sourceFilter, setSourceFilter] = useState<string>('all')
  const [transferDestFilter, setTransferDestFilter] = useState('')
  
  // Imposta date di default: dal 1° del mese corrente a oggi
  const getDefaultDateFrom = () => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`
  }
  
  const getDefaultDateTo = () => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
  }
  
  const [dateFrom, setDateFrom] = useState(getDefaultDateFrom())
  const [dateTo, setDateTo] = useState(getDefaultDateTo())
  const [sortColumn, setSortColumn] = useState<'date' | 'amount' | 'type' | 'source' | 'description' | 'beneficiary' | 'status' | null>('date')
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc')
  const [stats, setStats] = useState<PaymentStats | null>(null)
  const [payments, setPayments] = useState<Payment[]>([])
  const [loading, setLoading] = useState(false)
const [activeTab, setActiveTab] = useState<'payments' | 'import' | 'aggregate' | 'commissions' | 'ordinary-invoicing' | 'stripe-report' | 'payment-links'>('payments')
  const [importSource, setImportSource] = useState<'bank' | 'vivawallet' | 'nexi' | 'paypal'>('bank')
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [importing, setImporting] = useState(false)
  const [importingStripe, setImportingStripe] = useState(false)
  const [aggregatedData, setAggregatedData] = useState<any[]>([])
  const [aggregatedByDestination, setAggregatedByDestination] = useState<any[]>([])
  const [loadingAggregate, setLoadingAggregate] = useState(false)
  const [aggregateSubTab, setAggregateSubTab] = useState<'client' | 'destination'>('client')
  
  // Report Stripe states  
  const [stripeYear, setStripeYear] = useState(new Date().getFullYear())
  const [stripeMonth, setStripeMonth] = useState(new Date().getMonth() + 1)
  const [stripeTransactions, setStripeTransactions] = useState<any[]>([])
  const [stripeTotals, setStripeTotals] = useState<any>(null)
  const [stripeLoading, setStripeLoading] = useState(false)
  const [stripeNormalizing, setStripeNormalizing] = useState(false)
  const [stripeResetting, setStripeResetting] = useState(false)
  const [stripeExporting, setStripeExporting] = useState(false)
  const [stripeSending, setStripeSending] = useState(false)
  const [stripeNeedsNormalization, setStripeNeedsNormalization] = useState(false)
  const [stripeAccountantEmail, setStripeAccountantEmail] = useState('')
  const [stripeSaveEmail, setStripeSaveEmail] = useState(false)
  const [editingTransaction, setEditingTransaction] = useState<string | null>(null)
  const [aggregateDestFilter, setAggregateDestFilter] = useState('')
  const [syncStats, setSyncStats] = useState<any>(null)
  const [showSyncLog, setShowSyncLog] = useState(false)
  const [normalizeStats, setNormalizeStats] = useState<any>(null)
  const [showNormalizeLog, setShowNormalizeLog] = useState(false)
  
  // Stati per ordinamento Report Stripe
  const [stripeSortColumn, setStripeSortColumn] = useState<'id' | 'type' | 'source' | 'amount' | 'fee' | 'net' | 'date' | null>('date')
  const [stripeSortDirection, setStripeSortDirection] = useState<'asc' | 'desc'>('desc')

  // Payment Links (Checkout Sessions) states
  const [checkoutSessions, setCheckoutSessions] = useState<any[]>([])
  const [loadingCheckoutSessions, setLoadingCheckoutSessions] = useState(false)
  const [creatingCheckoutSession, setCreatingCheckoutSession] = useState(false)
  const [checkoutAmount, setCheckoutAmount] = useState('')
  const [checkoutDescription, setCheckoutDescription] = useState('')
  const [checkoutEmail, setCheckoutEmail] = useState('')
  const [checkoutStatusFilter, setCheckoutStatusFilter] = useState<string>('all')
  const [copiedLinkId, setCopiedLinkId] = useState<number | null>(null)
  
  // Stati per commissioni riscosse
  const [applicationFees, setApplicationFees] = useState<any[]>([])
  const [loadingApplicationFees, setLoadingApplicationFees] = useState(false)
  const [commissionsPeriod, setCommissionsPeriod] = useState(() => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  })
  // SEMPRE raggruppa per email per le fatture differite
  const groupByEmail = true
  
  // Stati per visualizzazione dettagliata commissioni
  const [detailedCommissions, setDetailedCommissions] = useState<any[]>([])
  const [loadingDetailedCommissions, setLoadingDetailedCommissions] = useState(false)
  const [commissionSearchTerm, setCommissionSearchTerm] = useState('')
  const [exportingCommissions, setExportingCommissions] = useState(false)
  const [exportingCSV, setExportingCSV] = useState(false)
  const [commissionSortColumn, setCommissionSortColumn] = useState<'date' | 'amount' | 'email'>('date')
  const [commissionSortDirection, setCommissionSortDirection] = useState<'asc' | 'desc'>('desc')
  
  // Stati per fatturazione differita commissioni
  const [showDeferredInvoicingModal, setShowDeferredInvoicingModal] = useState(false)
  const [deferredInvoicePreviews, setDeferredInvoicePreviews] = useState<any[]>([])
  const [loadingDeferredPreviews, setLoadingDeferredPreviews] = useState(false)
  const [generatingDeferredInvoices, setGeneratingDeferredInvoices] = useState(false)
  const [sendingToFIC, setSendingToFIC] = useState(false)
  
  // Stati per fatturazione ordinaria Stripe
  const [showOrdinaryInvoicingModal, setShowOrdinaryInvoicingModal] = useState(false)
  const [ordinaryInvoicePreviews, setOrdinaryInvoicePreviews] = useState<any[]>([])
  const [loadingOrdinaryPreviews, setLoadingOrdinaryPreviews] = useState(false)
  const [generatingOrdinaryInvoices, setGeneratingOrdinaryInvoices] = useState(false)
  const [sendingOrdinaryToFIC, setSendingOrdinaryToFIC] = useState(false)
  const [ordinaryPeriod, setOrdinaryPeriod] = useState(() => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  })
  
  const [showInvoiceModal, setShowInvoiceModal] = useState(false)
  const [invoicePreview, setInvoicePreview] = useState<any>(null)
  const [generatingInvoice, setGeneratingInvoice] = useState(false)
  const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null)
  const [showPaymentDetails, setShowPaymentDetails] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [itemsPerPage, setItemsPerPage] = useState(30)

  useEffect(() => {
    fetchStats()
    fetchPayments()
  }, [])

  // Auto-fetch commissioni quando si apre il tab
  useEffect(() => {
    if (activeTab === 'commissions') {
      fetchDetailedCommissions()
    }
  }, [activeTab, commissionsPeriod])

  // Auto-fetch checkout sessions quando si apre il tab
  useEffect(() => {
    if (activeTab === 'payment-links') {
      fetchCheckoutSessions()
    }
  }, [activeTab, checkoutStatusFilter])

  const fetchStats = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/payments-stats`, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      })
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      const data = await response.json()
      if (data.success) {
        setStats(data.data)
      }
    } catch (error) {
      console.error('Errore caricamento statistiche:', error)
    }
  }

  const fetchPayments = async () => {
    setLoading(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/payments`, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      })
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      const data = await response.json()
      if (data.success) {
        setPayments(data.data.data || [])
      }
    } catch (error) {
      console.error('Errore caricamento pagamenti:', error)
    } finally {
      setLoading(false)
    }
  }

  // === Checkout Sessions (Payment Links) ===
  const fetchCheckoutSessions = async () => {
    setLoadingCheckoutSessions(true)
    try {
      const token = localStorage.getItem('token')
      const params = checkoutStatusFilter !== 'all' ? `?status=${checkoutStatusFilter}` : ''
      const response = await fetch(`${API_URL}/stripe/checkout-sessions${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      })
      const data = await response.json()
      if (data.success) {
        setCheckoutSessions(data.data || [])
      }
    } catch (error) {
      console.error('Errore caricamento checkout sessions:', error)
    } finally {
      setLoadingCheckoutSessions(false)
    }
  }

  const handleCreateCheckoutSession = async () => {
    if (!checkoutAmount || !checkoutDescription) return

    setCreatingCheckoutSession(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/stripe/checkout-sessions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          amount: parseFloat(checkoutAmount),
          description: checkoutDescription,
          customer_email: checkoutEmail || undefined,
        })
      })
      const data = await response.json()
      if (data.success) {
        setCheckoutAmount('')
        setCheckoutDescription('')
        setCheckoutEmail('')
        fetchCheckoutSessions()
        notificationService.add('success', 'Link di pagamento creato con successo')
      } else {
        notificationService.add('error', data.message || 'Errore nella creazione del link')
      }
    } catch (error) {
      console.error('Errore creazione checkout session:', error)
      notificationService.add('error', 'Errore nella creazione del link di pagamento')
    } finally {
      setCreatingCheckoutSession(false)
    }
  }

  const handleRefreshCheckoutSession = async (sessionId: string) => {
    try {
      const token = localStorage.getItem('token')
      await fetch(`${API_URL}/stripe/checkout-sessions/${sessionId}/refresh`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      })
      fetchCheckoutSessions()
    } catch (error) {
      console.error('Errore refresh checkout session:', error)
    }
  }

  const handleCopyLink = (id: number, url: string) => {
    navigator.clipboard.writeText(url)
    setCopiedLinkId(id)
    setTimeout(() => setCopiedLinkId(null), 2000)
  }

  const formatAmount = (amount: number) => {
    return new Intl.NumberFormat('it-IT', {
      style: 'currency',
      currency: 'EUR'
    }).format(amount)
  }

  const getStatusColor = (status: PaymentStatus) => {
    switch (status) {
      case 'completed': return 'bg-green-500/20 text-green-400 border-green-500/30'
      case 'pending': return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
      case 'failed': return 'bg-red-500/20 text-red-400 border-red-500/30'
    }
  }

  const getStatusIcon = (status: PaymentStatus) => {
    switch (status) {
      case 'completed': return CheckCircle
      case 'pending': return Clock
      case 'failed': return XCircle
      default: return CheckCircle
    }
  }

  const getStatusLabel = (status: PaymentStatus) => {
    switch (status) {
      case 'completed': return 'Completato'
      case 'pending': return 'In Attesa'
      case 'failed': return 'Fallito'
    }
  }

  const getTypeLabel = (type: PaymentType) => {
    switch (type) {
      case 'income': return 'Entrata'
      case 'expense': return 'Uscita'
      case 'withdrawal': return 'Prelievo'
      case 'fee': return 'Commissione'
    }
  }

  const getTypeColor = (type: PaymentType) => {
    switch (type) {
      case 'income': return 'text-green-400'
      case 'expense': return 'text-red-400'
      case 'withdrawal': return 'text-orange-400'
      case 'fee': return 'text-gray-500'
    }
  }

  const handleImportCSV = async () => {
    if (!selectedFile) return

    setImporting(true)
    try {
      const token = localStorage.getItem('token')
      const formData = new FormData()
      formData.append('file', selectedFile)
      // La sorgente viene rilevata automaticamente dal backend

      const response = await fetch(`${API_URL}/payments/import-csv`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      })

      const data = await response.json()
      
      if (data.success) {
        notificationService.add('success', `Importazione completata: ${data.data.imported} transazioni importate, ${data.data.skipped} già esistenti`)
        setSelectedFile(null)
        // Reset filtri per mostrare tutte le transazioni
        setSourceFilter('all')
        setTypeFilter('all')
        setStatusFilter('all')
        setSearchTerm('')
        fetchPayments()
        fetchStats()
      } else {
        notificationService.add('error', 'Errore importazione: ' + data.message)
      }
    } catch (error) {
      console.error('Errore import CSV:', error)
      notificationService.add('error', 'Errore durante l\'importazione del file')
    } finally {
      setImporting(false)
    }
  }

  const handleStripeImport = async (force: boolean = false) => {
    setImportingStripe(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/stripe/import`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          days: 30,
          force: force,
        }),
      })

      const data = await response.json()
      
      if (data.success) {
        // Salva statistiche per il log (consultabile manualmente)
        setSyncStats(data.data)
        setShowSyncLog(true)
        
        let message = `Sincronizzazione completata: ${data.data.imported} transazioni importate`
        
        if (data.data.skipped > 0) {
          message += ` (${data.data.skipped} già presenti)`
        }
        
        if (data.data.message) {
          message = data.data.message
        }
        
        notificationService.add('success', message)
        // Reset filtri per mostrare tutte le transazioni
        setSourceFilter('all')
        setTypeFilter('all')
        setStatusFilter('all')
        setSearchTerm('')
        fetchPayments()
        fetchStats()
      } else {
        notificationService.add('error', 'Errore sincronizzazione: ' + data.message)
      }
    } catch (error) {
      console.error('Errore sincronizzazione Stripe:', error)
      notificationService.add('error', 'Errore durante la sincronizzazione con Stripe')
    } finally {
      setImportingStripe(false)
    }
  }

  const handleGenerateInvoice = async (clientId: number, beneficiary: string) => {
    try {
      const token = localStorage.getItem('token')
      
      // Trova tutti i payment_ids per questo cliente
      const response = await fetch(`${API_URL}/payments/aggregate-by-client`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      
      const data = await response.json()
      if (!data.success) {
        notificationService.add('error', 'Errore nel recupero dei dati')
        return
      }
      
      // Trova l'aggregato specifico
      const aggregate = data.data.find((a: any) => 
        a.client_id === clientId || a.beneficiary === beneficiary
      )
      
      if (!aggregate) {
        notificationService.add('error', 'Dati aggregati non trovati')
        return
      }
      
      // Recupera i dettagli dei pagamenti
      const paymentsResponse = await fetch(`${API_URL}/payments?client_id=${clientId}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      
      const paymentsData = await paymentsResponse.json()
      if (!paymentsData.success) {
        notificationService.add('error', 'Errore nel recupero dei pagamenti')
        return
      }
      
      // Filtra solo i pagamenti in entrata non ancora riconciliati
      const paymentIds = paymentsData.data.data
        .filter((p: any) => p.amount > 0 && !p.is_reconciled)
        .map((p: any) => p.id)
      
      if (paymentIds.length === 0) {
        notificationService.add('error', 'Nessun pagamento disponibile per la fatturazione')
        return
      }
      
      // Richiedi preview fattura
      const previewResponse = await fetch(`${API_URL}/invoices/preview-from-payments`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          client_id: clientId,
          payment_ids: paymentIds
        })
      })
      
      const previewData = await previewResponse.json()
      
      if (previewData.success) {
        setInvoicePreview(previewData.data)
        setShowInvoiceModal(true)
      } else {
        notificationService.add('error', 'Errore preview fattura: ' + previewData.message)
      }
      
    } catch (error) {
      console.error('Errore generazione fattura:', error)
      notificationService.add('error', 'Errore durante la generazione della fattura')
    }
  }

  const handleConfirmInvoice = async () => {
    if (!invoicePreview) return
    
    setGeneratingInvoice(true)
    try {
      const token = localStorage.getItem('token')
      
      // Estrai payment_ids da tutti gli items
      const paymentIds = invoicePreview.items.flatMap((item: any) => item.payment_ids)
      
      const response = await fetch(`${API_URL}/invoices/generate-from-payments`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          client_id: invoicePreview.client.id,
          payment_ids: paymentIds,
          invoice_data: invoicePreview.suggested_data,
          items: invoicePreview.items
        })
      })
      
      const data = await response.json()
      
      if (data.success) {
        notificationService.add('success', 'Fattura generata con successo!')
        setShowInvoiceModal(false)
        setInvoicePreview(null)
        fetchAggregatedData() // Ricarica dati
      } else {
        notificationService.add('error', 'Errore generazione: ' + data.message)
      }
      
    } catch (error) {
      console.error('Errore conferma fattura:', error)
      notificationService.add('error', 'Errore durante la conferma della fattura')
    } finally {
      setGeneratingInvoice(false)
    }
  }

  const handleRefundPayment = async (paymentId: number) => {
    if (!confirm('Sei sicuro di voler rimborsare questo pagamento? L\'operazione è irreversibile.')) return

    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/stripe/refund`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          payment_id: paymentId
        })
      })

      const data = await response.json()
      
      if (data.success) {
        notificationService.add('success', 'Rimborso effettuato con successo!')
        fetchPayments()
        fetchStats()
      } else {
        notificationService.add('error', 'Errore rimborso: ' + data.message)
      }
    } catch (error) {
      console.error('Errore rimborso:', error)
      notificationService.add('error', 'Errore durante il rimborso')
    }
  }

  const fetchAggregatedData = async () => {
    setLoadingAggregate(true)
    try {
      const token = localStorage.getItem('token')
      const params = new URLSearchParams()
      if (dateFrom) params.append('date_from', dateFrom)
      if (dateTo) params.append('date_to', dateTo)
      if (aggregateDestFilter) params.append('transfer_destination', aggregateDestFilter)

      const response = await fetch(`${API_URL}/payments/aggregate-by-client?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      if (data.success) {
        setAggregatedData(data.data)
      }
    } catch (error) {
      console.error('Errore caricamento aggregati:', error)
    } finally {
      setLoadingAggregate(false)
    }
  }

  const fetchAggregatedByDestination = async () => {
    setLoadingAggregate(true)
    try {
      const token = localStorage.getItem('token')
      const params = new URLSearchParams()
      if (dateFrom) params.append('date_from', dateFrom)
      if (dateTo) params.append('date_to', dateTo)

      const response = await fetch(`${API_URL}/payments/aggregate-by-destination?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      if (data.success) {
        setAggregatedByDestination(data.data)
      }
    } catch (error) {
      console.error('Errore caricamento aggregati per destinazione:', error)
    } finally {
      setLoadingAggregate(false)
    }
  }

  const fetchApplicationFees = async () => {
    setLoadingApplicationFees(true)
    try {
      const token = localStorage.getItem('token')
      const params = new URLSearchParams()
      if (commissionsPeriod) params.append('period_month', commissionsPeriod)
      // SEMPRE raggruppa per email (non per owner)
      params.append('group_by_owner', 'false')

      const response = await fetch(`${API_URL}/payments/application-fees?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      if (data.success) {
        setApplicationFees(data.data.aggregated || [])
      } else {
        notificationService.add('error', 'Errore caricamento commissioni: ' + data.message)
      }
    } catch (error) {
      console.error('Errore caricamento commissioni:', error)
      notificationService.add('error', 'Errore durante il caricamento delle commissioni')
    } finally {
      setLoadingApplicationFees(false)
    }
  }

  const fetchDetailedCommissions = async () => {
    setLoadingDetailedCommissions(true)
    try {
      const token = localStorage.getItem('token')
      const params = new URLSearchParams()
      if (commissionsPeriod) params.append('period_month', commissionsPeriod)

      const response = await fetch(`${API_URL}/payments/application-fees-detailed?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      if (data.success) {
        setDetailedCommissions(data.data.transactions || [])
        notificationService.add('success', `Caricate ${data.data.total_count} commissioni dettagliate`)
      } else {
        notificationService.add('error', 'Errore caricamento commissioni dettagliate: ' + data.message)
      }
    } catch (error) {
      console.error('Errore caricamento commissioni dettagliate:', error)
      notificationService.add('error', 'Errore durante il caricamento delle commissioni dettagliate')
    } finally {
      setLoadingDetailedCommissions(false)
    }
  }

  const handleExportCommissions = async () => {
    setExportingCommissions(true)
    try {
      const token = localStorage.getItem('token')
      const params = new URLSearchParams()
      if (commissionsPeriod) params.append('period_month', commissionsPeriod)

      const response = await fetch(`${API_URL}/payments/application-fees-export?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        throw new Error('Errore durante l\'export')
      }

      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `commissioni_riscosse_${commissionsPeriod}.xlsx`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
      notificationService.add('success', 'Commissioni esportate con successo!')
    } catch (error) {
      console.error('Errore export commissioni:', error)
      notificationService.add('error', 'Errore durante l\'export delle commissioni')
    } finally {
      setExportingCommissions(false)
    }
  }

  // Funzione rimossa - raggruppiamo sempre per email

  const handleGeneratePartnerInvoice = async (clientId: number, partnerEmail: string, partnerName: string) => {
    if (!clientId) {
      notificationService.add('info', `Partner ${partnerName} non collegato a un cliente. Associa prima il partner.`)
      return
    }
    
    // Apri modal fatturazione differita
    await handleOpenDeferredInvoicing()
  }

  // Apri modal fatturazione differita con pre-generazione
  const handleOpenDeferredInvoicing = async () => {
    setShowDeferredInvoicingModal(true)
    await handlePregenerateDeferredInvoices()
  }

  // ========== FATTURAZIONE ORDINARIA STRIPE (20 - 1 mese successivo) ==========
  
  // Apri modal fatturazione ordinaria con pre-generazione
  const handleOpenOrdinaryInvoicing = async () => {
    setShowOrdinaryInvoicingModal(true)
    await handlePregenerateOrdinaryInvoices()
  }

  // Pre-genera fatture ordinarie Stripe (anteprima)
  const handlePregenerateOrdinaryInvoices = async () => {
    setLoadingOrdinaryPreviews(true)
    try {
      const token = localStorage.getItem('token')
      const [year, month] = ordinaryPeriod.split('-')
      
      const response = await fetch(`${API_URL}/stripe/ordinary-invoices/pregenerate/${year}/${month}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      
      if (data.success) {
        setOrdinaryInvoicePreviews(data.data.previews || [])
        const previews = data.data.previews || []
        const alreadyGenerated = previews.filter((p: any) => p.invoice_id).length
        const toGenerate = previews.filter((p: any) => !p.invoice_id).length
        
        if (toGenerate > 0) {
          notificationService.add('success', `Trovate ${toGenerate} fatture ordinarie da generare${alreadyGenerated > 0 ? ` (${alreadyGenerated} già generate)` : ''}`)
        } else if (alreadyGenerated > 0) {
          notificationService.add('info', `Tutte le ${alreadyGenerated} fatture ordinarie per questo periodo sono già state generate`)
        } else {
          notificationService.add('info', 'Nessuna fattura ordinaria trovata per questo periodo')
        }
      } else {
        notificationService.add('error', 'Errore pre-generazione: ' + data.message)
      }
    } catch (error) {
      console.error('Errore pre-generazione fatture ordinarie:', error)
      notificationService.add('error', 'Errore durante la pre-generazione delle fatture ordinarie')
    } finally {
      setLoadingOrdinaryPreviews(false)
    }
  }

  // Genera fatture ordinarie Stripe
  const handleGenerateOrdinaryInvoices = async () => {
    const [year, month] = ordinaryPeriod.split('-')
    if (!confirm(`Sei sicuro di voler generare le fatture ordinarie per ${month}/${year}? Questa operazione creerà le fatture nel sistema.`)) {
      return
    }

    setGeneratingOrdinaryInvoices(true)
    try {
      const token = localStorage.getItem('token')
      
      const response = await fetch(`${API_URL}/stripe/ordinary-invoices/generate/${year}/${month}`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) throw new Error('Errore generazione fatture ordinarie')
      
      const data = await response.json()
      if (data.success) {
        const created = data.data.invoices_created || 0
        const errors = data.data.errors || []
        
        if (created > 0) {
          notificationService.add('success', `Generate ${created} fatture ordinarie`)
        } else {
          notificationService.add('info', 'Nessuna fattura generata. Controlla che i partner abbiano un cliente collegato nel sistema.')
        }
        
        // Mostra errori se presenti
        if (errors.length > 0) {
          console.log('Errori generazione:', errors)
          notificationService.add('info', `${errors.length} partner saltati (cliente non trovato)`)
        }
        
        // Ricarica preview per mostrare fatture generate
        await handlePregenerateOrdinaryInvoices()
      }
    } catch (error) {
      console.error('Errore generazione fatture ordinarie:', error)
      notificationService.add('error', 'Errore durante la generazione delle fatture ordinarie')
    } finally {
      setGeneratingOrdinaryInvoices(false)
    }
  }

  // Invia fatture ordinarie a Fatture in Cloud
  const handleSendOrdinaryInvoicesToFIC = async () => {
    const [year, month] = ordinaryPeriod.split('-')
    
    setSendingOrdinaryToFIC(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/stripe/ordinary-invoices/send-to-fic/${year}/${month}`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) throw new Error('Errore invio fatture a FIC')
      
      const data = await response.json()
      if (data.success) {
        notificationService.add('success', `Inviate ${data.data.sent_count || 0} fatture ordinarie a Fatture in Cloud`)
        await handlePregenerateOrdinaryInvoices()
      }
    } catch (error) {
      console.error('Errore invio fatture ordinarie a FIC:', error)
      notificationService.add('error', 'Errore durante l\'invio delle fatture a Fatture in Cloud')
    } finally {
      setSendingOrdinaryToFIC(false)
    }
  }

  // Genera fattura ordinaria singola
  const handleGenerateSingleOrdinaryInvoice = async (preview: any) => {
    // Controlla se cliente è collegato
    if (!preview.client_id) {
      notificationService.add('error', `Impossibile generare fattura: partner "${preview.partner_name}" non ha un cliente collegato nel sistema`)
      return
    }
    
    if (!confirm(`Generare fattura ordinaria per ${preview.partner_name}?\n\nImporto: ${formatAmount(preview.total_amount)}`)) {
      return
    }

    setGeneratingOrdinaryInvoices(true)
    try {
      const token = localStorage.getItem('token')
      const [year, month] = ordinaryPeriod.split('-')
      
      const response = await fetch(`${API_URL}/stripe/ordinary-invoices/generate-single/${year}/${month}`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          partner_email: preview.partner_email
        })
      })

      const data = await response.json()
      
      if (!response.ok || !data.success) {
        const errorMsg = data.message || 'Errore generazione fattura'
        throw new Error(errorMsg)
      }
      
      if (data.success) {
        notificationService.add('success', `Fattura generata per ${preview.partner_name}`)
        await handlePregenerateOrdinaryInvoices()
      }
    } catch (error: any) {
      console.error('Errore generazione fattura ordinaria:', error)
      notificationService.add('error', error.message || 'Errore durante la generazione della fattura')
    } finally {
      setGeneratingOrdinaryInvoices(false)
    }
  }

  // Invia singola fattura ordinaria a SDI
  const handleSendSingleOrdinaryInvoiceToFIC = async (invoiceId: number) => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/invoices/${invoiceId}/send-to-fic`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })

      if (!response.ok) throw new Error('Errore invio fattura a FIC')
      
      const data = await response.json()
      if (data.success) {
        notificationService.add('success', 'Fattura inviata a Fatture in Cloud con successo')
        await handlePregenerateOrdinaryInvoices()
      }
    } catch (error) {
      console.error('Errore invio fattura a FIC:', error)
      notificationService.add('error', 'Errore durante l\'invio della fattura a Fatture in Cloud')
    }
  }

  // Pre-genera fatture commissioni (anteprima)
  const handlePregenerateDeferredInvoices = async () => {
    setLoadingDeferredPreviews(true)
    try {
      const token = localStorage.getItem('token')
      const [year, month] = commissionsPeriod.split('-')
      
      const response = await fetch(`${API_URL}/payments/commission-invoices/pregenerate/${year}/${month}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      
      if (data.success) {
        setDeferredInvoicePreviews(data.data.previews || [])
        notificationService.add('success', `Trovate ${data.data.total_invoices} fatture da generare (${data.data.ready_invoices} pronte)`)
      } else {
        notificationService.add('error', 'Errore pre-generazione: ' + data.message)
      }
    } catch (error) {
      console.error('Errore pre-generazione:', error)
      notificationService.add('error', 'Errore durante la pre-generazione delle fatture')
    } finally {
      setLoadingDeferredPreviews(false)
    }
  }

  // Genera fatture differite commissioni (una per una usando i client_id dal preview)
  const handleGenerateDeferredInvoices = async () => {
    const readyPreviews = deferredInvoicePreviews.filter((p: any) => p.invoice_ready && p.client_id)
    if (readyPreviews.length === 0) {
      notificationService.add('info', 'Nessuna fattura pronta da generare')
      return
    }

    if (!confirm(`Sei sicuro di voler generare ${readyPreviews.length} fatture differite per ${commissionsPeriod}? Questa operazione creerà le fatture nel sistema.`)) {
      return
    }

    setGeneratingDeferredInvoices(true)
    try {
      const token = localStorage.getItem('token')
      const [year, month] = commissionsPeriod.split('-')

      let generated = 0
      let errors = 0

      for (const preview of readyPreviews) {
        try {
          const response = await fetch(`${API_URL}/payments/commission-invoices/generate-single/${year}/${month}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
              partner_email: preview.partner_email,
              client_id: preview.client_id
            })
          })

          const data = await response.json()
          if (data.success) {
            generated++
          } else {
            errors++
            console.error(`Errore fattura ${preview.partner_name}:`, data.message)
          }
        } catch (err) {
          errors++
          console.error(`Errore fattura ${preview.partner_name}:`, err)
        }
      }

      if (generated > 0) {
        notificationService.add('success', `Generate ${generated} fatture differite.${errors > 0 ? ` ${errors} errori.` : ''} Vai alla pagina Fatture per inviarle a FIC.`)
      } else {
        notificationService.add('error', `Nessuna fattura generata. ${errors} errori.`)
      }

      // Ricarica preview per aggiornare il conteggio
      await handlePregenerateDeferredInvoices()
    } catch (error) {
      console.error('Errore generazione fatture:', error)
      notificationService.add('error', 'Errore durante la generazione delle fatture')
    } finally {
      setGeneratingDeferredInvoices(false)
    }
  }

  // Invia fatture differite a Fatture in Cloud
  const handleSendDeferredInvoicesToFIC = async () => {
    setSendingToFIC(true)
    try {
      const token = localStorage.getItem('token')
      const [year, month] = commissionsPeriod.split('-')
      
      const response = await fetch(`${API_URL}/payments/commission-invoices/send-to-fic/${year}/${month}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      
      if (data.success) {
        notificationService.add('success', `Inviate ${data.data.success_count} fatture a Fatture in Cloud` + 
          (data.data.error_count > 0 ? ` (${data.data.error_count} errori)` : ''))
        setShowDeferredInvoicingModal(false)
        
        // Ricarica commissioni dettagliate
        fetchDetailedCommissions()
      } else {
        notificationService.add('error', 'Errore invio fatture: ' + data.message)
      }
    } catch (error) {
      console.error('Errore invio fatture:', error)
      notificationService.add('error', 'Errore durante l\'invio delle fatture')
    } finally {
      setSendingToFIC(false)
    }
  }

  // Genera fattura singola per un partner
  const handleGenerateSingleInvoice = async (preview: any) => {
    if (!confirm(`Generare fattura differita per ${preview.partner_name}?\n\nCommissioni: ${formatAmount(preview.total_commissions)}\nImporto netto: ${formatAmount(preview.net_amount)}`)) {
      return
    }

    setGeneratingDeferredInvoices(true)
    try {
      const token = localStorage.getItem('token')
      const [year, month] = commissionsPeriod.split('-')
      
      // Chiama endpoint per generare singola fattura
      const response = await fetch(`${API_URL}/payments/commission-invoices/generate-single/${year}/${month}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          partner_email: preview.partner_email,
          client_id: preview.client_id
        })
      })

      const data = await response.json()
      
      if (data.success) {
        notificationService.add('success', `Fattura generata per ${preview.partner_name}! Vai alla pagina Fatture per inviarla a FIC.`)
        
        // Ricarica preview
        await handlePregenerateDeferredInvoices()
      } else {
        notificationService.add('error', 'Errore generazione fattura: ' + data.message)
      }
    } catch (error) {
      console.error('Errore generazione fattura:', error)
      notificationService.add('error', 'Errore durante la generazione della fattura')
    } finally {
      setGeneratingDeferredInvoices(false)
    }
  }

  // Invia singola fattura a SDI
  const handleSendSingleInvoiceToFIC = async (invoiceId: number) => {
    try {
      const token = localStorage.getItem('token')
      
      const response = await fetch(`${API_URL}/invoices/${invoiceId}/send-sdi`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      })

      const data = await response.json()
      
      if (data.success) {
        notificationService.add('success', data.message || 'Fattura inviata a Fatture in Cloud / SDI!')
        // Ricarica commissioni dettagliate se siamo nel tab commissioni
        if (activeTab === 'commissions') {
          fetchDetailedCommissions()
        }
      } else {
        notificationService.add('error', data.message || 'Errore durante l\'invio')
      }
    } catch (error) {
      console.error('Errore invio a FIC:', error)
      notificationService.add('error', 'Errore durante l\'invio a Fatture in Cloud')
    }
  }

  // ========== FUNZIONI REPORT STRIPE ==========
  
  const loadStripeReport = async () => {
    setStripeLoading(true)
    try {
      const token = localStorage.getItem('token')
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      const response = await fetch(`${API_URL}/stripe-report/${stripeYear}/${stripeMonth}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const data = await response.json()
      
      if (data.success) {
        setStripeTransactions(data.data.transactions || [])
        setStripeTotals(data.data.totals || null)
        setStripeNeedsNormalization(data.data.needs_normalization || false)
      } else {
        throw new Error(data.error || 'Errore sconosciuto')
      }
    } catch (error) {
      console.error('Errore caricamento report:', error)
      notificationService.add('error', 'Errore durante il caricamento del report: ' + (error instanceof Error ? error.message : 'Errore sconosciuto'))
      // Imposta valori di default in caso di errore
      setStripeTransactions([])
      setStripeTotals(null)
      setStripeNeedsNormalization(false)
    } finally {
      setStripeLoading(false)
    }
  }

  const loadStripeAccountantEmail = async () => {
    try {
      const token = localStorage.getItem('token')
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      const response = await fetch(`${API_URL}/stripe-report/accountant-email`, {
        headers: { 'Authorization': `Bearer ${token}` }
      })
      const data = await response.json()
      if (data.email) {
        setStripeAccountantEmail(data.email)
      }
    } catch (error) {
      console.error('Errore caricamento email:', error)
    }
  }

  const handleStripeNormalize = async () => {
    setStripeNormalizing(true)
    try {
      const token = localStorage.getItem('token')
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      const response = await fetch(`${API_URL}/stripe-report/${stripeYear}/${stripeMonth}/normalize`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const data = await response.json()
      
      if (data.success) {
        setStripeTransactions(data.data.transactions)
        setStripeTotals(data.data.totals)
        setStripeNeedsNormalization(false)
        
        // Salva statistiche normalizzazione per il log
        setNormalizeStats({
          corrections_count: data.data.corrections_count,
          corrections: data.data.corrections || [],
          timestamp: new Date().toISOString()
        })
        setShowNormalizeLog(true)
        
        notificationService.add('success', `Normalizzazione completata! ${data.data.corrections_count} transazioni corrette.`)
        
        // Ricarica il report per vedere i dati aggiornati dal database
        setTimeout(() => loadStripeReport(), 500)
      } else {
        throw new Error(data.message || 'Errore sconosciuto')
      }
    } catch (error) {
      console.error('Errore normalizzazione:', error)
      notificationService.add('error', 'Errore durante la normalizzazione: ' + (error instanceof Error ? error.message : 'Errore sconosciuto'))
    } finally {
      setStripeNormalizing(false)
    }
  }

  const handleStripeReset = async () => {
    if (!confirm('Sei sicuro di voler resettare tutte le normalizzazioni Stripe? Questa operazione rimuoverà tutte le correzioni automatiche e manuali.')) {
      return
    }

    setStripeResetting(true)
    try {
      const token = localStorage.getItem('token')
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      const response = await fetch(`${API_URL}/stripe-report/reset`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()

      if (data.success) {
        notificationService.add('success', data.message || 'Reset completato!')

        // Ricarica il report per vedere i dati resettati
        await loadStripeReport()

        // Dopo il reset, esegui automaticamente la normalizzazione
        setTimeout(() => {
          handleStripeNormalize()
        }, 1000)
      } else {
        throw new Error(data.error || 'Errore sconosciuto')
      }
    } catch (error) {
      console.error('Errore reset:', error)
      notificationService.add('error', 'Errore durante il reset: ' + (error instanceof Error ? error.message : 'Errore sconosciuto'))
    } finally {
      setStripeResetting(false)
    }
  }

  const handleStripeExport = async () => {
    setStripeExporting(true)
    try {
      const token = localStorage.getItem('token')
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      const response = await fetch(`${API_URL}/stripe-report/${stripeYear}/${stripeMonth}/export`, {
        headers: { 'Authorization': `Bearer ${token}` }
      })
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `stripe_report_${stripeYear}_${stripeMonth}.xlsx`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      notificationService.add('success', 'Report esportato con successo!')
    } catch (error) {
      console.error('Errore export:', error)
      notificationService.add('error', 'Errore durante l\'export')
    } finally {
      setStripeExporting(false)
    }
  }

  const handleStripeSendToAccountant = async () => {
    if (!stripeAccountantEmail) {
      notificationService.add('error', 'Inserisci l\'email del commercialista')
      return
    }

    setStripeSending(true)
    try {
      const token = localStorage.getItem('token')
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      await fetch(`${API_URL}/stripe-report/${stripeYear}/${stripeMonth}/send`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          email: stripeAccountantEmail,
          save_email: stripeSaveEmail
        })
      })
      notificationService.add('success', 'Report inviato con successo!')
    } catch (error) {
      console.error('Errore invio email:', error)
      notificationService.add('error', 'Errore durante l\'invio')
    } finally {
      setStripeSending(false)
    }
  }

  const handleStripeSort = (column: 'id' | 'type' | 'source' | 'amount' | 'fee' | 'net' | 'date') => {
    if (stripeSortColumn === column) {
      setStripeSortDirection(stripeSortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setStripeSortColumn(column)
      setStripeSortDirection('desc')
    }
  }

  // Ordina le transazioni Stripe
  const sortedStripeTransactions = React.useMemo(() => {
    if (!stripeSortColumn || !stripeTransactions || stripeTransactions.length === 0) {
      return stripeTransactions
    }

    return [...stripeTransactions].sort((a, b) => {
      let comparison = 0

      switch (stripeSortColumn) {
        case 'id':
          comparison = (a.transaction_id || '').localeCompare(b.transaction_id || '')
          break
        case 'type':
          comparison = (a.type || '').localeCompare(b.type || '')
          break
        case 'source':
          comparison = (a.source || '').localeCompare(b.source || '')
          break
        case 'amount':
          comparison = parseFloat(a.amount || 0) - parseFloat(b.amount || 0)
          break
        case 'fee':
          comparison = parseFloat(a.fee || 0) - parseFloat(b.fee || 0)
          break
        case 'net':
          comparison = parseFloat(a.net || 0) - parseFloat(b.net || 0)
          break
        case 'date':
          comparison = new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
          break
      }

      return stripeSortDirection === 'asc' ? comparison : -comparison
    })
  }, [stripeTransactions, stripeSortColumn, stripeSortDirection])

  // Auto-load quando cambia mese/anno nella scheda Stripe Report
  useEffect(() => {
    if (activeTab === 'stripe-report') {
      loadStripeReport()
      loadStripeAccountantEmail()
    }
  }, [activeTab, stripeYear, stripeMonth])

  const handleSort = (column: 'date' | 'amount' | 'type' | 'source' | 'description' | 'beneficiary' | 'status') => {
    if (sortColumn === column) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortColumn(column)
      setSortDirection('desc')
    }
  }

  const filteredAndSortedPayments = payments
    .filter(payment => {
      // Usa display_type se disponibile, altrimenti type
      const paymentType = payment.display_type || payment.type
      
      // Filtro per ricerca testuale
      if (searchTerm) {
        const search = searchTerm.toLowerCase()
        if (
          !payment.description?.toLowerCase().includes(search) &&
          !payment.transaction_id?.toLowerCase().includes(search) &&
          !payment.amount.toString().includes(search)
        ) {
          return false
        }
      }

      // Filtro per tipo
      if (typeFilter !== 'all' && paymentType !== typeFilter) {
        return false
      }

      // Filtro per stato
      if (statusFilter !== 'all' && payment.status !== statusFilter) {
        return false
      }

      // Filtro per data inizio
      if (dateFrom && new Date(payment.transaction_date) < new Date(dateFrom)) {
        return false
      }

      // Filtro per data fine
      if (dateTo && new Date(payment.transaction_date) > new Date(dateTo)) {
        return false
      }

      // Filtro per sorgente
      if (sourceFilter !== 'all' && payment.source?.toUpperCase() !== sourceFilter) {
        return false
      }

      // Filtro per destinatario trasferimento (per transazioni Stripe)
      if (transferDestFilter && payment.source?.toUpperCase() === 'STRIPE') {
        const destSearch = transferDestFilter.toLowerCase()
        const sourceData = payment.source_data ? JSON.parse(payment.source_data) : null
        const transferDest = sourceData?.transfer_destination || ''
        if (!transferDest.toLowerCase().includes(destSearch)) {
          return false
        }
      }

      return true
    })
    .sort((a, b) => {
      if (!sortColumn) return 0

      let comparison = 0

      switch (sortColumn) {
        case 'date':
          comparison = new Date(a.transaction_date).getTime() - new Date(b.transaction_date).getTime()
          break
        case 'amount':
          comparison = Math.abs(a.amount) - Math.abs(b.amount)
          break
        case 'type':
          comparison = (a.display_type || a.type).localeCompare(b.display_type || b.type)
          break
        case 'source':
          comparison = (a.source || '').localeCompare(b.source || '')
          break
        case 'description':
          comparison = (a.description || '').localeCompare(b.description || '')
          break
        case 'beneficiary':
          comparison = (a.beneficiario || '').localeCompare(b.beneficiario || '')
          break
        case 'status':
          comparison = a.status.localeCompare(b.status)
          break
      }

      return sortDirection === 'asc' ? comparison : -comparison
    })

  // Paginazione
  const totalPages = Math.ceil(filteredAndSortedPayments.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const paginatedPayments = filteredAndSortedPayments.slice(startIndex, endIndex)

  // Calcola statistiche dai pagamenti filtrati
  const filteredStats = React.useMemo(() => {
    if (!filteredAndSortedPayments || filteredAndSortedPayments.length === 0) {
      return {
        income: 0,
        expenses: 0,
        pending: 0,
        failed: 0,
        income_change: 0,
        last_sync: stats?.last_sync || null
      }
    }

    const income = filteredAndSortedPayments
      .filter(p => {
        const paymentType = p.display_type || p.type
        return paymentType === 'income' && p.status === 'completed'
      })
      .reduce((sum, p) => sum + Math.abs(p.amount || 0), 0)
    
    const expenses = filteredAndSortedPayments
      .filter(p => {
        const paymentType = p.display_type || p.type
        return ['expense', 'withdrawal', 'fee'].includes(paymentType) && p.status === 'completed'
      })
      .reduce((sum, p) => sum + Math.abs(p.amount || 0), 0)
    
    const pending = filteredAndSortedPayments
      .filter(p => p.status === 'pending')
      .reduce((sum, p) => sum + Math.abs(p.amount || 0), 0)
    
    const failed = filteredAndSortedPayments
      .filter(p => p.status === 'failed')
      .length
    
    return {
      income: Number(income) || 0,
      expenses: Number(expenses) || 0,
      pending: Number(pending) || 0,
      failed: Number(failed) || 0,
      income_change: 0,
      last_sync: stats?.last_sync || null
    }
  }, [filteredAndSortedPayments, stats])

  // Filtro in tempo reale per aggregazione clienti per destinazione
  useEffect(() => {
    if (activeTab === 'aggregate' && aggregateSubTab === 'client') {
      const timeoutId = setTimeout(() => {
        fetchAggregatedData()
      }, 500) // Debounce di 500ms per evitare troppe chiamate mentre l'utente digita
      
      return () => clearTimeout(timeoutId)
    }
  }, [aggregateDestFilter])

  // Reset alla prima pagina quando cambiano i filtri
  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, typeFilter, statusFilter, sourceFilter, transferDestFilter, dateFrom, dateTo])

  const handleExportAllCSV = async () => {
    setExportingCSV(true)
    try {
      const response = await paymentsApi.export()
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `pagamenti_${new Date().toISOString().split('T')[0]}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error: any) {
      console.error('Errore esportazione:', error)
      alert('Errore durante l\'esportazione: ' + (error.response?.data?.message || error.message))
    } finally {
      setExportingCSV(false)
    }
  }

  return (
    <div className="space-y-6 max-w-full overflow-hidden">
      {/* Header */}
      <div className="flex flex-wrap justify-between items-center py-2 gap-4">
        <div>
          <h1 className="text-3xl font-bold">
            <span className="text-gradient">Pagamenti</span>
          </h1>
          <p className="text-gray-500 mt-1">Gestione pagamenti e transazioni Stripe</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleExportAllCSV}
            disabled={exportingCSV}
            className="glass-button flex items-center gap-2 disabled:opacity-50"
          >
            <Download className={`w-4 h-4 ${exportingCSV ? 'animate-pulse' : ''}`} />
            {exportingCSV ? 'Esportazione...' : 'Esporta CSV'}
          </motion.button>
          <button
            onClick={() => handleStripeImport(false)}
            disabled={importingStripe}
            className="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-lg hover:shadow-xl"
          >
            {importingStripe ? (
              <>
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                <span>Sincronizzazione...</span>
              </>
            ) : (
              <>
                <RefreshCw className="w-5 h-5" />
                <span>Sincronizza Stripe</span>
              </>
            )}
          </button>
        </div>
      </div>


      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 max-w-full overflow-hidden">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="glass-card p-6"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">
                Entrate {(dateFrom || dateTo) ? 'Filtrate' : 'Mese'}
              </p>
              <p className="text-2xl font-bold mt-1 text-green-400">
                {formatAmount(Number(filteredStats.income) || 0)}
              </p>
            </div>
            <TrendingUp className="w-8 h-8 text-green-400" />
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
              <p className="text-gray-500 text-sm">
                Uscite {(dateFrom || dateTo) ? 'Filtrate' : 'Mese'}
              </p>
              <p className="text-2xl font-bold mt-1 text-red-400">
                {formatAmount(Number(filteredStats.expenses) || 0)}
              </p>
            </div>
            <TrendingDown className="w-8 h-8 text-red-400" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="glass-card p-6"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-sm">
                In Attesa {(dateFrom || dateTo) ? 'Filtrati' : ''}
              </p>
              <p className="text-2xl font-bold mt-1 text-yellow-400">
                {formatAmount(Number(filteredStats.pending) || 0)}
              </p>
            </div>
            <Clock className="w-8 h-8 text-yellow-400" />
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
              <p className="text-gray-500 text-sm">
                Falliti {(dateFrom || dateTo) ? 'Filtrati' : ''}
              </p>
              <p className="text-2xl font-bold mt-1 text-red-400">
                {filteredStats.failed}
              </p>
            </div>
            <XCircle className="w-8 h-8 text-red-400" />
          </div>
        </motion.div>
      </div>

      {/* Tabs */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.5 }}
        className="overflow-x-auto border-b border-gray-200 -mx-4 px-4 md:mx-0 md:px-0"
      >
        <div className="flex gap-2 min-w-max">
          <button
            onClick={() => setActiveTab('payments')}
            className={`px-6 py-3 font-medium transition-all whitespace-nowrap ${
              activeTab === 'payments'
                ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-500'
                : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <div className="flex items-center gap-2">
              <List className="w-4 h-4" />
              Tutti i Pagamenti
            </div>
          </button>
          <button
            onClick={() => setActiveTab('import')}
            className={`px-6 py-3 font-medium transition-all whitespace-nowrap ${
              activeTab === 'import'
                ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-500'
                : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <div className="flex items-center gap-2">
              <Upload className="w-4 h-4" />
              Importa CSV
            </div>
          </button>
          <button
            onClick={() => {
              setActiveTab('commissions')
              if (applicationFees.length === 0) fetchApplicationFees()
            }}
            className={`px-6 py-3 font-medium transition-all whitespace-nowrap ${
              activeTab === 'commissions'
                ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-500'
                : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <div className="flex items-center gap-2">
              <DollarSign className="w-4 h-4" />
              Fatturazione differita
            </div>
          </button>
          <button
            onClick={() => setActiveTab('ordinary-invoicing')}
            className={`px-6 py-3 font-medium transition-all whitespace-nowrap ${
              activeTab === 'ordinary-invoicing'
                ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-500'
                : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <div className="flex items-center gap-2">
              <FileText className="w-4 h-4" />
              Fatturazione Ordinaria
            </div>
          </button>
          <button
            onClick={() => setActiveTab('stripe-report')}
            className={`px-6 py-3 font-medium transition-all whitespace-nowrap ${
              activeTab === 'stripe-report'
                ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-500'
                : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <div className="flex items-center gap-2">
              <BarChart3 className="w-4 h-4" />
              Report Stripe
            </div>
          </button>
          <button
            onClick={() => setActiveTab('payment-links')}
            className={`px-6 py-3 font-medium transition-all whitespace-nowrap ${
              activeTab === 'payment-links'
                ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-500'
                : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <div className="flex items-center gap-2">
              <Link2 className="w-4 h-4" />
              Link Pagamento
            </div>
          </button>
        </div>
      </motion.div>

      {/* Log Sincronizzazione Stripe (collassabile) */}
      {showSyncLog && syncStats && (
        <motion.div
          initial={{ opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: 'auto' }}
          className="glass-card overflow-hidden"
        >
          <div
            className="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50/30 transition-colors"
            onClick={() => setShowSyncLog(!showSyncLog)}
          >
            <div className="flex items-center gap-3">
              <FileText className="w-5 h-5 text-primary-400" />
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white">Log Ultima Sincronizzazione</h3>
                <p className="text-sm text-gray-500">
                  {syncStats.period?.start} - {syncStats.period?.end}
                </p>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <div className="flex gap-3 text-sm">
                <span className="text-green-400">{syncStats.imported || 0} importate</span>
                <span className="text-primary-400">{syncStats.skipped || 0} già presenti</span>
                {syncStats.errors && syncStats.errors.length > 0 && (
                  <span className="text-red-400">{syncStats.errors.length} errori</span>
                )}
              </div>
              <X className="w-5 h-5 text-gray-500 hover:text-gray-900 dark:hover:text-white" onClick={(e) => {
                e.stopPropagation()
                setShowSyncLog(false)
              }} />
            </div>
          </div>

          <div className="p-4 border-t border-gray-200 bg-gray-900/50">
            {/* Tipi di Transazioni */}
            {syncStats.type_stats && Object.keys(syncStats.type_stats).length > 0 && (
              <div className="mb-4">
                <h4 className="text-sm font-semibold text-gray-600 mb-2">Tipi di Transazioni Ricevute dall'API Stripe:</h4>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                  {Object.entries(syncStats.type_stats)
                    .sort((a: any, b: any) => b[1] - a[1])
                    .map(([type, count]: [string, any]) => (
                      <div key={type} className={`p-2 rounded border ${
                        type === 'transfer' ? 'bg-purple-900/20 border-purple-500/30' :
                        type === 'charge' ? 'bg-green-900/20 border-green-500/30' :
                        type === 'payment' ? 'bg-primary-900/20 border-primary-500/30' :
                        type === 'application_fee' ? 'bg-yellow-900/20 border-yellow-500/30' :
                        type === 'stripe_fee' ? 'bg-red-900/20 border-red-500/30' :
                        'bg-gray-50 border-gray-200'
                      }`}>
                        <div className="flex justify-between items-center">
                          <span className={`text-xs font-medium ${
                            type === 'transfer' ? 'text-purple-400' :
                            type === 'charge' ? 'text-green-400' :
                            type === 'payment' ? 'text-primary-400' :
                            type === 'application_fee' ? 'text-yellow-400' :
                            type === 'stripe_fee' ? 'text-red-400' :
                            'text-gray-500'
                          }`}>{type}</span>
                          <span className="text-gray-900 dark:text-white font-bold">{count}</span>
                        </div>
                      </div>
                    ))}
                </div>
              </div>
            )}

            {/* Alert se non ci sono transfer */}
            {syncStats.type_stats && (!syncStats.type_stats.transfer || syncStats.type_stats.transfer === 0) && (
              <div className="bg-yellow-900/20 border border-yellow-500/30 rounded-lg p-3 text-sm">
                <div className="flex items-start gap-2">
                  <AlertTriangle className="w-4 h-4 text-yellow-400 flex-shrink-0 mt-0.5" />
                  <div className="text-yellow-200">
                    <strong>Nessun transfer trovato</strong> - L'API Stripe non ha restituito balance transactions di tipo "transfer". 
                    Potrebbe essere un account Standard invece di Connect, oppure non ci sono stati trasferimenti nel periodo selezionato.
                  </div>
                </div>
              </div>
            )}

            {/* Errori */}
            {syncStats.errors && syncStats.errors.length > 0 && (
              <div className="mt-4">
                <h4 className="text-sm font-semibold text-red-400 mb-2">Errori:</h4>
                <div className="bg-red-900/10 border border-red-500/30 rounded p-3 max-h-40 overflow-y-auto">
                  <ul className="text-sm text-gray-600 space-y-1">
                    {syncStats.errors.map((error: string, index: number) => (
                      <li key={index} className="flex gap-2">
                        <span className="text-red-400">\u2022</span>
                        <span>{error}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            )}
          </div>
        </motion.div>
      )}

      {/* Import CSV Tab */}
      {activeTab === 'import' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card p-6"
        >
          <h2 className="text-xl font-bold mb-4">Importa Estratto Conto CSV</h2>
          <p className="text-gray-500 mb-6">
            Importa transazioni da diverse sorgenti di pagamento. Il sistema riconosce automaticamente la sorgente dal formato del file.
          </p>

          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-600 mb-2">
                File CSV
              </label>
              <input
                type="file"
                accept=".csv"
                onChange={(e) => setSelectedFile(e.target.files?.[0] || null)}
                className="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-600 file:text-white hover:file:bg-primary-700"
              />
            </div>

            <button
              onClick={handleImportCSV}
              disabled={!selectedFile || importing}
              className="w-full bg-primary-600 hover:bg-primary-700 disabled:bg-gray-100 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
            >
              {importing ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  Importazione in corso...
                </>
              ) : (
                <>
                  <Upload className="w-5 h-5" />
                  Importa Transazioni
                </>
              )}
            </button>
          </div>

          <div className="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 className="font-semibold mb-2">Note sull'importazione:</h3>
            <ul className="text-sm text-gray-500 space-y-1 list-disc list-inside">
              <li>La sorgente viene <strong className="text-green-400">riconosciuta automaticamente</strong> dalla struttura del CSV</li>
              <li>Le transazioni duplicate verranno ignorate automaticamente</li>
              <li>Il sistema identifica automaticamente i clienti</li>
              <li>Formati supportati: <strong>Banca (CRV)</strong>, <strong>Vivawallet</strong>, <strong>Nexi</strong>, <strong>PayPal</strong></li>
              <li><strong>Stripe</strong> viene sincronizzato automaticamente (non serve import)</li>
            </ul>
          </div>
        </motion.div>
      )}

      {/* Fatturazione differita Tab */}
      {activeTab === 'commissions' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card p-6"
        >
          <div className="flex justify-between items-center mb-6">
            <div>
              <h2 className="text-xl font-bold">Fatturazione differita - Commissioni Stripe</h2>
              <p className="text-gray-500 text-sm mt-1">
                Commissioni OPPLA riscosse tramite Stripe Connect da fatturare ai partner
              </p>
            </div>
            <div className="flex gap-2">
              {/* Pulsante Genera Fatture Differite */}
              <button
                onClick={handleOpenDeferredInvoicing}
                className="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-4 py-2 rounded-lg flex items-center gap-2 font-medium shadow-lg"
              >
                <FileText className="w-4 h-4" />
                Genera Fatture Differite
              </button>
              
              <button
                onClick={handleExportCommissions}
                disabled={exportingCommissions}
                className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2"
              >
                {exportingCommissions ? (
                  <Loader2 className="w-4 h-4 animate-spin" />
                ) : (
                  <Download className="w-4 h-4" />
                )}
                Export Excel
              </button>
              <button
                onClick={fetchDetailedCommissions}
                disabled={loadingDetailedCommissions}
                className="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center gap-2"
              >
                {loadingDetailedCommissions ? (
                  <Loader2 className="w-4 h-4 animate-spin" />
                ) : (
                  <RefreshCw className="w-4 h-4" />
                )}
                Aggiorna
              </button>
            </div>
          </div>

          {/* Filtri periodo e ricerca */}
          <div className="mb-6 p-4 bg-gray-50/50 rounded-lg border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Periodo (Mese)
                </label>
                <input
                  type="month"
                  value={commissionsPeriod}
                  min="2025-12"
                  onChange={(e) => {
                    setCommissionsPeriod(e.target.value)
                    // Auto-fetch quando cambia periodo
                    setTimeout(() => fetchDetailedCommissions(), 100)
                  }}
                  className="glass-input w-full"
                />
              </div>
              
              <div className="flex items-end">
                <div className="relative w-full">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5" />
                  <input
                    type="text"
                    placeholder="Cerca per email, account ID, charge..."
                    value={commissionSearchTerm}
                    onChange={(e) => setCommissionSearchTerm(e.target.value)}
                    className="glass-input w-full pl-10"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* VISTA DETTAGLIATA - Stile Dashboard Stripe */}
          {loadingDetailedCommissions ? (
                <div className="text-center py-12">
                  <Loader2 className="w-16 h-16 text-gray-500 mx-auto mb-4 animate-spin" />
                  <p className="text-gray-500">Caricamento commissioni dettagliate...</p>
                </div>
              ) : detailedCommissions.length === 0 ? (
                <div className="text-center py-12">
                  <DollarSign className="w-16 h-16 text-gray-500 mx-auto mb-4" />
                  <p className="text-gray-500">Nessuna commissione per il periodo selezionato</p>
                  <p className="text-sm text-gray-400 mt-2">Seleziona un periodo diverso</p>
                </div>
              ) : (
                <>
                  {/* Totali */}
                  <div className="mb-4 p-4 bg-green-600/10 border border-green-600/30 rounded-lg">
                    <div className="flex justify-between items-center">
                      <div>
                        <p className="text-sm text-gray-500">Totale Commissioni</p>
                        <p className="text-2xl font-bold text-green-400">
                          {formatAmount(detailedCommissions.reduce((sum, fee) => sum + fee.amount, 0))}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm text-gray-500">N° Transazioni</p>
                        <p className="text-2xl font-bold text-gray-900 dark:text-white">
                          {detailedCommissions.filter(fee => 
                            !commissionSearchTerm || 
                            fee.partner_email?.toLowerCase().includes(commissionSearchTerm.toLowerCase()) ||
                            fee.stripe_account_id?.toLowerCase().includes(commissionSearchTerm.toLowerCase()) ||
                            fee.charge_id?.toLowerCase().includes(commissionSearchTerm.toLowerCase())
                          ).length}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Tabella Stile Dashboard Stripe */}
                  <div className="overflow-x-auto">
                    <table className="w-full">
                      <thead>
                        <tr className="border-b border-gray-200">
                          <th 
                            className="text-left px-4 py-3 text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white"
                            onClick={() => {
                              if (commissionSortColumn === 'amount') {
                                setCommissionSortDirection(commissionSortDirection === 'asc' ? 'desc' : 'asc')
                              } else {
                                setCommissionSortColumn('amount')
                                setCommissionSortDirection('desc')
                              }
                            }}
                          >
                            IMPORTO {commissionSortColumn === 'amount' && (commissionSortDirection === 'asc' ? '↑' : '↓')}
                          </th>
                          <th className="text-left px-4 py-3 text-sm font-semibold text-gray-500">DESCRIZIONE</th>
                          <th className="text-center px-4 py-3 text-sm font-semibold text-gray-500">TIPO</th>
                          <th 
                            className="text-center px-4 py-3 text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white"
                            onClick={() => {
                              if (commissionSortColumn === 'date') {
                                setCommissionSortDirection(commissionSortDirection === 'asc' ? 'desc' : 'asc')
                              } else {
                                setCommissionSortColumn('date')
                                setCommissionSortDirection('desc')
                              }
                            }}
                          >
                            DATA {commissionSortColumn === 'date' && (commissionSortDirection === 'asc' ? '↑' : '↓')}
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        {detailedCommissions
                          .filter(fee => 
                            !commissionSearchTerm || 
                            fee.partner_email?.toLowerCase().includes(commissionSearchTerm.toLowerCase()) ||
                            fee.stripe_account_id?.toLowerCase().includes(commissionSearchTerm.toLowerCase()) ||
                            fee.charge_id?.toLowerCase().includes(commissionSearchTerm.toLowerCase()) ||
                            fee.description?.toLowerCase().includes(commissionSearchTerm.toLowerCase())
                          )
                          .sort((a, b) => {
                            if (commissionSortColumn === 'amount') {
                              return commissionSortDirection === 'asc' 
                                ? a.amount - b.amount 
                                : b.amount - a.amount
                            }
                            if (commissionSortColumn === 'date') {
                              return commissionSortDirection === 'asc'
                                ? new Date(a.created).getTime() - new Date(b.created).getTime()
                                : new Date(b.created).getTime() - new Date(a.created).getTime()
                            }
                            if (commissionSortColumn === 'email') {
                              return commissionSortDirection === 'asc'
                                ? (a.partner_email || '').localeCompare(b.partner_email || '')
                                : (b.partner_email || '').localeCompare(a.partner_email || '')
                            }
                            return 0
                          })
                          .map((fee, idx) => (
                            <tr key={idx} className="border-b border-gray-200 hover:bg-gray-50/30 transition-colors">
                              <td className="px-4 py-3">
                                <div className="flex items-center gap-2">
                                  <span className="text-green-400 font-bold text-lg">
                                    {formatAmount(fee.amount)}
                                  </span>
                                  {!fee.client_id && (
                                    <span className="text-xs text-yellow-400" title="Cliente non collegato">⚠️</span>
                                  )}
                                </div>
                              </td>
                              <td className="px-4 py-3">
                                <div className="text-sm">
                                  <p className="text-gray-900 dark:text-white font-medium">{fee.partner_email || 'N/A'}</p>
                                  <p className="text-gray-500 text-xs font-mono">{fee.stripe_account_id}</p>
                                  {fee.charge_id && (
                                    <p className="text-gray-400 text-xs font-mono mt-1">{fee.charge_id}</p>
                                  )}
                                </div>
                              </td>
                              <td className="px-4 py-3 text-center">
                                <span className="px-2 py-1 bg-purple-600/20 text-purple-400 rounded text-xs font-medium">
                                  Charge
                                </span>
                              </td>
                              <td className="px-4 py-3 text-center text-sm text-gray-600">
                                {new Date(fee.created).toLocaleString('it-IT', {
                                  day: '2-digit',
                                  month: '2-digit',
                                  year: '2-digit',
                                  hour: '2-digit',
                                  minute: '2-digit'
                                })}
                              </td>
                            </tr>
                          ))}
                      </tbody>
                    </table>
                  </div>
                </>
              )}
        </motion.div>
      )}



      {/* Filters - Solo per tab payments */}
      {activeTab === 'payments' && (
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.6 }}
        className="glass-card p-6 overflow-hidden"
      >
        <div className="flex flex-col gap-4 max-w-full">
          {/* Riga 1: Barra di ricerca */}
          <div className="w-full">
            <div className="relative max-w-full">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5" />
              <input
                type="text"
                placeholder="Cerca per importo, descrizione, cliente..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="glass-input pl-10 w-full"
              />
            </div>
          </div>

          {/* Riga 2: Filtri dropdown */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value as PaymentType | 'all')}
              className="glass-input w-full truncate"
            >
              <option value="all">Tutti i tipi</option>
              <option value="income">Entrate</option>
              <option value="expense">Uscite</option>
              <option value="withdrawal">Prelievi</option>
              <option value="fee">Commissioni</option>
            </select>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value as PaymentStatus | 'all')}
              className="glass-input w-full truncate"
            >
              <option value="all">Tutti gli stati</option>
              <option value="completed">Completato</option>
              <option value="pending">In Attesa</option>
              <option value="failed">Fallito</option>
            </select>
            <select
              value={sourceFilter}
              onChange={(e) => setSourceFilter(e.target.value)}
              className="glass-input w-full truncate"
            >
              <option value="all">Tutte le sorgenti</option>
              <option value="STRIPE">Stripe</option>
              <option value="BANK">CRV/Banca</option>
              <option value="NEXI">Nexi</option>
              <option value="PAYPAL">PayPal</option>
              <option value="VIVAWALLET">Vivawallet</option>
            </select>
          </div>
          
          {/* Filtro destinatario trasferimento per Stripe */}
          {sourceFilter === 'STRIPE' && (
            <div className="w-full">
              <label className="block text-sm text-gray-500 mb-2">Destinatario Trasferimento</label>
              <input
                type="text"
                placeholder="Filtra per destinatario..."
                value={transferDestFilter}
                onChange={(e) => setTransferDestFilter(e.target.value)}
                className="glass-input w-full"
              />
            </div>
          )}

          {/* Riga 3: Filtri per data ed elementi per pagina */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div>
              <label className="block text-sm text-gray-500 mb-2">Data da</label>
              <input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                className="glass-input w-full"
              />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-2">Data a</label>
              <input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                className="glass-input w-full"
              />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-2">Elementi per pagina</label>
              <select
                value={itemsPerPage}
                onChange={(e) => {
                  setItemsPerPage(Number(e.target.value))
                  setCurrentPage(1)
                }}
                className="glass-input w-full"
              >
                <option value={15}>15 per pagina</option>
                <option value={30}>30 per pagina</option>
                <option value={50}>50 per pagina</option>
                <option value={100}>100 per pagina</option>
                <option value={filteredAndSortedPayments.length}>Tutti ({filteredAndSortedPayments.length})</option>
              </select>
            </div>
          </div>

          {/* Bottone Reset Date */}
          <div className="flex justify-start">
            <button
              onClick={() => {
                setDateFrom(getDefaultDateFrom())
                setDateTo(getDefaultDateTo())
              }}
              className="glass-button px-4 py-2 text-sm"
            >
              Reset Date
            </button>
          </div>
        </div>
      </motion.div>
      )}

      {/* Empty State or Payments List - Solo per tab payments */}
      {activeTab === 'payments' && (
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.7 }}
        className="glass-card overflow-hidden"
      >
        {/* Controlli paginazione superiori */}
        {!loading && totalPages > 1 && (
          <div className="px-6 py-4 border-b border-gray-200 bg-gray-50/30 flex items-center justify-between">
            <div className="text-sm text-gray-500">
              Pagina {currentPage} di {totalPages}
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
                className="glass-button px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Precedente
              </button>
              <button
                onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                disabled={currentPage === totalPages}
                className="glass-button px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Successiva
              </button>
            </div>
          </div>
        )}

        {/* Contatore risultati */}
        {!loading && payments.length > 0 && (
          <div className="px-6 py-3 border-b border-gray-200 bg-gray-50/30">
            <p className="text-sm text-gray-500">
              Mostrati <span className="text-gray-900 dark:text-white font-semibold">{startIndex + 1}-{Math.min(endIndex, filteredAndSortedPayments.length)}</span> di <span className="text-gray-900 dark:text-white font-semibold">{filteredAndSortedPayments.length}</span> pagamenti
            </p>
          </div>
        )}
        
        {loading ? (
          <div className="p-12 text-center">
            <Loader2 className="w-16 h-16 text-gray-500 mx-auto mb-4 animate-spin" />
            <p className="text-gray-500">Caricamento pagamenti...</p>
          </div>
        ) : filteredAndSortedPayments.length === 0 ? (
          <div className="p-12 text-center">
            <CreditCard className="w-16 h-16 text-gray-500 mx-auto mb-4" />
            <h3 className="text-xl font-semibold mb-2">Nessun Pagamento Trovato</h3>
            <p className="text-gray-500 mb-6">
              Prova a modificare i filtri di ricerca
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-max">
              <thead>
                <tr className="border-b border-gray-200">
                  <th 
                    className="px-6 py-4 text-left text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors min-w-[120px]"
                    onClick={() => handleSort('date')}
                  >
                    <div className="flex items-center gap-2">
                      Data
                      {sortColumn === 'date' && (
                        sortDirection === 'asc' ? <ArrowUp className="w-4 h-4" /> : <ArrowDown className="w-4 h-4" />
                      )}
                      {sortColumn !== 'date' && <ArrowUpDown className="w-4 h-4 opacity-30" />}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-left text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors min-w-[100px]"
                    onClick={() => handleSort('source')}
                  >
                    <div className="flex items-center gap-2">
                      Sorgente
                      {sortColumn === 'source' && (
                        sortDirection === 'asc' ? <ArrowUp className="w-4 h-4" /> : <ArrowDown className="w-4 h-4" />
                      )}
                      {sortColumn !== 'source' && <ArrowUpDown className="w-4 h-4 opacity-30" />}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-left text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors min-w-[300px]"
                    onClick={() => handleSort('description')}
                  >
                    <div className="flex items-center gap-2">
                      Descrizione
                      {sortColumn === 'description' && (
                        sortDirection === 'asc' ? <ArrowUp className="w-4 h-4" /> : <ArrowDown className="w-4 h-4" />
                      )}
                      {sortColumn !== 'description' && <ArrowUpDown className="w-4 h-4 opacity-30" />}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-left text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors min-w-[200px]"
                    onClick={() => handleSort('beneficiary')}
                  >
                    <div className="flex items-center gap-2">
                      Beneficiario
                      {sortColumn === 'beneficiary' && (
                        sortDirection === 'asc' ? <ArrowUp className="w-4 h-4" /> : <ArrowDown className="w-4 h-4" />
                      )}
                      {sortColumn !== 'beneficiary' && <ArrowUpDown className="w-4 h-4 opacity-30" />}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-left text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors min-w-[100px]"
                    onClick={() => handleSort('type')}
                  >
                    <div className="flex items-center gap-2">
                      Tipo
                      {sortColumn === 'type' && (
                        sortDirection === 'asc' ? <ArrowUp className="w-4 h-4" /> : <ArrowDown className="w-4 h-4" />
                      )}
                      {sortColumn !== 'type' && <ArrowUpDown className="w-4 h-4 opacity-30" />}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-right text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors min-w-[120px]"
                    onClick={() => handleSort('amount')}
                  >
                    <div className="flex items-center justify-end gap-2">
                      Importo
                      {sortColumn === 'amount' && (
                        sortDirection === 'asc' ? <ArrowUp className="w-4 h-4" /> : <ArrowDown className="w-4 h-4" />
                      )}
                      {sortColumn !== 'amount' && <ArrowUpDown className="w-4 h-4 opacity-30" />}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-center text-sm font-semibold text-gray-500 cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors min-w-[140px]"
                    onClick={() => handleSort('status')}
                  >
                    <div className="flex items-center justify-center gap-2">
                      Stato
                      {sortColumn === 'status' && (
                        sortDirection === 'asc' ? <ArrowUp className="w-4 h-4" /> : <ArrowDown className="w-4 h-4" />
                      )}
                      {sortColumn !== 'status' && <ArrowUpDown className="w-4 h-4 opacity-30" />}
                    </div>
                  </th>
                  <th className="px-6 py-4 text-center text-sm font-semibold text-gray-500 min-w-[100px]">Azioni</th>
                </tr>
              </thead>
              <tbody>
                {paginatedPayments.map((payment) => {
                  const StatusIcon = getStatusIcon(payment.status)
                  return (
                    <tr 
                      key={payment.id} 
                      className="border-b border-gray-200/50 hover:bg-gray-50/30 transition-colors cursor-pointer"
                      onClick={() => {
                        setSelectedPayment(payment)
                        setShowPaymentDetails(true)
                      }}
                    >
                      <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                        {new Date(payment.transaction_date).toLocaleDateString('it-IT')}
                      </td>
                      <td className="px-4 py-3">
                        {payment.source ? (
                          <span className={`text-xs px-2 py-1 rounded font-medium ${
                            payment.source === 'stripe' ? 'bg-purple-500/20 text-purple-400' :
                            payment.source === 'bank' ? 'bg-primary-500/20 text-primary-400' :
                            payment.source === 'paypal' ? 'bg-primary-600/20 text-primary-300' :
                            payment.source === 'vivawallet' ? 'bg-orange-500/20 text-orange-400' :
                            payment.source === 'nexi' ? 'bg-green-500/20 text-green-400' :
                            'bg-slate-500/20 text-gray-500'
                          }`}>
                            {payment.source === 'bank' ? 'CRV' : 
                             payment.source === 'vivawallet' ? 'VIVAWALLET' :
                             payment.source === 'nexi' ? 'NEXI' :
                             payment.source === 'paypal' ? 'PAYPAL' :
                             payment.source === 'stripe' ? 'STRIPE' :
                             payment.source.toUpperCase()}
                          </span>
                        ) : (
                          <span className="text-xs text-gray-400">-</span>
                        )}
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900 dark:text-white">{payment.descrizione || payment.description || 'N/A'}</div>
                        <div className="text-xs text-gray-500">{payment.source_transaction_id || payment.transaction_id || ''}</div>
                        {payment.causale && <div className="text-xs text-gray-400">{payment.causale}</div>}
                        {payment.source === 'stripe' && payment.source_data && (() => {
                          try {
                            const sourceData = JSON.parse(payment.source_data)
                            return sourceData.transfer_destination && (
                              <div className="text-xs text-primary-400 mt-1">
                                Dest: {sourceData.transfer_destination}
                              </div>
                            )
                          } catch (e) {
                            return null
                          }
                        })()}
                      </td>
                      <td className="px-4 py-3">
                        <div className="text-sm text-gray-600">{payment.beneficiario || '-'}</div>
                        {payment.is_reconciled && (
                          <span className="text-xs text-green-400">Riconciliato</span>
                        )}
                      </td>
                      <td className="px-6 py-4">
                        <span className={`text-sm font-semibold ${getTypeColor(payment.display_type || payment.type)}`}>
                          {getTypeLabel(payment.display_type || payment.type)}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-right">
                        <span className={`text-sm font-bold ${getTypeColor(payment.display_type || payment.type)}`}>
                          {(payment.display_type || payment.type) === 'income' ? '+' : '-'}{formatAmount(Math.abs(payment.amount))}
                        </span>
                        {payment.fee && payment.fee > 0 && (
                          <div className="text-xs text-gray-500">Fee: {formatAmount(payment.fee)}</div>
                        )}
                        {payment.net_amount && (
                          <div className="text-xs text-green-400">Netto: {formatAmount(payment.net_amount)}</div>
                        )}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center justify-center">
                          <span className={`glass-badge ${getStatusColor(payment.status)}`}>
                            <StatusIcon className="w-3 h-3 mr-1" />
                            {getStatusLabel(payment.status)}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex gap-2 justify-center">
                          {payment.source === 'stripe' && (payment.display_type || payment.type) === 'income' && !payment.is_reconciled ? (
                            <button
                              onClick={(e) => {
                                e.stopPropagation() // Previene il click sulla riga
                                handleRefundPayment(payment.id)
                              }}
                              className="p-2 hover:bg-red-500/20 text-red-400 rounded transition-colors"
                              title="Rimborsa"
                            >
                              <XCircle className="w-4 h-4" />
                            </button>
                          ) : (
                            <span className="text-gray-400 text-sm">-</span>
                          )}
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Controlli paginazione */}
        {!loading && totalPages > 1 && (
          <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div className="text-sm text-gray-500">
              Pagina {currentPage} di {totalPages}
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
                className="glass-button px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Precedente
              </button>
              <button
                onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                disabled={currentPage === totalPages}
                className="glass-button px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Successiva
              </button>
            </div>
          </div>
        )}
      </motion.div>
      )}

      {/* Fatturazione Ordinaria Tab */}
      {activeTab === 'ordinary-invoicing' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card p-6"
        >
          {/* Header */}
          <div className="border-b border-gray-200 pb-6 mb-6">
            <div className="flex justify-between items-start">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  Fatturazione Ordinaria Stripe
                </h2>
                <p className="text-gray-500 text-sm">
                  Genera fatture ordinarie per tutte le transazioni Stripe del mese selezionato
                </p>
              </div>
            </div>

            {/* Selezione Periodo */}
            <div className="mt-4 flex items-center gap-4">
              <div>
                <label className="block text-sm text-gray-500 mb-2">Periodo (Mese/Anno)</label>
                <input
                  type="month"
                  value={ordinaryPeriod}
                  onChange={(e) => setOrdinaryPeriod(e.target.value)}
                  className="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                />
              </div>
              <button
                onClick={handlePregenerateOrdinaryInvoices}
                disabled={loadingOrdinaryPreviews}
                className="mt-6 px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                {loadingOrdinaryPreviews ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    Caricamento...
                  </>
                ) : (
                  <>
                    <RefreshCw className="w-4 h-4" />
                    Aggiorna Preview
                  </>
                )}
              </button>
            </div>
          </div>

          {/* Contenuto */}
          {loadingOrdinaryPreviews ? (
            <div className="p-12 flex flex-col items-center justify-center">
              <div className="w-16 h-16 border-4 border-primary-600 border-t-transparent rounded-full animate-spin mb-4" />
              <p className="text-gray-500">Caricamento preview fatture ordinarie...</p>
            </div>
          ) : (
            <>
              {/* Info Periodo */}
              <div className="mb-6 p-4 bg-primary-600/10 border border-primary-600/30 rounded-lg">
                <div className="flex items-start gap-3">
                  <Info className="w-5 h-5 text-primary-400 mt-0.5" />
                  <div>
                    <p className="text-primary-400 font-medium">Periodo Fatturazione</p>
                    <p className="text-sm text-gray-600 mt-1">
                      Tutte le fatture Stripe di {new Date(ordinaryPeriod + '-01').toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })}
                    </p>
                    <p className="text-xs text-gray-500 mt-2">
                      Le fatture saranno datate 1° {new Date(ordinaryPeriod + '-01').toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })}
                    </p>
                  </div>
                </div>
              </div>

              {/* Azioni Bulk */}
              {ordinaryInvoicePreviews.length > 0 && (
                <>
                  {/* Alert se tutte le fatture sono già generate */}
                  {ordinaryInvoicePreviews.filter(p => !p.invoice_id).length === 0 && (
                    <div className="mb-4 p-4 bg-primary-600/10 border border-primary-600/30 rounded-lg">
                      <div className="flex items-start gap-3">
                        <Info className="w-5 h-5 text-primary-400 mt-0.5" />
                        <div>
                          <p className="text-primary-400 font-medium">Tutte le fatture sono già state generate</p>
                          <p className="text-sm text-gray-600 mt-1">
                            Per questo periodo sono già presenti {ordinaryInvoicePreviews.length} fattura/e. 
                            Seleziona un periodo diverso per generare nuove fatture.
                          </p>
                        </div>
                      </div>
                    </div>
                  )}
                  
                  <div className="mb-6">
                    <button
                      onClick={handleGenerateOrdinaryInvoices}
                      disabled={generatingOrdinaryInvoices || ordinaryInvoicePreviews.filter(p => !p.invoice_id).length === 0}
                      className="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
                    >
                      {generatingOrdinaryInvoices ? (
                        <>
                          <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                          Generazione in corso...
                        </>
                      ) : (
                        <>
                          <FileText className="w-5 h-5" />
                          Genera Tutte le Fatture ({ordinaryInvoicePreviews.filter(p => !p.invoice_id).length})
                        </>
                      )}
                    </button>
                  </div>
                </>
              )}

              {/* Lista Preview Fatture */}
              {ordinaryInvoicePreviews.length === 0 ? (
                <div className="text-center py-12">
                  <FileText className="w-16 h-16 text-gray-500 mx-auto mb-4" />
                  <p className="text-gray-500">Nessuna fattura ordinaria trovata per questo periodo</p>
                  <p className="text-sm text-gray-400 mt-2">
                    Verifica che ci siano transazioni Stripe nel periodo selezionato
                  </p>
                </div>
              ) : (
                <div className="space-y-4">
                  {ordinaryInvoicePreviews.map((preview, index) => (
                    <div
                      key={index}
                      className="bg-gray-100/50 rounded-lg p-4 border border-gray-300 hover:border-gray-300 transition-colors"
                    >
                      <div className="flex justify-between items-start">
                        <div className="flex-1">
                          <div className="flex items-center gap-3 mb-2">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                              {preview.partner_name || 'Partner Sconosciuto'}
                            </h3>
                            {preview.invoice_id && (
                              <span className="px-2 py-1 bg-green-600/20 text-green-400 text-xs rounded-full">
                                Fattura #{preview.invoice_number}
                              </span>
                            )}
                            {!preview.client_id && !preview.invoice_id && (
                              <span className="px-2 py-1 bg-orange-600/20 text-orange-400 text-xs rounded-full flex items-center gap-1">
                                <AlertTriangle className="w-3 h-3" />
                                Cliente mancante
                              </span>
                            )}
                          </div>
                          
                          <p className="text-sm text-gray-500 mb-3">
                            {preview.partner_email}
                          </p>

                          <div className="grid grid-cols-3 gap-4 mb-3">
                            <div>
                              <p className="text-xs text-gray-400">Transazioni</p>
                              <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {preview.transaction_count || 0}
                              </p>
                            </div>
                            <div>
                              <p className="text-xs text-gray-400">Importo Totale</p>
                              <p className="text-sm font-medium text-green-400">
                                {formatAmount(preview.total_amount)}
                              </p>
                            </div>
                            <div>
                              <p className="text-xs text-gray-400">Cliente Collegato</p>
                              <p className={`text-sm font-medium ${preview.client_name ? 'text-gray-900 dark:text-white' : 'text-orange-400'}`}>
                                {preview.client_name || '⚠️ Non collegato'}
                              </p>
                            </div>
                          </div>

                          {preview.sample_transactions && preview.sample_transactions.length > 0 && (
                            <div className="mt-3 p-3 bg-gray-50/50 rounded border border-gray-300">
                              <p className="text-xs text-gray-500 mb-2">Prime transazioni:</p>
                              <div className="space-y-1">
                                {preview.sample_transactions.slice(0, 3).map((tx: any, txIndex: number) => (
                                  <div key={txIndex} className="text-xs text-gray-600 flex justify-between">
                                    <span>{tx.description || tx.type}</span>
                                    <span className="font-medium">{formatAmount(tx.amount)}</span>
                                  </div>
                                ))}
                                {preview.transaction_count > 3 && (
                                  <p className="text-xs text-gray-400 italic">
                                    ... e altre {preview.transaction_count - 3} transazioni
                                  </p>
                                )}
                              </div>
                            </div>
                          )}
                        </div>

                        <div className="flex flex-col gap-2 ml-4">
                          {!preview.client_id && !preview.invoice_id ? (
                            <button
                              onClick={() => window.open(`/clients?search=${encodeURIComponent(preview.partner_email)}`, '_blank')}
                              className="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors text-sm flex items-center gap-2"
                            >
                              <Users className="w-4 h-4" />
                              Collega Cliente
                            </button>
                          ) : !preview.invoice_id ? (
                            <button
                              onClick={() => handleGenerateSingleOrdinaryInvoice(preview)}
                              disabled={generatingOrdinaryInvoices}
                              className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center gap-2"
                            >
                              <FileText className="w-4 h-4" />
                              Genera
                            </button>
                          ) : (
                            <button
                              onClick={() => window.open(`/invoices/${preview.invoice_id}`, '_blank')}
                              className="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm flex items-center gap-2"
                            >
                              <Eye className="w-4 h-4" />
                              Vedi Fattura
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </>
          )}
        </motion.div>
      )}

      {/* Report Stripe Tab */}
      {activeTab === 'stripe-report' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card p-6"
        >
          <div className="flex justify-between items-center mb-6">
            <div>
              <h2 className="text-xl font-bold">Report Stripe</h2>
              <p className="text-gray-500 text-sm mt-1">
                Analisi transazioni mensili e normalizzazione automatica
              </p>
            </div>

            <div className="flex gap-2">
              <button
                onClick={handleStripeReset}
                disabled={stripeResetting || stripeNormalizing || stripeLoading}
                className="px-4 py-2 rounded-lg flex items-center gap-2 text-white bg-orange-600 hover:bg-orange-700 disabled:bg-gray-100 transition-colors"
                title="Reset e risincronizza da zero"
              >
                <RefreshCw className={`w-4 h-4 ${stripeResetting ? 'animate-spin' : ''}`} />
                {stripeResetting ? 'Resettando...' : 'Reset'}
              </button>

              <button
                onClick={handleStripeNormalize}
                disabled={stripeNormalizing || stripeLoading || stripeResetting}
                className={`px-4 py-2 rounded-lg flex items-center gap-2 text-white ${
                  stripeNeedsNormalization
                    ? 'bg-red-600 hover:bg-red-700 disabled:bg-gray-100'
                    : 'bg-green-600 hover:bg-green-700 disabled:bg-gray-100'
                }`}
              >
                <RefreshCw className={`w-4 h-4 ${stripeNormalizing ? 'animate-spin' : ''}`} />
                {stripeNormalizing ? 'Normalizzazione...' : 'Normalizza'}
              </button>
            </div>

            <div className="flex gap-2">
              {syncStats && (
                <button
                  onClick={() => setShowSyncLog(true)}
                  className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg flex items-center gap-2 transition-colors"
                  title="Visualizza log ultima sincronizzazione"
                >
                  <FileText className="w-4 h-4" />
                  Log Sync
                </button>
              )}
              
              {normalizeStats && (
                <button
                  onClick={() => setShowNormalizeLog(true)}
                  className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg flex items-center gap-2 transition-colors"
                  title="Visualizza log ultima normalizzazione"
                >
                  <FileText className="w-4 h-4" />
                  Log Normalizzazione
                </button>
              )}
            </div>
          </div>

          {/* Log Normalizzazione (collassabile) */}
          {showNormalizeLog && normalizeStats && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
              className="glass-card overflow-hidden mb-6"
            >
              <div
                className="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50/30 transition-colors"
                onClick={() => setShowNormalizeLog(!showNormalizeLog)}
              >
                <div className="flex items-center gap-3">
                  <RefreshCw className="w-5 h-5 text-purple-400" />
                  <div>
                    <h3 className="font-semibold">Log Normalizzazione Stripe</h3>
                    <p className="text-sm text-gray-500">
                      {new Date(normalizeStats.timestamp).toLocaleString('it-IT')}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div className="flex gap-3 text-sm">
                    <span className="text-purple-400">{normalizeStats.corrections_count} correzioni</span>
                  </div>
                  <X className="w-5 h-5 text-gray-500 hover:text-gray-900 dark:hover:text-white" onClick={(e) => {
                    e.stopPropagation()
                    setShowNormalizeLog(false)
                  }} />
                </div>
              </div>

              <div className="p-4 border-t border-gray-200 bg-gray-900/50">
                {/* Correzioni effettuate */}
                {normalizeStats.corrections && normalizeStats.corrections.length > 0 ? (
                  <div>
                    <h4 className="text-sm font-semibold text-gray-600 mb-3">Transazioni Corrette:</h4>
                    <div className="space-y-2 max-h-96 overflow-y-auto">
                      {normalizeStats.corrections.map((correction: any, index: number) => (
                        <div key={index} className="bg-gray-50 border border-gray-200 rounded-lg p-3">
                          <div className="flex items-start justify-between gap-4">
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center gap-2 mb-1 flex-wrap">
                                <span className="text-xs font-mono text-gray-500 truncate">
                                  {correction.transaction_id}
                                </span>
                                <div className="flex items-center gap-1">
                                  <span className="text-xs px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 border border-red-500/30 line-through">
                                    {correction.old_type}
                                  </span>
                                  <span className="text-xs text-gray-400">→</span>
                                  <span className="text-xs px-2 py-0.5 rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                                    {correction.new_type}
                                  </span>
                                </div>
                              </div>
                              <p className="text-xs text-gray-500 mb-1 truncate">
                                {correction.description}
                              </p>
                              <p className="text-xs text-purple-400 italic">
                                {correction.reason}
                              </p>
                              <p className="text-xs text-gray-400 mt-1">
                                {new Date(correction.date).toLocaleDateString('it-IT', { 
                                  day: '2-digit', 
                                  month: 'short', 
                                  year: 'numeric' 
                                })}
                              </p>
                            </div>
                            <div className="text-right flex-shrink-0">
                              <div className="text-sm font-semibold text-gray-900 dark:text-white">
                                {formatAmount(parseFloat(correction.amount))}
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ) : (
                  <div className="text-center py-6 text-gray-500">
                    <CheckCircle className="w-12 h-12 mx-auto mb-2 text-green-400" />
                    <p>Nessuna correzione necessaria</p>
                    <p className="text-sm mt-1">Tutte le transazioni sono già normalizzate</p>
                  </div>
                )}
              </div>
            </motion.div>
          )}

          {/* Month/Year Selector */}
          <div className="mb-6 p-4 bg-gray-50/50 rounded-lg border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">Mese</label>
                <select
                  value={stripeMonth}
                  onChange={(e) => setStripeMonth(parseInt(e.target.value))}
                  className="glass-input w-full"
                >
                  {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                    <option key={m} value={m}>
                      {new Date(2000, m - 1).toLocaleString('it-IT', { month: 'long' })}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">Anno</label>
                <select
                  value={stripeYear}
                  onChange={(e) => setStripeYear(parseInt(e.target.value))}
                  className="glass-input w-full"
                >
                  {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i).map((y) => (
                    <option key={y} value={y}>{y}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          {/* Totals Summary */}
          {stripeTotals && (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-sm text-gray-500">Commissioni Riscosse</p>
                <p className="text-2xl font-bold text-green-400 mt-1">
                  {formatAmount(stripeTotals.commissioni_riscosse)}
                </p>
              </div>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-sm text-gray-500">Total Charge</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {formatAmount(stripeTotals.total_charge)}
                </p>
              </div>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-sm text-gray-500">Total Transfer</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {formatAmount(stripeTotals.total_transfer)}
                </p>
              </div>
              <div className={`border rounded-lg p-4 ${Math.abs(stripeTotals.differenza) > 0.01 ? 'bg-red-900/20 border-red-500' : 'bg-green-900/20 border-green-500'}`}>
                <p className="text-sm text-gray-500">Differenza</p>
                <p className={`text-2xl font-bold mt-1 ${Math.abs(stripeTotals.differenza) > 0.01 ? 'text-red-400' : 'text-green-400'}`}>
                  {formatAmount(stripeTotals.differenza)}
                </p>
              </div>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-sm text-gray-500">Pagamenti Sottoscrizione</p>
                <p className="text-xl font-bold text-gray-900 dark:text-white mt-1">
                  {formatAmount(stripeTotals.total_payment)}
                </p>
              </div>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-sm text-gray-500">Commissioni Pagate</p>
                <p className="text-xl font-bold text-red-400 mt-1">
                  {formatAmount(stripeTotals.commissioni_pagate)}
                </p>
              </div>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-sm text-gray-500">Total Coupon</p>
                <p className="text-xl font-bold text-gray-900 dark:text-white mt-1">
                  {formatAmount(stripeTotals.total_coupon)}
                </p>
              </div>
            </div>
          )}

          {/* Normalization Status Indicator */}
          {stripeNeedsNormalization && (
            <div className="bg-red-900/20 border border-red-500/50 rounded-lg p-4 mb-6">
              <div className="flex items-start gap-3">
                <AlertTriangle className="w-6 h-6 text-red-400 flex-shrink-0 mt-0.5" />
                <div className="flex-1">
                  <h3 className="font-semibold text-red-400">Charge e Transfer non bilanciati</h3>
                  <p className="text-sm text-gray-600 mt-1">Clicca "Normalizza" per correggere automaticamente le discrepanze.</p>
                </div>
              </div>
            </div>
          )}

          {/* Transactions Table */}
          <div className="bg-gray-50 border border-gray-200 rounded-lg overflow-hidden mb-6">
            <div className="max-h-[500px] overflow-y-auto">
              <table className="w-full">
                <thead className="sticky top-0 bg-gray-900 border-b border-gray-200">
                  <tr>
                    <th 
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                      onClick={() => handleStripeSort('id')}
                    >
                      <div className="flex items-center gap-1">
                        ID
                        {stripeSortColumn === 'id' && (
                          stripeSortDirection === 'asc' ? <ArrowUp className="w-3 h-3" /> : <ArrowDown className="w-3 h-3" />
                        )}
                      </div>
                    </th>
                    <th 
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                      onClick={() => handleStripeSort('type')}
                    >
                      <div className="flex items-center gap-1">
                        Tipo
                        {stripeSortColumn === 'type' && (
                          stripeSortDirection === 'asc' ? <ArrowUp className="w-3 h-3" /> : <ArrowDown className="w-3 h-3" />
                        )}
                      </div>
                    </th>
                    <th 
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                      onClick={() => handleStripeSort('source')}
                    >
                      <div className="flex items-center gap-1">
                        Source
                        {stripeSortColumn === 'source' && (
                          stripeSortDirection === 'asc' ? <ArrowUp className="w-3 h-3" /> : <ArrowDown className="w-3 h-3" />
                        )}
                      </div>
                    </th>
                    <th 
                      className="px-4 py-3 text-right text-xs font-semibold text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                      onClick={() => handleStripeSort('amount')}
                    >
                      <div className="flex items-center justify-end gap-1">
                        Importo
                        {stripeSortColumn === 'amount' && (
                          stripeSortDirection === 'asc' ? <ArrowUp className="w-3 h-3" /> : <ArrowDown className="w-3 h-3" />
                        )}
                      </div>
                    </th>
                    <th 
                      className="px-4 py-3 text-right text-xs font-semibold text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                      onClick={() => handleStripeSort('fee')}
                    >
                      <div className="flex items-center justify-end gap-1">
                        Fee
                        {stripeSortColumn === 'fee' && (
                          stripeSortDirection === 'asc' ? <ArrowUp className="w-3 h-3" /> : <ArrowDown className="w-3 h-3" />
                        )}
                      </div>
                    </th>
                    <th 
                      className="px-4 py-3 text-right text-xs font-semibold text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                      onClick={() => handleStripeSort('net')}
                    >
                      <div className="flex items-center justify-end gap-1">
                        Net
                        {stripeSortColumn === 'net' && (
                          stripeSortDirection === 'asc' ? <ArrowUp className="w-3 h-3" /> : <ArrowDown className="w-3 h-3" />
                        )}
                      </div>
                    </th>
                    <th 
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                      onClick={() => handleStripeSort('date')}
                    >
                      <div className="flex items-center gap-1">
                        Data
                        {stripeSortColumn === 'date' && (
                          stripeSortDirection === 'asc' ? <ArrowUp className="w-3 h-3" /> : <ArrowDown className="w-3 h-3" />
                        )}
                      </div>
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500">Note</th>
                  </tr>
                </thead>
                <tbody>
                  {stripeLoading ? (
                    <tr>
                      <td colSpan={8} className="px-4 py-12 text-center">
                        <Loader2 className="w-8 h-8 text-gray-500 mx-auto animate-spin" />
                        <p className="text-gray-500 mt-2">Caricamento...</p>
                      </td>
                    </tr>
                  ) : sortedStripeTransactions.length === 0 ? (
                    <tr>
                      <td colSpan={8} className="px-4 py-12 text-center text-gray-500">
                        Nessuna transazione trovata per questo periodo
                      </td>
                    </tr>
                  ) : (
                    sortedStripeTransactions.map((t: any) => (
                      <tr key={t.transaction_id} className="border-b border-gray-200 hover:bg-gray-100/30">
                        <td className="px-4 py-3 font-mono text-xs text-gray-600">
                          {t.transaction_id?.substring(0, 12)}...
                        </td>
                        <td className="px-4 py-3">
                          <span className="text-sm text-primary-400">{t.type}</span>
                        </td>
                        <td className="px-4 py-3 font-mono text-xs text-gray-500">
                          {t.source?.substring(0, 12) || '-'}
                        </td>
                        <td className="px-4 py-3 text-right text-gray-900 dark:text-white font-medium">
                          {formatAmount(parseFloat(t.amount))}
                        </td>
                        <td className="px-4 py-3 text-right text-gray-500">
                          {formatAmount(parseFloat(t.fee))}
                        </td>
                        <td className="px-4 py-3 text-right text-gray-900 dark:text-white">
                          {formatAmount(parseFloat(t.net))}
                        </td>
                        <td className="px-4 py-3 text-xs text-gray-500">
                          {new Date(t.created_at).toLocaleString('it-IT', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                          })}
                        </td>
                        <td className="px-4 py-3">
                          {t.auto_corrected && (
                            <span className="text-xs text-primary-400 flex items-center gap-1">
                              <CheckCircle className="w-3 h-3" />
                              Auto
                            </span>
                          )}
                          {t.manually_corrected && (
                            <span className="text-xs text-orange-400 flex items-center gap-1">
                              <CheckCircle className="w-3 h-3" />
                              Manuale
                            </span>
                          )}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {/* Actions */}
          <div className="space-y-4">
            <div className="flex gap-2">
              <button 
                onClick={handleStripeExport}
                disabled={stripeExporting || stripeLoading}
                className="bg-green-600 hover:bg-green-700 disabled:bg-gray-100 text-white px-6 py-3 rounded-lg flex items-center gap-2"
              >
                <Download className="w-4 h-4" />
                {stripeExporting ? 'Esportazione...' : 'Esporta Excel'}
              </button>
            </div>
          </div>
        </motion.div>
      )}

      {/* Payment Links Tab */}
      {activeTab === 'payment-links' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          {/* Form Creazione Link */}
          <div className="glass-card p-6">
            <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Link2 className="w-5 h-5 text-primary-400" />
              Genera Link di Pagamento
            </h2>
            <p className="text-gray-500 text-sm mb-6">
              Crea un link Stripe Checkout da inviare al cliente. Il link scade dopo 24 ore.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Importo (EUR) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0.50"
                  value={checkoutAmount}
                  onChange={(e) => setCheckoutAmount(e.target.value)}
                  placeholder="0.00"
                  className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Descrizione *
                </label>
                <input
                  type="text"
                  value={checkoutDescription}
                  onChange={(e) => setCheckoutDescription(e.target.value)}
                  placeholder="Es: Servizio consulenza marzo 2026"
                  className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-600 mb-2">
                  Email cliente (opzionale)
                </label>
                <input
                  type="email"
                  value={checkoutEmail}
                  onChange={(e) => setCheckoutEmail(e.target.value)}
                  placeholder="cliente@email.com"
                  className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none"
                />
              </div>
            </div>

            <button
              onClick={handleCreateCheckoutSession}
              disabled={creatingCheckoutSession || !checkoutAmount || !checkoutDescription}
              className="px-6 py-3 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-100 disabled:cursor-not-allowed text-white rounded-lg font-medium transition-colors flex items-center gap-2"
            >
              {creatingCheckoutSession ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  Creazione in corso...
                </>
              ) : (
                <>
                  <Link2 className="w-5 h-5" />
                  Genera Link
                </>
              )}
            </button>
          </div>

          {/* Filtro Status */}
          <div className="flex items-center gap-3">
            <span className="text-sm text-gray-500">Filtra per stato:</span>
            {['all', 'open', 'complete', 'expired'].map((status) => (
              <button
                key={status}
                onClick={() => setCheckoutStatusFilter(status)}
                className={`px-3 py-1 rounded-full text-sm transition-colors ${
                  checkoutStatusFilter === status
                    ? 'bg-primary-600 text-white'
                    : 'bg-gray-50 text-gray-500 hover:text-gray-900'
                }`}
              >
                {status === 'all' ? 'Tutti' : status === 'open' ? 'Attivi' : status === 'complete' ? 'Pagati' : 'Scaduti'}
              </button>
            ))}
          </div>

          {/* Lista Link Generati */}
          <div className="glass-card overflow-hidden">
            {loadingCheckoutSessions ? (
              <div className="p-12 text-center">
                <Loader2 className="w-10 h-10 text-primary-400 mx-auto animate-spin mb-4" />
                <p className="text-gray-500">Caricamento link di pagamento...</p>
              </div>
            ) : checkoutSessions.length === 0 ? (
              <div className="p-12 text-center">
                <Link2 className="w-12 h-12 text-gray-500 mx-auto mb-4" />
                <p className="text-gray-500">Nessun link di pagamento generato</p>
                <p className="text-sm text-gray-400 mt-1">Crea il tuo primo link compilando il form sopra</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="text-left text-sm text-gray-500 border-b border-gray-200">
                      <th className="py-3 px-4">Descrizione</th>
                      <th className="py-3 px-4">Importo</th>
                      <th className="py-3 px-4">Stato</th>
                      <th className="py-3 px-4">Email</th>
                      <th className="py-3 px-4">Creato</th>
                      <th className="py-3 px-4">Scadenza</th>
                      <th className="py-3 px-4">Azioni</th>
                    </tr>
                  </thead>
                  <tbody>
                    {checkoutSessions.map((session: any) => (
                      <tr key={session.id} className="border-b border-gray-200 hover:bg-gray-50/30 transition-colors">
                        <td className="py-3 px-4">
                          <div className="text-gray-900 dark:text-white font-medium">{session.description}</div>
                          <div className="text-xs text-gray-400 font-mono mt-1">{session.stripe_session_id?.substring(0, 20)}...</div>
                        </td>
                        <td className="py-3 px-4 text-gray-900 dark:text-white font-bold">
                          {formatAmount(parseFloat(session.amount))}
                        </td>
                        <td className="py-3 px-4">
                          <span className={`px-2 py-1 text-xs rounded-full font-medium ${
                            session.status === 'complete' ? 'bg-green-500/20 text-green-400' :
                            session.status === 'open' ? 'bg-primary-500/20 text-primary-400' :
                            'bg-red-500/20 text-red-400'
                          }`}>
                            {session.status === 'complete' ? 'Pagato' : session.status === 'open' ? 'Attivo' : 'Scaduto'}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-gray-600 text-sm">
                          {session.customer_email || '-'}
                        </td>
                        <td className="py-3 px-4 text-gray-500 text-sm">
                          {new Date(session.created_at).toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                        </td>
                        <td className="py-3 px-4 text-gray-500 text-sm">
                          {new Date(session.expires_at).toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                        </td>
                        <td className="py-3 px-4">
                          <div className="flex items-center gap-2">
                            {session.status === 'open' && (
                              <button
                                onClick={() => handleCopyLink(session.id, session.payment_url)}
                                className={`px-3 py-1.5 rounded-lg text-sm transition-colors flex items-center gap-1.5 ${
                                  copiedLinkId === session.id
                                    ? 'bg-green-600 text-white'
                                    : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200'
                                }`}
                                title="Copia link"
                              >
                                {copiedLinkId === session.id ? (
                                  <>
                                    <CheckCircle className="w-3.5 h-3.5" />
                                    Copiato!
                                  </>
                                ) : (
                                  <>
                                    <Copy className="w-3.5 h-3.5" />
                                    Copia
                                  </>
                                )}
                              </button>
                            )}
                            {session.status === 'open' && (
                              <button
                                onClick={() => handleRefreshCheckoutSession(session.stripe_session_id)}
                                className="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 hover:text-gray-900 dark:hover:text-white transition-colors"
                                title="Aggiorna stato"
                              >
                                <RefreshCw className="w-3.5 h-3.5" />
                              </button>
                            )}
                            {session.status === 'complete' && (
                              <span className="text-green-400 text-sm flex items-center gap-1">
                                <CheckCircle className="w-3.5 h-3.5" />
                                {session.completed_at && new Date(session.completed_at).toLocaleDateString('it-IT')}
                              </span>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </motion.div>
      )}

      {/* Modal Preview Fattura */}
      {showInvoiceModal && invoicePreview && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-gray-900 rounded-xl border border-gray-200 max-w-4xl w-full max-h-[90vh] overflow-y-auto"
          >
            <div className="p-6 border-b border-gray-200">
              <div className="flex justify-between items-start">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Preview Fattura</h2>
                  <p className="text-gray-500">Verifica i dati prima di generare la fattura</p>
                </div>
                <button
                  onClick={() => setShowInvoiceModal(false)}
                  className="text-gray-500 hover:text-gray-900 dark:hover:text-white"
                >
                  <XCircle className="w-6 h-6" />
                </button>
              </div>
            </div>

            <div className="p-6 space-y-6">
              {/* Cliente */}
              <div className="glass-card p-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3">Cliente</h3>
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div>
                    <span className="text-gray-500">Ragione Sociale:</span>
                    <span className="text-gray-900 dark:text-white ml-2 font-medium">{invoicePreview.client.ragione_sociale}</span>
                  </div>
                  <div>
                    <span className="text-gray-500">P.IVA:</span>
                    <span className="text-gray-900 dark:text-white ml-2">{invoicePreview.client.piva || 'N/A'}</span>
                  </div>
                  <div>
                    <span className="text-gray-500">Email:</span>
                    <span className="text-gray-900 dark:text-white ml-2">{invoicePreview.client.email}</span>
                  </div>
                  <div>
                    <span className="text-gray-500">Città:</span>
                    <span className="text-gray-900 dark:text-white ml-2">{invoicePreview.client.citta || 'N/A'}</span>
                  </div>
                </div>
              </div>

              {/* Dati Fattura */}
              <div className="glass-card p-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3">Dati Fattura</h3>
                <div className="grid grid-cols-3 gap-3 text-sm">
                  <div>
                    <span className="text-gray-500">Data Emissione:</span>
                    <span className="text-gray-900 dark:text-white ml-2">{new Date(invoicePreview.suggested_data.data_emissione).toLocaleDateString('it-IT')}</span>
                  </div>
                  <div>
                    <span className="text-gray-500">Data Scadenza:</span>
                    <span className="text-gray-900 dark:text-white ml-2">{new Date(invoicePreview.suggested_data.data_scadenza).toLocaleDateString('it-IT')}</span>
                  </div>
                  <div>
                    <span className="text-gray-500">Metodo Pagamento:</span>
                    <span className="text-gray-900 dark:text-white ml-2 capitalize">{invoicePreview.suggested_data.payment_method}</span>
                  </div>
                </div>
              </div>

              {/* Righe Fattura */}
              <div className="glass-card p-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3">Righe Fattura</h3>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-gray-200">
                        <th className="text-left py-2 px-2 text-gray-500">Descrizione</th>
                        <th className="text-center py-2 px-2 text-gray-500">Qtà</th>
                        <th className="text-right py-2 px-2 text-gray-500">Prezzo Unit.</th>
                        <th className="text-right py-2 px-2 text-gray-500">Imponibile</th>
                        <th className="text-center py-2 px-2 text-gray-500">IVA %</th>
                        <th className="text-right py-2 px-2 text-gray-500">IVA</th>
                        <th className="text-right py-2 px-2 text-gray-500">Totale</th>
                      </tr>
                    </thead>
                    <tbody>
                      {invoicePreview.items.map((item: any, idx: number) => (
                        <tr key={idx} className="border-b border-gray-200">
                          <td className="py-3 px-2 text-gray-900 dark:text-white">{item.descrizione}</td>
                          <td className="py-3 px-2 text-center text-gray-900 dark:text-white">{item.quantita}</td>
                          <td className="py-3 px-2 text-right text-gray-900 dark:text-white">{formatAmount(item.prezzo_unitario)}</td>
                          <td className="py-3 px-2 text-right text-gray-900 dark:text-white">{formatAmount(item.imponibile)}</td>
                          <td className="py-3 px-2 text-center text-gray-900 dark:text-white">{item.iva_percentuale}%</td>
                          <td className="py-3 px-2 text-right text-gray-900 dark:text-white">{formatAmount(item.iva)}</td>
                          <td className="py-3 px-2 text-right font-bold text-gray-900 dark:text-white">{formatAmount(item.totale)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Totali */}
              <div className="glass-card p-4 bg-gray-50/50">
                <div className="flex justify-between items-center text-sm mb-2">
                  <span className="text-gray-500">Imponibile:</span>
                  <span className="text-gray-900 dark:text-white font-medium">{formatAmount(invoicePreview.totals.imponibile)}</span>
                </div>
                <div className="flex justify-between items-center text-sm mb-3">
                  <span className="text-gray-500">IVA:</span>
                  <span className="text-gray-900 dark:text-white font-medium">{formatAmount(invoicePreview.totals.iva)}</span>
                </div>
                <div className="flex justify-between items-center text-xl pt-3 border-t border-gray-200">
                  <span className="text-gray-900 dark:text-white font-bold">Totale:</span>
                  <span className="text-green-400 font-bold">{formatAmount(invoicePreview.totals.totale)}</span>
                </div>
              </div>

              {/* Pagamenti Inclusi */}
              <div className="glass-card p-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3">Pagamenti Inclusi ({invoicePreview.payments.length})</h3>
                <div className="max-h-40 overflow-y-auto space-y-2">
                  {invoicePreview.payments.map((payment: any) => (
                    <div key={payment.id} className="flex justify-between items-center text-sm bg-gray-50/30 p-2 rounded">
                      <div>
                        <span className="text-gray-900 dark:text-white">{payment.descrizione}</span>
                        <span className="text-gray-500 ml-2">({payment.transaction_date})</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-xs bg-primary-600/20 text-primary-400 px-2 py-1 rounded">{payment.source}</span>
                        <span className="text-green-400 font-medium">{formatAmount(payment.amount)}</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="p-6 border-t border-gray-200 bg-gray-50/30 flex justify-end gap-3">
              <button
                onClick={() => setShowInvoiceModal(false)}
                className="px-6 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              >
                Annulla
              </button>
              <button
                onClick={handleConfirmInvoice}
                disabled={generatingInvoice}
                className="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-100 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
              >
                {generatingInvoice ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    Generazione...
                  </>
                ) : (
                  <>
                    <CheckCircle className="w-5 h-5" />
                    Genera Fattura
                  </>
                )}
              </button>
            </div>
          </motion.div>
        </div>
      )}

      {/* Modal Dettagli Pagamento */}
      {showPaymentDetails && selectedPayment && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-gray-900 rounded-xl border border-gray-200 max-w-2xl w-full max-h-[90vh] overflow-y-auto"
          >
            <div className="p-6 border-b border-gray-200">
              <div className="flex justify-between items-start">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Dettagli Pagamento</h2>
                  <p className="text-gray-500">ID: {selectedPayment.id}</p>
                </div>
                <button
                  onClick={() => setShowPaymentDetails(false)}
                  className="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
                >
                  <XCircle className="w-6 h-6" />
                </button>
              </div>
            </div>

            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-1">Data Transazione</div>
                  <div className="text-gray-900 dark:text-white font-medium">
                    {new Date(selectedPayment.transaction_date).toLocaleString('it-IT')}
                  </div>
                </div>
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-1">Sorgente</div>
                  <div className="text-gray-900 dark:text-white font-medium">
                    <span className={`px-2 py-1 text-xs rounded-full font-medium ${
                      selectedPayment.source === 'stripe' ? 'bg-purple-500/20 text-purple-400' :
                      selectedPayment.source === 'bank' ? 'bg-primary-500/20 text-primary-400' :
                      selectedPayment.source === 'paypal' ? 'bg-primary-600/20 text-primary-300' :
                      selectedPayment.source === 'vivawallet' ? 'bg-orange-500/20 text-orange-400' :
                      selectedPayment.source === 'nexi' ? 'bg-green-500/20 text-green-400' :
                      'bg-slate-500/20 text-gray-500'
                    }`}>
                      {selectedPayment.source === 'bank' ? 'CRV (Banca)' : 
                       selectedPayment.source === 'vivawallet' ? 'VIVAWALLET' :
                       selectedPayment.source === 'nexi' ? 'NEXI' :
                       selectedPayment.source === 'paypal' ? 'PAYPAL' :
                       selectedPayment.source === 'stripe' ? 'STRIPE' :
                       selectedPayment.source?.toUpperCase() || 'N/A'}
                    </span>
                  </div>
                </div>
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-1">Tipo</div>
                  <div className={`font-medium ${getTypeColor(selectedPayment.display_type || selectedPayment.type)}`}>
                    {getTypeLabel(selectedPayment.display_type || selectedPayment.type)}
                  </div>
                </div>
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-1">Importo</div>
                  <div className={`text-lg font-bold ${getTypeColor(selectedPayment.display_type || selectedPayment.type)}`}>
                    {formatAmount(selectedPayment.amount)}
                  </div>
                </div>
                {selectedPayment.fee && selectedPayment.fee > 0 && (
                  <div className="glass-card p-4">
                    <div className="text-xs text-gray-500 mb-1">Commissione</div>
                    <div className="text-gray-900 dark:text-white font-medium">{formatAmount(selectedPayment.fee)}</div>
                  </div>
                )}
                {selectedPayment.net_amount && (
                  <div className="glass-card p-4">
                    <div className="text-xs text-gray-500 mb-1">Importo Netto</div>
                    <div className="text-green-400 font-bold">{formatAmount(selectedPayment.net_amount)}</div>
                  </div>
                )}
              </div>

              {selectedPayment.descrizione && (
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-2">Descrizione</div>
                  <div className="text-gray-900 dark:text-white">{selectedPayment.descrizione}</div>
                </div>
              )}

              {selectedPayment.beneficiario && (
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-2">Beneficiario</div>
                  <div className="text-gray-900 dark:text-white">{selectedPayment.beneficiario}</div>
                </div>
              )}

              {selectedPayment.causale && (
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-2">Causale</div>
                  <div className="text-gray-900 dark:text-white">{selectedPayment.causale}</div>
                </div>
              )}

              {selectedPayment.source_transaction_id && (
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-2">Transaction ID</div>
                  <div className="text-gray-900 dark:text-white font-mono text-sm">{selectedPayment.source_transaction_id}</div>
                </div>
              )}

              {selectedPayment.category && (
                <div className="glass-card p-4">
                  <div className="text-xs text-gray-500 mb-2">Categoria</div>
                  <div className="text-gray-900 dark:text-white">{selectedPayment.category}</div>
                </div>
              )}

              {selectedPayment.is_reconciled && (
                <div className="glass-card p-4 bg-green-500/10 border-green-500/30">
                  <div className="flex items-center gap-2 text-green-400">
                    <CheckCircle className="w-5 h-5" />
                    <span className="font-medium">Pagamento Riconciliato</span>
                  </div>
                  {selectedPayment.invoice_id && (
                    <div className="text-xs text-gray-500 mt-1">Fattura ID: {selectedPayment.invoice_id}</div>
                  )}
                </div>
              )}

              {selectedPayment.note && (
                <div className="glass-card p-4 bg-gray-50">
                  <div className="text-xs text-gray-500 mb-2">Note Tecniche</div>
                  <pre className="text-xs text-gray-600 overflow-x-auto">{selectedPayment.note}</pre>
                </div>
              )}
            </div>

            <div className="p-6 border-t border-gray-200 flex justify-end gap-3">
              {selectedPayment.source === 'stripe' && (selectedPayment.display_type || selectedPayment.type) === 'income' && !selectedPayment.is_reconciled && (
                <button
                  onClick={() => {
                    setShowPaymentDetails(false)
                    handleRefundPayment(selectedPayment.id)
                  }}
                  className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center gap-2"
                >
                  <XCircle className="w-4 h-4" />
                  Rimborsa
                </button>
              )}
              <button
                onClick={() => setShowPaymentDetails(false)}
                className="px-6 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
              >
                Chiudi
              </button>
            </div>
          </motion.div>
        </div>
      )}

      {/* Modal Fatturazione Differita Commissioni */}
      {showDeferredInvoicingModal && (
        <div 
          className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
          onClick={() => setShowDeferredInvoicingModal(false)}
        >
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            className="glass-card p-6 max-w-6xl w-full max-h-[90vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-6 pb-4 border-b border-gray-200">
              <div className="flex justify-between items-center mb-4">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <FileText className="w-6 h-6 text-purple-400" />
                    Fatturazione Differita Commissioni Stripe
                  </h2>
                  <p className="text-gray-500 text-sm mt-1">
                    Periodo: {commissionsPeriod} • Pre-generazione fatture da commissioni riscosse
                  </p>
                </div>
                <button
                  onClick={() => setShowDeferredInvoicingModal(false)}
                  className="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              {/* Pulsanti Azione Principali in Alto */}
              {!loadingDeferredPreviews && deferredInvoicePreviews.length > 0 && (
                <div className="flex gap-3">
                  <button
                    onClick={handlePregenerateDeferredInvoices}
                    disabled={loadingDeferredPreviews}
                    className="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors flex items-center gap-2 font-medium shadow-lg"
                  >
                    {loadingDeferredPreviews ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <RefreshCw className="w-4 h-4" />
                    )}
                    Ricarica Preview
                  </button>
                  <button
                    onClick={handleGenerateDeferredInvoices}
                    disabled={generatingDeferredInvoices || deferredInvoicePreviews.filter(p => p.invoice_ready).length === 0}
                    className="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-lg"
                  >
                    {generatingDeferredInvoices ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <FileText className="w-4 h-4" />
                    )}
                    Genera {deferredInvoicePreviews.filter(p => p.invoice_ready).length} Fatture
                  </button>
                </div>
              )}
            </div>

            {loadingDeferredPreviews ? (
              <div className="text-center py-12">
                <Loader2 className="w-16 h-16 text-purple-600 mx-auto mb-4 animate-spin" />
                <p className="text-gray-500">Caricamento preview fatture...</p>
              </div>
            ) : deferredInvoicePreviews.length === 0 ? (
              <div className="text-center py-12">
                <AlertTriangle className="w-16 h-16 text-yellow-600 mx-auto mb-4" />
                <p className="text-gray-500 text-lg font-semibold mb-2">Nessuna fattura da generare per questo periodo</p>
                <p className="text-sm text-gray-400 mt-2">Verifica che ci siano commissioni Stripe nel periodo selezionato</p>
                <button
                  onClick={() => {
                    setShowDeferredInvoicingModal(false)
                    fetchDetailedCommissions()
                  }}
                  className="mt-4 px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                >
                  Ricarica Commissioni
                </button>
              </div>
            ) : (
              <>
                {/* Alert per fatture già generate */}
                {deferredInvoicePreviews.filter(p => p.already_generated).length > 0 && (
                  <div className="mb-6 glass-card p-4 bg-primary-600/10 border border-primary-600/30">
                    <div className="flex items-center gap-3">
                      <CheckCircle className="w-5 h-5 text-primary-400" />
                      <div>
                        <p className="font-semibold text-primary-400">
                          {deferredInvoicePreviews.filter(p => p.already_generated).length} fatture già generate per questo periodo
                        </p>
                        <p className="text-sm text-gray-500 mt-1">
                          Questi partner sono mostrati nella lista ma con stato "Già Generata".
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Statistiche Totali */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                  <div className="glass-card p-4 bg-primary-600/10 border border-primary-600/30">
                    <p className="text-sm text-gray-500 mb-1">Totale Partner</p>
                    <p className="text-2xl font-bold text-primary-400">{deferredInvoicePreviews.length}</p>
                  </div>
                  <div className="glass-card p-4 bg-green-600/10 border border-green-600/30">
                    <p className="text-sm text-gray-500 mb-1">Fatture Pronte</p>
                    <p className="text-2xl font-bold text-green-400">
                      {deferredInvoicePreviews.filter(p => p.invoice_ready && !p.already_generated).length}
                    </p>
                  </div>
                  <div className="glass-card p-4 bg-gray-600/10 border border-gray-600/30">
                    <p className="text-sm text-gray-500 mb-1">Già Generate</p>
                    <p className="text-2xl font-bold text-gray-400">
                      {deferredInvoicePreviews.filter(p => p.already_generated).length}
                    </p>
                  </div>
                  <div className="glass-card p-4 bg-orange-600/10 border border-orange-600/30">
                    <p className="text-sm text-gray-500 mb-1">Totale Transazioni</p>
                    <p className="text-2xl font-bold text-orange-400">
                      {deferredInvoicePreviews.reduce((sum, p) => sum + (p.transaction_count || 0), 0)}
                    </p>
                  </div>
                </div>

                {/* Lista Raggruppamenti per Partner */}
                <div className="space-y-4 mb-6">
                  {deferredInvoicePreviews
                    .map((preview, idx) => (
                    <div 
                      key={idx} 
                      className={`glass-card p-5 border ${
                        preview.error ? 'border-red-500/30 bg-red-500/5' : 
                        preview.already_generated ? 'border-gray-500/30 bg-gray-500/5' :
                        preview.invoice_ready ? 'border-green-500/30 bg-green-500/5' : 
                        'border-yellow-500/30 bg-yellow-500/5'
                      }`}
                    >
                      <div className="flex items-start justify-between gap-4">
                        {/* Info Partner */}
                        <div className="flex-1">
                          <div className="flex items-center gap-3 mb-3">
                            <div className="w-10 h-10 rounded-full bg-purple-600/20 flex items-center justify-center">
                              <span className="text-lg font-bold text-purple-400">
                                {(preview.partner_name || preview.partner_email || '?')[0].toUpperCase()}
                              </span>
                            </div>
                            <div>
                              <h3 className="font-bold text-gray-900 dark:text-white text-lg">{preview.partner_name || 'Partner Sconosciuto'}</h3>
                              <p className="text-sm text-gray-500">{preview.partner_email}</p>
                            </div>
                          </div>

                          {/* Cliente Associato */}
                          {preview.already_generated && (
                            <div className="mb-3 p-3 bg-gray-600/10 rounded-lg border border-gray-600/30">
                              <p className="text-sm text-gray-400 flex items-center gap-2">
                                <CheckCircle className="w-4 h-4" />
                                Fattura già generata per questo periodo
                              </p>
                            </div>
                          )}

                          {preview.client_name ? (
                            <div className="mb-3 p-3 bg-gray-50/50 rounded-lg border border-gray-200">
                              <p className="text-xs text-gray-500 mb-1">Cliente Associato:</p>
                              <p className="font-semibold text-gray-900 dark:text-white">{preview.client_name}</p>
                              <p className="text-xs text-gray-400">ID Cliente: {preview.client_id}</p>
                            </div>
                          ) : (
                            <div className="mb-3 p-3 bg-yellow-600/10 rounded-lg border border-yellow-600/30">
                              <p className="text-sm text-yellow-400 flex items-center gap-2">
                                <AlertTriangle className="w-4 h-4" />
                                Partner non collegato a nessun cliente
                              </p>
                              <p className="text-xs text-gray-500 mt-1">Collega prima il partner per generare la fattura</p>
                            </div>
                          )}

                          {/* Dettagli Importi */}
                          <div className="grid grid-cols-3 gap-3 mb-3">
                            <div>
                              <p className="text-xs text-gray-500 mb-1">Commissioni Riscosse</p>
                              <p className="text-lg font-bold text-green-400">{formatAmount(preview.total_commissions || 0)}</p>
                              <p className="text-xs text-gray-400">{preview.transaction_count} transazioni</p>
                            </div>
                            {preview.total_coupons > 0 && (
                              <div>
                                <p className="text-xs text-gray-500 mb-1">Coupon Piattaforma</p>
                                <p className="text-lg font-bold text-red-400">-{formatAmount(preview.total_coupons)}</p>
                                <p className="text-xs text-gray-400">{preview.coupon_count} coupon</p>
                              </div>
                            )}
                            <div>
                              <p className="text-xs text-gray-500 mb-1">Importo Netto</p>
                              <p className="text-xl font-bold text-gray-900 dark:text-white">{formatAmount(preview.net_amount || 0)}</p>
                              <p className="text-xs text-gray-400">Da fatturare</p>
                            </div>
                          </div>

                          {/* Errore */}
                          {preview.error && (
                            <div className="p-3 bg-red-600/10 rounded-lg border border-red-600/30">
                              <p className="text-sm text-red-400">{preview.error}</p>
                            </div>
                          )}
                        </div>

                        {/* Pulsante Genera Fattura */}
                        <div className="flex flex-col gap-2">
                          {preview.already_generated ? (
                            <button
                              disabled
                              className="px-6 py-3 bg-gray-700 cursor-not-allowed text-gray-400 rounded-lg font-medium flex items-center gap-2"
                            >
                              <CheckCircle className="w-5 h-5" />
                              Già Generata
                            </button>
                          ) : preview.invoice_ready && !preview.error ? (
                            <button
                              onClick={() => handleGenerateSingleInvoice(preview)}
                              disabled={generatingDeferredInvoices}
                              className="px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white rounded-lg font-medium transition-colors flex items-center gap-2 shadow-lg"
                            >
                              <FileText className="w-5 h-5" />
                              Genera Fattura
                            </button>
                          ) : (
                            <button
                              disabled
                              className="px-6 py-3 bg-gray-100 cursor-not-allowed text-gray-500 rounded-lg font-medium flex items-center gap-2"
                            >
                              <AlertTriangle className="w-5 h-5" />
                              Non Disponibile
                            </button>
                          )}
                          
                          {/* Dettagli transazioni */}
                          <button
                            className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-sm transition-colors"
                            onClick={() => {
                              // Mostra dettagli transazioni in un alert o modal
                              alert(`Transazioni per ${preview.partner_name}:\n\n${preview.transactions?.slice(0, 5).map((t: any) => 
                                `${t.created}: ${formatAmount(t.amount)}`
                              ).join('\n')}\n\n${preview.transaction_count > 5 ? `... e altre ${preview.transaction_count - 5} transazioni` : ''}`)
                            }}
                          >
                            Vedi Dettagli
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Alert per fatture con errori (solo se ci sono partner senza cliente) */}
                {deferredInvoicePreviews.some((p: any) => !p.client_id && !p.already_generated) && (
                  <div className="mb-6 p-4 bg-yellow-600/10 border border-yellow-600/30 rounded-lg">
                    <div className="flex items-start gap-3">
                      <AlertTriangle className="w-5 h-5 text-yellow-400 mt-0.5" />
                      <div>
                        <p className="text-yellow-400 font-medium">Attenzione</p>
                        <p className="text-sm text-gray-600 mt-1">
                          {deferredInvoicePreviews.filter((p: any) => !p.client_id && !p.already_generated).length} partner non sono collegati a clienti nel sistema.
                          Associa manualmente i partner ai clienti prima di procedere.
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Pulsante Chiudi */}
                <div className="flex justify-end pt-4 border-t border-gray-200">
                  <button
                    onClick={() => setShowDeferredInvoicingModal(false)}
                    className="px-6 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                  >
                    Chiudi
                  </button>
                </div>
              </>
            )}
          </motion.div>
        </div>
      )}

      {/* Modal Fatturazione Ordinaria Stripe (20 - 1 mese successivo) */}
      {showOrdinaryInvoicingModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-gray-50 rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden"
          >
            {/* Header */}
            <div className="p-6 border-b border-gray-200">
              <div className="flex justify-between items-start">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Fatturazione Ordinaria Stripe
                  </h2>
                  <p className="text-gray-500 text-sm">
                    Genera fatture ordinarie per tutte le transazioni Stripe del mese selezionato
                  </p>
                </div>
                <button
                  onClick={() => setShowOrdinaryInvoicingModal(false)}
                  className="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              {/* Selezione Periodo */}
              <div className="mt-4 flex items-center gap-4">
                <div>
                  <label className="block text-sm text-gray-500 mb-2">Periodo (Mese/Anno)</label>
                  <input
                    type="month"
                    value={ordinaryPeriod}
                    onChange={(e) => setOrdinaryPeriod(e.target.value)}
                    className="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                  />
                </div>
                <button
                  onClick={handlePregenerateOrdinaryInvoices}
                  disabled={loadingOrdinaryPreviews}
                  className="mt-6 px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  {loadingOrdinaryPreviews ? (
                    <>
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      Caricamento...
                    </>
                  ) : (
                    <>
                      <RefreshCw className="w-4 h-4" />
                      Aggiorna Preview
                    </>
                  )}
                </button>
              </div>
            </div>

            {/* Contenuto */}
            {loadingOrdinaryPreviews ? (
              <div className="p-12 flex flex-col items-center justify-center">
                <div className="w-16 h-16 border-4 border-primary-600 border-t-transparent rounded-full animate-spin mb-4" />
                <p className="text-gray-500">Caricamento preview fatture ordinarie...</p>
              </div>
            ) : (
              <>
                <div className="p-6 overflow-y-auto max-h-[calc(90vh-300px)]">
                  {/* Info Periodo */}
                  <div className="mb-6 p-4 bg-primary-600/10 border border-primary-600/30 rounded-lg">
                    <div className="flex items-start gap-3">
                      <Info className="w-5 h-5 text-primary-400 mt-0.5" />
                      <div>
                        <p className="text-primary-400 font-medium">Periodo Fatturazione</p>
                        <p className="text-sm text-gray-600 mt-1">
                          Tutte le fatture Stripe di {new Date(ordinaryPeriod + '-01').toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })}
                        </p>
                        <p className="text-xs text-gray-500 mt-2">
                          Le fatture saranno datate 1° {new Date(ordinaryPeriod + '-01').toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Warning Dati Fiscali Mancanti */}
                  {ordinaryInvoicePreviews.some(p => p.validation_warning) && (
                    <div className="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                      <div className="flex items-start gap-3">
                        <AlertTriangle className="w-5 h-5 text-yellow-500 mt-0.5" />
                        <div>
                          <p className="text-yellow-400 font-medium">Attenzione: Dati Fiscali Mancanti</p>
                          <p className="text-sm text-gray-600 mt-1">
                            {ordinaryInvoicePreviews.filter(p => p.validation_warning).length} cliente/i non hanno P.IVA o Codice Fiscale.
                          </p>
                          <p className="text-xs text-gray-500 mt-2">
                            Le fatture verranno create ma risulteranno "Da completare" su Fatture in Cloud. 
                            Inserisci i dati fiscali dei clienti prima dell'invio a SDI.
                          </p>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Azioni Bulk */}
                  {ordinaryInvoicePreviews.length > 0 && (
                    <div className="mb-6 flex gap-3">
                      <button
                        onClick={handleGenerateOrdinaryInvoices}
                        disabled={generatingOrdinaryInvoices}
                        className="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
                      >
                        {generatingOrdinaryInvoices ? (
                          <>
                            <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                            Generazione in corso...
                          </>
                        ) : (
                          <>
                            <FileText className="w-5 h-5" />
                            Genera Tutte le Fatture ({ordinaryInvoicePreviews.filter(p => !p.invoice_id).length})
                          </>
                        )}
                      </button>

                      <button
                        onClick={handleSendOrdinaryInvoicesToFIC}
                        disabled={sendingOrdinaryToFIC || ordinaryInvoicePreviews.filter(p => p.invoice_id && !p.fic_sent).length === 0}
                        className="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
                      >
                        {sendingOrdinaryToFIC ? (
                          <>
                            <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                            Invio in corso...
                          </>
                        ) : (
                          <>
                            <Send className="w-5 h-5" />
                            Invia Tutte a FIC ({ordinaryInvoicePreviews.filter(p => p.invoice_id && !p.fic_sent).length})
                          </>
                        )}
                      </button>
                    </div>
                  )}

                  {/* Lista Preview Fatture */}
                  {ordinaryInvoicePreviews.length === 0 ? (
                    <div className="text-center py-12">
                      <FileText className="w-16 h-16 text-gray-500 mx-auto mb-4" />
                      <p className="text-gray-500">Nessuna fattura ordinaria trovata per questo periodo</p>
                      <p className="text-sm text-gray-400 mt-2">
                        Verifica che ci siano transazioni Stripe nel periodo selezionato
                      </p>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      {ordinaryInvoicePreviews.map((preview, index) => (
                        <div
                          key={index}
                          className="bg-gray-100/50 rounded-lg p-4 border border-gray-300 hover:border-gray-300 transition-colors"
                        >
                          <div className="flex justify-between items-start">
                            <div className="flex-1">
                              <div className="flex items-center gap-3 mb-2">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                  {preview.partner_name || 'Partner Sconosciuto'}
                                </h3>
                                {preview.invoice_id && (
                                  <span className="px-2 py-1 bg-green-600/20 text-green-400 text-xs rounded-full">
                                    Fattura #{preview.invoice_number}
                                  </span>
                                )}
                              </div>
                              
                              <p className="text-sm text-gray-500 mb-3">
                                {preview.partner_email}
                              </p>

                              {/* Warning Dati Fiscali */}
                              {preview.validation_warning && (
                                <div className="mb-3 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg flex items-start gap-2">
                                  <AlertTriangle className="w-4 h-4 text-yellow-500 flex-shrink-0 mt-0.5" />
                                  <p className="text-xs text-yellow-200">{preview.validation_warning}</p>
                                </div>
                              )}

                              <div className="grid grid-cols-3 gap-4 mb-3">
                                <div>
                                  <p className="text-xs text-gray-400">Transazioni</p>
                                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                                    {preview.transaction_count || 0}
                                  </p>
                                </div>
                                <div>
                                  <p className="text-xs text-gray-400">Importo Totale</p>
                                  <p className="text-sm font-medium text-green-400">
                                    {formatAmount(preview.total_amount)}
                                  </p>
                                </div>
                                <div>
                                  <p className="text-xs text-gray-400">Cliente Collegato</p>
                                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                                    {preview.client_name || 'Non collegato'}
                                  </p>
                                </div>
                              </div>

                              {preview.sample_transactions && preview.sample_transactions.length > 0 && (
                                <div className="mt-3 p-3 bg-gray-50/50 rounded border border-gray-300">
                                  <p className="text-xs text-gray-500 mb-2">Prime transazioni:</p>
                                  <div className="space-y-1">
                                    {preview.sample_transactions.slice(0, 3).map((tx: any, txIndex: number) => (
                                      <div key={txIndex} className="text-xs text-gray-600 flex justify-between">
                                        <span>{tx.description || tx.type}</span>
                                        <span className="font-medium">{formatAmount(tx.amount)}</span>
                                      </div>
                                    ))}
                                    {preview.transaction_count > 3 && (
                                      <p className="text-xs text-gray-400 italic">
                                        ... e altre {preview.transaction_count - 3} transazioni
                                      </p>
                                    )}
                                  </div>
                                </div>
                              )}
                            </div>

                            <div className="flex flex-col gap-2 ml-4">
                              {!preview.invoice_id ? (
                                <button
                                  onClick={() => handleGenerateSingleOrdinaryInvoice(preview)}
                                  disabled={generatingOrdinaryInvoices}
                                  className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center gap-2"
                                >
                                  <FileText className="w-4 h-4" />
                                  Genera
                                </button>
                              ) : (
                                <>
                                  {!preview.fic_sent && (
                                    <button
                                      onClick={() => handleSendSingleOrdinaryInvoiceToFIC(preview.invoice_id)}
                                      className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors text-sm flex items-center gap-2"
                                    >
                                      <Send className="w-4 h-4" />
                                      Invia a FIC
                                    </button>
                                  )}
                                  <button
                                    onClick={() => window.open(`/invoices/${preview.invoice_id}`, '_blank')}
                                    className="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm flex items-center gap-2"
                                  >
                                    <Eye className="w-4 h-4" />
                                    Vedi Fattura
                                  </button>
                                </>
                              )}
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Pulsante Chiudi */}
                <div className="flex justify-end p-6 border-t border-gray-200">
                  <button
                    onClick={() => setShowOrdinaryInvoicingModal(false)}
                    className="px-6 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                  >
                    Chiudi
                  </button>
                </div>
              </>
            )}
          </motion.div>
        </div>
      )}
    </div>
  )
}
