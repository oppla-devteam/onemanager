import { motion, AnimatePresence } from 'framer-motion'
import { useState, useEffect } from 'react'
import {
  Search,
  Truck,
  Clock,
  CheckCircle,
  XCircle,
  Package,
  Filter,
  Calendar,
  Euro,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  ChevronLeft,
  ChevronRight,
  Download,
  RefreshCw,
} from 'lucide-react'
import { deliveriesApi } from '../utils/api'

interface Delivery {
  id: number
  client_id: number
  order_id: string
  order_type: string
  delivery_code: string | null
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
  customer_name: string | null
  customer_phone: string | null
  payment_method: string | null
  delivery_notes: string | null
  created_at: string
  updated_at: string
  client?: {
    id: number
    ragione_sociale: string
    citta: string
  }
}

const statusColors: Record<string, string> = {
  Created: 'bg-primary-500/20 text-primary-400 border-primary-500/30',
  in_attesa: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  assegnata: 'bg-primary-500/20 text-primary-400 border-primary-500/30',
  in_consegna: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
  completata: 'bg-green-500/20 text-green-400 border-green-500/30',
  Completed: 'bg-green-500/20 text-green-400 border-green-500/30',
  annullata: 'bg-red-500/20 text-red-400 border-red-500/30',
  Delivering: 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30',
  Accepted: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
  Pending: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  New: 'bg-primary-500/20 text-primary-400 border-primary-500/30',
}

const statusLabels: Record<string, string> = {
  Created: 'Creata',
  in_attesa: 'In Attesa',
  assegnata: 'Assegnata',
  in_consegna: 'In Consegna',
  completata: 'Completata',
  Completed: 'Completata',
  annullata: 'Annullata',
  Delivering: 'In Consegna',
  Accepted: 'Accettata',
  Pending: 'In Attesa',
  New: 'Nuova',
}

