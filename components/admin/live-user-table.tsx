"use client"

import { useEffect, useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"

type UserRow = { id: number; nickName: string; mobile: string; balance: number; totalIncome: number; createTime: number }

export function LiveUserTable() {
  const [rows, setRows] = useState<UserRow[]>([])
  const [search, setSearch] = useState("")
  const [loading, setLoading] = useState(true)
  async function load() { setLoading(true); const res = await fetch(`/api/admin/users?search=${encodeURIComponent(search)}`, { cache: "no-store" }); const json = await res.json(); setRows(json.data || []); setLoading(false) }
  useEffect(() => { void load() }, [])
  return <Card className="overflow-hidden"><div className="flex flex-col gap-3 border-b p-4 md:flex-row md:items-center md:justify-between"><div><h1 className="text-2xl font-bold">用户管理</h1><p className="text-sm text-muted-foreground">仅显示碎片时间应用用户</p></div><div className="flex gap-2"><Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="昵称或手机号" /><Button onClick={load}>查询</Button></div></div><div className="overflow-auto"><Table><TableHeader><TableRow><TableHead>ID</TableHead><TableHead>用户</TableHead><TableHead>手机号</TableHead><TableHead>余额</TableHead><TableHead>累计收益</TableHead><TableHead>注册时间</TableHead></TableRow></TableHeader><TableBody>{loading ? <TableRow><TableCell colSpan={6}>正在读取真实数据...</TableCell></TableRow> : rows.map((row) => <TableRow key={row.id}><TableCell>{row.id}</TableCell><TableCell>{row.nickName || "未设置昵称"}</TableCell><TableCell>{row.mobile ? `${row.mobile.slice(0,3)}****${row.mobile.slice(-4)}` : "-"}</TableCell><TableCell>¥{Number(row.balance).toFixed(2)}</TableCell><TableCell>¥{Number(row.totalIncome).toFixed(2)}</TableCell><TableCell>{row.createTime ? new Date(row.createTime * 1000).toLocaleString("zh-CN") : "-"}</TableCell></TableRow>)}</TableBody></Table></div></Card>
}
