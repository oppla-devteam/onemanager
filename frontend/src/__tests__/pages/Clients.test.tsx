import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Clients from '../../pages/Clients'

// Mock axios
vi.mock('axios', () => {
  const mockAxios: any = {
    get: vi.fn(() => Promise.resolve({ data: { data: [] } })),
    post: vi.fn(() => Promise.resolve({ data: {} })),
    put: vi.fn(() => Promise.resolve({ data: {} })),
    delete: vi.fn(() => Promise.resolve({ data: {} })),
    create: vi.fn(function () { return mockAxios }),
    defaults: { headers: { common: {} } },
    interceptors: {
      request: { use: vi.fn(), eject: vi.fn() },
      response: { use: vi.fn(), eject: vi.fn() },
    },
  }
  return { default: mockAxios }
})

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
    clientsApi: {
      getAll: vi.fn(() => Promise.resolve({ data: { data: [] } })),
      create: vi.fn(() => Promise.resolve({ data: {} })),
      update: vi.fn(() => Promise.resolve({ data: {} })),
      delete: vi.fn(() => Promise.resolve({ data: {} })),
    },
    partnersApi: {
      getAll: vi.fn(() => Promise.resolve({ data: { data: [] } })),
      create: vi.fn(() => Promise.resolve({ data: {} })),
      update: vi.fn(() => Promise.resolve({ data: {} })),
      delete: vi.fn(() => Promise.resolve({ data: {} })),
      assignClient: vi.fn(() => Promise.resolve({ data: {} })),
      unassignClient: vi.fn(() => Promise.resolve({ data: {} })),
    },
  }
})

// Mock csv import utility
vi.mock('../../utils/csvImport', () => ({
  Client: {},
}))

// Mock sub-components that might be complex
vi.mock('../../components/OnboardingModalNew', () => ({
  default: () => <div data-testid="onboarding-modal">OnboardingModal</div>,
}))

vi.mock('../../components/RestaurantClosureModal', () => ({
  default: () => <div data-testid="closure-modal">ClosureModal</div>,
}))

vi.mock('../../components/Modal', () => ({
  default: ({ children, isOpen }: any) => isOpen ? <div data-testid="modal">{children}</div> : null,
}))

describe('Clients', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders without crashing', async () => {
    renderWithProviders(<Clients />)
    await waitFor(() => {
      expect(screen.getByPlaceholderText(/cerca/i)).toBeInTheDocument()
    })
  })

  it('shows a search input', async () => {
    renderWithProviders(<Clients />)
    await waitFor(() => {
      const searchInput = screen.getByPlaceholderText(/cerca/i)
      expect(searchInput).toBeInTheDocument()
    })
  })
})
