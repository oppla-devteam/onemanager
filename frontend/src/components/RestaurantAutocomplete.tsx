import { useState, useEffect, useRef, useMemo } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Search, Check, Building2, AlertCircle } from 'lucide-react'

interface Restaurant {
  id: number
  nome: string
  citta: string
}

interface RestaurantAutocompleteProps {
  restaurants: Restaurant[]
  selectedRestaurantId: number | null
  onSelect: (restaurantId: number | null) => void
  placeholder?: string
  disabled?: boolean
  className?: string
}

export default function RestaurantAutocomplete({
  restaurants,
  selectedRestaurantId,
  onSelect,
  placeholder = 'Cerca ristorante...',
  disabled = false,
  className = '',
}: RestaurantAutocompleteProps) {
  const [searchTerm, setSearchTerm] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  const wrapperRef = useRef<HTMLDivElement>(null)

  // Get selected restaurant for display
  const selectedRestaurant = useMemo(() => {
    return restaurants.find((r) => r.id === selectedRestaurantId) || null
  }, [restaurants, selectedRestaurantId])

  // Filter restaurants based on search term
  const filteredRestaurants = useMemo(() => {
    if (!searchTerm.trim()) return restaurants
    const term = searchTerm.toLowerCase()
    return restaurants.filter(
      (r) =>
        r.nome.toLowerCase().includes(term) ||
        r.citta.toLowerCase().includes(term)
    )
  }, [restaurants, searchTerm])

  // Handle click outside to close dropdown
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [isOpen])

  // Handle restaurant selection
  const handleSelect = (restaurant: Restaurant | null) => {
    if (restaurant) {
      setSearchTerm('')
      onSelect(restaurant.id)
    } else {
      setSearchTerm('')
      onSelect(null)
    }
    setIsOpen(false)
  }

  // Handle input change
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value)
    if (!isOpen) setIsOpen(true)
  }

  // Handle input focus
  const handleInputFocus = () => {
    setIsOpen(true)
  }

  // Display value in input
  const displayValue = useMemo(() => {
    if (isOpen) {
      return searchTerm
    }
    if (selectedRestaurant) {
      return `${selectedRestaurant.nome} - ${selectedRestaurant.citta}`
    }
    return ''
  }, [isOpen, searchTerm, selectedRestaurant])

  return (
    <div ref={wrapperRef} className={`relative ${className}`}>
      {/* Input Field */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" />
        <input
          type="text"
          value={displayValue}
          onChange={handleInputChange}
          onFocus={handleInputFocus}
          placeholder={placeholder}
          disabled={disabled}
          className="glass-input w-full pl-10 pr-4"
        />
      </div>

      {/* Dropdown Results */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
            className="absolute z-[100] w-full mt-2 bg-gray-900/95 border border-gray-200 rounded-xl backdrop-blur-xl max-h-96 overflow-y-auto shadow-2xl"
          >
            {/* "All Restaurants" Option */}
            <button
              onClick={() => handleSelect(null)}
              className={`w-full text-left px-4 py-3 border-b border-gray-200/50 transition-all ${
                selectedRestaurantId === null
                  ? 'bg-primary-500/20 text-primary-400'
                  : 'hover:bg-white/5 text-gray-600'
              }`}
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Building2 className="w-4 h-4" />
                  <span className="font-medium">Tutti i Ristoranti</span>
                </div>
                {selectedRestaurantId === null && (
                  <Check className="w-5 h-5 text-primary-400" />
                )}
              </div>
            </button>

            {/* Filtered Restaurants List */}
            {filteredRestaurants.length === 0 ? (
              <div className="px-4 py-8 text-center text-gray-500">
                <AlertCircle className="w-8 h-8 mx-auto mb-2 opacity-50" />
                <p className="text-sm">Nessun ristorante trovato</p>
              </div>
            ) : (
              <div className="p-2 space-y-1">
                {filteredRestaurants.map((restaurant) => (
                  <motion.button
                    key={restaurant.id}
                    onClick={() => handleSelect(restaurant)}
                    whileHover={{ scale: 1.01 }}
                    className={`w-full text-left px-3 py-3 rounded-lg border-2 transition-all ${
                      selectedRestaurantId === restaurant.id
                        ? 'border-primary-500 bg-primary-500/10'
                        : 'border-gray-200/50 bg-gray-50/30 hover:border-gray-300 hover:bg-gray-50/50'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex-1 min-w-0">
                        <div className="font-semibold text-white truncate">
                          {restaurant.nome}
                        </div>
                        <div className="text-sm text-gray-500 truncate">
                          {restaurant.citta}
                        </div>
                      </div>
                      {selectedRestaurantId === restaurant.id && (
                        <Check className="w-5 h-5 text-primary-400 ml-2 flex-shrink-0" />
                      )}
                    </div>
                  </motion.button>
                ))}
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
