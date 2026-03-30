import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Payments from '../../pages/Payments'

// Mock NotificationBell service
vi.mock('../../components/NotificationBell', () => ({
  default: () => <div data-testid="notification-bell">NotificationBell</div>,
  notificationService: {
    add: vi.fn(),
    remove: vi.fn(),
    clear: vi.fn(),
    markAllRead: vi.fn(),
    subscribe: vi.fn(() => () => {}),
  },
}))

// Mock global fetch for API calls (Payments uses fetch directly, not axios)
const mockFetchResponse = (data: any) =>
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve(data),
  } as Response)

beforeEach(() => {
  vi.clearAllMocks()
  global.fetch = vi.fn((url: string | URL | Request) => {
    const urlStr = typeof url === 'string' ? url : url.toString()
    if (urlStr.includes('stripe/status')) {
      return mockFetchResponse({ configured: false, connected: false, message: 'Not configured' })
    }
    if (urlStr.includes('payments-stats')) {
      return mockFetchResponse({
        success: true,
        data: {
          income: 0,
          expenses: 0,
          pending: 0,
          failed: 0,
          income_change: 0,
          last_sync: null,
        },
      })
    }
    if (urlStr.includes('payments')) {
      return mockFetchResponse({ success: true, data: [] })
    }
    return mockFetchResponse({})
  }) as any
})

describe('Payments', () => {
  it('renders without crashing', async () => {
    renderWithProviders(<Payments />)
    await waitFor(() => {
      expect(screen.getByText('Pagamenti')).toBeInTheDocument()
    })
  })

  it('shows the search input', async () => {
    renderWithProviders(<Payments />)
    await waitFor(() => {
      expect(screen.getByPlaceholderText(/cerca/i)).toBeInTheDocument()
    })
  })
})
