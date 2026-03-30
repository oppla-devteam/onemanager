import { motion } from 'framer-motion'
import { useState, useEffect } from 'react'
import {
  User,
  Palette,
  Zap,
  Save,
  Users,
  Plus,
  Edit,
  Trash2,
  Shield,
  Eye,
  Key,
  Copy,
  Check,
  Clock,
  Sun,
  Moon,
  Monitor
} from 'lucide-react'
import { useAuth } from '../contexts/AuthContext'
import Modal from '../components/Modal'
import { ToastContainer } from '../components/Toast'
import { useToast } from '../hooks/useToast'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

type SettingsTab = 'profile' | 'appearance' | 'integrations' | 'users'

interface BinkUser {
  id: number
  bink_username: string
  display_name: string | null
  role: string
  permissions: string[]
}

interface ApiToken {
  id: number
  name: string
  created_at: string
  last_used_at: string | null
}

const AVAILABLE_PERMISSIONS = [
  { key: 'dashboard', label: 'Dashboard' },
  { key: 'clients', label: 'Clienti' },
  { key: 'orders', label: 'Ordini' },
  { key: 'invoices', label: 'Fatture' },
  { key: 'tasks', label: 'Task' },
  { key: 'contracts', label: 'Contratti' },
  { key: 'menu', label: 'Menu' },
]

