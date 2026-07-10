"use client"

import { useEffect, useState } from "react"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"
import { Switch } from "@/components/ui/switch"

type Settings = { appName: string; appIcon: string; minMoney: string; maxMoney: string; showTaskMoney: number; showWithdrawalBtn: number; showMyMoney: number }
export function SettingsForm() {
  const [form, setForm] = useState<Settings | null>(null)
  const [message, setMessage] = useState("")
  useEffect(() => { fetch("/api/admin/settings", { cache: "no-store" }).then((r) => r.json()).then((j) => setForm(j.data)) }, [])
  if (!form) return <Card className="p-6">正在读取配置...</Card>
  async function save() { setMessage("保存中..."); const r = await fetch("/api/admin/settings", { method: "PATCH", headers: { "Content-Type": "application/json" }, body: JSON.stringify(form) }); setMessage(r.ok ? "保存成功，小程序下次请求立即生效" : "保存失败") }
  const set = (key: keyof Settings, value: string | number) => setForm((old) => old ? { ...old, [key]: value } : old)
  return <Card className="space-y-6 p-6"><div className="grid gap-2"><Label>平台名称</Label><Input value={form.appName} onChange={(e) => set("appName", e.target.value)} /></div><div className="grid gap-2"><Label>平台 Logo URL</Label><Input value={form.appIcon || ""} onChange={(e) => set("appIcon", e.target.value)} /></div><div className="grid grid-cols-2 gap-4"><div className="grid gap-2"><Label>最低提现</Label><Input type="number" value={form.minMoney} onChange={(e) => set("minMoney", e.target.value)} /></div><div className="grid gap-2"><Label>最高提现</Label><Input type="number" value={form.maxMoney} onChange={(e) => set("maxMoney", e.target.value)} /></div></div>{([['showTaskMoney','显示任务金额'],['showWithdrawalBtn','显示提现按钮'],['showMyMoney','显示我的收益']] as const).map(([key,label]) => <div key={key} className="flex items-center justify-between"><Label>{label}</Label><Switch checked={Boolean(form[key])} onCheckedChange={(v) => set(key, v ? 1 : 0)} /></div>)}<div className="flex items-center gap-3"><Button onClick={save}>保存配置</Button><span className="text-sm text-muted-foreground">{message}</span></div></Card>
}
