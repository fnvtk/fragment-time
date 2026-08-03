"use client"

import type React from "react"

import { useState } from "react"
import { Sidebar } from "@/components/admin/sidebar"
import { Button } from "@/components/ui/button"
import { Menu, ArrowLeft, CircleCheck } from "lucide-react"
import Link from "next/link"

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const [sidebarOpen, setSidebarOpen] = useState(true)

  return (
    <div className="flex min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-purple-50/20">
      <Sidebar open={sidebarOpen} onOpenChange={setSidebarOpen} />
      <div className="flex-1">
        <div className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-white/70 bg-white/85 px-4 shadow-sm backdrop-blur-xl lg:px-6">
          <div className="flex items-center">
            <Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setSidebarOpen(true)}>
              <Menu className="h-5 w-5" />
            </Button>
            <div className="ml-4"><div className="text-lg font-bold tracking-tight">碎片时间管理后台</div><div className="flex items-center gap-1.5 text-xs text-muted-foreground"><CircleCheck className="h-3.5 w-3.5 text-emerald-500" />数据已连接 · FastAdmin 权限统一管理</div></div>
          </div>
          <Link href="/">
            <Button variant="outline" size="sm" className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              返回前台
            </Button>
          </Link>
        </div>
        <main className="mx-auto min-h-[calc(100vh-4rem)] w-full max-w-7xl">{children}</main>
      </div>
    </div>
  )
}
