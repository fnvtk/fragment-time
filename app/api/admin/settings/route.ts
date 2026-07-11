import type { RowDataPacket } from "mysql2"
import { NextRequest, NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>(
    "SELECT id,appName,appIcon,appId,introduction,announcement,minMoney,maxMoney,distribution,showTaskMoney,showWithdrawalBtn,showMyMoney,isSeparate,auditMode,auditModeUrl,pay,other FROM WechatApp WHERE id=?",
    [FRAGMENT_APP_ID],
  )
  return NextResponse.json({ code: 1, data: rows[0] || null })
}

export async function PATCH(request: NextRequest) {
  const body = await request.json()
  const allowed = ["appName", "appIcon", "appId", "introduction", "announcement", "minMoney", "maxMoney", "distribution", "showTaskMoney", "showWithdrawalBtn", "showMyMoney", "isSeparate", "auditMode", "auditModeUrl", "pay", "other"] as const
  const entries = allowed.filter((key) => body[key] !== undefined).map((key) => {
    if (key === "pay" || key === "other") {
      try { return [key, typeof body[key] === "string" ? JSON.parse(body[key]) : body[key]] as const } catch { throw new Error(`${key} 必须是合法 JSON`) }
    }
    return [key, body[key]] as const
  })
  if (!entries.length) return NextResponse.json({ code: 0, msg: "没有可更新字段" }, { status: 400 })
  await getDb().execute(`UPDATE WechatApp SET ${entries.map(([key]) => `\`${key}\`=?`).join(",")} WHERE id=?`, [...entries.map(([, value]) => value), FRAGMENT_APP_ID])
  return NextResponse.json({ code: 1 })
}
