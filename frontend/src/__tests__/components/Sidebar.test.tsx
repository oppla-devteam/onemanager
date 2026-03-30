import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import { renderWithProviders } from '../../test/helpers'
import Sidebar from '../../components/Sidebar'

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

describe('Sidebar', () => {
  it('renders without crashing', () => {
    renderWithProviders(<Sidebar />)
    expect(screen.getByText('Oppla One')).toBeInTheDocument()
  })

  it('shows the app name when not collapsed', () => {
    renderWithProviders(<Sidebar isCollapsed={false} />)
    expect(screen.getByText('Oppla One')).toBeInTheDocument()
    expect(screen.getByText('Manager')).toBeInTheDocument()
  })

  it('renders navigation links', () => {
    renderWithProviders(<Sidebar />)
    expect(screen.getByText('Dashboard')).toBeInTheDocument()
    expect(screen.getByText('Clienti')).toBeInTheDocument()
    expect(screen.getByText('Ordini')).toBeInTheDocument()
    expect(screen.getByText('Riders')).toBeInTheDocument()
    expect(screen.getByText('Fatture')).toBeInTheDocument()
    expect(screen.getByText('Pagamenti')).toBeInTheDocument()
    expect(screen.getByText('Contratti')).toBeInTheDocument()
  })

  it('renders settings link', () => {
    renderWithProviders(<Sidebar />)
    expect(screen.getByText('Impostazioni')).toBeInTheDocument()
  })

  it('renders logout button', () => {
    renderWithProviders(<Sidebar />)
    expect(screen.getByText('Logout')).toBeInTheDocument()
  })

  it('renders partner protection link', () => {
    renderWithProviders(<Sidebar />)
    expect(screen.getByText('Protezione Partner')).toBeInTheDocument()
  })

  it('renders delivery zones link', () => {
    renderWithProviders(<Sidebar />)
    expect(screen.getByText('Zone Consegna')).toBeInTheDocument()
  })

  it('hides labels when collapsed', () => {
    renderWithProviders(<Sidebar isCollapsed={true} />)
    expect(screen.queryByText('Dashboard')).not.toBeInTheDocument()
    expect(screen.queryByText('Oppla One')).not.toBeInTheDocument()
  })
})
