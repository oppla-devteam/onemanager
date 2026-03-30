import { useState, useEffect, Fragment } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { trashApi } from '../utils/api';
import {
  Trash2,
  RotateCcw,
  AlertTriangle,
  Search,
  RefreshCw,
  Clock,
  X,
  Filter,
} from 'lucide-react';
import { Dialog, Transition } from '@headlessui/react';

interface TrashedItem {
  id: number;
  type: string;
  type_label: string;
  name: string;
  deleted_at: string;
  days_left: number;
  created_at: string | null;
}

interface TrashSummary {
  [key: string]: {
    label: string;
    count: number;
  };
}

export default function Trash() {
  const [items, setItems] = useState<TrashedItem[]>([]);
  const [summary, setSummary] = useState<TrashSummary>({});
  const [loading, setLoading] = useState(true);
  const [filterType, setFilterType] = useState<string>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [total, setTotal] = useState(0);
  const [refreshing, setRefreshing] = useState(false);

  // Modals
  const [showEmptyConfirm, setShowEmptyConfirm] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [itemToDelete, setItemToDelete] = useState<TrashedItem | null>(null);
  const [actionLoading, setActionLoading] = useState<string | null>(null);

  const fetchTrash = async (showRefresh = false) => {
    if (showRefresh) setRefreshing(true);
    try {
      const params: any = { per_page: 200 };
      if (filterType !== 'all') params.type = filterType;
      const response = await trashApi.getAll(params);
      setItems(response.data.data);
      setSummary(response.data.summary);
      setTotal(response.data.total);
    } catch (err) {
      console.error('Error fetching trash:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchTrash();
  }, [filterType]);

  const handleRestore = async (item: TrashedItem) => {
    setActionLoading(`restore-${item.type}-${item.id}`);
    try {
      await trashApi.restore(item.type, item.id);
      await fetchTrash();
    } catch (err) {
      console.error('Error restoring item:', err);
    } finally {
      setActionLoading(null);
    }
  };

  const handleForceDelete = async () => {
    if (!itemToDelete) return;
    setActionLoading(`delete-${itemToDelete.type}-${itemToDelete.id}`);
    try {
      await trashApi.forceDelete(itemToDelete.type, itemToDelete.id);
      setShowDeleteConfirm(false);
      setItemToDelete(null);
      await fetchTrash();
    } catch (err) {
      console.error('Error deleting item:', err);
    } finally {
      setActionLoading(null);
    }
  };

  const handleEmptyTrash = async () => {
    setActionLoading('empty');
    try {
      await trashApi.empty();
      setShowEmptyConfirm(false);
      await fetchTrash();
    } catch (err) {
      console.error('Error emptying trash:', err);
    } finally {
      setActionLoading(null);
    }
  };

  const filteredItems = items.filter(item =>
    item.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getDaysColor = (daysLeft: number) => {
    if (daysLeft > 20) return 'text-green-400 bg-green-500/10';
    if (daysLeft > 10) return 'text-amber-400 bg-amber-500/10';
    return 'text-red-400 bg-red-500/10';
  };

  const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
      task: 'bg-primary-500/20 text-primary-300',
      task_board: 'bg-purple-500/20 text-purple-300',
      client: 'bg-primary-500/20 text-primary-300',
      lead: 'bg-emerald-500/20 text-emerald-300',
      opportunity: 'bg-amber-500/20 text-amber-300',
      invoice: 'bg-orange-500/20 text-orange-300',
      contract: 'bg-indigo-500/20 text-indigo-300',
      supplier: 'bg-teal-500/20 text-teal-300',
      partner: 'bg-pink-500/20 text-pink-300',
      restaurant: 'bg-rose-500/20 text-rose-300',
    };
    return colors[type] || 'bg-slate-500/20 text-gray-600';
  };

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('it-IT', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const totalCount = Object.values(summary).reduce((sum, s) => sum + s.count, 0);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <RefreshCw className="w-8 h-8 text-primary-400 animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
      >
        <div className="flex items-center gap-3">
          <div className="p-3 bg-red-500/20 rounded-xl">
            <Trash2 className="w-7 h-7 text-red-400" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Cestino</h1>
            <p className="text-gray-500 text-sm">
              {totalCount} {totalCount === 1 ? 'elemento' : 'elementi'} nel cestino
            </p>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => fetchTrash(true)}
            className="glass-button p-2 hover:bg-white/10"
            title="Aggiorna"
          >
            <RefreshCw className={`w-5 h-5 text-gray-500 ${refreshing ? 'animate-spin' : ''}`} />
          </button>
          {totalCount > 0 && (
            <button
              onClick={() => setShowEmptyConfirm(true)}
              className="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl border border-red-500/30 hover:bg-red-500/30 transition-all text-sm font-medium"
            >
              Svuota Cestino
            </button>
          )}
        </div>
      </motion.div>

      {/* Warning banner */}
      <motion.div
        initial={{ opacity: 0, y: -10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 flex items-start gap-3"
      >
        <AlertTriangle className="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
        <p className="text-amber-200 text-sm">
          Gli elementi nel cestino vengono eliminati automaticamente dopo <strong>30 giorni</strong>.
          Puoi ripristinarli in qualsiasi momento prima della scadenza.
        </p>
      </motion.div>

      {/* Filter tabs + Search */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 0.2 }}
        className="flex flex-col lg:flex-row gap-4"
      >
        {/* Type filter tabs */}
        <div className="flex flex-wrap gap-2 flex-1">
          <button
            onClick={() => setFilterType('all')}
            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
              filterType === 'all'
                ? 'bg-primary-500/20 text-primary-300 border border-primary-500/50'
                : 'bg-white/5 text-gray-500 border border-gray-200 hover:bg-white/10'
            }`}
          >
            Tutti ({totalCount})
          </button>
          {Object.entries(summary).map(([key, value]) => (
            <button
              key={key}
              onClick={() => setFilterType(key)}
              className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                filterType === key
                  ? 'bg-primary-500/20 text-primary-300 border border-primary-500/50'
                  : 'bg-white/5 text-gray-500 border border-gray-200 hover:bg-white/10'
              }`}
            >
              {value.label} ({value.count})
            </button>
          ))}
        </div>

        {/* Search */}
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Cerca per nome..."
            className="glass-input pl-10 pr-4 py-2 w-full lg:w-64 text-sm"
          />
        </div>
      </motion.div>

      {/* Table */}
      {filteredItems.length === 0 ? (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ delay: 0.3 }}
          className="bg-white/5 backdrop-blur-xl border border-gray-200 rounded-2xl p-12 text-center"
        >
          <Trash2 className="w-16 h-16 text-gray-500 mx-auto mb-4" />
          <h3 className="text-lg font-semibold text-gray-600 mb-2">
            {searchTerm ? 'Nessun risultato' : 'Il cestino è vuoto'}
          </h3>
          <p className="text-gray-400 text-sm">
            {searchTerm
              ? 'Prova con un termine di ricerca diverso'
              : 'Gli elementi eliminati appariranno qui'}
          </p>
        </motion.div>
      ) : (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.3 }}
          className="bg-white/5 backdrop-blur-xl border border-gray-200 rounded-2xl overflow-hidden"
        >
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Nome
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Tipo
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Eliminato il
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">
                    Giorni rimasti
                  </th>
                  <th className="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Azioni
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                <AnimatePresence>
                  {filteredItems.map((item, index) => (
                    <motion.tr
                      key={`${item.type}-${item.id}`}
                      initial={{ opacity: 0, x: -10 }}
                      animate={{ opacity: 1, x: 0 }}
                      exit={{ opacity: 0, x: 10 }}
                      transition={{ delay: index * 0.02 }}
                      className="hover:bg-white/5 transition-colors"
                    >
                      <td className="px-6 py-4">
                        <span className="text-gray-900 dark:text-white font-medium text-sm">
                          {item.name}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <span className={`px-2.5 py-1 rounded-lg text-xs font-medium ${getTypeColor(item.type)}`}>
                          {item.type_label}
                        </span>
                      </td>
                      <td className="px-6 py-4 hidden sm:table-cell">
                        <div className="flex items-center gap-2 text-gray-500 text-sm">
                          <Clock className="w-3.5 h-3.5" />
                          {formatDate(item.deleted_at)}
                        </div>
                      </td>
                      <td className="px-6 py-4 hidden md:table-cell">
                        <span className={`px-2.5 py-1 rounded-lg text-xs font-medium ${getDaysColor(item.days_left)}`}>
                          {item.days_left} {item.days_left === 1 ? 'giorno' : 'giorni'}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => handleRestore(item)}
                            disabled={actionLoading === `restore-${item.type}-${item.id}`}
                            className="p-2 text-green-400 hover:text-green-300 hover:bg-green-500/10 rounded-lg transition-all disabled:opacity-50"
                            title="Ripristina"
                          >
                            {actionLoading === `restore-${item.type}-${item.id}` ? (
                              <RefreshCw className="w-4 h-4 animate-spin" />
                            ) : (
                              <RotateCcw className="w-4 h-4" />
                            )}
                          </button>
                          <button
                            onClick={() => {
                              setItemToDelete(item);
                              setShowDeleteConfirm(true);
                            }}
                            className="p-2 text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg transition-all"
                            title="Elimina definitivamente"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </motion.tr>
                  ))}
                </AnimatePresence>
              </tbody>
            </table>
          </div>
        </motion.div>
      )}

      {/* Confirm Force Delete Dialog */}
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
            <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" />
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
                <Dialog.Panel className="w-full max-w-md bg-gray-900 border border-gray-200 rounded-2xl p-6 shadow-xl">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="p-2 bg-red-500/20 rounded-xl">
                      <AlertTriangle className="w-6 h-6 text-red-400" />
                    </div>
                    <Dialog.Title className="text-lg font-semibold text-gray-900 dark:text-white">
                      Elimina definitivamente
                    </Dialog.Title>
                  </div>

                  <p className="text-gray-600 text-sm mb-6">
                    Stai per eliminare definitivamente <strong className="text-gray-900 dark:text-white">{itemToDelete?.name}</strong> ({itemToDelete?.type_label}).
                    Questa azione non può essere annullata.
                  </p>

                  <div className="flex justify-end gap-3">
                    <button
                      onClick={() => setShowDeleteConfirm(false)}
                      className="px-4 py-2 bg-white/5 text-gray-600 rounded-xl border border-gray-200 hover:bg-white/10 transition-all text-sm"
                    >
                      Annulla
                    </button>
                    <button
                      onClick={handleForceDelete}
                      disabled={actionLoading === `delete-${itemToDelete?.type}-${itemToDelete?.id}`}
                      className="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl border border-red-500/30 hover:bg-red-500/30 transition-all text-sm font-medium disabled:opacity-50"
                    >
                      {actionLoading?.startsWith('delete') ? 'Eliminazione...' : 'Elimina definitivamente'}
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Confirm Empty Trash Dialog */}
      <Transition appear show={showEmptyConfirm} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setShowEmptyConfirm(false)}>
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" />
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
                <Dialog.Panel className="w-full max-w-md bg-gray-900 border border-gray-200 rounded-2xl p-6 shadow-xl">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="p-2 bg-red-500/20 rounded-xl">
                      <AlertTriangle className="w-6 h-6 text-red-400" />
                    </div>
                    <Dialog.Title className="text-lg font-semibold text-gray-900 dark:text-white">
                      Svuota Cestino
                    </Dialog.Title>
                  </div>

                  <p className="text-gray-600 text-sm mb-2">
                    Stai per eliminare definitivamente <strong className="text-gray-900 dark:text-white">tutti i {totalCount} elementi</strong> nel cestino.
                  </p>
                  <p className="text-red-400 text-sm mb-6">
                    Questa azione non può essere annullata.
                  </p>

                  <div className="flex justify-end gap-3">
                    <button
                      onClick={() => setShowEmptyConfirm(false)}
                      className="px-4 py-2 bg-white/5 text-gray-600 rounded-xl border border-gray-200 hover:bg-white/10 transition-all text-sm"
                    >
                      Annulla
                    </button>
                    <button
                      onClick={handleEmptyTrash}
                      disabled={actionLoading === 'empty'}
                      className="px-4 py-2 bg-red-500/20 text-red-400 rounded-xl border border-red-500/30 hover:bg-red-500/30 transition-all text-sm font-medium disabled:opacity-50"
                    >
                      {actionLoading === 'empty' ? 'Svuotamento...' : 'Svuota tutto'}
                    </button>
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
