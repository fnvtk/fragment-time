import { NextRequest, NextResponse } from "next/server"
import { getUsers } from "@/lib/server/fragment-time"

export async function GET(request: NextRequest) {
  const search = request.nextUrl.searchParams.get("search") || ""
  return NextResponse.json({ code: 1, data: await getUsers(search) })
}
