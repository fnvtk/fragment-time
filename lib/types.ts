export type TaskStatus = "locked" | "unlocked" | "completed"

export interface Task {
  id: number
  title: string
  type: "wechat" | "tiktok" | "other"
  icon: string
  description: string
  reward?: string
  status: TaskStatus
  requiredTasks?: number[]
}

export interface User {
  id: string
  name: string
  avatar: string
  completedTasks: number[]
  balance: number
  todayEarnings: number
  totalEarnings: number
}

