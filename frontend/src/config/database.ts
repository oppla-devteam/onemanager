// Configurazione per la connessione al database PostgreSQL
export interface DatabaseConfig {
  host: string
  port: number
  database: string
  username: string
  password: string
  ssl?: boolean
  readonly?: boolean
}

// Configurazione di default (modifica con i tuoi parametri)
export const defaultDbConfig: DatabaseConfig = {
  host: process.env.VITE_DB_HOST || 'localhost',
  port: parseInt(process.env.VITE_DB_PORT || '5432'),
  database: process.env.VITE_DB_NAME || 'oppla_db',
  username: process.env.VITE_DB_USER || 'readonly_user',
  password: process.env.VITE_DB_PASSWORD || '',
  ssl: process.env.VITE_DB_SSL === 'true',
  readonly: true
}

// Query SQL per recuperare i clienti
export const SQL_QUERIES = {
  getAllClients: `
    SELECT 
      id,
      type,
      email,
      phone,
      first_name,
      last_name,
      name,
      created_at,
      updated_at,
      deleted_at
    FROM users 
    WHERE type = 'partner' 
    AND deleted_at IS NULL
    ORDER BY created_at DESC
  `,
  
  getClientById: `
    SELECT 
      id,
      type,
      email,
      phone,
      first_name,
      last_name,
      name,
      created_at,
      updated_at
    FROM users 
    WHERE id = $1 
    AND type = 'partner' 
    AND deleted_at IS NULL
  `,
  
  searchClients: `
    SELECT 
      id,
      type,
      email,
      phone,
      first_name,
      last_name,
      name,
      created_at,
      updated_at
    FROM users 
    WHERE type = 'partner' 
    AND deleted_at IS NULL
    AND (
      LOWER(name) LIKE LOWER($1) OR
      LOWER(email) LIKE LOWER($1) OR
      phone LIKE $1
    )
    ORDER BY created_at DESC
    LIMIT $2
  `,
  
  getClientStats: `
    SELECT 
      COUNT(*) as total_clients,
      COUNT(email) as clients_with_email,
      COUNT(phone) as clients_with_phone
    FROM users 
    WHERE type = 'partner' 
    AND deleted_at IS NULL
  `
}