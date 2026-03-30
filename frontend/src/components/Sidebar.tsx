import { NavLink } from 'react-router-dom'
import { useState, useEffect } from 'react'
import { useAuth } from '../contexts/AuthContext'
import {
  LayoutDashboard,
  Users,
  Target,
  ShoppingCart,
  Truck,
  FileText,
  CreditCard,
  Settings,
  CheckSquare,
  Briefcase,
  LogOut,
  ChevronLeft,
  ChevronDown,
  Bike,
  Building2,
  UtensilsCrossed,
  MapPin,
  Shield,
  Trash2
} from 'lucide-react'

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
      { to: '/deliveries', icon: Truck, label: 'Consegne', permission: 'deliveries' },
      { to: '/riders', icon: Bike, label: 'Riders', permission: 'riders' },
      { to: '/partner-protection', icon: Shield, label: 'Protezione Partner', permission: 'orders' },
      { to: '/delivery-zones', icon: MapPin, label: 'Zone Consegna', permission: 'orders' },
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

interface SidebarProps {
  isCollapsed?: boolean
  onToggle?: () => void
}

export default function Sidebar({ isCollapsed = false, onToggle }: SidebarProps) {
  const [deferredPrompt, setDeferredPrompt] = useState<any>(null)
  const [isInstalled, setIsInstalled] = useState(false)
  const [expandedGroups, setExpandedGroups] = useState<Record<string, boolean>>(() => {
    try {
      const saved = localStorage.getItem('sidebar-groups')
      return saved ? JSON.parse(saved) : { crm: true, operations: true, invoicing: true, management: true }
    } catch {
      return { crm: true, operations: true, invoicing: true, management: true }
    }
  })
  const { logout, hasPermission } = useAuth()

  useEffect(() => {
    if (window.matchMedia('(display-mode: standalone)').matches) {
      setIsInstalled(true)
    }

    const handler = (e: Event) => {
      e.preventDefault()
      setDeferredPrompt(e)
    }

    window.addEventListener('beforeinstallprompt', handler)
    return () => window.removeEventListener('beforeinstallprompt', handler)
  }, [])

  const toggleGroup = (id: string) => {
    setExpandedGroups(prev => {
      const next = { ...prev, [id]: !prev[id] }
      localStorage.setItem('sidebar-groups', JSON.stringify(next))
      return next
    })
  }

  const handleInstallClick = async () => {
    if (!deferredPrompt) {
      alert('La PWA è già installata o il browser non supporta l\'installazione')
      return
    }
    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice
    if (outcome === 'accepted') setIsInstalled(true)
    setDeferredPrompt(null)
  }

  return (
    <aside className={`glass-sidebar z-50 transition-all duration-300 ${isCollapsed ? 'w-20' : 'w-64'}`}>
      <div className="flex h-full flex-col p-4">
        {/* Logo e Toggle Button */}
        <div className="mb-6 px-4 pt-4 flex items-center justify-between shrink-0">
          {!isCollapsed && (
            <h1 className="text-2xl font-bold">
              <span className="text-primary-600">Oppla One</span>
              <span className="ml-2 text-gray-400 text-sm font-normal">Manager</span>
            </h1>
          )}
          {onToggle && (
            <button
              onClick={onToggle}
              className="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
              title={isCollapsed ? 'Espandi menu' : 'Chiudi menu'}
            >
              <ChevronLeft className={`h-5 w-5 text-gray-400 transition-transform ${isCollapsed ? 'rotate-180' : ''}`} />
            </button>
          )}
        </div>

        {/* Navigation - scrollable */}
        <nav className="flex-1 overflow-y-auto overflow-x-hidden min-h-0 pr-1 space-y-1">
          {/* Dashboard */}
          {hasPermission(navDashboard.permission) && (
            <NavLink
              to={navDashboard.to}
              end
              className={({ isActive }) =>
                `glass-nav-item ${isActive ? 'active' : ''} ${isCollapsed ? 'justify-center' : ''}`
              }
              title={isCollapsed ? navDashboard.label : undefined}
            >
              <navDashboard.icon className="h-5 w-5 shrink-0" />
              {!isCollapsed && <span>{navDashboard.label}</span>}
            </NavLink>
          )}

          {isCollapsed ? (
            /* Collapsed: flat list of all items */
            navGroups
              .flatMap(g => g.items)
              .filter(item => !item.permission || hasPermission(item.permission))
              .map(item => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  className={({ isActive }) =>
                    `glass-nav-item justify-center ${isActive ? 'active' : ''}`
                  }
                  title={item.label}
                >
                  <item.icon className="h-5 w-5 shrink-0" />
                </NavLink>
              ))
          ) : (
            /* Expanded: grouped with collapsible sections */
            navGroups.map(group => {
              const visibleItems = group.items.filter(
                item => !item.permission || hasPermission(item.permission)
              )
              if (visibleItems.length === 0) return null
              const isOpen = expandedGroups[group.id] !== false

              return (
                <div key={group.id} className="mt-2">
                  {/* Group header */}
                  <button
                    onClick={() => toggleGroup(group.id)}
                    className="w-full flex items-center justify-between px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded-md hover:bg-gray-50 dark:hover:bg-gray-800"
                  >
                    <span>{group.label}</span>
                    <ChevronDown
                      className={`h-3.5 w-3.5 transition-transform duration-200 ${isOpen ? '' : '-rotate-90'}`}
                    />
                  </button>

                  {/* Group items */}
                  {isOpen && (
                    <div className="mt-0.5 space-y-0.5">
                      {visibleItems.map(item => (
                        <NavLink
                          key={item.to}
                          to={item.to}
                          className={({ isActive }) =>
                            `glass-nav-item pl-5 ${isActive ? 'active' : ''}`
                          }
                        >
                          <item.icon className="h-5 w-5 shrink-0" />
                          <span>{item.label}</span>
                        </NavLink>
                      ))}
                    </div>
                  )}
                </div>
              )
            })
          )}
        </nav>

        {/* Footer: Cestino, Settings, Logout */}
        <div className="mt-2 pt-2 border-t border-gray-200 space-y-1 shrink-0">
          <NavLink
            to="/trash"
            className={({ isActive }) =>
              `glass-nav-item ${isActive ? 'active' : ''} ${isCollapsed ? 'justify-center' : ''}`
            }
            title={isCollapsed ? 'Cestino' : undefined}
          >
            <Trash2 className="h-5 w-5 shrink-0" />
            {!isCollapsed && <span>Cestino</span>}
          </NavLink>

          <NavLink
            to="/settings"
            className={({ isActive }) =>
              `glass-nav-item ${isActive ? 'active' : ''} ${isCollapsed ? 'justify-center' : ''}`
            }
            title={isCollapsed ? 'Impostazioni' : undefined}
          >
            <Settings className="h-5 w-5 shrink-0" />
            {!isCollapsed && <span>Impostazioni</span>}
          </NavLink>

          <button
            onClick={logout}
            className={`glass-nav-item w-full text-red-500 hover:text-red-600 hover:bg-red-50 ${isCollapsed ? 'justify-center' : ''}`}
            title={isCollapsed ? 'Logout' : undefined}
          >
            <LogOut className="h-5 w-5 shrink-0" />
            {!isCollapsed && <span>Logout</span>}
          </button>
        </div>
      </div>
    </aside>
  )
}
