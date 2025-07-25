import { create } from "zustand"
import { persist } from "zustand/middleware"

interface UserState {
  isWechatVerified: boolean
  isOnline: boolean
  balance: number
  todayEarnings: number
  totalEarnings: number
  completedTasks: number[]
  wechatStatus: {
    lastOnline: string | null
    onlineDuration: number // 在线时长（分钟）
  }
  setWechatVerified: (status: boolean) => void
  setOnline: (status: boolean) => void
  updateBalance: (amount: number) => void
  completeTask: (taskId: number) => void
  updateWechatStatus: (time: string) => void
}

export const useUserStore = create<UserState>()(
  persist(
    (set) => ({
      isWechatVerified: false,
      isOnline: false,
      balance: 0,
      todayEarnings: 0,
      totalEarnings: 0,
      completedTasks: [],
      wechatStatus: {
        lastOnline: null,
        onlineDuration: 0,
      },
      setWechatVerified: (status) => set({ isWechatVerified: status }),
      setOnline: (status) => set({ isOnline: status }),
      updateBalance: (amount) =>
        set((state) => ({
          balance: state.balance + amount,
          todayEarnings: state.todayEarnings + amount,
          totalEarnings: state.totalEarnings + amount,
        })),
      completeTask: (taskId) =>
        set((state) => ({
          completedTasks: [...state.completedTasks, taskId],
        })),
      updateWechatStatus: (time) =>
        set((state) => ({
          wechatStatus: {
            ...state.wechatStatus,
            lastOnline: time,
          },
        })),
    }),
    {
      name: "user-storage",
    },
  ),
)

