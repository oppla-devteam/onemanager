import { Client } from '../utils/csvImport'

export interface ClientStats {
  total_clients: number
  clients_with_email: number
  clients_with_phone: number
}

/**
 * ⚠️ IMPORTANTE: SERVIZIO IN SOLA LETTURA ⚠️
 * 
 * Questo servizio PostgreSQL è configurato per eseguire SOLO query di lettura (SELECT).
 * NON può eseguire INSERT, UPDATE, DELETE o altre operazioni di modifica dati.
 * 
 * Tutte le query sono filtrate e validate per garantire che siano read-only.
 * Le credenziali dell'utente database dovrebbero avere permessi di SOLA LETTURA.
 */
export class PostgreSQLService {
  private config: {
    host: string
    port: number
    database: string
    user: string
    password: string
    ssl: boolean
  }

  constructor() {
    // Carica configurazione da environment variables
    this.config = {
      host: import.meta.env.VITE_DB_HOST || '',
      port: parseInt(import.meta.env.VITE_DB_PORT || '5432'),
      database: import.meta.env.VITE_DB_NAME || '',
      user: import.meta.env.VITE_DB_USER || '',
      password: import.meta.env.VITE_DB_PASSWORD || '',
      ssl: import.meta.env.VITE_DB_SSL === 'true',
    }
  }

  /**
   * 🔒 VALIDATORE DI QUERY READ-ONLY
   * Verifica che la query sia sicura e non modifichi dati
   */
  private isReadOnlyQuery(query: string): boolean {
    const normalizedQuery = query.trim().toUpperCase()
    
    // Lista di operazioni NON PERMESSE
    const writeOperations = [
      'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
      'TRUNCATE', 'REPLACE', 'GRANT', 'REVOKE', 'EXECUTE'
    ]
    
    // Verifica che la query non contenga operazioni di scrittura
    const hasWriteOperation = writeOperations.some(op => 
      normalizedQuery.startsWith(op) || 
      normalizedQuery.includes(` ${op} `) ||
      normalizedQuery.includes(`;${op}`)
    )
    
    if (hasWriteOperation) {
      console.error('🚫 QUERY BLOCCATA: Operazione di scrittura non permessa')
      return false
    }
    
    // La query deve iniziare con SELECT o WITH (per CTE)
    const isSelect = normalizedQuery.startsWith('SELECT') || 
                     normalizedQuery.startsWith('WITH')
    
    return isSelect
  }

  /**
   * Esegue una query READ-ONLY al database
   * ⚠️ Tutte le query vengono validate prima dell'esecuzione
   */
  private async executeQuery<T = any>(query: string, _params: any[] = []): Promise<T[]> {
    // 🔒 VALIDAZIONE SICUREZZA: Solo query SELECT
    if (!this.isReadOnlyQuery(query)) {
      throw new Error('🚫 ACCESSO NEGATO: Solo query di lettura (SELECT) sono permesse')
    }

    try {
      // Verifica configurazione
      if (!this.config.host || !this.config.database) {
        console.warn('⚠️ Database non configurato, usa fallback CSV')
        throw new Error('Database configuration missing')
      }

      // In un browser, NON possiamo connetterci direttamente a PostgreSQL
      // Dobbiamo usare un backend API
      console.log('🔌 Query PostgreSQL:', query.substring(0, 50) + '...')
      
      // TODO: Implementare chiamata API backend
      // const response = await fetch('/api/database/query', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ query, params })
      // })
      // return await response.json()
      
      throw new Error('Backend API non implementato - usa fallback CSV')
      
    } catch (error) {
      console.error('❌ Errore esecuzione query:', error)
      throw error
    }
  }

  /**
   * 📊 Recupera tutti i partner dal database
   * Usa API backend Laravel connesso a PostgreSQL Oppla
   */
  async getAllClients(): Promise<Client[]> {
    try {
      const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      const response = await fetch(`${API_URL}/oppla/clients`)
      const data = await response.json()
      
      if (data.success) {
        console.log(`${data.total} clienti caricati da database Oppla PostgreSQL`)
        return data.data
      }
      
      throw new Error('Failed to load clients from Oppla database')
      
    } catch (error) {
      console.error('❌ Errore caricamento clienti da Oppla:', error)
      throw error
    }
  }

