import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '../../../test/helpers'
import RiderFilters from '../../../components/riders/RiderFilters'
import { Team } from '../../../components/riders/types'

const mockTeams: Team[] = [
  { team_id: 1, team_name: 'Team Alpha' },
  { team_id: 2, team_name: 'Team Beta' },
]

const defaultProps = {
  searchTerm: '',
  onSearchChange: vi.fn(),
  statusFilter: 'all',
  onStatusFilterChange: vi.fn(),
  teamFilter: 'all',
  onTeamFilterChange: vi.fn(),
  teams: mockTeams,
}

describe('RiderFilters', () => {
  it('renders without crashing', () => {
    renderWithProviders(<RiderFilters {...defaultProps} />)
    expect(screen.getByPlaceholderText(/cerca per nome/i)).toBeInTheDocument()
  })

  it('renders search input with placeholder', () => {
    renderWithProviders(<RiderFilters {...defaultProps} />)
    const searchInput = screen.getByPlaceholderText(/cerca per nome, telefono o email/i)
    expect(searchInput).toBeInTheDocument()
  })

  it('renders status filter dropdown', () => {
    renderWithProviders(<RiderFilters {...defaultProps} />)
    expect(screen.getByText('Tutti gli stati')).toBeInTheDocument()
  })

  it('shows status options in the dropdown', () => {
    renderWithProviders(<RiderFilters {...defaultProps} />)
    expect(screen.getByText('Disponibili')).toBeInTheDocument()
    expect(screen.getByText('In consegna')).toBeInTheDocument()
    expect(screen.getByText('Offline')).toBeInTheDocument()
  })

  it('renders team filter dropdown', () => {
    renderWithProviders(<RiderFilters {...defaultProps} />)
    expect(screen.getByText('Tutti i team')).toBeInTheDocument()
  })

  it('shows team names in the dropdown', () => {
    renderWithProviders(<RiderFilters {...defaultProps} />)
    expect(screen.getByText('Team Alpha')).toBeInTheDocument()
    expect(screen.getByText('Team Beta')).toBeInTheDocument()
  })

  it('shows "Senza team" option in team dropdown', () => {
    renderWithProviders(<RiderFilters {...defaultProps} />)
    expect(screen.getByText('Senza team')).toBeInTheDocument()
  })

  it('calls onSearchChange when typing in search', async () => {
    const user = userEvent.setup()
    const onSearchChange = vi.fn()
    renderWithProviders(<RiderFilters {...defaultProps} onSearchChange={onSearchChange} />)

    const searchInput = screen.getByPlaceholderText(/cerca per nome/i)
    await user.type(searchInput, 'Mario')

    expect(onSearchChange).toHaveBeenCalled()
  })

  it('calls onStatusFilterChange when selecting status', async () => {
    const user = userEvent.setup()
    const onStatusFilterChange = vi.fn()
    renderWithProviders(<RiderFilters {...defaultProps} onStatusFilterChange={onStatusFilterChange} />)

    const statusSelect = screen.getByDisplayValue('Tutti gli stati')
    await user.selectOptions(statusSelect, 'available')

    expect(onStatusFilterChange).toHaveBeenCalledWith('available')
  })

  it('calls onTeamFilterChange when selecting team', async () => {
    const user = userEvent.setup()
    const onTeamFilterChange = vi.fn()
    renderWithProviders(<RiderFilters {...defaultProps} onTeamFilterChange={onTeamFilterChange} />)

    const teamSelect = screen.getByDisplayValue('Tutti i team')
    await user.selectOptions(teamSelect, '1')

    expect(onTeamFilterChange).toHaveBeenCalledWith('1')
  })

  it('displays current search term value', () => {
    renderWithProviders(<RiderFilters {...defaultProps} searchTerm="test search" />)
    const searchInput = screen.getByPlaceholderText(/cerca per nome/i)
    expect(searchInput).toHaveValue('test search')
  })
})
