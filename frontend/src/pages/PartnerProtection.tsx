import { useState, useEffect, Fragment, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import axios from '../utils/api';
import { Dialog, Transition, Tab } from '@headlessui/react';
import {
  Shield,
  AlertTriangle,
  Clock,
  Package,
  RefreshCw,
  Search,
  Filter,
  X,
  CheckCircle,
  XCircle,
  DollarSign,
  Calendar,
  MapPin,
  Store,
  FileText,
  Plus,
  Settings,
  TrendingUp,
  TrendingDown,
  Eye,
} from 'lucide-react';

// Types
interface Incident {
  id: number;
  restaurant_id: number;
  delivery_id: number | null;
  rider_fleet_id: string | null;
  incident_type: string;
  delay_minutes: number | null;
  description: string | null;
  status: string;
  resolution_notes: string | null;
  created_at: string;
  resolved_at: string | null;
  restaurant?: { id: number; nome: string };
  delivery?: { id: number; order_id: string };
  penalty?: { id: number; amount: number; billing_status: string };
}

interface Penalty {
  id: number;
  restaurant_id: number;
  penalty_type: string;
  amount: number;
  billing_status: string;
  description: string | null;
  created_at: string;
  restaurant?: { id: number; nome: string };
  client?: { id: number; ragione_sociale: string };
}

interface Stats {
  total: number;
  pending: number;
  by_type: Record<string, number>;
  by_status: Record<string, number>;
}

const incidentTypeLabels: Record<string, string> = {
  delay: 'Ritardo consegna',
  forgotten_item: 'Prodotto dimenticato',
  bulky_unmarked: 'Voluminoso non segnalato',
  packaging_issue: 'Problema packaging',
  other: 'Altro',
};

const statusLabels: Record<string, string> = {
  pending: 'In attesa',
  reviewed: 'In revisione',
  resolved: 'Risolto',
  disputed: 'Contestato',
};

const penaltyStatusLabels: Record<string, string> = {
  pending: 'Da fatturare',
  invoiced: 'Fatturato',
  paid: 'Pagato',
  waived: 'Annullato',
};

const incidentTypeColors: Record<string, string> = {
  delay: 'bg-yellow-100 text-yellow-700 border-yellow-200',
  forgotten_item: 'bg-red-100 text-red-700 border-red-200',
  bulky_unmarked: 'bg-orange-100 text-orange-700 border-orange-200',
  packaging_issue: 'bg-purple-100 text-purple-700 border-purple-200',
  other: 'bg-gray-100 text-gray-500 border-gray-200',
};

const statusColors: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-700 border-yellow-200',
  reviewed: 'bg-primary-100 text-primary-700 border-primary-200',
  resolved: 'bg-green-100 text-green-700 border-green-200',
  disputed: 'bg-red-100 text-red-700 border-red-200',
};

interface ReportForm {
  type: 'delay' | 'forgotten_item' | 'bulky_unmarked';
  restaurant_id: number | null;
  delivery_id: string;
  delay_minutes: string;
  description: string;
}

interface RestaurantOption {
  id: number;
  nome: string;
}

interface DeliveryOption {
  id: number;
  order_id: string;
}

