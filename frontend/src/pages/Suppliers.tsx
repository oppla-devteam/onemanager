import { useState, useEffect, Fragment } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import axios, { suppliersApi, supplierInvoicesApi } from '../utils/api';
import {
  Building2,
  FileText,
  Phone,
  Mail,
  RefreshCw,
  Plus,
  X,
  Search,
  Trash2,
  Edit,
  Clock,
  AlertCircle,
  Euro,
  Calendar,
  Download,
  CreditCard,
  TrendingUp,
  TrendingDown,
  CheckCircle,
  XCircle,
  Upload,
  ExternalLink,
  Filter,
} from 'lucide-react';
import { Dialog, Transition } from '@headlessui/react';

// Types
interface Supplier {
  id: number;
  ragione_sociale: string;
  piva?: string;
  codice_fiscale?: string;
  indirizzo?: string;
  citta?: string;
  provincia?: string;
  cap?: string;
  telefono?: string;
  email?: string;
  iban?: string;
  type: 'italiano_sdi' | 'estero' | 'altro';
  giorni_pagamento: number;
  note?: string;
  created_at: string;
  // Computed from backend
  invoices_count?: number;
  invoices_sum_totale?: number;
  unpaid_count?: number;
  overdue_count?: number;
}

interface SupplierInvoice {
  id: number;
  supplier_id: number;
  numero_fattura: string;
  data_emissione: string;
  data_scadenza?: string;
  data_pagamento?: string;
  imponibile: number;
  iva: number;
  totale: number;
  payment_status: 'non_pagata' | 'pagata';
  fic_id?: number;
  note?: string;
  file_path?: string;
  supplier?: Supplier;
}

interface Stats {
  total_suppliers: number;
  total_amount: number;
  paid_amount: number;
  pending_amount: number;
  overdue_amount: number;
  count_by_status: Record<string, number>;
}

type TabType = 'suppliers' | 'invoices' | 'upcoming' | 'overdue';

