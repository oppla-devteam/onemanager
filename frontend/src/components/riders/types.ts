export interface Rider {
  id: string;
  fleet_id: string;
  username: string;
  first_name: string;
  last_name: string;
  name: string;
  email: string | null;
  phone: string | null;
  status: 'available' | 'busy' | 'offline';
  status_code: number;
  is_blocked: boolean;
  transport_type: string;
  transport_type_code: number;
  latitude: number | null;
  longitude: number | null;
  last_updated: string | null;
  team_id: number | null;
  team_name: string | null;
  tags: string;
  profile_image: string | null;
  is_online?: boolean;
  minutes_since_update?: number | null;
  battery_level?: number | null;
}

export interface Team {
  team_id: number;
  team_name: string;
}

export interface RiderTask {
  job_id: number;
  order_id: string | null;
  customer_name: string;
  customer_phone: string | null;
  customer_address: string;
  pickup_address: string | null;
  scheduled_time: string | null;
  description: string | null;
  job_status: number;
  status_label: string;
}

export interface Summary {
  total: number;
  available: number;
  busy: number;
  offline: number;
  online?: number;
}

export interface RealtimeRider {
  fleet_id: string;
  name: string;
  phone: string | null;
  latitude: number | null;
  longitude: number | null;
  status: number;
  is_available: boolean;
  is_online: boolean;
  minutes_since_update: number | null;
  location_update_datetime: string | null;
  battery_level: number | null;
  transport_type: number;
  team_id: number | null;
}

export const statusColors = {
  available: 'bg-green-500/20 text-green-400 border-green-500/30',
  busy: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  offline: 'bg-slate-600/50 text-gray-500 border-gray-300/30',
};

export const statusLabels = {
  available: 'Disponibile',
  busy: 'In consegna',
  offline: 'Offline',
};
