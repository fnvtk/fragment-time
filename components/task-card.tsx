import { Card } from "@/components/ui/card"
import { Avatar } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Lock } from "lucide-react"
import Image from "next/image"
import Link from "next/link"
import type { Task } from "@/lib/types"

interface TaskCardProps {
  task: Task
}

export function TaskCard({ task }: TaskCardProps) {
  const isLocked = task.status === "locked"

  return (
    <Card className="flex items-center justify-between p-4">
      <div className="flex items-center gap-4">
        <Avatar className="h-12 w-12">
          <Image src={task.icon || "/placeholder.svg"} alt={task.title} width={48} height={48} />
        </Avatar>
        <div>
          <h3 className="font-medium">{task.title}</h3>
          <p className="text-sm text-muted-foreground">{task.description}</p>
        </div>
      </div>
      <div className="flex items-center gap-2">
        {task.reward && <span className="text-sm font-medium text-red-500">+{task.reward}</span>}
        {isLocked ? (
          <Button size="sm" variant="outline" className="gap-2">
            <Lock className="h-4 w-4" />
            <span>未解锁</span>
          </Button>
        ) : (
          <Button asChild size="sm" className="bg-primary text-white">
            <Link href={`/tasks/${task.id}`}>领取</Link>
          </Button>
        )}
      </div>
    </Card>
  )
}

