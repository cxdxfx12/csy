/**
 * 手机管理端图标映射系统
 * 使用 Phosphor Icons — 世界顶级图标库，6种风格，1000+ 图标
 */

// 图标名称统一管理
export const ICONS = {
  // 底部导航
  dashboard: 'ph:gauge-fill',
  menu: 'ph:grid-nine-fill',
  messages: 'ph:bell-fill',
  profile: 'ph:user-circle-fill',

  // 通用操作
  search: 'ph:magnifying-glass',
  back: 'ph:caret-left',
  forward: 'ph:caret-right',
  down: 'ph:caret-down',
  up: 'ph:caret-up',
  add: 'ph:plus-circle',
  edit: 'ph:pencil-simple',
  delete: 'ph:trash',
  save: 'ph:floppy-disk',
  refresh: 'ph:arrows-clockwise',
  filter: 'ph:funnel',
  more: 'ph:dots-three-outline-vertical-fill',
  close: 'ph:x',
  check: 'ph:check-circle',
  download: 'ph:download-simple',
  upload: 'ph:upload-simple',
  share: 'ph:share-network',
  link: 'ph:link',
  copy: 'ph:copy',
  qrcode: 'ph:qr-code',
  scan: 'ph:scan',
  print: 'ph:printer',

  // 状态
  success: 'ph:check-circle-fill',
  warning: 'ph:warning-circle-fill',
  error: 'ph:x-circle-fill',
  info: 'ph:info-fill',
  question: 'ph:question-fill',
  lock: 'ph:lock',
  unlock: 'ph:lock-open',
  eye: 'ph:eye',
  eyeOff: 'ph:eye-closed',
  shield: 'ph:shield-check',

  // 导航
  home: 'ph:house-fill',
  logout: 'ph:sign-out',
  settings: 'ph:gear-fill',
  pc: 'ph:desktop',
  mobile: 'ph:device-mobile',
  tablet: 'ph:device-tablet',

  // 业务模块 - 系统管理
  system: 'ph:gear',
  user: 'ph:user',
  users: 'ph:users',
  userPlus: 'ph:user-plus',
  role: 'ph:user-gear',
  menuList: 'ph:list-bullets',
  config: 'ph:sliders',
  log: 'ph:scroll',
  database: 'ph:database',

  // 业务模块 - 房产
  property: 'ph:buildings-fill',
  building: 'ph:building-office',
  community: 'ph:buildings',
  room: 'ph:house',
  door: 'ph:door',

  // 业务模块 - 业主
  owner: 'ph:users-fill',
  family: 'ph:users-three',
  vote: 'ph:vote',
  activity: 'ph:confetti',
  complaint: 'ph:chat-centered-text',
  suggestion: 'ph:lightbulb',

  // 业务模块 - 收费
  charge: 'ph:currency-circle-dollar-fill',
  bill: 'ph:file-text',
  payment: 'ph:credit-card',
  finance: 'ph:chart-line-up',
  arrears: 'ph:warning-diamond',
  deposit: 'ph:hand-deposit',
  invoice: 'ph:receipt',
  meter: 'ph:gauge',

  // 业务模块 - 报修
  repair: 'ph:wrench-fill',
  workOrder: 'ph:clipboard-text',
  worker: 'ph:hard-hat',

  // 业务模块 - 安防
  security: 'ph:shield-check-fill',
  visitor: 'ph:identification-badge',
  patrol: 'ph:footprints',
  accessCard: 'ph:credit-card',
  accessConfig: 'ph:gear',
  accessDevice: 'ph:keyhole',

  // 业务模块 - 停车
  parking: 'ph:car-fill',
  vehicle: 'ph:car',
  parkingSpace: 'ph:park',
  parkingRecord: 'ph:clock-countdown',
  gateConfig: 'ph:traffic-cone',
  gateDevice: 'ph:barricade',

  // 业务模块 - 公告/通知
  notice: 'ph:megaphone-fill',
  announcement: 'ph:newspaper',
  message: 'ph:chat-circle-dots',
  notification: 'ph:bell-ringing',
  push: 'ph:paper-plane-tilt',

  // 业务模块 - 设备/IoT
  equipment: 'ph:cpu-fill',
  device: 'ph:microchip',
  maintain: 'ph:hammer',
  elevator: 'ph:elevator',
  iot: 'ph:plug',
  sensor: 'ph:radio',
  camera: 'ph:camera',

  // 业务模块 - 租赁
  lease: 'ph:house-line-fill',
  tenant: 'ph:person-simple-walk',
  contract: 'ph:handshake',
  rentPayment: 'ph:money',
  termination: 'ph:prohibit',

  // 业务模块 - 人事
  staff: 'ph:user-list-fill',
  attendance: 'ph:clock-user',
  schedule: 'ph:calendar-check',
  salary: 'ph:bank',

  // 业务模块 - 供应商
  supplier: 'ph:handshake-fill',
  purchase: 'ph:shopping-cart',
  evaluation: 'ph:star',

  // 业务模块 - 微信/短信
  wechat: 'ph:wechat-logo-fill',
  sms: 'ph:chat-centered-text-fill',

  // 业务模块 - 其他
  monitoring: 'ph:monitor-play',
  decoration: 'ph:paint-roller',
  renovation: 'ph:paint-brush-broad',
  ai: 'ph:brain-fill',
  bigscreen: 'ph:presentation-chart',
  report: 'ph:chart-bar',
  statistics: 'ph:chart-pie',

  // 数据指标
  revenue: 'ph:trend-up',
  expense: 'ph:trend-down',
  count: 'ph:hash',
  percent: 'ph:percent',
  target: 'ph:target',

  // 时间
  calendar: 'ph:calendar',
  clock: 'ph:clock',
  timer: 'ph:timer',
  hourglass: 'ph:hourglass',

  // 位置
  location: 'ph:map-pin',
  map: 'ph:map-trifold',

  // 通讯
  phone: 'ph:phone',
  email: 'ph:envelope',
  chat: 'ph:chat-circle',
} as const

