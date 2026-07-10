import { TaskList } from "@/components/task-list"
import { WelcomeBanner } from "@/components/welcome-banner"
import { QuickLinks } from "@/components/quick-links"
import { BottomNav } from "@/components/bottom-nav"

export default function HomePage() {
  return (
    <main className="flex min-h-screen flex-col bg-gray-50 pb-16">
      <WelcomeBanner />
      <QuickLinks />
      <TaskList />
      <BottomNav />
    </main>
  )
}

