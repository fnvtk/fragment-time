import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Avatar } from "@/components/ui/avatar"
import { Label } from "@/components/ui/label"
import Link from "next/link"
import Image from "next/image"

export default function ProfileInfoPage() {
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
        <h1 className="text-lg font-medium">个人资料</h1>
      </header>

      <div className="p-4">
        <Card className="space-y-4 p-4">
          <div className="flex flex-col items-center">
            <Avatar className="h-24 w-24">
              <Image src="/placeholder.svg" alt="头像" width={96} height={96} />
            </Avatar>
            <Button variant="outline" size="sm" className="mt-2">
              更换头像
            </Button>
          </div>

          <div className="space-y-2">
            <Label htmlFor="nickname">昵称</Label>
            <Input id="nickname" defaultValue="卡若" />
          </div>

          <div className="space-y-2">
            <Label htmlFor="gender">性别</Label>
            <Input id="gender" defaultValue="男" />
          </div>

          <div className="space-y-2">
            <Label htmlFor="birthday">生日</Label>
            <Input id="birthday" type="date" defaultValue="2011-03-01" />
          </div>

          <div className="space-y-2">
            <Label htmlFor="phone">手机号</Label>
            <Input id="phone" defaultValue="15880802661" disabled />
          </div>

          <Button className="w-full">保存修改</Button>
        </Card>
      </div>
    </main>
  )
}

