import { motion } from 'framer-motion'
import { X, Save } from 'lucide-react'
import { useState, useEffect } from 'react'
import { menusApi } from '../utils/api'

interface MenuItemModalProps {
  isOpen: boolean
  onClose: () => void
  restaurantId: number | null
  editingItem?: any | null
}

export default function MenuItemModal({
  isOpen,
  onClose,
  restaurantId,
  editingItem,
}: MenuItemModalProps) {
  const [formData, setFormData] = useState({
    category: '',
    product_name: '',
    description: '',
    price_cents: '',
    available_for_delivery: true,
    available_for_pickup: true,
    is_active: true,
    image_url: '',
  })

  const [saving, setSaving] = useState(false)

  useEffect(() => {
    if (editingItem) {
      setFormData({
        category: editingItem.category || '',
        product_name: editingItem.product_name || '',
        description: editingItem.description || '',
        price_cents: editingItem.price_cents?.toString() || '',
        available_for_delivery: editingItem.available_for_delivery ?? true,
        available_for_pickup: editingItem.available_for_pickup ?? true,
        is_active: editingItem.is_active ?? true,
        image_url: editingItem.image_url || '',
      })
    } else {
      setFormData({
        category: '',
        product_name: '',
        description: '',
        price_cents: '',
        available_for_delivery: true,
        available_for_pickup: true,
        is_active: true,
        image_url: '',
      })
    }
  }, [editingItem, isOpen])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!restaurantId) {
      alert('Please select a restaurant first')
      return
    }

    setSaving(true)

    try {
      const data = {
        ...formData,
        restaurant_id: restaurantId,
        price_cents: parseInt(formData.price_cents),
      }

      if (editingItem) {
        await menusApi.update(editingItem.id, data)
      } else {
        await menusApi.create(data)
      }

      alert(editingItem ? 'Menu item updated!' : 'Menu item created!')
      onClose()
    } catch (error: any) {
      console.error('Error saving menu item:', error)
      alert('Failed to save menu item: ' + (error.response?.data?.message || error.message))
    } finally {
      setSaving(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Modal */}
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        exit={{ opacity: 0, scale: 0.95 }}
        className="glass-card w-full max-w-2xl relative z-10"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 className="text-2xl font-bold text-gradient">
            {editingItem ? 'Edit Menu Item' : 'Add Menu Item'}
          </h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-white transition-colors"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div className="grid grid-cols-2 gap-4">
            {/* Category */}
            <div>
              <label className="block text-sm font-medium text-gray-600 mb-2">
                Category *
              </label>
              <input
                type="text"
                value={formData.category}
                onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                className="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
                required
                placeholder="e.g., BIRRE ARTIGIANALI"
              />
            </div>

            {/* Product Name */}
            <div>
              <label className="block text-sm font-medium text-gray-600 mb-2">
                Product Name *
              </label>
              <input
                type="text"
                value={formData.product_name}
                onChange={(e) => setFormData({ ...formData, product_name: e.target.value })}
                className="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
                required
                placeholder="e.g., Birra Corona"
              />
            </div>
          </div>

          {/* Description */}
          <div>
            <label className="block text-sm font-medium text-gray-600 mb-2">
              Description
            </label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              className="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
              rows={3}
              placeholder="Product description..."
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            {/* Price */}
            <div>
              <label className="block text-sm font-medium text-gray-600 mb-2">
                Price (cents) *
              </label>
              <input
                type="number"
                value={formData.price_cents}
                onChange={(e) => setFormData({ ...formData, price_cents: e.target.value })}
                className="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
                required
                min="0"
                placeholder="e.g., 1050 for €10.50"
              />
              {formData.price_cents && (
                <p className="text-gray-500 text-sm mt-1">
                  = €{(parseInt(formData.price_cents) / 100).toFixed(2)}
                </p>
              )}
            </div>

            {/* Image URL */}
            <div>
              <label className="block text-sm font-medium text-gray-600 mb-2">
                Image URL
              </label>
              <input
                type="url"
                value={formData.image_url}
                onChange={(e) => setFormData({ ...formData, image_url: e.target.value })}
                className="w-full bg-gray-50/50 border border-gray-200 rounded-lg px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
                placeholder="https://..."
              />
            </div>
          </div>

          {/* Checkboxes */}
          <div className="space-y-3">
            <label className="flex items-center gap-3">
              <input
                type="checkbox"
                checked={formData.available_for_delivery}
                onChange={(e) =>
                  setFormData({ ...formData, available_for_delivery: e.target.checked })
                }
                className="w-4 h-4 rounded border-gray-200 bg-gray-50 text-primary-500 focus:ring-primary-500"
              />
              <span className="text-gray-600">Available for Delivery</span>
            </label>

            <label className="flex items-center gap-3">
              <input
                type="checkbox"
                checked={formData.available_for_pickup}
                onChange={(e) =>
                  setFormData({ ...formData, available_for_pickup: e.target.checked })
                }
                className="w-4 h-4 rounded border-gray-200 bg-gray-50 text-primary-500 focus:ring-primary-500"
              />
              <span className="text-gray-600">Available for Pickup</span>
            </label>

            <label className="flex items-center gap-3">
              <input
                type="checkbox"
                checked={formData.is_active}
                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                className="w-4 h-4 rounded border-gray-200 bg-gray-50 text-primary-500 focus:ring-primary-500"
              />
              <span className="text-gray-600">Active</span>
            </label>
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={saving}
              className="glass-button-primary"
            >
              <Save className="w-5 h-5" />
              {saving ? 'Saving...' : editingItem ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </motion.div>
    </div>
  )
}
