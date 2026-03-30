import React, { createContext, useContext, useState, useEffect, useCallback } from 'react'

interface User {
  username: string
  email?: string
  name?: string
  bink_username?: string
  role?: string
  roles?: string[]
  permissions?: string[]
}

interface LoginWithBinkResult {
  success: boolean
  message?: string
}

interface AuthContextType {
  isAuthenticated: boolean
  login: (username: string, password: string) => Promise<boolean>
  loginWithBink: (code: string, redirectUri: string) => Promise<LoginWithBinkResult>
  logout: () => Promise<void>
  user: User | null
  hasPermission: (permission: string) => boolean
  isAdmin: boolean
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const [user, setUser] = useState<User | null>(null)

  useEffect(() => {
    const token = localStorage.getItem('token')
    const savedUser = localStorage.getItem('user')

    if (token && savedUser) {
      setIsAuthenticated(true)
      setUser(JSON.parse(savedUser))
    }
  }, [])

  const hasPermission = (permission: string): boolean => {
    if (!user) return false

    if (user.roles && user.roles.length > 0) {
      if (user.roles.includes('admin') || user.roles.includes('super-admin')) return true

      const permissionMap: Record<string, string[]> = {
        'dashboard': ['view-dashboard'],
        'clients': ['view-clients'],
        'orders': ['view-orders'],
        'invoices': ['view-invoices'],
        'tasks': ['view-tasks'],
        'contracts': ['view-contracts'],
        'deliveries': ['view-deliveries', 'view-orders'],
        'accounting': ['view-accounting'],
        'crm': ['view-crm'],
        'menu': ['view-menu'],
        'riders': ['manage-riders', 'view-orders'],
      }

      const mappedPermissions = permissionMap[permission] || []
      return mappedPermissions.some(p => user.permissions?.includes(p))
    }

    if (user.role === 'admin' || user.role === 'super-admin') return true
    if (user.permissions?.includes(permission)) return true

    return false
  }

  const setAuthData = (data: any, identifier: string) => {
    const userData: User = {
      username: data.user?.name || data.user?.email || identifier,
      email: data.user?.email,
      name: data.user?.name,
      bink_username: data.user?.bink_username,
      role: data.user?.role,
      roles: data.user?.roles || [],
      permissions: data.user?.permissions || []
    }

    setIsAuthenticated(true)
    setUser(userData)
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(userData))
  }

  const login = async (username: string, password: string): Promise<boolean> => {
    try {
      const response = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ email: username, password })
      })

      if (!response.ok) return false

      const data = await response.json()
      if (data.token) {
        setAuthData(data, username)
        return true
      }
      return false
    } catch (error) {
      console.error('Login error:', error)
      return false
    }
  }

  const loginWithBink = useCallback(async (code: string, redirectUri: string): Promise<LoginWithBinkResult> => {
    try {
      const response = await fetch(`${API_URL}/auth/bink`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ code, redirect_uri: redirectUri })
      })

      const data = await response.json()

      if (!response.ok) {
        return {
          success: false,
          message: data.message || 'Errore durante il login con Bink.'
        }
      }

      if (data.token) {
        setAuthData(data, data.user?.bink_username || 'bink_user')
        return { success: true }
      }

      return { success: false, message: 'Token non ricevuto.' }
    } catch (error) {
      console.error('Bink login error:', error)
      return { success: false, message: 'Errore di connessione.' }
    }
  }, [])

  const logout = async () => {
    try {
      const token = localStorage.getItem('token')
      if (token) {
        await fetch(`${API_URL}/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        })
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      setIsAuthenticated(false)
      setUser(null)
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  const isAdmin = !!(
    user?.roles?.includes('admin') ||
    user?.roles?.includes('super-admin') ||
    user?.role === 'admin' ||
    user?.role === 'super-admin'
  )

  const value = {
    isAuthenticated,
    login,
    loginWithBink,
    logout,
    user,
    hasPermission,
    isAdmin
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
