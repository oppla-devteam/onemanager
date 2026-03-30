// API utility functions for backend communication
import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://pedro.oppla.club/api'

console.log('API configured with base URL:', API_BASE_URL)

// Create axios instance with default config
export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: false,
  timeout: 120000, // 2 minuti timeout per sync pesanti
})

// Add token to requests if available
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle 401 errors (Unauthorized)
let isRedirecting = false
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Log dettagliato per debugging in produzione
    if (import.meta.env.MODE === 'production') {
      console.error('API Error:', {
        status: error.response?.status,
        url: error.config?.url,
        method: error.config?.method,
        baseURL: error.config?.baseURL,
        hasToken: !!localStorage.getItem('token'),
        timestamp: new Date().toISOString()
      })
    }
    
    if (error.response?.status === 401) {
      // Solo logout se siamo autenticati e non stiamo già reindirizzando
      const token = localStorage.getItem('token')
      const currentPath = window.location.pathname
      
      if (token && currentPath !== '/login' && !isRedirecting) {
        console.warn('Token expired or invalid, redirecting to login')
        isRedirecting = true
        // Token scaduto o non valido
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/login'
        setTimeout(() => { isRedirecting = false }, 1000)
      }
    }
    return Promise.reject(error)
  }
)

// Client API
export const clientsApi = {
  getAll: (params?: any) => api.get('/clients', { params }),
  getOne: (id: number) => api.get(`/clients/${id}`),
  create: (data: any) => api.post('/clients', data),
  update: (id: number, data: any) => api.put(`/clients/${id}`, data),
  delete: (id: number) => api.delete(`/clients/${id}`),
  stats: () => api.get('/clients/stats'),
  export: (params?: any) => api.get('/clients/export', { params, responseType: 'blob' }),
}

// Partner API
export const partnersApi = {
  getAll: (params?: any) => api.get('/partners', { params }),
  getOne: (id: number) => api.get(`/partners/${id}`),
  update: (id: number, data: any) => api.put(`/partners/${id}`, data),
  delete: (id: number) => api.delete(`/partners/${id}`),
  assignClient: (id: number, clientId: number) => api.post(`/partners/${id}/assign-client`, { client_id: clientId }),
  unassignClient: (id: number) => api.post(`/partners/${id}/unassign-client`),
  stats: () => api.get('/partners-stats'),
}

// Invoice API
export const invoicesApi = {
  getAll: (params?: any) => api.get('/invoices', { params }),
  getOne: (id: number) => api.get(`/invoices/${id}`),
  create: (data: any) => api.post('/invoices', data),
  update: (id: number, data: any) => api.put(`/invoices/${id}`, data),
  delete: (id: number) => api.delete(`/invoices/${id}`),
  generatePDF: (id: number) => api.get(`/invoices/${id}/pdf`, { responseType: 'blob' }),
  sendSDI: (id: number) => api.post(`/invoices/${id}/send-sdi`),
  stats: () => api.get('/invoices/stats'),
  export: (params?: any) => api.get('/invoices/export', { params, responseType: 'blob' }),
}

// Delivery API
export const deliveriesApi = {
  getAll: (params?: any) => api.get('/deliveries', { params }),
  getOne: (id: number) => api.get(`/deliveries/${id}`),
  create: (data: any) => api.post('/deliveries', data),
  update: (id: number, data: any) => api.put(`/deliveries/${id}`, data),
  delete: (id: number) => api.delete(`/deliveries/${id}`),
  updateStatus: (id: number, status: string) => api.patch(`/deliveries/${id}/status`, { status }),
  pregenerateInvoices: (params?: any) => api.get('/deliveries/invoices/pregenerate', { params }),
  generateInvoices: (data?: any) => api.post('/deliveries/invoices/generate', data),
  export: (params?: any) => api.get('/deliveries/export', { params, responseType: 'blob' }),
  stats: () => api.get('/deliveries-stats'),
}

