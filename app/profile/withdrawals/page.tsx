import { Card } from "@/components/ui/card"
import Link from "next/link"

const withdrawals = [
  {
    id: 1,
    amount: 0.39,
    status: "提现成功",
    balance: 0.0,
    requestTime: "2024-11-24 18:51:23",
    processTime: "2024-11-24 19:25:20",
  },
  {
    id: 2,
    amount: 0.1,
    status: "提现成功",
    balance: 0.0,
    requestTime: "2023-04-18 14:58:30",
    processTime: "2023-04-20 17:46:22",
  },
]

export default function WithdrawalsPage() {
  return (
    <main className="min-h-screen bg-gray-50">
      <header className="sticky top-0 z-10 flex items-center gap-2 bg-primary p-4 text-white">
        <Link href="/profile" className="rounded-full bg-white/10 p-2">
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
          >
            <path d="m15 18-6-6 6-6" />
          </svg>
        </Link>
        <h1 className="text-lg font-medium">提现记录</h1>
      </header>

      <div className="space-y-4 p-4">
        {withdrawals.map((record) => (
          <Card key={record.id} className="bg-green-50 p-4">
            <div className="mb-2 flex items-center justify-between">
              <span className="font-medium">状态：{record.status}</span>
            </div>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span>提现金额：</span>
                <span>{record.amount}</span>
              </div>
              <div className="flex justify-between">
                <span>余额：</span>
                <span>{record.balance}</span>
              </div>
              <div className="flex justify-between">
                <span>提现时间：</span>
                <span>{record.requestTime}</span>
              </div>
              <div className="flex justify-between">
                <span>处理时间：</span>
                <span>{record.processTime}</span>
              </div>
            </div>
          </Card>
        ))}
      </div>
    </main>
  )
}

