import { motion } from 'framer-motion'
import { useState, useEffect } from 'react'
import { X, Mail, Search, User, Send, FileText, CheckCircle } from 'lucide-react'

interface Client {
  id: number
  ragione_sociale: string
  email: string
  phone?: string
  piva?: string
  type: string
}

interface Contract {
  id: number
  contract_number?: string
  subject?: string
  title?: string
  client_name?: string
  client_email?: string
}

interface SendContractModalProps {
  contract: Contract
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
}

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export default function SendContractModal({ contract, isOpen, onClose, onSuccess }: SendContractModalProps) {
  const [clients, setClients] = useState<Client[]>([])
  const [selectedClient, setSelectedClient] = useState<Client | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [customMessage, setCustomMessage] = useState('')
  const [loading, setLoading] = useState(false)
  const [sending, setSending] = useState(false)

  // Blocca lo scroll del body quando il modale è aperto
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = 'unset'
    }
    
    return () => {
      document.body.style.overflow = 'unset'
    }
  }, [isOpen])

  useEffect(() => {
    if (isOpen) {
      fetchClients()
    }
  }, [isOpen])

  const fetchClients = async () => {
    try {
      setLoading(true)
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts/clients`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const data = await response.json()
        setClients(data)
      }
    } catch (error) {
      console.error('Error fetching clients:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleSend = async () => {
    if (!selectedClient) {
      alert('Seleziona un cliente')
      return
    }

    try {
      setSending(true)
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/contracts/${contract.id}/send-for-signature`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email: selectedClient.email,
          message: customMessage || null
        })
      })

      if (response.ok) {
        const result = await response.json()
        alert(`${result.message}\n\n📧 Inviato a: ${result.sent_to}`)
        onSuccess()
        onClose()
      } else {
        const error = await response.json()
        alert(`Errore: ${error.message || 'Impossibile inviare il contratto'}`)
      }
    } catch (error) {
      console.error('Error sending contract:', error)
      alert('Errore durante l\'invio del contratto')
    } finally {
      setSending(false)
    }
  }

  const filteredClients = clients.filter(client =>
    client.ragione_sociale.toLowerCase().includes(searchTerm.toLowerCase()) ||
    client.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (client.piva && client.piva.includes(searchTerm))
  )

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50">
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        exit={{ opacity: 0, scale: 0.95 }}
        className="glass-card w-full h-full flex flex-col overflow-hidden"
      >
        {/* Header - Fixed */}
        <div className="p-6 border-b border-gray-200 flex-shrink-0">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-lg bg-primary-500/20">
                <Mail className="w-6 h-6 text-primary-400" />
              </div>
              <div>
                <h2 className="text-xl font-bold text-white">Invia Contratto via Email</h2>
                <p className="text-sm text-gray-500">
                  {contract.contract_number || `#${contract.id}`} - {contract.subject || contract.title}
                </p>
              </div>
            </div>
            <button
              onClick={onClose}
              className="p-2 hover:bg-white/10 rounded-lg transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-6 space-y-6">
          {/* Search Clients */}
          <div>
            <label className="block text-sm font-medium mb-2 flex items-center gap-2">
              <User className="w-4 h-4 text-primary-400" />
              Seleziona Cliente
            </label>
            <div className="relative mb-3">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5" />
              <input
                type="text"
                placeholder="Cerca per nome, email o P.IVA..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="glass-input pl-10 w-full"
              />
            </div>

            {/* Clients List */}
            <div className="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
              {loading ? (
                <div className="text-center py-8 text-gray-500">
                  Caricamento clienti...
                </div>
              ) : filteredClients.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  Nessun cliente trovato
                </div>
              ) : (
                filteredClients.map((client) => (
                  <button
                    key={client.id}
                    onClick={() => setSelectedClient(client)}
                    className={`w-full text-left p-4 rounded-lg border transition-all ${
                      selectedClient?.id === client.id
                        ? 'bg-primary-500/20 border-primary-500/50 shadow-lg shadow-primary-500/20'
                        : 'bg-white/5 border-gray-200 hover:bg-white/10'
                    }`}
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="font-semibold text-white mb-1">
                          {client.ragione_sociale}
                        </div>
                        <div className="text-sm text-gray-500 space-y-1">
                          <div className="flex items-center gap-2">
                            <Mail className="w-3 h-3" />
                            {client.email}
                          </div>
                          {client.piva && (
                            <div>P.IVA: {client.piva}</div>
                          )}
                        </div>
                      </div>
                      <span className="text-xs px-2 py-1 rounded bg-white/10">
                        {client.type.replace('_', ' ')}
                      </span>
                    </div>
                  </button>
                ))
              )}
            </div>
          </div>

          {/* Selected Client Info */}
          {selectedClient && (
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              className="p-4 rounded-lg bg-green-500/10 border border-green-500/30"
            >
              <div className="flex items-center gap-2 text-green-400 mb-2">
                <CheckCircle className="w-5 h-5" />
                <span className="font-semibold">Cliente Selezionato</span>
              </div>
              <div className="text-sm text-gray-600">
                <div><strong>Nome:</strong> {selectedClient.ragione_sociale}</div>
                <div><strong>Email:</strong> {selectedClient.email}</div>
                {selectedClient.phone && <div><strong>Telefono:</strong> {selectedClient.phone}</div>}
              </div>
            </motion.div>
          )}

          {/* Custom Message */}
          <div>
            <label className="block text-sm font-medium mb-2">
              Messaggio Personalizzato (opzionale)
            </label>
            <textarea
              value={customMessage}
              onChange={(e) => setCustomMessage(e.target.value)}
              rows={4}
              placeholder="Aggiungi un messaggio personalizzato che verrà incluso nell'email..."
              className="glass-input w-full resize-none"
              maxLength={1000}
            />
            <div className="text-xs text-gray-500 mt-1">
              {customMessage.length}/1000 caratteri
            </div>
          </div>

          {/* Preview Info */}
          <div className="p-4 rounded-lg bg-primary-500/10 border border-primary-500/30">
            <div className="flex items-start gap-3">
              <FileText className="w-5 h-5 text-primary-400 mt-0.5" />
              <div className="text-sm text-gray-600">
                <div className="font-semibold text-primary-400 mb-1">Cosa verrà inviato:</div>
                <ul className="list-disc list-inside space-y-1 text-gray-500">
                  <li>Email con link per la firma del contratto</li>
                  <li>PDF del contratto in allegato</li>
                  <li>Codice OTP per la firma elettronica</li>
                  <li>Istruzioni per completare la procedura</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="p-6 border-t border-gray-200">
          <div className="flex gap-3">
            <button
              onClick={onClose}
              className="glass-button flex-1"
              disabled={sending}
            >
              Annulla
            </button>
            <button
              onClick={handleSend}
              disabled={!selectedClient || sending}
              className="glass-button-primary flex-1 flex items-center justify-center gap-2"
            >
              {sending ? (
                <>
                  <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  Invio in corso...
                </>
              ) : (
                <>
                  <Send className="w-4 h-4" />
                  Invia Contratto
                </>
              )}
            </button>
          </div>
        </div>
      </motion.div>
    </div>
  )
}
