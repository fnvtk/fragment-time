import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Avatar } from "@/components/ui/avatar"
import Link from "next/link"
import Image from "next/image"

const tasks = [
  {
    id: 1,
    title: "个微添加任务-京东",
    description: "使用个人vx号进行添...",
    reward: "+0.90",
    icon: "/wechat-icon.png",
    status: "待提交",
    time: "2025-02-16 17:05:05",
  },
  {
    id: 2,
    title: "抖音点赞",
    description: "用抖音扫码用户二维...",
    icon: "/tiktok-icon.png",
    status: "待提交",
    time: "2025-02-14 18:08:47",
  },
]

export default function MyTasksPage() {
  return (
    <main className="min-h-screen bg-gray-50">
      <header className="sticky top-0 z-10 flex items-center gap-2 bg-primary p-4 text-white">
        <Link href="/profile" className="rounded-full bg-white/10 p-2">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="m15 18-6-6 6-6" />
          </svg>
        </Link>
        <h1 className="text-lg font-medium">我的任务</h1>
      </header>

      <div className="space-y-4 p-4">
        {tasks.map((task) => (
          <Card key={task.id} className="flex items-center justify-between p-4">
            <div className="flex items-center gap-3">
              <Avatar className="h-12 w-12">
                <Image src={task.icon || "/placeholder.svg"} alt={task.title} width={48} height={48} />
              </Avatar>
              <div>
                <h3 className="font-medium">{task.title}</h3>
                <p className="text-sm text-muted-foreground">{task.description}</p>
                <p className="text-xs text-muted-foreground">领取时间：{task.time}</p>
              </div>
            </div>
            <div className="text-right">
              {task.reward && <div className="text-red-500">{task.reward}</div>}
              <Button variant="secondary" size="sm">
                {task.status}
              </Button>
            </div>
          </Card>
        ))}
      </div>
    </main>
  )
}

