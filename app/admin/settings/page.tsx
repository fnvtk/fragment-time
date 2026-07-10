import { SettingsForm } from "@/components/admin/settings-form"
export default function SettingsPage() { return <div className="p-4 lg:p-6"><div className="mb-6"><h1 className="text-2xl font-bold">系统设置</h1><p className="text-sm text-muted-foreground">配置直接写入碎片时间小程序数据库</p></div><SettingsForm /></div> }
