import { motion, AnimatePresence } from 'framer-motion'
import { useState, useEffect } from 'react'
import { Package, Search, Filter, Calendar, MapPin, Euro, TrendingUp, ArrowUpDown, ArrowUp, ArrowDown, Settings, Eye, EyeOff, RefreshCw, ChevronLeft, ChevronRight, Download, Building2, FileText, X, Ban, AlertTriangle, CheckCircle2, XCircle, Loader2 } from 'lucide-react'
import { ordersApi, deliveriesApi, restaurantsApi, cancellationApi } from '../utils/api'

interface Restaurant {
  id: number
  name: string
  address?: string
  city?: string
  user_id?: number
}

interface Order {
  id: number
  client_id: number
  restaurant_id: number
  order_number: string
  status: string
  delivery_type: string
  order_date: string
  subtotal: number
  delivery_fee: number
  discount: number
  total_amount: number
  customer_name: string
  shipping_address: string
  shipping_city: string
  items_count: number
  source_type?: 'order' | 'delivery' // Aggiunto per distinguere la fonte
  oppla_data?: {
    has_platform_discount?: boolean
    platform_fee_id?: string
    stripe_fee_id?: string
    payment_intent?: string
    [key: string]: any
  }
  client?: {
    id: number
    ragione_sociale: string
    citta: string
  }
  restaurant?: Restaurant
}

interface Delivery {
  id: number
  client_id: number
  order_id: string
  order_type: string
  is_partner_logistico: boolean
  pickup_address: string
  delivery_address: string
  distance_km: number
  order_amount: number
  delivery_fee_base: number
  delivery_fee_distance: number
  delivery_fee_total: number
  oppla_fee: number
  order_date: string
  pickup_time: string | null
  delivery_time: string | null
  status: string
  rider_id: number | null
  invoice_id: number | null
  is_invoiced: boolean
  note: string | null
  created_at: string
  updated_at: string
  client?: {
    id: number
    ragione_sociale: string
    citta: string
  }
}

interface DeliveryInvoicePreview {
  client_id: number | null
  client_name: string
  deliveries_count: number
  total_amount: number
  invoice_ready: boolean
  already_generated?: boolean
  existing_invoice_id?: number | null
  existing_invoice_number?: string | null
  error?: string | null
}

const statusColors: Record<string, string> = {
  // Stati Oppla
  New: 'bg-primary-500/20 text-primary-400 border-primary-500/30',
  Pending: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  Accepted: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
  Ready: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
  Delivering: 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30',
  Completed: 'bg-green-500/20 text-green-400 border-green-500/30',
  Rejected: 'bg-red-500/20 text-red-400 border-red-500/30',
  CancelledByCustomer: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
  // Stati legacy
  pending: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  confirmed: 'bg-primary-500/20 text-primary-400 border-primary-500/30',
  processing: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
  shipped: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
  delivered: 'bg-green-500/20 text-green-400 border-green-500/30',
  cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
  refunded: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
}

const statusLabels: Record<string, string> = {
  // Stati Oppla
  New: 'Nuovo',
  Pending: 'In Attesa',
  Accepted: 'Accettato',
  Ready: 'Pronto',
  Delivering: 'In Consegna',
  Completed: 'Completato',
  Rejected: 'Rifiutato',
  CancelledByCustomer: 'Annullato dal Cliente',
  // Stati legacy
  pending: 'In Attesa',
  confirmed: 'Confermato',
  processing: 'In Lavorazione',
  shipped: 'Spedito',
  delivered: 'Consegnato',
  cancelled: 'Annullato',
  refunded: 'Rimborsato',
}

type ColumnKey = 'id' | 'client_id' | 'restaurant_id' | 'restaurant_name' | 'order_number' | 'status' | 
  'order_date' | 'subtotal' | 'delivery_fee' | 'discount' | 'total_amount' | 'customer_name' | 
  'shipping_address' | 'shipping_city' | 'items_count' | 'has_platform_discount'

const availableColumns: Record<ColumnKey, string> = {
  id: 'ID',
  client_id: 'Cliente ID',
  restaurant_id: 'Ristorante',
  restaurant_name: 'Nome Ristorante',
  order_number: 'N. Ordine',
  status: 'Stato',
  order_date: 'Data',
  subtotal: 'Subtotale',
  delivery_fee: 'Costo Consegna',
  discount: 'Sconto',
  total_amount: 'Totale',
  customer_name: 'Nome Cliente',
  shipping_address: 'Indirizzo',
  shipping_city: 'Città',
  items_count: 'Articoli',
  has_platform_discount: 'Coupon'
}

const defaultVisibleColumns: ColumnKey[] = ['order_number', 'restaurant_id', 'status', 'order_date', 'total_amount', 'customer_name']

