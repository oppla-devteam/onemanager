import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '../../../test/helpers'
import RiderHeader from '../../../components/riders/RiderHeader'

const defaultProps = {
  lastSyncedAt: null,
  isStale: false,
  showMap: false,
  syncing: false,
  refreshing: false,
  error: null,
  onToggleMap: vi.fn(),
  onManageTeams: vi.fn(),
  onNotifyAll: vi.fn(),
  onNewRider: vi.fn(),
  onSync: vi.fn(),
  exporting: false,
  onExportCSV: vi.fn(),
}

describe('RiderHeader', () => {
  it('renders without crashing', () => {
    renderWithProviders(<RiderHeader {...defaultProps} />)
    expect(screen.getByText('Gestione Rider')).toBeInTheDocument()
  })

  it('shows description text', () => {
    renderWithProviders(<RiderHeader {...defaultProps} />)
    expect(screen.getByText(/gestisci i tuoi rider e le consegne in tempo reale/i)).toBeInTheDocument()
  })

  it('renders "Mappa Live" button when map is hidden', () => {
    renderWithProviders(<RiderHeader {...defaultProps} showMap={false} />)
    expect(screen.getByText('Mappa Live')).toBeInTheDocument()
  })

  it('renders "Chiudi Mappa" button when map is shown', () => {
    renderWithProviders(<RiderHeader {...defaultProps} showMap={true} />)
    expect(screen.getByText('Chiudi Mappa')).toBeInTheDocument()
  })

  it('renders "Gestisci Team" button', () => {
    renderWithProviders(<RiderHeader {...defaultProps} />)
    expect(screen.getByText('Gestisci Team')).toBeInTheDocument()
  })

  it('renders "Notifica tutti" button', () => {
    renderWithProviders(<RiderHeader {...defaultProps} />)
    expect(screen.getByText('Notifica tutti')).toBeInTheDocument()
  })

  it('renders "Nuovo Rider" button', () => {
    renderWithProviders(<RiderHeader {...defaultProps} />)
    expect(screen.getByText('Nuovo Rider')).toBeInTheDocument()
  })

  it('renders "Sincronizza" button when not syncing', () => {
    renderWithProviders(<RiderHeader {...defaultProps} syncing={false} />)
    expect(screen.getByText('Sincronizza')).toBeInTheDocument()
  })

  it('renders "Sincronizzazione..." text when syncing', () => {
    renderWithProviders(<RiderHeader {...defaultProps} syncing={true} />)
    expect(screen.getByText('Sincronizzazione...')).toBeInTheDocument()
  })

  it('calls onToggleMap when map button is clicked', async () => {
    const user = userEvent.setup()
    const onToggleMap = vi.fn()
    renderWithProviders(<RiderHeader {...defaultProps} onToggleMap={onToggleMap} />)

    await user.click(screen.getByText('Mappa Live'))
    expect(onToggleMap).toHaveBeenCalled()
  })

  it('calls onManageTeams when teams button is clicked', async () => {
    const user = userEvent.setup()
    const onManageTeams = vi.fn()
    renderWithProviders(<RiderHeader {...defaultProps} onManageTeams={onManageTeams} />)

    await user.click(screen.getByText('Gestisci Team'))
    expect(onManageTeams).toHaveBeenCalled()
  })

  it('calls onNotifyAll when notify all button is clicked', async () => {
    const user = userEvent.setup()
    const onNotifyAll = vi.fn()
    renderWithProviders(<RiderHeader {...defaultProps} onNotifyAll={onNotifyAll} />)

    await user.click(screen.getByText('Notifica tutti'))
    expect(onNotifyAll).toHaveBeenCalled()
  })

  it('calls onNewRider when new rider button is clicked', async () => {
    const user = userEvent.setup()
    const onNewRider = vi.fn()
    renderWithProviders(<RiderHeader {...defaultProps} onNewRider={onNewRider} />)

    await user.click(screen.getByText('Nuovo Rider'))
    expect(onNewRider).toHaveBeenCalled()
  })

  it('calls onSync when sync button is clicked', async () => {
    const user = userEvent.setup()
    const onSync = vi.fn()
    renderWithProviders(<RiderHeader {...defaultProps} onSync={onSync} />)

    await user.click(screen.getByText('Sincronizza'))
    expect(onSync).toHaveBeenCalled()
  })

  it('disables sync button when syncing', () => {
    renderWithProviders(<RiderHeader {...defaultProps} syncing={true} />)
    const syncButton = screen.getByText('Sincronizzazione...').closest('button')
    expect(syncButton).toBeDisabled()
  })

  it('disables sync button when refreshing', () => {
    renderWithProviders(<RiderHeader {...defaultProps} refreshing={true} />)
    const syncButton = screen.getByText('Sincronizza').closest('button')
    expect(syncButton).toBeDisabled()
  })

  it('shows stale data warning when isStale is true and no error', () => {
    renderWithProviders(<RiderHeader {...defaultProps} isStale={true} />)
    expect(screen.getByText(/dati potrebbero non essere aggiornati/i)).toBeInTheDocument()
  })

  it('does not show stale warning when there is an error', () => {
    renderWithProviders(<RiderHeader {...defaultProps} isStale={true} error="Connection error" />)
    expect(screen.queryByText(/dati potrebbero non essere aggiornati/i)).not.toBeInTheDocument()
  })

  it('shows error message when error is set', () => {
    renderWithProviders(<RiderHeader {...defaultProps} error="Connection error" />)
    expect(screen.getByText('Connection error')).toBeInTheDocument()
  })

  it('shows sync timestamp when lastSyncedAt is set', () => {
    renderWithProviders(<RiderHeader {...defaultProps} lastSyncedAt="2026-02-17T10:30:00Z" />)
    // The component shows "Sincronizzato" when not stale
    expect(screen.getByText(/sincronizzato/i)).toBeInTheDocument()
  })

  it('shows stale label when lastSyncedAt is set and isStale is true', () => {
    renderWithProviders(<RiderHeader {...defaultProps} lastSyncedAt="2026-02-17T08:00:00Z" isStale={true} />)
    expect(screen.getByText(/dati non aggiornati/i)).toBeInTheDocument()
  })
})
