import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import Link from "next/link"

export default function SwitchAccountPage() {
  return (
    <main className="min-h-screen bg-gray-50">
      <header className="sticky top-0 z-10 flex items-center gap-2 bg-primary p-4 text-white">
        <Link href="/profile" className="rounded-full bg-white/10 p-2">
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
        <h1 className="text-lg font-medium">切换账号</h1>
      </header>

      <div className="p-4">
        <Card className="space-y-4 p-4">
          <div className="space-y-2">
            <Label htmlFor="phone">手机号</Label>
            <Input id="phone" placeholder="请输入手机号" />
          </div>

          <div className="space-y-2">
            <Label htmlFor="password">密码</Label>
            <Input id="password" type="password" placeholder="请输入密码" />
          </div>

          <Button className="w-full">登录</Button>

          <div className="text-center text-sm text-muted-foreground">
            <Link href="/register" className="hover:underline">
              没有账号？立即注册
            </Link>
          </div>
        </Card>
      </div>
    </main>
  )
}