export type IconName = keyof typeof ICONS

/**
 * 根据菜单名称获取对应图标
 */
export function getMenuIcon(name: string): string {
  const n = (name || '').toLowerCase()

  // 系统管理
  if (n.includes('用户管理') || n.includes('用户列表')) return ICONS.user
  if (n.includes('角色管理')) return ICONS.role
  if (n.includes('菜单管理')) return ICONS.menuList
  if (n.includes('系统配置') || n.includes('配置管理')) return ICONS.config
  if (n.includes('操作日志') || n.includes('日志')) return ICONS.log
  if (n.includes('推送设备')) return ICONS.push
  if (n.includes('sse事件')) return ICONS.link
  if (n.includes('推送配置')) return ICONS.gear
  if (n.includes('服务商联系')) return ICONS.phone

  // 房产管理
  if (n.includes('小区管理')) return ICONS.community
  if (n.includes('楼栋管理')) return ICONS.building
  if (n.includes('房间管理')) return ICONS.room

  // 业主管理
  if (n.includes('业主信息') || n.includes('业主管理')) return ICONS.owner
  if (n.includes('家庭成员')) return ICONS.family
  if (n.includes('投票')) return ICONS.vote
  if (n.includes('活动报名')) return ICONS.activity
  if (n.includes('社区活动')) return ICONS.confetti
  if (n.includes('投诉建议') || n.includes('投诉管理')) return ICONS.complaint

  // 收费管理
  if (n.includes('收费项目')) return ICONS.charge
  if (n.includes('账单管理')) return ICONS.bill
  if (n.includes('缴费记录')) return ICONS.payment
  if (n.includes('抄表')) return ICONS.meter
  if (n.includes('财务流水')) return ICONS.finance
  if (n.includes('欠费') || n.includes('催缴')) return ICONS.arrears
  if (n.includes('押金')) return ICONS.deposit
  if (n.includes('发票')) return ICONS.invoice
  if (n.includes('支付') || n.includes('统一')) return ICONS.payment
  if (n.includes('催缴记录')) return ICONS.bill

  // 报修
  if (n.includes('工单管理') || n.includes('工单')) return ICONS.workOrder
  if (n.includes('维修人员') || n.includes('维修')) return ICONS.worker

  // 安防
  if (n.includes('访客登记') || n.includes('访客')) return ICONS.visitor
  if (n.includes('巡更记录') || n.includes('巡更路线')) return ICONS.patrol
  if (n.includes('门禁卡')) return ICONS.accessCard
  if (n.includes('门禁配置')) return ICONS.accessConfig
  if (n.includes('门禁设备')) return ICONS.accessDevice

  // 停车
  if (n.includes('车位管理') || n.includes('车位')) return ICONS.parkingSpace
  if (n.includes('车辆管理') || n.includes('车辆')) return ICONS.vehicle
  if (n.includes('停车记录')) return ICONS.parkingRecord
  if (n.includes('停车费率')) return ICONS.payment
  if (n.includes('停车缴费')) return ICONS.payment
  if (n.includes('道闸配置') || n.includes('道闸设备') || n.includes('道闸')) return ICONS.gateDevice

  // 公告
  if (n.includes('公告列表') || n.includes('公告通知')) return ICONS.announcement
  if (n.includes('消息推送') || n.includes('消息')) return ICONS.notification

  // 设备
  if (n.includes('设备台账') || n.includes('设备管理')) return ICONS.device
  if (n.includes('硬件设备')) return ICONS.microchip
  if (n.includes('设备事件')) return ICONS.clock
  if (n.includes('维保记录') || n.includes('维保')) return ICONS.maintain
  if (n.includes('电梯台账')) return ICONS.elevator
  if (n.includes('电梯故障')) return ICONS.error
  if (n.includes('电梯巡检')) return ICONS.check

  // 租赁
  if (n.includes('租赁') && n.includes('房源')) return ICONS.lease
  if (n.includes('租客')) return ICONS.tenant
  if (n.includes('租赁合同')) return ICONS.contract
  if (n.includes('租金')) return ICONS.rentPayment
  if (n.includes('退租')) return ICONS.termination

  // 人事
  if (n.includes('员工档案') || n.includes('员工')) return ICONS.staff
  if (n.includes('考勤')) return ICONS.attendance
  if (n.includes('排班')) return ICONS.schedule
  if (n.includes('工资')) return ICONS.salary

  // 供应商
  if (n.includes('供应商名录') || n.includes('供应商')) return ICONS.supplier
  if (n.includes('采购')) return ICONS.purchase
  if (n.includes('合同管理') || n.includes('合同')) return ICONS.contract
  if (n.includes('服务评价') || n.includes('评价')) return ICONS.evaluation

  // 打印
  if (n.includes('收据打印') || n.includes('打印')) return ICONS.print
  if (n.includes('打印模板')) return ICONS.print
  if (n.includes('打印日志')) return ICONS.log

  // 微信/短信
  if (n.includes('公众号') || n.includes('微信')) return ICONS.wechat
  if (n.includes('短信') && n.includes('配置')) return ICONS.sms
  if (n.includes('短信') && n.includes('模板')) return ICONS.sms
  if (n.includes('短信') && n.includes('发送')) return ICONS.send
  if (n.includes('短信') && n.includes('日志')) return ICONS.log

  // 其他
  if (n.includes('监控')) return ICONS.monitoring
  if (n.includes('装修')) return ICONS.decoration
  if (n.includes('施工')) return ICONS.worker
  if (n.includes('违规')) return ICONS.error
  if (n.includes('iot') || n.includes('物联网')) return ICONS.iot
  if (n.includes('ai') || n.includes('智能') || n.includes('助手')) return ICONS.ai
  if (n.includes('大屏') || n.includes('数据')) return ICONS.bigscreen
  if (n.includes('支付配置')) return ICONS.config

  // 默认
  return ICONS.link
}

