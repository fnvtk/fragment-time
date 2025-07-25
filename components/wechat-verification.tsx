"use client"

import { useState } from "react"
import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { useUserStore } from "@/lib/store/user-store"
import { CheckCircle2, Clock, Loader2 } from "lucide-react"
import Image from "next/image"

export function WechatVerification() {
  const [verifying, setVerifying] = useState(false)
  const { isWechatVerified, setWechatVerified, wechatStatus } = useUserStore()

  const handleVerification = async () => {
    setVerifying(true)
    // 模拟验证过程
    await new Promise((resolve) => setTimeout(resolve, 2000))
    setWechatVerified(true)
    setVerifying(false)
  }

  return (
    <Card className="p-4">
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-lg font-medium">微信验证状态</h3>
        {isWechatVerified ? (
          <span className="flex items-center gap-1 text-green-500">
            <CheckCircle2 className="h-4 w-4" />
            已验证
          </span>
        ) : (
          <span className="flex items-center gap-1 text-yellow-500">
            <Clock className="h-4 w-4" />
            待验证
          </span>
        )}
      </div>

      {!isWechatVerified ? (
        <>
          <div className="mb-4 rounded-lg border p-4">
            <Image src="/placeholder.svg" alt="微信二维码" width={200} height={200} className="mx-auto" />
            <p className="mt-2 text-center text-sm text-muted-foreground">请扫描二维码添加客服微信</p>
          </div>
          <Button className="w-full" disabled={verifying} onClick={handleVerification}>
            {verifying && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {verifying ? "验证中..." : "确认已添加"}
          </Button>
        </>
      ) : (
        <div className="space-y-2 text-sm">
          <p className="flex items-center justify-between">
            <span className="text-muted-foreground">最后在线</span>
            <span>{wechatStatus.lastOnline || "当前在线"}</span>
          </p>
          <p className="flex items-center justify-between">
            <span className="text-muted-foreground">在线时长</span>
            <span>{wechatStatus.onlineDuration} 分钟</span>
          </p>
        </div>
      )}
    </Card>
  )
}

