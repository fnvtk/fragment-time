#!/usr/bin/env bash
set -euo pipefail

# 只操作碎片时间独立后台，禁止使用 pkill/killall 等全局命令。
REMOTE='root@43.139.27.93'
PORT=3120
ROOT='/www/wwwroot/self/fragment-time-admin'

ssh -p 22022 -o BatchMode=yes "$REMOTE" "
set -e
pid=\$(ss -ltnp 2>/dev/null | sed -n 's/.*:${PORT}.*pid=\\([0-9]*\\).*/\\1/p' | head -1)
if [ -n \"\$pid\" ] && [ \"\$(readlink -f /proc/\$pid/cwd 2>/dev/null || true)\" = '$ROOT' ]; then
  kill \"\$pid\" 2>/dev/null || true
  sleep 1
fi
cd '$ROOT'
PORT=${PORT} NODE_ENV=production nohup /www/server/nodejs/v22.14.0/bin/pnpm start >/www/wwwlogs/fragment-time-admin.log 2>&1 < /dev/null &
sleep 3
test \"\$(ss -ltnp 2>/dev/null | grep -c ':${PORT} ')\" -gt 0
"

echo 'fragment-time-admin safe restart ok'
