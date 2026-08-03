"use client"

import Link from "next/link"
import { usePathname, useRouter } from "next/navigation"
import { useEffect, useState } from "react"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { ScrollArea } from "@/components/ui/scroll-area"
import { Sheet, SheetContent } from "@/components/ui/sheet"
import { LayoutDashboard, Users, FileSpreadsheet, Package, ClipboardList, BarChart3, Settings, X, WalletCards, Shield, ScrollText, UsersRound, ListTree, Images, FolderTree, SlidersHorizontal, LogOut, ReceiptText, UserCog, ChevronDown } from "lucide-react"

const menuGroups = [
  { title: "工作台", items: [{ title: "控制台", icon: <LayoutDashboard className="h-5 w-5" />, href: "/admin" }, { title: "统计报表", icon: <BarChart3 className="h-5 w-5" />, href: "/admin/stats" }] },
  { title: "用户与内容", items: [{ title: "用户管理", icon: <Users className="h-5 w-5" />, href: "/admin/users" }, { title: "粉丝列表", icon: <FileSpreadsheet className="h-5 w-5" />, href: "/admin/fans" }, { title: "素材管理", icon: <Images className="h-5 w-5" />, href: "/admin/attachments", rule: "attachments" }, { title: "分类管理", icon: <FolderTree className="h-5 w-5" />, href: "/admin/categories", rule: "categories" }] },
  { title: "运营管理", items: [{ title: "任务管理", icon: <ClipboardList className="h-5 w-5" />, href: "/admin/tasks" }, { title: "数据包管理", icon: <Package className="h-5 w-5" />, href: "/admin/packages" }] },
  { title: "财务与报表", items: [{ title: "提现记录", icon: <WalletCards className="h-5 w-5" />, href: "/admin/withdrawals" }, { title: "收费与账单", icon: <ReceiptText className="h-5 w-5" />, href: "/admin/bills", rule: "bills" }] },
  { title: "系统管理", items: [{ title: "系统设置", icon: <Settings className="h-5 w-5" />, href: "/admin/settings" }, { title: "后台账号", icon: <Shield className="h-5 w-5" />, href: "/admin/admins", rule: "admins" }, { title: "权限组", icon: <UsersRound className="h-5 w-5" />, href: "/admin/groups", rule: "groups" }, { title: "菜单规则", icon: <ListTree className="h-5 w-5" />, href: "/admin/rules", rule: "rules" }, { title: "通用配置", icon: <SlidersHorizontal className="h-5 w-5" />, href: "/admin/configs", rule: "configs" }, { title: "操作日志", icon: <ScrollText className="h-5 w-5" />, href: "/admin/logs", rule: "logs" }, { title: "个人资料", icon: <UserCog className="h-5 w-5" />, href: "/admin/profile", rule: "profile" }] },
].map(group => ({ ...group, items: group.items.map(item => ({ ...item, rule: item.rule || (item.href === "/admin" ? "dashboard" : item.href.split("/").pop()!) })) }))

interface SidebarProps {
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

export function Sidebar({ open, onOpenChange }: SidebarProps) {
  const pathname = usePathname()
  const router = useRouter()
  const [rules, setRules] = useState<string[]>([])
  const [nickname, setNickname] = useState("管理员")
  const [collapsed, setCollapsed] = useState<Record<string, boolean>>({})
  useEffect(() => { fetch("/api/admin/auth/me").then(r => r.json()).then(j => { setRules(j.data?.rules || []); setNickname(j.data?.nickname || j.data?.username || "管理员") }) }, [])
  async function logout(){ await fetch("/api/admin/auth/logout",{method:"POST"}); router.replace("/admin/login"); router.refresh() }

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
        <div className="space-y-3">
          {menuGroups.map((group) => {
            const items = group.items.filter(item => rules.includes("*") || rules.includes(item.rule))
            if (!items.length) return null
            const isCollapsed = collapsed[group.title]
            return <section key={group.title}>
              <button type="button" className="flex w-full items-center justify-between px-3 py-1 text-xs font-semibold tracking-wide text-muted-foreground" onClick={() => setCollapsed(value => ({ ...value, [group.title]: !value[group.title] }))}>
                <span>{group.title}</span><ChevronDown className={cn("h-4 w-4 transition-transform", isCollapsed && "-rotate-90")} />
              </button>
              {!isCollapsed && <div className="mt-1 space-y-1">{items.map((item) => <Link key={item.href} href={item.href}><Button variant="ghost" className={cn("w-full justify-start", pathname === item.href && "bg-primary/10 text-primary")}>{item.icon}<span className="ml-2">{item.title}</span></Button></Link>)}</div>}
            </section>
          })}
        </div>
        <div className="mt-8 border-t pt-4"><p className="px-3 pb-2 text-xs text-muted-foreground">当前账号：{nickname}</p><Button variant="ghost" className="w-full justify-start text-red-600" onClick={logout}><LogOut className="h-5 w-5"/><span className="ml-2">退出登录</span></Button></div>
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
