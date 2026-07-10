import { NextResponse } from "next/server"
import { getDashboardStats } from "@/lib/server/fragment-time"

export async function GET() {
  return NextResponse.json({ code: 1, data: await getDashboardStats() })
}
