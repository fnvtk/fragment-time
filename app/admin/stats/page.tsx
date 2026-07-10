import { Card } from "@/components/ui/card"
import { getDashboardStats } from "@/lib/server/fragment-time"

export const dynamic = "force-dynamic"

export default async function StatsPage() {
  const data = await getDashboardStats()
  const stats = [
    ["总用户数", data.users.toLocaleString()], ["任务总数", data.tasks.toLocaleString()],
    ["展示中任务", data.activeTasks.toLocaleString()], ["数据包", data.packages.toLocaleString()],
    ["任务领取", data.receives.toLocaleString()], ["已完成领取", data.completed.toLocaleString()],
    ["累计收益", `¥${data.income.toFixed(2)}`], ["提现记录", data.withdrawals.toLocaleString()],
    ["提现金额", `¥${data.withdrawalAmount.toFixed(2)}`],
  ]
  return (
    <div className="p-4 lg:p-6">
      <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 className="text-xl font-bold lg:text-2xl">统计报表</h1>
          <p className="mt-1 text-sm text-muted-foreground">查看平台运营数据和关键指标</p>
        </div>
        <p className="text-sm text-muted-foreground">统计口径与小程序 appId=1 实时同步</p>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        {stats.map(([title, value]) => (
          <Card key={title} className="p-6"><p className="text-sm text-muted-foreground">{title}</p><h3 className="mt-1 text-2xl font-bold">{value}</h3><p className="mt-1 text-sm text-muted-foreground">实时数据</p></Card>
        ))}
      </div>

    </div>
  )
}
