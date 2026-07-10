"use client"
import { useEffect, useState } from "react"
import { Card } from "@/components/ui/card"
export default function AdminsPage() {
  const [rows, setRows] = useState<any[]>([])
  useEffect(() => { fetch("/api/admin/admins").then(r => r.json()).then(r => setRows(r.data || [])) }, [])
  return <div className="p-4 lg:p-6"><h1 className="mb-1 text-2xl font-bold">后台账号</h1><p className="mb-6 text-sm text-muted-foreground">对应原 FastAdmin auth/admin，账号权限仍由原后台表控制。</p><Card className="overflow-auto"><table className="w-full text-sm"><thead><tr className="border-b text-left"><th className="p-4">ID</th><th>用户名</th><th>昵称</th><th>状态</th><th>最后登录</th><th>登录 IP</th></tr></thead><tbody>{rows.map(r => <tr className="border-b" key={r.id}><td className="p-4">{r.id}</td><td>{r.username}</td><td>{r.nickname || "-"}</td><td>{r.status}</td><td>{r.logintime || "-"}</td><td>{r.loginip || "-"}</td></tr>)}</tbody></table></Card></div>
}
