import crypto from "node:crypto"
import type { RowDataPacket } from "mysql2"
import { getDb } from "./db"
import { cookies } from "next/headers"

export type AdminSession = { id: number; username: string; nickname: string; rules: string[]; exp: number }

const secret = () => process.env.ADMIN_SESSION_SECRET || process.env.ADMIN_PASSWORD || "fragment-time-admin-session"
const b64 = (value: string) => Buffer.from(value).toString("base64url")
const sign = (value: string) => crypto.createHmac("sha256", secret()).update(value).digest("base64url")

export function createSessionToken(session: Omit<AdminSession, "exp">) {
  const payload = b64(JSON.stringify({ ...session, exp: Date.now() + 8 * 60 * 60 * 1000 }))
  return `${payload}.${sign(payload)}`
}

export function readSessionToken(token?: string | null): AdminSession | null {
  if (!token) return null
  const [payload, signature] = token.split(".")
  if (!payload || !signature) return null
  const expected = sign(payload)
  if (signature.length !== expected.length || !crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected))) return null
  const session = JSON.parse(Buffer.from(payload, "base64url").toString()) as AdminSession
  return session.exp > Date.now() ? session : null
}

export function hashAdminPassword(password: string, salt = crypto.randomBytes(8).toString("hex")) {
  return { salt, password: crypto.createHash("md5").update(crypto.createHash("md5").update(password).digest("hex") + salt).digest("hex") }
}

export function verifyAdminPassword(input: string, stored: string, salt: string) {
  const value = salt ? hashAdminPassword(input, salt).password : crypto.createHash("md5").update(input).digest("hex")
  return value.length === stored.length && crypto.timingSafeEqual(Buffer.from(value), Buffer.from(stored))
}

export async function getAdminSession(username: string, password: string) {
  const db = getDb()
  const [admins] = await db.query<RowDataPacket[]>("SELECT * FROM admin WHERE username=? LIMIT 1", [username])
  const admin = admins[0]
  if (!admin || admin.status !== "normal" || !verifyAdminPassword(password, admin.password, admin.salt || "")) return null
  const [groups] = await db.query<RowDataPacket[]>(`SELECT g.rules FROM auth_group g JOIN auth_group_access a ON a.group_id=g.id WHERE a.uid=? AND g.status='normal'`, [admin.id])
  const rules = [...new Set(groups.flatMap((group) => String(group.rules || "").split(",").filter(Boolean)))]
  await db.execute("UPDATE admin SET loginfailure=0,logintime=UNIX_TIMESTAMP(),loginip='' WHERE id=?", [admin.id])
  return { id: Number(admin.id), username: String(admin.username), nickname: String(admin.nickname || admin.username), rules }
}

export async function writeAdminLog(admin: Pick<AdminSession, "id" | "username">, title: string, url: string, content = "", ip = "") {
  await getDb().execute("INSERT INTO admin_log(admin_id,username,url,title,content,ip,useragent,createtime) VALUES(?,?,?,?,?,?,?,UNIX_TIMESTAMP())", [admin.id, admin.username, url, title, content, ip, "fragment-time-admin"])
}

export async function requireAdmin(rule?: string) {
  const token = (await cookies()).get("fragment_admin")?.value
  const session = readSessionToken(token)
  if (!session) throw new Error("UNAUTHORIZED")
  if (rule && !session.rules.includes("*") && !session.rules.includes(rule)) throw new Error("FORBIDDEN")
  return session
}
