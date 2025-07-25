import { create } from "zustand"
import { persist } from "zustand/middleware"

interface TaskStore {
  completedTasks: number[]
  currentTask: number | null
  taskProgress: Record<number, number> // taskId -> progress (0-100)
  addCompletedTask: (taskId: number) => void
  setCurrentTask: (taskId: number | null) => void
  updateTaskProgress: (taskId: number, progress: number) => void
}

export const useTaskStore = create<TaskStore>()(
  persist(
    (set) => ({
      completedTasks: [],
      currentTask: null,
      taskProgress: {},
      addCompletedTask: (taskId) =>
        set((state) => ({
          completedTasks: [...state.completedTasks, taskId],
        })),
      setCurrentTask: (taskId) =>
        set(() => ({
          currentTask: taskId,
        })),
      updateTaskProgress: (taskId, progress) =>
        set((state) => ({
          taskProgress: {
            ...state.taskProgress,
            [taskId]: progress,
          },
        })),
    }),
    {
      name: "task-storage",
    },
  ),
)

