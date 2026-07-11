"use client"
import { useEffect,useState } from "react"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
export default function ProfilePage(){const [form,setForm]=useState<any>(null),[msg,setMsg]=useState("");useEffect(()=>{fetch('/api/admin/profile').then(r=>r.json()).then(j=>setForm(j.data))},[]);if(!form)return <div className="p-6">读取资料中...</div>;async function save(){const r=await fetch('/api/admin/profile',{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify(form)});const j=await r.json();setMsg(j.msg||'')};return <div className="p-4 lg:p-6"><Card className="max-w-2xl space-y-5 p-6"><div><h1 className="text-2xl font-bold">个人资料与密码</h1><p className="text-sm text-muted-foreground">不填写新密码则保持原密码不变</p></div>{[['账号','username',true],['昵称','nickname',false],['邮箱','email',false],['手机','mobile',false],['头像URL','avatar',false],['新密码','password',false]].map(([label,key,disabled])=><div className="space-y-2" key={String(key)}><Label>{String(label)}</Label><Input disabled={Boolean(disabled)} type={key==='password'?'password':'text'} value={form[key]||''} onChange={e=>setForm({...form,[key]:e.target.value})}/></div>)}<Button onClick={save}>保存资料</Button><p className="text-sm text-orange-600">{msg}</p></Card></div>}
