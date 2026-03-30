import { useState } from 'react'
import { api } from '../utils/api'

interface OpplaWritePreview {
  confirmation_required: boolean
  token: string
  operation: string
  table: string
  preview: {
    operation_type: string
    table: string
    description: string
    summary: string
    data?: Record<string, any>
    changes?: Record<string, any>
    conditions?: Record<string, any>
  }
  expires_at: string
}

interface OpplaWriteRequest {
  operation: 'INSERT' | 'UPDATE' | 'DELETE'
  table: string
  data: Record<string, any>
  conditions?: Record<string, any>
}

export function useOpplaWrite() {
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [preview, setPreview] = useState<OpplaWritePreview | null>(null)
  const [isExecuting, setIsExecuting] = useState(false)

  /**
   * Request confirmation for a write operation
   * This shows the confirmation modal to the user
   */
  const requestWrite = async (request: OpplaWriteRequest): Promise<void> => {
    try {
      const response = await api.post('/oppla/write/request-confirmation', request)
      const data = response.data

      if (data.confirmation_required) {
        setPreview(data)
        setIsModalOpen(true)
      }
    } catch (error: any) {
      console.error('Failed to request write confirmation:', error)
      throw new Error(
        error.response?.data?.message || 'Failed to prepare write operation'
      )
    }
  }

  /**
   * Execute the write operation after user confirms
   */
  const executeWrite = async (): Promise<any> => {
    if (!preview?.token) {
      throw new Error('No confirmation token available')
    }

    setIsExecuting(true)

    try {
      const response = await api.post('/oppla/write/execute', {
        token: preview.token
      })

      setIsModalOpen(false)
      setPreview(null)

      return response.data
    } catch (error: any) {
      console.error('Failed to execute write:', error)
      throw new Error(
        error.response?.data?.message || 'Failed to execute write operation'
      )
    } finally {
      setIsExecuting(false)
    }
  }

  /**
   * Cancel the write operation
   */
  const cancelWrite = () => {
    setIsModalOpen(false)
    setPreview(null)
    setIsExecuting(false)
  }

  return {
    requestWrite,
    executeWrite,
    cancelWrite,
    isModalOpen,
    preview: preview?.preview || null,
    isExecuting
  }
}
