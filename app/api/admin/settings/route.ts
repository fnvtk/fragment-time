import type { RowDataPacket } from "mysql2"
import { NextRequest, NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>(
    "SELECT id,appName,appIcon,introduction,announcement,minMoney,maxMoney,distribution,showTaskMoney,showWithdrawalBtn,showMyMoney FROM WechatApp WHERE id=?",
    [FRAGMENT_APP_ID],
  )
  return NextResponse.json({ code: 1, data: rows[0] || null })
}

export async function PATCH(request: NextRequest) {
  const body = await request.json()
  const allowed = ["appName", "appIcon", "introduction", "announcement", "minMoney", "maxMoney", "distribution", "showTaskMoney", "showWithdrawalBtn", "showMyMoney"] as const
  const entries = allowed.filter((key) => body[key] !== undefined).map((key) => [key, body[key]] as const)
  if (!entries.length) return NextResponse.json({ code: 0, msg: "没有可更新字段" }, { status: 400 })
  await getDb().execute(`UPDATE WechatApp SET ${entries.map(([key]) => `\`${key}\`=?`).join(",")} WHERE id=?`, [...entries.map(([, value]) => value), FRAGMENT_APP_ID])
  return NextResponse.json({ code: 1 })
}