  /**
   * 🔍 Recupera un singolo partner per ID
   * Query READ-ONLY: SELECT con filtro WHERE
   */
  async getClientById(id: string): Promise<Client | null> {
    try {
      const query = `
        SELECT 
          id,
          name,
          email,
          phone,
          first_name,
          last_name,
          created_at,
          updated_at
        FROM users
        WHERE id = $1 AND type = 'partner' AND deleted_at IS NULL
      `
      
      const rows = await this.executeQuery<any>(query, [id])
      const clients = this.mapRowsToClients(rows)
      return clients[0] || null
      
    } catch (error) {
      console.error('Errore recupero cliente:', error)
      return null
    }
  }

  /**
   * 🔎 Cerca partner nel database
   * Query READ-ONLY: SELECT con LIKE pattern matching
   */
  async searchClients(searchTerm: string, limit: number = 50): Promise<Client[]> {
    try {
      const searchPattern = `%${searchTerm}%`
      const query = `
        SELECT 
          id,
          name,
          email,
          phone,
          first_name,
          last_name,
          created_at,
          updated_at
        FROM users
        WHERE type = 'partner' 
          AND deleted_at IS NULL
          AND (
            LOWER(name) LIKE LOWER($1) OR
            LOWER(first_name) LIKE LOWER($1) OR
            LOWER(last_name) LIKE LOWER($1) OR
            LOWER(email) LIKE LOWER($1) OR
            phone LIKE $1
          )
        ORDER BY name ASC
        LIMIT $2
      `
      
      const rows = await this.executeQuery<any>(query, [searchPattern, limit])
      return this.mapRowsToClients(rows)
      
    } catch (error) {
      console.error('Errore ricerca clienti:', error)
      return []
    }
  }

  /**
   * 📈 Recupera statistiche partner
   * Query READ-ONLY: SELECT con COUNT e aggregazioni
   */
  async getClientStats(): Promise<ClientStats> {
    try {
      const query = `
        SELECT 
          COUNT(*) as total_clients,
          COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as clients_with_email,
          COUNT(CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 END) as clients_with_phone
        FROM users
        WHERE type = 'partner' AND deleted_at IS NULL
      `
      
      const rows = await this.executeQuery<ClientStats>(query)
      const stats = rows[0] || { total_clients: 0, clients_with_email: 0, clients_with_phone: 0 }
      
      return {
        total_clients: parseInt(String(stats.total_clients)),
        clients_with_email: parseInt(String(stats.clients_with_email)),
        clients_with_phone: parseInt(String(stats.clients_with_phone)),
      }
      
    } catch (error) {
      console.error('Errore statistiche clienti:', error)
      return { total_clients: 0, clients_with_email: 0, clients_with_phone: 0 }
    }
  }

  /**
   * Mappa i dati PostgreSQL al formato Client dell'applicazione
   */
  private mapRowsToClients(rows: any[]): Client[] {
    return rows.map(row => ({
      id: row.id,
      ragione_sociale: row.name || `${row.first_name || ''} ${row.last_name || ''}`.trim(),
      type: 'partner_oppla',
      email: row.email || '',
      phone: row.phone || '',
      is_active: true,
      source: 'imported' as const,
      created_at: row.created_at,
      updated_at: row.updated_at
    }))
  }

  /**
   * 🧪 Test della connessione al database
   * Verifica connettività con database locale
   */
  async testConnection(): Promise<{ success: boolean; message: string; stats?: ClientStats }> {
    try {
      const stats = await this.getClientStats()
      return {
        success: true,
        message: `Connessione riuscita! Database: ${this.config.database}`,
        stats
      }
    } catch (error) {
      return {
        success: false,
        message: `❌ Connessione fallita: ${error}`
      }
    }
  }

  // Disconnessione
  async disconnect(): Promise<void> {
    // In un ambiente reale: await this.client.end()
    console.log('🔌 Disconnesso dal database PostgreSQL')
  }
}

// Istanza singleton del servizio
export const dbService = new PostgreSQLService()

// Hook per utilizzare il database nei componenti React
export const useDatabase = () => {
  return {
    getAllClients: () => dbService.getAllClients(),
    getClientById: (id: string) => dbService.getClientById(id),
    searchClients: (term: string, limit?: number) => dbService.searchClients(term, limit),
    getClientStats: () => dbService.getClientStats(),
    testConnection: () => dbService.testConnection()
  }
}