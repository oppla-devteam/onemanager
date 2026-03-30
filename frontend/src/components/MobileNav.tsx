import React, { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { X, Menu, ChevronDown } from 'lucide-react';
import {
  LayoutDashboard,
  Users,
  Target,
  ShoppingCart,
  Truck,
  FileText,
  CreditCard,
  CheckSquare,
  Briefcase,
  LogOut,
  Bike,
  Building2,
  UtensilsCrossed,
  MapPin,
  Shield,
  Settings,
  Trash2
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

interface NavItem {
  to: string
  icon: React.ElementType
  label: string
  permission: string
}

interface NavGroup {
  id: string
  label: string
  items: NavItem[]
}

const navDashboard: NavItem = { to: '/', icon: LayoutDashboard, label: 'Dashboard', permission: 'dashboard' }

const navGroups: NavGroup[] = [
  {
    id: 'crm',
    label: 'CRM',
    items: [
      { to: '/clients', icon: Users, label: 'Clienti', permission: 'clients' },
      { to: '/leads', icon: Target, label: 'Lead', permission: 'clients' },
    ]
  },
  {
    id: 'operations',
    label: 'Operazioni',
    items: [
      { to: '/orders', icon: ShoppingCart, label: 'Ordini', permission: 'orders' },
      { to: '/deliveries', icon: Truck, label: 'Consegne', permission: 'orders' },
      { to: '/riders', icon: Bike, label: 'Riders', permission: 'orders' },
      { to: '/partner-protection', icon: Shield, label: 'Protezione', permission: 'orders' },
      { to: '/delivery-zones', icon: MapPin, label: 'Zone', permission: 'orders' },
      { to: '/menus', icon: UtensilsCrossed, label: 'Menu', permission: 'menu' },
    ]
  },
  {
    id: 'invoicing',
    label: 'Fatturazione',
    items: [
      { to: '/invoices', icon: FileText, label: 'Fatture', permission: 'invoices' },
      { to: '/suppliers', icon: Building2, label: 'Fornitori', permission: 'invoices' },
      { to: '/payments', icon: CreditCard, label: 'Pagamenti', permission: 'invoices' },
    ]
  },
  {
    id: 'management',
    label: 'Gestione',
    items: [
      { to: '/tasks', icon: CheckSquare, label: 'Task', permission: 'tasks' },
      { to: '/contracts', icon: Briefcase, label: 'Contratti', permission: 'contracts' },
    ]
  },
]

const bottomItems: NavItem[] = [
  { to: '/trash', icon: Trash2, label: 'Cestino', permission: '' },
  { to: '/settings', icon: Settings, label: 'Impostazioni', permission: '' },
]

export default function MobileNav() {
  const [isOpen, setIsOpen] = useState(false);
  const [expandedGroups, setExpandedGroups] = useState<Record<string, boolean>>(
    { crm: true, operations: true, invoicing: true, management: true }
  );
  const { logout, hasPermission } = useAuth();

  const toggleGroup = (id: string) => {
    setExpandedGroups(prev => ({ ...prev, [id]: !prev[id] }))
  }

  const close = () => setIsOpen(false)

  return (
    <>
      {/* Mobile Menu Button - Bottom Right */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="lg:hidden fixed bottom-6 right-6 z-50 w-14 h-14 bg-primary-600 rounded-full shadow-lg flex items-center justify-center hover:bg-primary-700 hover:scale-110 transition-all"
      >
        {isOpen ? (
          <X className="w-6 h-6 text-white" />
        ) : (
          <Menu className="w-6 h-6 text-white" />
        )}
      </button>

      {/* Overlay */}
      {isOpen && (
        <div
          className="lg:hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-40"
          onClick={close}
        />
      )}

      {/* Mobile Menu */}
      <div
        className={`lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 shadow-lg transition-transform duration-300 ${
          isOpen ? 'translate-y-0' : 'translate-y-full'
        }`}
      >
        <div className="max-h-[75vh] overflow-y-auto">
          <div className="p-4 pb-24">
            <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-4">Menu</h2>

            {/* Dashboard */}
            {hasPermission(navDashboard.permission) && (
              <div className="mb-3">
                <NavLink
                  to={navDashboard.to}
                  end
                  onClick={close}
                  className={({ isActive }) =>
                    `flex flex-col items-center gap-2 p-4 rounded-xl transition-all ${
                      isActive
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    }`
                  }
                >
                  <navDashboard.icon className="w-6 h-6" />
                  <span className="text-xs font-medium text-center">{navDashboard.label}</span>
                </NavLink>
              </div>
            )}

            {/* Groups */}
            {navGroups.map(group => {
              const visibleItems = group.items.filter(
                item => !item.permission || hasPermission(item.permission)
              )
              if (visibleItems.length === 0) return null
              const isGroupOpen = expandedGroups[group.id] !== false

              return (
                <div key={group.id} className="mb-3">
                  {/* Group header */}
                  <button
                    onClick={() => toggleGroup(group.id)}
                    className="w-full flex items-center justify-between px-1 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 hover:text-gray-600 transition-colors"
                  >
                    <span>{group.label}</span>
                    <ChevronDown
                      className={`w-3.5 h-3.5 transition-transform duration-200 ${isGroupOpen ? '' : '-rotate-90'}`}
                    />
                  </button>

                  {/* Group items grid */}
                  {isGroupOpen && (
                    <nav className="grid grid-cols-3 gap-2">
                      {visibleItems.map(item => (
                        <NavLink
                          key={item.to}
                          to={item.to}
                          onClick={close}
                          className={({ isActive }) =>
                            `flex flex-col items-center gap-2 p-3 rounded-xl transition-all ${
                              isActive
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                            }`
                          }
                        >
                          <item.icon className="w-5 h-5" />
                          <span className="text-xs font-medium text-center leading-tight">{item.label}</span>
                        </NavLink>
                      ))}
                    </nav>
                  )}
                </div>
              )
            })}

            {/* Bottom items + Logout */}
            <div className="mt-3 pt-3 border-t border-gray-200">
              <nav className="grid grid-cols-3 gap-2">
                {bottomItems.map(item => (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    onClick={close}
                    className={({ isActive }) =>
                      `flex flex-col items-center gap-2 p-3 rounded-xl transition-all ${
                        isActive
                          ? 'bg-primary-600 text-white'
                          : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                      }`
                    }
                  >
                    <item.icon className="w-5 h-5" />
                    <span className="text-xs font-medium text-center">{item.label}</span>
                  </NavLink>
                ))}

                <button
                  onClick={() => { logout(); close(); }}
                  className="flex flex-col items-center gap-2 p-3 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-all"
                >
                  <LogOut className="w-5 h-5" />
                  <span className="text-xs font-medium text-center">Logout</span>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
