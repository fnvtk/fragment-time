import type { ResultSetHeader, RowDataPacket } from "mysql2"
import { NextRequest, NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"
import { requireAdmin, writeAdminLog } from "@/lib/server/admin-system"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>(
    `SELECT p.id,p.name,p.remarks,p.status,p.createTime,COUNT(l.id) total,SUM(l.status=0) available
       FROM WechatApp_dataPacket p LEFT JOIN WechatApp_dataPacketList l ON l.dataPacketId=p.id AND l.appId=p.appId
      WHERE p.appId=? AND p.isDel=0 GROUP BY p.id ORDER BY p.id DESC`, [FRAGMENT_APP_ID],
  )
  return NextResponse.json({ code: 1, data: rows })
}

export async function POST(request: NextRequest) { let admin; try { admin=await requireAdmin("packages") } catch { return NextResponse.json({code:0,msg:"无权限"},{status:403}) }; const b=await request.json(); if(!b.name)return NextResponse.json({code:0,msg:"名称不能为空"},{status:400}); const [result]=await getDb().execute<ResultSetHeader>("INSERT INTO WechatApp_dataPacket(appId,name,remarks,status,isDel,type,createTime,updateTime) VALUES(?,?,?,1,0,?,NOW(),NOW())",[FRAGMENT_APP_ID,b.name,b.remarks||"",Number(b.type||0)]); await writeAdminLog(admin,"新增数据包","/api/admin/packages",b.name); return NextResponse.json({code:1,data:result}) }
export async function PATCH(request: NextRequest) { let admin; try { admin=await requireAdmin("packages") } catch { return NextResponse.json({code:0,msg:"无权限"},{status:403}) }; const b=await request.json(); await getDb().execute("UPDATE WechatApp_dataPacket SET name=?,remarks=?,status=?,type=?,updateTime=NOW() WHERE id=? AND appId=? AND isDel=0",[b.name,b.remarks||"",Number(b.status),Number(b.type||0),Number(b.id),FRAGMENT_APP_ID]); await writeAdminLog(admin,"修改数据包","/api/admin/packages",String(b.id)); return NextResponse.json({code:1}) }
