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
  const [error, setError] = useState("")
  const router = useRouter()

  async function load() {
    setLoading(true)
    setError("")
    try { const res = await fetch(`/api/admin/tasks?search=${encodeURIComponent(search)}`, { cache: "no-store" }); if (!res.ok) throw new Error("请求失败"); const json = await res.json(); setRows(json.data || []) } catch { setError("任务数据加载失败，请重试") }
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
      <div className="flex flex-wrap gap-2"><Input className="min-w-48" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="搜索任务名称" /><Button variant="outline" onClick={load}>查询</Button><Button variant="outline" onClick={load}>刷新</Button><Button onClick={() => router.push("/admin/tasks/new")}>新增任务</Button></div>
    </div>
    <div className="overflow-auto">
      <Table><TableHeader><TableRow><TableHead>ID</TableHead><TableHead>任务名称</TableHead><TableHead>奖励</TableHead><TableHead>领取/完成</TableHead><TableHead>状态</TableHead><TableHead className="text-right">操作</TableHead></TableRow></TableHeader>
      <TableBody>{loading ? <TableRow><TableCell colSpan={6} className="h-24 text-center text-muted-foreground">正在读取真实数据...</TableCell></TableRow> : error ? <TableRow><TableCell colSpan={6} className="h-24 text-center text-red-600">{error} <Button size="sm" variant="outline" className="ml-2" onClick={load}>重试</Button></TableCell></TableRow> : rows.length === 0 ? <TableRow><TableCell colSpan={6} className="h-24 text-center text-muted-foreground">暂无任务数据，可点击“新增任务”创建</TableCell></TableRow> : rows.map((row) => <TableRow key={row.id}><TableCell>{row.id}</TableCell><TableCell>{row.name}</TableCell><TableCell>¥{Number(row.reward).toFixed(2)}</TableCell><TableCell>{row.receiveNum}/{row.completeNum}</TableCell><TableCell><Badge variant={row.isShow ? "default" : "secondary"}>{row.isShow ? "显示" : "隐藏"}</Badge></TableCell><TableCell className="text-right"><div className="flex justify-end gap-2"><Button size="sm" variant="outline" onClick={() => edit(row)}>编辑</Button><Button size="sm" variant="outline" onClick={() => toggle(row)}>{row.isShow ? "隐藏" : "显示"}</Button></div></TableCell></TableRow>)}</TableBody></Table>
    </div>
  </Card>
}
