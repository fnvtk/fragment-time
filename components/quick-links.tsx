import { Card } from "@/components/ui/card"
import { FileText, Megaphone } from "lucide-react"
import Link from "next/link"

export function QuickLinks() {
  const links = [
    {
      icon: <FileText className="h-6 w-6 text-blue-500" />,
      title: "赚钱攻略",
      description: "了解如何更好地完成任务",
      href: "/guides",
    },
    {
      icon: <Megaphone className="h-6 w-6 text-orange-500" />,
      title: "最新公告",
      description: "查看平台最新消息",
      href: "/announcements",
    },
  ]

  return (
    <div className="grid grid-cols-2 gap-4 p-4">
      {links.map((link) => (
        <Link key={link.title} href={link.href}>
          <Card className="p-4 transition-all hover:bg-gray-50 hover:shadow-md">
            <div className="mb-2">{link.icon}</div>
            <h3 className="font-medium">{link.title}</h3>
            <p className="text-sm text-muted-foreground">{link.description}</p>
          </Card>
        </Link>
      ))}
    </div>
  )
}

