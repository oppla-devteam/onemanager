import { motion } from 'framer-motion';
import {
  Bike,
  Car,
  Phone,
  Mail,
  MapPin,
  Lock,
  Unlock,
  Trash2,
  Edit,
  Package,
  Users,
  Truck,
  MessageSquare,
} from 'lucide-react';
import { Rider, statusColors, statusLabels } from './types';

interface RiderCardProps {
  rider: Rider;
  index: number;
  onEdit: (rider: Rider) => void;
  onToggleBlock: (rider: Rider) => void;
  onDelete: (rider: Rider) => void;
  onViewTasks: (rider: Rider) => void;
  onNotify: (rider: Rider) => void;
  onAssignTeam: (rider: Rider) => void;
}

const getTransportIcon = (type: string | number) => {
  const typeStr = typeof type === 'number' ? type.toString() : type;
  switch (typeStr) {
    case 'car':
    case '1':
      return Car;
    case 'truck':
    case '6':
      return Truck;
    default:
      return Bike;
  }
};

export default function RiderCard({
  rider,
  index,
  onEdit,
  onToggleBlock,
  onDelete,
  onViewTasks,
  onNotify,
  onAssignTeam,
}: RiderCardProps) {
  const TransportIcon = getTransportIcon(rider.transport_type);

  return (
    <motion.div
      initial={{ opacity: 0, scale: 0.9 }}
      animate={{ opacity: 1, scale: 1 }}
      exit={{ opacity: 0, scale: 0.9 }}
      transition={{ delay: index * 0.05 }}
      className={`glass-card overflow-hidden ${rider.is_blocked ? 'border-red-500/30 bg-red-500/10' : ''}`}
    >
      {/* Card Header */}
      <div className={`p-4 border-b ${rider.is_blocked ? 'border-red-500/30' : 'border-gray-200'}`}>
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-3">
            <div
              className={`w-12 h-12 rounded-full flex items-center justify-center ${
                rider.status === 'available'
                  ? 'bg-green-500/20'
                  : rider.status === 'busy'
                    ? 'bg-yellow-500/20'
                    : 'bg-slate-600/50'
              }`}
            >
              <TransportIcon
                className={`w-6 h-6 ${
                  rider.status === 'available'
                    ? 'text-green-400'
                    : rider.status === 'busy'
                      ? 'text-yellow-400'
                      : 'text-gray-500'
                }`}
              />
            </div>
            <div>
              <h3 className="font-semibold text-white flex items-center gap-2">
                {rider.name || rider.username}
                {rider.is_blocked && <Lock className="w-4 h-4 text-red-400" />}
              </h3>
              <span
                className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${statusColors[rider.status]}`}
              >
                {statusLabels[rider.status]}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Card Body */}
      <div className="p-4 space-y-2">
        {rider.phone && (
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <Phone className="w-4 h-4 text-gray-500" />
            <a href={`tel:${rider.phone}`} className="hover:text-primary-400">
              {rider.phone}
            </a>
          </div>
        )}
        {rider.email && (
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <Mail className="w-4 h-4 text-gray-500" />
            <a href={`mailto:${rider.email}`} className="hover:text-primary-400 truncate">
              {rider.email}
            </a>
          </div>
        )}
        {rider.team_name ? (
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <Users className="w-4 h-4 text-gray-500" />
            <span>{rider.team_name}</span>
          </div>
        ) : (
          <button
            onClick={() => onAssignTeam(rider)}
            className="flex items-center gap-2 text-sm text-primary-400 hover:text-primary-300"
          >
            <Users className="w-4 h-4" />
            <span>Assegna a team</span>
          </button>
        )}
        {rider.latitude && rider.longitude && (
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <MapPin className="w-4 h-4 text-gray-500" />
            <a
              href={`https://www.google.com/maps?q=${rider.latitude},${rider.longitude}`}
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-primary-400"
            >
              Vedi posizione
            </a>
          </div>
        )}
      </div>

      {/* Card Actions */}
      <div className="p-3 bg-gray-50/50 border-t border-gray-200 flex items-center justify-between">
        <div className="flex items-center gap-1">
          <button
            onClick={() => onViewTasks(rider)}
            className="p-2 text-gray-500 hover:text-primary-400 hover:bg-primary-500/20 rounded-lg transition-colors"
            title="Vedi consegne"
          >
            <Package className="w-4 h-4" />
          </button>
          <button
            onClick={() => onNotify(rider)}
            className="p-2 text-gray-500 hover:text-amber-400 hover:bg-amber-500/20 rounded-lg transition-colors"
            title="Invia notifica"
          >
            <MessageSquare className="w-4 h-4" />
          </button>
          <button
            onClick={() => onEdit(rider)}
            className="p-2 text-gray-500 hover:text-primary-400 hover:bg-primary-500/20 rounded-lg transition-colors"
            title="Modifica"
          >
            <Edit className="w-4 h-4" />
          </button>
          <button
            onClick={() => onAssignTeam(rider)}
            className="p-2 text-gray-500 hover:text-purple-400 hover:bg-purple-500/20 rounded-lg transition-colors"
            title="Assegna a team"
          >
            <Users className="w-4 h-4" />
          </button>
        </div>
        <div className="flex items-center gap-1">
          <button
            onClick={() => onToggleBlock(rider)}
            className={`p-2 rounded-lg transition-colors ${
              rider.is_blocked
                ? 'text-green-400 hover:text-green-300 hover:bg-green-500/20'
                : 'text-red-400 hover:text-red-300 hover:bg-red-500/20'
            }`}
            title={rider.is_blocked ? 'Sblocca' : 'Blocca'}
          >
            {rider.is_blocked ? <Unlock className="w-4 h-4" /> : <Lock className="w-4 h-4" />}
          </button>
          <button
            onClick={() => onDelete(rider)}
            className="p-2 text-red-400 hover:text-red-300 hover:bg-red-500/20 rounded-lg transition-colors"
            title="Elimina"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      </div>
    </motion.div>
  );
}