export default function Deliveries() {
  const [deliveries, setDeliveries] = useState<Delivery[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [periodFilter, setPeriodFilter] = useState<string>('all')
  const [startDate, setStartDate] = useState('')
  const [endDate, setEndDate] = useState('')
  const [sortField, setSortField] = useState<string>('created_at')
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc')
  const [exporting, setExporting] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [totalItems, setTotalItems] = useState(0)
  const [itemsPerPage, setItemsPerPage] = useState(20)
  const [stats, setStats] = useState({
    total: 0,
    in_attesa: 0,
    assegnata: 0,
    in_consegna: 0,
    completata: 0,
    completata_oggi: 0,
    totale_mese: 0,
  })

  useEffect(() => {
    loadDeliveries()
  }, [periodFilter, statusFilter, startDate, endDate, currentPage, itemsPerPage, sortField, sortDirection])

  useEffect(() => {
    loadStats()
  }, [])

  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, statusFilter, periodFilter, startDate, endDate, itemsPerPage])

  const loadStats = async () => {
    try {
      const response = await deliveriesApi.stats()
      setStats(response.data)
    } catch (error) {
      console.error('Errore caricamento statistiche:', error)
    }
  }

  const loadDeliveries = async () => {
    setLoading(true)
    try {
      const params: any = {
        per_page: itemsPerPage,
        page: currentPage,
        sort_by: sortField,
        sort_order: sortDirection,
      }

      if (startDate || endDate) {
        if (startDate) params.start_date = startDate
        if (endDate) params.end_date = endDate
      } else if (periodFilter !== 'all') {
        params.period = periodFilter
      }

      if (statusFilter !== 'all') {
        params.status = statusFilter
      }

      if (searchTerm) {
        params.search = searchTerm
      }

      const response = await deliveriesApi.getAll(params)
      const paginated = response.data
      setDeliveries(paginated.data || [])
      setTotalPages(paginated.last_page || 1)
      setTotalItems(paginated.total || 0)
    } catch (error) {
      console.error('Errore caricamento consegne:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const params: any = {}

      if (startDate || endDate) {
        if (startDate) params.start_date = startDate
        if (endDate) params.end_date = endDate
      } else if (periodFilter !== 'all') {
        params.period = periodFilter
      }

      if (statusFilter !== 'all') {
        params.status = statusFilter
      }

      const response = await deliveriesApi.export(params)

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      const dateSuffix = startDate && endDate ? `_${startDate}_${endDate}` : `_${periodFilter}`
      link.setAttribute('download', `consegne_gestite${dateSuffix}.csv`)
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

  const formatCurrency = (amount: number) => {
    if (amount === null || amount === undefined || isNaN(amount)) return '-'
    const actualAmount = amount / 100
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(actualAmount)
  }

  const formatDate = (date: string) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const handleSort = (field: string) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortField(field)
      setSortDirection('asc')
    }
  }

  const SortIcon = ({ field }: { field: string }) => {
    if (sortField !== field) return <ArrowUpDown className="h-3 w-3 opacity-50" />
    return sortDirection === 'asc'
      ? <ArrowUp className="h-3 w-3" />
      : <ArrowDown className="h-3 w-3" />
  }

  const goToPage = (page: number) => {
    setCurrentPage(Math.max(1, Math.min(page, totalPages)))
  }

  const renderPaginationControls = () => {
    const maxVisiblePages = 5
    const pages: number[] = []
    let startP = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2))
    let endP = Math.min(totalPages, startP + maxVisiblePages - 1)
    if (endP - startP < maxVisiblePages - 1) {
      startP = Math.max(1, endP - maxVisiblePages + 1)
    }
    for (let i = startP; i <= endP; i++) pages.push(i)

    return (
      <div className="flex items-center justify-between gap-4 p-4 border-t border-gray-200">
        <div className="flex items-center gap-4 text-sm text-gray-500">
          <span>
            Mostra {((currentPage - 1) * itemsPerPage) + 1}-{Math.min(currentPage * itemsPerPage, totalItems)} di {totalItems}
          </span>
          <div className="flex items-center gap-2">
            <label>Per pagina:</label>
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
          {startP > 1 && (
            <>
              <motion.button whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }} onClick={() => goToPage(1)} className="glass-button px-3 py-1 text-sm">1</motion.button>
              {startP > 2 && <span className="text-gray-400">...</span>}
            </>
          )}
          {pages.map(page => (
            <motion.button
              key={page}
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => goToPage(page)}
              className={`px-3 py-1 text-sm rounded-lg transition-all ${page === currentPage ? 'bg-primary-600 text-white' : 'glass-button'}`}
            >
              {page}
            </motion.button>
          ))}
          {endP < totalPages && (
            <>
              {endP < totalPages - 1 && <span className="text-gray-400">...</span>}
              <motion.button whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }} onClick={() => goToPage(totalPages)} className="glass-button px-3 py-1 text-sm">{totalPages}</motion.button>
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

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 className="text-3xl font-bold">
            <span className="text-gradient">Consegne Gestite</span>
          </h1>
          <p className="text-gray-500 mt-1">Managed deliveries dalla piattaforma Oppla</p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={handleExportCSV}
            disabled={exporting || loading}
            className="glass-button flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Download className={`w-4 h-4 ${exporting ? 'animate-pulse' : ''}`} />
            {exporting ? 'Esportazione...' : 'Esporta CSV'}
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => { loadDeliveries(); loadStats(); }}
            disabled={loading}
            className="glass-button-primary flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            Aggiorna
          </motion.button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="glass-card p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-xs">In Attesa</p>
              <p className="text-xl font-bold mt-1 text-yellow-400">{stats.in_attesa}</p>
            </div>
            <Clock className="w-6 h-6 text-yellow-400" />
          </div>
        </motion.div>
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="glass-card p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-xs">Assegnate</p>
              <p className="text-xl font-bold mt-1 text-primary-400">{stats.assegnata}</p>
            </div>
            <Package className="w-6 h-6 text-primary-400" />
          </div>
        </motion.div>
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="glass-card p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-xs">In Consegna</p>
              <p className="text-xl font-bold mt-1 text-purple-400">{stats.in_consegna}</p>
            </div>
            <Truck className="w-6 h-6 text-purple-400" />
          </div>
        </motion.div>
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }} className="glass-card p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-xs">Completate Oggi</p>
              <p className="text-xl font-bold mt-1 text-green-400">{stats.completata_oggi}</p>
            </div>
            <CheckCircle className="w-6 h-6 text-green-400" />
          </div>
        </motion.div>
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.5 }} className="glass-card p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-500 text-xs">Totale Mese</p>
              <p className="text-xl font-bold mt-1">{stats.totale_mese}</p>
            </div>
            <Truck className="w-6 h-6 text-primary-400" />
          </div>
        </motion.div>
      </div>

      {/* Filters */}
      <div className="glass-card p-6">
        <div className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Search */}
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
              <input
                type="search"
                placeholder="Cerca per codice, cliente, indirizzo..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                onKeyDown={(e) => { if (e.key === 'Enter') loadDeliveries() }}
                className="glass-input pl-10 w-full"
              />
            </div>

            {/* Period Filter */}
            <div className="relative">
              <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" />
              <select
                value={periodFilter}
                onChange={(e) => { setPeriodFilter(e.target.value); setStartDate(''); setEndDate('') }}
                className="glass-input pl-10 w-full"
                disabled={!!(startDate || endDate)}
              >
                <option value="all">Tutti</option>
                <option value="today">Oggi</option>
                <option value="week">Questa Settimana</option>
                <option value="month">Questo Mese</option>
                <option value="year">Quest'Anno</option>
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
                <option value="Created">Creata</option>
                <option value="in_attesa">In Attesa</option>
                <option value="assegnata">Assegnata</option>
                <option value="in_consegna">In Consegna</option>
                <option value="completata">Completata</option>
                <option value="Completed">Completed</option>
                <option value="annullata">Annullata</option>
                <option value="Delivering">Delivering</option>
              </select>
            </div>

            {/* Search Button */}
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={loadDeliveries}
              className="glass-button flex items-center justify-center gap-2"
            >
              <Search className="w-4 h-4" />
              Cerca
            </motion.button>
          </div>

          {/* Custom Date Range */}
          <div className="border-t border-gray-200 pt-4">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
              <div>
                <label className="block text-xs text-gray-500 mb-2">Data Inizio</label>
                <input
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="glass-input w-full"
                />
              </div>
              <div>
                <label className="block text-xs text-gray-500 mb-2">Data Fine</label>
                <input
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="glass-input w-full"
                />
              </div>
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => { setStartDate(''); setEndDate('') }}
                disabled={!startDate && !endDate}
                className="glass-button w-full disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Reset Date
              </motion.button>
            </div>
            {(startDate || endDate) && (
              <p className="text-xs text-primary-400 mt-2">
                Filtro personalizzato attivo - Il filtro periodo è disabilitato
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Deliveries Table */}
      <div className="glass-card overflow-hidden">
        {loading ? (
          <div className="p-12 text-center">
            <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-primary-500 border-r-transparent"></div>
            <p className="mt-4 text-gray-500">Caricamento consegne...</p>
          </div>
        ) : deliveries.length === 0 ? (
          <div className="p-12 text-center">
            <Truck className="mx-auto h-12 w-12 text-gray-500" />
            <p className="mt-4 text-gray-500">Nessuna consegna trovata</p>
            <p className="text-sm text-gray-400 mt-2">
              Sincronizza gli ordini dalla pagina Ordini per importare le consegne gestite
            </p>
          </div>
        ) : (
          <>
            {renderPaginationControls()}
            <div className="overflow-x-auto">
              <table className="w-full min-w-max">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th onClick={() => handleSort('delivery_code')} className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400">
                      <div className="flex items-center gap-1">Codice <SortIcon field="delivery_code" /></div>
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Cliente</th>
                    <th onClick={() => handleSort('status')} className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400">
                      <div className="flex items-center gap-1">Stato <SortIcon field="status" /></div>
                    </th>
                    <th onClick={() => handleSort('created_at')} className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400">
                      <div className="flex items-center gap-1">Data <SortIcon field="created_at" /></div>
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Indirizzo Consegna</th>
                    <th onClick={() => handleSort('order_amount')} className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400">
                      <div className="flex items-center gap-1">Importo <SortIcon field="order_amount" /></div>
                    </th>
                    <th onClick={() => handleSort('delivery_fee_total')} className="px-4 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer hover:text-primary-400">
                      <div className="flex items-center gap-1">Fee <SortIcon field="delivery_fee_total" /></div>
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Cliente Finale</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600">Pagamento</th>
                    <th className="px-4 py-3 text-center text-xs font-semibold text-gray-600">Fatturata</th>
                  </tr>
                </thead>
                <tbody>
                  {deliveries.map((delivery, index) => (
                    <motion.tr
                      key={delivery.id}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: index * 0.03 }}
                      className="border-b border-white/5 hover:bg-white/5 transition-colors"
                    >
                      <td className="px-4 py-3">
                        <span className="font-mono text-xs font-semibold text-primary-400">
                          {delivery.delivery_code || delivery.order_id || `#${delivery.id}`}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {delivery.client?.ragione_sociale || 'N/A'}
                      </td>
                      <td className="px-4 py-3">
                        <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border ${statusColors[delivery.status] || 'bg-slate-500/20 text-gray-500 border-gray-300/30'}`}>
                          {statusLabels[delivery.status] || delivery.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-xs text-gray-600">
                        {formatDate(delivery.order_date || delivery.created_at)}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600 max-w-[200px] truncate" title={delivery.delivery_address}>
                        {delivery.delivery_address || '-'}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-1">
                          <Euro className="h-3 w-3 text-green-400" />
                          <span className="font-semibold text-green-400 text-sm">
                            {formatCurrency(delivery.order_amount)}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {formatCurrency(delivery.delivery_fee_total)}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {delivery.customer_name || '-'}
                      </td>
                      <td className="px-4 py-3 text-xs text-gray-500">
                        {delivery.payment_method || '-'}
                      </td>
                      <td className="px-4 py-3 text-center">
                        {delivery.is_invoiced ? (
                          <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                            Sì
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-slate-500/20 text-gray-500 border border-gray-300/30">
                            No
                          </span>
                        )}
                      </td>
                    </motion.tr>
                  ))}
                </tbody>
              </table>
            </div>
            {renderPaginationControls()}
          </>
        )}
      </div>
    </div>
  )
}
