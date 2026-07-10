import Link from "next/link"
import { Home, Trophy, User } from "lucide-react"

export function BottomNav() {
  return (
    <nav className="fixed bottom-0 left-0 right-0 z-50 flex h-16 items-center justify-around border-t bg-white shadow-lg">
      <Link href="/" className="flex flex-col items-center gap-1">
        <Home className="h-6 w-6 text-primary" />
        <span className="text-xs">任务首页</span>
      </Link>
      <Link href="/rankings" className="flex flex-col items-center gap-1">
        <Trophy className="h-6 w-6 text-muted-foreground" />
        <span className="text-xs text-muted-foreground">收益排行榜</span>
      </Link>
      <Link href="/profile" className="flex flex-col items-center gap-1">
        <User className="h-6 w-6 text-muted-foreground" />
        <span className="text-xs text-muted-foreground">个人中心</span>
      </Link>
    </nav>
  )
}

