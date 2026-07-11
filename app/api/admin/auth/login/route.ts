import { NextRequest, NextResponse } from "next/server"
import { createSessionToken, getAdminSession, writeAdminLog } from "@/lib/server/admin-system"

export async function POST(request: NextRequest) {
  const { username, password } = await request.json()
  const admin = await getAdminSession(String(username || ""), String(password || ""))
  if (!admin) return NextResponse.json({ code: 0, msg: "账号或密码错误" }, { status: 401 })
  const response = NextResponse.json({ code: 1, data: admin })
  response.cookies.set("fragment_admin", createSessionToken(admin), { httpOnly: true, secure: true, sameSite: "lax", path: "/", maxAge: 28800 })
  await writeAdminLog(admin, "登录新后台", "/admin/login", "")
  return response
}
