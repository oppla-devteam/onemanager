// Servizio per sincronizzazione database Oppla
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export interface SyncResult {
  success: boolean
  message: string
  output?: string
  synced_at?: string
  error?: string
  data?: any
}

export interface SyncStats {
  total_partners: number
  last_sync?: string
  status: 'connected' | 'disconnected' | 'syncing'
}

/**
 * Test connessione al database PostgreSQL
 */
export async function testDatabaseConnection(): Promise<{ success: boolean; message: string; total_partners?: number }> {
  try {
    const response = await fetch(`${API_URL}/database/test`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const data = await response.json()
    return data
  } catch (error) {
    console.error('Errore test connessione:', error)
    return {
      success: false,
      message: error instanceof Error ? error.message : 'Errore connessione al server'
    }
  }
}

/**
 * Sincronizzazione manuale partners da Oppla
 */
export async function syncOpplaPartners(): Promise<SyncResult> {
  try {
    const response = await fetch(`${API_URL}/database/sync`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const data = await response.json()
    return data
  } catch (error) {
    console.error('Errore sincronizzazione:', error)
    return {
      success: false,
      message: 'Errore durante la sincronizzazione',
      error: error instanceof Error ? error.message : 'Errore sconosciuto'
    }
  }
}

/**
 * Sincronizza pagamenti Stripe
 */
export async function syncStripePayments(days: number = 30): Promise<SyncResult> {
  try {
    const response = await fetch(`${API_URL}/stripe/sync`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ days }),
    })
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const data = await response.json()
    return data
  } catch (error) {
    console.error('Errore sincronizzazione Stripe:', error)
    return {
      success: false,
      message: 'Errore durante la sincronizzazione Stripe',
      error: error instanceof Error ? error.message : 'Errore sconosciuto'
    }
  }
}

/**
 * Recupera statistiche sincronizzazione
 */
export async function getSyncStats(): Promise<SyncStats> {
  try {
    const response = await fetch(`${API_URL}/database/stats`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const data = await response.json()
    
    if (data.success) {
      return {
        total_partners: data.data.total_partners,
        status: 'connected'
      }
    }
    
    return {
      total_partners: 0,
      status: 'disconnected'
    }
  } catch (error) {
    console.error('Errore recupero statistiche:', error)
    return {
      total_partners: 0,
      status: 'disconnected'
    }
  }
}
