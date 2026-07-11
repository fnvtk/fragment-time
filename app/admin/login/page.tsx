"use client"
import { useState } from "react"
import { useRouter } from "next/navigation"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"

export default function LoginPage() {
  const router = useRouter(); const [username,setUsername]=useState("admin"); const [password,setPassword]=useState(""); const [message,setMessage]=useState("")
  async function login(){ setMessage("登录中..."); const res=await fetch("/api/admin/auth/login",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({username,password})}); const json=await res.json(); if(res.ok){router.replace("/admin");router.refresh()}else setMessage(json.msg||"登录失败") }
  return <main className="flex min-h-screen items-center justify-center bg-gradient-to-br from-stone-100 via-orange-50 to-amber-100 p-4"><Card className="w-full max-w-md border-white/60 bg-white/85 p-8 shadow-2xl backdrop-blur"><div className="mb-8"><p className="text-sm font-medium text-orange-600">FRAGMENT TIME</p><h1 className="mt-2 text-3xl font-bold text-stone-900">碎片时间管理后台</h1><p className="mt-2 text-sm text-stone-500">使用数据库管理员账号登录</p></div><div className="space-y-5"><div className="space-y-2"><Label>账号</Label><Input value={username} onChange={e=>setUsername(e.target.value)} autoComplete="username" /></div><div className="space-y-2"><Label>密码</Label><Input type="password" value={password} onChange={e=>setPassword(e.target.value)} onKeyDown={e=>e.key==='Enter'&&login()} autoComplete="current-password" /></div><Button className="h-11 w-full bg-orange-600 hover:bg-orange-700" onClick={login}>登录</Button><p className="text-center text-sm text-red-600">{message}</p></div></Card></main>
}
