import { Search } from 'lucide-react'
import NotificationBell from './NotificationBell'

export default function Header() {
  return (
    <header className="sticky top-0 z-40 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
      <div className="flex h-16 items-center justify-between px-4 lg:px-8">
        {/* Search */}
        <div className="flex-1 max-w-2xl mr-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input
              type="search"
              placeholder="Cerca clienti, fatture..."
              className="glass-input pl-10 w-full"
            />
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-4">
          <NotificationBell />
        </div>
      </div>
    </header>
  )
}
