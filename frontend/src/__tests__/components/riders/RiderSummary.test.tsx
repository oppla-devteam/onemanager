import { describe, it, expect } from 'vitest'
import { screen } from '@testing-library/react'
import { renderWithProviders } from '../../../test/helpers'
import RiderSummary from '../../../components/riders/RiderSummary'
import { Summary } from '../../../components/riders/types'

const createMockSummary = (overrides: Partial<Summary> = {}): Summary => ({
  total: 25,
  available: 10,
  busy: 8,
  offline: 7,
  online: 18,
  ...overrides,
})

describe('RiderSummary', () => {
  it('renders without crashing', () => {
    const summary = createMockSummary()
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('Totale Rider')).toBeInTheDocument()
  })

  it('displays total rider count', () => {
    const summary = createMockSummary({ total: 25 })
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('25')).toBeInTheDocument()
  })

  it('displays available rider count', () => {
    const summary = createMockSummary({ available: 10 })
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('Disponibili')).toBeInTheDocument()
    expect(screen.getByText('10')).toBeInTheDocument()
  })

  it('displays busy rider count', () => {
    const summary = createMockSummary({ busy: 8 })
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('In Consegna')).toBeInTheDocument()
    expect(screen.getByText('8')).toBeInTheDocument()
  })

  it('displays offline rider count', () => {
    const summary = createMockSummary({ offline: 7 })
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('7')).toBeInTheDocument()
  })

  it('displays online rider count', () => {
    const summary = createMockSummary({ online: 18 })
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('Online')).toBeInTheDocument()
    expect(screen.getByText('18')).toBeInTheDocument()
  })

  it('shows dash when online is undefined', () => {
    const summary = createMockSummary({ online: undefined })
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('-')).toBeInTheDocument()
  })

  it('renders all five summary cards', () => {
    const summary = createMockSummary()
    renderWithProviders(<RiderSummary summary={summary} />)
    expect(screen.getByText('Totale Rider')).toBeInTheDocument()
    expect(screen.getByText('Online')).toBeInTheDocument()
    expect(screen.getByText('Disponibili')).toBeInTheDocument()
    expect(screen.getByText('In Consegna')).toBeInTheDocument()
    expect(screen.getByText('Offline')).toBeInTheDocument()
  })

  it('handles zero values correctly', () => {
    const summary = createMockSummary({ total: 0, available: 0, busy: 0, offline: 0, online: 0 })
    renderWithProviders(<RiderSummary summary={summary} />)
    const zeroElements = screen.getAllByText('0')
    expect(zeroElements.length).toBeGreaterThanOrEqual(4)
  })
})
