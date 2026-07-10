import mysql, { type Pool } from "mysql2/promise"

let pool: Pool | undefined

export function getDb() {
  if (!pool) {
    const host = process.env.FRAGMENT_DB_HOST
    const user = process.env.FRAGMENT_DB_USER
    const password = process.env.FRAGMENT_DB_PASSWORD
    const database = process.env.FRAGMENT_DB_NAME || "fragment_time"

    if (!host || !user || !password) {
      throw new Error("缺少 FRAGMENT_DB_HOST/USER/PASSWORD 数据库配置")
    }

    pool = mysql.createPool({
      host,
      port: Number(process.env.FRAGMENT_DB_PORT || 3306),
      user,
      password,
      database,
      charset: "utf8mb4",
      connectionLimit: 8,
      enableKeepAlive: true,
    })
  }

  return pool
}
