import { NextRequest, NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"

export async function PATCH(request: NextRequest, context: { params: Promise<{ id: string }> }) {
  const { id } = await context.params
  const body = await request.json()
  const allowed = ["taskCode", "dataPacketId", "maxUserNum", "unRepeat", "type", "name", "brief", "pic", "isShow", "isHot", "sort", "status", "reward", "addReward", "notice", "explain", "steps", "deadline", "taskTime", "drawType", "recoveryTask", "drawNum", "apikey"] as const
  const entries = allowed.filter((key) => body[key] !== undefined).map((key) => [key, body[key]] as const)
  if (!entries.length) return NextResponse.json({ code: 0, msg: "没有可更新字段" }, { status: 400 })
  const sql = entries.map(([key]) => `\`${key}\`=?`).join(",")
  await getDb().execute(`UPDATE WechatApp_task SET ${sql},updataTime=UNIX_TIMESTAMP() WHERE id=? AND appId=? AND isDel=0`, [...entries.map(([, value]) => value), Number(id), FRAGMENT_APP_ID])
  return NextResponse.json({ code: 1 })
}

export async function DELETE(_request: NextRequest, context: { params: Promise<{ id: string }> }) {
  const { id } = await context.params
  await getDb().execute("UPDATE WechatApp_task SET isDel=1,deleteTime=UNIX_TIMESTAMP() WHERE id=? AND appId=?", [Number(id), FRAGMENT_APP_ID])
  return NextResponse.json({ code: 1 })
}
