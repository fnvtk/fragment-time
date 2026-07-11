import type { ResultSetHeader, RowDataPacket } from "mysql2"
import { NextRequest, NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { hashAdminPassword, requireAdmin, writeAdminLog } from "@/lib/server/admin-system"

const resources = new Set(["admins","groups","rules"])
const fail = (message:string,status=400) => NextResponse.json({code:0,msg:message},{status})

export async function GET(_request:NextRequest, context:{params:Promise<{resource:string}>}){
  const {resource}=await context.params
  if(!resources.has(resource)) return fail("未知资源",404)
  try { await requireAdmin(resource) } catch(e){ return fail(String(e).includes("FORBIDDEN")?"无权限":"请先登录",403) }
  const db=getDb()
  if(resource==="admins"){
    const [rows]=await db.query<RowDataPacket[]>(`SELECT a.id,a.username,a.nickname,a.avatar,a.email,a.mobile,a.status,a.loginfailure,a.logintime,a.loginip,a.createtime,GROUP_CONCAT(aga.group_id) groupIds,GROUP_CONCAT(g.name) groupNames FROM admin a LEFT JOIN auth_group_access aga ON aga.uid=a.id LEFT JOIN auth_group g ON g.id=aga.group_id GROUP BY a.id ORDER BY a.id`)
    return NextResponse.json({code:1,data:rows})
  }
  if(resource==="groups"){
    const [rows]=await db.query<RowDataPacket[]>(`SELECT g.*,COUNT(DISTINCT a.uid) adminCount FROM auth_group g LEFT JOIN auth_group_access a ON a.group_id=g.id GROUP BY g.id ORDER BY g.pid,g.id`)
    return NextResponse.json({code:1,data:rows})
  }
  const [rows]=await db.query<RowDataPacket[]>("SELECT * FROM auth_rule ORDER BY weigh DESC,id")
  return NextResponse.json({code:1,data:rows})
}

export async function POST(request:NextRequest, context:{params:Promise<{resource:string}>}){
  const {resource}=await context.params; if(!resources.has(resource)) return fail("未知资源",404)
  let admin; try { admin=await requireAdmin(resource) } catch { return fail("无权限",403) }
  const body=await request.json(); const db=getDb()
  try{
    if(resource==="admins"){
      if(!/^\w{3,30}$/.test(body.username||"") || String(body.password||"").length<6) return fail("账号需3-30位，密码至少6位")
      const hashed=hashAdminPassword(body.password)
      const [result]=await db.execute<ResultSetHeader>("INSERT INTO admin(username,password,salt,nickname,email,mobile,avatar,status,createtime,updatetime) VALUES(?,?,?,?,?,?,?,'normal',UNIX_TIMESTAMP(),UNIX_TIMESTAMP())",[body.username,hashed.password,hashed.salt,body.nickname||body.username,body.email||"",body.mobile||"",body.avatar||""])
      for(const groupId of body.groupIds||[]) await db.execute("INSERT IGNORE INTO auth_group_access(uid,group_id) VALUES(?,?)",[result.insertId,Number(groupId)])
    }else if(resource==="groups"){
      if(!String(body.name||"").trim()) return fail("权限组名称不能为空")
      await db.execute("INSERT INTO auth_group(pid,name,rules,status) VALUES(?,?,?,?)",[Number(body.pid||0),body.name,(body.rules||[]).join(","),body.status||"normal"])
    }else{
      if(!String(body.name||"").trim()||!String(body.title||"").trim()) return fail("规则标识和标题不能为空")
      await db.execute("INSERT INTO auth_rule(pid,name,title,icon,`condition`,ismenu,weigh,status) VALUES(?,?,?,?,?,?,?,?)",[Number(body.pid||0),body.name,body.title,body.icon||"",body.condition||"",body.ismenu?1:0,Number(body.weigh||0),body.status||"normal"])
    }
    await writeAdminLog(admin,`新增${resource}`,request.nextUrl.pathname,JSON.stringify({...body,password:body.password?"***":undefined}))
    return NextResponse.json({code:1,msg:"新增成功"})
  }catch(e){ return fail(e instanceof Error?e.message:"新增失败") }
}

export async function PATCH(request:NextRequest, context:{params:Promise<{resource:string}>}){
  const {resource}=await context.params; if(!resources.has(resource)) return fail("未知资源",404)
  let admin; try { admin=await requireAdmin(resource) } catch { return fail("无权限",403) }
  const body=await request.json(); const id=Number(body.id); const db=getDb(); if(!id) return fail("缺少ID")
  try{
    if(resource==="admins"){
      const sets=["username=?","nickname=?","email=?","mobile=?","avatar=?","status=?","updatetime=UNIX_TIMESTAMP()"]
      const values=[body.username,body.nickname||body.username,body.email||"",body.mobile||"",body.avatar||"",body.status||"normal"]
      if(body.password){ if(String(body.password).length<6)return fail("密码至少6位"); const hashed=hashAdminPassword(body.password); sets.push("password=?","salt=?"); values.push(hashed.password,hashed.salt) }
      await db.execute(`UPDATE admin SET ${sets.join(",")} WHERE id=?`,[...values,id])
      await db.execute("DELETE FROM auth_group_access WHERE uid=?",[id]); for(const groupId of body.groupIds||[]) await db.execute("INSERT IGNORE INTO auth_group_access(uid,group_id) VALUES(?,?)",[id,Number(groupId)])
    }else if(resource==="groups") await db.execute("UPDATE auth_group SET pid=?,name=?,rules=?,status=? WHERE id=?",[Number(body.pid||0),body.name,(body.rules||[]).join(","),body.status||"normal",id])
    else await db.execute("UPDATE auth_rule SET pid=?,name=?,title=?,icon=?,`condition`=?,ismenu=?,weigh=?,status=? WHERE id=?",[Number(body.pid||0),body.name,body.title,body.icon||"",body.condition||"",body.ismenu?1:0,Number(body.weigh||0),body.status||"normal",id])
    await writeAdminLog(admin,`修改${resource}`,request.nextUrl.pathname,JSON.stringify({...body,password:body.password?"***":undefined})); return NextResponse.json({code:1,msg:"保存成功"})
  }catch(e){ return fail(e instanceof Error?e.message:"保存失败") }
}

export async function DELETE(request:NextRequest, context:{params:Promise<{resource:string}>}){
  const {resource}=await context.params; if(!resources.has(resource)) return fail("未知资源",404)
  let admin; try { admin=await requireAdmin(resource) } catch { return fail("无权限",403) }
  const id=Number(request.nextUrl.searchParams.get("id")); if(!id)return fail("缺少ID"); const db=getDb()
  if(resource==="admins"){ if(id===admin.id)return fail("不能删除当前登录账号"); await db.execute("DELETE FROM auth_group_access WHERE uid=?",[id]); await db.execute("DELETE FROM admin WHERE id=?",[id]) }
  else if(resource==="groups"){ const [[used]]=await db.query<RowDataPacket[]>("SELECT COUNT(*) total FROM auth_group_access WHERE group_id=?",[id]); if(Number(used.total)>0)return fail("权限组仍有管理员，不能删除"); await db.execute("DELETE FROM auth_group WHERE id=?",[id]) }
  else { await db.execute("DELETE FROM auth_rule WHERE id=?",[id]) }
  await writeAdminLog(admin,`删除${resource}`,request.nextUrl.pathname,String(id)); return NextResponse.json({code:1,msg:"删除成功"})
}
