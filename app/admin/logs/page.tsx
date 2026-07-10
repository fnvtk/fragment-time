"use client"
import { useEffect, useState } from "react"
import { Card } from "@/components/ui/card"
export default function LogsPage() {
  const [rows, setRows] = useState<any[]>([])
  useEffect(() => { fetch("/api/admin/logs").then(r => r.json()).then(r => setRows(r.data || [])) }, [])
  return <div className="p-4 lg:p-6"><h1 className="mb-1 text-2xl font-bold">操作日志</h1><p className="mb-6 text-sm text-muted-foreground">对应原 FastAdmin auth/adminlog。</p><Card className="overflow-auto"><table className="w-full text-sm"><thead><tr className="border-b text-left"><th className="p-4">时间</th><th>账号</th><th>标题</th><th>URL</th><th>IP</th></tr></thead><tbody>{rows.map(r => <tr className="border-b" key={r.id}><td className="p-4">{r.createtime || "-"}</td><td>{r.username || r.admin_id}</td><td>{r.title || "-"}</td><td className="max-w-md truncate">{r.url || "-"}</td><td>{r.ip || "-"}</td></tr>)}</tbody></table></Card></div>
}
