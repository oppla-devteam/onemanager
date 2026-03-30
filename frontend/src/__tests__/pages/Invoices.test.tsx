import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Invoices from '../../pages/Invoices'

// Mock api utils
vi.mock('../../utils/api', () => {
  const mockApi = {
    get: vi.fn(() => Promise.resolve({ data: { data: [] } })),
    post: vi.fn(() => Promise.resolve({ data: {} })),
    put: vi.fn(() => Promise.resolve({ data: {} })),
    delete: vi.fn(() => Promise.resolve({ data: {} })),
    create: vi.fn(),
    defaults: { headers: { common: {} } },
    interceptors: {
      request: { use: vi.fn(), eject: vi.fn() },
      response: { use: vi.fn(), eject: vi.fn() },
    },
  }
  return {
    default: mockApi,
    api: mockApi,
    invoicesApi: {
      getAll: vi.fn(() => Promise.resolve({ data: { data: [] } })),
      create: vi.fn(() => Promise.resolve({ data: {} })),
      update: vi.fn(() => Promise.resolve({ data: {} })),
      delete: vi.fn(() => Promise.resolve({ data: {} })),
    },
    clientsApi: {
      getAll: vi.fn(() => Promise.resolve({ data: { data: [] } })),
    },
  }
})

// Mock Modal component
vi.mock('../../components/Modal', () => ({
  default: ({ children, isOpen }: any) => isOpen ? <div data-testid="modal">{children}</div> : null,
}))

describe('Invoices', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders without crashing', async () => {
    renderWithProviders(<Invoices />)
    await waitFor(() => {
      expect(screen.getByPlaceholderText(/cerca/i)).toBeInTheDocument()
    })
  })

  it('shows the search input field', async () => {
    renderWithProviders(<Invoices />)
    await waitFor(() => {
      const searchInput = screen.getByPlaceholderText(/cerca/i)
      expect(searchInput).toBeInTheDocument()
    })
  })
})
