import type { RowDataPacket } from "mysql2"
import { NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>(
    `SELECT w.id,w.uid,f.nickName,f.mobile,w.money,w.status,w.reason,w.createTime
       FROM WechatApp_withdraw w JOIN WechatApp_fans f ON f.id=w.uid
      WHERE f.appid=? ORDER BY w.id DESC LIMIT 300`, [FRAGMENT_APP_ID],
  )
  return NextResponse.json({ code: 1, data: rows })
}
