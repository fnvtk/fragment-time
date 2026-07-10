"use client"

import { useState } from "react"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Switch } from "@/components/ui/switch"
import { ImagePlus } from "lucide-react"

export default function NewTaskPage() {
  const [step, setStep] = useState(1)

  return (
    <div className="p-6">
      <Card className="p-6">
        <div className="mb-6">
          <h1 className="text-2xl font-bold">创建任务</h1>
          <div className="mt-4 flex items-center gap-4">
            <div className="flex items-center gap-2">
              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-white">1</div>
              <span className="font-medium">基础设置</span>
            </div>
            <div className="h-px flex-1 bg-border" />
            <div className="flex items-center gap-2">
              <div
                className={`flex h-8 w-8 items-center justify-center rounded-full ${
                  step >= 2 ? "bg-primary text-white" : "bg-muted text-muted-foreground"
                }`}
              >
                2
              </div>
              <span className={step >= 2 ? "font-medium" : "text-muted-foreground"}>其他设置</span>
            </div>
            <div className="h-px flex-1 bg-border" />
            <div className="flex items-center gap-2">
              <div
                className={`flex h-8 w-8 items-center justify-center rounded-full ${
                  step >= 3 ? "bg-primary text-white" : "bg-muted text-muted-foreground"
                }`}
              >
                3
              </div>
              <span className={step >= 3 ? "font-medium" : "text-muted-foreground"}>任务步骤设置</span>
            </div>
          </div>
        </div>

        {step === 1 && (
          <div className="space-y-6">
            <div className="grid gap-2">
              <Label htmlFor="taskName">任务名称</Label>
              <Input id="taskName" placeholder="请输入任务名称" />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="taskDesc">任务简介</Label>
              <Textarea id="taskDesc" placeholder="请输入任务简介" />
            </div>

            <div className="grid gap-2">
              <Label>任务封面</Label>
              <div className="flex h-32 cursor-pointer items-center justify-center rounded-lg border border-dashed">
                <div className="text-center">
                  <ImagePlus className="mx-auto h-8 w-8 text-muted-foreground" />
                  <span className="mt-2 text-sm text-muted-foreground">点击或拖拽上传图片</span>
                </div>
              </div>
            </div>

            <div className="grid gap-2">
              <Label>任务奖励</Label>
              <div className="flex items-center gap-2">
                <Input type="number" placeholder="0.00" />
                <span className="text-muted-foreground">元</span>
              </div>
            </div>

            <div className="grid gap-2">
              <Label>结束时间</Label>
              <Input type="date" />
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Switch id="recycle" />
                <Label htmlFor="recycle">回收超时任务</Label>
              </div>
              <Button onClick={() => setStep(2)}>下一步</Button>
            </div>
          </div>
        )}

        {step === 2 && (
          <div className="space-y-6">
            <div className="grid gap-2">
              <Label>任务领取限制</Label>
              <Select defaultValue="once">
                <SelectTrigger>
                  <SelectValue placeholder="请选择领取限制" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="once">一天单次</SelectItem>
                  <SelectItem value="multiple">一天多次</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="grid gap-2">
              <Label>领取次数</Label>
              <div className="flex items-center gap-2">
                <Button variant="outline" size="icon">
                  -
                </Button>
                <Input type="number" className="w-20 text-center" value="1" />
                <Button variant="outline" size="icon">
                  +
                </Button>
              </div>
            </div>

            <div className="grid gap-2">
              <Label>任务时长（分钟）</Label>
              <div className="flex items-center gap-2">
                <Button variant="outline" size="icon">
                  -
                </Button>
                <Input type="number" className="w-20 text-center" value="10" />
                <Button variant="outline" size="icon">
                  +
                </Button>
              </div>
            </div>

            <div className="flex items-center justify-between">
              <Button variant="outline" onClick={() => setStep(1)}>
                上一步
              </Button>
              <Button onClick={() => setStep(3)}>下一步</Button>
            </div>
          </div>
        )}

        {step === 3 && (
          <div className="space-y-6">
            <div className="grid gap-2">
              <Label>任务步骤</Label>
              <div className="space-y-4">
                <div className="rounded-lg border p-4">
                  <div className="mb-2 flex items-center justify-between">
                    <span className="font-medium">步骤 1</span>
                    <Button variant="ghost" size="sm">
                      删除
                    </Button>
                  </div>
                  <Textarea placeholder="请输入步骤说明" className="mb-2" />
                  <div className="flex h-32 cursor-pointer items-center justify-center rounded-lg border border-dashed">
                    <div className="text-center">
                      <ImagePlus className="mx-auto h-8 w-8 text-muted-foreground" />
                      <span className="mt-2 text-sm text-muted-foreground">上传步骤示例图</span>
                    </div>
                  </div>
                </div>
              </div>
              <Button variant="outline" className="mt-2">
                添加步骤
              </Button>
            </div>

            <div className="flex items-center justify-between">
              <Button variant="outline" onClick={() => setStep(2)}>
                上一步
              </Button>
              <Button>提交</Button>
            </div>
          </div>
        )}
      </Card>
    </div>
  )
}

