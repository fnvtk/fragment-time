import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Textarea } from "@/components/ui/textarea"
import { ImagePlus, ArrowLeft, AlertTriangle } from "lucide-react"
import Link from "next/link"
import Image from "next/image"

export default function MomentsTaskPage() {
  return (
    <main className="min-h-screen bg-gray-50 pb-16">
      <header className="sticky top-0 z-10 flex items-center gap-2 bg-primary p-4 text-white">
        <Link href="/tasks/3" className="rounded-full bg-white/10 p-2">
          <ArrowLeft className="h-6 w-6" />
        </Link>
        <h1 className="text-lg font-medium">朋友圈转发任务</h1>
      </header>

      <div className="p-4 space-y-4">
        <Card className="p-4 space-y-4">
          <div className="space-y-2">
            <h2 className="font-medium">转发内容</h2>
            <Textarea
              className="min-h-[120px]"
              placeholder="转发文案将在这里显示..."
              defaultValue="✨推荐一个超棒的赚钱平台！
每天只需要几分钟，就能赚取额外收入！
现在加入还能获得新人奖励，赶快来试试吧～
#副业 #赚钱 #兼职"
              readOnly
            />
          </div>

          <div className="space-y-2">
            <h2 className="font-medium">配图</h2>
            <div className="grid grid-cols-3 gap-2">
              <div className="relative aspect-square rounded-xl overflow-hidden">
                <Image src="/placeholder.svg" alt="朋友圈配图1" fill className="object-cover" />
              </div>
              <div className="relative aspect-square rounded-xl overflow-hidden">
                <Image src="/placeholder.svg" alt="朋友圈配图2" fill className="object-cover" />
              </div>
              <div className="relative aspect-square rounded-xl overflow-hidden">
                <Image src="/placeholder.svg" alt="朋友圈配图3" fill className="object-cover" />
              </div>
            </div>
          </div>

          <div className="space-y-2">
            <h2 className="font-medium">截图验证</h2>
            <div className="border-2 border-dashed rounded-xl p-8 flex flex-col items-center justify-center gap-2">
              <ImagePlus className="h-8 w-8 text-muted-foreground" />
              <p className="text-sm text-muted-foreground">上传朋友圈发布截图</p>
            </div>
          </div>

          <div className="rounded-lg bg-yellow-50 p-4">
            <div className="flex gap-2">
              <AlertTriangle className="h-5 w-5 text-yellow-600" />
              <div className="flex-1">
                <h3 className="font-medium text-yellow-800">注意事项</h3>
                <ul className="mt-2 space-y-1 text-sm text-yellow-700">
                  <li>• 朋友圈必须设为公开可见</li>
                  <li>• 发布内容需保持48小时以上</li>
                  <li>• 不得修改官方提供的文案和图片</li>
                  <li>• 截图需包含发布时间和点赞数</li>
                </ul>
              </div>
            </div>
          </div>
        </Card>

        <Button className="w-full" size="lg">
          提交验证
        </Button>
      </div>
    </main>
  )
}

