import { Card } from "@/components/ui/card"
import { Users, ClipboardList, Package, FileSpreadsheet, TrendingUp, DollarSign } from "lucide-react"

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

export default function AdminDashboard() {
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
                <p className={`text-sm mt-1 ${stat.trend === "up" ? "text-green-500" : "text-red-500"}`}>
                  {stat.change} vs 昨日
                </p>
              </div>
              {stat.icon}
            </div>
          </Card>
        ))}
      </div>
    </div>
  )
}

