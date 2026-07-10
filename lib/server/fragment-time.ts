import type { RowDataPacket } from "mysql2"
import { getDb } from "./db"

export const FRAGMENT_APP_ID = 1

export async function getDashboardStats() {
  const db = getDb()
  const [[users], [tasks], [packages], [receives], [income], [withdrawals]] = await Promise.all([
    db.query<RowDataPacket[]>("SELECT COUNT(*) total FROM WechatApp_fans WHERE appid=?", [FRAGMENT_APP_ID]),
    db.query<RowDataPacket[]>("SELECT COUNT(*) total, SUM(isDel=0 AND isShow=1) active FROM WechatApp_task WHERE appId=?", [FRAGMENT_APP_ID]),
    db.query<RowDataPacket[]>("SELECT COUNT(*) total FROM WechatApp_dataPacket WHERE appId=? AND isDel=0", [FRAGMENT_APP_ID]),
    db.query<RowDataPacket[]>("SELECT COUNT(*) total, SUM(status=2) completed FROM WechatApp_taskReceive WHERE taskId IN (SELECT id FROM WechatApp_task WHERE appId=?)", [FRAGMENT_APP_ID]),
    db.query<RowDataPacket[]>("SELECT COALESCE(SUM(money),0) total FROM WechatApp_bill WHERE uid IN (SELECT id FROM WechatApp_fans WHERE appid=?)", [FRAGMENT_APP_ID]),
    db.query<RowDataPacket[]>("SELECT COUNT(*) total, COALESCE(SUM(money),0) amount FROM WechatApp_withdraw WHERE uid IN (SELECT id FROM WechatApp_fans WHERE appid=?)", [FRAGMENT_APP_ID]),
  ])

  return {
    users: Number(users[0]?.total || 0),
    tasks: Number(tasks[0]?.total || 0),
    activeTasks: Number(tasks[0]?.active || 0),
    packages: Number(packages[0]?.total || 0),
    receives: Number(receives[0]?.total || 0),
    completed: Number(receives[0]?.completed || 0),
    income: Number(income[0]?.total || 0),
    withdrawals: Number(withdrawals[0]?.total || 0),
    withdrawalAmount: Number(withdrawals[0]?.amount || 0),
  }
}

export async function getTasks(search = "") {
  const [rows] = await getDb().query<RowDataPacket[]>(
    `SELECT id, name, brief, type, reward, isShow, isHot, status, receiveNum, completeNum, deadline
       FROM WechatApp_task
      WHERE appId=? AND isDel=0 AND (?='' OR name LIKE CONCAT('%',?,'%'))
      ORDER BY sort DESC,id DESC LIMIT 200`,
    [FRAGMENT_APP_ID, search, search],
  )
  return rows
}

export async function getUsers(search = "") {
  const [rows] = await getDb().query<RowDataPacket[]>(
    `SELECT id,nickName,mobile,avatarUrl,balance,totalIncome,createTime
       FROM WechatApp_fans
      WHERE appid=? AND (?='' OR nickName LIKE CONCAT('%',?,'%') OR mobile LIKE CONCAT('%',?,'%'))
      ORDER BY id DESC LIMIT 200`,
    [FRAGMENT_APP_ID, search, search, search],
  )
  return rows
}
