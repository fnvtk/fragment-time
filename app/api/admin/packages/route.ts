import type { RowDataPacket } from "mysql2"
import { NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>(
    `SELECT p.id,p.name,p.remarks,p.status,p.createTime,COUNT(l.id) total,SUM(l.status=0) available
       FROM WechatApp_dataPacket p LEFT JOIN WechatApp_dataPacketList l ON l.dataPacketId=p.id AND l.appId=p.appId
      WHERE p.appId=? AND p.isDel=0 GROUP BY p.id ORDER BY p.id DESC`, [FRAGMENT_APP_ID],
  )
  return NextResponse.json({ code: 1, data: rows })
}
