import { motion, AnimatePresence } from 'framer-motion'
import { useState, useEffect } from 'react'
import { 
  Plus, Search, Edit, Trash2, TrendingUp, User,
  Mail, Phone, Building2, Calendar, DollarSign,
  ArrowRight, Filter, CheckCircle2, XCircle, Clock,
  Target, Users, Sparkles, AlertCircle, Download
} from 'lucide-react'
import Modal from '../components/Modal'
import OnboardingModalNew from '../components/OnboardingModalNew'
import { leadsApi } from '../utils/api'

interface Lead {
  id: number
  lead_number: string
  company_name: string
  contact_name: string
  email?: string
  phone?: string
  mobile?: string
  website?: string
  address?: string
  city?: string
  state?: string
  postal_code?: string
  country?: string
  source: 'website' | 'referral' | 'cold_call' | 'social_media' | 'event' | 'partner' | 'advertising' | 'other' | 'direct'
  status?: 'new' | 'contacted' | 'qualified' | 'unqualified' | 'converted' | 'lost'
  priority?: 'low' | 'medium' | 'high' | 'urgent'
  rating?: 'hot' | 'warm' | 'cold'
  industry?: string
  company_size?: string
  estimated_value?: number
  estimated_close_date?: string
  assigned_to?: number
  notes?: string
  converted_to_client_id?: number
  converted_at?: string
  last_contact_at?: string
  next_follow_up_at?: string
  lost_at?: string
  lost_reason?: string
  created_at: string
  updated_at: string
}

interface LeadStats {
  total: number
  by_status: Record<string, number>
  by_rating: Record<string, number>
  by_source: Record<string, number>
  active: number
  need_followup: number
  converted_this_month: number
  total_estimated_value: number
}

const sourceLabels: Record<string, string> = {
  website: 'Sito Web',
  referral: 'Referral',
  cold_call: 'Cold Call',
  social_media: 'Social Media',
  event: 'Evento',
  partner: 'Partner',
  advertising: 'Pubblicità',
  other: 'Altro',
  direct: 'Diretto'
}

const statusLabels: Record<string, string> = {
  new: 'Nuovo',
  contacted: 'Contattato',
  qualified: 'Qualificato',
  unqualified: 'Non Qualificato',
  converted: 'Convertito',
  lost: 'Perso'
}

const priorityLabels: Record<string, string> = {
  low: 'Bassa',
  medium: 'Media',
  high: 'Alta',
  urgent: 'Urgente'
}

const ratingLabels: Record<string, string> = {
  hot: 'Hot',
  warm: 'Warm',
  cold: 'Cold'
}

const statusColors: Record<string, string> = {
  new: 'bg-primary-500/20 text-primary-400 border-primary-500/30',
  contacted: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
  qualified: 'bg-green-500/20 text-green-400 border-green-500/30',
  unqualified: 'bg-gray-500/20 text-gray-400 border-gray-500/30',
  converted: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
  lost: 'bg-red-500/20 text-red-400 border-red-500/30'
}

const ratingColors: Record<string, string> = {
  hot: 'bg-red-500/20 text-red-400 border-red-500/30',
  warm: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
  cold: 'bg-primary-500/20 text-primary-400 border-primary-500/30'
}

const priorityColors: Record<string, string> = {
  low: 'bg-slate-500/20 text-gray-500 border-gray-300/30',
  medium: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
  high: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
  urgent: 'bg-red-500/20 text-red-400 border-red-500/30'
}

