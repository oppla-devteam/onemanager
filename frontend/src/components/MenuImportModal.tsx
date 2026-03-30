import { motion } from 'framer-motion'
import { X, Upload, FileText, AlertCircle, CheckCircle, Download } from 'lucide-react'
import { useState } from 'react'
import { menusApi } from '../utils/api'

interface MenuImportModalProps {
  isOpen: boolean
  onClose: () => void
  restaurantId: number | null
}

export default function MenuImportModal({
  isOpen,
  onClose,
  restaurantId,
}: MenuImportModalProps) {
  const [file, setFile] = useState<File | null>(null)
  const [importing, setImporting] = useState(false)
  const [results, setResults] = useState<any>(null)

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setFile(e.target.files[0])
      setResults(null)
    }
  }

  const handleImport = async () => {
    if (!file || !restaurantId) {
      alert('Please select a file and restaurant')
      return
    }

    setImporting(true)
    setResults(null)

    try {
      const response = await menusApi.importCsv(restaurantId, file)
      setResults(response.data.results)
      alert('Import completed!')
    } catch (error: any) {
      console.error('Import error:', error)
      alert('Import failed: ' + (error.response?.data?.message || error.message))
    } finally {
      setImporting(false)
    }
  }

  const downloadTemplate = () => {
    const csv = `category,product_name,price_cents,description,available_for_delivery,available_for_pickup,image_url
BIRRE ARTIGIANALI,Birra Corona,500,Birra messicana chiara e rinfrescante,true,true,https://example.com/corona.jpg
ANTIPASTI,Bruschetta,800,Pane tostato con pomodoro fresco,true,true,
PIZZE,Margherita,900,Pomodoro e mozzarella,true,false,`

    const blob = new Blob([csv], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'menu_template.csv'
    a.click()
  }

  const handleClose = () => {
    setFile(null)
    setResults(null)
    onClose()
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onClick={handleClose}
      />

      {/* Modal */}
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        exit={{ opacity: 0, scale: 0.95 }}
        className="glass-card w-full max-w-2xl relative z-10"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 className="text-2xl font-bold text-gradient">Import Menu from CSV</h2>
          <button
            onClick={handleClose}
            className="text-gray-500 hover:text-white transition-colors"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 space-y-6">
          {/* Instructions */}
          <div className="bg-primary-500/10 border border-primary-500/30 rounded-lg p-4">
            <div className="flex gap-3">
              <AlertCircle className="w-5 h-5 text-primary-400 flex-shrink-0 mt-0.5" />
              <div className="text-sm text-gray-600 space-y-2">
                <p className="font-semibold text-primary-400">CSV Format Requirements:</p>
                <ul className="list-disc list-inside space-y-1 text-gray-500">
                  <li>
                    <strong>category</strong>: Category name (e.g., "BIRRE ARTIGIANALI")
                  </li>
                  <li>
                    <strong>product_name</strong>: Product name (required)
                  </li>
                  <li>
                    <strong>price_cents</strong>: Price in cents (e.g., 1050 for €10.50)
                  </li>
                  <li>
                    <strong>description</strong>: Product description (optional)
                  </li>
                  <li>
                    <strong>available_for_delivery</strong>: true/false
                  </li>
                  <li>
                    <strong>available_for_pickup</strong>: true/false
                  </li>
                  <li>
                    <strong>image_url</strong>: Image URL (optional)
                  </li>
                </ul>
              </div>
            </div>
          </div>

          {/* Download Template */}
          <button
            onClick={downloadTemplate}
            className="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
          >
            <Download className="w-5 h-5" />
            Download CSV Template
          </button>

          {/* File Upload */}
          <div>
            <label className="block text-sm font-medium text-gray-600 mb-2">
              Select CSV File
            </label>
            <div className="relative">
              <input
                type="file"
                accept=".csv,.txt"
                onChange={handleFileChange}
                className="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-500 file:text-white hover:file:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
            {file && (
              <p className="text-gray-500 text-sm mt-2 flex items-center gap-2">
                <FileText className="w-4 h-4" />
                {file.name} ({(file.size / 1024).toFixed(2)} KB)
              </p>
            )}
          </div>

          {/* Results */}
          {results && (
            <div className="space-y-3">
              <div className="grid grid-cols-2 gap-3">
                <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-3">
                  <div className="flex items-center gap-2 text-green-400">
                    <CheckCircle className="w-5 h-5" />
                    <span className="font-semibold">Created</span>
                  </div>
                  <p className="text-2xl font-bold text-white mt-1">{results.created}</p>
                </div>

                <div className="bg-primary-500/10 border border-primary-500/30 rounded-lg p-3">
                  <div className="flex items-center gap-2 text-primary-400">
                    <CheckCircle className="w-5 h-5" />
                    <span className="font-semibold">Updated</span>
                  </div>
                  <p className="text-2xl font-bold text-white mt-1">{results.updated}</p>
                </div>

                {results.skipped > 0 && (
                  <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3">
                    <div className="flex items-center gap-2 text-yellow-400">
                      <AlertCircle className="w-5 h-5" />
                      <span className="font-semibold">Skipped</span>
                    </div>
                    <p className="text-2xl font-bold text-white mt-1">{results.skipped}</p>
                  </div>
                )}

                {results.errors && results.errors.length > 0 && (
                  <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                    <div className="flex items-center gap-2 text-red-400">
                      <AlertCircle className="w-5 h-5" />
                      <span className="font-semibold">Errors</span>
                    </div>
                    <p className="text-2xl font-bold text-white mt-1">
                      {results.errors.length}
                    </p>
                  </div>
                )}
              </div>

              {results.errors && results.errors.length > 0 && (
                <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-4 max-h-40 overflow-y-auto">
                  <p className="text-red-400 font-semibold mb-2">Errors:</p>
                  <ul className="space-y-1 text-sm text-gray-500">
                    {results.errors.map((error: any, index: number) => (
                      <li key={index}>
                        Row {error.row}: {error.error}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}

          {/* Actions */}
          <div className="flex justify-end gap-3 pt-4">
            <button
              onClick={handleClose}
              className="px-6 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
            >
              Close
            </button>
            <button
              onClick={handleImport}
              disabled={!file || importing || !restaurantId}
              className="glass-button-primary disabled:opacity-50"
            >
              <Upload className="w-5 h-5" />
              {importing ? 'Importing...' : 'Import'}
            </button>
          </div>
        </div>
      </motion.div>
    </div>
  )
}
