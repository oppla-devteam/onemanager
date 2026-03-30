import { motion, AnimatePresence } from 'framer-motion'
import { useState, useEffect } from 'react'
import {
  Plus,
  MoreVertical,
  CheckSquare,
  Circle,
  User,
  Calendar,
  Tag,
  X,
  Save,
  Trash2,
  Edit,
  GripVertical,
  Pencil,
  Filter,
  RotateCcw
} from 'lucide-react'
import Modal from '../components/Modal'
import { ToastContainer } from '../components/Toast'
import { useToast } from '../hooks/useToast'
import LoadingOverlay from '../components/LoadingOverlay'
import {
  DndContext,
  DragEndEvent,
  DragOverlay,
  DragStartEvent,
  PointerSensor,
  useSensor,
  useSensors,
  closestCorners,
  useDraggable,
  useDroppable,
} from '@dnd-kit/core'
import { CSS } from '@dnd-kit/utilities'

interface Task {
  id: number
  task_list_id: number
  title: string
  description?: string
  status: 'todo' | 'in_progress' | 'done'
  priority: 'low' | 'medium' | 'high'
  assigned_to?: string
  due_date?: string
  tags?: string[]
  position: number
}

interface TaskList {
  id: number
  task_board_id: number
  name: string
  status_type: 'todo' | 'in_progress' | 'done'
  position: number
  tasks?: Task[]
}

interface Board {
  id: number
  name: string
  color: string
  description?: string
  task_lists?: TaskList[]
}

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const DEPARTMENT_TAGS = [
  { name: 'Marketing', color: 'bg-pink-500/20 text-pink-400 border-pink-500/30' },
  { name: 'Operation', color: 'bg-primary-500/20 text-primary-400 border-primary-500/30' },
  { name: 'Business Development', color: 'bg-orange-500/20 text-orange-400 border-orange-500/30' },
  { name: 'Finance', color: 'bg-green-500/20 text-green-400 border-green-500/30' },
  { name: 'Legal', color: 'bg-purple-500/20 text-purple-400 border-purple-500/30' },
  { name: 'HR', color: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30' },
  { name: 'Tech', color: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30' },
] as const

const getTagColor = (tagName: string) => {
  const tag = DEPARTMENT_TAGS.find(t => t.name === tagName)
  return tag?.color || 'bg-slate-500/20 text-gray-500 border-gray-300/30'
}

// Componente Draggable Task Card
function DraggableTask({ task, onEdit, onDelete }: { 
  task: Task
  onEdit: (task: Task) => void
  onDelete: (taskId: number) => void
}) {
  const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
    id: task.id,
  })

  const style = {
    transform: CSS.Translate.toString(transform),
    opacity: isDragging ? 0.5 : 1,
  }

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'high': return 'bg-red-500/20 text-red-400 border-red-500/30'
      case 'medium': return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
      case 'low': return 'bg-green-500/20 text-green-400 border-green-500/30'
      default: return 'bg-slate-500/20 text-gray-500 border-gray-300/30'
    }
  }

  const getPriorityLabel = (priority: string) => {
    switch (priority) {
      case 'high': return 'Alta'
      case 'medium': return 'Media'
      case 'low': return 'Bassa'
      default: return priority
    }
  }

  return (
    <motion.div
      ref={setNodeRef}
      style={style}
      {...listeners}
      {...attributes}
      whileHover={{ scale: 1.02 }}
      className="glass-card p-4 cursor-grab active:cursor-grabbing group touch-none"
    >
      <div className="flex items-start justify-between mb-2">
        <h4 className="font-medium text-sm flex-1">{task.title}</h4>
        <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button 
            onClick={(e) => {
              e.stopPropagation()
              onEdit(task)
            }}
            className="text-gray-500 hover:text-primary-400"
          >
            <Edit className="w-4 h-4" />
          </button>
          <button 
            onClick={(e) => {
              e.stopPropagation()
              onDelete(task.id)
            }}
            className="text-gray-500 hover:text-red-400"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      </div>
      
      {task.description && (
        <p className="text-xs text-gray-500 mb-3">{task.description}</p>
      )}
      
      {task.tags && task.tags.length > 0 && (
        <div className="flex flex-wrap gap-1 mb-3">
          {task.tags.map((tag, i) => (
            <span key={i} className={`text-xs px-2 py-0.5 rounded border ${getTagColor(tag)}`}>
              <Tag className="w-3 h-3 inline mr-1" />
              {tag}
            </span>
          ))}
        </div>
      )}
      
      <div className="flex items-center justify-between text-xs text-gray-500 mb-3">
        {task.assigned_to && (
          <div className="flex items-center gap-1">
            <User className="w-3 h-3" />
            <span>{task.assigned_to}</span>
          </div>
        )}
        {task.due_date && (
          <div className="flex items-center gap-1">
            <Calendar className="w-3 h-3" />
            <span>{new Date(task.due_date).toLocaleDateString('it-IT')}</span>
          </div>
        )}
      </div>
      
      <div>
        <span className={`text-xs px-2 py-1 rounded border ${getPriorityColor(task.priority)}`}>
          {getPriorityLabel(task.priority)}
        </span>
      </div>
    </motion.div>
  )
}

