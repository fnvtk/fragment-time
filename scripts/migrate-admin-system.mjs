import mysql from "mysql2/promise"
import crypto from "node:crypto"

const db = await mysql.createConnection({ host: process.env.FRAGMENT_DB_HOST, port: Number(process.env.FRAGMENT_DB_PORT || 3306), user: process.env.FRAGMENT_DB_USER, password: process.env.FRAGMENT_DB_PASSWORD, database: process.env.FRAGMENT_DB_NAME, multipleStatements: true })
await db.query(`
CREATE TABLE IF NOT EXISTS admin (id int unsigned NOT NULL AUTO_INCREMENT,username varchar(30) NOT NULL,password varchar(32) NOT NULL,salt varchar(30) NOT NULL DEFAULT '',nickname varchar(50) NOT NULL DEFAULT '',avatar varchar(255) NOT NULL DEFAULT '',email varchar(100) NOT NULL DEFAULT '',mobile varchar(20) NOT NULL DEFAULT '',loginfailure int NOT NULL DEFAULT 0,logintime bigint NULL,loginip varchar(50) NULL,createtime bigint NULL,updatetime bigint NULL,token varchar(59) NOT NULL DEFAULT '',status varchar(30) NOT NULL DEFAULT 'normal',PRIMARY KEY(id),UNIQUE KEY(username)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS auth_group (id int unsigned NOT NULL AUTO_INCREMENT,pid int NOT NULL DEFAULT 0,name varchar(100) NOT NULL DEFAULT '',rules text NOT NULL,status varchar(30) NOT NULL DEFAULT 'normal',PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS auth_group_access (uid int unsigned NOT NULL,group_id int unsigned NOT NULL,UNIQUE KEY(uid,group_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS auth_rule (id int unsigned NOT NULL AUTO_INCREMENT,pid int NOT NULL DEFAULT 0,name varchar(100) NOT NULL DEFAULT '',title varchar(100) NOT NULL DEFAULT '',icon varchar(100) NOT NULL DEFAULT '',\`condition\` text NULL,ismenu tinyint NOT NULL DEFAULT 1,weigh int NOT NULL DEFAULT 0,status varchar(30) NOT NULL DEFAULT 'normal',PRIMARY KEY(id),UNIQUE KEY(name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS admin_log (id int unsigned NOT NULL AUTO_INCREMENT,admin_id int unsigned NOT NULL DEFAULT 0,username varchar(30) NOT NULL DEFAULT '',url varchar(1500) NOT NULL DEFAULT '',title varchar(100) NOT NULL DEFAULT '',content text NOT NULL,ip varchar(50) NOT NULL DEFAULT '',useragent varchar(255) NOT NULL DEFAULT '',createtime bigint NULL,PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS attachment (id int unsigned NOT NULL AUTO_INCREMENT,admin_id int unsigned NOT NULL DEFAULT 0,user_id int unsigned NOT NULL DEFAULT 0,url varchar(255) NOT NULL DEFAULT '',imagewidth varchar(30) NOT NULL DEFAULT '',imageheight varchar(30) NOT NULL DEFAULT '',imagetype varchar(30) NOT NULL DEFAULT '',imageframes int unsigned NOT NULL DEFAULT 0,filesize int unsigned NOT NULL DEFAULT 0,mimetype varchar(100) NOT NULL DEFAULT '',extparam varchar(255) NOT NULL DEFAULT '',createtime bigint NULL,updatetime bigint NULL,uploadtime bigint NULL,storage varchar(100) NOT NULL DEFAULT 'local',sha1 varchar(40) NOT NULL DEFAULT '',PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS category (id int unsigned NOT NULL AUTO_INCREMENT,pid int unsigned NOT NULL DEFAULT 0,type varchar(30) NOT NULL DEFAULT 'default',name varchar(100) NOT NULL DEFAULT '',nickname varchar(100) NOT NULL DEFAULT '',flag varchar(255) NOT NULL DEFAULT '',image varchar(255) NOT NULL DEFAULT '',keywords varchar(255) NOT NULL DEFAULT '',description varchar(255) NOT NULL DEFAULT '',diyname varchar(30) NOT NULL DEFAULT '',createtime bigint NULL,updatetime bigint NULL,weigh int NOT NULL DEFAULT 0,status varchar(30) NOT NULL DEFAULT 'normal',PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS config (id int unsigned NOT NULL AUTO_INCREMENT,name varchar(30) NOT NULL DEFAULT '',\`group\` varchar(30) NOT NULL DEFAULT 'basic',title varchar(100) NOT NULL DEFAULT '',tip varchar(255) NOT NULL DEFAULT '',type varchar(30) NOT NULL DEFAULT 'string',value text NOT NULL,content text NULL,rule varchar(100) NOT NULL DEFAULT '',extend varchar(255) NOT NULL DEFAULT '',setting varchar(255) NOT NULL DEFAULT '',PRIMARY KEY(id),UNIQUE KEY(name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
`)
const [columns] = await db.query("SHOW COLUMNS FROM admin LIKE 'nickname'")
if (!columns.length) await db.query("ALTER TABLE admin ADD nickname varchar(50) NOT NULL DEFAULT '' AFTER salt")
const rules = [
  ['dashboard','控制台','LayoutDashboard'],['users','小程序用户','Users'],['tasks','任务管理','ClipboardList'],['packages','数据包管理','Package'],['withdrawals','提现管理','WalletCards'],['bills','收费与账单','ReceiptText'],['settings','小程序设置','Settings'],['admins','管理员','Shield'],['groups','权限组','UsersRound'],['rules','菜单规则','ListTree'],['attachments','素材管理','Images'],['categories','分类管理','FolderTree'],['configs','系统配置','SlidersHorizontal'],['logs','操作日志','ScrollText'],['stats','统计报表','BarChart3'],['profile','个人资料','UserCog']
]
for (let i=0;i<rules.length;i++) await db.query("INSERT INTO auth_rule(name,title,icon,ismenu,weigh,status) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),icon=VALUES(icon),ismenu=1", [...rules[i],1,100-i,'normal'])
await db.query("INSERT INTO auth_group(id,pid,name,rules,status) VALUES(1,0,'超级管理员','*','normal') ON DUPLICATE KEY UPDATE name='超级管理员',rules='*',status='normal'")
try {
  const [legacy] = await db.query("SELECT Username,Password FROM AdminAccount")
  for (const row of legacy) await db.query("INSERT IGNORE INTO admin(username,password,salt,nickname,createtime,status) VALUES(?,?,?, ?,UNIX_TIMESTAMP(),'normal')", [row.Username,row.Password,'',row.Username])
} catch {}
const adminPassword = process.env.ADMIN_PASSWORD || 'fragment-time-admin'
const salt = crypto.randomBytes(8).toString('hex')
const password = crypto.createHash('md5').update(crypto.createHash('md5').update(adminPassword).digest('hex') + salt).digest('hex')
await db.query("INSERT INTO admin(username,password,salt,nickname,createtime,status) VALUES('admin',?,?, '管理员',UNIX_TIMESTAMP(),'normal') ON DUPLICATE KEY UPDATE password=VALUES(password),salt=VALUES(salt),nickname='管理员',status='normal'", [password,salt])
const [[admin]] = await db.query("SELECT id FROM admin WHERE username='admin'")
await db.query("INSERT IGNORE INTO auth_group_access(uid,group_id) VALUES(?,1)",[admin.id])
await db.end()
console.log('admin-system-migration-ok')
