"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"

type TaskRow = { id: number; name: string; type: number; reward: number; isShow: number; isHot: number; receiveNum: number; completeNum: number }

export function LiveTaskTable() {
  const [rows, setRows] = useState<TaskRow[]>([])
  const [search, setSearch] = useState("")
  const [loading, setLoading] = useState(true)
  const router = useRouter()

  async function load() {
    setLoading(true)
    const res = await fetch(`/api/admin/tasks?search=${encodeURIComponent(search)}`, { cache: "no-store" })
    const json = await res.json()
    setRows(json.data || [])
    setLoading(false)
  }

  useEffect(() => { void load() }, [])

  async function toggle(row: TaskRow) {
    await fetch(`/api/admin/tasks/${row.id}`, { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ isShow: row.isShow ? 0 : 1 }) })
    await load()
  }
  async function edit(row: TaskRow) {
    const name = window.prompt("任务名称", row.name); if (!name) return
    const reward = window.prompt("任务奖励", String(row.reward)); if (reward === null) return
    await fetch(`/api/admin/tasks/${row.id}`, { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ name, reward: Number(reward) }) }); await load()
  }

  return <Card className="overflow-hidden">
      <div className="flex flex-col gap-3 border-b p-4 md:flex-row md:items-center md:justify-between">
      <div><h1 className="text-2xl font-bold">任务管理</h1><p className="text-sm text-muted-foreground">直接管理碎片时间小程序任务</p></div>
      <div className="flex gap-2"><Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="搜索任务名称" /><Button onClick={load}>查询</Button><Button onClick={() => router.push("/admin/tasks/new")}>新增任务</Button></div>
    </div>
    <div className="overflow-auto">
      <Table><TableHeader><TableRow><TableHead>ID</TableHead><TableHead>任务名称</TableHead><TableHead>奖励</TableHead><TableHead>领取/完成</TableHead><TableHead>状态</TableHead><TableHead className="text-right">操作</TableHead></TableRow></TableHeader>
      <TableBody>{loading ? <TableRow><TableCell colSpan={6}>正在读取真实数据...</TableCell></TableRow> : rows.map((row) => <TableRow key={row.id}><TableCell>{row.id}</TableCell><TableCell>{row.name}</TableCell><TableCell>¥{Number(row.reward).toFixed(2)}</TableCell><TableCell>{row.receiveNum}/{row.completeNum}</TableCell><TableCell><Badge variant={row.isShow ? "default" : "secondary"}>{row.isShow ? "显示" : "隐藏"}</Badge></TableCell><TableCell className="text-right"><div className="flex justify-end gap-2"><Button size="sm" variant="outline" onClick={() => edit(row)}>编辑</Button><Button size="sm" variant="outline" onClick={() => toggle(row)}>{row.isShow ? "隐藏" : "显示"}</Button></div></TableCell></TableRow>)}</TableBody></Table>
    </div>
  </Card>
}
