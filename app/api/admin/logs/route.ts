import type { RowDataPacket } from "mysql2"
import { NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>("SELECT id,admin_id,username,url,title,createtime,ip FROM admin_log ORDER BY id DESC LIMIT 300")
  return NextResponse.json({ code: 1, data: rows })
}
