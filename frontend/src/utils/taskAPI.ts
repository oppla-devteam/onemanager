// Task CRUD API Wrapper

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export interface Task {
  id?: number
  task_list_id: number
  title: string
  description?: string
  status?: 'todo' | 'in_progress' | 'done'
  priority?: 'low' | 'medium' | 'high'
  assigned_to?: string
  due_date?: string
  tags?: string[]
  position?: number
}

export interface TaskList {
  id: number
  name: string
  status_type: 'todo' | 'in_progress' | 'done'
}

export interface Board {
  id: number
  name: string
  task_lists?: TaskList[]
}

// Get all boards
export async function getBoards(): Promise<Board[]> {
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(`${API_URL}/task-boards`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })
    if (!response.ok) throw new Error('Failed to fetch boards')
    return await response.json()
  } catch (error) {
    console.error('Error fetching boards:', error)
    throw error
  }
}

// Get tasks from a specific board
export async function getTasks(boardId: number): Promise<Task[]> {
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(`${API_URL}/task-boards/${boardId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })
    if (!response.ok) throw new Error('Failed to fetch board details')
    const board = await response.json()
    
    // Extract all tasks from all lists
    const allTasks: Task[] = []
    board.task_lists?.forEach((list: TaskList & { tasks?: Task[] }) => {
      if (list.tasks) {
        allTasks.push(...list.tasks)
      }
    })
    
    return allTasks
  } catch (error) {
    console.error('Error fetching tasks:', error)
    throw error
  }
}

// Create a new task
export async function createTask(taskData: Task): Promise<Task> {
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(`${API_URL}/tasks`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(taskData)
    })
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to create task')
    }
    return await response.json()
  } catch (error) {
    console.error('Error creating task:', error)
    throw error
  }
}

// Update a task
export async function updateTask(taskId: number, updates: Partial<Task>): Promise<Task> {
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
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to update task')
    }
    return await response.json()
  } catch (error) {
    console.error('Error updating task:', error)
    throw error
  }
}

// Delete a task
export async function deleteTask(taskId: number): Promise<void> {
  try {
    const token = localStorage.getItem('token')
    const response = await fetch(`${API_URL}/tasks/${taskId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to delete task')
    }
  } catch (error) {
    console.error('Error deleting task:', error)
    throw error
  }
}

// Search tasks by title or description
export function searchTasks(tasks: Task[], query: string): Task[] {
  const lowerQuery = query.toLowerCase()
  return tasks.filter(task =>
    task.title.toLowerCase().includes(lowerQuery) ||
    (task.description && task.description.toLowerCase().includes(lowerQuery)) ||
    (task.assigned_to && task.assigned_to.toLowerCase().includes(lowerQuery))
  )
}

// Format tasks list for display
export function formatTasksList(tasks: Task[], limit: number = 10): string {
  if (tasks.length === 0) {
    return "❌ Nessun task trovato."
  }
  
  const displayTasks = tasks.slice(0, limit)
  let result = `📋 **Tasks trovati** (${tasks.length} totali):\n\n`
  
  displayTasks.forEach((task, index) => {
    const priorityIcon = task.priority === 'high' ? '🔴' : task.priority === 'medium' ? '🟡' : '🟢'
    const statusIcon = task.status === 'done' ? '' : task.status === 'in_progress' ? '⏳' : '⭕'
    
    result += `${index + 1}. ${statusIcon} **${task.title}**\n`
    if (task.description) {
      result += `   📝 ${task.description.substring(0, 60)}${task.description.length > 60 ? '...' : ''}\n`
    }
    result += `   ${priorityIcon} Priorità: ${task.priority || 'media'}\n`
    if (task.assigned_to) {
      result += `   👤 Assegnato a: ${task.assigned_to}\n`
    }
    if (task.due_date) {
      result += `   📅 Scadenza: ${new Date(task.due_date).toLocaleDateString('it-IT')}\n`
    }
    result += `   🆔 ID: ${task.id}\n\n`
  })
  
  if (tasks.length > limit) {
    result += `... e altri ${tasks.length - limit} tasks.\n`
  }
  
  return result
}

// Get task statistics
export function getTaskStats(tasks: Task[]): string {
  const total = tasks.length
  const todo = tasks.filter(t => t.status === 'todo').length
  const inProgress = tasks.filter(t => t.status === 'in_progress').length
  const done = tasks.filter(t => t.status === 'done').length
  const highPriority = tasks.filter(t => t.priority === 'high').length
  const overdue = tasks.filter(t => t.due_date && new Date(t.due_date) < new Date()).length
  
  return `📊 **Statistiche Tasks**\n\n` +
         `• **Totale tasks**: ${total}\n` +
         `• ⭕ **Da fare**: ${todo} (${Math.round(todo/total*100)}%)\n` +
         `• ⏳ **In corso**: ${inProgress} (${Math.round(inProgress/total*100)}%)\n` +
         `• **Completati**: ${done} (${Math.round(done/total*100)}%)\n` +
         `• 🔴 **Alta priorità**: ${highPriority}\n` +
         `• ⚠️ **Scaduti**: ${overdue}\n`
}
