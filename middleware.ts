import { NextRequest, NextResponse } from "next/server"

async function sessionFrom(token: string) {
  const [payload, signature] = token.split(".")
  if (!payload || !signature) return null
  const key = await crypto.subtle.importKey("raw", new TextEncoder().encode(process.env.ADMIN_SESSION_SECRET || process.env.ADMIN_PASSWORD || "fragment-time-admin-session"), { name: "HMAC", hash: "SHA-256" }, false, ["sign"])
  const signed = await crypto.subtle.sign("HMAC", key, new TextEncoder().encode(payload))
  const expected = btoa(String.fromCharCode(...new Uint8Array(signed))).replace(/\+/g,"-").replace(/\//g,"_").replace(/=+$/g,"")
  if (signature !== expected) return null
  const session = JSON.parse(new TextDecoder().decode(Uint8Array.from(atob(payload.replace(/-/g,"+").replace(/_/g,"/")), c => c.charCodeAt(0))))
  return Number(session.exp) > Date.now() ? session : null
}

export async function middleware(request: NextRequest) {
  if (request.nextUrl.pathname === "/admin/login" || request.nextUrl.pathname === "/api/admin/auth/login") return NextResponse.next()
  const token = request.cookies.get("fragment_admin")?.value
  const session = token ? await sessionFrom(token) : null
  if (!session) {
    if (request.nextUrl.pathname.startsWith("/api/")) return NextResponse.json({ code: 0, msg: "请先登录" }, { status: 401 })
    return NextResponse.redirect(new URL("/admin/login", request.url))
  }
  const segment = request.nextUrl.pathname === "/admin" ? "dashboard" : request.nextUrl.pathname.replace(/^\/api\/admin\//, "").replace(/^\/admin\//, "").split("/")[0]
  const rule = segment === "" || segment === "dashboard" ? "dashboard" : segment
  if (!session.rules.includes("*") && !session.rules.includes(rule) && !["auth"].includes(rule)) {
    if (request.nextUrl.pathname.startsWith("/api/")) return NextResponse.json({ code: 0, msg: "无此功能权限" }, { status: 403 })
    return NextResponse.redirect(new URL("/admin", request.url))
  }
  return NextResponse.next()
}

export const config = { matcher: ["/admin/:path*", "/api/admin/:path*"] }
