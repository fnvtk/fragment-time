"use client"

import type React from "react"

import { useState } from "react"
import { Sidebar } from "@/components/admin/sidebar"
import { Button } from "@/components/ui/button"
import { Menu, ArrowLeft } from "lucide-react"
import Link from "next/link"

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const [sidebarOpen, setSidebarOpen] = useState(true)

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar open={sidebarOpen} onOpenChange={setSidebarOpen} />
      <div className="flex-1">
        <div className="sticky top-0 z-10 flex h-16 items-center justify-between border-b bg-white px-4 lg:px-6">
          <div className="flex items-center">
            <Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setSidebarOpen(true)}>
              <Menu className="h-5 w-5" />
            </Button>
            <div className="ml-4 text-lg font-semibold">碎片时间管理后台</div>
          </div>
          <Link href="/">
            <Button variant="outline" size="sm" className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              返回前台
            </Button>
          </Link>
        </div>
        <div className="mx-auto max-w-6xl">{children}</div>
      </div>
    </div>
  )
}

