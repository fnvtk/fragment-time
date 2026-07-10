import { Card } from "@/components/ui/card"
import { Avatar } from "@/components/ui/avatar"
import Image from "next/image"
import { generateAvatar } from "@/lib/avatar-utils"
import { BottomNav } from "@/components/bottom-nav"

const rankings = [
  { id: 1, name: "小熊饼干", phone: "158****2661", earnings: 38456.5 },
  { id: 2, name: "云朵棉花糖", phone: "137****3366", earnings: 25678.2 },
  { id: 3, name: "夏日柠檬茶", phone: "139****8855", earnings: 19843.8 },
  { id: 4, name: "深海萤火虫", phone: "135****1234", earnings: 15432.5 },
  { id: 5, name: "星空漫步者", phone: "136****5678", earnings: 12898.9 },
  { id: 6, name: "草莓慕斯", phone: "133****9012", earnings: 9876.3 },
  { id: 7, name: "午后阳光", phone: "134****3456", earnings: 7654.7 },
  { id: 8, name: "寂静森林", phone: "138****7890", earnings: 6543.2 },
  { id: 9, name: "城市浪人", phone: "132****2345", earnings: 5432.6 },
  { id: 10, name: "彩虹糖果", phone: "131****6789", earnings: 4310.1 },
]

export default function RankingsPage() {
  return (
    <main className="min-h-screen bg-gradient-to-b from-primary to-purple-800 pb-16">
      <div className="relative py-8">
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute left-0 top-0 h-40 w-40 -translate-x-1/2 -translate-y-1/2 rounded-full bg-purple-400/20" />
          <div className="absolute right-0 top-0 h-32 w-32 translate-x-1/2 -translate-y-1/2 rounded-full bg-purple-400/20" />
        </div>
        <h1 className="relative text-center text-3xl font-bold text-white">收益排行榜</h1>
        <div className="relative mt-2 text-center">
          <div className="inline-block rounded-full bg-yellow-400 px-4 py-1 text-sm font-medium">收益排名前10名</div>
        </div>
      </div>

      <div className="space-y-4 p-4">
        {rankings.map((user, index) => (
          <Card key={user.id} className="flex items-center justify-between p-4 rounded-xl">
            <div className="flex items-center gap-3">
              <div className="relative">
                <Avatar className="h-12 w-12 rounded-xl border-2 border-white/10">
                  <Image src={generateAvatar(user.name) || "/placeholder.svg"} alt={user.name} width={48} height={48} />
                </Avatar>
                <div className="absolute -top-2 -left-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                  {index + 1}
                </div>
              </div>
              <div>
                <div className="font-medium">{user.name}</div>
                <div className="text-sm text-muted-foreground">{user.phone}</div>
              </div>
            </div>
            <div className="text-right">
              <div className="text-red-500">+{user.earnings.toFixed(2)}</div>
              <div className="text-sm text-muted-foreground">总收益</div>
            </div>
          </Card>
        ))}
      </div>

      <BottomNav />
    </main>
  )
}

