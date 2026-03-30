import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '../../test/helpers'
import Settings from '../../pages/Settings'

// Mock AuthContext
vi.mock('../../contexts/AuthContext', () => ({
  useAuth: () => ({
    login: vi.fn(),
    isAuthenticated: true,
    user: {
      username: 'testuser',
      name: 'Test User',
      email: 'test@test.com',
      role: 'admin',
      roles: ['admin'],
      permissions: [],
    },
    logout: vi.fn(),
    hasPermission: vi.fn(() => true),
  }),
}))

describe('Settings', () => {
  it('renders without crashing', () => {
    renderWithProviders(<Settings />)
    expect(screen.getByText('Impostazioni')).toBeInTheDocument()
  })

  it('shows settings description', () => {
    renderWithProviders(<Settings />)
    expect(screen.getByText(/configura il tuo account/i)).toBeInTheDocument()
  })

  it('renders tab navigation', () => {
    renderWithProviders(<Settings />)
    expect(screen.getByText('Profilo')).toBeInTheDocument()
    expect(screen.getByText('Aspetto')).toBeInTheDocument()
    expect(screen.getByText('Integrazioni')).toBeInTheDocument()
  })

  it('shows profile tab by default', () => {
    renderWithProviders(<Settings />)
    expect(screen.getByText('Informazioni Profilo')).toBeInTheDocument()
  })

  it('can switch to appearance tab', async () => {
    const user = userEvent.setup()
    renderWithProviders(<Settings />)

    await user.click(screen.getByText('Aspetto'))
    expect(screen.getByText(/tema/i)).toBeInTheDocument()
  })

  it('displays user initials', () => {
    renderWithProviders(<Settings />)
    expect(screen.getByText('TU')).toBeInTheDocument()
  })
})