/**
 * 菜单模块颜色映射
 */
export function getMenuColor(name: string): string {
  const n = (name || '').toLowerCase()
  if (n.includes('系统') || n.includes('用户') || n.includes('角色') || n.includes('日志')) return '#6366f1'
  if (n.includes('房产') || n.includes('小区') || n.includes('楼栋') || n.includes('房间')) return '#0891b2'
  if (n.includes('业主') || n.includes('家庭') || n.includes('投票') || n.includes('活动')) return '#059669'
  if (n.includes('收费') || n.includes('账单') || n.includes('缴费') || n.includes('财务') || n.includes('欠费')) return '#ea580c'
  if (n.includes('报修') || n.includes('工单') || n.includes('维修')) return '#dc2626'
  if (n.includes('安防') || n.includes('访客') || n.includes('巡更') || n.includes('门禁')) return '#7c3aed'
  if (n.includes('停车') || n.includes('车辆') || n.includes('车位') || n.includes('道闸')) return '#2563eb'
  if (n.includes('公告') || n.includes('通知') || n.includes('消息') || n.includes('推送')) return '#d946ef'
  if (n.includes('设备') || n.includes('维保') || n.includes('电梯')) return '#0891b2'
  if (n.includes('租赁') || n.includes('租客')) return '#0d9488'
  if (n.includes('人事') || n.includes('员工') || n.includes('考勤') || n.includes('工资')) return '#ca8a04'
  if (n.includes('供应商') || n.includes('采购') || n.includes('合同') || n.includes('评价')) return '#4f46e5'
  if (n.includes('打印') || n.includes('收据') || n.includes('催缴')) return '#6b7280'
  if (n.includes('微信') || n.includes('短信')) return '#16a34a'
  if (n.includes('监控') || n.includes('摄像')) return '#0284c7'
  if (n.includes('装修') || n.includes('施工') || n.includes('违规')) return '#b45309'
  if (n.includes('iot')) return '#0e7490'
  if (n.includes('ai') || n.includes('智能') || n.includes('助手')) return '#8b5cf6'
  if (n.includes('大屏') || n.includes('数据') || n.includes('报表')) return '#0369a1'
  return '#64748b'
}
