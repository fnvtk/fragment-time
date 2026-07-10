import { Card } from "@/components/ui/card"
import Image from "next/image"

export function WelcomeBanner() {
  return (
    <div className="bg-primary p-4">
      <Card className="overflow-hidden bg-gradient-to-r from-blue-500 to-indigo-600 shadow-lg">
        <div className="relative p-6">
          <div className="absolute right-0 top-0">
            <Image
              src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/screenshot-20250218-132323-qddDR4nvvuCPGddvw0S8vQ5DhFssUx.png"
              alt="金币"
              width={120}
              height={120}
              className="opacity-20"
            />
          </div>
          <h1 className="mb-2 text-2xl font-bold text-white">每天几分钟</h1>
          <p className="text-lg text-primary-foreground">轻松赚收益</p>
          <p className="mt-2 text-sm text-primary-foreground/80">完成任务即可获得奖励</p>
        </div>
      </Card>
    </div>
  )
}

