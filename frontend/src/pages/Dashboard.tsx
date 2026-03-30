import { motion } from 'framer-motion'
import { useEffect, useState, useCallback } from 'react'
import {
  TrendingUp,
  TrendingDown,
  Users,
  Package,
  Bike,
  Clock,
  MapPin,
  CheckCircle,
  AlertCircle,
  RefreshCw,
  Calendar,
  Euro,
  Store,
  Timer,
  Activity,
  Truck,
  Circle,
  FileText,
  CreditCard,
  PiggyBank,
  AlertTriangle,
  Building2,
  Target
} from 'lucide-react'
import api from '../utils/api'
import FinancialManagement from '../components/FinancialManagement'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { Line, Bar, Doughnut } from 'react-chartjs-2'

// Registrazione componenti Chart.js
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface DeliveryOpsData {
  timestamp: string
  today: {
    total: number
    completed: number
    in_progress: number
    pending: number
    cancelled: number
    completion_rate: number
  }
  revenue: {
    delivery_fees: number
    order_amounts: number
    oppla_fees: number
    distance_fees: number
    total_km: number
  }
  weekly_trend: Array<{
    date: string
    day_name: string
    count: number
    revenue: number
  }>
  top_restaurants: Array<{
    name: string
    orders: number
    fees: number
  }>
  hourly_distribution: Array<{
    hour: string
    count: number
  }>
  avg_times: {
    pickup: number
    delivery: number
    total: number
  }
  comparison: {
    deliveries_diff: number
    deliveries_diff_percent: number
    revenue_diff: number
    revenue_diff_percent: number
  }
  riders: {
    total: number
    available: number
    busy: number
    offline: number
    agents: Array<{
      fleet_id: string
      name: string
      status: 'available' | 'busy' | 'offline'
      transport_type: string
    }>
    last_synced_at?: string
    error?: string
  }
  tookan_tasks: {
    total: number
    assigned: number
    started: number
    successful: number
    failed: number
    in_progress: number
    unassigned: number
    error?: string
  }
}

interface UnifiedDashboardData {
  period: {
    start: string
    end: string
    days: number
  }
  summary: {
    net_income: number
    total_revenue: number
    total_costs: number
    revenue_growth: number
    comparison: {
      previous_revenue: number
      previous_costs: number
    }
  }
  revenue: {
    total_revenue: number
    total_invoices: number
    average_invoice: number
    growth_percentage: number
  }
  clients: {
    total_active: number
    new_clients: number
    clients_with_revenue: number
    avg_revenue_per_client: number
    by_type: {
      partner: number
      extra: number
      consumer: number
    }
  }
  operations: {
    deliveries: {
      total: number
      completed: number
      total_km: number
      total_revenue: number
    }
    pos_orders: {
      total: number
      total_amount: number
      total_fees: number
      avg_order_value: number
    }
  }
  receivables: {
    total_outstanding: number
    total_invoices: number
    overdue_amount: number
    overdue_count: number
  }
  payables: {
    total_outstanding: number
    total_invoices: number
    overdue_amount: number
    overdue_count: number
    due_this_month: number
  }
  cash_flow: {
    cash_in: number
    cash_out: number
    net_cash_flow: number
    current_balance: number
    forecast: {
      '30_days': { expected_in: number; expected_out: number; net: number }
      '60_days': { expected_in: number; expected_out: number; net: number }
    }
  }
  alerts: Array<{
    type: 'warning' | 'danger' | 'info'
    message: string
    count?: number
    amount?: number
  }>
}

type TabType = 'operations' | 'financial' | 'financial-management'

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(amount)
}