export default function Leads() {
  const [leads, setLeads] = useState<Lead[]>([])
  const [stats, setStats] = useState<LeadStats | null>(null)
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [filterSource, setFilterSource] = useState<string>('all')
  const [filterStatus, setFilterStatus] = useState<string>('all')
  const [filterRating, setFilterRating] = useState<string>('all')
  
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [isOnboardingOpen, setIsOnboardingOpen] = useState(false)
  const [editingLead, setEditingLead] = useState<Lead | null>(null)
  const [convertingLead, setConvertingLead] = useState<Lead | null>(null)
  const [exporting, setExporting] = useState(false)
  
  const [formData, setFormData] = useState({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    mobile: '',
    website: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'Italia',
    source: 'website' as Lead['source'],
    status: 'new' as Lead['status'],
    priority: 'medium' as Lead['priority'],
    rating: 'warm' as Lead['rating'],
    industry: '',
    company_size: '',
    estimated_value: 0,
    estimated_close_date: '',
    notes: ''
  })

  useEffect(() => {
    loadLeads()
    loadStats()
  }, [])

  const loadLeads = async () => {
    setLoading(true)
    try {
      const response = await leadsApi.getAll()
      setLeads(response.data.data.data || response.data.data || [])
    } catch (error) {
      console.error('Errore caricamento lead:', error)
      setLeads([])
    } finally {
      setLoading(false)
    }
  }

  const loadStats = async () => {
    try {
      const response = await leadsApi.stats()
      setStats(response.data)
    } catch (error) {
      console.error('Errore caricamento statistiche:', error)
    }
  }

  const handleSubmit = async () => {
    try {
      if (editingLead) {
        await leadsApi.update(editingLead.id, formData)
      } else {
        await leadsApi.create(formData)
      }
      
      await loadLeads()
      await loadStats()
      setIsModalOpen(false)
      resetForm()
    } catch (error) {
      console.error('Errore salvataggio lead:', error)
      alert('Errore durante il salvataggio del lead')
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm('Sei sicuro di voler eliminare questo lead?')) return
    
    try {
      await leadsApi.delete(id)
      await loadLeads()
      await loadStats()
    } catch (error) {
      console.error('Errore eliminazione lead:', error)
      alert('Errore durante l\'eliminazione del lead')
    }
  }

  const handleConvertToClient = (lead: Lead) => {
    setConvertingLead(lead)
    setIsOnboardingOpen(true)
  }

  const handleOnboardingComplete = async () => {
    if (convertingLead) {
      // After successful onboarding, we mark the lead as converted
      // The backend should handle this through the onboarding process
      await loadLeads()
      await loadStats()
    }
    setIsOnboardingOpen(false)
    setConvertingLead(null)
  }

  const resetForm = () => {
    setFormData({
      company_name: '',
      contact_name: '',
      email: '',
      phone: '',
      mobile: '',
      website: '',
      address: '',
      city: '',
      state: '',
      postal_code: '',
      country: 'Italia',
      source: 'website',
      status: 'new',
      priority: 'medium',
      rating: 'warm',
      industry: '',
      company_size: '',
      estimated_value: 0,
      estimated_close_date: '',
      notes: ''
    })
    setEditingLead(null)
  }

  const openEditModal = (lead: Lead) => {
    setEditingLead(lead)
    setFormData({
      company_name: lead.company_name,
      contact_name: lead.contact_name,
      email: lead.email || '',
      phone: lead.phone || '',
      mobile: lead.mobile || '',
      website: lead.website || '',
      address: lead.address || '',
      city: lead.city || '',
      state: lead.state || '',
      postal_code: lead.postal_code || '',
      country: lead.country || 'Italia',
      source: lead.source,
      status: lead.status || 'new',
      priority: lead.priority || 'medium',
      rating: lead.rating || 'warm',
      industry: lead.industry || '',
      company_size: lead.company_size || '',
      estimated_value: lead.estimated_value || 0,
      estimated_close_date: lead.estimated_close_date || '',
      notes: lead.notes || ''
    })
    setIsModalOpen(true)
  }

  const openCreateModal = () => {
    resetForm()
    setIsModalOpen(true)
  }

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const params: any = {}
      if (filterStatus !== 'all') params.status = filterStatus
      if (filterSource !== 'all') params.source = filterSource

      const response = await leadsApi.export(params)
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `lead_${new Date().toISOString().split('T')[0]}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error: any) {
      console.error('Errore esportazione:', error)
      alert('Errore durante l\'esportazione: ' + (error.response?.data?.message || error.message))
    } finally {
      setExporting(false)
    }
  }

  const filteredLeads = leads.filter(lead => {
    const matchesSearch = 
      lead.company_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      lead.contact_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (lead.email && lead.email.toLowerCase().includes(searchTerm.toLowerCase())) ||
      (lead.phone && lead.phone.includes(searchTerm))
    
    const matchesSource = filterSource === 'all' || lead.source === filterSource
    const matchesStatus = filterStatus === 'all' || lead.status === filterStatus
    const matchesRating = filterRating === 'all' || lead.rating === filterRating
    
    return matchesSearch && matchesSource && matchesStatus && matchesRating
  })

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-400 mx-auto mb-4"></div>
          <p className="text-gray-500">Caricamento lead...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header con Statistiche */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">Gestione Lead</h1>
          <p className="text-gray-500">{filteredLeads.length} lead trovati</p>
        </div>
        <div className="flex flex-wrap gap-3">
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleExportCSV}
            disabled={exporting}
            className="glass-button flex items-center gap-2 disabled:opacity-50"
          >
            <Download className={`w-4 h-4 ${exporting ? 'animate-pulse' : ''}`} />
            {exporting ? 'Esportazione...' : 'Esporta CSV'}
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={openCreateModal}
            className="glass-button-primary"
          >
            <Plus className="w-5 h-5 mr-2 inline" />
            Nuovo Lead
          </motion.button>
        </div>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm mb-1">Lead Attivi</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.active}</p>
              </div>
              <div className="w-12 h-12 bg-primary-500/20 rounded-lg flex items-center justify-center">
                <Target className="w-6 h-6 text-primary-400" />
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm mb-1">Da Seguire</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.need_followup}</p>
              </div>
              <div className="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center">
                <Clock className="w-6 h-6 text-orange-400" />
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm mb-1">Convertiti Mese</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.converted_this_month}</p>
              </div>
              <div className="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center">
                <CheckCircle2 className="w-6 h-6 text-emerald-400" />
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm mb-1">Valore Stimato</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  €{stats.total_estimated_value.toLocaleString('it-IT')}
                </p>
              </div>
              <div className="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center">
                <DollarSign className="w-6 h-6 text-purple-400" />
              </div>
            </div>
          </motion.div>
        </div>
      )}

      {/* Filtri */}
      <div className="glass-card p-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
            <input
              type="search"
              placeholder="Cerca lead..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="glass-input pl-10 w-full"
            />
          </div>
          
          <select
            value={filterSource}
            onChange={(e) => setFilterSource(e.target.value)}
            className="glass-input"
          >
            <option value="all">Tutte le Sorgenti</option>
            {Object.entries(sourceLabels).map(([value, label]) => (
              <option key={value} value={value}>{label}</option>
            ))}
          </select>

          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="glass-input"
          >
            <option value="all">Tutti gli Stati</option>
            {Object.entries(statusLabels).map(([value, label]) => (
              <option key={value} value={value}>{label}</option>
            ))}
          </select>

          <select
            value={filterRating}
            onChange={(e) => setFilterRating(e.target.value)}
            className="glass-input"
          >
            <option value="all">Tutti i Rating</option>
            {Object.entries(ratingLabels).map(([value, label]) => (
              <option key={value} value={value}>{label}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Tabella Lead */}
      <div className="glass-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="border-b border-gray-200">
              <tr>
                <th className="text-left p-4 text-gray-600 font-semibold">Lead</th>
                <th className="text-left p-4 text-gray-600 font-semibold">Contatto</th>
                <th className="text-left p-4 text-gray-600 font-semibold">Sorgente</th>
                <th className="text-left p-4 text-gray-600 font-semibold">Stato</th>
                <th className="text-left p-4 text-gray-600 font-semibold">Rating</th>
                <th className="text-left p-4 text-gray-600 font-semibold">Valore</th>
                <th className="text-left p-4 text-gray-600 font-semibold">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {filteredLeads.map((lead) => (
                <motion.tr
                  key={lead.id}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  className="border-b border-white/5 hover:bg-white/5 transition-colors"
                >
                  <td className="p-4">
                    <div>
                      <div className="font-medium text-gray-900 dark:text-white">{lead.company_name}</div>
                      <div className="text-xs text-gray-400">{lead.lead_number}</div>
                    </div>
                  </td>
                  <td className="p-4">
                    <div>
                      <div className="text-gray-600 flex items-center gap-1">
                        <User className="w-3 h-3" />
                        {lead.contact_name}
                      </div>
                      {lead.email && (
                        <div className="text-xs text-gray-400 flex items-center gap-1 mt-1">
                          <Mail className="w-3 h-3" />
                          {lead.email}
                        </div>
                      )}
                      {lead.phone && (
                        <div className="text-xs text-gray-400 flex items-center gap-1">
                          <Phone className="w-3 h-3" />
                          {lead.phone}
                        </div>
                      )}
                    </div>
                  </td>
                  <td className="p-4">
                    <span className="text-gray-600">{sourceLabels[lead.source]}</span>
                  </td>
                  <td className="p-4">
                    <span className={`px-2 py-1 rounded text-xs border ${statusColors[lead.status || 'new']}`}>
                      {statusLabels[lead.status || 'new']}
                    </span>
                  </td>
                  <td className="p-4">
                    {lead.rating && (
                      <span className={`px-2 py-1 rounded text-xs border ${ratingColors[lead.rating]}`}>
                        {ratingLabels[lead.rating]}
                      </span>
                    )}
                  </td>
                  <td className="p-4">
                    {lead.estimated_value ? (
                      <span className="text-emerald-400 font-medium">
                        €{lead.estimated_value.toLocaleString('it-IT')}
                      </span>
                    ) : (
                      <span className="text-gray-400">-</span>
                    )}
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      {lead.status !== 'converted' && (
                        <motion.button
                          whileHover={{ scale: 1.1 }}
                          whileTap={{ scale: 0.9 }}
                          onClick={() => handleConvertToClient(lead)}
                          className="p-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 rounded-lg transition-colors"
                          title="Converti in Cliente"
                        >
                          <ArrowRight className="w-4 h-4" />
                        </motion.button>
                      )}
                      <motion.button
                        whileHover={{ scale: 1.1 }}
                        whileTap={{ scale: 0.9 }}
                        onClick={() => openEditModal(lead)}
                        className="p-2 bg-primary-500/20 hover:bg-primary-500/30 text-primary-400 rounded-lg transition-colors"
                      >
                        <Edit className="w-4 h-4" />
                      </motion.button>
                      {lead.status !== 'converted' && (
                        <motion.button
                          whileHover={{ scale: 1.1 }}
                          whileTap={{ scale: 0.9 }}
                          onClick={() => handleDelete(lead.id)}
                          className="p-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-colors"
                        >
                          <Trash2 className="w-4 h-4" />
                        </motion.button>
                      )}
                    </div>
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
          
          {filteredLeads.length === 0 && (
            <div className="text-center py-12">
              <AlertCircle className="w-12 h-12 text-gray-500 mx-auto mb-3" />
              <p className="text-gray-500">Nessun lead trovato</p>
            </div>
          )}
        </div>
      </div>

      {/* Modal Creazione/Modifica Lead */}
      <Modal isOpen={isModalOpen} onClose={() => { setIsModalOpen(false); resetForm(); }}>
        <div className="space-y-6">
          <div className="flex items-center justify-between border-b border-gray-200 pb-4">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
              {editingLead ? 'Modifica Lead' : 'Nuovo Lead'}
            </h2>
          </div>

          <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
            {/* Informazioni Azienda */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <Building2 className="w-5 h-5" />
                Informazioni Azienda
              </h3>
              <div className="grid grid-cols-2 gap-4">
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Nome Azienda *
                  </label>
                  <input
                    type="text"
                    value={formData.company_name}
                    onChange={(e) => setFormData({ ...formData, company_name: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Es: Ristorante Da Mario"
                  />
                </div>
                
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Nome Contatto *
                  </label>
                  <input
                    type="text"
                    value={formData.contact_name}
                    onChange={(e) => setFormData({ ...formData, contact_name: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Es: Mario Rossi"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Email
                  </label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="glass-input w-full"
                    placeholder="info@azienda.it"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Telefono
                  </label>
                  <input
                    type="tel"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    className="glass-input w-full"
                    placeholder="+39 0586 123456"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Cellulare
                  </label>
                  <input
                    type="tel"
                    value={formData.mobile}
                    onChange={(e) => setFormData({ ...formData, mobile: e.target.value })}
                    className="glass-input w-full"
                    placeholder="+39 333 1234567"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Sito Web
                  </label>
                  <input
                    type="url"
                    value={formData.website}
                    onChange={(e) => setFormData({ ...formData, website: e.target.value })}
                    className="glass-input w-full"
                    placeholder="https://www.azienda.it"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Settore
                  </label>
                  <input
                    type="text"
                    value={formData.industry}
                    onChange={(e) => setFormData({ ...formData, industry: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Es: Ristorazione"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Dimensione Azienda
                  </label>
                  <select
                    value={formData.company_size}
                    onChange={(e) => setFormData({ ...formData, company_size: e.target.value })}
                    className="glass-input w-full"
                  >
                    <option value="">Seleziona...</option>
                    <option value="1-10">1-10 dipendenti</option>
                    <option value="11-50">11-50 dipendenti</option>
                    <option value="51-200">51-200 dipendenti</option>
                    <option value="201-500">201-500 dipendenti</option>
                    <option value="500+">500+ dipendenti</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Classificazione Lead */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <Sparkles className="w-5 h-5" />
                Classificazione
              </h3>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Sorgente *
                  </label>
                  <select
                    value={formData.source}
                    onChange={(e) => setFormData({ ...formData, source: e.target.value as Lead['source'] })}
                    className="glass-input w-full"
                  >
                    {Object.entries(sourceLabels).map(([value, label]) => (
                      <option key={value} value={value}>{label}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Stato
                  </label>
                  <select
                    value={formData.status}
                    onChange={(e) => setFormData({ ...formData, status: e.target.value as Lead['status'] })}
                    className="glass-input w-full"
                  >
                    {Object.entries(statusLabels).map(([value, label]) => (
                      <option key={value} value={value}>{label}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Priorità
                  </label>
                  <select
                    value={formData.priority}
                    onChange={(e) => setFormData({ ...formData, priority: e.target.value as Lead['priority'] })}
                    className="glass-input w-full"
                  >
                    {Object.entries(priorityLabels).map(([value, label]) => (
                      <option key={value} value={value}>{label}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Rating
                  </label>
                  <select
                    value={formData.rating}
                    onChange={(e) => setFormData({ ...formData, rating: e.target.value as Lead['rating'] })}
                    className="glass-input w-full"
                  >
                    {Object.entries(ratingLabels).map(([value, label]) => (
                      <option key={value} value={value}>{label}</option>
                    ))}
                  </select>
                </div>
              </div>
            </div>

            {/* Opportunità */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <TrendingUp className="w-5 h-5" />
                Opportunità
              </h3>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Valore Stimato (€)
                  </label>
                  <input
                    type="number"
                    value={formData.estimated_value}
                    onChange={(e) => setFormData({ ...formData, estimated_value: parseFloat(e.target.value) || 0 })}
                    className="glass-input w-full"
                    placeholder="10000"
                    min="0"
                    step="100"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Data Chiusura Prevista
                  </label>
                  <input
                    type="date"
                    value={formData.estimated_close_date}
                    onChange={(e) => setFormData({ ...formData, estimated_close_date: e.target.value })}
                    className="glass-input w-full"
                  />
                </div>
              </div>
            </div>

            {/* Indirizzo */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <Building2 className="w-5 h-5" />
                Indirizzo
              </h3>
              <div className="grid grid-cols-2 gap-4">
                <div className="col-span-2">
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Indirizzo
                  </label>
                  <input
                    type="text"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Via Roma 123"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Città
                  </label>
                  <input
                    type="text"
                    value={formData.city}
                    onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Livorno"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Provincia
                  </label>
                  <input
                    type="text"
                    value={formData.state}
                    onChange={(e) => setFormData({ ...formData, state: e.target.value.toUpperCase() })}
                    className="glass-input w-full"
                    placeholder="LI"
                    maxLength={2}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    CAP
                  </label>
                  <input
                    type="text"
                    value={formData.postal_code}
                    onChange={(e) => setFormData({ ...formData, postal_code: e.target.value })}
                    className="glass-input w-full"
                    placeholder="57100"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-600 mb-2">
                    Paese
                  </label>
                  <input
                    type="text"
                    value={formData.country}
                    onChange={(e) => setFormData({ ...formData, country: e.target.value })}
                    className="glass-input w-full"
                    placeholder="Italia"
                  />
                </div>
              </div>
            </div>

            {/* Note */}
            <div>
              <label className="block text-sm font-medium text-gray-600 mb-2">
                Note
              </label>
              <textarea
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                className="glass-input w-full"
                rows={4}
                placeholder="Note aggiuntive sul lead..."
              />
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              onClick={() => { setIsModalOpen(false); resetForm(); }}
              className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
            >
              Annulla
            </button>
            <button
              onClick={handleSubmit}
              className="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors"
            >
              {editingLead ? 'Salva Modifiche' : 'Crea Lead'}
            </button>
          </div>
        </div>
      </Modal>

      {/* Onboarding Modal per conversione */}
      <OnboardingModalNew
        isOpen={isOnboardingOpen}
        onClose={() => {
          setIsOnboardingOpen(false)
          setConvertingLead(null)
        }}
        onComplete={handleOnboardingComplete}
      />
    </div>
  )
}
