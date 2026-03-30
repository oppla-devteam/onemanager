import { useEffect, useRef, useState, useCallback } from 'react'
import mapboxgl from 'mapbox-gl'
import MapboxDraw from '@mapbox/mapbox-gl-draw'
import { MapPin, Pencil, Trash2, Save, X, Loader2, Plus } from 'lucide-react'

import 'mapbox-gl/dist/mapbox-gl.css'
import '@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css'

// Mapbox access token - should be in env
const MAPBOX_TOKEN = import.meta.env.VITE_MAPBOX_TOKEN || 'pk.eyJ1Ijoib3BwbGEiLCJhIjoiY2x4eW9nNGV2MGIxNDJrcXV4OWZsMnB2bSJ9.1234567890' // Replace with actual token

interface DeliveryZone {
  id: number
  oppla_id?: string | null
  name: string
  city: string
  description?: string
  geometry?: {
    type: 'Polygon' | 'MultiPolygon'
    coordinates: number[][][]
  } | null
  center_lat?: number | null
  center_lng?: number | null
  color?: string
  source?: string
  has_geometry?: boolean
}

interface DeliveryZoneMapProps {
  zones: DeliveryZone[]
  selectedZoneId?: number | null
  onZoneSelect?: (zone: DeliveryZone) => void
  onZoneUpdate?: (zoneId: number, geometry: any) => void
  onZoneCreate?: (geometry: any) => void
  editable?: boolean
  height?: string
  initialCenter?: [number, number]
  initialZoom?: number
}

