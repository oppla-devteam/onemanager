import { motion, AnimatePresence } from 'framer-motion'
import { AlertTriangle, X, Database, Info } from 'lucide-react'

interface OpplaWritePreview {
  operation_type: string
  table: string
  description: string
  summary: string
  data?: Record<string, any>
  changes?: Record<string, any>
  conditions?: Record<string, any>
}

interface OpplaConfirmationModalProps {
  isOpen: boolean
  onClose: () => void
  onConfirm: () => void
  preview: OpplaWritePreview | null
  isExecuting?: boolean
}

export default function OpplaConfirmationModal({
  isOpen,
  onClose,
  onConfirm,
  preview,
  isExecuting = false
}: OpplaConfirmationModalProps) {
  if (!preview) return null

  const getOperationColor = (operation: string) => {
    switch (operation) {
      case 'INSERT':
        return 'text-green-400 border-green-500/30 bg-green-500/10'
      case 'UPDATE':
        return 'text-yellow-400 border-yellow-500/30 bg-yellow-500/10'
      case 'DELETE':
        return 'text-red-400 border-red-500/30 bg-red-500/10'
      default:
        return 'text-primary-400 border-primary-500/30 bg-primary-500/10'
    }
  }

  const getOperationIcon = (operation: string) => {
    const baseClass = "w-6 h-6"
    switch (operation) {
      case 'DELETE':
        return <AlertTriangle className={baseClass} />
      default:
        return <Database className={baseClass} />
    }
  }

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100]"
            onClick={onClose}
          />

          {/* Modal */}
          <div className="fixed inset-0 z-[101] flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="glass-card max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Header */}
              <div className="flex items-center justify-between p-6 border-b border-gray-200">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-lg bg-red-500/20 border border-red-500/30">
                    <AlertTriangle className="w-6 h-6 text-red-400" />
                  </div>
                  <div>
                    <h2 className="text-xl font-bold text-white">
                      Oppla Database Write Confirmation
                    </h2>
                    <p className="text-sm text-gray-500 mt-1">
                      This operation will modify Oppla's production database
                    </p>
                  </div>
                </div>
                <button
                  onClick={onClose}
                  className="text-gray-500 hover:text-white transition-colors"
                  disabled={isExecuting}
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              {/* Content */}
              <div className="flex-1 overflow-y-auto p-6 space-y-4">
                {/* Operation Badge */}
                <div
                  className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg border ${getOperationColor(
                    preview.operation_type
                  )}`}
                >
                  {getOperationIcon(preview.operation_type)}
                  <span className="font-bold">{preview.operation_type}</span>
                  <span className="text-gray-600">on table: {preview.table}</span>
                </div>

                {/* Description */}
                <div className="glass-card p-4 border border-gray-200">
                  <div className="flex items-start gap-2">
                    <Info className="w-5 h-5 text-primary-400 mt-0.5 flex-shrink-0" />
                    <div>
                      <p className="text-sm font-semibold text-gray-700">
                        {preview.description}
                      </p>
                      <p className="text-sm text-gray-500 mt-1">{preview.summary}</p>
                    </div>
                  </div>
                </div>

                {/* Data Preview */}
                {preview.data && (
                  <div className="space-y-2">
                    <h3 className="text-sm font-semibold text-gray-600">
                      Data to Insert:
                    </h3>
                    <div className="glass-card p-4 border border-gray-200">
                      <pre className="text-xs text-gray-600 overflow-x-auto">
                        {JSON.stringify(preview.data, null, 2)}
                      </pre>
                    </div>
                  </div>
                )}

                {preview.changes && (
                  <div className="space-y-2">
                    <h3 className="text-sm font-semibold text-gray-600">
                      Changes to Apply:
                    </h3>
                    <div className="glass-card p-4 border border-gray-200">
                      <pre className="text-xs text-gray-600 overflow-x-auto">
                        {JSON.stringify(preview.changes, null, 2)}
                      </pre>
                    </div>
                  </div>
                )}

                {preview.conditions && (
                  <div className="space-y-2">
                    <h3 className="text-sm font-semibold text-gray-600">
                      Conditions (WHERE clause):
                    </h3>
                    <div className="glass-card p-4 border border-gray-200">
                      <pre className="text-xs text-gray-600 overflow-x-auto">
                        {JSON.stringify(preview.conditions, null, 2)}
                      </pre>
                    </div>
                  </div>
                )}

                {/* Warning */}
                <div className="glass-card p-4 border border-red-500/30 bg-red-500/10">
                  <div className="flex items-start gap-2">
                    <AlertTriangle className="w-5 h-5 text-red-400 mt-0.5 flex-shrink-0" />
                    <div className="text-sm text-red-200">
                      <p className="font-semibold">Warning: Production Database</p>
                      <p className="mt-1 text-red-300">
                        This operation will directly modify Oppla's production database.
                        Make sure you understand the impact before proceeding.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Footer */}
              <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-900/50">
                <button
                  onClick={onClose}
                  className="px-6 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-white font-medium transition-colors"
                  disabled={isExecuting}
                >
                  Cancel
                </button>
                <button
                  onClick={onConfirm}
                  disabled={isExecuting}
                  className={`px-6 py-2.5 rounded-lg font-medium transition-all ${
                    preview.operation_type === 'DELETE'
                      ? 'bg-red-600 hover:bg-red-700 text-white'
                      : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white'
                  } ${isExecuting ? 'opacity-50 cursor-not-allowed' : ''}`}
                >
                  {isExecuting ? (
                    <span className="flex items-center gap-2">
                      <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle
                          className="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          strokeWidth="4"
                          fill="none"
                        />
                        <path
                          className="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                      </svg>
                      Executing...
                    </span>
                  ) : (
                    'Confirm & Execute'
                  )}
                </button>
              </div>
            </motion.div>
          </div>
        </>
      )}
    </AnimatePresence>
  )
}