// Componente Droppable Column
function DroppableColumn({ list, children }: { list: TaskList, children: React.ReactNode }) {
  const { setNodeRef, isOver } = useDroppable({
    id: list.id,
  })

  return (
    <div 
      ref={setNodeRef}
      className={`space-y-3 min-h-[200px] max-h-[calc(100vh-400px)] overflow-y-auto p-2 rounded-lg transition-colors scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-slate-800 ${
        isOver ? 'bg-primary-500/10 ring-2 ring-primary-500/50' : ''
      }`}
    >
      {children}
    </div>
  )
}

export default function Tasks() {
  const { toasts, removeToast, success, error, warning } = useToast()
  const [boards, setBoards] = useState<Board[]>([])
  const [selectedBoard, setSelectedBoard] = useState<number | null>(null)
  const [loading, setLoading] = useState(true)
  const [isCreatingBoard, setIsCreatingBoard] = useState(false)
  const [isCreatingTask, setIsCreatingTask] = useState(false)
  const [isDeletingBoard, setIsDeletingBoard] = useState(false)
  const [isDeletingTask, setIsDeletingTask] = useState(false)
  const [activeTask, setActiveTask] = useState<Task | null>(null)
  const [isDraggingUpdate, setIsDraggingUpdate] = useState(false)
  
  // Board rename states
  const [renamingBoardId, setRenamingBoardId] = useState<number | null>(null)
  const [renameBoardName, setRenameBoardName] = useState('')

  // Filter states
  const [showFilters, setShowFilters] = useState(false)
  const [filters, setFilters] = useState({
    assigned_to: '',
    priority: '',
    due_date_from: '',
    due_date_to: '',
    tag: '',
  })

  // User assignment states
  const [allUsers, setAllUsers] = useState<{id: number, name: string, email: string}[]>([])
  const [isAssignUsersModalOpen, setIsAssignUsersModalOpen] = useState(false)
  const [selectedUserIds, setSelectedUserIds] = useState<number[]>([])
  const [isAssigningUsers, setIsAssigningUsers] = useState(false)
  
  // Sensors per drag & drop
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  )
  
  // Modals
  const [isNewBoardModalOpen, setIsNewBoardModalOpen] = useState(false)
  const [isNewTaskModalOpen, setIsNewTaskModalOpen] = useState(false)
  const [isDeleteBoardModalOpen, setIsDeleteBoardModalOpen] = useState(false)
  const [selectedTaskListId, setSelectedTaskListId] = useState<number | null>(null)
  const [editingTask, setEditingTask] = useState<Task | null>(null)
  
  // Forms
  const [boardForm, setBoardForm] = useState({ name: '', color: 'from-primary-500 to-cyan-500', description: '' })
  const [taskForm, setTaskForm] = useState({
    title: '',
    description: '',
    priority: 'medium' as 'low' | 'medium' | 'high',
    assigned_to: '',
    due_date: '',
    tags: [] as string[]
  })

  const colorOptions = [
    'from-primary-500 to-cyan-500',
    'from-purple-500 to-pink-500',
    'from-orange-500 to-red-500',
    'from-green-500 to-emerald-500',
    'from-yellow-500 to-amber-500',
    'from-indigo-500 to-violet-500',
  ]

  useEffect(() => {
    loadBoards()
    loadAllUsers()
  }, [])

  useEffect(() => {
    if (selectedBoard) {
      loadBoardDetails(selectedBoard)
    }
  }, [selectedBoard])

  const loadAllUsers = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/users`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const users = await response.json()
        setAllUsers(users)
      }
    } catch (err) {
      console.error('Errore caricamento utenti:', err)
    }
  }

  const loadBoards = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/task-boards`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const data = await response.json()
      const boardsArray = Array.isArray(data) ? data : []
      setBoards(boardsArray)
      
      if (boardsArray.length > 0 && !selectedBoard) {
        setSelectedBoard(boardsArray[0].id)
      }
    } catch (error) {
      console.error('Errore caricamento boards:', error)
      setBoards([])
    } finally {
      setLoading(false)
    }
  }

  const loadBoardDetails = async (boardId: number) => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/task-boards/${boardId}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      const board = await response.json()
      setBoards(prev => prev.map(b => b.id === boardId ? board : b))
    } catch (error) {
      console.error('Errore caricamento board:', error)
    }
  }

  const openAssignUsersModal = async () => {
    if (!selectedBoard) return
    
    // Carica utenti già assegnati
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/task-boards/${selectedBoard}/users`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      if (response.ok) {
        const assignedUsers = await response.json()
        setSelectedUserIds(assignedUsers.map((u: any) => u.id))
      }
    } catch (err) {
      console.error('Errore caricamento utenti assegnati:', err)
    }
    
    setIsAssignUsersModalOpen(true)
  }

  const handleAssignUsers = async () => {
    if (!selectedBoard) return
    
    setIsAssigningUsers(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/task-boards/${selectedBoard}/assign-users`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ user_ids: selectedUserIds })
      })
      
      if (response.ok) {
        setIsAssignUsersModalOpen(false)
        success('Utenti assegnati con successo!')
      } else {
        const errorData = await response.json()
        error('Errore: ' + (errorData.message || 'Impossibile assegnare gli utenti'))
      }
    } catch (err) {
      console.error('Errore assegnazione utenti:', err)
      error('Errore durante l\'assegnazione degli utenti')
    } finally {
      setIsAssigningUsers(false)
    }
  }

  const handleRenameBoard = async (boardId: number, newName: string) => {
    if (!newName.trim()) {
      setRenamingBoardId(null)
      return
    }
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/task-boards/${boardId}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ name: newName.trim() })
      })
      if (response.ok) {
        setBoards(prev => prev.map(b => b.id === boardId ? { ...b, name: newName.trim() } : b))
        success('Board rinominata!')
      } else {
        error('Errore durante la rinomina della board')
      }
    } catch (err) {
      console.error('Errore rinomina board:', err)
      error('Errore durante la rinomina della board')
    } finally {
      setRenamingBoardId(null)
    }
  }

  const handleCreateBoard = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsCreatingBoard(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/task-boards`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(boardForm)
      })
      
      if (response.ok) {
        const newBoard = await response.json()
        await loadBoards()
        setSelectedBoard(newBoard.id)
        setIsNewBoardModalOpen(false)
        setBoardForm({ name: '', color: 'from-primary-500 to-cyan-500', description: '' })
        success('Board creata con successo!')
      } else {
        const errorData = await response.json()
        error('Errore: ' + (errorData.message || 'Impossibile creare la board'))
      }
    } catch (err) {
      console.error('Errore creazione board:', err)
      error('Errore durante la creazione della board')
    } finally {
      setIsCreatingBoard(false)
    }
  }

  const handleCreateTask = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedTaskListId) {
      error('Seleziona prima una lista!')
      return
    }

    setIsCreatingTask(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/tasks`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          ...taskForm,
          task_list_id: selectedTaskListId
        })
      })
      
      if (response.ok) {
        setIsNewTaskModalOpen(false)
        setTaskForm({
          title: '',
          description: '',
          priority: 'medium',
          assigned_to: '',
          due_date: '',
          tags: []
        })
        if (selectedBoard) {
          await loadBoardDetails(selectedBoard)
        }
        success('Task creato con successo!')
      } else {
        const errorData = await response.json()
        error('Errore: ' + (errorData.message || 'Impossibile creare il task'))
      }
    } catch (err) {
      console.error('Errore creazione task:', err)
      error('Errore durante la creazione del task')
    } finally {
      setIsCreatingTask(false)
    }
  }

  const handleUpdateTask = async (taskId: number, updates: Partial<Task>) => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/tasks/${taskId}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(updates)
      })
      
      if (response.ok && selectedBoard) {
        await loadBoardDetails(selectedBoard)
        success('Task aggiornato!')
      }
    } catch (err) {
      console.error('Errore aggiornamento task:', err)
      error('Errore aggiornamento task')
    }
  }

  const handleDeleteBoard = async () => {
    if (!selectedBoard) return

    setIsDeletingBoard(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/task-boards/${selectedBoard}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      
      if (response.ok) {
        await loadBoards()
        setSelectedBoard(boards.length > 1 ? boards.find(b => b.id !== selectedBoard)?.id || null : null)
        setIsDeleteBoardModalOpen(false)
        success('Board eliminata con successo!')
      } else {
        const errorData = await response.json()
        error('Errore: ' + (errorData.message || 'Impossibile eliminare la board'))
      }
    } catch (err) {
      console.error('Errore eliminazione board:', err)
      error('Errore durante l\'eliminazione della board')
    } finally {
      setIsDeletingBoard(false)
    }
  }

  const handleDeleteTask = async (taskId: number) => {
    if (!confirm('Sei sicuro di voler eliminare questo task?')) return

    setIsDeletingTask(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/tasks/${taskId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      
      if (response.ok && selectedBoard) {
        await loadBoardDetails(selectedBoard)
        success('Task eliminato!')
      }
    } catch (err) {
      console.error('Errore eliminazione task:', err)
      error('Errore eliminazione task')
    } finally {
      setIsDeletingTask(false)
    }
  }

  const handleDragStart = (event: DragStartEvent) => {
    const taskId = event.active.id as number
    const task = taskLists
      .flatMap(list => list.tasks || [])
      .find(t => t.id === taskId)
    setActiveTask(task || null)
  }

  const handleDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event
    setActiveTask(null)

    if (!over || active.id === over.id) return

    const taskId = active.id as number
    const newListId = over.id as number

    // Trova la lista di destinazione
    const targetList = taskLists.find(l => l.id === newListId)
    if (!targetList) return

    // Mostra loading durante l'aggiornamento
    setIsDraggingUpdate(true)
    try {
      // Aggiorna il task
      await handleUpdateTask(taskId, {
        task_list_id: newListId,
        status: targetList.status_type
      })
    } finally {
      setIsDraggingUpdate(false)
    }
  }

  const handleEditTask = (task: Task) => {
    setEditingTask(task)
    setTaskForm({
      title: task.title,
      description: task.description || '',
      priority: task.priority,
      assigned_to: task.assigned_to || '',
      due_date: task.due_date || '',
      tags: task.tags || []
    })
    setSelectedTaskListId(task.task_list_id)
    setIsNewTaskModalOpen(true)
  }

  const handleSaveEdit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editingTask) {
      handleCreateTask(e)
      return
    }

    setIsCreatingTask(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch(`${API_URL}/tasks/${editingTask.id}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(taskForm)
      })
      
      if (response.ok) {
        setIsNewTaskModalOpen(false)
        setEditingTask(null)
        setTaskForm({
          title: '',
          description: '',
          priority: 'medium',
          assigned_to: '',
          due_date: '',
          tags: []
        })
        if (selectedBoard) {
          await loadBoardDetails(selectedBoard)
        }
        success('Task modificato con successo!')
      } else {
        const errorData = await response.json()
        error('Errore: ' + (errorData.message || 'Impossibile modificare il task'))
      }
    } catch (err) {
      console.error('Errore modifica task:', err)
      error('Errore durante la modifica del task')
    } finally {
      setIsCreatingTask(false)
    }
  }

  const currentBoard = boards.find(b => b.id === selectedBoard)
  const taskLists = currentBoard?.task_lists || []

  const hasActiveFilters = filters.assigned_to || filters.priority || filters.due_date_from || filters.due_date_to || filters.tag

  const filteredTaskLists = taskLists.map(list => ({
    ...list,
    tasks: (list.tasks || []).filter(task => {
      if (filters.assigned_to && task.assigned_to !== filters.assigned_to) return false
      if (filters.priority && task.priority !== filters.priority) return false
      if (filters.due_date_from && (!task.due_date || task.due_date < filters.due_date_from)) return false
      if (filters.due_date_to && (!task.due_date || task.due_date > filters.due_date_to)) return false
      if (filters.tag && (!task.tags || !task.tags.includes(filters.tag))) return false
      return true
    })
  }))

  const resetFilters = () => setFilters({ assigned_to: '', priority: '', due_date_from: '', due_date_to: '', tag: '' })

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'high': return 'bg-red-500/20 text-red-400 border-red-500/30'
      case 'medium': return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
      case 'low': return 'bg-green-500/20 text-green-400 border-green-500/30'
      default: return 'bg-slate-500/20 text-gray-500 border-gray-300/30'
    }
  }

  const getPriorityLabel = (priority: string) => {
    switch (priority) {
      case 'high': return 'Alta'
      case 'medium': return 'Media'
      case 'low': return 'Bassa'
      default: return priority
    }
  }

  const getListIcon = (statusType: string) => {
    switch (statusType) {
      case 'todo': return <Circle className="w-5 h-5 text-gray-500" />
      case 'in_progress': return (
        <div className="w-5 h-5 rounded-full bg-primary-500/20 border-2 border-primary-500 flex items-center justify-center">
          <div className="w-2 h-2 bg-primary-500 rounded-full animate-pulse"></div>
        </div>
      )
      case 'done': return <CheckSquare className="w-5 h-5 text-green-400" />
      default: return <Circle className="w-5 h-5" />
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="animate-spin w-12 h-12 border-4 border-primary-500 border-t-transparent rounded-full"></div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">
            <span className="text-gradient">Task Board</span>
          </h1>
          <p className="text-gray-500 mt-1">Gestione attività e progetti stile Kanban</p>
        </div>
        <motion.button
          whileHover={{ scale: 1.02 }}
          whileTap={{ scale: 0.98 }}
          onClick={() => {
            if (taskLists.length > 0) {
              setEditingTask(null)
              setSelectedTaskListId(taskLists[0].id)
              setIsNewTaskModalOpen(true)
            } else {
              warning('Crea prima una board!')
            }
          }}
          className="glass-button-primary"
        >
          <Plus className="w-5 h-5 mr-2" />
          Nuovo Task
        </motion.button>
      </div>

      {/* Boards Tabs */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="glass-card p-4"
      >
        <div className="flex flex-wrap gap-3">
          {boards.map((board) => (
            renamingBoardId === board.id ? (
              <input
                key={board.id}
                autoFocus
                value={renameBoardName}
                onChange={(e) => setRenameBoardName(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') handleRenameBoard(board.id, renameBoardName)
                  if (e.key === 'Escape') setRenamingBoardId(null)
                }}
                onBlur={() => handleRenameBoard(board.id, renameBoardName)}
                className={`px-6 py-3 rounded-lg font-medium bg-gradient-to-r ${board.color} text-white outline-none ring-2 ring-white/50 w-48`}
              />
            ) : (
              <motion.button
                key={board.id}
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => setSelectedBoard(board.id)}
                onDoubleClick={() => {
                  setRenamingBoardId(board.id)
                  setRenameBoardName(board.name)
                }}
                className={`px-6 py-3 rounded-lg font-medium transition-all whitespace-nowrap ${
                  selectedBoard === board.id
                    ? `bg-gradient-to-r ${board.color} text-white`
                    : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
                }`}
              >
                {board.name}
              </motion.button>
            )
          ))}
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={() => setIsNewBoardModalOpen(true)}
            className="px-6 py-3 rounded-lg font-medium text-gray-500 hover:text-gray-900 dark:hover:text-white border-2 border-dashed border-gray-200 hover:border-gray-300 transition-all whitespace-nowrap"
          >
            <Plus className="w-5 h-5 inline mr-2" />
            Nuova Board
          </motion.button>
          {selectedBoard && (
            <>
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => {
                  const board = boards.find(b => b.id === selectedBoard)
                  if (board) {
                    setRenamingBoardId(board.id)
                    setRenameBoardName(board.name)
                  }
                }}
                className="px-6 py-3 rounded-lg font-medium text-amber-400 hover:text-gray-900 dark:hover:text-white border-2 border-amber-700/50 hover:border-amber-600 transition-all whitespace-nowrap"
              >
                <Pencil className="w-5 h-5 inline mr-2" />
                Rinomina
              </motion.button>
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={openAssignUsersModal}
                className="px-6 py-3 rounded-lg font-medium text-primary-400 hover:text-gray-900 dark:hover:text-white border-2 border-primary-700/50 hover:border-primary-600 transition-all whitespace-nowrap"
              >
                <User className="w-5 h-5 inline mr-2" />
                Assegna Utenti
              </motion.button>
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => setIsDeleteBoardModalOpen(true)}
                className="px-6 py-3 rounded-lg font-medium text-red-400 hover:text-gray-900 dark:hover:text-white border-2 border-red-700/50 hover:border-red-600 transition-all whitespace-nowrap"
              >
                <Trash2 className="w-5 h-5 inline mr-2" />
                Elimina Board
              </motion.button>
            </>
          )}
        </div>
      </motion.div>

      {/* Filter Bar */}
      {currentBoard && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.15 }}
          className="glass-card p-4"
        >
          <div className="flex items-center justify-between mb-3">
            <button
              onClick={() => setShowFilters(!showFilters)}
              className={`flex items-center gap-2 text-sm font-medium transition-colors ${
                hasActiveFilters ? 'text-primary-400' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
              }`}
            >
              <Filter className="w-4 h-4" />
              Filtri
              {hasActiveFilters && (
                <span className="bg-primary-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                  Attivi
                </span>
              )}
            </button>
            {hasActiveFilters && (
              <button
                onClick={resetFilters}
                className="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
              >
                <RotateCcw className="w-3 h-3" />
                Reset
              </button>
            )}
          </div>

          <AnimatePresence>
            {showFilters && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2 }}
                className="overflow-hidden"
              >
                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                  <div>
                    <label className="block text-xs text-gray-500 mb-1">Assegnatario</label>
                    <select
                      value={filters.assigned_to}
                      onChange={(e) => setFilters({ ...filters, assigned_to: e.target.value })}
                      className="glass-input w-full text-sm [&>option]:text-gray-900 [&>option]:bg-white"
                    >
                      <option value="">Tutti</option>
                      <option value="Lorenzo Moschella">Lorenzo Moschella</option>
                      <option value="Matteo Curti">Matteo Curti</option>
                      <option value="Federico Binatomy">Federico Binatomy</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs text-gray-500 mb-1">Priorità</label>
                    <select
                      value={filters.priority}
                      onChange={(e) => setFilters({ ...filters, priority: e.target.value })}
                      className="glass-input w-full text-sm [&>option]:text-gray-900 [&>option]:bg-white"
                    >
                      <option value="">Tutte</option>
                      <option value="high">Alta</option>
                      <option value="medium">Media</option>
                      <option value="low">Bassa</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs text-gray-500 mb-1">Scadenza da</label>
                    <input
                      type="date"
                      value={filters.due_date_from}
                      onChange={(e) => setFilters({ ...filters, due_date_from: e.target.value })}
                      className="glass-input w-full text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-xs text-gray-500 mb-1">Scadenza a</label>
                    <input
                      type="date"
                      value={filters.due_date_to}
                      onChange={(e) => setFilters({ ...filters, due_date_to: e.target.value })}
                      className="glass-input w-full text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-xs text-gray-500 mb-1">Tag</label>
                    <select
                      value={filters.tag}
                      onChange={(e) => setFilters({ ...filters, tag: e.target.value })}
                      className="glass-input w-full text-sm [&>option]:text-gray-900 [&>option]:bg-white"
                    >
                      <option value="">Tutti</option>
                      {DEPARTMENT_TAGS.map(tag => (
                        <option key={tag.name} value={tag.name}>{tag.name}</option>
                      ))}
                    </select>
                  </div>
                </div>
              </motion.div>
            )}
          </AnimatePresence>
        </motion.div>
      )}

      {/* Kanban Board */}
      {currentBoard && (
        <DndContext
          sensors={sensors}
          collisionDetection={closestCorners}
          onDragStart={handleDragStart}
          onDragEnd={handleDragEnd}
        >
        <div className="flex gap-6">
          {filteredTaskLists.map((list, index) => (
            <motion.div
              key={list.id}
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: 0.2 + index * 0.1 }}
              className="flex-1 space-y-4"
            >
              <div className="glass-card p-4">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-2">
                    {getListIcon(list.status_type)}
                    <h3 className="font-semibold">{list.name}</h3>
                    <span className="glass-badge">{list.tasks?.length || 0}</span>
                  </div>
                  <button 
                    onClick={() => {
                      setEditingTask(null)
                      setSelectedTaskListId(list.id)
                      setIsNewTaskModalOpen(true)
                    }}
                    className="text-gray-500 hover:text-gray-900 dark:hover:text-white"
                  >
                    <Plus className="w-5 h-5" />
                  </button>
                </div>
                
                {/* Tasks */}
                <DroppableColumn list={list}>
                  {list.tasks && list.tasks.length > 0 ? (
                    list.tasks.map((task) => (
                      <DraggableTask
                        key={task.id}
                        task={task}
                        onEdit={handleEditTask}
                        onDelete={handleDeleteTask}
                      />
                    ))
                  ) : (
                    <div className="text-center py-8 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                      <div className="flex flex-col items-center justify-center">
                        {getListIcon(list.status_type)}
                        <p className="text-sm mt-2">Nessun task</p>
                      </div>
                    </div>
                  )}
                </DroppableColumn>
              </div>
            </motion.div>
          ))}
        </div>
        </DndContext>
      )}

      {boards.length === 0 && (
        <div className="text-center py-16">
          <h3 className="text-xl font-semibold text-gray-500 mb-4">Nessuna Board Trovata</h3>
          <p className="text-gray-400 mb-6">Crea la tua prima board per iniziare!</p>
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => setIsNewBoardModalOpen(true)}
            className="glass-button-primary"
          >
            <Plus className="w-5 h-5 mr-2 inline" />
            Crea Prima Board
          </motion.button>
        </div>
      )}

      {/* New Board Modal */}
      <Modal
        isOpen={isNewBoardModalOpen}
        onClose={() => setIsNewBoardModalOpen(false)}
        title="Nuova Board"
      >
        <form onSubmit={handleCreateBoard} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">Nome Board *</label>
            <input
              type="text"
              required
              value={boardForm.name}
              onChange={(e) => setBoardForm({ ...boardForm, name: e.target.value })}
              className="glass-input w-full"
              placeholder="Es: Sviluppo, Marketing..."
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Colore</label>
            <div className="grid grid-cols-3 gap-2">
              {colorOptions.map((color) => (
                <button
                  key={color}
                  type="button"
                  onClick={() => setBoardForm({ ...boardForm, color })}
                  className={`h-12 rounded-lg bg-gradient-to-r ${color} ${
                    boardForm.color === color ? 'ring-2 ring-white ring-offset-2 ring-offset-slate-900' : ''
                  }`}
                />
              ))}
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Descrizione</label>
            <textarea
              value={boardForm.description}
              onChange={(e) => setBoardForm({ ...boardForm, description: e.target.value })}
              className="glass-input w-full"
              rows={3}
              placeholder="Descrizione opzionale..."
            />
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={() => setIsNewBoardModalOpen(false)}
              className="glass-button flex-1"
            >
              Annulla
            </button>
            <button
              type="submit"
              className="glass-button-primary flex-1"
            >
              <Save className="w-4 h-4 mr-2 inline" />
              Crea Board
            </button>
          </div>
        </form>
      </Modal>

      {/* Delete Board Confirmation Modal */}
      <Modal
        isOpen={isDeleteBoardModalOpen}
        onClose={() => setIsDeleteBoardModalOpen(false)}
        title="Elimina Board"
      >
        <div className="space-y-4">
          <div className="p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
            <p className="text-gray-600">
              Sei sicuro di voler eliminare la board <span className="font-semibold text-gray-900 dark:text-white">{currentBoard?.name}</span>?
            </p>
            <p className="text-gray-500 text-sm mt-2">
              ⚠️ Questa azione eliminerà anche tutti i task contenuti nella board e non può essere annullata.
            </p>
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={() => setIsDeleteBoardModalOpen(false)}
              className="glass-button flex-1"
            >
              Annulla
            </button>
            <button
              type="button"
              onClick={handleDeleteBoard}
              className="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-all"
            >
              <Trash2 className="w-4 h-4 mr-2 inline" />
              Elimina Board
            </button>
          </div>
        </div>
      </Modal>

      {/* New/Edit Task Modal */}
      <Modal
        isOpen={isNewTaskModalOpen}
        onClose={() => {
          setIsNewTaskModalOpen(false)
          setEditingTask(null)
          setTaskForm({
            title: '',
            description: '',
            priority: 'medium',
            assigned_to: '',
            due_date: '',
            tags: []
          })
        }}
        title={editingTask ? 'Modifica Task' : 'Nuovo Task'}
      >
        <form onSubmit={editingTask ? handleSaveEdit : handleCreateTask} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">Titolo *</label>
            <input
              type="text"
              required
              value={taskForm.title}
              onChange={(e) => setTaskForm({ ...taskForm, title: e.target.value })}
              className="glass-input w-full"
              placeholder="Titolo del task..."
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Descrizione</label>
            <textarea
              value={taskForm.description}
              onChange={(e) => setTaskForm({ ...taskForm, description: e.target.value })}
              className="glass-input w-full"
              rows={3}
              placeholder="Descrizione dettagliata..."
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Priorità</label>
            <select
              value={taskForm.priority}
              onChange={(e) => setTaskForm({ ...taskForm, priority: e.target.value as any })}
              className="glass-input w-full [&>option]:text-gray-900 [&>option]:bg-white"
            >
              <option value="low">Bassa</option>
              <option value="medium">Media</option>
              <option value="high">Alta</option>
            </select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-2">Assegnato a</label>
              <select
                value={taskForm.assigned_to}
                onChange={(e) => setTaskForm({ ...taskForm, assigned_to: e.target.value })}
                className="glass-input w-full [&>option]:text-gray-900 [&>option]:bg-white"
              >
                <option value="">Seleziona...</option>
                <option value="Lorenzo Moschella">Lorenzo Moschella</option>
                <option value="Matteo Curti">Matteo Curti</option>
                <option value="Federico Binatomy">Federico Binatomy</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium mb-2">Scadenza</label>
              <input
                type="date"
                value={taskForm.due_date}
                onChange={(e) => setTaskForm({ ...taskForm, due_date: e.target.value })}
                className="glass-input w-full"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">Tag Dipartimento</label>
            <div className="flex flex-wrap gap-2">
              {DEPARTMENT_TAGS.map((tag) => {
                const isSelected = taskForm.tags.includes(tag.name)
                return (
                  <button
                    key={tag.name}
                    type="button"
                    onClick={() => {
                      setTaskForm(prev => ({
                        ...prev,
                        tags: isSelected
                          ? prev.tags.filter(t => t !== tag.name)
                          : [...prev.tags, tag.name]
                      }))
                    }}
                    className={`text-xs px-3 py-1.5 rounded border transition-all ${
                      isSelected
                        ? `${tag.color} ring-1 ring-white/30`
                        : 'bg-gray-50/50 text-gray-400 border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <Tag className="w-3 h-3 inline mr-1" />
                    {tag.name}
                  </button>
                )
              })}
            </div>
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={() => {
                setIsNewTaskModalOpen(false)
                setEditingTask(null)
                setTaskForm({
                  title: '',
                  description: '',
                  priority: 'medium',
                  assigned_to: '',
                  due_date: '',
                  tags: []
                })
              }}
              className="glass-button flex-1"
            >
              Annulla
            </button>
            <button
              type="submit"
              className="glass-button-primary flex-1"
            >
              <Save className="w-4 h-4 mr-2 inline" />
              {editingTask ? 'Salva Modifiche' : 'Crea Task'}
            </button>
          </div>
        </form>
      </Modal>

      {/* Modal Assegnazione Utenti */}
      <Modal
        isOpen={isAssignUsersModalOpen}
        onClose={() => setIsAssignUsersModalOpen(false)}
        title="Assegna Utenti alla Board"
      >
        <div className="space-y-4">
          <p className="text-gray-500 text-sm">
            Seleziona gli utenti che possono visualizzare questa board
          </p>
          
          <div className="space-y-2 max-h-96 overflow-y-auto">
            {allUsers.map(user => (
              <label
                key={user.id}
                className="flex items-center gap-3 p-3 glass-card hover:bg-gray-50/50 cursor-pointer transition-colors"
              >
                <input
                  type="checkbox"
                  checked={selectedUserIds.includes(user.id)}
                  onChange={(e) => {
                    if (e.target.checked) {
                      setSelectedUserIds([...selectedUserIds, user.id])
                    } else {
                      setSelectedUserIds(selectedUserIds.filter(id => id !== user.id))
                    }
                  }}
                  className="w-4 h-4 rounded border-gray-300 bg-gray-50 text-primary-500 focus:ring-primary-500 focus:ring-offset-slate-900"
                />
                <div className="flex-1">
                  <div className="text-gray-900 dark:text-white font-medium">{user.name}</div>
                  <div className="text-gray-500 text-sm">{user.email}</div>
                </div>
              </label>
            ))}
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={() => setIsAssignUsersModalOpen(false)}
              className="glass-button flex-1"
            >
              Annulla
            </button>
            <button
              type="button"
              onClick={handleAssignUsers}
              disabled={isAssigningUsers}
              className="glass-button-primary flex-1"
            >
              <User className="w-4 h-4 mr-2 inline" />
              {isAssigningUsers ? 'Assegnazione...' : 'Assegna Utenti'}
            </button>
          </div>
        </div>
      </Modal>

      {/* Toast Notifications */}
      <ToastContainer toasts={toasts} onClose={removeToast} />

      {/* Loading Overlays */}
      <AnimatePresence>
        {isCreatingBoard && (
          <LoadingOverlay message="Creazione board in corso..." />
        )}
        {isCreatingTask && (
          <LoadingOverlay message={editingTask ? "Salvataggio modifiche..." : "Creazione task in corso..."} />
        )}
        {isDeletingBoard && (
          <LoadingOverlay message="Eliminazione board in corso..." />
        )}
        {isDeletingTask && (
          <LoadingOverlay message="Eliminazione task in corso..." />
        )}
        {isDraggingUpdate && (
          <LoadingOverlay message="Spostamento task in corso..." />
        )}
      </AnimatePresence>
    </div>
  )
}