// Contract API
export const contractsApi = {
  getAll: (params?: any) => api.get('/contracts', { params }),
  getOne: (id: number) => api.get(`/contracts/${id}`),
  create: (data: any) => api.post('/contracts', data),
  update: (id: number, data: any) => api.put(`/contracts/${id}`, data),
  delete: (id: number) => api.delete(`/contracts/${id}`),
  uploadFile: (id: number, file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    return api.post(`/contracts/${id}/upload`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  export: (params?: any) => api.get('/contracts/export', { params, responseType: 'blob' }),
}

// Task API
export const tasksApi = {
  getAll: (params?: any) => api.get('/tasks', { params }),
  getOne: (id: number) => api.get(`/tasks/${id}`),
  create: (data: any) => api.post('/tasks', data),
  update: (id: number, data: any) => api.put(`/tasks/${id}`, data),
  delete: (id: number) => api.delete(`/tasks/${id}`),
  updateStatus: (id: number, status: string) => api.patch(`/tasks/${id}/status`, { status }),
}

// Payment API
export const paymentsApi = {
  getAll: (params?: any) => api.get('/payments', { params }),
  getOne: (id: number) => api.get(`/payments/${id}`),
  create: (data: any) => api.post('/payments', data),
  update: (id: number, data: any) => api.put(`/payments/${id}`, data),
  delete: (id: number) => api.delete(`/payments/${id}`),
  stats: () => api.get('/payments/stats'),
  export: (params?: any) => api.get('/payments/export-csv', { params, responseType: 'blob' }),
}

// Order API
export const ordersApi = {
  getAll: (params?: any) => api.get('/orders', { params }),
  getOne: (id: number) => api.get(`/orders/${id}`),
  create: (data: any) => api.post('/orders', data),
  update: (id: number, data: any) => api.put(`/orders/${id}`, data),
  delete: (id: number) => api.delete(`/orders/${id}`),
  sync: (params?: any) => api.post('/orders/sync', params),
  export: (params?: any) => api.get('/orders/export', { params, responseType: 'blob' }),
}

// Restaurant API
export const restaurantsApi = {
  getAll: () => api.get('/oppla/restaurants'),
}

// Sync API
export const syncApi = {
  syncAll: () => api.post('/oppla/sync/all'), // Sync tutto (DB Oppla + Stripe + FIC)
  syncOpplaDatabase: () => api.post('/oppla/sync/database'), // Solo DB Oppla
  syncOpplaClients: () => api.post('/oppla/sync/clients'), // DEPRECATO
  testConnection: () => api.get('/oppla/sync/test'),
}

// Oppla Write API (REQUIRES CONFIRMATION)
export const opplaWriteApi = {
  requestConfirmation: (data: {
    operation: 'INSERT' | 'UPDATE' | 'DELETE'
    table: string
    data: Record<string, any>
    conditions?: Record<string, any>
  }) => api.post('/oppla/write/request-confirmation', data),

  execute: (token: string) => api.post('/oppla/write/execute', { token }),

  // Convenience methods for specific operations
  updateRestaurant: (id: number, data: Record<string, any>) =>
    api.post(`/oppla/restaurants/${id}/update`, data),

  createPartner: (data: Record<string, any>) =>
    api.post('/oppla/partners/create', data),

  updatePartner: (id: number, data: Record<string, any>) =>
    api.post(`/oppla/partners/${id}/update`, data),

  updateOrderStatus: (id: number, status: string) =>
    api.post(`/oppla/orders/${id}/update-status`, { status }),

  deleteOrder: (id: number) =>
    api.delete(`/oppla/orders/${id}`),
}

// Lead API (CRM)
export const leadsApi = {
  getAll: (params?: any) => api.get('/crm/leads', { params }),
  getOne: (id: number) => api.get(`/crm/leads/${id}`),
  create: (data: any) => api.post('/crm/leads', data),
  update: (id: number, data: any) => api.put(`/crm/leads/${id}`, data),
  delete: (id: number) => api.delete(`/crm/leads/${id}`),
  stats: () => api.get('/crm/leads/stats'),
  convertToClient: (id: number, data: any) => api.post(`/crm/leads/${id}/convert-to-client`, data),
  convertToOpportunity: (id: number, data: any) => api.post(`/crm/leads/${id}/convert-to-opportunity`, data),
  export: (params?: any) => api.get('/crm/leads/export', { params, responseType: 'blob' }),
}

// Auth API
export const authApi = {
  login: (email: string, password: string) => api.post('/login', { email, password }),
  register: (data: any) => api.post('/register', data),
  logout: () => api.post('/logout'),
  me: () => api.get('/me'),
}

// Menu API
export const menusApi = {
  getAll: (params?: any) => api.get('/menus', { params }),
  getOne: (id: number) => api.get(`/menus/${id}`),
  create: (data: any) => api.post('/menus', data),
  update: (id: number, data: any) => api.put(`/menus/${id}`, data),
  delete: (id: number) => api.delete(`/menus/${id}`),
  importCsv: (restaurantId: number, file: File) => {
    const formData = new FormData()
    formData.append('restaurant_id', restaurantId.toString())
    formData.append('file', file)
    return api.post('/menus/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  exportCsv: (restaurantId: number) => api.get('/menus/export', {
    params: { restaurant_id: restaurantId },
    responseType: 'blob'
  }),
  getCategories: (restaurantId?: number) => api.get('/menus/categories', {
    params: restaurantId ? { restaurant_id: restaurantId } : {}
  }),
  getImportHistory: (restaurantId: number) => api.get('/menus/import-history', {
    params: { restaurant_id: restaurantId }
  }),
  bulkUpdate: (ids: number[], updates: any) => api.post('/menus/bulk-update', {
    ids,
    updates
  }),
}

// Trash API (Cestino)
export const trashApi = {
  getAll: (params?: { type?: string; page?: number; per_page?: number }) =>
    api.get('/trash', { params }),
  restore: (type: string, id: number) =>
    api.post(`/trash/${type}/${id}/restore`),
  forceDelete: (type: string, id: number) =>
    api.delete(`/trash/${type}/${id}`),
  empty: () =>
    api.delete('/trash/empty'),
}

// Financial Entries API (Gestione Finanziaria)
export const financialEntriesApi = {
  getAll: (params?: any) => api.get('/financial-entries', { params }),
  getOne: (id: number) => api.get(`/financial-entries/${id}`),
  create: (data: any) => api.post('/financial-entries', data),
  update: (id: number, data: any) => api.put(`/financial-entries/${id}`, data),
  delete: (id: number) => api.delete(`/financial-entries/${id}`),
  summary: (params?: any) => api.get('/financial-entries/summary', { params }),
}

// Cancellation API (Annullamento unificato ordini/consegne)
export const cancellationApi = {
  preview: (type: 'order' | 'delivery', id: number) =>
    api.post('/cancel/preview', { type, id }),
  execute: (confirmationToken: string) =>
    api.post('/cancel/execute', { confirmation_token: confirmationToken }),
}

// Accounting Categories API
export const accountingCategoriesApi = {
  getAll: () => api.get('/accounting/categories'),
}

// Suppliers API (Fornitori)
export const suppliersApi = {
  getAll: (params?: any) => api.get('/suppliers', { params }),
  getOne: (id: number) => api.get(`/suppliers/${id}`),
  create: (data: any) => api.post('/suppliers', data),
  update: (id: number, data: any) => api.put(`/suppliers/${id}`, data),
  delete: (id: number) => api.delete(`/suppliers/${id}`),
  stats: () => api.get('/suppliers/stats'),
  search: (q: string) => api.get('/suppliers/search', { params: { q } }),
  export: (params?: any) => api.get('/suppliers/export', { params, responseType: 'blob' }),
}

// Supplier Invoices API (Fatture Passive)
export const supplierInvoicesApi = {
  getAll: (params?: any) => api.get('/supplier-invoices', { params }),
  getOne: (id: number) => api.get(`/supplier-invoices/${id}`),
  create: (data: any) => api.post('/supplier-invoices', data),
  update: (id: number, data: any) => api.put(`/supplier-invoices/${id}`, data),
  delete: (id: number) => api.delete(`/supplier-invoices/${id}`),
  stats: (params?: any) => api.get('/supplier-invoices/stats', { params }),
  markPaid: (id: number, data?: any) => api.post(`/supplier-invoices/${id}/mark-paid`, data),
  export: (params?: any) => api.get('/supplier-invoices/export', { params, responseType: 'blob' }),
}

// Riders API
export const ridersApi = {
  getAll: (params?: any) => api.get('/riders', { params }),
  getOne: (fleetId: string) => api.get(`/riders/${fleetId}`),
  export: (params?: any) => api.get('/riders/export', { params, responseType: 'blob' }),
}

export default api
