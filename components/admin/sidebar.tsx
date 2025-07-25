"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { ScrollArea } from "@/components/ui/scroll-area"
import { Sheet, SheetContent } from "@/components/ui/sheet"
import { LayoutDashboard, Users, FileSpreadsheet, Package, ClipboardList, BarChart3, Settings, X } from "lucide-react"

const menuItems = [
  {
    title: "控制台",
    icon: <LayoutDashboard className="h-5 w-5" />,
    href: "/admin",
  },
  {
    title: "用户管理",
    icon: <Users className="h-5 w-5" />,
    href: "/admin/users",
  },
  {
    title: "任务管理",
    icon: <ClipboardList className="h-5 w-5" />,
    href: "/admin/tasks",
  },
  {
    title: "数据包管理",
    icon: <Package className="h-5 w-5" />,
    href: "/admin/packages",
  },
  {
    title: "粉丝列表",
    icon: <FileSpreadsheet className="h-5 w-5" />,
    href: "/admin/fans",
  },
  {
    title: "统计报表",
    icon: <BarChart3 className="h-5 w-5" />,
    href: "/admin/stats",
  },
  {
    title: "系统设置",
    icon: <Settings className="h-5 w-5" />,
    href: "/admin/settings",
  },
]

interface SidebarProps {
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

export function Sidebar({ open, onOpenChange }: SidebarProps) {
  const pathname = usePathname()

  const content = (
    <ScrollArea className="h-full py-6">
      <div className="space-y-4 px-3">
        <div className="flex h-12 items-center px-2">
          <Link href="/admin" className="flex items-center gap-2 font-semibold">
            <span className="text-xl">碎片时间</span>
          </Link>
          <Button variant="ghost" size="icon" className="ml-auto lg:hidden" onClick={() => onOpenChange?.(false)}>
            <X className="h-5 w-5" />
          </Button>
        </div>
        <div className="space-y-1">
          {menuItems.map((item) => (
            <Link key={item.href} href={item.href}>
              <Button
                variant="ghost"
                className={cn("w-full justify-start", pathname === item.href && "bg-primary/10 text-primary")}
              >
                {item.icon}
                <span className="ml-2">{item.title}</span>
              </Button>
            </Link>
          ))}
        </div>
      </div>
    </ScrollArea>
  )

  return (
    <>
      <aside className="hidden w-64 border-r bg-white lg:block">{content}</aside>
      <Sheet open={open} onOpenChange={onOpenChange}>
        <SheetContent side="left" className="w-64 p-0">
          {content}
        </SheetContent>
      </Sheet>
    </>
  )
}