export default function Suppliers() {
  // Tab state
  const [activeTab, setActiveTab] = useState<TabType>('suppliers');
  
  // Suppliers state
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [loadingSuppliers, setLoadingSuppliers] = useState(true);
  
  // Invoices state
  const [invoices, setInvoices] = useState<SupplierInvoice[]>([]);
  const [loadingInvoices, setLoadingInvoices] = useState(false);
  
  // Stats state
  const [stats, setStats] = useState<Stats | null>(null);
  
  // UI state
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [syncingFIC, setSyncingFIC] = useState(false);
  const [exporting, setExporting] = useState(false);
  
  // Modals
  const [showAddSupplierModal, setShowAddSupplierModal] = useState(false);
  const [showEditSupplierModal, setShowEditSupplierModal] = useState(false);
  const [showAddInvoiceModal, setShowAddInvoiceModal] = useState(false);
  const [showEditInvoiceModal, setShowEditInvoiceModal] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [showInvoicesModal, setShowInvoicesModal] = useState(false);
  
  // Selected items
  const [selectedSupplier, setSelectedSupplier] = useState<Supplier | null>(null);
  const [selectedInvoice, setSelectedInvoice] = useState<SupplierInvoice | null>(null);
  const [supplierInvoices, setSupplierInvoices] = useState<SupplierInvoice[]>([]);
  
  // Form state - Supplier
  const [supplierForm, setSupplierForm] = useState({
    ragione_sociale: '',
    piva: '',
    codice_fiscale: '',
    indirizzo: '',
    citta: '',
    provincia: '',
    cap: '',
    telefono: '',
    email: '',
    iban: '',
    type: 'italiano_sdi' as 'italiano_sdi' | 'estero' | 'altro',
    giorni_pagamento: 30,
    note: '',
  });
  
  // Form state - Invoice
  const [invoiceForm, setInvoiceForm] = useState({
    supplier_id: 0,
    numero_fattura: '',
    data_emissione: new Date().toISOString().split('T')[0],
    data_scadenza: '',
    imponibile: 0,
    iva: 0,
    totale: 0,
    payment_status: 'non_pagata' as 'non_pagata' | 'pagata',
    note: '',
  });

  // Fetch suppliers
  const fetchSuppliers = async (showRefresh = false) => {
    if (showRefresh) setRefreshing(true);
    try {
      const response = await axios.get('/api/suppliers');
      if (response.data.data) {
        setSuppliers(response.data.data);
        setError(null);
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Errore caricamento fornitori');
    } finally {
      setLoadingSuppliers(false);
      setRefreshing(false);
    }
  };

  // Fetch invoices
  const fetchInvoices = async () => {
    setLoadingInvoices(true);
    try {
      const params: any = {};
      if (statusFilter !== 'all') {
        params.payment_status = statusFilter;
      }
      const response = await axios.get('/api/supplier-invoices', { params });
      if (response.data.data) {
        setInvoices(response.data.data);
      }
    } catch (err: any) {
      console.error('Error fetching invoices:', err);
    } finally {
      setLoadingInvoices(false);
    }
  };

  // Fetch stats
  const fetchStats = async () => {
    try {
      const response = await axios.get('/api/supplier-invoices/stats');
      setStats(response.data);
    } catch (err) {
      console.error('Error fetching stats:', err);
    }
  };

  // Fetch upcoming payments
  const fetchUpcoming = async () => {
    setLoadingInvoices(true);
    try {
      const response = await axios.get('/api/supplier-invoices/upcoming', {
        params: { days: 30 }
      });
      setInvoices(response.data.invoices || []);
    } catch (err) {
      console.error('Error fetching upcoming:', err);
    } finally {
      setLoadingInvoices(false);
    }
  };

  // Fetch overdue payments
  const fetchOverdue = async () => {
    setLoadingInvoices(true);
    try {
      const response = await axios.get('/api/supplier-invoices/overdue');
      setInvoices(response.data.invoices || []);
    } catch (err) {
      console.error('Error fetching overdue:', err);
    } finally {
      setLoadingInvoices(false);
    }
  };

  // Sync from FIC
  const syncFromFIC = async () => {
    setSyncingFIC(true);
    try {
      const response = await axios.post('/api/supplier-invoices/sync-fic', {
        year: new Date().getFullYear()
      });
      alert(`Sincronizzazione completata!\nCreate: ${response.data.created}\nAggiornate: ${response.data.updated}`);
      fetchInvoices();
      fetchStats();
    } catch (err: any) {
      alert('Errore sincronizzazione: ' + (err.response?.data?.error || err.message));
    } finally {
      setSyncingFIC(false);
    }
  };

  // Fetch supplier invoices
  const fetchSupplierInvoices = async (supplierId: number) => {
    try {
      const response = await axios.get('/api/supplier-invoices', {
        params: { supplier_id: supplierId }
      });
      setSupplierInvoices(response.data.data || []);
    } catch (err) {
      console.error('Error fetching supplier invoices:', err);
    }
  };

  // Create supplier
  const handleCreateSupplier = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await axios.post('/api/suppliers', supplierForm);
      setShowAddSupplierModal(false);
      resetSupplierForm();
      fetchSuppliers();
    } catch (err: any) {
      alert('Errore: ' + (err.response?.data?.message || err.message));
    }
  };

  // Update supplier
  const handleUpdateSupplier = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedSupplier) return;
    try {
      await axios.put(`/api/suppliers/${selectedSupplier.id}`, supplierForm);
      setShowEditSupplierModal(false);
      fetchSuppliers();
    } catch (err: any) {
      alert('Errore: ' + (err.response?.data?.message || err.message));
    }
  };

  // Delete supplier
  const handleDeleteSupplier = async () => {
    if (!selectedSupplier) return;
    try {
      await axios.delete(`/api/suppliers/${selectedSupplier.id}`);
      setShowDeleteConfirm(false);
      setSelectedSupplier(null);
      fetchSuppliers();
    } catch (err: any) {
      alert('Errore: ' + (err.response?.data?.message || err.message));
    }
  };

  // Create invoice
  const handleCreateInvoice = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await axios.post('/api/supplier-invoices', invoiceForm);
      setShowAddInvoiceModal(false);
      resetInvoiceForm();
      fetchInvoices();
      fetchStats();
    } catch (err: any) {
      alert('Errore: ' + (err.response?.data?.message || err.message));
    }
  };

  // Update invoice
  const handleUpdateInvoice = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedInvoice) return;
    try {
      await axios.put(`/api/supplier-invoices/${selectedInvoice.id}`, invoiceForm);
      setShowEditInvoiceModal(false);
      fetchInvoices();
      fetchStats();
    } catch (err: any) {
      alert('Errore: ' + (err.response?.data?.message || err.message));
    }
  };

  // Mark invoice as paid
  const handleMarkPaid = async (invoice: SupplierInvoice) => {
    try {
      await axios.post(`/api/supplier-invoices/${invoice.id}/mark-paid`);
      fetchInvoices();
      fetchStats();
    } catch (err: any) {
      alert('Errore: ' + (err.response?.data?.message || err.message));
    }
  };

  // Delete invoice
  const handleDeleteInvoice = async (invoice: SupplierInvoice) => {
    if (!confirm('Eliminare questa fattura?')) return;
    try {
      await axios.delete(`/api/supplier-invoices/${invoice.id}`);
      fetchInvoices();
      fetchStats();
    } catch (err: any) {
      alert('Errore: ' + (err.response?.data?.message || err.message));
    }
  };

  // Reset forms
  const resetSupplierForm = () => {
    setSupplierForm({
      ragione_sociale: '',
      piva: '',
      codice_fiscale: '',
      indirizzo: '',
      citta: '',
      provincia: '',
      cap: '',
      telefono: '',
      email: '',
      iban: '',
      type: 'italiano_sdi',
      giorni_pagamento: 30,
      note: '',
    });
  };

  const resetInvoiceForm = () => {
    setInvoiceForm({
      supplier_id: 0,
      numero_fattura: '',
      data_emissione: new Date().toISOString().split('T')[0],
      data_scadenza: '',
      imponibile: 0,
      iva: 0,
      totale: 0,
      payment_status: 'non_pagata',
      note: '',
    });
  };

  // Effects
  useEffect(() => {
    fetchSuppliers();
    fetchStats();
  }, []);

  useEffect(() => {
    if (activeTab === 'invoices') {
      fetchInvoices();
    } else if (activeTab === 'upcoming') {
      fetchUpcoming();
    } else if (activeTab === 'overdue') {
      fetchOverdue();
    }
  }, [activeTab, statusFilter]);

  // Filter suppliers
  const filteredSuppliers = suppliers.filter(s =>
    s.ragione_sociale.toLowerCase().includes(searchTerm.toLowerCase()) ||
    s.piva?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    s.email?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // Filter invoices
  const filteredInvoices = invoices.filter(inv =>
    inv.numero_fattura.toLowerCase().includes(searchTerm.toLowerCase()) ||
    inv.supplier?.ragione_sociale?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
  };

  // Format date
  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('it-IT');
  };

  // Open edit supplier modal
  const openEditSupplier = (supplier: Supplier) => {
    setSelectedSupplier(supplier);
    setSupplierForm({
      ragione_sociale: supplier.ragione_sociale,
      piva: supplier.piva || '',
      codice_fiscale: supplier.codice_fiscale || '',
      indirizzo: supplier.indirizzo || '',
      citta: supplier.citta || '',
      provincia: supplier.provincia || '',
      cap: supplier.cap || '',
      telefono: supplier.telefono || '',
      email: supplier.email || '',
      iban: supplier.iban || '',
      type: supplier.type,
      giorni_pagamento: supplier.giorni_pagamento,
      note: supplier.note || '',
    });
    setShowEditSupplierModal(true);
  };

  // Open add invoice modal
  const openAddInvoice = (supplier?: Supplier) => {
    resetInvoiceForm();
    if (supplier) {
      setInvoiceForm(prev => ({
        ...prev,
        supplier_id: supplier.id,
        data_scadenza: new Date(Date.now() + supplier.giorni_pagamento * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      }));
    }
    setShowAddInvoiceModal(true);
  };

  // Open edit invoice modal
  const openEditInvoice = (invoice: SupplierInvoice) => {
    setSelectedInvoice(invoice);
    setInvoiceForm({
      supplier_id: invoice.supplier_id,
      numero_fattura: invoice.numero_fattura,
      data_emissione: invoice.data_emissione,
      data_scadenza: invoice.data_scadenza || '',
      imponibile: invoice.imponibile,
      iva: invoice.iva,
      totale: invoice.totale,
      payment_status: invoice.payment_status,
      note: invoice.note || '',
    });
    setShowEditInvoiceModal(true);
  };

  // Open supplier invoices modal
  const openSupplierInvoices = (supplier: Supplier) => {
    setSelectedSupplier(supplier);
    fetchSupplierInvoices(supplier.id);
    setShowInvoicesModal(true);
  };

  const handleExportCSV = async () => {
    setExporting(true)
    try {
      const response = await suppliersApi.export()
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `fornitori_${new Date().toISOString().split('T')[0]}.csv`)
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

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap justify-between items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Fornitori & Fatture Passive</h1>
          <p className="text-gray-500 mt-1">Gestione anagrafica fornitori e fatture di acquisto</p>
        </div>
        <div className="flex flex-col gap-2 flex-shrink-0">
          <div className="flex flex-wrap items-center gap-2">
            <button
              onClick={() => fetchSuppliers(true)}
              disabled={refreshing}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-white text-gray-700 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors disabled:opacity-50"
            >
              <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
              Aggiorna
            </button>
            <button
              onClick={handleExportCSV}
              disabled={exporting}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-white text-gray-700 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors disabled:opacity-50"
            >
              <Download className={`w-4 h-4 ${exporting ? 'animate-pulse' : ''}`} />
              {exporting ? 'Esportazione...' : 'Esporta CSV'}
            </button>
            <button
              onClick={syncFromFIC}
              disabled={syncingFIC}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-white text-gray-700 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors disabled:opacity-50"
            >
              <Download className={`w-4 h-4 ${syncingFIC ? 'animate-spin' : ''}`} />
              {syncingFIC ? 'Sync...' : 'Sync FIC'}
            </button>
          </div>
          <div className="flex items-center gap-2 justify-end">
            <button
              onClick={() => { resetSupplierForm(); setShowAddSupplierModal(true); }}
              className="flex items-center gap-2 px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
            >
              <Plus className="w-4 h-4" />
              Nuovo Fornitore
            </button>
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass-card p-4"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-primary-50">
                <Building2 className="w-6 h-6 text-primary-600" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Fornitori</p>
                <p className="text-xl font-bold text-gray-900">{suppliers.length}</p>
              </div>
            </div>
          </motion.div>
          
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="glass-card p-4"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-green-50">
                <CheckCircle className="w-6 h-6 text-green-600" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Pagate</p>
                <p className="text-xl font-bold text-gray-900">{formatCurrency(stats.paid_amount || 0)}</p>
              </div>
            </div>
          </motion.div>
          
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="glass-card p-4"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-yellow-50">
                <Clock className="w-6 h-6 text-yellow-600" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Da Pagare</p>
                <p className="text-xl font-bold text-gray-900">{formatCurrency(stats.pending_amount || 0)}</p>
              </div>
            </div>
          </motion.div>
          
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="glass-card p-4"
          >
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-lg bg-red-50">
                <AlertCircle className="w-6 h-6 text-red-500" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Scadute</p>
                <p className="text-xl font-bold text-gray-900">{formatCurrency(stats.overdue_amount || 0)}</p>
              </div>
            </div>
          </motion.div>
        </div>
      )}

      {/* Tabs */}
      <div className="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
      <div className="flex gap-2 border-b border-gray-200 min-w-max">
        <button
          onClick={() => setActiveTab('suppliers')}
          className={`px-4 py-2 -mb-px ${activeTab === 'suppliers' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-900'}`}
        >
          <Building2 className="w-4 h-4 inline mr-2" />
          Fornitori
        </button>
        <button
          onClick={() => setActiveTab('invoices')}
          className={`px-4 py-2 -mb-px ${activeTab === 'invoices' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-900'}`}
        >
          <FileText className="w-4 h-4 inline mr-2" />
          Fatture Passive
        </button>
        <button
          onClick={() => setActiveTab('upcoming')}
          className={`px-4 py-2 -mb-px ${activeTab === 'upcoming' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-900'}`}
        >
          <Calendar className="w-4 h-4 inline mr-2" />
          In Scadenza
        </button>
        <button
          onClick={() => setActiveTab('overdue')}
          className={`px-4 py-2 -mb-px ${activeTab === 'overdue' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-900'}`}
        >
          <AlertCircle className="w-4 h-4 inline mr-2" />
          Scadute
        </button>
      </div>
      </div>

      {/* Search & Filters */}
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder={activeTab === 'suppliers' ? 'Cerca fornitore...' : 'Cerca fattura...'}
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="input-field pl-10 w-full"
          />
        </div>
        {activeTab === 'invoices' && (
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="input-field w-48"
          >
            <option value="all">Tutti gli stati</option>
            <option value="non_pagata">Non pagate</option>
            <option value="pagata">Pagate</option>
          </select>
        )}
      </div>

      {/* Content */}
      <AnimatePresence mode="wait">
        {activeTab === 'suppliers' && (
          <motion.div
            key="suppliers"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          >
            {loadingSuppliers ? (
              <div className="flex justify-center py-12">
                <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
              </div>
            ) : error ? (
              <div className="text-center py-12 text-red-500">{error}</div>
            ) : filteredSuppliers.length === 0 ? (
              <div className="text-center py-12 text-gray-400">
                <Building2 className="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>Nessun fornitore trovato</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="text-left text-gray-400 border-b border-gray-200">
                      <th className="pb-3 font-medium">Ragione Sociale</th>
                      <th className="pb-3 font-medium">P.IVA</th>
                      <th className="pb-3 font-medium">Tipo</th>
                      <th className="pb-3 font-medium">Contatti</th>
                      <th className="pb-3 font-medium text-right">Fatture</th>
                      <th className="pb-3 font-medium text-right">Totale</th>
                      <th className="pb-3 font-medium text-right">Azioni</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-200">
                    {filteredSuppliers.map((supplier) => (
                      <tr key={supplier.id} className="hover:bg-gray-50">
                        <td className="py-4">
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                              <Building2 className="w-5 h-5 text-white" />
                            </div>
                            <div>
                              <p className="font-medium text-gray-900">{supplier.ragione_sociale}</p>
                              {supplier.citta && (
                                <p className="text-sm text-gray-400">{supplier.citta}</p>
                              )}
                            </div>
                          </div>
                        </td>
                        <td className="py-4 text-gray-300">{supplier.piva || '-'}</td>
                        <td className="py-4">
                          <span className={`px-2 py-1 rounded-full text-xs ${
                            supplier.type === 'italiano_sdi' ? 'bg-green-50 text-green-600' :
                            supplier.type === 'estero' ? 'bg-primary-100 text-primary-700' :
                            'bg-gray-100 text-gray-500'
                          }`}>
                            {supplier.type === 'italiano_sdi' ? 'IT SDI' : 
                             supplier.type === 'estero' ? 'Estero' : 'Altro'}
                          </span>
                        </td>
                        <td className="py-4">
                          <div className="flex flex-col gap-1">
                            {supplier.email && (
                              <span className="text-sm text-gray-400 flex items-center gap-1">
                                <Mail className="w-3 h-3" /> {supplier.email}
                              </span>
                            )}
                            {supplier.telefono && (
                              <span className="text-sm text-gray-400 flex items-center gap-1">
                                <Phone className="w-3 h-3" /> {supplier.telefono}
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="py-4 text-right">
                          <button
                            onClick={() => openSupplierInvoices(supplier)}
                            className="text-primary-600 hover:text-primary-300"
                          >
                            {supplier.invoices_count || 0} fatture
                          </button>
                        </td>
                        <td className="py-4 text-right font-medium text-gray-900">
                          {formatCurrency(supplier.invoices_sum_totale || 0)}
                        </td>
                        <td className="py-4">
                          <div className="flex justify-end gap-2">
                            <button
                              onClick={() => openAddInvoice(supplier)}
                              className="p-2 hover:bg-gray-100 rounded-lg text-green-600"
                              title="Aggiungi fattura"
                            >
                              <FileText className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => openEditSupplier(supplier)}
                              className="p-2 hover:bg-gray-100 rounded-lg text-gray-400"
                              title="Modifica"
                            >
                              <Edit className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => { setSelectedSupplier(supplier); setShowDeleteConfirm(true); }}
                              className="p-2 hover:bg-gray-100 rounded-lg text-red-500"
                              title="Elimina"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </motion.div>
        )}

        {(activeTab === 'invoices' || activeTab === 'upcoming' || activeTab === 'overdue') && (
          <motion.div
            key={activeTab}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          >
            {loadingInvoices ? (
              <div className="flex justify-center py-12">
                <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
              </div>
            ) : filteredInvoices.length === 0 ? (
              <div className="text-center py-12 text-gray-400">
                <FileText className="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>Nessuna fattura trovata</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="text-left text-gray-400 border-b border-gray-200">
                      <th className="pb-3 font-medium">Fornitore</th>
                      <th className="pb-3 font-medium">N. Fattura</th>
                      <th className="pb-3 font-medium">Data</th>
                      <th className="pb-3 font-medium">Scadenza</th>
                      <th className="pb-3 font-medium text-right">Imponibile</th>
                      <th className="pb-3 font-medium text-right">IVA</th>
                      <th className="pb-3 font-medium text-right">Totale</th>
                      <th className="pb-3 font-medium">Stato</th>
                      <th className="pb-3 font-medium text-right">Azioni</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-200">
                    {filteredInvoices.map((invoice) => (
                      <tr key={invoice.id} className="hover:bg-gray-50">
                        <td className="py-4">
                          <p className="font-medium text-gray-900">{invoice.supplier?.ragione_sociale || 'N/D'}</p>
                        </td>
                        <td className="py-4 text-gray-300">{invoice.numero_fattura}</td>
                        <td className="py-4 text-gray-300">{formatDate(invoice.data_emissione)}</td>
                        <td className="py-4">
                          <span className={`${
                            invoice.data_scadenza && new Date(invoice.data_scadenza) < new Date() && invoice.payment_status !== 'pagata'
                              ? 'text-red-500' : 'text-gray-300'
                          }`}>
                            {invoice.data_scadenza ? formatDate(invoice.data_scadenza) : '-'}
                          </span>
                        </td>
                        <td className="py-4 text-right text-gray-300">{formatCurrency(invoice.imponibile)}</td>
                        <td className="py-4 text-right text-gray-300">{formatCurrency(invoice.iva)}</td>
                        <td className="py-4 text-right font-medium text-gray-900">{formatCurrency(invoice.totale)}</td>
                        <td className="py-4">
                          <span className={`px-2 py-1 rounded-full text-xs ${
                            invoice.payment_status === 'pagata' 
                              ? 'bg-green-50 text-green-600' 
                              : 'bg-yellow-50 text-yellow-600'
                          }`}>
                            {invoice.payment_status === 'pagata' ? 'Pagata' : 'Non pagata'}
                          </span>
                        </td>
                        <td className="py-4">
                          <div className="flex justify-end gap-2">
                            {invoice.payment_status !== 'pagata' && (
                              <button
                                onClick={() => handleMarkPaid(invoice)}
                                className="p-2 hover:bg-gray-100 rounded-lg text-green-600"
                                title="Segna come pagata"
                              >
                                <CheckCircle className="w-4 h-4" />
                              </button>
                            )}
                            <button
                              onClick={() => openEditInvoice(invoice)}
                              className="p-2 hover:bg-gray-100 rounded-lg text-gray-400"
                              title="Modifica"
                            >
                              <Edit className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => handleDeleteInvoice(invoice)}
                              className="p-2 hover:bg-gray-100 rounded-lg text-red-500"
                              title="Elimina"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>

      {/* Add/Edit Supplier Modal */}
      <Transition appear show={showAddSupplierModal || showEditSupplierModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => { setShowAddSupplierModal(false); setShowEditSupplierModal(false); }}
        >
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/50" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <Building2 className="w-5 h-5" />
                    {showEditSupplierModal ? 'Modifica Fornitore' : 'Nuovo Fornitore'}
                  </Dialog.Title>

                  <form onSubmit={showEditSupplierModal ? handleUpdateSupplier : handleCreateSupplier}>
                    <div className="grid grid-cols-2 gap-4">
                      <div className="col-span-2">
                        <label className="block text-sm text-gray-500 mb-1">Ragione Sociale *</label>
                        <input
                          type="text"
                          required
                          value={supplierForm.ragione_sociale}
                          onChange={(e) => setSupplierForm({ ...supplierForm, ragione_sociale: e.target.value })}
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">P.IVA</label>
                        <input
                          type="text"
                          value={supplierForm.piva}
                          onChange={(e) => setSupplierForm({ ...supplierForm, piva: e.target.value })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Codice Fiscale</label>
                        <input
                          type="text"
                          value={supplierForm.codice_fiscale}
                          onChange={(e) => setSupplierForm({ ...supplierForm, codice_fiscale: e.target.value })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Tipo</label>
                        <select
                          value={supplierForm.type}
                          onChange={(e) => setSupplierForm({ ...supplierForm, type: e.target.value as any })}
                          className="input-field w-full"
                        >
                          <option value="italiano_sdi">Italiano (SDI)</option>
                          <option value="estero">Estero</option>
                          <option value="altro">Altro</option>
                        </select>
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Giorni Pagamento</label>
                        <input
                          type="number"
                          value={supplierForm.giorni_pagamento}
                          onChange={(e) => setSupplierForm({ ...supplierForm, giorni_pagamento: parseInt(e.target.value) || 30 })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div className="col-span-2">
                        <label className="block text-sm text-gray-400 mb-1">Indirizzo</label>
                        <input
                          type="text"
                          value={supplierForm.indirizzo}
                          onChange={(e) => setSupplierForm({ ...supplierForm, indirizzo: e.target.value })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Città</label>
                        <input
                          type="text"
                          value={supplierForm.citta}
                          onChange={(e) => setSupplierForm({ ...supplierForm, citta: e.target.value })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div className="grid grid-cols-2 gap-2">
                        <div>
                          <label className="block text-sm text-gray-400 mb-1">Provincia</label>
                          <input
                            type="text"
                            maxLength={2}
                            value={supplierForm.provincia}
                            onChange={(e) => setSupplierForm({ ...supplierForm, provincia: e.target.value.toUpperCase() })}
                            className="input-field w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm text-gray-400 mb-1">CAP</label>
                          <input
                            type="text"
                            maxLength={5}
                            value={supplierForm.cap}
                            onChange={(e) => setSupplierForm({ ...supplierForm, cap: e.target.value })}
                            className="input-field w-full"
                          />
                        </div>
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Email</label>
                        <input
                          type="email"
                          value={supplierForm.email}
                          onChange={(e) => setSupplierForm({ ...supplierForm, email: e.target.value })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Telefono</label>
                        <input
                          type="text"
                          value={supplierForm.telefono}
                          onChange={(e) => setSupplierForm({ ...supplierForm, telefono: e.target.value })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div className="col-span-2">
                        <label className="block text-sm text-gray-400 mb-1">IBAN</label>
                        <input
                          type="text"
                          value={supplierForm.iban}
                          onChange={(e) => setSupplierForm({ ...supplierForm, iban: e.target.value.toUpperCase() })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div className="col-span-2">
                        <label className="block text-sm text-gray-400 mb-1">Note</label>
                        <textarea
                          value={supplierForm.note}
                          onChange={(e) => setSupplierForm({ ...supplierForm, note: e.target.value })}
                          className="input-field w-full"
                          rows={3}
                        />
                      </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                      <button
                        type="button"
                        onClick={() => { setShowAddSupplierModal(false); setShowEditSupplierModal(false); }}
                        className="btn-secondary"
                      >
                        Annulla
                      </button>
                      <button type="submit" className="btn-primary">
                        {showEditSupplierModal ? 'Salva' : 'Crea'}
                      </button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Add/Edit Invoice Modal */}
      <Transition appear show={showAddInvoiceModal || showEditInvoiceModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => { setShowAddInvoiceModal(false); setShowEditInvoiceModal(false); }}
        >
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/50" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <FileText className="w-5 h-5" />
                    {showEditInvoiceModal ? 'Modifica Fattura' : 'Nuova Fattura Passiva'}
                  </Dialog.Title>

                  <form onSubmit={showEditInvoiceModal ? handleUpdateInvoice : handleCreateInvoice}>
                    <div className="space-y-4">
                      <div>
                        <label className="block text-sm text-gray-500 mb-1">Fornitore *</label>
                        <select
                          required
                          value={invoiceForm.supplier_id}
                          onChange={(e) => setInvoiceForm({ ...invoiceForm, supplier_id: parseInt(e.target.value) })}
                          className="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-primary-500"
                        >
                          <option value={0}>Seleziona fornitore...</option>
                          {suppliers.map((s) => (
                            <option key={s.id} value={s.id}>{s.ragione_sociale}</option>
                          ))}
                        </select>
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Numero Fattura *</label>
                        <input
                          type="text"
                          required
                          value={invoiceForm.numero_fattura}
                          onChange={(e) => setInvoiceForm({ ...invoiceForm, numero_fattura: e.target.value })}
                          className="input-field w-full"
                        />
                      </div>
                      
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-sm text-gray-400 mb-1">Data Emissione *</label>
                          <input
                            type="date"
                            required
                            value={invoiceForm.data_emissione}
                            onChange={(e) => setInvoiceForm({ ...invoiceForm, data_emissione: e.target.value })}
                            className="input-field w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm text-gray-400 mb-1">Data Scadenza</label>
                          <input
                            type="date"
                            value={invoiceForm.data_scadenza}
                            onChange={(e) => setInvoiceForm({ ...invoiceForm, data_scadenza: e.target.value })}
                            className="input-field w-full"
                          />
                        </div>
                      </div>
                      
                      <div className="grid grid-cols-3 gap-4">
                        <div>
                          <label className="block text-sm text-gray-400 mb-1">Imponibile *</label>
                          <input
                            type="number"
                            step="0.01"
                            required
                            value={invoiceForm.imponibile}
                            onChange={(e) => {
                              const imp = parseFloat(e.target.value) || 0;
                              const iva = invoiceForm.iva;
                              setInvoiceForm({ ...invoiceForm, imponibile: imp, totale: imp + iva });
                            }}
                            className="input-field w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm text-gray-400 mb-1">IVA</label>
                          <input
                            type="number"
                            step="0.01"
                            value={invoiceForm.iva}
                            onChange={(e) => {
                              const iva = parseFloat(e.target.value) || 0;
                              const imp = invoiceForm.imponibile;
                              setInvoiceForm({ ...invoiceForm, iva, totale: imp + iva });
                            }}
                            className="input-field w-full"
                          />
                        </div>
                        <div>
                          <label className="block text-sm text-gray-400 mb-1">Totale *</label>
                          <input
                            type="number"
                            step="0.01"
                            required
                            value={invoiceForm.totale}
                            onChange={(e) => setInvoiceForm({ ...invoiceForm, totale: parseFloat(e.target.value) || 0 })}
                            className="input-field w-full"
                          />
                        </div>
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Stato Pagamento</label>
                        <select
                          value={invoiceForm.payment_status}
                          onChange={(e) => setInvoiceForm({ ...invoiceForm, payment_status: e.target.value as any })}
                          className="input-field w-full"
                        >
                          <option value="non_pagata">Non pagata</option>
                          <option value="pagata">Pagata</option>
                        </select>
                      </div>
                      
                      <div>
                        <label className="block text-sm text-gray-400 mb-1">Note</label>
                        <textarea
                          value={invoiceForm.note}
                          onChange={(e) => setInvoiceForm({ ...invoiceForm, note: e.target.value })}
                          className="input-field w-full"
                          rows={2}
                        />
                      </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                      <button
                        type="button"
                        onClick={() => { setShowAddInvoiceModal(false); setShowEditInvoiceModal(false); }}
                        className="btn-secondary"
                      >
                        Annulla
                      </button>
                      <button type="submit" className="btn-primary">
                        {showEditInvoiceModal ? 'Salva' : 'Crea'}
                      </button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Supplier Invoices Modal */}
      <Transition appear show={showInvoicesModal} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => setShowInvoicesModal(false)}
        >
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/50" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-medium text-gray-900 mb-4 flex items-center justify-between">
                    <span className="flex items-center gap-2">
                      <FileText className="w-5 h-5" />
                      Fatture di {selectedSupplier?.ragione_sociale}
                    </span>
                    <button
                      onClick={() => openAddInvoice(selectedSupplier || undefined)}
                      className="btn-primary flex items-center gap-2 text-sm"
                    >
                      <Plus className="w-4 h-4" />
                      Nuova Fattura
                    </button>
                  </Dialog.Title>

                  {supplierInvoices.length === 0 ? (
                    <div className="text-center py-12 text-gray-400">
                      <FileText className="w-12 h-12 mx-auto mb-4 opacity-50" />
                      <p>Nessuna fattura per questo fornitore</p>
                    </div>
                  ) : (
                    <div className="overflow-x-auto">
                      <table className="w-full">
                        <thead>
                          <tr className="text-left text-gray-400 border-b border-gray-200">
                            <th className="pb-3 font-medium">N. Fattura</th>
                            <th className="pb-3 font-medium">Data</th>
                            <th className="pb-3 font-medium">Scadenza</th>
                            <th className="pb-3 font-medium text-right">Totale</th>
                            <th className="pb-3 font-medium">Stato</th>
                            <th className="pb-3 font-medium text-right">Azioni</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                          {supplierInvoices.map((invoice) => (
                            <tr key={invoice.id} className="hover:bg-gray-50">
                              <td className="py-3 text-gray-900">{invoice.numero_fattura}</td>
                              <td className="py-3 text-gray-300">{formatDate(invoice.data_emissione)}</td>
                              <td className="py-3">
                                <span className={`${
                                  invoice.data_scadenza && new Date(invoice.data_scadenza) < new Date() && invoice.payment_status !== 'pagata'
                                    ? 'text-red-500' : 'text-gray-300'
                                }`}>
                                  {invoice.data_scadenza ? formatDate(invoice.data_scadenza) : '-'}
                                </span>
                              </td>
                              <td className="py-3 text-right font-medium text-gray-900">{formatCurrency(invoice.totale)}</td>
                              <td className="py-3">
                                <span className={`px-2 py-1 rounded-full text-xs ${
                                  invoice.payment_status === 'pagata' 
                                    ? 'bg-green-50 text-green-600' 
                                    : 'bg-yellow-50 text-yellow-600'
                                }`}>
                                  {invoice.payment_status === 'pagata' ? 'Pagata' : 'Non pagata'}
                                </span>
                              </td>
                              <td className="py-3">
                                <div className="flex justify-end gap-2">
                                  {invoice.payment_status !== 'pagata' && (
                                    <button
                                      onClick={() => handleMarkPaid(invoice)}
                                      className="p-1 hover:bg-gray-200 rounded text-green-600"
                                      title="Segna come pagata"
                                    >
                                      <CheckCircle className="w-4 h-4" />
                                    </button>
                                  )}
                                  <button
                                    onClick={() => { setShowInvoicesModal(false); openEditInvoice(invoice); }}
                                    className="p-1 hover:bg-gray-200 rounded text-gray-400"
                                  >
                                    <Edit className="w-4 h-4" />
                                  </button>
                                </div>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}

                  <div className="mt-6 flex justify-end">
                    <button onClick={() => setShowInvoicesModal(false)} className="btn-secondary">
                      Chiudi
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Delete Confirmation Modal */}
      <Transition appear show={showDeleteConfirm} as={Fragment}>
        <Dialog
          as="div"
          className="relative z-50"
          onClose={() => setShowDeleteConfirm(false)}
        >
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/50" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all border border-gray-200">
                  <Dialog.Title className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <AlertCircle className="w-5 h-5 text-red-500" />
                    Conferma Eliminazione
                  </Dialog.Title>

                  <p className="text-gray-600 mb-6">
                    Sei sicuro di voler eliminare il fornitore <strong className="text-gray-900 dark:text-white">{selectedSupplier?.ragione_sociale}</strong>?
                    Questa azione non può essere annullata.
                  </p>

                  <div className="flex justify-end gap-3">
                    <button
                      onClick={() => setShowDeleteConfirm(false)}
                      className="btn-secondary"
                    >
                      Annulla
                    </button>
                    <button
                      onClick={handleDeleteSupplier}
                      className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg"
                    >
                      Elimina
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </div>
  );
}
