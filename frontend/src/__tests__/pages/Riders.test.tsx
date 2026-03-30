import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Riders from '../../pages/Riders'

// Mock the api utility
vi.mock('../../utils/api', () => {
  const mockApi = {
    get: vi.fn((url: string) => {
      if (url.includes('/riders/teams')) {
        return Promise.resolve({ data: { success: true, data: [] } })
      }
      if (url.includes('/riders')) {
        return Promise.resolve({
          data: {
            success: true,
            data: [],
            summary: { total: 0, available: 0, busy: 0, offline: 0 },
            teams: [],
            last_synced_at: null,
            is_stale: false,
          },
        })
      }
      return Promise.resolve({ data: {} })
    }),
    post: vi.fn(() => Promise.resolve({ data: { success: true } })),
    put: vi.fn(() => Promise.resolve({ data: {} })),
    delete: vi.fn(() => Promise.resolve({ data: {} })),
    create: vi.fn(),
    defaults: { headers: { common: {} } },
    interceptors: {
      request: { use: vi.fn(), eject: vi.fn() },
      response: { use: vi.fn(), eject: vi.fn() },
    },
  }
  return { default: mockApi }
})

// Mock @headlessui/react
vi.mock('@headlessui/react', () => {
  const Dialog = Object.assign(
    ({ children, open }: any) => open ? <div role="dialog">{children}</div> : null,
    {
      Panel: ({ children }: any) => <div>{children}</div>,
      Title: ({ children, className }: any) => <h2 className={className}>{children}</h2>,
    }
  )
  const Transition = Object.assign(
    ({ children, show }: any) => show !== false ? <>{children}</> : null,
    {
      Child: ({ children }: any) => <>{children}</>,
    }
  )
  return { Dialog, Transition, Fragment: ({ children }: any) => <>{children}</> }
})

// Mock the RiderMap component (uses mapbox-gl)
vi.mock('../../components/riders/RiderMap', () => ({
  default: () => <div data-testid="rider-map">RiderMap</div>,
}))

// Mock mapbox-gl
vi.mock('mapbox-gl', () => ({
  default: { Map: vi.fn(), Marker: vi.fn() },
  Map: vi.fn(),
}))

describe('Riders', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders without crashing', async () => {
    renderWithProviders(<Riders />)
    await waitFor(() => {
      expect(screen.getByText('Gestione Rider')).toBeInTheDocument()
    })
  })

  it('shows the rider header with title', async () => {
    renderWithProviders(<Riders />)
    await waitFor(() => {
      expect(screen.getByText('Gestione Rider')).toBeInTheDocument()
      expect(screen.getByText(/gestisci i tuoi rider/i)).toBeInTheDocument()
    })
  })

  it('renders new rider button', async () => {
    renderWithProviders(<Riders />)
    await waitFor(() => {
      expect(screen.getByText('Nuovo Rider')).toBeInTheDocument()
    })
  })

  it('renders sync button', async () => {
    renderWithProviders(<Riders />)
    await waitFor(() => {
      expect(screen.getByText('Sincronizza')).toBeInTheDocument()
    })
  })
})
