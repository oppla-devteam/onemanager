import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Contracts from '../../pages/Contracts'

// Mock global fetch for API calls (Contracts uses fetch directly)
beforeEach(() => {
  vi.clearAllMocks()
  global.fetch = vi.fn((url: string | URL | Request) => {
    const urlStr = typeof url === 'string' ? url : url.toString()
    // /contracts/clients returns a plain array, other endpoints return { data: [] }
    const body = urlStr.includes('/clients') ? [] : { data: [] }
    return Promise.resolve({
      ok: true,
      json: () => Promise.resolve(body),
    } as Response)
  }) as any
})

describe('Contracts', () => {
  it('renders without crashing', async () => {
    renderWithProviders(<Contracts />)
    await waitFor(() => {
      expect(screen.getByPlaceholderText(/cerca/i)).toBeInTheDocument()
    })
  })

  it('shows search input field', async () => {
    renderWithProviders(<Contracts />)
    await waitFor(() => {
      const searchInput = screen.getByPlaceholderText(/cerca/i)
      expect(searchInput).toBeInTheDocument()
    })
  })
})
