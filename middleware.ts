import { NextRequest, NextResponse } from "next/server"

export function middleware(request: NextRequest) {
  const auth = request.headers.get("authorization")
  const expected = `Basic ${btoa(`${process.env.ADMIN_USER || "admin"}:${process.env.ADMIN_PASSWORD || "change-me"}`)}`
  if (auth !== expected) return new NextResponse("需要管理员登录", { status: 401, headers: { "WWW-Authenticate": 'Basic realm="Fragment Time Admin"' } })
  return NextResponse.next()
}

export const config = { matcher: ["/admin/:path*", "/api/admin/:path*"] }