export default function Settings() {
  const { user, isAdmin } = useAuth()
  const { toasts, removeToast, success, error } = useToast()
  const [activeTab, setActiveTab] = useState<SettingsTab>('profile')

  // Bink user management state
  const [binkUsers, setBinkUsers] = useState<BinkUser[]>([])
  const [loadingUsers, setLoadingUsers] = useState(false)
  const [isUserModalOpen, setIsUserModalOpen] = useState(false)
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false)
  const [editingUser, setEditingUser] = useState<BinkUser | null>(null)
  const [deletingUserId, setDeletingUserId] = useState<number | null>(null)
  const [savingUser, setSavingUser] = useState(false)
  const [userForm, setUserForm] = useState({
    bink_username: '',
    display_name: '',
    role: 'viewer',
    permissions: [] as string[],
  })

  // Theme state
  const [theme, setTheme] = useState<'light' | 'dark' | 'system'>(() => {
    return (localStorage.getItem('theme') as 'light' | 'dark' | 'system') || 'system'
  })

  const applyTheme = (newTheme: 'light' | 'dark' | 'system') => {
    setTheme(newTheme)
    localStorage.setItem('theme', newTheme)
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    if (newTheme === 'dark' || (newTheme === 'system' && prefersDark)) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  // API Token state
  const [apiTokens, setApiTokens] = useState<ApiToken[]>([])
  const [loadingTokens, setLoadingTokens] = useState(false)
  const [newTokenName, setNewTokenName] = useState('')
  const [creatingToken, setCreatingToken] = useState(false)
  const [newlyCreatedToken, setNewlyCreatedToken] = useState<string | null>(null)
  const [copiedToken, setCopiedToken] = useState(false)

  const tabs = [
    { id: 'profile' as const, label: 'Profilo', icon: User },
    { id: 'appearance' as const, label: 'Aspetto', icon: Palette },
    { id: 'integrations' as const, label: 'Integrazioni', icon: Zap },
    ...(isAdmin ? [{ id: 'users' as const, label: 'Gestione Utenti', icon: Users }] : []),
  ]

  const initials = user?.name
    ? user.name.split(' ').map((n: string) => n[0]).join('').toUpperCase().slice(0, 2)
    : 'OM'

  useEffect(() => {
    if (activeTab === 'users' && isAdmin) {
      loadUsers()
    }
    if (activeTab === 'integrations') {
      loadApiTokens()
    }
  }, [activeTab, isAdmin])

  const loadUsers = async () => {
    setLoadingUsers(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/admin/bink-users`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const data = await response.json()
        setBinkUsers(data)
      }
    } catch (err) {
      console.error('Errore caricamento utenti:', err)
    } finally {
      setLoadingUsers(false)
    }
  }

  // API Token functions
  const loadApiTokens = async () => {
    setLoadingTokens(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api-tokens`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const data = await response.json()
        setApiTokens(data)
      }
    } catch (err) {
      console.error('Errore caricamento token:', err)
    } finally {
      setLoadingTokens(false)
    }
  }

  const handleCreateToken = async () => {
    if (!newTokenName.trim()) return
    setCreatingToken(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api-tokens`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ name: newTokenName.trim() })
      })
      if (response.ok) {
        const data = await response.json()
        setNewlyCreatedToken(data.token)
        setNewTokenName('')
        await loadApiTokens()
        success('Token API creato!')
      } else {
        const data = await response.json()
        error(data.message || 'Errore durante la creazione del token')
      }
    } catch (err) {
      console.error('Errore creazione token:', err)
      error('Errore durante la creazione del token')
    } finally {
      setCreatingToken(false)
    }
  }

  const handleRevokeToken = async (tokenId: number) => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/api-tokens/${tokenId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        await loadApiTokens()
        success('Token revocato!')
      } else {
        error('Errore durante la revoca del token')
      }
    } catch (err) {
      console.error('Errore revoca token:', err)
      error('Errore durante la revoca del token')
    }
  }

  const copyToClipboard = async (text: string) => {
    await navigator.clipboard.writeText(text)
    setCopiedToken(true)
    setTimeout(() => setCopiedToken(false), 2000)
  }

  const openCreateUser = () => {
    setEditingUser(null)
    setUserForm({ bink_username: '', display_name: '', role: 'viewer', permissions: [] })
    setIsUserModalOpen(true)
  }

  const openEditUser = (u: BinkUser) => {
    setEditingUser(u)
    setUserForm({
      bink_username: u.bink_username,
      display_name: u.display_name || '',
      role: u.role,
      permissions: u.permissions || [],
    })
    setIsUserModalOpen(true)
  }

  const handleSaveUser = async (e: React.FormEvent) => {
    e.preventDefault()
    setSavingUser(true)

    try {
      const token = localStorage.getItem('token')
      const isEditing = !!editingUser
      const url = isEditing
        ? `${API_URL}/admin/bink-users/${editingUser.id}`
        : `${API_URL}/admin/bink-users`

      const body = {
        bink_username: userForm.bink_username,
        display_name: userForm.display_name || null,
        role: userForm.role,
        permissions: userForm.role === 'admin' ? [] : userForm.permissions,
      }

      const response = await fetch(url, {
        method: isEditing ? 'PUT' : 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(body)
      })

      if (response.ok) {
        setIsUserModalOpen(false)
        await loadUsers()
        success(isEditing ? 'Utente modificato!' : 'Utente aggiunto!')
      } else {
        const data = await response.json()
        error(data.message || 'Errore durante il salvataggio')
      }
    } catch (err) {
      console.error('Errore salvataggio utente:', err)
      error('Errore durante il salvataggio')
    } finally {
      setSavingUser(false)
    }
  }

  const handleDeleteUser = async () => {
    if (!deletingUserId) return

    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/admin/bink-users/${deletingUserId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })

      if (response.ok) {
        setIsDeleteModalOpen(false)
        setDeletingUserId(null)
        await loadUsers()
        success('Utente rimosso!')
      } else {
        const data = await response.json()
        error(data.message || 'Errore durante l\'eliminazione')
      }
    } catch (err) {
      console.error('Errore eliminazione utente:', err)
      error('Errore durante l\'eliminazione')
    }
  }

  const togglePermission = (key: string) => {
    setUserForm(prev => ({
      ...prev,
      permissions: prev.permissions.includes(key)
        ? prev.permissions.filter(p => p !== key)
        : [...prev.permissions, key]
    }))
  }

  const getRoleBadge = (role: string) => {
    switch (role) {
      case 'admin':
        return (
          <span className="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-amber-100 text-amber-700 border border-amber-200">
            <Shield className="w-3 h-3" />
            Admin
          </span>
        )
      default:
        return (
          <span className="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-primary-50 text-primary-600 border border-primary-200">
            <Eye className="w-3 h-3" />
            Viewer
          </span>
        )
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">
          <span className="text-gradient">Impostazioni</span>
        </h1>
        <p className="text-gray-500 mt-1">Configura il tuo account e le preferenze</p>
      </div>

      {/* Tabs Navigation */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="glass-card p-2"
      >
        <div className="flex gap-2 overflow-x-auto">
          {tabs.map((tab) => (
            <motion.button
              key={tab.id}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={() => setActiveTab(tab.id)}
              className={`px-4 py-3 rounded-lg font-medium transition-all whitespace-nowrap flex items-center gap-2 ${
                activeTab === tab.id
                  ? 'bg-primary-600 text-white'
                  : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'
              }`}
            >
              <tab.icon className="w-5 h-5" />
              {tab.label}
            </motion.button>
          ))}
        </div>
      </motion.div>

      {/* Profile Tab */}
      {activeTab === 'profile' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          <div className="glass-card p-6">
            <h2 className="text-xl font-semibold mb-6 flex items-center gap-2">
              <User className="w-5 h-5 text-primary-600" />
              Informazioni Profilo
            </h2>
            <div className="space-y-6">
              <div className="flex items-center gap-6">
                <div className="h-20 w-20 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-2xl font-semibold">
                  {initials}
                </div>
                <div>
                  <p className="text-lg font-semibold text-gray-900">{user?.name || 'Utente'}</p>
                  <p className="text-sm text-gray-500">{user?.bink_username ? `@${user.bink_username}` : user?.email || ''}</p>
                </div>
              </div>
            </div>
          </div>
        </motion.div>
      )}

      {/* Appearance Tab */}
      {activeTab === 'appearance' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          {/* Theme / Dark Mode */}
          <div className="glass-card p-6">
            <h2 className="text-xl font-semibold mb-6 flex items-center gap-2">
              <Palette className="w-5 h-5 text-primary-600" />
              Tema
            </h2>
            <div className="grid grid-cols-3 gap-3">
              {([
                { value: 'light' as const, label: 'Chiaro', icon: Sun, desc: 'Tema chiaro' },
                { value: 'dark' as const, label: 'Scuro', icon: Moon, desc: 'Tema scuro' },
                { value: 'system' as const, label: 'Sistema', icon: Monitor, desc: 'Segui il sistema' },
              ]).map((opt) => (
                <button
                  key={opt.value}
                  onClick={() => applyTheme(opt.value)}
                  className={`flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all ${
                    theme === opt.value
                      ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                      : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                  }`}
                >
                  <opt.icon className={`w-6 h-6 ${theme === opt.value ? 'text-primary-600' : 'text-gray-400'}`} />
                  <span className={`text-sm font-medium ${theme === opt.value ? 'text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400'}`}>
                    {opt.label}
                  </span>
                  <span className="text-xs text-gray-400">{opt.desc}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Other appearance settings */}
          <div className="glass-card p-6">
            <h2 className="text-xl font-semibold mb-6">Personalizzazione</h2>
            <div className="space-y-6">
              <div>
                <label className="block text-sm font-medium mb-2">Lingua</label>
                <select className="glass-input">
                  <option value="it">Italiano</option>
                  <option value="en">English</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium mb-2">Formato Data</label>
                <select className="glass-input">
                  <option value="dd/mm/yyyy">GG/MM/AAAA</option>
                  <option value="mm/dd/yyyy">MM/GG/AAAA</option>
                  <option value="yyyy-mm-dd">AAAA-MM-GG</option>
                </select>
              </div>
            </div>
          </div>
        </motion.div>
      )}

      {/* Integrations Tab */}
      {activeTab === 'integrations' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          {/* API Tokens / MCP Section */}
          <div className="glass-card p-6">
            <h2 className="text-xl font-semibold mb-2 flex items-center gap-2">
              <Key className="w-5 h-5 text-primary-600" />
              Token API
            </h2>
            <p className="text-sm text-gray-500 mb-6">
              Genera token per integrazioni esterne come il server MCP (Claude AI), script automatici o API di terze parti.
            </p>

            {/* Create new token */}
            <div className="flex gap-3 mb-6">
              <input
                type="text"
                value={newTokenName}
                onChange={(e) => setNewTokenName(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleCreateToken()}
                className="glass-input flex-1"
                placeholder="Nome del token (es. mcp-server, script-fatture...)"
              />
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={handleCreateToken}
                disabled={creatingToken || !newTokenName.trim()}
                className="glass-button-primary flex items-center gap-2 whitespace-nowrap"
              >
                <Plus className="w-4 h-4" />
                {creatingToken ? 'Creazione...' : 'Genera Token'}
              </motion.button>
            </div>

            {/* Newly created token display */}
            {newlyCreatedToken && (
              <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div className="flex items-center gap-2 mb-2">
                  <Check className="w-4 h-4 text-green-600" />
                  <p className="text-sm font-medium text-green-800">Token creato! Copialo ora, non sara piu visibile.</p>
                </div>
                <div className="flex items-center gap-2">
                  <code className="flex-1 text-xs bg-white px-3 py-2 rounded border border-green-300 font-mono break-all select-all">
                    {newlyCreatedToken}
                  </code>
                  <button
                    onClick={() => copyToClipboard(newlyCreatedToken)}
                    className="p-2 text-green-600 hover:text-green-800 transition-colors"
                    title="Copia"
                  >
                    {copiedToken ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                  </button>
                </div>
                <p className="text-xs text-gray-500 mt-2">
                  Per usarlo con il server MCP, inseriscilo come <code className="bg-gray-100 px-1 rounded">ONEMANAGER_AUTH_TOKEN</code> nella configurazione.
                </p>
                <button
                  onClick={() => setNewlyCreatedToken(null)}
                  className="text-xs text-gray-400 hover:text-gray-600 mt-2"
                >
                  Chiudi
                </button>
              </div>
            )}

            {/* Token list */}
            {loadingTokens ? (
              <div className="flex items-center justify-center py-8">
                <div className="animate-spin w-6 h-6 border-4 border-primary-600 border-t-transparent rounded-full"></div>
              </div>
            ) : apiTokens.length > 0 ? (
              <div className="space-y-2">
                {apiTokens.map((t) => (
                  <div key={t.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <div className="flex items-center gap-3">
                      <Key className="w-4 h-4 text-gray-400" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t.name.replace('api-', '')}</p>
                        <div className="flex items-center gap-3 text-xs text-gray-400">
                          <span>Creato: {new Date(t.created_at).toLocaleDateString('it-IT')}</span>
                          {t.last_used_at && (
                            <span className="flex items-center gap-1">
                              <Clock className="w-3 h-3" />
                              Ultimo uso: {new Date(t.last_used_at).toLocaleDateString('it-IT')}
                            </span>
                          )}
                        </div>
                      </div>
                    </div>
                    <button
                      onClick={() => handleRevokeToken(t.id)}
                      className="p-2 text-gray-400 hover:text-red-500 transition-colors"
                      title="Revoca token"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-400 text-sm">
                Nessun token API creato
              </div>
            )}
          </div>

          {/* External Integrations */}
          <div className="glass-card p-6">
            <h2 className="text-xl font-semibold mb-6 flex items-center gap-2">
              <Zap className="w-5 h-5 text-primary-600" />
              Integrazioni Esterne
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {[
                { name: 'Bink', desc: 'Autenticazione e gestione utenti' },
                { name: 'Stripe', desc: 'Pagamenti e abbonamenti' },
                { name: 'Fatture in Cloud', desc: 'Fatturazione elettronica e SDI' },
                { name: 'Tookan', desc: 'Gestione rider e consegne' },
                { name: 'OPPLA Platform', desc: 'Sincronizzazione ordini e ristoranti' },
              ].map((integration, index) => (
                <div key={index} className="glass-card p-4">
                  <h3 className="font-semibold text-gray-900">{integration.name}</h3>
                  <p className="text-sm text-gray-500">{integration.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </motion.div>
      )}

      {/* User Management Tab (Admin Only) - Bink Usernames */}
      {activeTab === 'users' && isAdmin && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          <div className="glass-card p-6">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h2 className="text-xl font-semibold flex items-center gap-2">
                  <Users className="w-5 h-5 text-primary-600" />
                  Utenti Autorizzati
                </h2>
                <p className="text-sm text-gray-500 mt-1">Gestisci gli username Bink autorizzati ad accedere</p>
              </div>
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={openCreateUser}
                className="glass-button-primary flex items-center gap-2"
              >
                <Plus className="w-4 h-4" />
                Aggiungi Utente
              </motion.button>
            </div>

            {loadingUsers ? (
              <div className="flex items-center justify-center py-12">
                <div className="animate-spin w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full"></div>
              </div>
            ) : (
              <div className="space-y-3">
                {binkUsers.map((u) => (
                  <div key={u.id} className="glass-card p-4 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                      <div className="h-10 w-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-sm font-semibold">
                        {(u.display_name || u.bink_username).slice(0, 2).toUpperCase()}
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <p className="font-medium text-gray-900">@{u.bink_username}</p>
                          {getRoleBadge(u.role)}
                        </div>
                        {u.display_name && (
                          <p className="text-sm text-gray-500">{u.display_name}</p>
                        )}
                        {u.role !== 'admin' && u.permissions && u.permissions.length > 0 && (
                          <div className="flex flex-wrap gap-1 mt-1">
                            {u.permissions.map(p => (
                              <span key={p} className="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">
                                {AVAILABLE_PERMISSIONS.find(ap => ap.key === p)?.label || p}
                              </span>
                            ))}
                          </div>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <button
                        onClick={() => openEditUser(u)}
                        className="p-2 text-gray-400 hover:text-primary-600 transition-colors"
                        title="Modifica"
                      >
                        <Edit className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => {
                          setDeletingUserId(u.id)
                          setIsDeleteModalOpen(true)
                        }}
                        className="p-2 text-gray-400 hover:text-red-500 transition-colors"
                        title="Rimuovi"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ))}
                {binkUsers.length === 0 && (
                  <div className="text-center py-12 text-gray-400">
                    Nessun utente autorizzato
                  </div>
                )}
              </div>
            )}
          </div>
        </motion.div>
      )}

      {/* Create/Edit Bink User Modal */}
      <Modal
        isOpen={isUserModalOpen}
        onClose={() => setIsUserModalOpen(false)}
        title={editingUser ? 'Modifica Utente' : 'Aggiungi Utente Bink'}
      >
        <form onSubmit={handleSaveUser} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">Username Bink *</label>
            <input
              type="text"
              required
              value={userForm.bink_username}
              onChange={(e) => setUserForm({ ...userForm, bink_username: e.target.value })}
              className="glass-input w-full"
              placeholder="username"
              disabled={!!editingUser}
            />
            <p className="text-xs text-gray-400 mt-1">Lo username dell'account Bink dell'utente</p>
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Nome visualizzato</label>
            <input
              type="text"
              value={userForm.display_name}
              onChange={(e) => setUserForm({ ...userForm, display_name: e.target.value })}
              className="glass-input w-full"
              placeholder="Nome e cognome (opzionale)"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Ruolo</label>
            <select
              value={userForm.role}
              onChange={(e) => setUserForm({ ...userForm, role: e.target.value })}
              className="glass-input w-full"
            >
              <option value="viewer">Viewer</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          {/* Permission toggles */}
          <div>
            <label className="block text-sm font-medium mb-3">Permessi Sezioni</label>
            {userForm.role === 'admin' ? (
              <p className="text-sm text-gray-500 italic">
                Gli admin hanno accesso a tutte le sezioni
              </p>
            ) : (
              <div className="grid grid-cols-2 gap-2">
                {AVAILABLE_PERMISSIONS.map((perm) => {
                  const isActive = userForm.permissions.includes(perm.key)
                  return (
                    <button
                      key={perm.key}
                      type="button"
                      onClick={() => togglePermission(perm.key)}
                      className={`flex items-center justify-between px-3 py-2 rounded-lg border transition-all text-sm ${
                        isActive
                          ? 'bg-primary-50 border-primary-300 text-primary-700'
                          : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-gray-300'
                      }`}
                    >
                      <span>{perm.label}</span>
                      <div className={`w-8 h-4 rounded-full transition-colors ${isActive ? 'bg-primary-600' : 'bg-gray-300'}`}>
                        <div className={`w-3 h-3 rounded-full bg-white transform transition-transform mt-0.5 ${isActive ? 'translate-x-4.5 ml-[18px]' : 'ml-0.5'}`} />
                      </div>
                    </button>
                  )
                })}
              </div>
            )}
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={() => setIsUserModalOpen(false)}
              className="glass-button flex-1"
            >
              Annulla
            </button>
            <button
              type="submit"
              disabled={savingUser}
              className="glass-button-primary flex-1"
            >
              <Save className="w-4 h-4 mr-2 inline" />
              {savingUser ? 'Salvataggio...' : (editingUser ? 'Salva Modifiche' : 'Aggiungi Utente')}
            </button>
          </div>
        </form>
      </Modal>

      {/* Delete User Confirmation Modal */}
      <Modal
        isOpen={isDeleteModalOpen}
        onClose={() => { setIsDeleteModalOpen(false); setDeletingUserId(null) }}
        title="Rimuovi Utente"
      >
        <div className="space-y-4">
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-gray-700">
              Sei sicuro di voler rimuovere <span className="font-semibold text-gray-900">@{binkUsers.find(u => u.id === deletingUserId)?.bink_username}</span>?
            </p>
            <p className="text-gray-500 text-sm mt-2">
              L'utente non potra piu accedere a OneManager.
            </p>
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={() => { setIsDeleteModalOpen(false); setDeletingUserId(null) }}
              className="glass-button flex-1"
            >
              Annulla
            </button>
            <button
              type="button"
              onClick={handleDeleteUser}
              className="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-all"
            >
              <Trash2 className="w-4 h-4 mr-2 inline" />
              Rimuovi Utente
            </button>
          </div>
        </div>
      </Modal>

      {/* Toast Notifications */}
      <ToastContainer toasts={toasts} onClose={removeToast} />
    </div>
  )
}
