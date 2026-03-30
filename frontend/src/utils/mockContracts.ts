export interface Contract {
  id: number
  title: string
  client_id: number
  client_name?: string
  contract_type: string
  status: 'attivo' | 'in_scadenza' | 'scaduto' | 'bozza'
  start_date: string
  end_date: string
  value?: number
  created_at: string
}

export interface ContractTemplate {
  id: number
  name: string
  description: string
  template_content: string
}

// Mock data per contratti
export const mockContracts: Contract[] = [
  {
    id: 1,
    title: "Contratto di Sviluppo App Mobile",
    client_id: 1,
    client_name: "Sushi No Experience",
    contract_type: "sviluppo",
    status: "attivo",
    start_date: "2024-01-15",
    end_date: "2024-12-15",
    value: 15000,
    created_at: "2024-01-15T10:00:00Z"
  },
  {
    id: 2,
    title: "Servizio di Delivery Management",
    client_id: 2,
    client_name: "Marco Dell'Omodarme",
    contract_type: "servizio",
    status: "attivo",
    start_date: "2024-02-01",
    end_date: "2025-02-01",
    value: 8400,
    created_at: "2024-02-01T09:00:00Z"
  },
  {
    id: 3,
    title: "Integrazione Sistema POS",
    client_id: 3,
    client_name: "Andrea Scalabrella",
    contract_type: "integrazione",
    status: "in_scadenza",
    start_date: "2024-03-01",
    end_date: "2024-12-31",
    value: 5200,
    created_at: "2024-03-01T14:30:00Z"
  },
  {
    id: 4,
    title: "Consulenza Digitalizzazione",
    client_id: 4,
    client_name: "Massimiliano Silvestri",
    contract_type: "consulenza",
    status: "bozza",
    start_date: "2024-12-15",
    end_date: "2025-06-15",
    value: 7800,
    created_at: "2024-12-01T16:00:00Z"
  },
  {
    id: 5,
    title: "Manutenzione Sistema Legacy",
    client_id: 5,
    client_name: "Enrico Ungheretti",
    contract_type: "manutenzione",
    status: "scaduto",
    start_date: "2023-06-01",
    end_date: "2024-06-01",
    value: 3600,
    created_at: "2023-06-01T11:00:00Z"
  },
  {
    id: 6,
    title: "Sviluppo E-commerce",
    client_id: 6,
    client_name: "Maria Abi Nader",
    contract_type: "sviluppo",
    status: "attivo",
    start_date: "2024-05-01",
    end_date: "2025-05-01",
    value: 12000,
    created_at: "2024-05-01T13:15:00Z"
  }
]

// Mock data per template contratti
export const mockContractTemplates: ContractTemplate[] = [
  {
    id: 1,
    name: "Template Sviluppo Software",
    description: "Contratto standard per progetti di sviluppo software su misura",
    template_content: `
CONTRATTO DI SVILUPPO SOFTWARE

Tra [CLIENTE] e [FORNITORE]

OGGETTO: Sviluppo di applicazione software personalizzata

TERMINI E CONDIZIONI:
1. Descrizione del progetto
2. Tempistiche di consegna
3. Condizioni economiche
4. Modalità di pagamento
5. Garanzie e supporto
...
    `
  },
  {
    id: 2,
    name: "Template Servizi di Manutenzione",
    description: "Contratto per servizi di manutenzione e supporto tecnico",
    template_content: `
CONTRATTO DI MANUTENZIONE E SUPPORTO

Tra [CLIENTE] e [FORNITORE]

OGGETTO: Servizi di manutenzione e supporto tecnico

TERMINI E CONDIZIONI:
1. Servizi inclusi
2. Livelli di servizio (SLA)
3. Modalità di intervento
4. Costi e fatturazione
5. Durata del contratto
...
    `
  },
  {
    id: 3,
    name: "Template Consulenza",
    description: "Contratto standard per servizi di consulenza specialistica",
    template_content: `
CONTRATTO DI CONSULENZA SPECIALISTICA

Tra [CLIENTE] e [FORNITORE]

OGGETTO: Servizi di consulenza e analisi

TERMINI E CONDIZIONI:
1. Ambito della consulenza
2. Deliverable attesi
3. Metodologia di lavoro
4. Tariffe e modalità di pagamento
5. Riservatezza
...
    `
  },
  {
    id: 4,
    name: "Template Integrazione Sistemi",
    description: "Contratto per progetti di integrazione e migrazione sistemi",
    template_content: `
CONTRATTO DI INTEGRAZIONE SISTEMI

Tra [CLIENTE] e [FORNITORE]

OGGETTO: Integrazione e migrazione sistemi informatici

TERMINI E CONDIZIONI:
1. Sistemi coinvolti
2. Fasi di migrazione
3. Test e collaudi
4. Formazione del personale
5. Garanzie post go-live
...
    `
  }
]

// Funzioni helper per simulare le API
export const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

export const mockFetchContracts = async (): Promise<Contract[]> => {
  await delay(500) // Simula tempo di caricamento
  return mockContracts
}

export const mockFetchTemplates = async (): Promise<ContractTemplate[]> => {
  await delay(300) // Simula tempo di caricamento
  return mockContractTemplates
}

export const mockCreateContract = async (contractData: any): Promise<Contract> => {
  await delay(800) // Simula tempo di elaborazione
  
  const newContract: Contract = {
    id: Math.max(...mockContracts.map(c => c.id)) + 1,
    title: contractData.title,
    client_id: parseInt(contractData.client_id),
    client_name: `Cliente ${contractData.client_id}`,
    contract_type: contractData.contract_type,
    status: 'bozza',
    start_date: contractData.start_date,
    end_date: contractData.end_date,
    value: contractData.value ? parseFloat(contractData.value) : undefined,
    created_at: new Date().toISOString()
  }
  
  mockContracts.push(newContract)
  return newContract
}