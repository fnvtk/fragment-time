import { NextRequest, NextResponse } from "next/server"
import { getTasks, FRAGMENT_APP_ID } from "@/lib/server/fragment-time"
import { getDb } from "@/lib/server/db"

export async function GET(request: NextRequest) {
  const search = request.nextUrl.searchParams.get("search") || ""
  return NextResponse.json({ code: 1, data: await getTasks(search) })
}

export async function POST(request: NextRequest) {
  const body = await request.json()
  if (!body.name?.trim()) return NextResponse.json({ code: 0, msg: "任务名称不能为空" }, { status: 400 })
  const [result] = await getDb().execute(
    `INSERT INTO WechatApp_task (appId,name,brief,type,reward,isShow,isHot,status,isDel,createTime,updataTime)
     VALUES (?,?,?,?,?,1,?,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())`,
    [FRAGMENT_APP_ID, body.name.trim(), body.brief || "", Number(body.type || 0), Number(body.reward || 0), body.isHot ? 1 : 0],
  )
  return NextResponse.json({ code: 1, data: result })
}
