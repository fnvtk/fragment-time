"use client"

import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { ImagePlus } from "lucide-react"

export default function SettingsPage() {
  return (
    <div className="p-4 lg:p-6">
      <div className="mb-6">
        <h1 className="text-xl font-bold lg:text-2xl">系统设置</h1>
        <p className="mt-1 text-sm text-muted-foreground">管理小程序和系统相关配置</p>
      </div>

      <Tabs defaultValue="basic" className="space-y-4">
        <TabsList>
          <TabsTrigger value="basic">基础设置</TabsTrigger>
          <TabsTrigger value="miniapp">小程序配置</TabsTrigger>
          <TabsTrigger value="payment">支付设置</TabsTrigger>
          <TabsTrigger value="notice">通知设置</TabsTrigger>
        </TabsList>

        <TabsContent value="basic">
          <Card className="p-6">
            <div className="space-y-6">
              <div className="grid gap-2">
                <Label htmlFor="siteName">平台名称</Label>
                <Input id="siteName" defaultValue="碎片时间" />
              </div>

              <div className="grid gap-2">
                <Label>平台Logo</Label>
                <div className="flex h-32 cursor-pointer items-center justify-center rounded-lg border border-dashed">
                  <div className="text-center">
                    <ImagePlus className="mx-auto h-8 w-8 text-muted-foreground" />
                    <span className="mt-2 text-sm text-muted-foreground">点击或拖拽上传图片</span>
                  </div>
                </div>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="contact">客服电话</Label>
                <Input id="contact" type="tel" />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label>维护模式</Label>
                  <div className="text-sm text-muted-foreground">开启后用户将无法访问平台</div>
                </div>
                <Switch />
              </div>

              <Button>保存更改</Button>
            </div>
          </Card>
        </TabsContent>

        <TabsContent value="miniapp">
          <Card className="p-6">
            <div className="space-y-6">
              <div className="grid gap-2">
                <Label htmlFor="appId">AppID</Label>
                <Input id="appId" />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="appSecret">AppSecret</Label>
                <Input id="appSecret" type="password" />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label>显示任务金额</Label>
                  <div className="text-sm text-muted-foreground">是否在小程序中显示任务奖励金额</div>
                </div>
                <Switch defaultChecked />
              </div>

              <Button>保存更改</Button>
            </div>
          </Card>
        </TabsContent>

        {/* 其他标签页内容 */}
      </Tabs>
    </div>
  )
}