const formatTime = (minutes: number) => {
  if (minutes < 60) return `${minutes} min`
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${hours}h ${mins}m`
}

export default function Dashboard() {
  const [deliveryOps, setDeliveryOps] = useState<DeliveryOpsData | null>(null)
  const [unifiedData, setUnifiedData] = useState<UnifiedDashboardData | null>(null)
  const [loading, setLoading] = useState(true)
  const [lastUpdate, setLastUpdate] = useState<Date | null>(null)
  const [autoRefresh, setAutoRefresh] = useState(true)
  const [activeTab, setActiveTab] = useState<TabType>('operations')
  const [financialPeriod, setFinancialPeriod] = useState('month')
  const loadDeliveryOps = useCallback(async () => {
    try {
      const response = await api.get('/dashboard/delivery-ops')
      setDeliveryOps(response.data)
      setLastUpdate(new Date())
    } catch (error) {
      console.error('Error loading delivery ops:', error)
    } finally {
      setLoading(false)
    }
  }, [])

  const loadUnifiedDashboard = useCallback(async () => {
    try {
      const response = await api.get('/dashboard/unified', {
        params: { period: financialPeriod }
      })
      setUnifiedData(response.data)
    } catch (error) {
      console.error('Error loading unified dashboard:', error)
    }
  }, [financialPeriod])

  // Funzione per aggiornare manualmente i dati (refresh locale, non sync da OPPLA)
  const handleRefresh = async () => {
    setLoading(true)
    try {
      await Promise.all([loadDeliveryOps(), loadUnifiedDashboard()])
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadDeliveryOps()
    loadUnifiedDashboard()

    // Auto-refresh ogni 15 secondi se abilitato (per dati real-time)
    let interval: NodeJS.Timeout | null = null
    if (autoRefresh) {
      interval = setInterval(() => {
        loadDeliveryOps()
        if (activeTab === 'financial') loadUnifiedDashboard()
      }, 15000)
    }

    return () => {
      if (interval) clearInterval(interval)
    }
  }, [loadDeliveryOps, loadUnifiedDashboard, autoRefresh, activeTab])

  // Reload financial data when period changes
  useEffect(() => {
    if (activeTab === 'financial') {
      loadUnifiedDashboard()
    }
  }, [financialPeriod, activeTab, loadUnifiedDashboard])

  // Chart data per trend settimanale
  const weeklyChartData = {
    labels: deliveryOps?.weekly_trend.map(d => d.day_name) || [],
    datasets: [
      {
        label: 'Consegne',
        data: deliveryOps?.weekly_trend.map(d => d.count) || [],
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4,
      }
    ]
  }

  // Chart data per distribuzione oraria
  const hourlyChartData = {
    labels: deliveryOps?.hourly_distribution.map(d => d.hour) || [],
    datasets: [
      {
        label: 'Consegne',
        data: deliveryOps?.hourly_distribution.map(d => d.count) || [],
        backgroundColor: 'rgba(59, 130, 246, 0.7)',
        borderRadius: 4,
      }
    ]
  }

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
    },
    scales: {
      x: {
        grid: { color: 'rgba(0,0,0,0.06)' },
        ticks: { color: 'rgba(0,0,0,0.5)' },
      },
      y: {
        grid: { color: 'rgba(0,0,0,0.06)' },
        ticks: { color: 'rgba(0,0,0,0.5)' },
        beginAtZero: true,
      },
    },
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="flex items-center gap-3 text-gray-500">
          <RefreshCw className="w-6 h-6 animate-spin" />
          <span>Caricamento dashboard operativa...</span>
        </div>
      </div>
    )
  }

  const today = deliveryOps?.today
  const revenue = deliveryOps?.revenue
  const comparison = deliveryOps?.comparison
  const riders = deliveryOps?.riders
  const avgTimes = deliveryOps?.avg_times

  // Financial KPI data
  const summary = unifiedData?.summary
  const clients = unifiedData?.clients
  const receivables = unifiedData?.receivables
  const payables = unifiedData?.payables
  const cashFlow = unifiedData?.cash_flow
  const alerts = unifiedData?.alerts || []

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <div className="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
              <Target className="w-5 h-5 text-primary-600" />
            </div>
            Dashboard OPPLA
          </h1>
          <p className="text-gray-500 text-sm mt-1">
            {activeTab === 'operations' ? 'Monitoraggio consegne in tempo reale' :
             activeTab === 'financial' ? 'KPI finanziari e business intelligence' :
             'Gestione costi, entrate, debiti e crediti'}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          {activeTab === 'financial' && (
            <select
              value={financialPeriod}
              onChange={(e) => setFinancialPeriod(e.target.value)}
              className="glass-input w-auto"
            >
              <option value="today">Oggi</option>
              <option value="week">Questa Settimana</option>
              <option value="month">Questo Mese</option>
              <option value="quarter">Questo Trimestre</option>
              <option value="year">Quest'Anno</option>
            </select>
          )}
          <button
            onClick={handleRefresh}
            disabled={loading}
            className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 disabled:cursor-not-allowed rounded-lg text-white text-sm font-medium transition-colors"
          >
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            {loading ? 'Aggiornamento...' : 'Aggiorna'}
          </button>
          <button
            onClick={() => setAutoRefresh(!autoRefresh)}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border ${
              autoRefresh
                ? 'bg-green-50 text-green-700 border-green-200'
                : 'bg-white text-gray-500 border-gray-300'
            }`}
          >
            <RefreshCw className={`w-4 h-4 ${autoRefresh ? 'animate-spin' : ''}`} />
            {autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF'}
          </button>
          {lastUpdate && (
            <span className="text-xs text-gray-400">
              Ultimo aggiornamento: {lastUpdate.toLocaleTimeString('it-IT')}
            </span>
          )}
        </div>
      </div>

      {/* Tabs */}
      <div className="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
      <div className="flex gap-2 border-b border-gray-200 min-w-max">
        <button
          onClick={() => setActiveTab('operations')}
          className={`flex items-center gap-2 px-4 py-3 -mb-px text-sm font-medium transition-colors ${
            activeTab === 'operations'
              ? 'text-primary-600 border-b-2 border-primary-600'
              : 'text-gray-500 hover:text-gray-900'
          }`}
        >
          <Truck className="w-4 h-4" />
          Operazioni Consegne
        </button>
        <button
          onClick={() => setActiveTab('financial')}
          className={`flex items-center gap-2 px-4 py-3 -mb-px text-sm font-medium transition-colors ${
            activeTab === 'financial'
              ? 'text-primary-600 border-b-2 border-primary-600'
              : 'text-gray-500 hover:text-gray-900'
          }`}
        >
          <Euro className="w-4 h-4" />
          Dashboard Finanziaria
        </button>
        <button
          onClick={() => setActiveTab('financial-management')}
          className={`flex items-center gap-2 px-4 py-3 -mb-px text-sm font-medium transition-colors ${
            activeTab === 'financial-management'
              ? 'text-primary-600 border-b-2 border-primary-600'
              : 'text-gray-500 hover:text-gray-900'
          }`}
        >
          <PiggyBank className="w-4 h-4" />
          Gestione Finanziaria
        </button>
      </div>
      </div>

      {/* Operations Tab Content */}
      {activeTab === 'operations' && (
        <>
          {/* KPI Cards - Riga principale */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        {/* Consegne Oggi */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card p-4"
        >
          <div className="flex items-center gap-2 mb-2">
            <div className="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
              <Package className="w-4 h-4 text-primary-600" />
            </div>
            <span className="text-xs text-gray-500">Consegne Oggi</span>
          </div>
          <div className="text-2xl font-bold text-gray-900">{today?.total || 0}</div>
          <div className="flex items-center gap-1 mt-1">
            {comparison && comparison.deliveries_diff >= 0 ? (
              <TrendingUp className="w-3 h-3 text-green-600" />
            ) : (
              <TrendingDown className="w-3 h-3 text-red-600" />
            )}
            <span className={`text-xs ${comparison && comparison.deliveries_diff >= 0 ? 'text-green-600' : 'text-red-600'}`}>
              {comparison?.deliveries_diff_percent || 0}% vs ieri
            </span>
          </div>
        </motion.div>

        {/* In Corso */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.05 }}
          className="glass-card p-4"
        >
          <div className="flex items-center gap-2 mb-2">
            <div className="w-8 h-8 bg-yellow-50 rounded-lg flex items-center justify-center">
              <Activity className="w-4 h-4 text-yellow-600" />
            </div>
            <span className="text-xs text-gray-500">In Corso</span>
          </div>
          <div className="text-2xl font-bold text-yellow-600">{today?.in_progress || 0}</div>
          <div className="text-xs text-gray-400 mt-1">
            {today?.pending || 0} in attesa
          </div>
        </motion.div>

        {/* Completate */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="glass-card p-4"
        >
          <div className="flex items-center gap-2 mb-2">
            <div className="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-4 h-4 text-green-600" />
            </div>
            <span className="text-xs text-gray-500">Completate</span>
          </div>
          <div className="text-2xl font-bold text-green-600">{today?.completed || 0}</div>
          <div className="text-xs text-gray-400 mt-1">
            {today?.completion_rate || 0}% success rate
          </div>
        </motion.div>

        {/* Rider Attivi */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.15 }}
          className="glass-card p-4"
        >
          <div className="flex items-center gap-2 mb-2">
            <div className="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
              <Bike className="w-4 h-4 text-purple-600" />
            </div>
            <span className="text-xs text-gray-500">Rider</span>
          </div>
          <div className="text-2xl font-bold text-gray-900">
            {riders?.available || 0}
            <span className="text-sm text-gray-400 font-normal">/{riders?.total || 0}</span>
          </div>
          <div className="flex items-center gap-2 mt-1">
            <span className="flex items-center gap-1 text-xs text-gray-500">
              <Circle className="w-2 h-2 fill-green-500 text-green-500" />
              {riders?.available || 0}
            </span>
            <span className="flex items-center gap-1 text-xs text-gray-500">
              <Circle className="w-2 h-2 fill-orange-500 text-orange-500" />
              {riders?.busy || 0}
            </span>
            <span className="flex items-center gap-1 text-xs text-gray-500">
              <Circle className="w-2 h-2 fill-gray-400 text-gray-400" />
              {riders?.offline || 0}
            </span>
          </div>
        </motion.div>

        {/* Revenue Oggi */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="glass-card p-4"
        >
          <div className="flex items-center gap-2 mb-2">
            <div className="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
              <Euro className="w-4 h-4 text-green-600" />
            </div>
            <span className="text-xs text-gray-500">Revenue Oggi</span>
          </div>
          <div className="text-2xl font-bold text-green-600">
            {formatCurrency(revenue?.delivery_fees || 0)}
          </div>
          <div className="flex items-center gap-1 mt-1">
            {comparison && comparison.revenue_diff >= 0 ? (
              <TrendingUp className="w-3 h-3 text-green-600" />
            ) : (
              <TrendingDown className="w-3 h-3 text-red-600" />
            )}
            <span className={`text-xs ${comparison && comparison.revenue_diff >= 0 ? 'text-green-600' : 'text-red-600'}`}>
              {comparison?.revenue_diff_percent || 0}% vs ieri
            </span>
          </div>
        </motion.div>

        {/* Tempo Medio */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.25 }}
          className="glass-card p-4"
        >
          <div className="flex items-center gap-2 mb-2">
            <div className="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
              <Timer className="w-4 h-4 text-primary-600" />
            </div>
            <span className="text-xs text-gray-500">Tempo Medio</span>
          </div>
          <div className="text-2xl font-bold text-gray-900">
            {formatTime(avgTimes?.total || 0)}
          </div>
          <div className="text-xs text-gray-400 mt-1">
            Pickup: {avgTimes?.pickup || 0}' | Delivery: {avgTimes?.delivery || 0}'
          </div>
        </motion.div>
      </div>

      {/* Seconda riga: Rider Status + Top Restaurants */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Rider Status Card */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="glass-card p-6"
        >
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-lg text-gray-900 flex items-center gap-2">
              <Bike className="w-5 h-5 text-purple-600" />
              Stato Rider
              {riders?.error && (
                <span className="text-xs text-red-500 ml-2">(Tookan offline)</span>
              )}
            </h3>
            {riders?.last_synced_at && (
              <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                <Clock className="w-3 h-3 mr-1" />
                {new Date(riders.last_synced_at).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })}
              </span>
            )}
          </div>
          <div className="space-y-2 max-h-64 overflow-y-auto">
            {riders?.agents?.slice(0, 8).map((rider, index) => (
              <div
                key={rider.fleet_id || index}
                className="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg"
              >
                <div className="flex items-center gap-3">
                  <div className={`w-2 h-2 rounded-full ${
                    rider.status === 'available' ? 'bg-green-500' :
                    rider.status === 'busy' ? 'bg-orange-500' : 'bg-gray-400'
                  }`} />
                  <span className="text-sm font-medium text-gray-900">{rider.name || 'Rider'}</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-xs text-gray-500 capitalize">{rider.transport_type}</span>
                  <span className={`text-xs px-2 py-0.5 rounded-full ${
                    rider.status === 'available' ? 'bg-green-100 text-green-700' :
                    rider.status === 'busy' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500'
                  }`}>
                    {rider.status === 'available' ? 'Disponibile' :
                     rider.status === 'busy' ? 'In consegna' : 'Offline'}
                  </span>
                </div>
              </div>
            ))}
            {(!riders?.agents || riders.agents.length === 0) && (
              <div className="text-center text-gray-400 py-4">
                {riders?.error ? 'Impossibile caricare i rider' : 'Nessun rider registrato'}
              </div>
            )}
          </div>
        </motion.div>

        {/* Top Restaurants */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.35 }}
          className="glass-card p-6"
        >
          <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
            <Store className="w-5 h-5 text-orange-500" />
            Top Ristoranti Oggi
          </h3>
          <div className="space-y-2 max-h-64 overflow-y-auto">
            {deliveryOps?.top_restaurants?.map((restaurant, index) => (
              <div
                key={index}
                className="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg"
              >
                <div className="flex items-center gap-3">
                  <span className="text-xs font-bold text-gray-400 w-5">#{index + 1}</span>
                  <span className="text-sm font-medium text-gray-900 truncate max-w-[150px]">{restaurant.name}</span>
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-xs text-gray-500">{restaurant.orders} ordini</span>
                  <span className="text-xs font-semibold text-green-600">
                    {formatCurrency(restaurant.fees)}
                  </span>
                </div>
              </div>
            ))}
            {(!deliveryOps?.top_restaurants || deliveryOps.top_restaurants.length === 0) && (
              <div className="text-center text-gray-400 py-4">
                Nessun ordine oggi
              </div>
            )}
          </div>
        </motion.div>

        {/* Distribuzione Oraria */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="glass-card p-6"
        >
          <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
            <Clock className="w-5 h-5 text-primary-600" />
            Consegne per Ora (Oggi)
          </h3>
          <div className="h-48">
            <Bar data={hourlyChartData} options={chartOptions} />
          </div>
        </motion.div>
      </div>

      {/* Terza riga: Trend Settimanale + Metriche */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Trend Settimanale */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.45 }}
          className="glass-card p-6"
        >
          <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
            <Calendar className="w-5 h-5 text-primary-600" />
            Trend Settimanale
          </h3>
          <div className="h-64">
            <Line data={weeklyChartData} options={chartOptions} />
          </div>
        </motion.div>

        {/* Metriche Dettagliate */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.5 }}
          className="glass-card p-6"
        >
          <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
            <Activity className="w-5 h-5 text-green-600" />
            Metriche Dettagliate Oggi
          </h3>
          <div className="grid grid-cols-2 gap-4">
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="text-xs text-gray-500 mb-1">KM Totali</div>
              <div className="text-xl font-bold text-gray-900">
                {(revenue?.total_km || 0).toFixed(1)} km
              </div>
            </div>
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="text-xs text-gray-500 mb-1">Fee Distanza</div>
              <div className="text-xl font-bold text-primary-600">
                {formatCurrency(revenue?.distance_fees || 0)}
              </div>
            </div>
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="text-xs text-gray-500 mb-1">Fee OPPLA</div>
              <div className="text-xl font-bold text-purple-600">
                {formatCurrency(revenue?.oppla_fees || 0)}
              </div>
            </div>
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="text-xs text-gray-500 mb-1">Valore Ordini</div>
              <div className="text-xl font-bold text-orange-600">
                {formatCurrency(revenue?.order_amounts || 0)}
              </div>
            </div>
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="text-xs text-gray-500 mb-1">Consegne Annullate</div>
              <div className="text-xl font-bold text-red-600">
                {today?.cancelled || 0}
              </div>
            </div>
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="text-xs text-gray-500 mb-1">Media per Consegna</div>
              <div className="text-xl font-bold text-green-600">
                {today?.completed && today.completed > 0
                  ? formatCurrency((revenue?.delivery_fees || 0) / today.completed)
                  : formatCurrency(0)
                }
              </div>
            </div>
          </div>
        </motion.div>
      </div>
        </>
      )}

      {/* Financial Tab Content */}
      {activeTab === 'financial' && (
        <>
          {/* Business Alerts */}
          {alerts.length > 0 && (
            <div className="space-y-2">
              {alerts.slice(0, 3).map((alert, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: index * 0.1 }}
                  className={`p-3 rounded-lg flex items-center gap-3 ${
                    alert.type === 'danger' ? 'bg-red-50 border border-red-200' :
                    alert.type === 'warning' ? 'bg-yellow-50 border border-yellow-200' :
                    'bg-primary-50 border border-primary-200'
                  }`}
                >
                  <AlertTriangle className={`w-5 h-5 ${
                    alert.type === 'danger' ? 'text-red-500' :
                    alert.type === 'warning' ? 'text-yellow-500' : 'text-primary-500'
                  }`} />
                  <span className={`text-sm ${
                    alert.type === 'danger' ? 'text-red-700' :
                    alert.type === 'warning' ? 'text-yellow-700' : 'text-primary-700'
                  }`}>{alert.message}</span>
                  {alert.amount && (
                    <span className={`ml-auto text-sm font-medium ${
                      alert.type === 'danger' ? 'text-red-700' :
                      alert.type === 'warning' ? 'text-yellow-700' : 'text-primary-700'
                    }`}>
                      {formatCurrency(alert.amount)}
                    </span>
                  )}
                </motion.div>
              ))}
            </div>
          )}

          {/* Financial KPI Cards - Prima Riga */}
          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {/* Fatturato */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="glass-card p-4"
            >
              <div className="flex items-center gap-2 mb-2">
                <div className="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                  <Euro className="w-4 h-4 text-green-600" />
                </div>
                <span className="text-xs text-gray-500">Fatturato</span>
              </div>
              <div className="text-2xl font-bold text-green-600">
                {formatCurrency(summary?.total_revenue || 0)}
              </div>
              <div className="flex items-center gap-1 mt-1">
                {(summary?.revenue_growth || 0) >= 0 ? (
                  <TrendingUp className="w-3 h-3 text-green-600" />
                ) : (
                  <TrendingDown className="w-3 h-3 text-red-600" />
                )}
                <span className={`text-xs ${(summary?.revenue_growth || 0) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                  {summary?.revenue_growth || 0}% vs periodo prec.
                </span>
              </div>
            </motion.div>

            {/* Costi */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.05 }}
              className="glass-card p-4"
            >
              <div className="flex items-center gap-2 mb-2">
                <div className="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                  <CreditCard className="w-4 h-4 text-red-600" />
                </div>
                <span className="text-xs text-gray-500">Costi</span>
              </div>
              <div className="text-2xl font-bold text-red-600">
                {formatCurrency(summary?.total_costs || 0)}
              </div>
              <div className="text-xs text-gray-400 mt-1">
                Fatture fornitori
              </div>
            </motion.div>

            {/* Utile Netto */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.1 }}
              className="glass-card p-4"
            >
              <div className="flex items-center gap-2 mb-2">
                <div className="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                  <PiggyBank className="w-4 h-4 text-purple-600" />
                </div>
                <span className="text-xs text-gray-500">Utile Netto</span>
              </div>
              <div className={`text-2xl font-bold ${(summary?.net_income || 0) >= 0 ? 'text-purple-600' : 'text-red-600'}`}>
                {formatCurrency(summary?.net_income || 0)}
              </div>
              <div className="text-xs text-gray-400 mt-1">
                Fatturato - Costi
              </div>
            </motion.div>

            {/* Clienti Attivi */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.15 }}
              className="glass-card p-4"
            >
              <div className="flex items-center gap-2 mb-2">
                <div className="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                  <Users className="w-4 h-4 text-primary-600" />
                </div>
                <span className="text-xs text-gray-500">Clienti Attivi</span>
              </div>
              <div className="text-2xl font-bold text-gray-900">
                {clients?.total_active || 0}
              </div>
              <div className="text-xs text-green-600 mt-1">
                +{clients?.new_clients || 0} nuovi
              </div>
            </motion.div>

            {/* Crediti (Da incassare) */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.2 }}
              className="glass-card p-4"
            >
              <div className="flex items-center gap-2 mb-2">
                <div className="w-8 h-8 bg-yellow-50 rounded-lg flex items-center justify-center">
                  <FileText className="w-4 h-4 text-yellow-600" />
                </div>
                <span className="text-xs text-gray-500">Da Incassare</span>
              </div>
              <div className="text-2xl font-bold text-yellow-600">
                {formatCurrency(receivables?.total_outstanding || 0)}
              </div>
              <div className="text-xs text-gray-400 mt-1">
                {receivables?.total_invoices || 0} fatture
              </div>
            </motion.div>

            {/* Debiti (Da pagare) */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.25 }}
              className="glass-card p-4"
            >
              <div className="flex items-center gap-2 mb-2">
                <div className="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center">
                  <Building2 className="w-4 h-4 text-orange-600" />
                </div>
                <span className="text-xs text-gray-500">Da Pagare</span>
              </div>
              <div className="text-2xl font-bold text-orange-600">
                {formatCurrency(payables?.total_outstanding || 0)}
              </div>
              <div className="text-xs text-gray-400 mt-1">
                {payables?.total_invoices || 0} fatture
              </div>
            </motion.div>
          </div>

          {/* Seconda Riga: Cash Flow + Receivables + Payables */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Cash Flow Card */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.3 }}
              className="glass-card p-6"
            >
              <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
                <PiggyBank className="w-5 h-5 text-purple-600" />
                Flusso di Cassa
              </h3>
              <div className="space-y-4">
                <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200">
                  <div className="flex items-center gap-2">
                    <TrendingUp className="w-4 h-4 text-green-600" />
                    <span className="text-sm text-gray-600">Entrate</span>
                  </div>
                  <span className="font-semibold text-green-600">
                    {formatCurrency(cashFlow?.cash_in || 0)}
                  </span>
                </div>
                <div className="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                  <div className="flex items-center gap-2">
                    <TrendingDown className="w-4 h-4 text-red-600" />
                    <span className="text-sm text-gray-600">Uscite</span>
                  </div>
                  <span className="font-semibold text-red-600">
                    {formatCurrency(cashFlow?.cash_out || 0)}
                  </span>
                </div>
                <div className="border-t border-gray-200 pt-3">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-600">Saldo Netto</span>
                    <span className={`text-lg font-bold ${(cashFlow?.net_cash_flow || 0) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      {formatCurrency(cashFlow?.net_cash_flow || 0)}
                    </span>
                  </div>
                </div>
                <div className="p-3 bg-gray-50 rounded-lg mt-2">
                  <div className="text-xs text-gray-500 mb-1">Saldo Bancario Attuale</div>
                  <div className="text-xl font-bold text-gray-900">
                    {formatCurrency(cashFlow?.current_balance || 0)}
                  </div>
                </div>
              </div>
            </motion.div>

            {/* Crediti (Receivables) */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.35 }}
              className="glass-card p-6"
            >
              <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
                <FileText className="w-5 h-5 text-yellow-600" />
                Crediti (Da Incassare)
              </h3>
              <div className="space-y-3">
                <div className="p-4 bg-gray-50 rounded-lg">
                  <div className="text-xs text-gray-500 mb-1">Totale Outstanding</div>
                  <div className="text-2xl font-bold text-yellow-600">
                    {formatCurrency(receivables?.total_outstanding || 0)}
                  </div>
                  <div className="text-xs text-gray-400 mt-1">
                    {receivables?.total_invoices || 0} fatture non pagate
                  </div>
                </div>
                {(receivables?.overdue_amount || 0) > 0 && (
                  <div className="p-4 bg-red-50 rounded-lg border border-red-200">
                    <div className="flex items-center gap-2 text-xs text-red-600 mb-1">
                      <AlertCircle className="w-3 h-3" />
                      Scadute
                    </div>
                    <div className="text-xl font-bold text-red-600">
                      {formatCurrency(receivables?.overdue_amount || 0)}
                    </div>
                    <div className="text-xs text-gray-400 mt-1">
                      {receivables?.overdue_count || 0} fatture scadute
                    </div>
                  </div>
                )}
              </div>
            </motion.div>

            {/* Debiti (Payables) */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.4 }}
              className="glass-card p-6"
            >
              <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
                <Building2 className="w-5 h-5 text-orange-600" />
                Debiti (Da Pagare)
              </h3>
              <div className="space-y-3">
                <div className="p-4 bg-gray-50 rounded-lg">
                  <div className="text-xs text-gray-500 mb-1">Totale Outstanding</div>
                  <div className="text-2xl font-bold text-orange-600">
                    {formatCurrency(payables?.total_outstanding || 0)}
                  </div>
                  <div className="text-xs text-gray-400 mt-1">
                    {payables?.total_invoices || 0} fatture fornitori
                  </div>
                </div>
                <div className="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                  <div className="text-xs text-yellow-700 mb-1">In scadenza questo mese</div>
                  <div className="text-xl font-bold text-yellow-600">
                    {formatCurrency(payables?.due_this_month || 0)}
                  </div>
                </div>
                {(payables?.overdue_amount || 0) > 0 && (
                  <div className="p-4 bg-red-50 rounded-lg border border-red-200">
                    <div className="flex items-center gap-2 text-xs text-red-600 mb-1">
                      <AlertCircle className="w-3 h-3" />
                      Scadute
                    </div>
                    <div className="text-xl font-bold text-red-600">
                      {formatCurrency(payables?.overdue_amount || 0)}
                    </div>
                    <div className="text-xs text-gray-400 mt-1">
                      {payables?.overdue_count || 0} fatture scadute
                    </div>
                  </div>
                )}
              </div>
            </motion.div>
          </div>

          {/* Terza Riga: Clienti + Previsioni */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Clienti per Tipo */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.45 }}
              className="glass-card p-6"
            >
              <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
                <Users className="w-5 h-5 text-primary-600" />
                Composizione Clienti
              </h3>
              <div className="grid grid-cols-3 gap-4">
                <div className="p-4 bg-primary-50 rounded-lg text-center">
                  <div className="text-2xl font-bold text-primary-600">
                    {clients?.by_type?.partner || 0}
                  </div>
                  <div className="text-xs text-gray-500 mt-1">Partner</div>
                </div>
                <div className="p-4 bg-purple-50 rounded-lg text-center">
                  <div className="text-2xl font-bold text-purple-600">
                    {clients?.by_type?.extra || 0}
                  </div>
                  <div className="text-xs text-gray-500 mt-1">Extra</div>
                </div>
                <div className="p-4 bg-green-50 rounded-lg text-center">
                  <div className="text-2xl font-bold text-green-600">
                    {clients?.by_type?.consumer || 0}
                  </div>
                  <div className="text-xs text-gray-500 mt-1">Consumatori</div>
                </div>
              </div>
              <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-500">Fatturato medio per cliente</span>
                  <span className="text-lg font-bold text-gray-900">
                    {formatCurrency(clients?.avg_revenue_per_client || 0)}
                  </span>
                </div>
              </div>
            </motion.div>

            {/* Previsioni Cash Flow */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.5 }}
              className="glass-card p-6"
            >
              <h3 className="font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
                <Calendar className="w-5 h-5 text-primary-600" />
                Previsioni Cash Flow
              </h3>
              <div className="space-y-3">
                <div className="p-4 bg-gray-50 rounded-lg">
                  <div className="flex justify-between items-center mb-2">
                    <span className="text-sm text-gray-500">Prossimi 30 giorni</span>
                    <span className={`text-lg font-bold ${(cashFlow?.forecast?.['30_days']?.net || 0) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      {formatCurrency(cashFlow?.forecast?.['30_days']?.net || 0)}
                    </span>
                  </div>
                  <div className="flex gap-4 text-xs">
                    <span className="text-green-600">
                      +{formatCurrency(cashFlow?.forecast?.['30_days']?.expected_in || 0)}
                    </span>
                    <span className="text-red-600">
                      -{formatCurrency(cashFlow?.forecast?.['30_days']?.expected_out || 0)}
                    </span>
                  </div>
                </div>
                <div className="p-4 bg-gray-50 rounded-lg">
                  <div className="flex justify-between items-center mb-2">
                    <span className="text-sm text-gray-500">Prossimi 60 giorni</span>
                    <span className={`text-lg font-bold ${(cashFlow?.forecast?.['60_days']?.net || 0) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      {formatCurrency(cashFlow?.forecast?.['60_days']?.net || 0)}
                    </span>
                  </div>
                  <div className="flex gap-4 text-xs">
                    <span className="text-green-600">
                      +{formatCurrency(cashFlow?.forecast?.['60_days']?.expected_in || 0)}
                    </span>
                    <span className="text-red-600">
                      -{formatCurrency(cashFlow?.forecast?.['60_days']?.expected_out || 0)}
                    </span>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        </>
      )}

      {/* Financial Management Tab Content */}
      {activeTab === 'financial-management' && (
        <FinancialManagement />
      )}
    </div>
  )
}
