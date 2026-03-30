export interface ImportedClient {
  id: string
  type: 'partner' | 'customer' | 'admin'
  email: string
  phone?: string
  first_name: string
  last_name: string
  name: string
  created_at: string
  updated_at: string
  deleted_at?: string
}

export interface Client {
  id: string
  ragione_sociale: string
  type: string
  tipo_societa?: 'societa' | 'ditta_individuale'
  email: string
  piva?: string
  phone?: string
  telefono?: string
  indirizzo?: string
  citta?: string
  provincia?: string
  cap?: string
  codice_fiscale?: string
  codice_fiscale_titolare?: string
  pec?: string
  sdi_code?: string
  is_active: boolean
  source?: 'imported' | 'manual' | 'stripe_auto'
  oppla_user_id?: number | null
  oppla_restaurant_ids?: number[]
  created_at?: string
  updated_at?: string
}

export function parseCSVToClients(csvContent: string): Client[] {
  const lines = csvContent.split('\n')
  const headers = lines[0].split(',').map(h => h.replace(/"/g, ''))
  
  const clients: Client[] = []
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim()
    if (!line) continue
    
    // Parse CSV line considering quoted values
    const values = parseCSVLine(line)
    if (values.length < headers.length) continue
    
    const row: any = {}
    headers.forEach((header, index) => {
      row[header] = values[index] || ''
    })
    
    // Filter only partners and active users
    if (row.type === 'partner' && !row.deleted_at) {
      const client: Client = {
        id: row.id,
        ragione_sociale: row.name || `${row.first_name} ${row.last_name}`.trim(),
        type: 'partner_oppla',
        email: row.email,
        phone: row.phone,
        is_active: true,
        source: 'imported',
        created_at: row.created_at,
        updated_at: row.updated_at
      }
      clients.push(client)
    }
  }
  
  return clients
}

function parseCSVLine(line: string): string[] {
  const result: string[] = []
  let current = ''
  let inQuotes = false
  let i = 0
  
  while (i < line.length) {
    const char = line[i]
    
    if (char === '"') {
      if (inQuotes && line[i + 1] === '"') {
        // Escaped quote
        current += '"'
        i += 2
      } else {
        // Toggle quote state
        inQuotes = !inQuotes
        i++
      }
    } else if (char === ',' && !inQuotes) {
      // Field separator
      result.push(current)
      current = ''
      i++
    } else {
      current += char
      i++
    }
  }
  
  // Add the last field
  result.push(current)
  
  return result
}

// Load CSV data from file
export async function loadClientsFromCSV(): Promise<Client[]> {
  try {
    const response = await fetch('/users.csv')
    if (!response.ok) {
      throw new Error('Failed to load CSV file')
    }
    const csvContent = await response.text()
    return parseCSVToClients(csvContent)
  } catch (error) {
    console.error('Error loading CSV:', error)
    return []
  }
}