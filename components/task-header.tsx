import Image from "next/image"
import { Card } from "@/components/ui/card"

export function TaskHeader() {
  return (
    <div className="sticky top-0 z-10 bg-primary pb-4">
      <div className="flex items-center justify-between px-4 py-2">
        <h1 className="text-xl font-bold text-white">碎片时间</h1>
        <div className="flex items-center gap-2">
          <button className="rounded-full bg-primary-foreground/10 p-2">
            <span className="sr-only">菜单</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="text-white"
            >
              <path d="M3 12h18M3 6h18M3 18h18" />
            </svg>
          </button>
          <button className="rounded-full bg-primary-foreground/10 p-2">
            <span className="sr-only">设置</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="text-white"
            >
              <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>
      </div>
      <Card className="mx-4 overflow-hidden bg-gradient-to-br from-primary to-purple-600">
        <div className="relative p-4">
          <Image
            src="/placeholder.svg?height=100&width=100"
            alt="Coins"
            width={100}
            height={100}
            className="absolute right-0 top-0 opacity-20"
          />
          <h2 className="mb-2 text-2xl font-bold text-white">每天几分钟</h2>
          <p className="text-primary-foreground">轻松赚收益</p>
        </div>
      </Card>
    </div>
  )
}

