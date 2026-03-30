import { motion, AnimatePresence } from 'framer-motion'
import { useState, useEffect } from 'react'
import {
  Plus,
  Search,
  Edit2,
  Trash2,
  Package,
  FileText,
  ShoppingBag,
  AlertCircle,
} from 'lucide-react'
import { menusApi, restaurantsApi } from '../utils/api'
import MenuItemModal from '../components/MenuItemModal'
import RestaurantAutocomplete from '../components/RestaurantAutocomplete'

interface Restaurant {
  id: number
  nome: string
  citta: string
}

interface MenuItem {
  id: number
  restaurant_id: number
  category: string
  product_name: string
  description: string | null
  price_cents: number
  available_for_delivery: boolean
  available_for_pickup: boolean
  is_active: boolean
  image_url: string | null
  sort_order: number
  restaurant?: Restaurant
  formatted_price?: string
}

export default function Menus() {
  const [menuItems, setMenuItems] = useState<MenuItem[]>([])
  const [restaurants, setRestaurants] = useState<Restaurant[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedRestaurant, setSelectedRestaurant] = useState<number | null>(null)
  const [selectedCategory, setSelectedCategory] = useState<string | 'all'>('all')
  const [categories, setCategories] = useState<string[]>([])

  // Modals
  const [isItemModalOpen, setIsItemModalOpen] = useState(false)
  const [editingItem, setEditingItem] = useState<MenuItem | null>(null)

  // Pagination
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [total, setTotal] = useState(0)

  useEffect(() => {
    loadRestaurants()
    loadMenuItems()
  }, [])

  useEffect(() => {
    loadMenuItems()
  }, [selectedRestaurant, selectedCategory, searchTerm, currentPage])

  useEffect(() => {
    if (selectedRestaurant) {
      loadCategories()
    }
  }, [selectedRestaurant])

  const loadRestaurants = async () => {
    try {
      const response = await restaurantsApi.getAll()
      setRestaurants(response.data.data || [])
    } catch (error) {
      console.error('Error loading restaurants:', error)
    }
  }

  const loadMenuItems = async () => {
    try {
      setLoading(true)
      const params: any = {
        page: currentPage,
        per_page: 50,
      }

      if (selectedRestaurant) {
        params.restaurant_id = selectedRestaurant
      }

      if (selectedCategory && selectedCategory !== 'all') {
        params.category = selectedCategory
      }

      if (searchTerm) {
        params.search = searchTerm
      }

      const response = await menusApi.getAll(params)
      setMenuItems(response.data.data || [])
      setTotal(response.data.total || 0)
      setTotalPages(response.data.last_page || 1)
    } catch (error) {
      console.error('Error loading menu items:', error)
    } finally {
      setLoading(false)
    }
  }

  const loadCategories = async () => {
    try {
      const response = await menusApi.getCategories(selectedRestaurant || undefined)
      setCategories(response.data.data || [])
    } catch (error) {
      console.error('Error loading categories:', error)
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this menu item?')) return

    try {
      await menusApi.delete(id)
      loadMenuItems()
    } catch (error) {
      console.error('Error deleting menu item:', error)
      alert('Failed to delete menu item')
    }
  }

  const handleToggleActive = async (item: MenuItem) => {
    try {
      await menusApi.update(item.id, {
        is_active: !item.is_active,
      })
      loadMenuItems()
    } catch (error) {
      console.error('Error toggling active status:', error)
      alert('Failed to update menu item')
    }
  }

  const handleEdit = (item: MenuItem) => {
    setEditingItem(item)
    setIsItemModalOpen(true)
  }

  const handleItemModalClose = () => {
    setIsItemModalOpen(false)
    setEditingItem(null)
    loadMenuItems()
    loadCategories()
  }

  const formatPrice = (cents: number) => {
    return '€' + (cents / 100).toFixed(2).replace('.', ',')
  }

  // Group items by category
  const groupedItems = menuItems.reduce((acc, item) => {
    if (!acc[item.category]) {
      acc[item.category] = []
    }
    acc[item.category].push(item)
    return acc
  }, {} as Record<string, MenuItem[]>)

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">
            <span className="text-gradient">Menu Management</span>
          </h1>
          <p className="text-gray-500 mt-1">Manage restaurant menus and products</p>
        </div>
        <motion.button
          whileHover={{ scale: 1.02 }}
          whileTap={{ scale: 0.98 }}
          onClick={() => {
            setEditingItem(null)
            setIsItemModalOpen(true)
          }}
          className="glass-button-primary"
          disabled={!selectedRestaurant}
        >
          <Plus className="w-5 h-5" />
          Add Item
        </motion.button>
      </div>

      {/* Filters */}
      <div className="glass-card p-6 !overflow-visible">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Restaurant Filter */}
          <div>
            <label className="block text-sm font-medium text-gray-600 mb-2">
              Restaurant
            </label>
            <RestaurantAutocomplete
              restaurants={restaurants}
              selectedRestaurantId={selectedRestaurant}
              onSelect={(id) => {
                setSelectedRestaurant(id)
                setSelectedCategory('all')
              }}
              placeholder="Search restaurants..."
            />
          </div>

          {/* Category Filter */}
          <div>
            <label className="block text-sm font-medium text-gray-600 mb-2">
              Category
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
              disabled={!selectedRestaurant}
            >
              <option value="all">All Categories</option>
              {categories.map((category) => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </select>
          </div>

          {/* Search */}
          <div>
            <label className="block text-sm font-medium text-gray-600 mb-2">
              Search
            </label>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5" />
              <input
                type="text"
                placeholder="Search products..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full bg-gray-50/50 border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-gray-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Stats */}
      {selectedRestaurant && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Total Items</p>
                <p className="text-2xl font-bold mt-1">{total}</p>
              </div>
              <Package className="w-8 h-8 text-primary-400" />
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Categories</p>
                <p className="text-2xl font-bold mt-1">{categories.length}</p>
              </div>
              <FileText className="w-8 h-8 text-green-400" />
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Delivery</p>
                <p className="text-2xl font-bold mt-1 text-purple-400">
                  {menuItems.filter((i) => i.available_for_delivery).length}
                </p>
              </div>
              <ShoppingBag className="w-8 h-8 text-purple-400" />
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="glass-card p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">Pickup</p>
                <p className="text-2xl font-bold mt-1 text-cyan-400">
                  {menuItems.filter((i) => i.available_for_pickup).length}
                </p>
              </div>
              <Package className="w-8 h-8 text-cyan-400" />
            </div>
          </motion.div>
        </div>
      )}

      {/* Menu Items Table */}
      {loading ? (
        <div className="glass-card p-12 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
          <p className="text-gray-500 mt-4">Loading menu items...</p>
        </div>
      ) : !selectedRestaurant ? (
        <div className="glass-card p-12 text-center">
          <AlertCircle className="w-12 h-12 text-gray-500 mx-auto mb-4" />
          <p className="text-gray-500">Please select a restaurant to view menu items</p>
        </div>
      ) : menuItems.length === 0 ? (
        <div className="glass-card p-12 text-center">
          <Package className="w-12 h-12 text-gray-500 mx-auto mb-4" />
          <p className="text-gray-500">No menu items found</p>
          <p className="text-gray-400 text-sm mt-2">
            Add items manually using the Add Item button
          </p>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Grouped by Category */}
          {Object.entries(groupedItems).map(([category, items]) => (
            <motion.div
              key={category}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="glass-card"
            >
              {/* Category Header */}
              <div className="border-b border-gray-200 p-6">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white">{category}</h3>
                <p className="text-gray-500 text-sm mt-1">{items.length} items</p>
              </div>

              {/* Items Table */}
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead className="bg-gray-50/50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                        Product
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                        Description
                      </th>
                      <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                        Price
                      </th>
                      <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                        Delivery
                      </th>
                      <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                        Pickup
                      </th>
                      <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-800">
                    {items.map((item) => (
                      <tr key={item.id} className="hover:bg-gray-50/30">
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-3">
                            {item.image_url ? (
                              <img
                                src={item.image_url}
                                alt={item.product_name}
                                className="w-10 h-10 rounded object-cover"
                              />
                            ) : (
                              <div className="w-10 h-10 rounded bg-gray-100 flex items-center justify-center">
                                <Package className="w-5 h-5 text-gray-500" />
                              </div>
                            )}
                            <div>
                              <p className="text-gray-900 dark:text-white font-medium">{item.product_name}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 text-gray-500 text-sm max-w-xs truncate">
                          {item.description || '-'}
                        </td>
                        <td className="px-6 py-4 text-center">
                          <span className="text-gray-900 dark:text-white font-semibold">
                            {formatPrice(item.price_cents)}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-center">
                          {item.available_for_delivery ? (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                              Yes
                            </span>
                          ) : (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-500/20 text-gray-500 border border-gray-300/30">
                              No
                            </span>
                          )}
                        </td>
                        <td className="px-6 py-4 text-center">
                          {item.available_for_pickup ? (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                              Yes
                            </span>
                          ) : (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-500/20 text-gray-500 border border-gray-300/30">
                              No
                            </span>
                          )}
                        </td>
                        <td className="px-6 py-4 text-center">
                          <button
                            onClick={() => handleToggleActive(item)}
                            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${
                              item.is_active
                                ? 'bg-green-500/20 text-green-400 border-green-500/30'
                                : 'bg-red-500/20 text-red-400 border-red-500/30'
                            }`}
                          >
                            {item.is_active ? 'Active' : 'Inactive'}
                          </button>
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center justify-center gap-2">
                            <button
                              onClick={() => handleEdit(item)}
                              className="text-primary-400 hover:text-primary-300 transition-colors"
                              title="Edit"
                            >
                              <Edit2 className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => handleDelete(item.id)}
                              className="text-red-400 hover:text-red-300 transition-colors"
                              title="Delete"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </motion.div>
          ))}

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex justify-center gap-2">
              <button
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                disabled={currentPage === 1}
                className="px-4 py-2 rounded bg-gray-50 text-gray-700 disabled:opacity-50"
              >
                Previous
              </button>
              <span className="px-4 py-2 text-gray-500">
                Page {currentPage} of {totalPages}
              </span>
              <button
                onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
                className="px-4 py-2 rounded bg-gray-50 text-gray-700 disabled:opacity-50"
              >
                Next
              </button>
            </div>
          )}
        </div>
      )}

      {/* Modals */}
      <MenuItemModal
        isOpen={isItemModalOpen}
        onClose={handleItemModalClose}
        restaurantId={selectedRestaurant}
        editingItem={editingItem}
      />
    </div>
  )
}
