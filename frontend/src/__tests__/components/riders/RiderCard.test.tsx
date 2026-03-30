import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '../../../test/helpers'
import RiderCard from '../../../components/riders/RiderCard'
import { Rider } from '../../../components/riders/types'

const createMockRider = (overrides: Partial<Rider> = {}): Rider => ({
  id: '1',
  fleet_id: 'fleet-1',
  username: 'rider1',
  first_name: 'Mario',
  last_name: 'Rossi',
  name: 'Mario Rossi',
  email: 'mario@test.com',
  phone: '+39 333 1234567',
  status: 'available',
  status_code: 0,
  is_blocked: false,
  transport_type: 'bike',
  transport_type_code: 0,
  latitude: 41.9028,
  longitude: 12.4964,
  last_updated: '2026-02-17T10:00:00Z',
  team_id: 1,
  team_name: 'Team A',
  tags: '',
  profile_image: null,
  ...overrides,
})

const mockHandlers = {
  onEdit: vi.fn(),
  onToggleBlock: vi.fn(),
  onDelete: vi.fn(),
  onViewTasks: vi.fn(),
  onNotify: vi.fn(),
  onAssignTeam: vi.fn(),
}

describe('RiderCard', () => {
  it('renders rider name', () => {
    const rider = createMockRider()
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('Mario Rossi')).toBeInTheDocument()
  })

  it('renders rider phone number', () => {
    const rider = createMockRider()
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('+39 333 1234567')).toBeInTheDocument()
  })

  it('renders rider email', () => {
    const rider = createMockRider()
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('mario@test.com')).toBeInTheDocument()
  })

  it('renders team name when assigned', () => {
    const rider = createMockRider({ team_name: 'Team A' })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('Team A')).toBeInTheDocument()
  })

  it('renders assign to team button when no team', () => {
    const rider = createMockRider({ team_id: null, team_name: null })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('Assegna a team')).toBeInTheDocument()
  })

  it('renders status label for available rider', () => {
    const rider = createMockRider({ status: 'available' })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('Disponibile')).toBeInTheDocument()
  })

  it('renders status label for busy rider', () => {
    const rider = createMockRider({ status: 'busy' })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('In consegna')).toBeInTheDocument()
  })

  it('renders status label for offline rider', () => {
    const rider = createMockRider({ status: 'offline' })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('Offline')).toBeInTheDocument()
  })

  it('shows location link when coordinates exist', () => {
    const rider = createMockRider({ latitude: 41.9028, longitude: 12.4964 })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('Vedi posizione')).toBeInTheDocument()
  })

  it('does not show location link when no coordinates', () => {
    const rider = createMockRider({ latitude: null, longitude: null })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.queryByText('Vedi posizione')).not.toBeInTheDocument()
  })

  it('calls onEdit when edit button is clicked', async () => {
    const user = userEvent.setup()
    const rider = createMockRider()
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)

    const editButton = screen.getByTitle('Modifica')
    await user.click(editButton)
    expect(mockHandlers.onEdit).toHaveBeenCalledWith(rider)
  })

  it('calls onDelete when delete button is clicked', async () => {
    const user = userEvent.setup()
    const rider = createMockRider()
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)

    const deleteButton = screen.getByTitle('Elimina')
    await user.click(deleteButton)
    expect(mockHandlers.onDelete).toHaveBeenCalledWith(rider)
  })

  it('calls onViewTasks when tasks button is clicked', async () => {
    const user = userEvent.setup()
    const rider = createMockRider()
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)

    const tasksButton = screen.getByTitle('Vedi consegne')
    await user.click(tasksButton)
    expect(mockHandlers.onViewTasks).toHaveBeenCalledWith(rider)
  })

  it('calls onNotify when notify button is clicked', async () => {
    const user = userEvent.setup()
    const rider = createMockRider()
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)

    const notifyButton = screen.getByTitle('Invia notifica')
    await user.click(notifyButton)
    expect(mockHandlers.onNotify).toHaveBeenCalledWith(rider)
  })

  it('shows block button for unblocked rider', () => {
    const rider = createMockRider({ is_blocked: false })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByTitle('Blocca')).toBeInTheDocument()
  })

  it('shows unblock button for blocked rider', () => {
    const rider = createMockRider({ is_blocked: true })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByTitle('Sblocca')).toBeInTheDocument()
  })

  it('calls onToggleBlock when block button is clicked', async () => {
    const user = userEvent.setup()
    const rider = createMockRider({ is_blocked: false })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)

    const blockButton = screen.getByTitle('Blocca')
    await user.click(blockButton)
    expect(mockHandlers.onToggleBlock).toHaveBeenCalledWith(rider)
  })

  it('uses username when name is empty', () => {
    const rider = createMockRider({ name: '', username: 'rider_user' })
    renderWithProviders(<RiderCard rider={rider} index={0} {...mockHandlers} />)
    expect(screen.getByText('rider_user')).toBeInTheDocument()
  })
})