export default function PartnerProtection() {
  const [activeTab, setActiveTab] = useState(0);
  const [incidents, setIncidents] = useState<Incident[]>([]);
  const [penalties, setPenalties] = useState<Penalty[]>([]);
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Filters
  const [searchTerm, setSearchTerm] = useState('');
  const [typeFilter, setTypeFilter] = useState<string>('all');
  const [statusFilter, setStatusFilter] = useState<string>('all');

  // Modals
  const [showResolveModal, setShowResolveModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [showReportModal, setShowReportModal] = useState(false);
  const [selectedIncident, setSelectedIncident] = useState<Incident | null>(null);

  // Form
  const [resolutionNotes, setResolutionNotes] = useState('');
  const [waivePenalty, setWaivePenalty] = useState(false);

  // Report form
  const [reportForm, setReportForm] = useState<ReportForm>({
    type: 'delay',
    restaurant_id: null,
    delivery_id: '',
    delay_minutes: '',
    description: '',
  });
  const [reportSubmitting, setReportSubmitting] = useState(false);
  const [reportError, setReportError] = useState('');
  const [restaurants, setRestaurants] = useState<RestaurantOption[]>([]);
  const [deliveries, setDeliveries] = useState<DeliveryOption[]>([]);
  const [loadingDeliveries, setLoadingDeliveries] = useState(false);

  const fetchIncidents = useCallback(async () => {
    try {
      const params: any = {};
      if (typeFilter !== 'all') params.incident_type = typeFilter;
      if (statusFilter !== 'all') params.status = statusFilter;

      const response = await axios.get('/partner-protection/incidents', { params });
      if (response.data.success) {
        setIncidents(response.data.data.data || []);
      }
    } catch (err) {
      console.error('Error fetching incidents:', err);
    }
  }, [typeFilter, statusFilter]);

  const fetchPenalties = useCallback(async () => {
    try {
      const response = await axios.get('/partner-protection/penalties');
      if (response.data.success) {
        setPenalties(response.data.data.data || []);
      }
    } catch (err) {
      console.error('Error fetching penalties:', err);
    }
  }, []);

  const fetchStats = useCallback(async () => {
    try {
      const response = await axios.get('/partner-protection/incidents/stats');
      if (response.data.success) {
        setStats(response.data.data);
      }
    } catch (err) {
      console.error('Error fetching stats:', err);
    }
  }, []);

  const fetchRestaurants = useCallback(async () => {
    try {
      const response = await axios.get('/restaurants');
      const data = response.data.data || response.data || [];
      setRestaurants(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error('Error fetching restaurants:', err);
    }
  }, []);

  const fetchDeliveries = useCallback(async (restaurantId: number) => {
    setLoadingDeliveries(true);
    try {
      const response = await axios.get('/deliveries', {
        params: { restaurant_id: restaurantId, per_page: 50, sort: '-order_date' },
      });
      const data = response.data.data?.data || response.data.data || [];
      setDeliveries(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error('Error fetching deliveries:', err);
      setDeliveries([]);
    } finally {
      setLoadingDeliveries(false);
    }
  }, []);

  const handleReportSubmit = async () => {
    if (!reportForm.restaurant_id || !reportForm.delivery_id) {
      setReportError('Seleziona ristorante e consegna');
      return;
    }

    setReportSubmitting(true);
    setReportError('');

    try {
      let endpoint = '';
      let payload: any = {
        restaurant_id: reportForm.restaurant_id,
        delivery_id: parseInt(reportForm.delivery_id),
        description: reportForm.description || undefined,
      };

      switch (reportForm.type) {
        case 'delay':
          if (!reportForm.delay_minutes || parseInt(reportForm.delay_minutes) < 1) {
            setReportError('Inserisci i minuti di ritardo');
            setReportSubmitting(false);
            return;
          }
          endpoint = '/partner-protection/incidents/delay';
          payload.delay_minutes = parseInt(reportForm.delay_minutes);
          break;
        case 'forgotten_item':
          endpoint = '/partner-protection/incidents/forgotten-item';
          break;
        case 'bulky_unmarked':
          endpoint = '/partner-protection/incidents/bulky-unmarked';
          break;
      }

      const response = await axios.post(endpoint, payload);
      if (response.data.success) {
        setShowReportModal(false);
        setReportForm({ type: 'delay', restaurant_id: null, delivery_id: '', delay_minutes: '', description: '' });
        setDeliveries([]);
        await handleRefresh();
      }
    } catch (err: any) {
      setReportError(err.response?.data?.error || err.response?.data?.message || 'Errore nella segnalazione');
    } finally {
      setReportSubmitting(false);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setLoading(true);
      await Promise.all([fetchIncidents(), fetchPenalties(), fetchStats()]);
      setLoading(false);
    };
    loadData();
  }, [fetchIncidents, fetchPenalties, fetchStats]);

  useEffect(() => {
    if (reportForm.restaurant_id) {
      fetchDeliveries(reportForm.restaurant_id);
    } else {
      setDeliveries([]);
    }
  }, [reportForm.restaurant_id, fetchDeliveries]);

  const handleRefresh = async () => {
    setRefreshing(true);
    await Promise.all([fetchIncidents(), fetchPenalties(), fetchStats()]);
    setRefreshing(false);
  };

  const handleResolveIncident = async () => {
    if (!selectedIncident) return;
    try {
      const response = await axios.put(`/partner-protection/incidents/${selectedIncident.id}/resolve`, {
        resolution_notes: resolutionNotes,
        waive_penalty: waivePenalty,
      });
      if (response.data.success) {
        setShowResolveModal(false);
        setSelectedIncident(null);
        setResolutionNotes('');
        setWaivePenalty(false);
        await handleRefresh();
      }
    } catch (err: any) {
      alert(err.response?.data?.error || 'Errore nella risoluzione');
    }
  };

  const handleWaivePenalty = async (penaltyId: number) => {
    if (!confirm('Sei sicuro di voler annullare questa penale?')) return;
    try {
      const response = await axios.post(`/partner-protection/penalties/${penaltyId}/waive`);
      if (response.data.success) {
        await fetchPenalties();
      }
    } catch (err: any) {
      alert(err.response?.data?.error || "Errore nell'annullamento");
    }
  };

  const filteredIncidents = incidents.filter(incident => {
    if (searchTerm) {
      const search = searchTerm.toLowerCase();
      return (
        incident.restaurant?.nome?.toLowerCase().includes(search) ||
        incident.delivery?.order_id?.toLowerCase().includes(search)
      );
    }
    return true;
  });

  const filteredPenalties = penalties.filter(penalty => {
    if (searchTerm) {
      const search = searchTerm.toLowerCase();
      return (
        penalty.restaurant?.nome?.toLowerCase().includes(search) ||
        penalty.client?.ragione_sociale?.toLowerCase().includes(search)
      );
    }
    return true;
  });

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="text-center">
          <RefreshCw className="w-10 h-10 text-primary-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-500">Caricamento dati...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="mb-6">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
              <Shield className="w-7 h-7 text-primary-600" />
              Protezione Partner
            </h1>
            <p className="text-gray-500 mt-1">Gestisci segnalazioni e penali per i ristoranti</p>
          </div>
          <div className="flex flex-col gap-2 flex-shrink-0">
            <div className="flex flex-wrap items-center gap-2">
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => {
                  setShowReportModal(true);
                  fetchRestaurants();
                }}
                className="flex items-center gap-2 px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
              >
                <Plus className="w-4 h-4" />
                Nuova Segnalazione
              </motion.button>
            </div>
            <div className="flex items-center gap-2 justify-end">
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={handleRefresh}
                disabled={refreshing}
                className="flex items-center gap-2 px-3 py-2 text-sm bg-white text-gray-700 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors disabled:opacity-50"
              >
                <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
                Aggiorna
              </motion.button>
            </div>
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="glass-card p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">Totale Segnalazioni</p>
                <p className="text-2xl font-bold text-gray-900">{stats.total}</p>
              </div>
              <div className="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center">
                <AlertTriangle className="w-6 h-6 text-primary-600" />
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="glass-card p-4"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">In Attesa</p>
                <p className="text-2xl font-bold text-yellow-600">{stats.pending}</p>
              </div>
              <div className="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                <Clock className="w-6 h-6 text-yellow-600" />
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="glass-card p-4"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">Ritardi</p>
                <p className="text-2xl font-bold text-orange-600">{stats.by_type?.delay || 0}</p>
              </div>
              <div className="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                <Clock className="w-6 h-6 text-orange-600" />
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="glass-card p-4"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">Dimenticanze</p>
                <p className="text-2xl font-bold text-red-500">{stats.by_type?.forgotten_item || 0}</p>
              </div>
              <div className="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                <Package className="w-6 h-6 text-red-500" />
              </div>
            </div>
          </motion.div>
        </div>
      )}

      {/* Tabs */}
      <Tab.Group selectedIndex={activeTab} onChange={setActiveTab}>
        <Tab.List className="flex space-x-2 bg-gray-100 p-1 rounded-xl mb-6">
          <Tab
            className={({ selected }) =>
              `flex-1 py-2.5 text-sm font-medium rounded-lg transition-colors ${
                selected ? 'bg-primary-600 text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'
              }`
            }
          >
            <span className="flex items-center justify-center gap-2">
              <AlertTriangle className="w-4 h-4" />
              Segnalazioni ({incidents.length})
            </span>
          </Tab>
          <Tab
            className={({ selected }) =>
              `flex-1 py-2.5 text-sm font-medium rounded-lg transition-colors ${
                selected ? 'bg-primary-600 text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'
              }`
            }
          >
            <span className="flex items-center justify-center gap-2">
              <DollarSign className="w-4 h-4" />
              Penali ({penalties.length})
            </span>
          </Tab>
        </Tab.List>

        <Tab.Panels>
          {/* Incidents Tab */}
          <Tab.Panel>
            {/* Filters */}
            <div className="glass-card p-4 mb-6">
              <div className="flex flex-col md:flex-row gap-4">
                <div className="flex-1 relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                  <input
                    type="text"
                    placeholder="Cerca per ristorante o ordine..."
                    value={searchTerm}
                    onChange={e => setSearchTerm(e.target.value)}
                    className="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
                <div className="flex items-center gap-4">
                  <select
                    value={typeFilter}
                    onChange={e => setTypeFilter(e.target.value)}
                    className="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500"
                  >
                    <option value="all">Tutti i tipi</option>
                    <option value="delay">Ritardo</option>
                    <option value="forgotten_item">Dimenticanza</option>
                    <option value="bulky_unmarked">Voluminoso</option>
                    <option value="other">Altro</option>
                  </select>
                  <select
                    value={statusFilter}
                    onChange={e => setStatusFilter(e.target.value)}
                    className="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500"
                  >
                    <option value="all">Tutti gli stati</option>
                    <option value="pending">In attesa</option>
                    <option value="reviewed">In revisione</option>
                    <option value="resolved">Risolto</option>
                    <option value="disputed">Contestato</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Incidents List */}
            <div className="space-y-4">
              <AnimatePresence mode="popLayout">
                {filteredIncidents.map((incident, index) => (
                  <motion.div
                    key={incident.id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    transition={{ delay: index * 0.05 }}
                    className="glass-card p-4"
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex items-start gap-4">
                        <div
                          className={`w-10 h-10 rounded-lg flex items-center justify-center ${
                            incidentTypeColors[incident.incident_type]?.split(' ')[0] || 'bg-gray-100'
                          }`}
                        >
                          {incident.incident_type === 'delay' && <Clock className="w-5 h-5 text-yellow-600" />}
                          {incident.incident_type === 'forgotten_item' && (
                            <Package className="w-5 h-5 text-red-500" />
                          )}
                          {incident.incident_type === 'bulky_unmarked' && (
                            <AlertTriangle className="w-5 h-5 text-orange-600" />
                          )}
                          {!['delay', 'forgotten_item', 'bulky_unmarked'].includes(incident.incident_type) && (
                            <FileText className="w-5 h-5 text-gray-500" />
                          )}
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-medium text-gray-900">
                              {incident.restaurant?.nome || `Ristorante #${incident.restaurant_id}`}
                            </span>
                            <span
                              className={`px-2 py-0.5 text-xs rounded-full border ${
                                incidentTypeColors[incident.incident_type] || ''
                              }`}
                            >
                              {incidentTypeLabels[incident.incident_type] || incident.incident_type}
                            </span>
                            <span
                              className={`px-2 py-0.5 text-xs rounded-full border ${
                                statusColors[incident.status] || ''
                              }`}
                            >
                              {statusLabels[incident.status] || incident.status}
                            </span>
                          </div>
                          <div className="text-sm text-gray-500 mt-1">
                            {incident.delivery?.order_id && (
                              <span className="mr-3">Ordine: {incident.delivery.order_id}</span>
                            )}
                            {incident.delay_minutes && <span className="mr-3">Ritardo: {incident.delay_minutes} min</span>}
                            <span>{new Date(incident.created_at).toLocaleDateString('it-IT')}</span>
                          </div>
                          {incident.description && (
                            <p className="text-sm text-gray-600 mt-2">{incident.description}</p>
                          )}
                          {incident.penalty && (
                            <div className="mt-2 text-sm">
                              <span className="text-gray-500">Penale: </span>
                              <span className="text-amber-600 font-medium">
                                €{incident.penalty.amount?.toFixed(2)}
                              </span>
                              <span
                                className={`ml-2 px-2 py-0.5 text-xs rounded-full ${
                                  incident.penalty.billing_status === 'pending'
                                    ? 'bg-yellow-100 text-yellow-700'
                                    : incident.penalty.billing_status === 'waived'
                                      ? 'bg-gray-100 text-gray-500'
                                      : 'bg-green-100 text-green-700'
                                }`}
                              >
                                {penaltyStatusLabels[incident.penalty.billing_status]}
                              </span>
                            </div>
                          )}
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => {
                            setSelectedIncident(incident);
                            setShowDetailModal(true);
                          }}
                          className="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                          title="Dettagli"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                        {incident.status === 'pending' && (
                          <button
                            onClick={() => {
                              setSelectedIncident(incident);
                              setShowResolveModal(true);
                            }}
                            className="p-2 text-gray-500 hover:text-green-600 hover:bg-green-500/20 rounded-lg transition-colors"
                            title="Risolvi"
                          >
                            <CheckCircle className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                    </div>
                  </motion.div>
                ))}
              </AnimatePresence>

              {filteredIncidents.length === 0 && (
                <div className="text-center py-12">
                  <AlertTriangle className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 mb-2">Nessuna segnalazione</h3>
                  <p className="text-gray-500">Non ci sono segnalazioni con i filtri selezionati</p>
                </div>
              )}
            </div>
          </Tab.Panel>

          {/* Penalties Tab */}
          <Tab.Panel>
            <div className="space-y-4">
              <AnimatePresence mode="popLayout">
                {filteredPenalties.map((penalty, index) => (
                  <motion.div
                    key={penalty.id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    transition={{ delay: index * 0.05 }}
                    className="glass-card p-4"
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex items-start gap-4">
                        <div className="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                          <DollarSign className="w-5 h-5 text-amber-600" />
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-medium text-gray-900">
                              €{penalty.amount?.toFixed(2)}
                            </span>
                            <span
                              className={`px-2 py-0.5 text-xs rounded-full border ${
                                penalty.billing_status === 'pending'
                                  ? 'bg-yellow-100 text-yellow-700 border-yellow-200'
                                  : penalty.billing_status === 'waived'
                                    ? 'bg-gray-100 text-gray-500 border-gray-200'
                                    : 'bg-green-100 text-green-700 border-green-200'
                              }`}
                            >
                              {penaltyStatusLabels[penalty.billing_status]}
                            </span>
                          </div>
                          <div className="text-sm text-gray-500 mt-1">
                            <span className="mr-3">
                              {penalty.restaurant?.nome || `Ristorante #${penalty.restaurant_id}`}
                            </span>
                            <span>{new Date(penalty.created_at).toLocaleDateString('it-IT')}</span>
                          </div>
                          {penalty.description && (
                            <p className="text-sm text-gray-600 mt-2">{penalty.description}</p>
                          )}
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        {penalty.billing_status === 'pending' && (
                          <button
                            onClick={() => handleWaivePenalty(penalty.id)}
                            className="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                            title="Annulla penale"
                          >
                            <XCircle className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                    </div>
                  </motion.div>
                ))}
              </AnimatePresence>

              {filteredPenalties.length === 0 && (
                <div className="text-center py-12">
                  <DollarSign className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 mb-2">Nessuna penale</h3>
                  <p className="text-gray-500">Non ci sono penali registrate</p>
                </div>
              )}
            </div>
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>

      {/* Resolve Incident Modal */}
      <Transition appear show={showResolveModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => {
            setShowResolveModal(false);
            setSelectedIncident(null);
          }}
        >
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/30 backdrop-blur-sm" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-semibold text-gray-900 flex items-center justify-between">
                    Risolvi Segnalazione
                    <button
                      onClick={() => {
                        setShowResolveModal(false);
                        setSelectedIncident(null);
                      }}
                      className="p-1 hover:bg-gray-100 rounded-lg"
                    >
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  <div className="mt-4 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Note di risoluzione</label>
                      <textarea
                        rows={4}
                        value={resolutionNotes}
                        onChange={e => setResolutionNotes(e.target.value)}
                        placeholder="Descrivi come è stata risolta la segnalazione..."
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500"
                      />
                    </div>

                    {selectedIncident?.penalty && (
                      <label className="flex items-center gap-3">
                        <input
                          type="checkbox"
                          checked={waivePenalty}
                          onChange={e => setWaivePenalty(e.target.checked)}
                          className="w-4 h-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-primary-500"
                        />
                        <span className="text-sm text-gray-600">
                          Annulla penale associata (€{selectedIncident.penalty.amount?.toFixed(2)})
                        </span>
                      </label>
                    )}

                    <div className="flex justify-end gap-3 pt-4">
                      <button
                        onClick={() => {
                          setShowResolveModal(false);
                          setSelectedIncident(null);
                        }}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                      >
                        Annulla
                      </button>
                      <button
                        onClick={handleResolveIncident}
                        className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                      >
                        Risolvi
                      </button>
                    </div>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Detail Modal */}
      <Transition appear show={showDetailModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => {
            setShowDetailModal(false);
            setSelectedIncident(null);
          }}
        >
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/30 backdrop-blur-sm" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-semibold text-gray-900 flex items-center justify-between">
                    Dettaglio Segnalazione
                    <button
                      onClick={() => {
                        setShowDetailModal(false);
                        setSelectedIncident(null);
                      }}
                      className="p-1 hover:bg-gray-100 rounded-lg"
                    >
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  {selectedIncident && (
                    <div className="mt-4 space-y-4">
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <p className="text-sm text-gray-500">Ristorante</p>
                          <p className="text-gray-900 dark:text-white font-medium">
                            {selectedIncident.restaurant?.nome || `#${selectedIncident.restaurant_id}`}
                          </p>
                        </div>
                        <div>
                          <p className="text-sm text-gray-500">Tipo</p>
                          <p className="text-gray-900 dark:text-white font-medium">
                            {incidentTypeLabels[selectedIncident.incident_type]}
                          </p>
                        </div>
                        <div>
                          <p className="text-sm text-gray-500">Stato</p>
                          <span
                            className={`px-2 py-0.5 text-xs rounded-full border ${
                              statusColors[selectedIncident.status]
                            }`}
                          >
                            {statusLabels[selectedIncident.status]}
                          </span>
                        </div>
                        <div>
                          <p className="text-sm text-gray-500">Data</p>
                          <p className="text-gray-900 dark:text-white font-medium">
                            {new Date(selectedIncident.created_at).toLocaleString('it-IT')}
                          </p>
                        </div>
                        {selectedIncident.delay_minutes && (
                          <div>
                            <p className="text-sm text-gray-500">Ritardo</p>
                            <p className="text-gray-900 dark:text-white font-medium">{selectedIncident.delay_minutes} minuti</p>
                          </div>
                        )}
                        {selectedIncident.delivery?.order_id && (
                          <div>
                            <p className="text-sm text-gray-500">Ordine</p>
                            <p className="text-gray-900 dark:text-white font-medium">{selectedIncident.delivery.order_id}</p>
                          </div>
                        )}
                      </div>

                      {selectedIncident.description && (
                        <div>
                          <p className="text-sm text-gray-500 mb-1">Descrizione</p>
                          <p className="text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                            {selectedIncident.description}
                          </p>
                        </div>
                      )}

                      {selectedIncident.resolution_notes && (
                        <div>
                          <p className="text-sm text-gray-500 mb-1">Note di risoluzione</p>
                          <p className="text-white bg-green-50 p-3 rounded-lg border border-green-200">
                            {selectedIncident.resolution_notes}
                          </p>
                        </div>
                      )}

                      {selectedIncident.penalty && (
                        <div className="bg-amber-50 p-4 rounded-lg border border-amber-200">
                          <p className="text-sm text-amber-600 font-medium mb-2">Penale Associata</p>
                          <div className="flex items-center justify-between">
                            <span className="text-gray-900 dark:text-white font-bold text-lg">
                              €{selectedIncident.penalty.amount?.toFixed(2)}
                            </span>
                            <span
                              className={`px-2 py-1 text-xs rounded-full ${
                                selectedIncident.penalty.billing_status === 'pending'
                                  ? 'bg-yellow-100 text-yellow-700'
                                  : selectedIncident.penalty.billing_status === 'waived'
                                    ? 'bg-gray-100 text-gray-500'
                                    : 'bg-green-100 text-green-700'
                              }`}
                            >
                              {penaltyStatusLabels[selectedIncident.penalty.billing_status]}
                            </span>
                          </div>
                        </div>
                      )}
                    </div>
                  )}
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Report Modal */}
      <Transition appear show={showReportModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => {
            setShowReportModal(false);
            setReportForm({ type: 'delay', restaurant_id: null, delivery_id: '', delay_minutes: '', description: '' });
            setReportError('');
            setDeliveries([]);
          }}
        >
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/30 backdrop-blur-sm" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-semibold text-gray-900 flex items-center justify-between">
                    <span className="flex items-center gap-2">
                      <Plus className="w-5 h-5 text-primary-600" />
                      Nuova Segnalazione
                    </span>
                    <button
                      onClick={() => {
                        setShowReportModal(false);
                        setReportForm({ type: 'delay', restaurant_id: null, delivery_id: '', delay_minutes: '', description: '' });
                        setReportError('');
                        setDeliveries([]);
                      }}
                      className="p-1 hover:bg-gray-100 rounded-lg"
                    >
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  <div className="mt-4 space-y-4">
                    {/* Tipo segnalazione */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Tipo segnalazione</label>
                      <div className="grid grid-cols-3 gap-2">
                        {[
                          { value: 'delay' as const, label: 'Ritardo', icon: Clock, color: 'yellow' },
                          { value: 'forgotten_item' as const, label: 'Dimenticanza', icon: Package, color: 'red' },
                          { value: 'bulky_unmarked' as const, label: 'Voluminoso', icon: AlertTriangle, color: 'orange' },
                        ].map((opt) => (
                          <button
                            key={opt.value}
                            onClick={() => setReportForm(f => ({ ...f, type: opt.value }))}
                            className={`p-3 rounded-lg border text-center transition-all ${
                              reportForm.type === opt.value
                                ? `bg-${opt.color}-500/20 border-${opt.color}-500/50 text-${opt.color}-400`
                                : 'border-gray-200 text-gray-500 hover:border-gray-300'
                            }`}
                          >
                            <opt.icon className="w-5 h-5 mx-auto mb-1" />
                            <span className="text-xs">{opt.label}</span>
                          </button>
                        ))}
                      </div>
                    </div>

                    {/* Ristorante */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Ristorante</label>
                      <select
                        value={reportForm.restaurant_id || ''}
                        onChange={e => {
                          const id = e.target.value ? parseInt(e.target.value) : null;
                          setReportForm(f => ({ ...f, restaurant_id: id, delivery_id: '' }));
                        }}
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500"
                      >
                        <option value="">Seleziona ristorante...</option>
                        {restaurants.map(r => (
                          <option key={r.id} value={r.id}>{r.nome}</option>
                        ))}
                      </select>
                    </div>

                    {/* Consegna */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Consegna</label>
                      <select
                        value={reportForm.delivery_id}
                        onChange={e => setReportForm(f => ({ ...f, delivery_id: e.target.value }))}
                        disabled={!reportForm.restaurant_id || loadingDeliveries}
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                      >
                        <option value="">
                          {loadingDeliveries ? 'Caricamento...' : 'Seleziona consegna...'}
                        </option>
                        {deliveries.map(d => (
                          <option key={d.id} value={d.id}>#{d.order_id}</option>
                        ))}
                      </select>
                    </div>

                    {/* Minuti di ritardo (solo per tipo delay) */}
                    {reportForm.type === 'delay' && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Minuti di ritardo</label>
                        <input
                          type="number"
                          min="1"
                          value={reportForm.delay_minutes}
                          onChange={e => setReportForm(f => ({ ...f, delay_minutes: e.target.value }))}
                          placeholder="es. 15"
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500"
                        />
                      </div>
                    )}

                    {/* Descrizione */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Descrizione (opzionale)</label>
                      <textarea
                        rows={3}
                        value={reportForm.description}
                        onChange={e => setReportForm(f => ({ ...f, description: e.target.value }))}
                        placeholder="Dettagli aggiuntivi..."
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500"
                      />
                    </div>

                    {/* Errore */}
                    {reportError && (
                      <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-500 text-sm">
                        {reportError}
                      </div>
                    )}

                    {/* Azioni */}
                    <div className="flex justify-end gap-3 pt-2">
                      <button
                        onClick={() => {
                          setShowReportModal(false);
                          setReportForm({ type: 'delay', restaurant_id: null, delivery_id: '', delay_minutes: '', description: '' });
                          setReportError('');
                          setDeliveries([]);
                        }}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                      >
                        Annulla
                      </button>
                      <button
                        onClick={handleReportSubmit}
                        disabled={reportSubmitting}
                        className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-600 transition-colors disabled:opacity-50 flex items-center gap-2"
                      >
                        {reportSubmitting && <RefreshCw className="w-4 h-4 animate-spin" />}
                        Invia Segnalazione
                      </button>
                    </div>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </div>
  );
}
