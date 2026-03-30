import { useState, useEffect, Fragment, useCallback } from 'react';
import { AnimatePresence } from 'framer-motion';
import axios, { ridersApi } from '../utils/api';
import { Dialog, Transition } from '@headlessui/react';
import {
  Bike,
  RefreshCw,
  X,
  Bell,
  Clock,
  AlertCircle,
  Send,
  Users,
  Package,
  Edit,
  Trash2,
  AlertTriangle,
  PackageX,
  Scale,
  Download,
} from 'lucide-react';

import {
  RiderCard,
  RiderMap,
  RiderFilters,
  RiderSummary,
  RiderHeader,
  Rider,
  Team,
  RiderTask,
  Summary,
  RealtimeRider,
} from '../components/riders';

export default function Riders() {
  const [riders, setRiders] = useState<Rider[]>([]);
  const [realtimeRiders, setRealtimeRiders] = useState<RealtimeRider[]>([]);
  const [teams, setTeams] = useState<Team[]>([]);
  const [summary, setSummary] = useState<Summary>({ total: 0, available: 0, busy: 0, offline: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [teamFilter, setTeamFilter] = useState<string>('all');
  const [refreshing, setRefreshing] = useState(false);
  const [lastSyncedAt, setLastSyncedAt] = useState<string | null>(null);
  const [isStale, setIsStale] = useState(false);
  const [syncing, setSyncing] = useState(false);
  const [showMap, setShowMap] = useState(false);
  const [mapLoading, setMapLoading] = useState(false);
  const [exporting, setExporting] = useState(false);

  // Modals
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showTasksModal, setShowTasksModal] = useState(false);
  const [showNotifyModal, setShowNotifyModal] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [showTeamModal, setShowTeamModal] = useState(false);
  const [showAssignTeamModal, setShowAssignTeamModal] = useState(false);
  const [showIncidentModal, setShowIncidentModal] = useState(false);

  // Selected rider
  const [selectedRider, setSelectedRider] = useState<Rider | null>(null);
  const [riderTasks, setRiderTasks] = useState<RiderTask[]>([]);
  const [loadingTasks, setLoadingTasks] = useState(false);

  // Form state
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    username: '',
    phone: '',
    email: '',
    password: '',
    team_id: '',
    transport_type: 'motorcycle',
  });

  // Team form state
  const [teamFormData, setTeamFormData] = useState({ team_name: '' });
  const [editingTeam, setEditingTeam] = useState<Team | null>(null);
  const [selectedTeamForAssign, setSelectedTeamForAssign] = useState<string>('');

  // Notification state
  const [notificationMessage, setNotificationMessage] = useState('');
  const [selectedRidersForNotify, setSelectedRidersForNotify] = useState<string[]>([]);

  // Incident reporting state
  const [selectedTaskForIncident, setSelectedTaskForIncident] = useState<RiderTask | null>(null);
  const [incidentType, setIncidentType] = useState<'delay' | 'forgotten_item' | 'bulky_unmarked'>('delay');
  const [delayMinutes, setDelayMinutes] = useState<number>(15);
  const [incidentDescription, setIncidentDescription] = useState('');

  // Fetch riders
  const fetchRiders = useCallback(async (showRefresh = false) => {
    if (showRefresh) setRefreshing(true);
    try {
      const response = await axios.get('/riders');
      if (response.data.success) {
        setRiders(response.data.data);
        setSummary(response.data.summary);
        setLastSyncedAt(response.data.last_synced_at);
        setIsStale(response.data.is_stale || false);
        setError(null);
      } else {
        setError(response.data.error || 'Errore nel caricamento riders');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Errore di connessione');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  // Fetch realtime data for map
  const fetchRealtimeData = useCallback(async () => {
    setMapLoading(true);
    try {
      const response = await axios.get('/riders/realtime');
      if (response.data.success) {
        setRealtimeRiders(response.data.data);
        setSummary(prev => ({ ...prev, online: response.data.summary?.online || 0 }));
      }
    } catch (err) {
      console.error('Error fetching realtime data:', err);
    } finally {
      setMapLoading(false);
    }
  }, []);

  // Manual sync
  const handleManualSync = async () => {
    setSyncing(true);
    try {
      const response = await axios.post('/riders/sync-now');
      if (response.data.success) {
        await fetchRiders();
      } else {
        alert('Sync failed: ' + (response.data.error || 'Unknown error'));
      }
    } catch (err: any) {
      alert('Sync failed: ' + (err.response?.data?.error || err.message || 'Unknown error'));
    } finally {
      setSyncing(false);
    }
  };

  // Fetch teams
  const fetchTeams = async () => {
    try {
      const response = await axios.get('/riders/teams');
      if (response.data.success) {
        setTeams(response.data.data);
      }
    } catch (err) {
      console.error('Error fetching teams:', err);
    }
  };

  // Fetch rider tasks
  const fetchRiderTasks = async (fleetId: string) => {
    setLoadingTasks(true);
    try {
      const response = await axios.get(`/riders/${fleetId}/tasks`);
      if (response.data.success) {
        setRiderTasks(response.data.data || []);
      }
    } catch (err) {
      console.error('Error fetching tasks:', err);
      setRiderTasks([]);
    } finally {
      setLoadingTasks(false);
    }
  };

  useEffect(() => {
    fetchRiders();
    fetchTeams();
    const interval = setInterval(() => fetchRiders(), 30000);
    return () => clearInterval(interval);
  }, [fetchRiders]);

  // Realtime refresh when map is open
  useEffect(() => {
    if (showMap) {
      fetchRealtimeData();
      const interval = setInterval(fetchRealtimeData, 15000);
      return () => clearInterval(interval);
    }
  }, [showMap, fetchRealtimeData]);

  // Create rider
  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await axios.post('/riders', {
        ...formData,
        team_id: parseInt(formData.team_id),
      });
      if (response.data.success) {
        setShowAddModal(false);
        resetForm();
        fetchRiders();
      }
    } catch (err: any) {
      alert(err.response?.data?.error || 'Errore nella creazione');
    }
  };

  // Update rider
  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedRider) return;
    try {
      const updateData: any = {};
      if (formData.first_name) updateData.first_name = formData.first_name;
      if (formData.last_name) updateData.last_name = formData.last_name;
      if (formData.phone) updateData.phone = formData.phone;
      if (formData.email) updateData.email = formData.email;
      if (formData.team_id) updateData.team_id = parseInt(formData.team_id);
      if (formData.transport_type) updateData.transport_type = formData.transport_type;

      const response = await axios.put(`/riders/${selectedRider.fleet_id}`, updateData);
      if (response.data.success) {
        setShowEditModal(false);
        setSelectedRider(null);
        resetForm();
        fetchRiders();
      }
    } catch (err: any) {
      alert(err.response?.data?.error || "Errore nell'aggiornamento");
    }
  };

  // Toggle block
  const handleToggleBlock = async (rider: Rider) => {
    try {
      const response = await axios.post(`/riders/${rider.fleet_id}/toggle-block`, {
        block: !rider.is_blocked,
        reason: rider.is_blocked ? undefined : 'Bloccato da admin',
      });
      if (response.data.success) {
        fetchRiders();
      }
    } catch (err: any) {
      alert(err.response?.data?.error || 'Errore nel cambio stato');
    }
  };

  // Delete rider
  const handleDelete = async () => {
    if (!selectedRider) return;
    try {
      const response = await axios.delete(`/riders/${selectedRider.fleet_id}`);
      if (response.data.success) {
        setShowDeleteConfirm(false);
        setSelectedRider(null);
        fetchRiders();
      }
    } catch (err: any) {
      alert(err.response?.data?.error || "Errore nell'eliminazione");
    }
  };

  // Send notification
  const handleSendNotification = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!notificationMessage.trim() || selectedRidersForNotify.length === 0) return;

    try {
      const response = await axios.post('/riders/notify', {
        fleet_ids: selectedRidersForNotify,
        message: notificationMessage.trim(),
      });
      if (response.data.success) {
        setShowNotifyModal(false);
        setNotificationMessage('');
        setSelectedRidersForNotify([]);
        alert('Notifica inviata con successo!');
      }
    } catch (err: any) {
      alert(err.response?.data?.error || "Errore nell'invio della notifica");
    }
  };

  // Team management
  const handleCreateTeam = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await axios.post('/riders/teams', { team_name: teamFormData.team_name });
      if (response.data.success) {
        setTeamFormData({ team_name: '' });
        fetchTeams();
        alert('Team creato con successo!');
      }
    } catch (err: any) {
      alert(err.response?.data?.error || 'Errore nella creazione del team');
    }
  };

  const handleUpdateTeam = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingTeam) return;
    try {
      const response = await axios.put(`/riders/teams/${editingTeam.team_id}`, {
        team_name: teamFormData.team_name,
      });
      if (response.data.success) {
        setEditingTeam(null);
        setTeamFormData({ team_name: '' });
        fetchTeams();
        alert('Team aggiornato con successo!');
      }
    } catch (err: any) {
      alert(err.response?.data?.error || "Errore nell'aggiornamento del team");
    }
  };

  const handleDeleteTeam = async (teamId: number) => {
    if (!confirm('Sei sicuro di voler eliminare questo team?')) return;
    try {
      const response = await axios.delete(`/riders/teams/${teamId}`);
      if (response.data.success) {
        fetchTeams();
        alert('Team eliminato con successo!');
      }
    } catch (err: any) {
      alert(err.response?.data?.error || "Errore nell'eliminazione del team");
    }
  };

  const handleAssignRiderToTeam = async () => {
    if (!selectedRider || !selectedTeamForAssign) return;
    try {
      const response = await axios.post('/riders/assign-team', {
        fleet_id: selectedRider.fleet_id,
        team_id: parseInt(selectedTeamForAssign),
      });
      if (response.data.success) {
        setShowAssignTeamModal(false);
        setSelectedRider(null);
        setSelectedTeamForAssign('');
        fetchRiders();
        alert('Rider assegnato al team con successo!');
      }
    } catch (err: any) {
      alert(err.response?.data?.error || "Errore nell'assegnazione");
    }
  };

  // Report incident
  const handleReportIncident = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTaskForIncident) return;

    try {
      // We need restaurant_id and delivery_id - for now we use job_id as delivery_id
      // In a real scenario, you'd have these mapped properly
      const endpoint = incidentType === 'delay'
        ? '/partner-protection/incidents/delay'
        : incidentType === 'forgotten_item'
          ? '/partner-protection/incidents/forgotten-item'
          : '/partner-protection/incidents/bulky-unmarked';

      const payload: any = {
        restaurant_id: 1, // This should come from the task/delivery data
        delivery_id: selectedTaskForIncident.job_id,
        description: incidentDescription || undefined,
      };

      if (incidentType === 'delay') {
        payload.delay_minutes = delayMinutes;
      }

      const response = await axios.post(endpoint, payload);
      if (response.data.success) {
        setShowIncidentModal(false);
        setSelectedTaskForIncident(null);
        setIncidentDescription('');
        setDelayMinutes(15);
        alert('Segnalazione inviata con successo!');
      }
    } catch (err: any) {
      alert(err.response?.data?.error || 'Errore nella segnalazione');
    }
  };

  const openIncidentModal = (task: RiderTask, type: 'delay' | 'forgotten_item' | 'bulky_unmarked') => {
    setSelectedTaskForIncident(task);
    setIncidentType(type);
    setShowIncidentModal(true);
  };

  // Reset form
  const resetForm = () => {
    setFormData({
      first_name: '',
      last_name: '',
      username: '',
      phone: '',
      email: '',
      password: '',
      team_id: teams.length > 0 ? teams[0].team_id.toString() : '',
      transport_type: 'motorcycle',
    });
  };

  // Open edit modal
  const openEditModal = (rider: Rider) => {
    setSelectedRider(rider);
    setFormData({
      first_name: rider.first_name,
      last_name: rider.last_name,
      username: rider.username,
      phone: rider.phone || '',
      email: rider.email || '',
      password: '',
      team_id: rider.team_id?.toString() || '',
      transport_type: rider.transport_type,
    });
    setShowEditModal(true);
  };

  // Open tasks modal
  const openTasksModal = (rider: Rider) => {
    setSelectedRider(rider);
    fetchRiderTasks(rider.fleet_id);
    setShowTasksModal(true);
  };

  // Open notify modal
  const openNotifyModal = (rider?: Rider) => {
    if (rider) {
      setSelectedRidersForNotify([rider.fleet_id]);
    } else {
      setSelectedRidersForNotify(riders.filter(r => !r.is_blocked).map(r => r.fleet_id));
    }
    setShowNotifyModal(true);
  };

  // Open assign team modal
  const openAssignTeamModal = (rider: Rider) => {
    setSelectedRider(rider);
    setSelectedTeamForAssign(rider.team_id?.toString() || '');
    setShowAssignTeamModal(true);
  };

  // Filter riders
  const filteredRiders = riders.filter(rider => {
    const matchesSearch =
      rider.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (rider.phone && rider.phone.includes(searchTerm)) ||
      (rider.email && rider.email.toLowerCase().includes(searchTerm.toLowerCase()));
    const matchesStatus = statusFilter === 'all' || rider.status === statusFilter;
    const matchesTeam =
      teamFilter === 'all' ||
      (teamFilter === 'no_team' && !rider.team_id) ||
      rider.team_id?.toString() === teamFilter;
    return matchesSearch && matchesStatus && matchesTeam;
  });

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const response = await ridersApi.export()
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `riders_${new Date().toISOString().split('T')[0]}.csv`)
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

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="text-center">
          <RefreshCw className="w-10 h-10 text-primary-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-500">Caricamento riders...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <RiderHeader
        lastSyncedAt={lastSyncedAt}
        isStale={isStale}
        showMap={showMap}
        syncing={syncing}
        refreshing={refreshing}
        error={error}
        exporting={exporting}
        onToggleMap={() => setShowMap(!showMap)}
        onManageTeams={() => setShowTeamModal(true)}
        onNotifyAll={() => openNotifyModal()}
        onNewRider={() => {
          resetForm();
          setShowAddModal(true);
        }}
        onSync={handleManualSync}
        onExportCSV={handleExportCSV}
      />

      {/* Summary Cards */}
      <RiderSummary summary={summary} />

      {/* Live Map */}
      <AnimatePresence>
        {showMap && <RiderMap riders={realtimeRiders} loading={mapLoading} />}
      </AnimatePresence>

      {/* Filters */}
      <RiderFilters
        searchTerm={searchTerm}
        onSearchChange={setSearchTerm}
        statusFilter={statusFilter}
        onStatusFilterChange={setStatusFilter}
        teamFilter={teamFilter}
        onTeamFilterChange={setTeamFilter}
        teams={teams}
      />

      {/* Riders Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <AnimatePresence mode="popLayout">
          {filteredRiders.map((rider, index) => (
            <RiderCard
              key={rider.fleet_id}
              rider={rider}
              index={index}
              onEdit={openEditModal}
              onToggleBlock={handleToggleBlock}
              onDelete={rider => {
                setSelectedRider(rider);
                setShowDeleteConfirm(true);
              }}
              onViewTasks={openTasksModal}
              onNotify={openNotifyModal}
              onAssignTeam={openAssignTeamModal}
            />
          ))}
        </AnimatePresence>
      </div>

      {/* Empty state */}
      {filteredRiders.length === 0 && !loading && (
        <div className="text-center py-12">
          <Bike className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Nessun rider trovato</h3>
          <p className="text-gray-500">
            {searchTerm || statusFilter !== 'all' || teamFilter !== 'all'
              ? 'Prova a modificare i filtri di ricerca'
              : 'Aggiungi il tuo primo rider per iniziare'}
          </p>
        </div>
      )}

      {/* Add/Edit Modal */}
      <Transition appear show={showAddModal || showEditModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => {
            setShowAddModal(false);
            setShowEditModal(false);
            setSelectedRider(null);
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
                    {showAddModal ? 'Nuovo Rider' : 'Modifica Rider'}
                    <button
                      onClick={() => {
                        setShowAddModal(false);
                        setShowEditModal(false);
                        setSelectedRider(null);
                      }}
                      className="p-1 hover:bg-gray-100 rounded-lg"
                    >
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  <form onSubmit={showAddModal ? handleCreate : handleUpdate} className="mt-4 space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input
                          type="text"
                          required={showAddModal}
                          value={formData.first_name}
                          onChange={e => setFormData({ ...formData, first_name: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                        <input
                          type="text"
                          value={formData.last_name}
                          onChange={e => setFormData({ ...formData, last_name: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                      </div>
                    </div>

                    {showAddModal && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                        <input
                          type="text"
                          required
                          value={formData.username}
                          onChange={e => setFormData({ ...formData, username: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                      </div>
                    )}

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Telefono *</label>
                      <input
                        type="tel"
                        required={showAddModal}
                        value={formData.phone}
                        onChange={e => setFormData({ ...formData, phone: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                      <input
                        type="email"
                        value={formData.email}
                        onChange={e => setFormData({ ...formData, email: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      />
                    </div>

                    {showAddModal && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <input
                          type="password"
                          required
                          minLength={6}
                          value={formData.password}
                          onChange={e => setFormData({ ...formData, password: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                      </div>
                    )}

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Team *</label>
                      <select
                        required={showAddModal}
                        value={formData.team_id}
                        onChange={e => setFormData({ ...formData, team_id: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      >
                        <option value="">Seleziona team</option>
                        {teams.map(team => (
                          <option key={team.team_id} value={team.team_id}>
                            {team.team_name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Mezzo di trasporto</label>
                      <select
                        value={formData.transport_type}
                        onChange={e => setFormData({ ...formData, transport_type: e.target.value })}
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      >
                        <option value="motorcycle">Moto / Scooter</option>
                        <option value="bicycle">Bicicletta</option>
                        <option value="car">Auto</option>
                        <option value="foot">A piedi</option>
                        <option value="truck">Furgone</option>
                      </select>
                    </div>

                    <div className="flex justify-end gap-3 pt-4">
                      <button
                        type="button"
                        onClick={() => {
                          setShowAddModal(false);
                          setShowEditModal(false);
                          setSelectedRider(null);
                        }}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                      >
                        Annulla
                      </button>
                      <button
                        type="submit"
                        className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                      >
                        {showAddModal ? 'Crea Rider' : 'Salva modifiche'}
                      </button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Team Management Modal */}
      <Transition appear show={showTeamModal} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setShowTeamModal(false)}>
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
                    <span className="flex items-center gap-2">
                      <Users className="w-5 h-5 text-primary-600" />
                      Gestione Team
                    </span>
                    <button onClick={() => setShowTeamModal(false)} className="p-1 hover:bg-gray-100 rounded-lg">
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  <form onSubmit={editingTeam ? handleUpdateTeam : handleCreateTeam} className="mt-4 flex gap-2">
                    <input
                      type="text"
                      required
                      placeholder="Nome del team..."
                      value={teamFormData.team_name}
                      onChange={e => setTeamFormData({ team_name: e.target.value })}
                      className="flex-1 px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                    <button
                      type="submit"
                      className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                    >
                      {editingTeam ? 'Aggiorna' : 'Crea'}
                    </button>
                    {editingTeam && (
                      <button
                        type="button"
                        onClick={() => {
                          setEditingTeam(null);
                          setTeamFormData({ team_name: '' });
                        }}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                      >
                        Annulla
                      </button>
                    )}
                  </form>

                  <div className="mt-6 space-y-2 max-h-80 overflow-y-auto">
                    {teams.map(team => (
                      <div
                        key={team.team_id}
                        className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200"
                      >
                        <div>
                          <p className="font-medium text-gray-900">{team.team_name}</p>
                          <p className="text-xs text-gray-500">ID: {team.team_id}</p>
                        </div>
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => {
                              setEditingTeam(team);
                              setTeamFormData({ team_name: team.team_name });
                            }}
                            className="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-500/20 rounded-lg transition-colors"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleDeleteTeam(team.team_id)}
                            className="p-2 text-red-500 hover:text-red-300 hover:bg-red-500/20 rounded-lg transition-colors"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Assign Team Modal */}
      <Transition appear show={showAssignTeamModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => {
            setShowAssignTeamModal(false);
            setSelectedRider(null);
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
                <Dialog.Panel className="w-full max-w-sm transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-semibold text-gray-900">
                    Assegna {selectedRider?.name} a Team
                  </Dialog.Title>

                  <div className="mt-4">
                    <select
                      value={selectedTeamForAssign}
                      onChange={e => setSelectedTeamForAssign(e.target.value)}
                      className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >
                      <option value="">Seleziona team</option>
                      {teams.map(team => (
                        <option key={team.team_id} value={team.team_id}>
                          {team.team_name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="flex justify-end gap-3 mt-6">
                    <button
                      onClick={() => {
                        setShowAssignTeamModal(false);
                        setSelectedRider(null);
                      }}
                      className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                      Annulla
                    </button>
                    <button
                      onClick={handleAssignRiderToTeam}
                      disabled={!selectedTeamForAssign}
                      className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
                    >
                      Assegna
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Tasks Modal */}
      <Transition appear show={showTasksModal} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setShowTasksModal(false)}>
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
                <Dialog.Panel className="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-semibold text-gray-900 flex items-center justify-between">
                    <span className="flex items-center gap-2">
                      <Package className="w-5 h-5 text-primary-600" />
                      Consegne di {selectedRider?.name || selectedRider?.username}
                    </span>
                    <button onClick={() => setShowTasksModal(false)} className="p-1 hover:bg-gray-100 rounded-lg">
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  <div className="mt-4">
                    {loadingTasks ? (
                      <div className="text-center py-8">
                        <RefreshCw className="w-8 h-8 text-primary-600 animate-spin mx-auto" />
                        <p className="text-gray-500 mt-2">Caricamento consegne...</p>
                      </div>
                    ) : riderTasks.length > 0 ? (
                      <div className="space-y-3 max-h-96 overflow-y-auto">
                        {riderTasks.map(task => (
                          <div key={task.job_id} className="p-4 border border-gray-200 rounded-lg bg-gray-50">
                            <div className="flex items-start justify-between">
                              <div>
                                <p className="font-medium text-gray-900">{task.customer_name}</p>
                                <p className="text-sm text-gray-500">{task.customer_address}</p>
                                {task.scheduled_time && (
                                  <p className="text-sm text-gray-400 flex items-center gap-1 mt-1">
                                    <Clock className="w-3 h-3" />
                                    {new Date(task.scheduled_time).toLocaleString('it-IT')}
                                  </p>
                                )}
                              </div>
                              <span
                                className={`px-2 py-1 rounded-full text-xs font-medium ${
                                  task.job_status === 2
                                    ? 'bg-green-100 text-green-700'
                                    : task.job_status === 3
                                      ? 'bg-red-100 text-red-700'
                                      : task.job_status === 4
                                        ? 'bg-yellow-100 text-yellow-700'
                                        : 'bg-gray-100 text-gray-500'
                                }`}
                              >
                                {task.status_label || 'In corso'}
                              </span>
                            </div>
                            {/* Incident Report Buttons */}
                            <div className="flex gap-2 mt-3 pt-3 border-t border-gray-100">
                              <button
                                onClick={() => openIncidentModal(task, 'delay')}
                                className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs bg-amber-100 text-amber-700 hover:bg-amber-500/30 rounded-lg transition-colors"
                                title="Segnala ritardo"
                              >
                                <Clock className="w-3.5 h-3.5" />
                                Ritardo
                              </button>
                              <button
                                onClick={() => openIncidentModal(task, 'forgotten_item')}
                                className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs bg-red-100 text-red-700 hover:bg-red-500/30 rounded-lg transition-colors"
                                title="Segnala oggetto dimenticato"
                              >
                                <PackageX className="w-3.5 h-3.5" />
                                Dimenticanza
                              </button>
                              <button
                                onClick={() => openIncidentModal(task, 'bulky_unmarked')}
                                className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs bg-purple-100 text-purple-700 hover:bg-purple-500/30 rounded-lg transition-colors"
                                title="Segnala ordine voluminoso non dichiarato"
                              >
                                <Scale className="w-3.5 h-3.5" />
                                Voluminoso
                              </button>
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="text-center py-8">
                        <Package className="w-12 h-12 text-gray-300 mx-auto mb-2" />
                        <p className="text-gray-500">Nessuna consegna per oggi</p>
                      </div>
                    )}
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Notification Modal */}
      <Transition appear show={showNotifyModal} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setShowNotifyModal(false)}>
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
                      <Bell className="w-5 h-5 text-amber-500" />
                      Invia Notifica
                    </span>
                    <button onClick={() => setShowNotifyModal(false)} className="p-1 hover:bg-gray-100 rounded-lg">
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  <form onSubmit={handleSendNotification} className="mt-4">
                    <div className="mb-4">
                      <p className="text-sm text-gray-600 mb-2">
                        Destinatari: <span className="font-medium text-gray-900">{selectedRidersForNotify.length} rider</span>
                      </p>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Messaggio</label>
                      <textarea
                        required
                        minLength={4}
                        maxLength={160}
                        rows={4}
                        value={notificationMessage}
                        onChange={e => setNotificationMessage(e.target.value)}
                        placeholder="Scrivi il messaggio da inviare..."
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      />
                      <p className="text-xs text-gray-400 mt-1">{notificationMessage.length}/160 caratteri</p>
                    </div>

                    <div className="flex justify-end gap-3 pt-4">
                      <button
                        type="button"
                        onClick={() => setShowNotifyModal(false)}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                      >
                        Annulla
                      </button>
                      <button
                        type="submit"
                        className="flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors"
                      >
                        <Send className="w-4 h-4" />
                        Invia
                      </button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Delete Confirmation Modal */}
      <Transition appear show={showDeleteConfirm} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setShowDeleteConfirm(false)}>
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
                <Dialog.Panel className="w-full max-w-sm transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <div className="text-center">
                    <div className="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                      <AlertCircle className="w-6 h-6 text-red-500" />
                    </div>
                    <Dialog.Title className="text-lg font-semibold text-gray-900">Elimina Rider</Dialog.Title>
                    <p className="text-gray-500 mt-2">
                      Sei sicuro di voler eliminare{' '}
                      <strong className="text-gray-900 dark:text-white">{selectedRider?.name || selectedRider?.username}</strong>? Questa
                      azione non puo essere annullata.
                    </p>
                  </div>

                  <div className="flex justify-center gap-3 mt-6">
                    <button
                      onClick={() => setShowDeleteConfirm(false)}
                      className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                      Annulla
                    </button>
                    <button
                      onClick={handleDelete}
                      className="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                    >
                      Elimina
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Incident Report Modal */}
      <Transition appear show={showIncidentModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => {
            setShowIncidentModal(false);
            setSelectedTaskForIncident(null);
            setIncidentDescription('');
            setDelayMinutes(15);
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
                      <AlertTriangle
                        className={`w-5 h-5 ${
                          incidentType === 'delay'
                            ? 'text-amber-500'
                            : incidentType === 'forgotten_item'
                              ? 'text-red-500'
                              : 'text-purple-500'
                        }`}
                      />
                      {incidentType === 'delay'
                        ? 'Segnala Ritardo'
                        : incidentType === 'forgotten_item'
                          ? 'Segnala Dimenticanza'
                          : 'Segnala Voluminoso Non Dichiarato'}
                    </span>
                    <button
                      onClick={() => {
                        setShowIncidentModal(false);
                        setSelectedTaskForIncident(null);
                        setIncidentDescription('');
                        setDelayMinutes(15);
                      }}
                      className="p-1 hover:bg-gray-100 rounded-lg"
                    >
                      <X className="w-5 h-5 text-gray-500" />
                    </button>
                  </Dialog.Title>

                  {selectedTaskForIncident && (
                    <div className="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                      <p className="text-sm text-gray-500">Consegna</p>
                      <p className="font-medium text-gray-900">{selectedTaskForIncident.customer_name}</p>
                      <p className="text-sm text-gray-500">{selectedTaskForIncident.customer_address}</p>
                    </div>
                  )}

                  <form onSubmit={handleReportIncident} className="mt-4 space-y-4">
                    {incidentType === 'delay' && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Minuti di ritardo</label>
                        <select
                          value={delayMinutes}
                          onChange={e => setDelayMinutes(parseInt(e.target.value))}
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                          <option value={5}>5 minuti</option>
                          <option value={10}>10 minuti</option>
                          <option value={15}>15 minuti</option>
                          <option value={20}>20 minuti</option>
                          <option value={30}>30 minuti</option>
                          <option value={45}>45 minuti</option>
                          <option value={60}>1 ora</option>
                          <option value={90}>1 ora e 30 minuti</option>
                          <option value={120}>2 ore o più</option>
                        </select>
                      </div>
                    )}

                    {incidentType === 'forgotten_item' && (
                      <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p className="text-sm text-red-300">
                          Questa segnalazione creerà automaticamente una consegna di ritorno e applicherà la penale
                          prevista al ristorante.
                        </p>
                      </div>
                    )}

                    {incidentType === 'bulky_unmarked' && (
                      <div className="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                        <p className="text-sm text-purple-300">
                          Questa segnalazione documenta che il ristorante non ha dichiarato l'ordine come voluminoso.
                          Verrà applicato un sovrapprezzo e registrata l'infrazione.
                        </p>
                      </div>
                    )}

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Note (opzionale)</label>
                      <textarea
                        rows={3}
                        value={incidentDescription}
                        onChange={e => setIncidentDescription(e.target.value)}
                        placeholder="Aggiungi dettagli sulla segnalazione..."
                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      />
                    </div>

                    <div className="flex justify-end gap-3 pt-4">
                      <button
                        type="button"
                        onClick={() => {
                          setShowIncidentModal(false);
                          setSelectedTaskForIncident(null);
                          setIncidentDescription('');
                          setDelayMinutes(15);
                        }}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                      >
                        Annulla
                      </button>
                      <button
                        type="submit"
                        className={`flex items-center gap-2 px-4 py-2 text-white rounded-lg transition-colors ${
                          incidentType === 'delay'
                            ? 'bg-amber-500 hover:bg-amber-600'
                            : incidentType === 'forgotten_item'
                              ? 'bg-red-500 hover:bg-red-600'
                              : 'bg-purple-500 hover:bg-purple-600'
                        }`}
                      >
                        <AlertTriangle className="w-4 h-4" />
                        Invia Segnalazione
                      </button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </div>
  );
}
