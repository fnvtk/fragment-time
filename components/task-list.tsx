"use client"

import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Avatar } from "@/components/ui/avatar"
import Image from "next/image"
import Link from "next/link"
import { tasks } from "@/lib/tasks"

export function TaskList() {
  return (
    <div className="flex-1 space-y-4 p-4">
      <h2 className="text-lg font-semibold">可用任务</h2>
      <div className="space-y-4">
        {tasks.map((task) => (
          <Link key={task.id} href={`/tasks/${task.id}`}>
            <Card className="overflow-hidden hover:bg-gray-50 transition-all hover:shadow-md">
              <div className="flex items-center justify-between p-4">
                <div className="flex items-center gap-3">
                  <Avatar className="h-12 w-12 rounded-xl border-2 border-primary/10">
                    <Image
                      src={task.icon || "/placeholder.svg"}
                      alt={task.title}
                      width={48}
                      height={48}
                      className="p-2"
                    />
                  </Avatar>
                  <div>
                    <h3 className="font-medium">{task.title}</h3>
                    <p className="text-sm text-muted-foreground">{task.description}</p>
                  </div>
                </div>
                <div className="text-right">
                  <div className="text-red-500 font-medium">+{task.reward}</div>
                  <Button variant="secondary" size="sm" className="mt-1 px-6">
                    领取
                  </Button>
                </div>
              </div>
              <div className="border-t bg-muted/50 px-4 py-2">
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span>剩余: {task.remaining}</span>
                  <span>时长: {Math.floor(Number(task.duration) / 60)}小时</span>
                  <span>截止: {task.deadline}</span>
                </div>
              </div>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  )
}

