import { NextRequest, NextResponse } from "next/server"
import { getUsers } from "@/lib/server/fragment-time"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"
import crypto from "node:crypto"
import { requireAdmin, writeAdminLog } from "@/lib/server/admin-system"

export async function GET(request: NextRequest) {
  const search = request.nextUrl.searchParams.get("search") || ""
  return NextResponse.json({ code: 1, data: await getUsers(search) })
}

export async function PATCH(request: NextRequest) { let admin; try { admin=await requireAdmin("users") } catch { return NextResponse.json({code:0,msg:"无权限"},{status:403}) }; const b=await request.json(); if(!Number(b.id))return NextResponse.json({code:0,msg:"缺少用户ID"},{status:400}); const fields:string[]=[];const values:any[]=[]; for(const key of ["nickName","mobile","gender","birthday","balance"]){if(b[key]!==undefined){fields.push(`\`${key}\`=?`);values.push(b[key])}} if(b.password){if(String(b.password).length<6)return NextResponse.json({code:0,msg:"密码至少6位"},{status:400});fields.push("password=?");values.push(crypto.createHash("md5").update(String(b.password)).digest("hex"))} if(!fields.length)return NextResponse.json({code:0,msg:"没有可更新字段"},{status:400}); await getDb().execute(`UPDATE WechatApp_fans SET ${fields.join(",")},updataTime=NOW() WHERE id=? AND appid=?`,[...values,Number(b.id),FRAGMENT_APP_ID]); await writeAdminLog(admin,"修改小程序用户","/api/admin/users",JSON.stringify({id:b.id,fields:fields.map(x=>x.split("`")[1])})); return NextResponse.json({code:1,msg:"保存成功"}) }

export async function DELETE(request: NextRequest) { let admin; try { admin=await requireAdmin("users") } catch { return NextResponse.json({code:0,msg:"无权限"},{status:403}) }; const id=Number(request.nextUrl.searchParams.get("id")); if(!id)return NextResponse.json({code:0,msg:"缺少用户ID"},{status:400}); await getDb().execute("DELETE FROM WechatApp_fans WHERE id=? AND appid=?",[id,FRAGMENT_APP_ID]); await writeAdminLog(admin,"删除小程序用户","/api/admin/users",String(id)); return NextResponse.json({code:1,msg:"删除成功"}) }
