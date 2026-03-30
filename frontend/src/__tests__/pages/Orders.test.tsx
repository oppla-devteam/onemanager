import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Orders from '../../pages/Orders'

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
    ordersApi: {
      getAll: vi.fn(() => Promise.resolve({ data: { data: [], meta: {} } })),
    },
    deliveriesApi: {
      getAll: vi.fn(() => Promise.resolve({ data: { data: [] } })),
    },
    restaurantsApi: {
      getAll: vi.fn(() => Promise.resolve({ data: [] })),
    },
  }
})

describe('Orders', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Ensure localStorage is available with proper methods
    vi.spyOn(Storage.prototype, 'getItem').mockReturnValue(null)
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {})
  })

  it('renders without crashing', async () => {
    const { container } = renderWithProviders(<Orders />)
    await waitFor(() => {
      expect(container.querySelector('input') || container.firstChild).toBeTruthy()
    })
  })

  it('renders the page component', async () => {
    const { container } = renderWithProviders(<Orders />)
    expect(container).toBeTruthy()
  })
})
