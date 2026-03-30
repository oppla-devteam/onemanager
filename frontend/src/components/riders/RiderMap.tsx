import { useEffect, useRef, useState } from 'react'
import mapboxgl from 'mapbox-gl'
import { motion } from 'framer-motion'
import { MapPin, RefreshCw, Wifi, WifiOff, Battery } from 'lucide-react'
import { RealtimeRider } from './types'

import 'mapbox-gl/dist/mapbox-gl.css'

const MAPBOX_TOKEN = import.meta.env.VITE_MAPBOX_TOKEN

interface RiderMapProps {
  riders: RealtimeRider[]
  loading: boolean
}

export default function RiderMap({ riders, loading }: RiderMapProps) {
  const mapContainer = useRef<HTMLDivElement>(null)
  const map = useRef<mapboxgl.Map | null>(null)
  const markersRef = useRef<mapboxgl.Marker[]>([])
  const [mapLoaded, setMapLoaded] = useState(false)

  const onlineRiders = riders.filter(r => r.is_online)
  const offlineRiders = riders.filter(r => !r.is_online)

  // Initialize map once on mount
  useEffect(() => {
    if (!mapContainer.current || map.current) return

    mapboxgl.accessToken = MAPBOX_TOKEN

    map.current = new mapboxgl.Map({
      container: mapContainer.current,
      style: 'mapbox://styles/mapbox/dark-v11',
      center: [10.4017, 43.7228],
      zoom: 12,
    })

    map.current.addControl(new mapboxgl.NavigationControl(), 'top-right')

    map.current.on('load', () => setMapLoaded(true))

    return () => {
      markersRef.current.forEach(m => m.remove())
      markersRef.current = []
      map.current?.remove()
      map.current = null
    }
  }, [])

  // Update markers whenever riders data changes
  useEffect(() => {
    if (!map.current || !mapLoaded) return

    // Remove existing markers
    markersRef.current.forEach(m => m.remove())
    markersRef.current = []

    // Only show online riders with a known position on the map
    const ridersWithLocation = riders.filter(r => r.is_online && r.latitude && r.longitude)

    ridersWithLocation.forEach(rider => {
      // Outer element: Mapbox uses transform:translate() on this – don't touch transform here
      const el = document.createElement('div')
      el.style.cssText = 'width:34px;height:34px;cursor:pointer;'

      // Inner element: safe to apply hover scale here without breaking map positioning
      const inner = document.createElement('div')
      inner.style.cssText = `
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background-color: #059669;
        border: 3px solid #34d399;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        box-shadow: 0 0 0 5px rgba(52, 211, 153, 0.25);
        transition: transform 0.15s ease;
      `
      inner.textContent = '🏍️'
      el.appendChild(inner)
      el.addEventListener('mouseenter', () => { inner.style.transform = 'scale(1.2)' })
      el.addEventListener('mouseleave', () => { inner.style.transform = '' })

      const popup = new mapboxgl.Popup({
        offset: 20,
        closeButton: true,
        closeOnClick: false,
        maxWidth: '200px',
      }).setHTML(`
        <div style="padding:4px 2px">
          <div style="font-weight:700;font-size:14px;margin-bottom:6px;color:#f1f5f9">${rider.name}</div>
          <div style="font-size:12px;color:${rider.is_online ? '#34d399' : '#94a3b8'};margin-bottom:4px">
            ${rider.is_online ? '● Online' : '○ Offline'}
          </div>
          ${rider.battery_level !== null ? `<div style="font-size:12px;color:#94a3b8">🔋 ${rider.battery_level}%</div>` : ''}
          ${rider.minutes_since_update !== null ? `<div style="font-size:12px;color:#94a3b8;margin-top:2px">⏱ aggiornato ${rider.minutes_since_update}m fa</div>` : ''}
          <a href="https://www.google.com/maps?q=${rider.latitude},${rider.longitude}" target="_blank" rel="noopener noreferrer"
            style="display:inline-block;margin-top:8px;font-size:11px;color:#38bdf8;text-decoration:none">
            Apri in Maps ↗
          </a>
        </div>
      `)

      const marker = new mapboxgl.Marker(el)
        .setLngLat([rider.longitude!, rider.latitude!])
        .setPopup(popup)
        .addTo(map.current!)

      markersRef.current.push(marker)
    })

    // Fit map to show all riders
    if (ridersWithLocation.length === 1) {
      map.current.flyTo({
        center: [ridersWithLocation[0].longitude!, ridersWithLocation[0].latitude!],
        zoom: 14,
        duration: 800,
      })
    } else if (ridersWithLocation.length > 1) {
      const bounds = new mapboxgl.LngLatBounds()
      ridersWithLocation.forEach(r => bounds.extend([r.longitude!, r.latitude!]))
      map.current.fitBounds(bounds, { padding: 70, maxZoom: 15, duration: 800 })
    }
  }, [riders, mapLoaded])

  return (
    <motion.div
      initial={{ opacity: 0, height: 0 }}
      animate={{ opacity: 1, height: 'auto' }}
      exit={{ opacity: 0, height: 0 }}
      className="mb-6 overflow-hidden"
    >
      <div className="glass-card p-4">
        {/* Header */}
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-white flex items-center gap-2">
            <MapPin className="w-5 h-5 text-primary-400" />
            Posizione Rider in Tempo Reale
            {loading && <RefreshCw className="w-4 h-4 text-primary-400 animate-spin" />}
          </h2>
          <div className="flex items-center gap-4 text-sm">
            <span className="flex items-center gap-1 text-emerald-400">
              <Wifi className="w-4 h-4" />
              {onlineRiders.length} online
            </span>
            <span className="flex items-center gap-1 text-gray-500">
              <WifiOff className="w-4 h-4" />
              {offlineRiders.length} offline
            </span>
          </div>
        </div>

        {/* Map */}
        <div className="relative rounded-xl overflow-hidden border border-gray-200" style={{ height: '460px' }}>
          <div ref={mapContainer} className="w-full h-full" />

          {/* Loading overlay */}
          {!mapLoaded && (
            <div className="absolute inset-0 bg-gray-900/80 flex items-center justify-center">
              <div className="flex items-center gap-3 text-white">
                <RefreshCw className="w-5 h-5 animate-spin text-primary-400" />
                <span className="text-gray-600">Caricamento mappa...</span>
              </div>
            </div>
          )}

          {/* Empty state overlay */}
          {mapLoaded && riders.filter(r => r.is_online && r.latitude && r.longitude).length === 0 && !loading && (
            <div className="absolute inset-0 bg-gray-900/70 flex items-center justify-center">
              <div className="text-center">
                <MapPin className="w-10 h-10 text-gray-400 mx-auto mb-2" />
                <p className="text-gray-500 text-sm">Nessun rider online con posizione disponibile</p>
              </div>
            </div>
          )}
        </div>

        {/* Rider list – only online riders are clickable to center the map */}
        {riders.length > 0 && (
          <div className="mt-4 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
            {riders.slice(0, 12).map(rider => {
              const canFly = rider.is_online && rider.latitude && rider.longitude
              return (
                <button
                  key={rider.fleet_id}
                  disabled={!canFly}
                  onClick={() => {
                    if (!canFly || !map.current) return
                    map.current.flyTo({
                      center: [rider.longitude!, rider.latitude!],
                      zoom: 15,
                      duration: 600,
                    })
                    const onlineWithLoc = riders.filter(r => r.is_online && r.latitude && r.longitude)
                    const idx = onlineWithLoc.findIndex(r => r.fleet_id === rider.fleet_id)
                    markersRef.current[idx]?.togglePopup()
                  }}
                  className={`p-2 rounded-lg border text-left transition-all ${
                    rider.is_online
                      ? 'bg-emerald-500/10 border-emerald-500/30 hover:bg-emerald-500/20 hover:scale-[1.03] cursor-pointer'
                      : 'bg-gray-100/30 border-gray-200/30 opacity-50 cursor-default'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <div className={`w-2 h-2 rounded-full flex-shrink-0 ${rider.is_online ? 'bg-emerald-400' : 'bg-slate-500'}`} />
                    <span className="text-sm text-white truncate">{rider.name}</span>
                  </div>
                  <div className="flex items-center gap-2 mt-1 text-xs text-gray-500">
                    {rider.is_online ? (
                      <>
                        {rider.battery_level !== null && (
                          <span className="flex items-center gap-0.5">
                            <Battery className="w-3 h-3" />
                            {rider.battery_level}%
                          </span>
                        )}
                        {rider.minutes_since_update !== null && (
                          <span>{rider.minutes_since_update}m fa</span>
                        )}
                      </>
                    ) : (
                      <span className="text-gray-400">Offline</span>
                    )}
                  </div>
                </button>
              )
            })}
          </div>
        )}
      </div>
    </motion.div>
  )
}
