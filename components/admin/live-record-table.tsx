"use client"

import { useEffect, useState } from "react"
import { Card } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"

export function LiveRecordTable({ kind }: { kind: "packages" | "withdrawals" }) {
  const [rows, setRows] = useState<Record<string, unknown>[]>([])
  useEffect(() => { fetch(`/api/admin/${kind}`, { cache: "no-store" }).then((r) => r.json()).then((j) => setRows(j.data || [])) }, [kind])
  const packages = kind === "packages"
  return <Card className="overflow-hidden"><div className="border-b p-5"><h1 className="text-2xl font-bold">{packages ? "数据包管理" : "提现记录"}</h1><p className="text-sm text-muted-foreground">{packages ? "查看任务数据包库存" : "查看碎片时间用户提现记录"}</p></div><div className="overflow-auto"><Table><TableHeader><TableRow>{(packages ? ["ID","名称","说明","总量","可用","状态"] : ["ID","用户","手机号","金额","状态","时间"]).map((x) => <TableHead key={x}>{x}</TableHead>)}</TableRow></TableHeader><TableBody>{rows.map((row) => packages ? <TableRow key={String(row.id)}><TableCell>{String(row.id)}</TableCell><TableCell>{String(row.name || "-")}</TableCell><TableCell>{String(row.remarks || "-")}</TableCell><TableCell>{String(row.total || 0)}</TableCell><TableCell>{String(row.available || 0)}</TableCell><TableCell>{Number(row.status) === 1 ? "启用" : "停用"}</TableCell></TableRow> : <TableRow key={String(row.id)}><TableCell>{String(row.id)}</TableCell><TableCell>{String(row.nickName || `用户${row.uid}`)}</TableCell><TableCell>{row.mobile ? `${String(row.mobile).slice(0,3)}****${String(row.mobile).slice(-4)}` : "-"}</TableCell><TableCell>¥{Number(row.money).toFixed(2)}</TableCell><TableCell>{["待处理","已完成","已拒绝"][Number(row.status)] || String(row.status)}</TableCell><TableCell>{row.createTime ? new Date(Number(row.createTime) * 1000).toLocaleString("zh-CN") : "-"}</TableCell></TableRow>)}</TableBody></Table></div></Card>
}
