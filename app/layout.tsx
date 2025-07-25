import type React from "react"
import { Noto_Sans_SC } from "next/font/google"
import "./globals.css"

const notoSansSC = Noto_Sans_SC({
  subsets: ["latin"],
  weight: ["400", "500", "700"],
})

export const metadata = {
  title: "碎片时间",
  description: "每天几分钟，轻松赚收益",
    generator: 'v0.dev'
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="zh">
      <body className={notoSansSC.className}>{children}</body>
    </html>
  )
}

