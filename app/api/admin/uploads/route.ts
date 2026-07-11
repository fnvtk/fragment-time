import crypto from "node:crypto"
import fs from "node:fs/promises"
import path from "node:path"
import { NextRequest,NextResponse } from "next/server"
import { getDb } from "@/lib/server/db"
import { requireAdmin,writeAdminLog } from "@/lib/server/admin-system"

export const runtime="nodejs"
export async function POST(request:NextRequest){
 let admin;try{admin=await requireAdmin("attachments")}catch{return NextResponse.json({code:0,msg:"无权限"},{status:403})}
 const data=await request.formData(),file=data.get("file")
 if(!(file instanceof File))return NextResponse.json({code:0,msg:"请选择文件"},{status:400})
 if(file.size>20*1024*1024)return NextResponse.json({code:0,msg:"文件不能超过20MB"},{status:400})
 const bytes=Buffer.from(await file.arrayBuffer()),ext=path.extname(file.name).replace(/[^.a-zA-Z0-9]/g,"").toLowerCase(),name=`${Date.now()}-${crypto.randomBytes(6).toString("hex")}${ext}`,dir=path.join(process.cwd(),"public","uploads","admin")
 await fs.mkdir(dir,{recursive:true});await fs.writeFile(path.join(dir,name),bytes)
 const url=`/uploads/admin/${name}`,sha1=crypto.createHash("sha1").update(bytes).digest("hex")
 await getDb().execute("INSERT INTO attachment(admin_id,url,filesize,mimetype,createtime,updatetime,uploadtime,storage,sha1) VALUES(?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'local',?)",[admin.id,url,file.size,file.type||"application/octet-stream",sha1])
 await writeAdminLog(admin,"上传素材","/api/admin/uploads",file.name);return NextResponse.json({code:1,msg:"上传成功",data:{url}})
}
