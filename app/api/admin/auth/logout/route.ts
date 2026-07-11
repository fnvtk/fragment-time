import { NextResponse } from "next/server"
export async function POST() { const response = NextResponse.json({ code: 1 }); response.cookies.delete("fragment_admin"); return response }
