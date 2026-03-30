import { motion } from 'framer-motion';
import { Users, Wifi, UserCheck, Package, UserX } from 'lucide-react';
import { Summary } from './types';

interface RiderSummaryProps {
  summary: Summary;
}

export default function RiderSummary({ summary }: RiderSummaryProps) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="glass-card p-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-gray-500">Totale Rider</p>
            <p className="text-2xl font-bold text-white">{summary.total}</p>
          </div>
          <div className="w-12 h-12 bg-primary-500/20 rounded-xl flex items-center justify-center">
            <Users className="w-6 h-6 text-primary-400" />
          </div>
        </div>
      </motion.div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.05 }}
        className="glass-card p-4"
      >
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-gray-500">Online</p>
            <p className="text-2xl font-bold text-emerald-400">{summary.online ?? '-'}</p>
          </div>
          <div className="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
            <Wifi className="w-6 h-6 text-emerald-400" />
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
            <p className="text-sm text-gray-500">Disponibili</p>
            <p className="text-2xl font-bold text-green-400">{summary.available}</p>
          </div>
          <div className="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
            <UserCheck className="w-6 h-6 text-green-400" />
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
            <p className="text-sm text-gray-500">In Consegna</p>
            <p className="text-2xl font-bold text-yellow-400">{summary.busy}</p>
          </div>
          <div className="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
            <Package className="w-6 h-6 text-yellow-400" />
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
            <p className="text-sm text-gray-500">Offline</p>
            <p className="text-2xl font-bold text-gray-400">{summary.offline}</p>
          </div>
          <div className="w-12 h-12 bg-slate-600/50 rounded-xl flex items-center justify-center">
            <UserX className="w-6 h-6 text-gray-500" />
          </div>
        </div>
      </motion.div>
    </div>
  );
}
