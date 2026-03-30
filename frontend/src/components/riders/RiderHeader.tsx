import { motion } from 'framer-motion';
import { Bike, RefreshCw, Plus, Bell, Map, Settings, Clock, AlertCircle, Download } from 'lucide-react';

interface RiderHeaderProps {
  lastSyncedAt: string | null;
  isStale: boolean;
  showMap: boolean;
  syncing: boolean;
  refreshing: boolean;
  error: string | null;
  onToggleMap: () => void;
  onManageTeams: () => void;
  onNotifyAll: () => void;
  onNewRider: () => void;
  onSync: () => void;
  exporting: boolean;
  onExportCSV: () => void;
}

export default function RiderHeader({
  lastSyncedAt,
  isStale,
  showMap,
  syncing,
  refreshing,
  error,
  onToggleMap,
  onManageTeams,
  onNotifyAll,
  onNewRider,
  onSync,
  exporting,
  onExportCSV,
}: RiderHeaderProps) {
  return (
    <div className="mb-6">
      <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        {/* Left: title + subtitle + badge */}
        <div className="min-w-0">
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Bike className="w-7 h-7 text-primary-600 flex-shrink-0" />
            Gestione Rider
          </h1>
          <div className="flex items-center flex-wrap gap-2 mt-1">
            <p className="text-gray-500 text-sm">Gestisci i tuoi rider e le consegne in tempo reale</p>
            {lastSyncedAt && (
              <span
                className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap ${
                  isStale
                    ? 'bg-yellow-100 text-yellow-700 border border-yellow-200'
                    : 'bg-green-100 text-green-700 border border-green-200'
                }`}
              >
                <Clock className="w-3 h-3 mr-1" />
                {isStale ? 'Dati non aggiornati' : 'Sincronizzato'} ·{' '}
                {new Date(lastSyncedAt).toLocaleTimeString('it-IT', {
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </span>
            )}
          </div>
        </div>

        {/* Right: buttons in two rows */}
        <div className="flex flex-col gap-2 flex-shrink-0">
          {/* Row 1 */}
          <div className="flex flex-wrap items-center gap-2">
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={onToggleMap}
              className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors ${
                showMap
                  ? 'bg-primary-600 text-white'
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
              }`}
            >
              <Map className="w-4 h-4" />
              {showMap ? 'Chiudi Mappa' : 'Mappa Live'}
            </motion.button>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={onManageTeams}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-colors border border-gray-200"
            >
              <Settings className="w-4 h-4" />
              Gestisci Team
            </motion.button>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={onNotifyAll}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors"
            >
              <Bell className="w-4 h-4" />
              Notifica tutti
            </motion.button>
          </div>
          {/* Row 2 */}
          <div className="flex flex-wrap items-center gap-2 justify-end">
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={onNewRider}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
            >
              <Plus className="w-4 h-4" />
              Nuovo Rider
            </motion.button>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={onExportCSV}
              disabled={exporting}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-colors border border-gray-200 disabled:opacity-50"
            >
              <Download className={`w-4 h-4 ${exporting ? 'animate-pulse' : ''}`} />
              {exporting ? 'Esportazione...' : 'Esporta CSV'}
            </motion.button>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={onSync}
              disabled={syncing || refreshing}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-colors border border-gray-200 disabled:opacity-50"
            >
              <RefreshCw className={`w-4 h-4 ${syncing || refreshing ? 'animate-spin' : ''}`} />
              {syncing ? 'Sync...' : 'Sincronizza'}
            </motion.button>
          </div>
        </div>
      </div>

      {/* Stale Data Warning */}
      {isStale && !error && (
        <motion.div
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-3"
        >
          <AlertCircle className="w-5 h-5 text-yellow-600" />
          <span className="text-yellow-700">
            I dati potrebbero non essere aggiornati. Clicca "Sincronizza" per aggiornare.
          </span>
        </motion.div>
      )}

      {/* Error Alert */}
      {error && (
        <motion.div
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3"
        >
          <AlertCircle className="w-5 h-5 text-red-600" />
          <span className="text-red-700">{error}</span>
        </motion.div>
      )}
    </div>
  );
}
