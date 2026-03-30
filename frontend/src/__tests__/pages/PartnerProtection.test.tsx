import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import PartnerProtection from '../../pages/PartnerProtection'

// Mock the api utility
vi.mock('../../utils/api', () => {
  const mockApi = {
    get: vi.fn((url: string) => {
      if (url.includes('stats')) {
        return Promise.resolve({
          data: { total: 0, pending: 0, by_type: {}, by_status: {} },
        })
      }
      if (url.includes('incidents')) {
        return Promise.resolve({ data: { data: [] } })
      }
      if (url.includes('penalties')) {
        return Promise.resolve({ data: { data: [] } })
      }
      if (url.includes('settings')) {
        return Promise.resolve({ data: {} })
      }
      return Promise.resolve({ data: {} })
    }),
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
  return { default: mockApi }
})

// Mock @headlessui/react with compound component pattern
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
  const Tab = Object.assign(
    ({ children }: any) => <div>{children}</div>,
    {
      Group: ({ children }: any) => <div>{children}</div>,
      List: ({ children }: any) => <div role="tablist">{children}</div>,
      Panels: ({ children }: any) => <div>{children}</div>,
      Panel: ({ children }: any) => <div role="tabpanel">{children}</div>,
    }
  )
  return { Dialog, Transition, Tab, Fragment: ({ children }: any) => <>{children}</> }
})

describe('PartnerProtection', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders without crashing', async () => {
    renderWithProviders(<PartnerProtection />)
    await waitFor(() => {
      expect(screen.getByText(/protezione partner/i)).toBeInTheDocument()
    })
  })
})
