import type { RowDataPacket } from "mysql2"
import { NextRequest,NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { FRAGMENT_APP_ID } from "@/lib/server/fragment-time"
import { requireAdmin } from "@/lib/server/admin-system"
export async function GET(request:NextRequest){try{await requireAdmin("bills")}catch{return NextResponse.json({code:0,msg:"无权限"},{status:403})};const search=request.nextUrl.searchParams.get("search")||"";const [rows]=await getDb().query<RowDataPacket[]>(`SELECT b.id,b.type,b.uid,f.nickName,f.mobile,b.money,b.balance,b.explain,b.createTime,b.taskId,t.name taskName FROM WechatApp_bill b LEFT JOIN WechatApp_fans f ON f.id=b.uid LEFT JOIN WechatApp_task t ON t.id=b.taskId WHERE f.appid=? AND (?='' OR f.nickName LIKE CONCAT('%',?,'%') OR b.explain LIKE CONCAT('%',?,'%')) ORDER BY b.id DESC LIMIT 500`,[FRAGMENT_APP_ID,search,search,search]);return NextResponse.json({code:1,data:rows})}
