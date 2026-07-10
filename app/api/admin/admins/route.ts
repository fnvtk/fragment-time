import type { RowDataPacket } from "mysql2"
import { NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>("SELECT id,username,nickname,status,loginfailure,logintime,loginip,createtime,updatetime FROM admin ORDER BY id DESC")
  return NextResponse.json({ code: 1, data: rows })
}
