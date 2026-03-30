import { useState, useEffect, useCallback, Fragment } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import {
  Euro,
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  Bell,
  Plus,
  Edit,
  Trash2,
  Search,
  RefreshCw,
  CreditCard,
  PiggyBank,
  Calendar,
  Key,
  CheckCircle,
  XCircle,
  Clock,
  X,
  RotateCcw,
} from 'lucide-react'
import { Doughnut, Bar } from 'react-chartjs-2'
import { Dialog, Transition } from '@headlessui/react'
import { financialEntriesApi, accountingCategoriesApi } from '../utils/api'

// Types
interface FinancialEntry {
  id: number
  category_id: number | null
  entry_type: string
  description: string
  amount: number
  paid_amount: number
  date: string
  due_date: string | null
  is_recurring: boolean
  recurring_interval: string | null
  next_renewal_date: string | null
  vendor_name: string | null
  notes: string | null
  status: string
  category?: { id: number; name: string; slug: string; color: string; icon: string | null; parent_id: number | null }
  remaining_amount: number
  is_overdue: boolean
  entry_type_label: string
}

interface Category {
  id: number
  name: string
  slug: string
  type: string
  color: string
  icon: string | null
  parent_id: number | null
  children?: Category[]
}

interface SummaryData {
  totals_by_type: {
    costi_fissi: number
    costi_variabili: number
    entrate_fisse: number
    entrate_variabili: number
    debiti: number
    debiti_remaining: number
    crediti: number
    crediti_remaining: number
  }
  total_costs: number
  total_incomes: number
  net_balance: number
  by_category: Array<{ category_name: string; color: string; total: number; count: number }>
  renewals: FinancialEntry[]
  overdue: FinancialEntry[]
  monthly_trend: Array<{ month: string; costs: number; incomes: number }>
}

const ENTRY_TYPES = [
  { value: 'costo_fisso', label: 'Costo Fisso', color: 'text-red-400 bg-red-500/10' },
  { value: 'costo_variabile', label: 'Costo Variabile', color: 'text-orange-400 bg-orange-500/10' },
  { value: 'entrata_fissa', label: 'Entrata Fissa', color: 'text-green-400 bg-green-500/10' },
  { value: 'entrata_variabile', label: 'Entrata Variabile', color: 'text-cyan-400 bg-cyan-500/10' },
  { value: 'debito', label: 'Debito', color: 'text-red-500 bg-red-600/10' },
  { value: 'credito', label: 'Credito', color: 'text-yellow-400 bg-yellow-500/10' },
]

const formatCurrency = (amount: number) =>
  new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(amount)

const formatDate = (dateStr: string) =>
  new Date(dateStr).toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' })

const getEntryTypeInfo = (type: string) => ENTRY_TYPES.find(t => t.value === type) || ENTRY_TYPES[0]

const emptyForm = {
  category_id: '' as string | number,
  entry_type: 'costo_fisso',
  description: '',
  amount: '',
  paid_amount: '',
  date: new Date().toISOString().split('T')[0],
  due_date: '',
  is_recurring: false,
  recurring_interval: '',
  next_renewal_date: '',
  vendor_name: '',
  notes: '',
  status: 'active',
}

