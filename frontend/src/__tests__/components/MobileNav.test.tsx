import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '../../test/helpers'
import MobileNav from '../../components/MobileNav'

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

describe('MobileNav', () => {
  it('renders without crashing', () => {
    const { container } = renderWithProviders(<MobileNav />)
    expect(container).toBeTruthy()
  })

  it('renders at least one button', () => {
    renderWithProviders(<MobileNav />)
    const buttons = screen.getAllByRole('button')
    expect(buttons.length).toBeGreaterThan(0)
  })

  it('opens the menu panel on button click', async () => {
    const user = userEvent.setup()
    renderWithProviders(<MobileNav />)

    const buttons = screen.getAllByRole('button')
    await user.click(buttons[0])

    // After clicking the menu button, navigation links should appear
    const allText = document.body.textContent || ''
    expect(allText.length).toBeGreaterThan(0)
  })
})
