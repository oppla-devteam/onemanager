import { Navigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

interface ProtectedRouteProps {
  children: React.ReactNode
  permission?: string
}

export default function ProtectedRoute({ children, permission }: ProtectedRouteProps) {
  const { hasPermission, user } = useAuth()

  // Se non c'è permesso richiesto, mostra il contenuto
  if (!permission) {
    return <>{children}</>
  }

  // Se l'utente non ha il permesso, reindirizza alla prima pagina disponibile
  if (!hasPermission(permission)) {
    // Trova la prima pagina a cui l'utente ha accesso
    const availablePages = [
      { path: '/', permission: 'dashboard' },
      { path: '/clients', permission: 'clients' },
      { path: '/orders', permission: 'orders' },
      { path: '/riders', permission: 'riders' },
      { path: '/deliveries', permission: 'deliveries' },
      { path: '/tasks', permission: 'tasks' },
      { path: '/contracts', permission: 'contracts' },
      { path: '/invoices', permission: 'invoices' },
    ]

    const firstAvailable = availablePages.find(page => hasPermission(page.permission))
    const redirectTo = firstAvailable ? firstAvailable.path : '/clients'
    
    return <Navigate to={redirectTo} replace />
  }

  return <>{children}</>
}
