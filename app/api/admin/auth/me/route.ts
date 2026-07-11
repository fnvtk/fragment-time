import { NextResponse } from "next/server"
import { requireAdmin } from "@/lib/server/admin-system"
export async function GET(){ try { return NextResponse.json({code:1,data:await requireAdmin()}) } catch { return NextResponse.json({code:0,msg:"请先登录"},{status:401}) } }
