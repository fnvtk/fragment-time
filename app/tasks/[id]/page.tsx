import { tasks } from "@/lib/tasks"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { WechatVerification } from "@/components/wechat-verification"
import { Badge } from "@/components/ui/badge"
import { Clock, AlertTriangle } from "lucide-react"
import Link from "next/link"

export default function TaskDetailPage({ params }: { params: { id: string } }) {
  const task = tasks.find((t) => t.id === Number.parseInt(params.id))

  if (!task) {
    return <div>任务不存在</div>
  }

  const isWechatTask = task.type === "wechat"

  return (
    <main className="min-h-screen bg-gray-50 pb-16">
      <header className="sticky top-0 z-10 flex items-center gap-2 bg-primary p-4 text-white">
        <Link href="/" className="rounded-full bg-white/10 p-2">
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
        <h1 className="text-lg font-medium">{task.title}</h1>
      </header>

      <div className="p-4 space-y-4">
        {isWechatTask && <WechatVerification />}

        <Card>
          <div className="border-b p-4">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-lg font-medium">任务信息</h2>
              <Badge variant="outline" className="gap-1">
                <Clock className="h-3 w-3" />
                {Math.floor(Number(task.duration) / 60)}小时
              </Badge>
            </div>
            <div className="space-y-2 text-sm text-muted-foreground">
              <p>任务数量：{task.quantity}</p>
              <p>剩余数量：{task.remaining}</p>
              <p>截止领取：{task.deadline}</p>
              <p>预计收益：{task.reward}元</p>
            </div>
          </div>

          <div className="p-4">
            <h3 className="mb-2 font-medium">任务步骤</h3>
            <div className="space-y-4">
              {task.steps.map((step, index) => (
                <div key={index} className="flex gap-4">
                  <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-white text-sm">
                    {index + 1}
                  </div>
                  <p className="text-sm text-muted-foreground">{step}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="border-t p-4">
            <h3 className="mb-2 font-medium flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-yellow-500" />
              注意事项
            </h3>
            <div className="space-y-2">
              {task.notice.map((item, index) => (
                <p key={index} className="text-sm text-muted-foreground">
                  {index + 1}. {item}
                </p>
              ))}
            </div>
          </div>
        </Card>

        <Button className="w-full" size="lg">
          立即领取
        </Button>
      </div>
    </main>
  )
}

