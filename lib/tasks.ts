export const tasks = [
  {
    id: 1,
    title: "微信挂机任务",
    type: "wechat",
    icon: "https://api.iconify.design/ri:wechat-fill.svg?color=%2307c160",
    description: "添加微信并保持在线状态",
    reward: "1.00",
    quantity: 42645,
    remaining: 42545,
    deadline: "2025-02-29",
    duration: "7200",
    steps: [
      "使用个人微信号，登录并添加提供的微信号",
      "添加后保持微信在线状态",
      "系统将自动检测在线状态，保持在线即可获得奖励",
      "每日奖励将自动发放到账户",
    ],
    notice: ["微信号需要实名认证", "在线时长达到要求后自动发放奖励", "请勿使用小号或批量账号"],
  },
  {
    id: 2,
    title: "抖音点赞任务",
    type: "tiktok",
    icon: "https://api.iconify.design/simple-icons:tiktok.svg?color=%23000000",
    description: "使用抖音扫码完成点赞",
    reward: "0.2-18",
    quantity: 8083,
    remaining: 8083,
    deadline: "2025-03-01",
    duration: "120",
    steps: ["使用抖音扫描二维码进入指定账号", "点击视频进行点赞操作", "截图保存点赞证明", "上传截图完成验证"],
    notice: ["需使用实名认证的抖音账号", "请勿使用小号或批量账号", "点赞后24小时内不要取消"],
  },
  {
    id: 3,
    title: "朋友圈转发",
    type: "wechat",
    icon: "https://api.iconify.design/ri:wechat-moments-fill.svg?color=%2307c160",
    description: "转发指定内容到朋友圈",
    reward: "1.00",
    quantity: 100,
    remaining: 77,
    deadline: "2025-03-10",
    duration: "2880",
    steps: ["复制提供的文案和图片", "发布到朋友圈并保持48小时", "上传朋友圈截图进行验证", "系统审核通过后发放奖励"],
    notice: ["需要将朋友圈设置为公开可见", "发布内容需保持48小时以上", "不得修改官方提供的文案和图片"],
  },
  {
    id: 4,
    title: "企业微信添加",
    type: "wechat",
    icon: "https://api.iconify.design/ri:wechat-work-fill.svg?color=%2307c160",
    description: "使用企业微信添加好友",
    reward: "1.00",
    quantity: 500,
    remaining: 486,
    deadline: "2025-06-30",
    duration: "1440",
    steps: ["下载并安装企业微信", "使用手机号注册企业微信账号", "扫码添加指定的企业微信好友", "保持好友关系24小时"],
    notice: ["需要使用本人手机号注册", "保持好友关系时间不得少于24小时", "请勿使用虚拟号码注册"],
  },
  {
    id: 5,
    title: "VX自动挂机",
    type: "wechat",
    icon: "https://api.iconify.design/ri:wechat-fill.svg?color=%2307c160",
    description: "使用vx挂机赚取收益",
    reward: "198.00",
    quantity: 50,
    remaining: 32,
    deadline: "2025-12-31",
    duration: "10080",
    steps: ["下载并安装指定的挂机软件", "登录您的微信账号", "设置自动回复内容", "保持软件在线运行"],
    notice: ["需要保持网络稳定", "电脑需要持续开机", "不影响正常微信使用"],
  },
]

