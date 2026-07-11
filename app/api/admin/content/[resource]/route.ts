import type { RowDataPacket } from "mysql2"
import { NextRequest, NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { requireAdmin, writeAdminLog } from "@/lib/server/admin-system"

const resources=new Set(["attachments","categories","configs","logs"])
const fail=(msg:string,status=400)=>NextResponse.json({code:0,msg},{status})
export async function GET(request:NextRequest,context:{params:Promise<{resource:string}>}){
 const {resource}=await context.params;if(!resources.has(resource))return fail("未知资源",404)
 try{await requireAdmin(resource)}catch{return fail("无权限",403)}
 const db=getDb();let rows:RowDataPacket[]=[]
 if(resource==="attachments")[rows]=await db.query<RowDataPacket[]>("SELECT * FROM attachment ORDER BY id DESC LIMIT 500")
 else if(resource==="categories")[rows]=await db.query<RowDataPacket[]>("SELECT * FROM category ORDER BY weigh DESC,id DESC")
 else if(resource==="configs")[rows]=await db.query<RowDataPacket[]>("SELECT * FROM config ORDER BY `group`,id")
 else [rows]=await db.query<RowDataPacket[]>("SELECT * FROM admin_log ORDER BY id DESC LIMIT 500")
 return NextResponse.json({code:1,data:rows})
}
export async function POST(request:NextRequest,context:{params:Promise<{resource:string}>}){
 const {resource}=await context.params;if(!["categories","configs"].includes(resource))return fail("不支持新增")
 let admin;try{admin=await requireAdmin(resource)}catch{return fail("无权限",403)}
 const b=await request.json(),db=getDb()
 if(resource==="categories")await db.execute("INSERT INTO category(pid,type,name,nickname,flag,image,keywords,description,diyname,createtime,updatetime,weigh,status) VALUES(?,?,?,?,?,?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),?,?)",[Number(b.pid||0),b.type||"default",b.name,b.nickname||"",b.flag||"",b.image||"",b.keywords||"",b.description||"",b.diyname||"",Number(b.weigh||0),b.status||"normal"])
 else await db.execute("INSERT INTO config(name,`group`,title,tip,type,value,content,rule,extend,setting) VALUES(?,?,?,?,?,?,?,?,?,?)",[b.name,b.group||"basic",b.title,b.tip||"",b.type||"string",b.value||"",b.content||"",b.rule||"",b.extend||"",b.setting||""])
 await writeAdminLog(admin,`新增${resource}`,request.nextUrl.pathname,JSON.stringify(b));return NextResponse.json({code:1,msg:"新增成功"})
}
export async function PATCH(request:NextRequest,context:{params:Promise<{resource:string}>}){
 const {resource}=await context.params;if(!["categories","configs"].includes(resource))return fail("不支持修改")
 let admin;try{admin=await requireAdmin(resource)}catch{return fail("无权限",403)}
 const b=await request.json(),id=Number(b.id),db=getDb();if(!id)return fail("缺少ID")
 if(resource==="categories")await db.execute("UPDATE category SET pid=?,type=?,name=?,nickname=?,flag=?,image=?,keywords=?,description=?,diyname=?,updatetime=UNIX_TIMESTAMP(),weigh=?,status=? WHERE id=?",[Number(b.pid||0),b.type||"default",b.name,b.nickname||"",b.flag||"",b.image||"",b.keywords||"",b.description||"",b.diyname||"",Number(b.weigh||0),b.status||"normal",id])
 else await db.execute("UPDATE config SET name=?,`group`=?,title=?,tip=?,type=?,value=?,content=?,rule=?,extend=?,setting=? WHERE id=?",[b.name,b.group||"basic",b.title,b.tip||"",b.type||"string",b.value||"",b.content||"",b.rule||"",b.extend||"",b.setting||"",id])
 await writeAdminLog(admin,`修改${resource}`,request.nextUrl.pathname,JSON.stringify(b));return NextResponse.json({code:1,msg:"保存成功"})
}
export async function DELETE(request:NextRequest,context:{params:Promise<{resource:string}>}){
 const {resource}=await context.params;if(!resources.has(resource))return fail("未知资源",404)
 let admin;try{admin=await requireAdmin(resource)}catch{return fail("无权限",403)}
 const id=Number(request.nextUrl.searchParams.get("id"));if(!id)return fail("缺少ID")
 const table={attachments:"attachment",categories:"category",configs:"config",logs:"admin_log"}[resource]!
 await getDb().execute(`DELETE FROM \`${table}\` WHERE id=?`,[id]);await writeAdminLog(admin,`删除${resource}`,request.nextUrl.pathname,String(id));return NextResponse.json({code:1,msg:"删除成功"})
}
