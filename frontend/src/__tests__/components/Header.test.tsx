import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Header from '../../components/Header'

// Mock the NotificationBell component since it has complex internal state
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

describe('Header', () => {
  it('renders without crashing', () => {
    renderWithProviders(<Header />)
    expect(screen.getByRole('banner')).toBeInTheDocument()
  })

  it('renders search input', () => {
    renderWithProviders(<Header />)
    const searchInput = screen.getByPlaceholderText(/cerca clienti, fatture/i)
    expect(searchInput).toBeInTheDocument()
  })

  it('renders search input with correct type', () => {
    renderWithProviders(<Header />)
    const searchInput = screen.getByPlaceholderText(/cerca clienti, fatture/i)
    expect(searchInput).toHaveAttribute('type', 'search')
  })

  it('renders notification bell component', () => {
    renderWithProviders(<Header />)
    expect(screen.getByTestId('notification-bell')).toBeInTheDocument()
  })
})
