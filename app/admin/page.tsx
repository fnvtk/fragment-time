import { Card } from "@/components/ui/card"
import { Users, ClipboardList, Package, CheckCircle2, WalletCards, ReceiptText } from "lucide-react"
import { getDashboardStats } from "@/lib/server/fragment-time"

export const dynamic = "force-dynamic"

export default async function AdminDashboard() {
  const data = await getDashboardStats()
  const stats = [
    { title: "用户总数", value: data.users.toLocaleString(), note: "碎片时间真实用户", icon: <Users className="h-6 w-6 text-blue-500" /> },
    { title: "任务总数", value: data.tasks.toLocaleString(), note: `${data.activeTasks} 条正在展示`, icon: <ClipboardList className="h-6 w-6 text-green-500" /> },
    { title: "数据包", value: data.packages.toLocaleString(), note: "可分配任务数据", icon: <Package className="h-6 w-6 text-orange-500" /> },
    { title: "任务领取", value: data.receives.toLocaleString(), note: `${data.completed} 条已完成`, icon: <CheckCircle2 className="h-6 w-6 text-teal-500" /> },
    { title: "累计收益", value: `¥${data.income.toFixed(2)}`, note: "用户账单累计", icon: <ReceiptText className="h-6 w-6 text-rose-500" /> },
    { title: "提现记录", value: data.withdrawals.toLocaleString(), note: `累计 ¥${data.withdrawalAmount.toFixed(2)}`, icon: <WalletCards className="h-6 w-6 text-amber-500" /> },
  ]
  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold">控制台</h1>
        <p className="text-muted-foreground">欢迎回来，管理员</p>
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {stats.map((stat) => (
          <Card key={stat.title} className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">{stat.title}</p>
                <h3 className="text-2xl font-bold mt-1">{stat.value}</h3>
                <p className="mt-1 text-sm text-muted-foreground">{stat.note}</p>
              </div>
              {stat.icon}
            </div>
          </Card>
        ))}
      </div>
    </div>
  )
}
