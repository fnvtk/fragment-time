import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Avatar } from "@/components/ui/avatar"
import { UserCircle, Wallet, ClipboardList, UserCog, ChevronRight, Settings } from "lucide-react"
import Image from "next/image"
import Link from "next/link"
import { BottomNav } from "@/components/bottom-nav"

const menuItems = [
  {
    icon: <UserCircle className="h-5 w-5 text-orange-500" />,
    title: "个人资料",
    path: "/profile/info",
  },
  {
    icon: <Wallet className="h-5 w-5 text-blue-500" />,
    title: "提现记录",
    path: "/profile/withdrawals",
  },
  {
    icon: <ClipboardList className="h-5 w-5 text-green-500" />,
    title: "我的任务",
    path: "/profile/tasks",
  },
  {
    icon: <UserCog className="h-5 w-5 text-purple-500" />,
    title: "切换账号",
    path: "/profile/switch",
  },
]

export default function ProfilePage() {
  return (
    <main className="min-h-screen bg-gray-50 pb-16">
      <div className="bg-primary pb-6 pt-4">
        <div className="px-4">
          <Card className="overflow-hidden bg-gradient-to-r from-purple-500 to-primary">
            <div className="p-4">
              <div className="mb-4 flex items-center gap-3">
                <Avatar className="h-16 w-16 border-2 border-white">
                  <Image
                    src="https://api.dicebear.com/7.x/avataaars/svg?seed=卡若"
                    alt="用户头像"
                    width={64}
                    height={64}
                  />
                </Avatar>
                <div className="text-white">
                  <h2 className="text-xl font-bold">卡若</h2>
                  <p className="text-sm text-white/80">ID: 158****2661</p>
                </div>
                <Button variant="secondary" size="sm" className="ml-auto">
                  提现
                </Button>
              </div>

              <div className="grid grid-cols-3 gap-4 text-white">
                <div className="text-center">
                  <div className="text-lg font-bold">12,458.50</div>
                  <div className="text-sm text-white/80">总收益(元)</div>
                </div>
                <div className="text-center">
                  <div className="text-lg font-bold">258.50</div>
                  <div className="text-sm text-white/80">今日收益(元)</div>
                </div>
                <div className="text-center">
                  <div className="text-lg font-bold">12,200.00</div>
                  <div className="text-sm text-white/80">余额(元)</div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </div>

      <div className="p-4 space-y-4">
        <Card>
          {menuItems.map((item, index) => (
            <Link
              key={item.path}
              href={item.path}
              className={`flex items-center justify-between p-4 ${index !== menuItems.length - 1 ? "border-b" : ""}`}
            >
              <div className="flex items-center gap-3">
                {item.icon}
                <span>{item.title}</span>
              </div>
              <ChevronRight className="h-5 w-5 text-muted-foreground" />
            </Link>
          ))}
        </Card>

        <Link href="/admin">
          <Card className="p-4 flex items-center justify-between hover:bg-gray-50">
            <div className="flex items-center gap-3">
              <Settings className="h-5 w-5 text-gray-500" />
              <span>管理后台</span>
            </div>
            <ChevronRight className="h-5 w-5 text-muted-foreground" />
          </Card>
        </Link>
      </div>

      <BottomNav />
    </main>
  )
}

