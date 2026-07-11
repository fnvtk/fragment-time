import type { RowDataPacket } from "mysql2"
import { NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"
import { requireAdmin, writeAdminLog } from "@/lib/server/admin-system"

export async function GET() {
  const [rows] = await getDb().query<RowDataPacket[]>(
    `SELECT w.id,w.uid,f.nickName,f.mobile,w.money,w.status,w.reason,w.createTime
       FROM WechatApp_withdraw w JOIN WechatApp_fans f ON f.id=w.uid
      WHERE f.appid=? ORDER BY w.id DESC LIMIT 300`, [FRAGMENT_APP_ID],
  )
  return NextResponse.json({ code: 1, data: rows })
}

export async function PATCH(request: NextRequest) {
  let admin; try { admin = await requireAdmin("withdrawals") } catch { return NextResponse.json({ code: 0, msg: "无权限" }, { status: 403 }) }
  const body = await request.json(); const id = Number(body.id); const status = Number(body.status)
  if (!id || ![0,1,2].includes(status)) return NextResponse.json({ code: 0, msg: "参数错误" }, { status: 400 })
  await getDb().execute("UPDATE WechatApp_withdraw w JOIN WechatApp_fans f ON f.id=w.uid SET w.status=?,w.reason=?,w.updataTime=NOW() WHERE w.id=? AND f.appid=?", [status, body.reason || "", id, FRAGMENT_APP_ID])
  await writeAdminLog(admin, status === 1 ? "通过提现" : status === 2 ? "拒绝提现" : "重置提现", "/api/admin/withdrawals", JSON.stringify({ id, status }))
  return NextResponse.json({ code: 1, msg: "处理成功" })
}