export default function DeliveryZoneMap({
  zones,
  selectedZoneId,
  onZoneSelect,
  onZoneUpdate,
  onZoneCreate,
  editable = false,
  height = '500px',
  initialCenter = [10.4017, 43.7228], // Livorno, Italy
  initialZoom = 11,
}: DeliveryZoneMapProps) {
  const mapContainer = useRef<HTMLDivElement>(null)
  const map = useRef<mapboxgl.Map | null>(null)
  const draw = useRef<MapboxDraw | null>(null)
  const [mapLoaded, setMapLoaded] = useState(false)
  const [isDrawing, setIsDrawing] = useState(false)
  const [drawMode, setDrawMode] = useState<'view' | 'draw' | 'edit'>('view')

  // Initialize map
  useEffect(() => {
    if (!mapContainer.current || map.current) return

    mapboxgl.accessToken = MAPBOX_TOKEN

    map.current = new mapboxgl.Map({
      container: mapContainer.current,
      style: 'mapbox://styles/mapbox/dark-v11',
      center: initialCenter,
      zoom: initialZoom,
    })

    // Add navigation controls
    map.current.addControl(new mapboxgl.NavigationControl(), 'top-right')

    // Initialize draw controls if editable
    if (editable) {
      draw.current = new MapboxDraw({
        displayControlsDefault: false,
        controls: {
          polygon: true,
          trash: true,
        },
        defaultMode: 'simple_select',
        styles: [
          // Polygon fill
          {
            id: 'gl-draw-polygon-fill',
            type: 'fill',
            filter: ['all', ['==', '$type', 'Polygon'], ['!=', 'mode', 'static']],
            paint: {
              'fill-color': '#3b82f6',
              'fill-outline-color': '#3b82f6',
              'fill-opacity': 0.3,
            },
          },
          // Polygon outline
          {
            id: 'gl-draw-polygon-stroke-active',
            type: 'line',
            filter: ['all', ['==', '$type', 'Polygon'], ['!=', 'mode', 'static']],
            layout: {
              'line-cap': 'round',
              'line-join': 'round',
            },
            paint: {
              'line-color': '#3b82f6',
              'line-width': 2,
            },
          },
          // Vertex points
          {
            id: 'gl-draw-point',
            type: 'circle',
            filter: ['all', ['==', '$type', 'Point'], ['==', 'meta', 'vertex']],
            paint: {
              'circle-radius': 6,
              'circle-color': '#fff',
              'circle-stroke-color': '#3b82f6',
              'circle-stroke-width': 2,
            },
          },
        ],
      })
      map.current.addControl(draw.current)
    }

    map.current.on('load', () => {
      setMapLoaded(true)
    })

    // Cleanup
    return () => {
      map.current?.remove()
      map.current = null
    }
  }, [editable, initialCenter, initialZoom])

  // Add zones to map when loaded
  useEffect(() => {
    if (!map.current || !mapLoaded) return

    const addZonesToMap = () => {
      if (!map.current) return

      // Remove existing layers and sources
      zones.forEach((zone) => {
        const sourceId = `zone-${zone.id}`
        if (map.current?.getLayer(`${sourceId}-fill`)) {
          map.current.removeLayer(`${sourceId}-fill`)
        }
        if (map.current?.getLayer(`${sourceId}-outline`)) {
          map.current.removeLayer(`${sourceId}-outline`)
        }
        if (map.current?.getLayer(`${sourceId}-label`)) {
          map.current.removeLayer(`${sourceId}-label`)
        }
        if (map.current?.getSource(`${sourceId}-label-source`)) {
          map.current.removeSource(`${sourceId}-label-source`)
        }
        if (map.current?.getSource(sourceId)) {
          map.current.removeSource(sourceId)
        }
      })

      // Add zones with geometry
      zones.forEach((zone) => {
        if (!zone.geometry || !zone.has_geometry) return

        const sourceId = `zone-${zone.id}`
        const color = zone.color || '#3b82f6'
        const isSelected = zone.id === selectedZoneId

        // Add source
        map.current?.addSource(sourceId, {
          type: 'geojson',
          data: {
            type: 'Feature',
            properties: {
              id: zone.id,
              name: zone.name,
              city: zone.city,
            },
            geometry: zone.geometry as GeoJSON.Polygon | GeoJSON.MultiPolygon,
          },
        })

        // Add fill layer
        map.current?.addLayer({
          id: `${sourceId}-fill`,
          type: 'fill',
          source: sourceId,
          paint: {
            'fill-color': color,
            'fill-opacity': isSelected ? 0.5 : 0.3,
          },
        })

        // Add outline layer
        map.current?.addLayer({
          id: `${sourceId}-outline`,
          type: 'line',
          source: sourceId,
          paint: {
            'line-color': color,
            'line-width': isSelected ? 3 : 2,
          },
        })

        // Add label at center
        if (zone.center_lat && zone.center_lng) {
          map.current?.addSource(`${sourceId}-label-source`, {
            type: 'geojson',
            data: {
              type: 'Feature',
              properties: { name: zone.name },
              geometry: {
                type: 'Point',
                coordinates: [zone.center_lng, zone.center_lat],
              },
            },
          })

          map.current?.addLayer({
            id: `${sourceId}-label`,
            type: 'symbol',
            source: `${sourceId}-label-source`,
            layout: {
              'text-field': ['get', 'name'],
              'text-size': 12,
              'text-anchor': 'center',
            },
            paint: {
              'text-color': '#ffffff',
              'text-halo-color': '#000000',
              'text-halo-width': 1,
            },
          })
        }

        // Click handler
        map.current?.on('click', `${sourceId}-fill`, () => {
          if (onZoneSelect && drawMode === 'view') {
            onZoneSelect(zone)
          }
        })

        // Hover effects
        map.current?.on('mouseenter', `${sourceId}-fill`, () => {
          if (map.current) {
            map.current.getCanvas().style.cursor = 'pointer'
          }
        })

        map.current?.on('mouseleave', `${sourceId}-fill`, () => {
          if (map.current) {
            map.current.getCanvas().style.cursor = ''
          }
        })
      })

      // Fit bounds to show all zones
      const zonesWithGeometry = zones.filter((z) => z.geometry && z.has_geometry)
      if (zonesWithGeometry.length > 0) {
        const bounds = new mapboxgl.LngLatBounds()

        zonesWithGeometry.forEach((zone) => {
          if (zone.geometry) {
            const coords = zone.geometry.coordinates[0]
            coords.forEach((coord: number[]) => {
              bounds.extend([coord[0], coord[1]])
            })
          }
        })

        map.current?.fitBounds(bounds, { padding: 50 })
      }
    }

    if (map.current.isStyleLoaded()) {
      addZonesToMap()
    } else {
      map.current.once('style.load', addZonesToMap)
    }
  }, [zones, mapLoaded, selectedZoneId, onZoneSelect, drawMode])

  // Handle draw events
  useEffect(() => {
    if (!map.current || !draw.current || !editable) return

    const handleCreate = (e: any) => {
      const feature = e.features[0]
      if (feature && onZoneCreate) {
        onZoneCreate(feature.geometry)
        // Clear the drawn polygon
        draw.current?.deleteAll()
        setDrawMode('view')
        setIsDrawing(false)
      }
    }

    const handleUpdate = (e: any) => {
      const feature = e.features[0]
      if (feature && selectedZoneId && onZoneUpdate) {
        onZoneUpdate(selectedZoneId, feature.geometry)
      }
    }

    map.current.on('draw.create', handleCreate)
    map.current.on('draw.update', handleUpdate)

    return () => {
      map.current?.off('draw.create', handleCreate)
      map.current?.off('draw.update', handleUpdate)
    }
  }, [editable, onZoneCreate, onZoneUpdate, selectedZoneId])

  // Start drawing a new zone
  const startDrawing = useCallback(() => {
    if (draw.current) {
      draw.current.changeMode('draw_polygon')
      setDrawMode('draw')
      setIsDrawing(true)
    }
  }, [])

  // Cancel drawing
  const cancelDrawing = useCallback(() => {
    if (draw.current) {
      draw.current.deleteAll()
      draw.current.changeMode('simple_select')
      setDrawMode('view')
      setIsDrawing(false)
    }
  }, [])

  // Center on a specific zone
  const centerOnZone = useCallback((zone: DeliveryZone) => {
    if (!map.current) return

    if (zone.center_lat && zone.center_lng) {
      map.current.flyTo({
        center: [zone.center_lng, zone.center_lat],
        zoom: 13,
      })
    } else if (zone.geometry) {
      const bounds = new mapboxgl.LngLatBounds()
      const coords = zone.geometry.coordinates[0]
      coords.forEach((coord: number[]) => {
        bounds.extend([coord[0], coord[1]])
      })
      map.current.fitBounds(bounds, { padding: 50 })
    }
  }, [])

  return (
    <div className="relative rounded-lg overflow-hidden border border-gray-200">
      {/* Map container */}
      <div ref={mapContainer} style={{ height }} className="w-full" />

      {/* Loading overlay */}
      {!mapLoaded && (
        <div className="absolute inset-0 bg-gray-900/80 flex items-center justify-center">
          <div className="flex items-center gap-3 text-white">
            <Loader2 className="w-6 h-6 animate-spin" />
            <span>Caricamento mappa...</span>
          </div>
        </div>
      )}

      {/* Controls overlay */}
      {editable && mapLoaded && (
        <div className="absolute top-4 left-4 flex flex-col gap-2">
          {drawMode === 'view' && (
            <button
              onClick={startDrawing}
              className="flex items-center gap-2 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg shadow-lg transition-colors"
            >
              <Plus className="w-4 h-4" />
              <span>Disegna zona</span>
            </button>
          )}

          {drawMode === 'draw' && (
            <div className="flex flex-col gap-2">
              <div className="px-3 py-2 bg-amber-600 text-white rounded-lg shadow-lg flex items-center gap-2">
                <Pencil className="w-4 h-4" />
                <span>Disegna il poligono</span>
              </div>
              <button
                onClick={cancelDrawing}
                className="flex items-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg shadow-lg transition-colors"
              >
                <X className="w-4 h-4" />
                <span>Annulla</span>
              </button>
            </div>
          )}
        </div>
      )}

      {/* Legend */}
      <div className="absolute bottom-4 left-4 bg-gray-50/90 backdrop-blur-sm rounded-lg p-3 shadow-lg">
        <div className="text-xs text-gray-500 mb-2">Zone di consegna</div>
        <div className="flex flex-col gap-1">
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-primary-500/50 border border-primary-500" />
            <span className="text-xs text-white">Zone OPPLA</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-emerald-500/50 border border-emerald-500" />
            <span className="text-xs text-white">Zone manuali</span>
          </div>
        </div>
      </div>

      {/* Zones without geometry indicator */}
      {zones.filter((z) => !z.has_geometry).length > 0 && (
        <div className="absolute bottom-4 right-4 bg-amber-600/90 backdrop-blur-sm rounded-lg p-3 shadow-lg">
          <div className="text-xs text-white">
            <MapPin className="w-4 h-4 inline mr-1" />
            {zones.filter((z) => !z.has_geometry).length} zone senza area disegnata
          </div>
        </div>
      )}
    </div>
  )
}
