"use client"

import { useState } from "react"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Plus, Search } from "lucide-react"
import { useRouter } from "next/navigation"

const tasks = [
  {
    id: "93",
    type: "抖音点赞",
    status: "显示",
    company: "系统添加",
    name: "测试勿点",
  },
  {
    id: "88",
    type: "普通任务",
    status: "显示",
    company: "系统添加",
    name: "vx号注册",
  },
  {
    id: "72",
    type: "添加好友",
    status: "显示",
    company: "系统添加",
    name: "抖音点赞",
  },
  {
    id: "44",
    type: "代发朋友圈",
    status: "显示",
    company: "系统添加",
    name: "朋友圈代发",
  },
  {
    id: "41",
    type: "普通任务",
    status: "显示",
    company: "系统添加",
    name: "vx自动挂机",
  },
]

export default function TasksPage() {
  const router = useRouter()
  const [searchTerm, setSearchTerm] = useState("")

  return (
    <div className="p-6">
      <Card className="p-6">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold">任务管理</h1>
          <Button onClick={() => router.push("/admin/tasks/new")}>
            <Plus className="mr-2 h-4 w-4" />
            添加任务
          </Button>
        </div>

        <div className="mb-6 flex items-center gap-4">
          <div className="flex w-[300px] items-center rounded-md border px-3">
            <Search className="h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="搜索任务名称"
              className="border-0 focus-visible:ring-0"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <Select defaultValue="all">
            <SelectTrigger className="w-[200px]">
              <SelectValue placeholder="任务类型" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">全部类型</SelectItem>
              <SelectItem value="normal">普通任务</SelectItem>
              <SelectItem value="friend">添加好友</SelectItem>
              <SelectItem value="moments">朋友圈任务</SelectItem>
              <SelectItem value="tiktok">抖音任务</SelectItem>
            </SelectContent>
          </Select>
          <Select defaultValue="all">
            <SelectTrigger className="w-[200px]">
              <SelectValue placeholder="显示状态" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">全部状态</SelectItem>
              <SelectItem value="show">显示</SelectItem>
              <SelectItem value="hide">隐藏</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>编号</TableHead>
              <TableHead>任务类型</TableHead>
              <TableHead>状态</TableHead>
              <TableHead>发布公司/账号</TableHead>
              <TableHead>任务名称</TableHead>
              <TableHead className="text-right">操作</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {tasks.map((task) => (
              <TableRow key={task.id}>
                <TableCell>{task.id}</TableCell>
                <TableCell>
                  <Badge variant="secondary">{task.type}</Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="success" className="bg-green-100 text-green-800">
                    {task.status}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{task.company}</Badge>
                </TableCell>
                <TableCell>{task.name}</TableCell>
                <TableCell className="text-right">
                  <div className="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm">
                      审核
                    </Button>
                    <Button variant="outline" size="sm">
                      编辑
                    </Button>
                    <Button variant="outline" size="sm">
                      统计
                    </Button>
                    <Button variant="outline" size="sm">
                      任务详情
                    </Button>
                    <Button variant="destructive" size="sm">
                      删除
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>

        <div className="mt-4 flex items-center justify-between">
          <div className="text-sm text-muted-foreground">共 {tasks.length} 条记录</div>
          <div className="flex items-center gap-2">
            <Select defaultValue="10">
              <SelectTrigger className="w-[80px]">
                <SelectValue placeholder="每页条数" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="10">10条/页</SelectItem>
                <SelectItem value="20">20条/页</SelectItem>
                <SelectItem value="50">50条/页</SelectItem>
              </SelectContent>
            </Select>
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

