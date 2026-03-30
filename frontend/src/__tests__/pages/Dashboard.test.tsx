import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Dashboard from '../../pages/Dashboard'

// Mock chart.js and react-chartjs-2
vi.mock('chart.js', () => ({
  Chart: {
    register: vi.fn(),
  },
  CategoryScale: vi.fn(),
  LinearScale: vi.fn(),
  PointElement: vi.fn(),
  LineElement: vi.fn(),
  BarElement: vi.fn(),
  ArcElement: vi.fn(),
  Title: vi.fn(),
  Tooltip: vi.fn(),
  Legend: vi.fn(),
  Filler: vi.fn(),
}))

vi.mock('react-chartjs-2', () => ({
  Line: () => <div data-testid="line-chart">Line Chart</div>,
  Bar: () => <div data-testid="bar-chart">Bar Chart</div>,
  Doughnut: () => <div data-testid="doughnut-chart">Doughnut Chart</div>,
}))

// Mock the api utility
vi.mock('../../utils/api', () => {
  const mockApi = {
    get: vi.fn(() => Promise.resolve({ data: null })),
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
  }
})

describe('Dashboard', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders loading state initially', () => {
    renderWithProviders(<Dashboard />)
    expect(screen.getByText(/caricamento/i)).toBeInTheDocument()
  })

  it('renders dashboard title after loading', async () => {
    const { default: mockApi } = await import('../../utils/api')
    ;(mockApi.get as ReturnType<typeof vi.fn>).mockImplementation((url: string) => {
      if (url.includes('delivery-ops')) {
        return Promise.resolve({
          data: {
            today: { total: 0, completed: 0, in_progress: 0, pending: 0, cancelled: 0, completion_rate: 0 },
            revenue: { delivery_fees: 0, order_amounts: 0, oppla_fees: 0, distance_fees: 0, total_km: 0 },
            weekly_trend: [],
            top_restaurants: [],
            hourly_distribution: [],
            avg_times: { pickup: 0, delivery: 0, total: 0 },
            comparison: { deliveries_diff: 0, deliveries_diff_percent: 0, revenue_diff: 0, revenue_diff_percent: 0 },
            riders: { total: 0, available: 0, busy: 0, offline: 0, agents: [] },
            tookan_tasks: { total: 0, assigned: 0, started: 0, successful: 0, failed: 0, in_progress: 0, unassigned: 0 },
          },
        })
      }
      if (url.includes('unified')) {
        return Promise.resolve({ data: null })
      }
      return Promise.resolve({ data: {} })
    })

    renderWithProviders(<Dashboard />)

    await waitFor(() => {
      expect(screen.getByText('Dashboard OPPLA')).toBeInTheDocument()
    })
  })
})
