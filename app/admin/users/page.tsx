"use client"

import { useState } from "react"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Search } from "lucide-react"

const users = [
  {
    id: "1",
    name: "张三",
    phone: "158****2661",
    balance: 198.5,
    todayEarnings: 18.5,
    status: "正常",
    registerTime: "2024-02-18 10:30:22",
  },
  {
    id: "2",
    name: "李四",
    phone: "137****3366",
    balance: 156.2,
    todayEarnings: 12.3,
    status: "正常",
    registerTime: "2024-02-18 09:15:43",
  },
  {
    id: "3",
    name: "王五",
    phone: "139****8855",
    balance: 143.8,
    todayEarnings: 8.6,
    status: "禁用",
    registerTime: "2024-02-17 15:22:11",
  },
]

export default function UsersPage() {
  const [searchTerm, setSearchTerm] = useState("")

  return (
    <div className="p-4 lg:p-6">
      <Card className="overflow-hidden">
        <div className="border-b p-4 lg:p-6">
          <h1 className="text-xl font-bold lg:text-2xl">用户管理</h1>
          <div className="mt-4 flex flex-col gap-4 lg:flex-row lg:items-center">
            <div className="flex w-full items-center rounded-md border px-3 lg:w-[300px]">
              <Search className="h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="搜索用户名/手机号"
                className="border-0 focus-visible:ring-0"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            <div className="flex gap-2">
              <Button variant="outline">导出数据</Button>
              <Button variant="outline">批量操作</Button>
            </div>
          </div>
        </div>

        <div className="overflow-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>用户名</TableHead>
                <TableHead>手机号</TableHead>
                <TableHead>账户余额</TableHead>
                <TableHead>今日收益</TableHead>
                <TableHead>状态</TableHead>
                <TableHead>注册时间</TableHead>
                <TableHead className="text-right">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.map((user) => (
                <TableRow key={user.id}>
                  <TableCell>{user.id}</TableCell>
                  <TableCell>{user.name}</TableCell>
                  <TableCell>{user.phone}</TableCell>
                  <TableCell>¥{user.balance.toFixed(2)}</TableCell>
                  <TableCell>¥{user.todayEarnings.toFixed(2)}</TableCell>
                  <TableCell>
                    <Badge variant={user.status === "正常" ? "success" : "destructive"} className="rounded-md">
                      {user.status}
                    </Badge>
                  </TableCell>
                  <TableCell>{user.registerTime}</TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-2">
                      <Button variant="outline" size="sm">
                        详情
                      </Button>
                      <Button variant="outline" size="sm">
                        编辑
                      </Button>
                      <Button variant="destructive" size="sm">
                        {user.status === "正常" ? "禁用" : "启用"}
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>

        <div className="flex items-center justify-between border-t p-4">
          <div className="text-sm text-muted-foreground">共 {users.length} 条记录</div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" disabled>
              上一页
            </Button>
            <Button variant="outline" size="sm">
              下一页
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}

