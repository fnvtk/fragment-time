"use client"

import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Users, ClipboardList, Package, FileSpreadsheet, DollarSign, TrendingUp } from "lucide-react"

const stats = [
  {
    title: "总用户数",
    value: "42,645",
    icon: <Users className="h-6 w-6 text-blue-500" />,
    change: "+2.5%",
    trend: "up",
  },
  {
    title: "今日任务",
    value: "1,234",
    icon: <ClipboardList className="h-6 w-6 text-green-500" />,
    change: "+12.3%",
    trend: "up",
  },
  {
    title: "数据包总数",
    value: "36",
    icon: <Package className="h-6 w-6 text-purple-500" />,
    change: "+1",
    trend: "up",
  },
  {
    title: "粉丝总数",
    value: "9,447",
    icon: <FileSpreadsheet className="h-6 w-6 text-orange-500" />,
    change: "+5.7%",
    trend: "up",
  },
  {
    title: "今日收入",
    value: "¥3,456.78",
    icon: <DollarSign className="h-6 w-6 text-red-500" />,
    change: "+8.2%",
    trend: "up",
  },
  {
    title: "转化率",
    value: "24.5%",
    icon: <TrendingUp className="h-6 w-6 text-indigo-500" />,
    change: "+1.2%",
    trend: "up",
  },
]

export default function StatsPage() {
  return (
    <div className="p-4 lg:p-6">
      <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 className="text-xl font-bold lg:text-2xl">统计报表</h1>
          <p className="mt-1 text-sm text-muted-foreground">查看平台运营数据和关键指标</p>
        </div>
        <div className="flex items-center gap-4">
          <Select defaultValue="today">
            <SelectTrigger className="w-[160px]">
              <SelectValue placeholder="选择时间范围" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="today">今日</SelectItem>
              <SelectItem value="yesterday">昨日</SelectItem>
              <SelectItem value="week">近7天</SelectItem>
              <SelectItem value="month">近30天</SelectItem>
            </SelectContent>
          </Select>
          <Button>导出报表</Button>
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        {stats.map((stat) => (
          <Card key={stat.title} className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">{stat.title}</p>
                <h3 className="mt-1 text-2xl font-bold">{stat.value}</h3>
                <p className={`mt-1 text-sm ${stat.trend === "up" ? "text-green-500" : "text-red-500"}`}>
                  {stat.change} vs 昨日
                </p>
              </div>
              {stat.icon}
            </div>
          </Card>
        ))}
      </div>

      <div className="mt-6 grid gap-4 lg:grid-cols-2">
        <Card className="p-6">
          <h3 className="mb-4 text-lg font-semibold">收入趋势</h3>
          <div className="h-[300px] rounded-lg bg-muted/20">{/* 这里可以添加图表组件 */}</div>
        </Card>
        <Card className="p-6">
          <h3 className="mb-4 text-lg font-semibold">任务完成情况</h3>
          <div className="h-[300px] rounded-lg bg-muted/20">{/* 这里可以添加图表组件 */}</div>
        </Card>
      </div>
    </div>
  )
}