export default function FinancialManagement() {
  const [summary, setSummary] = useState<SummaryData | null>(null)
  const [entries, setEntries] = useState<FinancialEntry[]>([])
  const [categories, setCategories] = useState<Category[]>([])
  const [loading, setLoading] = useState(true)
  const [entriesLoading, setEntriesLoading] = useState(false)

  // Filters
  const [filterType, setFilterType] = useState('')
  const [filterStatus, setFilterStatus] = useState('')
  const [searchTerm, setSearchTerm] = useState('')

  // Modal
  const [showModal, setShowModal] = useState(false)
  const [editingEntry, setEditingEntry] = useState<FinancialEntry | null>(null)
  const [form, setForm] = useState(emptyForm)
  const [saving, setSaving] = useState(false)

  // Delete confirm
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)
  const [entryToDelete, setEntryToDelete] = useState<FinancialEntry | null>(null)

  const loadSummary = useCallback(async () => {
    try {
      const res = await financialEntriesApi.summary()
      setSummary(res.data)
    } catch (err) {
      console.error('Error loading summary:', err)
    }
  }, [])

  const loadEntries = useCallback(async () => {
    setEntriesLoading(true)
    try {
      const params: any = { per_page: 50 }
      if (filterType) params.entry_type = filterType
      if (filterStatus) params.status = filterStatus
      if (searchTerm) params.search = searchTerm
      const res = await financialEntriesApi.getAll(params)
      setEntries(res.data.data)
    } catch (err) {
      console.error('Error loading entries:', err)
    } finally {
      setEntriesLoading(false)
    }
  }, [filterType, filterStatus, searchTerm])

  const loadCategories = useCallback(async () => {
    try {
      const res = await accountingCategoriesApi.getAll()
      setCategories(res.data)
    } catch (err) {
      console.error('Error loading categories:', err)
    }
  }, [])

  useEffect(() => {
    Promise.all([loadSummary(), loadEntries(), loadCategories()]).finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    loadEntries()
  }, [filterType, filterStatus, searchTerm])

  const openCreateModal = () => {
    setEditingEntry(null)
    setForm(emptyForm)
    setShowModal(true)
  }

  const openEditModal = (entry: FinancialEntry) => {
    setEditingEntry(entry)
    setForm({
      category_id: entry.category_id || '',
      entry_type: entry.entry_type,
      description: entry.description,
      amount: String(entry.amount),
      paid_amount: String(entry.paid_amount),
      date: entry.date?.split('T')[0] || '',
      due_date: entry.due_date?.split('T')[0] || '',
      is_recurring: entry.is_recurring,
      recurring_interval: entry.recurring_interval || '',
      next_renewal_date: entry.next_renewal_date?.split('T')[0] || '',
      vendor_name: entry.vendor_name || '',
      notes: entry.notes || '',
      status: entry.status,
    })
    setShowModal(true)
  }

  const handleSave = async () => {
    setSaving(true)
    try {
      const data: any = {
        ...form,
        amount: parseFloat(form.amount as string) || 0,
        paid_amount: parseFloat(form.paid_amount as string) || 0,
        category_id: form.category_id || null,
        due_date: form.due_date || null,
        recurring_interval: form.recurring_interval || null,
        next_renewal_date: form.next_renewal_date || null,
        vendor_name: form.vendor_name || null,
        notes: form.notes || null,
      }
      if (editingEntry) {
        await financialEntriesApi.update(editingEntry.id, data)
      } else {
        await financialEntriesApi.create(data)
      }
      setShowModal(false)
      await Promise.all([loadSummary(), loadEntries()])
    } catch (err) {
      console.error('Error saving entry:', err)
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = async () => {
    if (!entryToDelete) return
    try {
      await financialEntriesApi.delete(entryToDelete.id)
      setShowDeleteConfirm(false)
      setEntryToDelete(null)
      await Promise.all([loadSummary(), loadEntries()])
    } catch (err) {
      console.error('Error deleting entry:', err)
    }
  }

  // Group categories by parent for dropdown
  const parentCategories = categories.filter(c => !c.parent_id && c.slug?.startsWith('fin-'))
  const getCategoryChildren = (parentId: number) => categories.filter(c => c.parent_id === parentId)

  // Chart data
  const categoryChartData = {
    labels: summary?.by_category?.slice(0, 8).map(c => c.category_name) || [],
    datasets: [{
      data: summary?.by_category?.slice(0, 8).map(c => c.total) || [],
      backgroundColor: summary?.by_category?.slice(0, 8).map(c => c.color + '80') || [],
      borderColor: summary?.by_category?.slice(0, 8).map(c => c.color) || [],
      borderWidth: 1,
    }],
  }

  const trendChartData = {
    labels: summary?.monthly_trend?.map(m => m.month) || [],
    datasets: [
      {
        label: 'Costi',
        data: summary?.monthly_trend?.map(m => m.costs) || [],
        backgroundColor: 'rgba(239, 68, 68, 0.7)',
        borderRadius: 4,
      },
      {
        label: 'Entrate',
        data: summary?.monthly_trend?.map(m => m.incomes) || [],
        backgroundColor: 'rgba(34, 197, 94, 0.7)',
        borderRadius: 4,
      },
    ],
  }

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: 'rgba(255,255,255,0.6)' } },
      y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: 'rgba(255,255,255,0.6)' }, beginAtZero: true },
    },
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <RefreshCw className="w-8 h-8 text-purple-400 animate-spin" />
      </div>
    )
  }

  const t = summary?.totals_by_type

  return (
    <div className="space-y-6">
      {/* Overview Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {[
          { label: 'Costi Fissi', value: t?.costi_fissi || 0, color: 'text-red-400', icon: CreditCard, bg: 'bg-red-500/10' },
          { label: 'Costi Variabili', value: t?.costi_variabili || 0, color: 'text-orange-400', icon: TrendingDown, bg: 'bg-orange-500/10' },
          { label: 'Entrate Fisse', value: t?.entrate_fisse || 0, color: 'text-green-400', icon: TrendingUp, bg: 'bg-green-500/10' },
          { label: 'Entrate Variabili', value: t?.entrate_variabili || 0, color: 'text-cyan-400', icon: Euro, bg: 'bg-cyan-500/10' },
          { label: 'Debiti residui', value: t?.debiti_remaining || 0, color: 'text-red-500', icon: AlertTriangle, bg: 'bg-red-600/10' },
          { label: 'Crediti residui', value: t?.crediti_remaining || 0, color: 'text-yellow-400', icon: PiggyBank, bg: 'bg-yellow-500/10' },
        ].map((card, i) => (
          <motion.div
            key={card.label}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.05 }}
            className="glass-card p-4"
          >
            <div className="flex items-center gap-2 mb-2">
              <div className={`p-1.5 rounded-lg ${card.bg}`}>
                <card.icon className={`w-4 h-4 ${card.color}`} />
              </div>
              <span className="text-xs text-gray-500">{card.label}</span>
            </div>
            <div className={`text-xl font-bold ${card.color}`}>
              {formatCurrency(card.value)}
            </div>
          </motion.div>
        ))}
      </div>

      {/* Net Balance */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.3 }}
        className={`glass-card p-4 border ${(summary?.net_balance || 0) >= 0 ? 'border-green-500/20' : 'border-red-500/20'}`}
      >
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Euro className={`w-6 h-6 ${(summary?.net_balance || 0) >= 0 ? 'text-green-400' : 'text-red-400'}`} />
            <div>
              <span className="text-sm text-gray-500">Bilancio Netto (Entrate - Costi)</span>
              <div className={`text-2xl font-bold ${(summary?.net_balance || 0) >= 0 ? 'text-green-400' : 'text-red-400'}`}>
                {formatCurrency(summary?.net_balance || 0)}
              </div>
            </div>
          </div>
          <div className="text-right text-sm text-gray-500">
            <div>Entrate: <span className="text-green-400">{formatCurrency(summary?.total_incomes || 0)}</span></div>
            <div>Costi: <span className="text-red-400">{formatCurrency(summary?.total_costs || 0)}</span></div>
          </div>
        </div>
      </motion.div>

      {/* Alerts: Renewals + Overdue */}
      {((summary?.renewals?.length || 0) > 0 || (summary?.overdue?.length || 0) > 0) && (
        <div className="space-y-3">
          {/* Renewals */}
          {(summary?.renewals || []).map((renewal, i) => {
            const daysUntil = renewal.next_renewal_date
              ? Math.ceil((new Date(renewal.next_renewal_date).getTime() - Date.now()) / (1000 * 60 * 60 * 24))
              : 0
            const urgencyColor = daysUntil <= 7 ? 'border-red-500/30 bg-red-500/10' : daysUntil <= 14 ? 'border-yellow-500/30 bg-yellow-500/10' : 'border-primary-500/30 bg-primary-500/10'
            const textColor = daysUntil <= 7 ? 'text-red-400' : daysUntil <= 14 ? 'text-yellow-400' : 'text-primary-400'
            return (
              <motion.div
                key={`renewal-${renewal.id}`}
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: i * 0.05 }}
                className={`p-3 rounded-xl border ${urgencyColor} flex items-center gap-3`}
              >
                <Key className={`w-5 h-5 ${textColor} flex-shrink-0`} />
                <div className="flex-1 min-w-0">
                  <span className="text-sm text-white font-medium">{renewal.description}</span>
                  {renewal.vendor_name && <span className="text-xs text-gray-500 ml-2">({renewal.vendor_name})</span>}
                </div>
                <span className={`text-xs font-medium ${textColor} whitespace-nowrap`}>
                  {daysUntil <= 0 ? 'Scaduto!' : `${daysUntil}gg`}
                </span>
                <span className="text-sm font-medium text-white">{formatCurrency(renewal.amount)}</span>
              </motion.div>
            )
          })}
          {/* Overdue */}
          {(summary?.overdue || []).map((item, i) => (
            <motion.div
              key={`overdue-${item.id}`}
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              className="p-3 rounded-xl border border-red-500/30 bg-red-500/10 flex items-center gap-3"
            >
              <AlertTriangle className="w-5 h-5 text-red-400 flex-shrink-0" />
              <div className="flex-1 min-w-0">
                <span className="text-sm text-white font-medium">{item.description}</span>
                <span className="text-xs text-red-400 ml-2">Scaduto il {formatDate(item.due_date!)}</span>
              </div>
              <span className="text-sm font-medium text-red-400">{formatCurrency(item.remaining_amount)}</span>
            </motion.div>
          ))}
        </div>
      )}

      {/* Charts */}
      {(summary?.by_category?.length || 0) > 0 && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Category Breakdown */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            className="glass-card p-6"
          >
            <h3 className="text-sm font-semibold text-gray-600 mb-4">Spese per Categoria</h3>
            <div className="h-64">
              <Doughnut data={categoryChartData} options={{ responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' as const, labels: { color: 'rgba(255,255,255,0.7)', font: { size: 11 } } } } }} />
            </div>
          </motion.div>

          {/* Monthly Trend */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.5 }}
            className="glass-card p-6"
          >
            <h3 className="text-sm font-semibold text-gray-600 mb-4">Trend Mensile</h3>
            <div className="flex items-center gap-4 mb-3 text-xs">
              <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-red-500" /> Costi</span>
              <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-green-500" /> Entrate</span>
            </div>
            <div className="h-56">
              <Bar data={trendChartData} options={chartOptions} />
            </div>
          </motion.div>
        </div>
      )}

      {/* Entries Table Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 className="text-lg font-semibold text-white">Voci Finanziarie</h2>
        <button
          onClick={openCreateModal}
          className="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-xl text-white text-sm font-medium transition-colors"
        >
          <Plus className="w-4 h-4" />
          Nuova Voce
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <select
          value={filterType}
          onChange={(e) => setFilterType(e.target.value)}
          className="glass-input px-3 py-2 text-sm rounded-lg"
        >
          <option value="">Tutti i tipi</option>
          {ENTRY_TYPES.map(t => (
            <option key={t.value} value={t.value}>{t.label}</option>
          ))}
        </select>
        <select
          value={filterStatus}
          onChange={(e) => setFilterStatus(e.target.value)}
          className="glass-input px-3 py-2 text-sm rounded-lg"
        >
          <option value="">Tutti gli stati</option>
          <option value="active">Attivo</option>
          <option value="paid">Pagato</option>
          <option value="overdue">Scaduto</option>
          <option value="cancelled">Annullato</option>
        </select>
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Cerca per descrizione, fornitore..."
            className="glass-input pl-10 pr-4 py-2 w-full text-sm rounded-lg"
          />
        </div>
      </div>

      {/* Table */}
      <div className="glass-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Data</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Descrizione</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Fornitore</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Categoria</th>
                <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Importo</th>
                <th className="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Status</th>
                <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Azioni</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {entriesLoading ? (
                <tr><td colSpan={8} className="px-4 py-8 text-center text-gray-500"><RefreshCw className="w-5 h-5 animate-spin inline mr-2" />Caricamento...</td></tr>
              ) : entries.length === 0 ? (
                <tr><td colSpan={8} className="px-4 py-8 text-center text-gray-400">Nessuna voce trovata</td></tr>
              ) : entries.map((entry) => {
                const typeInfo = getEntryTypeInfo(entry.entry_type)
                return (
                  <tr key={entry.id} className="hover:bg-white/5 transition-colors">
                    <td className="px-4 py-3 text-sm text-gray-600">{formatDate(entry.date)}</td>
                    <td className="px-4 py-3">
                      <span className={`px-2 py-0.5 rounded text-xs font-medium ${typeInfo.color}`}>
                        {typeInfo.label}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="text-sm text-white font-medium">{entry.description}</div>
                      {entry.is_recurring && (
                        <span className="text-xs text-purple-400 flex items-center gap-1 mt-0.5">
                          <RotateCcw className="w-3 h-3" />
                          {entry.recurring_interval === 'monthly' ? 'Mensile' : entry.recurring_interval === 'quarterly' ? 'Trimestrale' : 'Annuale'}
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{entry.vendor_name || '-'}</td>
                    <td className="px-4 py-3 hidden lg:table-cell">
                      {entry.category ? (
                        <span className="text-xs px-2 py-0.5 rounded" style={{ backgroundColor: entry.category.color + '20', color: entry.category.color }}>
                          {entry.category.name}
                        </span>
                      ) : <span className="text-xs text-gray-400">-</span>}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <span className="text-sm font-medium text-white">{formatCurrency(entry.amount)}</span>
                      {(entry.entry_type === 'debito' || entry.entry_type === 'credito') && entry.paid_amount > 0 && (
                        <div className="text-xs text-gray-500">
                          Pagato: {formatCurrency(entry.paid_amount)}
                        </div>
                      )}
                    </td>
                    <td className="px-4 py-3 text-center hidden sm:table-cell">
                      <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                        entry.status === 'active' ? 'text-green-400 bg-green-500/10' :
                        entry.status === 'paid' ? 'text-primary-400 bg-primary-500/10' :
                        entry.status === 'overdue' ? 'text-red-400 bg-red-500/10' :
                        'text-gray-500 bg-slate-500/10'
                      }`}>
                        {entry.status === 'active' ? 'Attivo' : entry.status === 'paid' ? 'Pagato' : entry.status === 'overdue' ? 'Scaduto' : 'Annullato'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button onClick={() => openEditModal(entry)} className="p-1.5 text-gray-500 hover:text-white hover:bg-white/10 rounded-lg transition-all" title="Modifica">
                          <Edit className="w-4 h-4" />
                        </button>
                        <button onClick={() => { setEntryToDelete(entry); setShowDeleteConfirm(true) }} className="p-1.5 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all" title="Elimina">
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
      </div>

      {/* Create/Edit Modal */}
      <Transition appear show={showModal} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setShowModal(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-300" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-200" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-300" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-200" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-lg bg-gray-900 border border-gray-200 rounded-2xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                  <Dialog.Title className="text-lg font-semibold text-white mb-4">
                    {editingEntry ? 'Modifica Voce' : 'Nuova Voce Finanziaria'}
                  </Dialog.Title>

                  <div className="space-y-4">
                    {/* Entry Type */}
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">Tipo *</label>
                      <select value={form.entry_type} onChange={(e) => setForm({ ...form, entry_type: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg">
                        {ENTRY_TYPES.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
                      </select>
                    </div>

                    {/* Description */}
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">Descrizione *</label>
                      <input type="text" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" placeholder="es. Abbonamento ChatGPT" />
                    </div>

                    {/* Amount + Date row */}
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs text-gray-500 mb-1">Importo *</label>
                        <input type="number" step="0.01" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" placeholder="0.00" />
                      </div>
                      <div>
                        <label className="block text-xs text-gray-500 mb-1">Data *</label>
                        <input type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" />
                      </div>
                    </div>

                    {/* Paid Amount (debito/credito only) */}
                    {(form.entry_type === 'debito' || form.entry_type === 'credito') && (
                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs text-gray-500 mb-1">Importo pagato</label>
                          <input type="number" step="0.01" value={form.paid_amount} onChange={(e) => setForm({ ...form, paid_amount: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" placeholder="0.00" />
                        </div>
                        <div>
                          <label className="block text-xs text-gray-500 mb-1">Data scadenza</label>
                          <input type="date" value={form.due_date} onChange={(e) => setForm({ ...form, due_date: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" />
                        </div>
                      </div>
                    )}

                    {/* Vendor + Category */}
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs text-gray-500 mb-1">Fornitore</label>
                        <input type="text" value={form.vendor_name} onChange={(e) => setForm({ ...form, vendor_name: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" placeholder="es. OpenAI" />
                      </div>
                      <div>
                        <label className="block text-xs text-gray-500 mb-1">Categoria</label>
                        <select value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg">
                          <option value="">Nessuna</option>
                          {parentCategories.map(parent => (
                            <optgroup key={parent.id} label={parent.name}>
                              <option value={parent.id}>{parent.name} (generale)</option>
                              {getCategoryChildren(parent.id).map(child => (
                                <option key={child.id} value={child.id}>{child.name}</option>
                              ))}
                            </optgroup>
                          ))}
                        </select>
                      </div>
                    </div>

                    {/* Recurring toggle */}
                    <div className="flex items-center gap-3">
                      <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={form.is_recurring} onChange={(e) => setForm({ ...form, is_recurring: e.target.checked })} className="w-4 h-4 rounded border-white/20 bg-white/5 text-purple-500 focus:ring-purple-500" />
                        <span className="text-sm text-gray-600">Ricorrente</span>
                      </label>
                    </div>

                    {/* Recurring fields */}
                    {form.is_recurring && (
                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs text-gray-500 mb-1">Intervallo</label>
                          <select value={form.recurring_interval} onChange={(e) => setForm({ ...form, recurring_interval: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg">
                            <option value="">Seleziona</option>
                            <option value="monthly">Mensile</option>
                            <option value="quarterly">Trimestrale</option>
                            <option value="yearly">Annuale</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-xs text-gray-500 mb-1">Prossimo rinnovo</label>
                          <input type="date" value={form.next_renewal_date} onChange={(e) => setForm({ ...form, next_renewal_date: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" />
                        </div>
                      </div>
                    )}

                    {/* Status */}
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">Stato</label>
                      <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg">
                        <option value="active">Attivo</option>
                        <option value="paid">Pagato</option>
                        <option value="overdue">Scaduto</option>
                        <option value="cancelled">Annullato</option>
                      </select>
                    </div>

                    {/* Notes */}
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">Note</label>
                      <textarea value={form.notes || ''} onChange={(e) => setForm({ ...form, notes: e.target.value })} className="glass-input w-full px-3 py-2 text-sm rounded-lg" rows={2} placeholder="Note aggiuntive..." />
                    </div>
                  </div>

                  <div className="flex justify-end gap-3 mt-6">
                    <button onClick={() => setShowModal(false)} className="px-4 py-2 bg-white/5 text-gray-600 rounded-xl border border-gray-200 hover:bg-white/10 transition-all text-sm">
                      Annulla
                    </button>
                    <button onClick={handleSave} disabled={saving || !form.description || !form.amount} className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-all text-sm font-medium disabled:opacity-50">
                      {saving ? 'Salvataggio...' : editingEntry ? 'Aggiorna' : 'Crea'}
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Delete Confirm Dialog */}
      <Transition appear show={showDeleteConfirm} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setShowDeleteConfirm(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-300" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-200" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-300" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-200" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md bg-gray-900 border border-gray-200 rounded-2xl p-6 shadow-xl">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="p-2 bg-red-500/20 rounded-xl">
                      <AlertTriangle className="w-6 h-6 text-red-400" />
                    </div>
                    <Dialog.Title className="text-lg font-semibold text-white">
                      Elimina voce
                    </Dialog.Title>
                  </div>
                  <p className="text-gray-600 text-sm mb-6">
                    Stai per eliminare <strong className="text-white">{entryToDelete?.description}</strong>. Questa azione sposterà la voce nel cestino.
                  </p>
                  <div className="flex justify-end gap-3">
                    <button onClick={() => setShowDeleteConfirm(false)} className="px-4 py-2 bg-white/5 text-gray-600 rounded-xl border border-gray-200 hover:bg-white/10 transition-all text-sm">
                      Annulla
                    </button>
                    <button onClick={handleDelete} className="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl border border-red-500/30 hover:bg-red-500/30 transition-all text-sm font-medium">
                      Elimina
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </div>
  )
}