export default function Orders() {
  const [orders, setOrders] = useState<Order[]>([])
  const [restaurants, setRestaurants] = useState<Restaurant[]>([])
  const [loading, setLoading] = useState(true)
  const [loadingRestaurants, setLoadingRestaurants] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [periodFilter, setPeriodFilter] = useState<string>('all')
  const [restaurantFilter, setRestaurantFilter] = useState<string>('all')
  const [restaurantSearchTerm, setRestaurantSearchTerm] = useState('')
  const [showRestaurantDropdown, setShowRestaurantDropdown] = useState(false)
  const [startDate, setStartDate] = useState('')
  const [endDate, setEndDate] = useState('')
  const [dataSourceFilter, setDataSourceFilter] = useState<'all' | 'orders' | 'deliveries'>('all')
  const [sortField, setSortField] = useState<keyof Order>('order_date')
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc')
  const [showColumnSettings, setShowColumnSettings] = useState(false)
  const [syncing, setSyncing] = useState(false)
  const [exporting, setExporting] = useState(false)
  const [showDeliveryInvoicingModal, setShowDeliveryInvoicingModal] = useState(false)
  const [deliveryInvoicePreviews, setDeliveryInvoicePreviews] = useState<DeliveryInvoicePreview[]>([])
  const [deliveryInvoicePeriodLabel, setDeliveryInvoicePeriodLabel] = useState('')
  const [loadingDeliveryInvoicePreviews, setLoadingDeliveryInvoicePreviews] = useState(false)
  const [generatingDeliveryInvoices, setGeneratingDeliveryInvoices] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [itemsPerPage, setItemsPerPage] = useState(20)
  const [visibleColumns, setVisibleColumns] = useState<ColumnKey[]>(() => {
    const saved = localStorage.getItem('orders_visible_columns')
    return saved ? JSON.parse(saved) : defaultVisibleColumns
  })

  // Cancellation state
  const [showCancelModal, setShowCancelModal] = useState(false)
  const [cancelPreview, setCancelPreview] = useState<any>(null)
  const [cancelToken, setCancelToken] = useState<string>('')
  const [cancelWarnings, setCancelWarnings] = useState<string[]>([])
  const [cancelLoading, setCancelLoading] = useState(false)
  const [cancelExecuting, setCancelExecuting] = useState(false)
  const [cancelResult, setCancelResult] = useState<any>(null)
  const [cancelTargetOrder, setCancelTargetOrder] = useState<Order | null>(null)

  useEffect(() => {
    loadRestaurants()
  }, [])

  useEffect(() => {
    loadOrders()
  }, [periodFilter, statusFilter, restaurantFilter, startDate, endDate, dataSourceFilter])

  useEffect(() => {
    localStorage.setItem('orders_visible_columns', JSON.stringify(visibleColumns))
  }, [visibleColumns])

  const toggleColumn = (column: ColumnKey) => {
    setVisibleColumns(prev => 
      prev.includes(column) 
        ? prev.filter(c => c !== column)
        : [...prev, column]
    )
  }

  const isColumnVisible = (column: ColumnKey) => visibleColumns.includes(column)

  const loadRestaurants = async () => {
    setLoadingRestaurants(true)
    try {
      const response = await restaurantsApi.getAll()
      const restaurantsData = response.data.data || response.data || []
      setRestaurants(Array.isArray(restaurantsData) ? restaurantsData : [])
    } catch (error) {
      console.error('Errore caricamento ristoranti:', error)
    } finally {
      setLoadingRestaurants(false)
    }
  }

  const loadOrders = async () => {
    setLoading(true)
    try {
      const params: any = {
        all: true  // Carica tutti gli ordini senza paginazione backend
      }
      
      // Se ci sono date personalizzate, usa quelle invece del period
      if (startDate || endDate) {
        if (startDate) params.start_date = startDate
        if (endDate) params.end_date = endDate
      } else {
        params.period = periodFilter
      }
      
      if (statusFilter !== 'all') {
        params.status = statusFilter
      }

      if (restaurantFilter !== 'all') {
        params.restaurant_id = restaurantFilter
      }

      let combinedData: Order[] = []

      // Carica ordini se richiesto
      if (dataSourceFilter === 'all' || dataSourceFilter === 'orders') {
        const ordersResponse = await ordersApi.getAll(params)
        // Con all=true, il backend restituisce array diretto senza paginazione
        const ordersArray = ordersResponse.data.data || ordersResponse.data || []
        const ordersData = Array.isArray(ordersArray) ? ordersArray.map((order: any) => ({
          ...order,
          source_type: 'order' as const
        })) : []
        combinedData = [...combinedData, ...ordersData]
      }

      // Carica deliveries se richiesto e trasforma in formato Order
      if (dataSourceFilter === 'all' || dataSourceFilter === 'deliveries') {
        const deliveriesResponse = await deliveriesApi.getAll(params)
        // DeliveryController restituisce direttamente paginatedDeliveries
        // che ha { data: [...], current_page, total, etc }
        const deliveriesArray = deliveriesResponse.data.data || deliveriesResponse.data || []
        const deliveriesData = Array.isArray(deliveriesArray) ? deliveriesArray.map((delivery: Delivery) => ({
          id: delivery.id,
          client_id: delivery.client_id,
          restaurant_id: 0,
          order_number: delivery.order_id || 'N/A',
          status: delivery.status,
          delivery_type: 'managed_delivery',
          order_date: delivery.order_date || delivery.created_at,
          subtotal: delivery.delivery_fee_total || 0,
          delivery_fee: delivery.delivery_fee_total || 0,
          discount: 0,
          total_amount: delivery.order_amount || 0,
          customer_name: delivery.client?.ragione_sociale || 'N/A',
          shipping_address: delivery.delivery_address || '',
          shipping_city: '',
          items_count: 0,
          source_type: 'delivery' as const,
          client: delivery.client
        })) : []
        combinedData = [...combinedData, ...deliveriesData]
      }

      setOrders(combinedData)
    } catch (error) {
      console.error('Errore caricamento dati:', error)
      alert('Errore: impossibile caricare i dati')
    } finally {
      setLoading(false)
    }
  }

  const handleSync = async () => {
    setSyncing(true)
    try {
      const response = await ordersApi.sync()
      
      if (response.data.success) {
        const data = response.data.data
        alert(`Sincronizzazione completata!\nOrdini importati: ${data.orders_imported || 0}\nConsegne importate: ${data.deliveries_imported || 0}`)
        await loadOrders()
      } else {
        alert('Errore durante la sincronizzazione: ' + response.data.message)
      }
    } catch (error: any) {
      console.error('Errore sincronizzazione:', error)
      alert('Errore durante la sincronizzazione: ' + (error.response?.data?.message || error.message))
    } finally {
      setSyncing(false)
    }
  }

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const params: any = {
        all: true
      }
      
      // Applica gli stessi filtri dell'interfaccia
      if (startDate || endDate) {
        if (startDate) params.start_date = startDate
        if (endDate) params.end_date = endDate
      } else {
        params.period = periodFilter
      }
      
      if (statusFilter !== 'all') {
        params.status = statusFilter
      }

      if (restaurantFilter !== 'all') {
        params.restaurant_id = restaurantFilter
      }

      if (dataSourceFilter !== 'all') {
        params.data_source = dataSourceFilter
      }

      // Chiamata API per esportazione
      const response = await ordersApi.export(params)
      
      // Crea un URL blob e scarica il file
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      // Nome file con data e filtri applicati
      const filterSuffix = dataSourceFilter !== 'all' ? `_${dataSourceFilter}` : ''
      const dateSuffix = startDate && endDate ? `_${startDate}_${endDate}` : `_${periodFilter}`
      link.setAttribute('download', `ordini${filterSuffix}${dateSuffix}.csv`)
      
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
      alert('Esportazione completata!')
    } catch (error: any) {
      console.error('Errore esportazione:', error)
      alert('Errore durante l\'esportazione: ' + (error.response?.data?.message || error.message))
    } finally {
      setExporting(false)
    }
  }

  const buildDeliveryInvoiceParams = (overrides: Record<string, any> = {}) => {
    const params: any = {}

    if (startDate || endDate) {
      if (startDate) params.start_date = startDate
      if (endDate) params.end_date = endDate
    } else {
      params.period = periodFilter
    }

    if (statusFilter !== 'all') {
      params.status = statusFilter
    }

    return { ...params, ...overrides }
  }

  const handlePregenerateDeliveryInvoices = async () => {
    setLoadingDeliveryInvoicePreviews(true)
    try {
      const params = buildDeliveryInvoiceParams()
      const response = await deliveriesApi.pregenerateInvoices(params)
      const data = response.data

      if (data.success) {
        setDeliveryInvoicePreviews(data.data.previews || [])
        setDeliveryInvoicePeriodLabel(data.data.period?.label || '')
      } else {
        alert('Errore pre-generazione fatture: ' + (data.message || 'Errore sconosciuto'))
      }
    } catch (error: any) {
      console.error('Errore pre-generazione fatture consegne:', error)
      alert('Errore pre-generazione fatture: ' + (error.response?.data?.message || error.message))
    } finally {
      setLoadingDeliveryInvoicePreviews(false)
    }
  }

  const handleOpenDeliveryInvoicing = async () => {
    setShowDeliveryInvoicingModal(true)
    await handlePregenerateDeliveryInvoices()
  }

  const handleGenerateDeliveryInvoices = async (clientId?: number) => {
    const readyCount = deliveryInvoicePreviews.filter(p => p.invoice_ready).length
    const targetLabel = clientId ? 'questo cliente' : `${readyCount} fatture`

    if (!confirm(`Generare ${targetLabel} per le consegne gestite${deliveryInvoicePeriodLabel ? ` (${deliveryInvoicePeriodLabel})` : ''}?`)) {
      return
    }

    setGeneratingDeliveryInvoices(true)
    try {
      const payload = buildDeliveryInvoiceParams(clientId ? { client_id: clientId } : {})
      const response = await deliveriesApi.generateInvoices(payload)
      const data = response.data

      if (data.success) {
        alert(data.message || 'Fatture generate con successo')
        await handlePregenerateDeliveryInvoices()
        await loadOrders()
      } else {
        alert('Errore generazione fatture: ' + (data.message || 'Errore sconosciuto'))
      }
    } catch (error: any) {
      console.error('Errore generazione fatture consegne:', error)
      alert('Errore generazione fatture: ' + (error.response?.data?.message || error.message))
    } finally {
      setGeneratingDeliveryInvoices(false)
    }
  }

  const handleCancelOrder = async (order: Order) => {
    setCancelTargetOrder(order)
    setCancelPreview(null)
    setCancelToken('')
    setCancelWarnings([])
    setCancelResult(null)
    setCancelLoading(true)
    setShowCancelModal(true)

    try {
      const type = order.source_type === 'delivery' ? 'delivery' : 'order'
      const response = await cancellationApi.preview(type, order.id)
      const data = response.data
      setCancelPreview(data.preview)
      setCancelToken(data.confirmation_token)
      setCancelWarnings(data.warnings || [])
    } catch (error: any) {
      console.error('Errore preview cancellazione:', error)
      setCancelWarnings([error.response?.data?.message || 'Errore nel caricamento del preview'])
    } finally {
      setCancelLoading(false)
    }
  }

  const handleConfirmCancel = async () => {
    if (!cancelToken) return
    setCancelExecuting(true)
    try {
      const response = await cancellationApi.execute(cancelToken)
      setCancelResult(response.data)
      if (response.data.overall_success) {
        // Reload after successful cancellation
        setTimeout(() => {
          setShowCancelModal(false)
          loadOrders()
        }, 2000)
      }
    } catch (error: any) {
      console.error('Errore cancellazione:', error)
      setCancelResult({
        overall_success: false,
        results: { local: { success: false, message: error.response?.data?.message || 'Errore' } }
      })
    } finally {
      setCancelExecuting(false)
    }
  }

  const formatCurrency = (amount: number) => {
    // Gestisce valori null, undefined, NaN
    if (amount === null || amount === undefined || isNaN(amount)) {
      return '-'
    }
    // I prezzi nel DB sono memorizzati come interi (es. 3979 = 39.79€)
    const actualAmount = amount / 100
    return new Intl.NumberFormat('it-IT', {
      style: 'currency',
      currency: 'EUR',
    }).format(actualAmount)
  }

  const formatEuro = (amount: number) => {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return '-'
    }
    return new Intl.NumberFormat('it-IT', {
      style: 'currency',
      currency: 'EUR',
    }).format(amount)
  }

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  const getRestaurantName = (restaurantId: number): string => {
    const restaurant = restaurants.find(r => r.id === restaurantId)
    return restaurant?.name || `Ristorante #${restaurantId}`
  }

  const filteredRestaurants = restaurants.filter(restaurant => 
    restaurant.name.toLowerCase().includes(restaurantSearchTerm.toLowerCase()) ||
    restaurant.city?.toLowerCase().includes(restaurantSearchTerm.toLowerCase())
  )

  const handleRestaurantSelect = (restaurantId: string) => {
    setRestaurantFilter(restaurantId)
    if (restaurantId === 'all') {
      setRestaurantSearchTerm('')
    } else {
      const restaurant = restaurants.find(r => r.id === Number(restaurantId))
      setRestaurantSearchTerm(restaurant?.name || '')
    }
    setShowRestaurantDropdown(false)
  }

  const filteredOrders = orders.filter(order => {
    const searchLower = searchTerm.toLowerCase()
    const restaurantName = getRestaurantName(order.restaurant_id)
    return (
      order.order_number?.toLowerCase().includes(searchLower) ||
      order.client?.ragione_sociale?.toLowerCase().includes(searchLower) ||
      order.shipping_city?.toLowerCase().includes(searchLower) ||
      order.customer_name?.toLowerCase().includes(searchLower) ||
      restaurantName?.toLowerCase().includes(searchLower)
    )
  })

  const handleSort = (field: keyof Order) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortDirection('asc')
    }
  }

  const sortedOrders = [...filteredOrders].sort((a, b) => {
    let aVal: any = a[sortField]
    let bVal: any = b[sortField]
    
    // Gestione speciale per has_platform_discount (coupon)
    if (sortField === 'has_platform_discount' as any) {
      aVal = a.oppla_data?.has_platform_discount ? 1 : 0
      bVal = b.oppla_data?.has_platform_discount ? 1 : 0
    }
    
    if (aVal === null || aVal === undefined) return 1
    if (bVal === null || bVal === undefined) return -1
    
    if (typeof aVal === 'string' && typeof bVal === 'string') {
      return sortDirection === 'asc' 
        ? aVal.localeCompare(bVal)
        : bVal.localeCompare(aVal)
    }
    
    if (typeof aVal === 'number' && typeof bVal === 'number') {
      return sortDirection === 'asc' ? aVal - bVal : bVal - aVal
    }
    
    if (typeof aVal === 'boolean' && typeof bVal === 'boolean') {
      return sortDirection === 'asc' 
        ? (aVal === bVal ? 0 : aVal ? 1 : -1)
        : (aVal === bVal ? 0 : aVal ? -1 : 1)
    }
    
    return 0
  })

  // Paginazione
  const totalPages = Math.ceil(sortedOrders.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const paginatedOrders = sortedOrders.slice(startIndex, endIndex)

  // Reset alla pagina 1 quando cambiano i filtri
  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, statusFilter, restaurantFilter, periodFilter, startDate, endDate, dataSourceFilter, itemsPerPage])

  const goToPage = (page: number) => {
    setCurrentPage(Math.max(1, Math.min(page, totalPages)))
  }

  const renderPaginationControls = () => {
    const maxVisiblePages = 5
    const pages: number[] = []
    
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2))
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1)
    
    if (endPage - startPage < maxVisiblePages - 1) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1)
    }
    
    for (let i = startPage; i <= endPage; i++) {
      pages.push(i)
    }

    return (
      <div className="flex items-center justify-between gap-4 p-4 border-t border-gray-200">
        {/* Info e Selezione Risultati */}
        <div className="flex items-center gap-4 text-sm text-gray-500">
          <span>
            Mostra {startIndex + 1}-{Math.min(endIndex, sortedOrders.length)} di {sortedOrders.length}
          </span>
          <div className="flex items-center gap-2">
            <label>Risultati per pagina:</label>
            <select
              value={itemsPerPage}
              onChange={(e) => setItemsPerPage(Number(e.target.value))}
              className="glass-input py-1 px-2 text-sm"
            >
              <option value={10}>10</option>
              <option value={20}>20</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
        </div>

        {/* Controlli Navigazione */}
        <div className="flex items-center gap-2">
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => goToPage(currentPage - 1)}
            disabled={currentPage === 1}
            className="glass-button p-2 disabled:opacity-30 disabled:cursor-not-allowed"
          >
            <ChevronLeft className="w-4 h-4" />
          </motion.button>

          {startPage > 1 && (
            <>
              <motion.button
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
                onClick={() => goToPage(1)}
                className="glass-button px-3 py-1 text-sm"
              >
                1
              </motion.button>
              {startPage > 2 && <span className="text-gray-400">...</span>}
            </>
          )}

          {pages.map(page => (
            <motion.button
              key={page}
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => goToPage(page)}
              className={`px-3 py-1 text-sm rounded-lg transition-all ${
                page === currentPage
                  ? 'bg-primary-600 text-white'
                  : 'glass-button'
              }`}
            >
              {page}
            </motion.button>
          ))}

          {endPage < totalPages && (
            <>
              {endPage < totalPages - 1 && <span className="text-gray-400">...</span>}
              <motion.button
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
                onClick={() => goToPage(totalPages)}
                className="glass-button px-3 py-1 text-sm"
              >
                {totalPages}
              </motion.button>
            </>
          )}

          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => goToPage(currentPage + 1)}
            disabled={currentPage === totalPages}
            className="glass-button p-2 disabled:opacity-30 disabled:cursor-not-allowed"
          >
            <ChevronRight className="w-4 h-4" />
          </motion.button>
        </div>
      </div>
    )
  }

  const SortIcon = ({ field }: { field: keyof Order }) => {
    if (sortField !== field) return <ArrowUpDown className="h-3 w-3 opacity-50" />
    return sortDirection === 'asc' 
      ? <ArrowUp className="h-3 w-3" />
      : <ArrowDown className="h-3 w-3" />
  }

  const readyDeliveryInvoiceCount = deliveryInvoicePreviews.filter(p => p.invoice_ready).length
  const totalDeliveryInvoiceAmount = deliveryInvoicePreviews.reduce((sum, p) => sum + (p.total_amount || 0), 0)

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 className="text-3xl font-bold">
            <span className="text-gradient">Ordini</span>
          </h1>
          <p className="text-gray-500 mt-1">Gestione ordini dalla piattaforma Oppla</p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={async () => {
              const originalSource = dataSourceFilter
              setDataSourceFilter('deliveries')
              await new Promise(resolve => setTimeout(resolve, 100))
              await handleExportCSV()
              setDataSourceFilter(originalSource)
            }}
            disabled={exporting || loading}
            className="glass-button flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed bg-purple-600/20 border-purple-500/30"
          >
            <Download className={`w-4 h-4 ${exporting ? 'animate-pulse' : ''}`} />
            Export Deliveries
          </motion.button>
          {dataSourceFilter === 'deliveries' && (
            <motion.button
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={handleOpenDeliveryInvoicing}
              disabled={loading || syncing}
              className="glass-button flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed bg-emerald-600/20 border-emerald-500/30"
            >
              <FileText className="w-4 h-4" />
              Fattura Consegne
            </motion.button>
          )}
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={handleExportCSV}
            disabled={exporting || loading || filteredOrders.length === 0}
            className="glass-button flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Download className={`w-4 h-4 ${exporting ? 'animate-pulse' : ''}`} />
            {exporting ? 'Esportazione...' : 'Esporta Tutto'}
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={handleSync}
            disabled={syncing || loading}
            className="glass-button-primary flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <RefreshCw className={`w-4 h-4 ${syncing ? 'animate-spin' : ''}`} />
            {syncing ? 'Sincronizzazione...' : 'Sincronizza'}
          </motion.button>
          <div className="relative">
            <motion.button
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => setShowColumnSettings(!showColumnSettings)}
              className="glass-button flex items-center gap-2"
            >
              <Settings className="w-4 h-4" />
              Colonne
            </motion.button>

            <AnimatePresence>
              {showColumnSettings && (
                <motion.div
                  initial={{ opacity: 0, y: -10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                  className="absolute right-0 mt-2 w-64 glass-card p-4 z-50"
                >
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="font-semibold text-sm">Personalizza Colonne</h3>
                    <button
                      onClick={() => setVisibleColumns(defaultVisibleColumns)}
                      className="text-xs text-primary-400 hover:text-primary-300"
                    >
                      Reset
                    </button>
                  </div>
                  <div className="space-y-2 max-h-96 overflow-y-auto">
                    {(Object.keys(availableColumns) as ColumnKey[]).map(column => (
                      <label
                        key={column}
                        className="flex items-center gap-2 cursor-pointer hover:bg-white/5 p-2 rounded transition-colors"
                      >
                        <input
                          type="checkbox"
                          checked={isColumnVisible(column)}
                          onChange={() => toggleColumn(column)}
                          className="w-4 h-4 rounded border-gray-300 bg-gray-100 text-primary-500 focus:ring-primary-500 focus:ring-offset-0"
                        />
                        <span className="text-sm flex items-center gap-2">
                          {isColumnVisible(column) ? (
                            <Eye className="w-3 h-3 text-green-400" />
                          ) : (
                            <EyeOff className="w-3 h-3 text-gray-400" />
                          )}
                          {availableColumns[column]}
                        </span>
                      </label>
                    ))}
                  </div>
                  <div className="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-500">
                    {visibleColumns.length} di {Object.keys(availableColumns).length} colonne visibili
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </div>
      </div>

      {/* Filtri */}
      <div className="glass-card p-6">
        <div className="space-y-4">
          {/* Prima riga: Search, Period, Status, Restaurant, Data Source */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {/* Search */}
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
              <input
                type="search"
                placeholder="Cerca per numero ordine, cliente, città..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="glass-input pl-10 w-full"
              />
            </div>

            {/* Period Filter */}
            <div className="relative">
              <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
              <select
                value={periodFilter}
                onChange={(e) => {
                  setPeriodFilter(e.target.value)
                  // Reset date personalizzate quando si seleziona un periodo
                  setStartDate('')
                  setEndDate('')
                }}
                className="glass-input pl-10 w-full"
                disabled={!!(startDate || endDate)}
              >
                <option value="all">Tutti</option>
                <option value="today">Oggi</option>
                <option value="week">Questa Settimana</option>
                <option value="month">Questo Mese</option>
                <option value="last_month">Ultimo Mese</option>
                <option value="year">Quest'Anno</option>
                <option value="last_year">Ultimo Anno</option>
              </select>
            </div>

            {/* Status Filter */}
            <div className="relative">
              <Filter className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="glass-input pl-10 w-full"
              >
                <option value="all">Tutti gli Stati</option>
                <option value="New">Nuovo</option>
                <option value="Pending">In Attesa</option>
                <option value="Accepted">Accettato</option>
                <option value="Ready">Pronto</option>
                <option value="Delivering">In Consegna</option>
                <option value="Completed">Completato</option>
                <option value="Rejected">Rifiutato</option>
                <option value="CancelledByCustomer">Annullato dal Cliente</option>
              </select>
            </div>

            {/* Restaurant Filter - Autocomplete */}
            <div className="relative">
              <Building2 className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500 z-10" />
              <input
                type="text"
                placeholder="Cerca ristorante..."
                value={restaurantSearchTerm}
                onChange={(e) => {
                  setRestaurantSearchTerm(e.target.value)
                  setShowRestaurantDropdown(true)
                  if (!e.target.value) {
                    setRestaurantFilter('all')
                  }
                }}
                onFocus={() => setShowRestaurantDropdown(true)}
                className="glass-input pl-10 w-full"
                disabled={loadingRestaurants}
              />
              {showRestaurantDropdown && (
                <>
                  <div 
                    className="fixed inset-0 z-10" 
                    onClick={() => setShowRestaurantDropdown(false)}
                  />
                  <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="absolute top-full left-0 right-0 mt-1 glass-card max-h-64 overflow-y-auto z-20"
                  >
                    <div
                      onClick={() => handleRestaurantSelect('all')}
                      className={`px-4 py-2 cursor-pointer hover:bg-white/10 transition-colors ${
                        restaurantFilter === 'all' ? 'bg-primary-500/20' : ''
                      }`}
                    >
                      <span className="font-medium">Tutti i Ristoranti</span>
                    </div>
                    {filteredRestaurants.length === 0 ? (
                      <div className="px-4 py-2 text-gray-500 text-sm">
                        Nessun ristorante trovato
                      </div>
                    ) : (
                      filteredRestaurants.map(restaurant => (
                        <div
                          key={restaurant.id}
                          onClick={() => handleRestaurantSelect(String(restaurant.id))}
                          className={`px-4 py-2 cursor-pointer hover:bg-white/10 transition-colors ${
                            restaurantFilter === String(restaurant.id) ? 'bg-primary-500/20' : ''
                          }`}
                        >
                          <div className="font-medium">{restaurant.name}</div>
                          {restaurant.city && (
                            <div className="text-xs text-gray-500">{restaurant.city}</div>
                          )}
                        </div>
                      ))
                    )}
                  </motion.div>
                </>
              )}
            </div>

            {/* Data Source Filter */}
            <div className="relative">
              <Package className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
              <select
                value={dataSourceFilter}
                onChange={(e) => setDataSourceFilter(e.target.value as 'all' | 'orders' | 'deliveries')}
                className="glass-input pl-10 w-full"
              >
                <option value="all">Tutti i Dati</option>
                <option value="orders">Solo Ordini</option>
                <option value="deliveries">Solo Consegne Gestite</option>
              </select>
            </div>
          </div>

          {/* Seconda riga: Filtro Date Personalizzato */}
          <div className="border-t border-gray-200 pt-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
              {/* Data Inizio */}
              <div>
                <label className="block text-xs text-gray-500 mb-2">Data Inizio</label>
                <input
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="glass-input w-full"
                />
              </div>

              {/* Data Fine */}
              <div>
                <label className="block text-xs text-gray-500 mb-2">Data Fine</label>
                <input
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="glass-input w-full"
                />
              </div>

              {/* Pulsante Reset Date */}
              <div>
                <motion.button
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                  onClick={() => {
                    setStartDate('')
                    setEndDate('')
                  }}
                  disabled={!startDate && !endDate}
                  className="glass-button w-full disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Reset Date
                </motion.button>
              </div>
            </div>
            {(startDate || endDate) && (
              <p className="text-xs text-primary-400 mt-2">
                Filtro personalizzato attivo - Il filtro periodo è disabilitato
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Orders List */}
      <div className="glass-card overflow-hidden">
        {loading ? (
          <div className="p-12 text-center">
            <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-primary-500 border-r-transparent"></div>
            <p className="mt-4 text-gray-500">Caricamento ordini...</p>
          </div>
        ) : filteredOrders.length === 0 ? (
          <div className="p-12 text-center">
            <Package className="mx-auto h-12 w-12 text-gray-500" />
            <p className="mt-4 text-gray-500">Nessun ordine trovato</p>
            <p className="text-sm text-gray-400 mt-2">
              Clicca su "Sincronizza" per importare gli ordini da Oppla
            </p>
          </div>
        ) : (
          <>
            {/* Controlli Paginazione Superiori */}
            {renderPaginationControls()}

            <div className="overflow-x-auto">
            <table className="w-full min-w-max">
              <thead>
                <tr className="border-b border-gray-200">
                  {isColumnVisible('id') && (
                    <th 
                      onClick={() => handleSort('id')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        ID <SortIcon field="id" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('client_id') && (
                    <th 
                      onClick={() => handleSort('client_id')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Cliente ID <SortIcon field="client_id" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('restaurant_id') && (
                    <th 
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600"
                    >
                      Ristorante
                    </th>
                  )}
                  {isColumnVisible('restaurant_name') && (
                    <th 
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600"
                    >
                      Ristorante
                    </th>
                  )}
                  {isColumnVisible('order_number') && (
                    <th 
                      onClick={() => handleSort('order_number')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        N. Ordine <SortIcon field="order_number" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('status') && (
                    <th 
                      onClick={() => handleSort('status')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Stato <SortIcon field="status" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('order_date') && (
                    <th 
                      onClick={() => handleSort('order_date')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Data <SortIcon field="order_date" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('subtotal') && (
                    <th 
                      onClick={() => handleSort('subtotal')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Subtotale <SortIcon field="subtotal" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('delivery_fee') && (
                    <th 
                      onClick={() => handleSort('delivery_fee')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Costo Consegna <SortIcon field="delivery_fee" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('discount') && (
                    <th 
                      onClick={() => handleSort('discount')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Sconto <SortIcon field="discount" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('total_amount') && (
                    <th 
                      onClick={() => handleSort('total_amount')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Totale <SortIcon field="total_amount" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('customer_name') && (
                    <th 
                      onClick={() => handleSort('customer_name')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Nome Cliente <SortIcon field="customer_name" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('shipping_address') && (
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                      Indirizzo
                    </th>
                  )}
                  {isColumnVisible('shipping_city') && (
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">
                      Città
                    </th>
                  )}
                  {isColumnVisible('items_count') && (
                    <th 
                      onClick={() => handleSort('items_count')}
                      className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors"
                    >
                      <div className="flex items-center gap-1">
                        Articoli <SortIcon field="items_count" />
                      </div>
                    </th>
                  )}
                  {isColumnVisible('has_platform_discount') && (
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400 transition-colors">
                      <div className="flex items-center gap-1">
                        Coupon
                      </div>
                    </th>
                  )}
                  <th className="px-4 py-3 text-right text-xs font-semibold text-gray-600">
                    Azioni
                  </th>
                </tr>
              </thead>
              <tbody>
                {paginatedOrders.map((order, index) => (
                  <motion.tr
                    key={order.id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.05 }}
                    className="border-b border-white/5 hover:bg-white/5 transition-colors"
                  >
                    {isColumnVisible('id') && (
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {order.id}
                      </td>
                    )}
                    {isColumnVisible('client_id') && (
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {order.client_id}
                      </td>
                    )}
                    {isColumnVisible('restaurant_id') && (
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <Building2 className="h-4 w-4 text-purple-400" />
                          <span className="text-sm text-gray-600">
                            {getRestaurantName(order.restaurant_id)}
                          </span>
                        </div>
                      </td>
                    )}
                    {isColumnVisible('restaurant_name') && (
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <Building2 className="h-4 w-4 text-purple-400" />
                          <span className="text-sm text-gray-600">
                            {getRestaurantName(order.restaurant_id)}
                          </span>
                        </div>
                      </td>
                    )}
                    {isColumnVisible('order_number') && (
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <Package className="h-4 w-4 text-primary-400" />
                          <div className="flex flex-col gap-1">
                            <span className="font-mono text-xs font-semibold text-primary-400">
                              {order.order_number}
                            </span>
                            {order.source_type && (
                              <span className={`text-[10px] px-1.5 py-0.5 rounded-full font-medium ${
                                order.source_type === 'order' 
                                  ? 'bg-primary-500/20 text-primary-400' 
                                  : 'bg-purple-500/20 text-purple-400'
                              }`}>
                                {order.source_type === 'order' ? 'Ordine' : 'Consegna Gestita'}
                              </span>
                            )}
                          </div>
                        </div>
                      </td>
                    )}
                    {isColumnVisible('status') && (
                      <td className="px-4 py-3">
                        <span
                          className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border ${
                            statusColors[order.status] || statusColors.pending
                          }`}
                        >
                          {statusLabels[order.status] || order.status}
                        </span>
                      </td>
                    )}
                    {isColumnVisible('order_date') && (
                      <td className="px-4 py-3 text-xs text-gray-600">
                        {formatDate(order.order_date)}
                      </td>
                    )}
                    {isColumnVisible('subtotal') && (
                      <td className="px-4 py-3">
                        <span className="text-sm text-gray-600">
                          {formatCurrency(order.subtotal)}
                        </span>
                      </td>
                    )}
                    {isColumnVisible('delivery_fee') && (
                      <td className="px-4 py-3">
                        <span className="text-sm text-gray-600">
                          {formatCurrency(order.delivery_fee)}
                        </span>
                      </td>
                    )}
                    {isColumnVisible('discount') && (
                      <td className="px-4 py-3">
                        <span className="text-sm text-orange-400">
                          {formatCurrency(order.discount)}
                        </span>
                      </td>
                    )}
                    {isColumnVisible('total_amount') && (
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <Euro className="h-4 w-4 text-green-400" />
                          <span className="font-semibold text-green-400 text-sm">
                            {formatCurrency(order.total_amount)}
                          </span>
                        </div>
                      </td>
                    )}
                    {isColumnVisible('customer_name') && (
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {order.customer_name || '-'}
                      </td>
                    )}
                    {isColumnVisible('shipping_address') && (
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {order.shipping_address || '-'}
                      </td>
                    )}
                    {isColumnVisible('shipping_city') && (
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {order.shipping_city || '-'}
                      </td>
                    )}
                    {isColumnVisible('items_count') && (
                      <td className="px-4 py-3 text-sm text-gray-600 text-center">
                        {order.items_count || 0}
                      </td>
                    )}
                    {isColumnVisible('has_platform_discount') && (
                      <td className="px-4 py-3 text-center">
                        {order.oppla_data?.has_platform_discount ? (
                          <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                            Sì
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-slate-500/20 text-gray-500 border border-gray-300/30">
                            No
                          </span>
                        )}
                      </td>
                    )}
                    <td className="px-4 py-3 text-right">
                      <motion.button
                        whileHover={{ scale: 1.1 }}
                        whileTap={{ scale: 0.9 }}
                        onClick={() => handleCancelOrder(order)}
                        className="p-1.5 rounded-lg hover:bg-red-500/20 text-gray-500 hover:text-red-400 transition-colors"
                        title="Annulla ordine/consegna"
                      >
                        <Ban className="w-4 h-4" />
                      </motion.button>
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>

            {/* Controlli Paginazione Inferiori */}
            {renderPaginationControls()}
          </div>
          </>
        )}
      </div>

      {/* Stats */}
      {!loading && sortedOrders.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass-card p-6"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-primary-500/20">
                <Package className="w-6 h-6 text-primary-400" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Totale Ordini</p>
                <p className="text-2xl font-bold">{sortedOrders.length}</p>
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="glass-card p-6"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-green-500/20">
                <Euro className="w-6 h-6 text-green-400" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Fatturato Totale</p>
                <p className="text-2xl font-bold text-green-400">
                  {formatCurrency(sortedOrders.reduce((sum, o) => sum + o.total_amount, 0))}
                </p>
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="glass-card p-6"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-purple-500/20">
                <TrendingUp className="w-6 h-6 text-purple-400" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Valore Medio</p>
                <p className="text-2xl font-bold text-purple-400">
                  {formatCurrency(
                    sortedOrders.length > 0
                      ? sortedOrders.reduce((sum, o) => sum + o.total_amount, 0) /
                          sortedOrders.length
                      : 0
                  )}
                </p>
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="glass-card p-6"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-green-500/20">
                <Package className="w-6 h-6 text-green-400" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Consegnati</p>
                <p className="text-2xl font-bold text-green-400">
                  {sortedOrders.filter((o) => o.status === 'delivered').length}
                </p>
              </div>
            </div>
          </motion.div>
        </div>
      )}

      <AnimatePresence>
        {showDeliveryInvoicingModal && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
          >
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: 20 }}
              className="glass-card w-full max-w-4xl p-6"
            >
              <div className="flex items-start justify-between gap-4">
                <div>
                  <h2 className="text-2xl font-bold">Fatturazione Consegne Gestite</h2>
                  <p className="text-sm text-gray-500">
                    Periodo: {deliveryInvoicePeriodLabel || 'N/D'} • Solo consegne non fatturate
                  </p>
                  <p className="text-xs text-gray-400 mt-1">
                    Nota: con filtro stato su \"Tutti\" vengono incluse solo consegne Completed/Completata.
                  </p>
                </div>
                <button
                  onClick={() => setShowDeliveryInvoicingModal(false)}
                  className="glass-button p-2"
                  aria-label="Chiudi"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>

              <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-6 text-sm text-gray-600">
                  <span>
                    Clienti: <span className="text-gray-900 dark:text-white font-semibold">{deliveryInvoicePreviews.length}</span>
                  </span>
                  <span>
                    Pronte: <span className="text-emerald-400 font-semibold">{readyDeliveryInvoiceCount}</span>
                  </span>
                  <span>
                    Totale: <span className="text-gray-900 dark:text-white font-semibold">{formatEuro(totalDeliveryInvoiceAmount)}</span>
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <motion.button
                    whileHover={{ scale: 1.03 }}
                    whileTap={{ scale: 0.97 }}
                    onClick={handlePregenerateDeliveryInvoices}
                    disabled={loadingDeliveryInvoicePreviews}
                    className="glass-button px-3 py-2 text-sm disabled:opacity-50"
                  >
                    {loadingDeliveryInvoicePreviews ? 'Aggiornamento...' : 'Aggiorna Preview'}
                  </motion.button>
                  <motion.button
                    whileHover={{ scale: 1.03 }}
                    whileTap={{ scale: 0.97 }}
                    onClick={() => handleGenerateDeliveryInvoices()}
                    disabled={generatingDeliveryInvoices || readyDeliveryInvoiceCount === 0}
                    className="glass-button-primary px-3 py-2 text-sm disabled:opacity-50"
                  >
                    {generatingDeliveryInvoices ? 'Generazione...' : `Genera ${readyDeliveryInvoiceCount} Fatture`}
                  </motion.button>
                </div>
              </div>

              <div className="mt-6">
                {loadingDeliveryInvoicePreviews ? (
                  <div className="text-center text-gray-500 py-10">Caricamento preview...</div>
                ) : deliveryInvoicePreviews.length === 0 ? (
                  <div className="text-center text-gray-500 py-10">
                    Nessuna consegna da fatturare per questo periodo.
                  </div>
                ) : (
                  <div className="space-y-3 max-h-[60vh] overflow-y-auto pr-2">
                    {deliveryInvoicePreviews.map((preview) => (
                      <div
                        key={`${preview.client_id ?? 'missing'}-${preview.client_name}`}
                        className="glass-card p-4 flex flex-wrap items-center justify-between gap-4"
                      >
                        <div>
                          <p className="font-semibold text-gray-900 dark:text-white">{preview.client_name}</p>
                          <p className="text-xs text-gray-500">
                            {preview.deliveries_count} consegne • {formatEuro(preview.total_amount)}
                          </p>
                          {preview.already_generated && (
                            <p className="text-xs text-amber-400">
                              Fattura già generata {preview.existing_invoice_number ? `#${preview.existing_invoice_number}` : ''}
                            </p>
                          )}
                          {preview.error && (
                            <p className="text-xs text-red-400">{preview.error}</p>
                          )}
                        </div>
                        <motion.button
                          whileHover={{ scale: 1.03 }}
                          whileTap={{ scale: 0.97 }}
                          onClick={() => preview.client_id && handleGenerateDeliveryInvoices(preview.client_id)}
                          disabled={!preview.invoice_ready || generatingDeliveryInvoices}
                          className="glass-button px-3 py-2 text-sm disabled:opacity-50"
                        >
                          Genera Fattura
                        </motion.button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Cancellation Modal */}
      <AnimatePresence>
        {showCancelModal && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
          >
            <motion.div
              initial={{ opacity: 0, y: 20, scale: 0.95 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: 20, scale: 0.95 }}
              className="glass-card w-full max-w-lg p-6"
            >
              <div className="flex items-start justify-between gap-4 mb-4">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-lg bg-red-500/20">
                    <Ban className="w-5 h-5 text-red-400" />
                  </div>
                  <div>
                    <h2 className="text-xl font-bold">Annulla {cancelTargetOrder?.source_type === 'delivery' ? 'Consegna' : 'Ordine'}</h2>
                    <p className="text-sm text-gray-500">
                      {cancelTargetOrder?.order_number} - {cancelTargetOrder?.customer_name}
                    </p>
                  </div>
                </div>
                <button
                  onClick={() => setShowCancelModal(false)}
                  className="glass-button p-2"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>

              {cancelLoading ? (
                <div className="text-center py-8">
                  <Loader2 className="w-8 h-8 animate-spin text-primary-400 mx-auto" />
                  <p className="text-gray-500 mt-3">Analisi in corso...</p>
                </div>
              ) : cancelResult ? (
                /* Results */
                <div className="space-y-4">
                  <div className={`p-4 rounded-lg border ${cancelResult.overall_success ? 'bg-green-500/10 border-green-500/30' : 'bg-amber-500/10 border-amber-500/30'}`}>
                    <div className="flex items-center gap-2 mb-3">
                      {cancelResult.overall_success ? (
                        <CheckCircle2 className="w-5 h-5 text-green-400" />
                      ) : (
                        <AlertTriangle className="w-5 h-5 text-amber-400" />
                      )}
                      <span className="font-semibold">
                        {cancelResult.overall_success ? 'Annullamento completato' : 'Annullamento parziale'}
                      </span>
                    </div>
                    <div className="space-y-2 text-sm">
                      {Object.entries(cancelResult.results || {}).map(([key, val]: [string, any]) => (
                        <div key={key} className="flex items-center gap-2">
                          {val.success ? (
                            <CheckCircle2 className="w-4 h-4 text-green-400 flex-shrink-0" />
                          ) : val.skipped ? (
                            <span className="w-4 h-4 text-gray-400 flex-shrink-0">-</span>
                          ) : (
                            <XCircle className="w-4 h-4 text-red-400 flex-shrink-0" />
                          )}
                          <span className="text-gray-600 capitalize">{key}:</span>
                          <span className={val.success ? 'text-green-400' : val.skipped ? 'text-gray-400' : 'text-red-400'}>
                            {val.message}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                  <div className="flex justify-end">
                    <motion.button
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      onClick={() => { setShowCancelModal(false); loadOrders(); }}
                      className="glass-button"
                    >
                      Chiudi
                    </motion.button>
                  </div>
                </div>
              ) : cancelPreview ? (
                /* Preview */
                <div className="space-y-4">
                  <div className="space-y-3">
                    {/* Local */}
                    <div className="flex items-center gap-3 p-3 rounded-lg bg-white/5">
                      <CheckCircle2 className="w-5 h-5 text-green-400 flex-shrink-0" />
                      <div>
                        <p className="text-sm font-medium">Database Locale</p>
                        <p className="text-xs text-gray-500">
                          Record verra' eliminato
                          {cancelPreview.local?.has_invoice && ` (rimosso da fattura #${cancelPreview.local.invoice_number || cancelPreview.local.invoice_id})`}
                        </p>
                      </div>
                    </div>

                    {/* Oppla */}
                    <div className="flex items-center gap-3 p-3 rounded-lg bg-white/5">
                      {cancelPreview.oppla?.can_cancel ? (
                        <CheckCircle2 className="w-5 h-5 text-green-400 flex-shrink-0" />
                      ) : (
                        <XCircle className="w-5 h-5 text-gray-400 flex-shrink-0" />
                      )}
                      <div>
                        <p className="text-sm font-medium">Oppla</p>
                        <p className="text-xs text-gray-500">
                          {cancelPreview.oppla?.can_cancel
                            ? `Eliminazione da ${cancelPreview.oppla.table} (ID: ${cancelPreview.oppla.oppla_id})`
                            : 'Nessun ID Oppla - non verra\' cancellato'}
                        </p>
                      </div>
                    </div>

                    {/* Tookan */}
                    <div className="flex items-center gap-3 p-3 rounded-lg bg-white/5">
                      {cancelPreview.tookan?.can_cancel ? (
                        <CheckCircle2 className="w-5 h-5 text-green-400 flex-shrink-0" />
                      ) : (
                        <AlertTriangle className="w-5 h-5 text-amber-400 flex-shrink-0" />
                      )}
                      <div>
                        <p className="text-sm font-medium">Tookan (Task Rider)</p>
                        <p className="text-xs text-gray-500">
                          {cancelPreview.tookan?.can_cancel
                            ? `Task #${cancelPreview.tookan.job_id} verra' cancellato${cancelPreview.tookan.rider ? ` (Rider: ${cancelPreview.tookan.rider})` : ''}`
                            : (cancelPreview.tookan?.reason || 'Job ID non trovato - cancellazione manuale necessaria')}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Warnings */}
                  {cancelWarnings.length > 0 && (
                    <div className="p-3 rounded-lg bg-amber-500/10 border border-amber-500/30">
                      <div className="flex items-center gap-2 mb-1">
                        <AlertTriangle className="w-4 h-4 text-amber-400" />
                        <span className="text-sm font-medium text-amber-400">Attenzione</span>
                      </div>
                      <ul className="text-xs text-amber-300/80 space-y-1">
                        {cancelWarnings.map((w, i) => (
                          <li key={i}>- {w}</li>
                        ))}
                      </ul>
                    </div>
                  )}

                  <div className="flex gap-3 justify-end pt-2">
                    <motion.button
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      onClick={() => setShowCancelModal(false)}
                      className="glass-button"
                    >
                      Annulla
                    </motion.button>
                    <motion.button
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      onClick={handleConfirmCancel}
                      disabled={cancelExecuting}
                      className="glass-button bg-red-600/20 border-red-500/30 hover:bg-red-600/40 text-red-400 disabled:opacity-50"
                    >
                      {cancelExecuting ? (
                        <>
                          <Loader2 className="w-4 h-4 animate-spin inline mr-2" />
                          Cancellazione...
                        </>
                      ) : (
                        'Conferma Annullamento'
                      )}
                    </motion.button>
                  </div>
                </div>
              ) : (
                /* Error state */
                <div className="text-center py-6">
                  <XCircle className="w-8 h-8 text-red-400 mx-auto" />
                  <p className="text-red-400 mt-2">Errore nel caricamento</p>
                  {cancelWarnings.length > 0 && (
                    <p className="text-sm text-gray-500 mt-1">{cancelWarnings[0]}</p>
                  )}
                  <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    onClick={() => setShowCancelModal(false)}
                    className="glass-button mt-4"
                  >
                    Chiudi
                  </motion.button>
                </div>
              )}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
