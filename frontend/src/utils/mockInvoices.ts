export interface Invoice {
  id: number
  numero_fattura: string
  data_emissione: string
  client_id: number
  client_name: string
  importo_imponibile: number
  importo_iva: number
  importo_totale: number
  status: 'bozza' | 'emessa' | 'pagata' | 'scaduta'
  type: 'ordinaria' | 'differita'
  created_at: string
}

// Mock data per fatture
export const mockInvoices: Invoice[] = [
  {
    id: 1,
    numero_fattura: "2024/001",
    data_emissione: "2024-01-15",
    client_id: 1,
    client_name: "Sushi No Experience",
    importo_imponibile: 8500.00,
    importo_iva: 1870.00,
    importo_totale: 10370.00,
    status: "pagata",
    type: "ordinaria",
    created_at: "2024-01-15T10:00:00Z"
  },
  {
    id: 2,
    numero_fattura: "2024/002",
    data_emissione: "2024-02-01",
    client_id: 2,
    client_name: "Marco Dell'Omodarme",
    importo_imponibile: 2500.00,
    importo_iva: 550.00,
    importo_totale: 3050.00,
    status: "emessa",
    type: "ordinaria",
    created_at: "2024-02-01T14:30:00Z"
  },
  {
    id: 3,
    numero_fattura: "2024/003",
    data_emissione: "2024-03-10",
    client_id: 3,
    client_name: "Andrea Scalabrella",
    importo_imponibile: 1800.00,
    importo_iva: 396.00,
    importo_totale: 2196.00,
    status: "scaduta",
    type: "ordinaria",
    created_at: "2024-03-10T09:15:00Z"
  },
  {
    id: 4,
    numero_fattura: "2024/004",
    data_emissione: "2024-11-20",
    client_id: 4,
    client_name: "Massimiliano Silvestri",
    importo_imponibile: 5200.00,
    importo_iva: 1144.00,
    importo_totale: 6344.00,
    status: "bozza",
    type: "ordinaria",
    created_at: "2024-11-20T16:45:00Z"
  },
  {
    id: 5,
    numero_fattura: "2024/005",
    data_emissione: "2024-12-01",
    client_id: 5,
    client_name: "Enrico Ungheretti",
    importo_imponibile: 3200.00,
    importo_iva: 704.00,
    importo_totale: 3904.00,
    status: "emessa",
    type: "differita",
    created_at: "2024-12-01T11:20:00Z"
  }
]

// Funzione per generare il prossimo numero fattura
export const generateInvoiceNumber = (): string => {
  const currentYear = new Date().getFullYear()
  const existingInvoices = mockInvoices.filter(inv => 
    inv.numero_fattura.startsWith(`${currentYear}/`)
  )
  const nextNumber = existingInvoices.length + 1
  return `${currentYear}/${nextNumber.toString().padStart(3, '0')}`
}

// Funzioni helper per simulare le API
export const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

export const mockFetchInvoices = async (): Promise<Invoice[]> => {
  await delay(600)
  return [...mockInvoices]
}

export const mockCreateInvoice = async (invoiceData: any): Promise<Invoice> => {
  await delay(1000)
  
  const newInvoice: Invoice = {
    id: Math.max(...mockInvoices.map(i => i.id)) + 1,
    numero_fattura: invoiceData.numero_fattura || generateInvoiceNumber(),
    data_emissione: invoiceData.data_emissione,
    client_id: parseInt(invoiceData.client_id),
    client_name: invoiceData.client_name || `Cliente ${invoiceData.client_id}`,
    importo_imponibile: parseFloat(invoiceData.importo_imponibile),
    importo_iva: parseFloat(invoiceData.importo_iva),
    importo_totale: parseFloat(invoiceData.importo_imponibile) + parseFloat(invoiceData.importo_iva),
    status: 'bozza',
    type: invoiceData.type,
    created_at: new Date().toISOString()
  }
  
  mockInvoices.push(newInvoice)
  return newInvoice
}

export const mockUpdateInvoice = async (id: number, invoiceData: any): Promise<Invoice> => {
  await delay(800)
  
  const index = mockInvoices.findIndex(i => i.id === id)
  if (index === -1) throw new Error('Fattura non trovata')
  
  const updatedInvoice = {
    ...mockInvoices[index],
    ...invoiceData,
    importo_totale: parseFloat(invoiceData.importo_imponibile) + parseFloat(invoiceData.importo_iva)
  }
  
  mockInvoices[index] = updatedInvoice
  return updatedInvoice
}

export const mockDeleteInvoice = async (id: number): Promise<void> => {
  await delay(400)
  
  const index = mockInvoices.findIndex(i => i.id === id)
  if (index === -1) throw new Error('Fattura non trovata')
  
  mockInvoices.splice(index, 1)
}