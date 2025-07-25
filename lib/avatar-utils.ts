const AVATAR_STYLES = ["avataaars", "bottts", "micah", "thumbs", "lorelei"] as const

export function generateAvatar(seed: string) {
  // Randomly select an avatar style for each user
  const style = AVATAR_STYLES[Math.floor(Math.random() * AVATAR_STYLES.length)]
  return `https://api.dicebear.com/7.x/${style}/svg?seed=${seed}`
}

