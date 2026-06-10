import { createRouter, createWebHistory } from 'vue-router'
import { useAppStore } from '@/stores/app'

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes: [
    { path: '/login', name: 'Login', component: () => import('@/views/login/LoginView.vue') },
    {
      path: '/',
      component: () => import('@/layout/MainLayout.vue'),
      redirect: '/dashboard',
      children: [
        { path: 'dashboard', name: 'Dashboard', component: () => import('@/views/dashboard/DashboardView.vue'), meta: { title: '控制台', icon: 'DataAnalysis' } },
        { path: 'bigscreen', name: 'BigScreen', component: () => import('@/views/dashboard/BigScreen.vue'), meta: { title: '数据大屏', hidden: true } },
        { path: 'profile', name: 'Profile', component: () => import('@/views/profile/ProfileView.vue'), meta: { title: '个人中心', hidden: true } },
        { path: 'system/admin', name: 'SystemAdmin', component: () => import('@/views/system/AdminUser.vue'), meta: { title: '用户管理' } },
        { path: 'system/role', name: 'SystemRole', component: () => import('@/views/system/Role.vue'), meta: { title: '角色管理' } },
        { path: 'system/menu', name: 'SystemMenu', component: () => import('@/views/system/Menu.vue'), meta: { title: '菜单管理' } },
        { path: 'system/config', name: 'SystemConfig', component: () => import('@/views/system/Config.vue'), meta: { title: '系统配置' } },
        { path: 'system/log', name: 'SystemLog', component: () => import('@/views/system/Log.vue'), meta: { title: '操作日志' } },
        { path: 'property/community', name: 'PropertyCommunity', component: () => import('@/views/property/Community.vue'), meta: { title: '小区管理' } },
        { path: 'property/building', name: 'PropertyBuilding', component: () => import('@/views/property/Building.vue'), meta: { title: '楼栋管理' } },
        { path: 'property/room', name: 'PropertyRoom', component: () => import('@/views/property/Room.vue'), meta: { title: '房间管理' } },
        { path: 'owner/index', name: 'OwnerIndex', component: () => import('@/views/owner/OwnerIndex.vue'), meta: { title: '业主管理' } },
        { path: 'owner/family', name: 'OwnerFamily', component: () => import('@/views/owner/Family.vue'), meta: { title: '家庭成员' } },
        { path: 'owner/vote', name: 'OwnerVote', component: () => import('@/views/owner/Vote.vue'), meta: { title: '业主投票' } },
        { path: 'owner/activity', name: 'OwnerActivity', component: () => import('@/views/owner/Activity.vue'), meta: { title: '社区活动' } },
        { path: 'owner/notice', name: 'OwnerNotice', component: () => import('@/views/owner/Notice.vue'), meta: { title: '公告通知' } },
        { path: 'owner/complaint', name: 'OwnerComplaint', component: () => import('@/views/owner/Complaint.vue'), meta: { title: '投诉建议' } },
        { path: 'charge/item', name: 'ChargeItem', component: () => import('@/views/charge/ChargeItem.vue'), meta: { title: '收费项目' } },
        { path: 'charge/bill', name: 'ChargeBill', component: () => import('@/views/charge/Bill.vue'), meta: { title: '账单管理' } },
        { path: 'charge/payment', name: 'ChargePayment', component: () => import('@/views/charge/Payment.vue'), meta: { title: '缴费记录' } },
        { path: 'charge/meter', name: 'ChargeMeter', component: () => import('@/views/charge/Meter.vue'), meta: { title: '抄表管理' } },
        { path: 'charge/finance', name: 'ChargeFinance', component: () => import('@/views/charge/Finance.vue'), meta: { title: '财务流水' } },
        { path: 'charge/arrears', name: 'ChargeArrears', component: () => import('@/views/charge/Arrears.vue'), meta: { title: '欠费管理' } },
        { path: 'repair/order', name: 'RepairOrder', component: () => import('@/views/repair/RepairOrder.vue'), meta: { title: '工单管理' } },
        { path: 'repair/worker', name: 'RepairWorker', component: () => import('@/views/repair/RepairWorker.vue'), meta: { title: '维修人员' } },
        { path: 'security/visitor', name: 'SecurityVisitor', component: () => import('@/views/security/Visitor.vue'), meta: { title: '访客登记' } },
        { path: 'security/patrol', name: 'SecurityPatrol', component: () => import('@/views/security/Patrol.vue'), meta: { title: '巡更记录' } },
        { path: 'security/patrol-route', name: 'SecurityPatrolRoute', component: () => import('@/views/security/PatrolRoute.vue'), meta: { title: '巡更路线' } },
        { path: 'security/access-card', name: 'SecurityAccessCard', component: () => import('@/views/security/AccessCard.vue'), meta: { title: '门禁卡' } },
        { path: 'parking/space', name: 'ParkingSpace', component: () => import('@/views/parking/ParkingSpace.vue'), meta: { title: '车位管理' } },
        { path: 'parking/vehicle', name: 'ParkingVehicle', component: () => import('@/views/parking/Vehicle.vue'), meta: { title: '车辆管理' } },
        { path: 'parking/record', name: 'ParkingRecord', component: () => import('@/views/parking/ParkingRecord.vue'), meta: { title: '停车记录' } },
        { path: 'notice/index', name: 'NoticeIndex', component: () => import('@/views/notice/NoticeIndex.vue'), meta: { title: '公告列表' } },
        { path: 'equipment/index', name: 'EquipmentIndex', component: () => import('@/views/equipment/EquipmentIndex.vue'), meta: { title: '设备台账' } },
        { path: 'equipment/maintain', name: 'EquipmentMaintain', component: () => import('@/views/equipment/EquipmentMaintain.vue'), meta: { title: '维保记录' } },
        { path: 'complaint/index', name: 'ComplaintIndex', component: () => import('@/views/complaint/ComplaintIndex.vue'), meta: { title: '投诉管理' } },
        { path: 'print', redirect: '/print/receipt' },
        { path: 'print/receipt', name: 'PrintReceipt', component: () => import('@/views/print/Receipt.vue'), meta: { title: '收据打印' } },
        { path: 'print/notice', name: 'PrintNotice', component: () => import('@/views/print/PrintNotice.vue'), meta: { title: '催缴通知' } },
        { path: 'staff/index', name: 'StaffIndex', component: () => import('@/views/staff/StaffIndex.vue'), meta: { title: '员工档案' } },
        { path: 'staff/attendance', name: 'StaffAttendance', component: () => import('@/views/staff/Attendance.vue'), meta: { title: '考勤管理' } },
        { path: 'staff/schedule', name: 'StaffSchedule', component: () => import('@/views/staff/Schedule.vue'), meta: { title: '排班管理' } },
        { path: 'staff/salary', name: 'StaffSalary', component: () => import('@/views/staff/Salary.vue'), meta: { title: '工资管理' } },
        { path: 'supplier/index', name: 'SupplierIndex', component: () => import('@/views/supplier/SupplierIndex.vue'), meta: { title: '供应商名录' } },
        { path: 'supplier/purchase', name: 'SupplierPurchase', component: () => import('@/views/supplier/Purchase.vue'), meta: { title: '采购订单' } },
        { path: 'supplier/contract', name: 'SupplierContract', component: () => import('@/views/supplier/Contract.vue'), meta: { title: '合同管理' } },
        { path: 'supplier/evaluation', name: 'SupplierEvaluation', component: () => import('@/views/supplier/Evaluation.vue'), meta: { title: '服务评价' } },
        { path: 'payment/config', name: 'PaymentConfig', component: () => import('@/views/payment/PaymentConfig.vue'), meta: { title: '支付配置' } },
        { path: 'wechat/config', name: 'WechatConfig', component: () => import('@/views/wechat/WechatConfig.vue'), meta: { title: '公众号配置' } },
        { path: 'wechat/user', name: 'WechatUser', component: () => import('@/views/wechat/WechatUser.vue'), meta: { title: '微信用户' } },
        { path: 'sms/config', name: 'SmsConfig', component: () => import('@/views/sms/SmsConfig.vue'), meta: { title: '服务商配置' } },
        // ====== 合并 dasheng_community 新模块 ======
        { path: 'equipment/Device', name: 'DeviceIndex', component: () => import('@/views/equipment/Device.vue'), meta: { title: '硬件设备' } },
        { path: 'equipment/DeviceEvent', name: 'DeviceEventIndex', component: () => import('@/views/equipment/DeviceEvent.vue'), meta: { title: '设备事件' } },
        { path: 'equipment/Elevator', name: 'ElevatorIndex', component: () => import('@/views/equipment/Elevator.vue'), meta: { title: '电梯台账' } },
        { path: 'equipment/ElevatorFault', name: 'ElevatorFaultIndex', component: () => import('@/views/equipment/ElevatorFault.vue'), meta: { title: '电梯故障' } },
        { path: 'equipment/ElevatorInspection', name: 'ElevatorInspectionIndex', component: () => import('@/views/equipment/ElevatorInspection.vue'), meta: { title: '电梯巡检' } },
        { path: 'lease/LeaseProperty', name: 'LeasePropertyIndex', component: () => import('@/views/lease/LeaseProperty.vue'), meta: { title: '可租赁房源' } },
        { path: 'lease/LeaseTenant', name: 'LeaseTenantIndex', component: () => import('@/views/lease/LeaseTenant.vue'), meta: { title: '租客信息' } },
        { path: 'lease/LeaseContract', name: 'LeaseContractIndex', component: () => import('@/views/lease/LeaseContract.vue'), meta: { title: '租赁合同' } },
        { path: 'lease/LeasePayment', name: 'LeasePaymentIndex', component: () => import('@/views/lease/LeasePayment.vue'), meta: { title: '租金支付' } },
        { path: 'lease/LeaseTermination', name: 'LeaseTerminationIndex', component: () => import('@/views/lease/LeaseTermination.vue'), meta: { title: '退租记录' } },
        { path: 'charge/Deposit', name: 'DepositIndex', component: () => import('@/views/charge/Deposit.vue'), meta: { title: '押金管理' } },
        { path: 'charge/Invoice', name: 'InvoiceIndex', component: () => import('@/views/charge/Invoice.vue'), meta: { title: '发票记录' } },
        { path: 'charge/InvoiceInfo', name: 'InvoiceInfoIndex', component: () => import('@/views/charge/InvoiceInfo.vue'), meta: { title: '发票抬头' } },
        { path: 'charge/UnifiedPayment', name: 'UnifiedPaymentIndex', component: () => import('@/views/charge/UnifiedPayment.vue'), meta: { title: '统一支付' } },
        { path: 'parking/ParkingFeeRule', name: 'ParkingFeeRuleIndex', component: () => import('@/views/parking/ParkingFeeRule.vue'), meta: { title: '停车费率' } },
        { path: 'parking/ParkingPayment', name: 'ParkingPaymentIndex', component: () => import('@/views/parking/ParkingPayment.vue'), meta: { title: '停车缴费' } },
        { path: 'parking/gateConfig', name: 'GateConfigIndex', component: () => import('@/views/parking/GateConfig.vue'), meta: { title: '道闸配置' } },
        { path: 'parking/gateDevice', name: 'GateDeviceIndex', component: () => import('@/views/parking/GateDevice.vue'), meta: { title: '道闸设备' } },
        { path: 'security/accessConfig', name: 'AccessConfigIndex', component: () => import('@/views/security/AccessConfig.vue'), meta: { title: '门禁配置' } },
        { path: 'security/accessDevice', name: 'AccessDeviceIndex', component: () => import('@/views/security/AccessDevice.vue'), meta: { title: '门禁设备' } },
        { path: 'print/PrintTemplate', name: 'PrintTemplateIndex', component: () => import('@/views/print/PrintTemplate.vue'), meta: { title: '打印模板' } },
        { path: 'print/PrintLog', name: 'PrintLogIndex', component: () => import('@/views/print/PrintLog.vue'), meta: { title: '打印日志' } },
        { path: 'notice/Notification', name: 'NotificationIndex', component: () => import('@/views/notice/Notification.vue'), meta: { title: '消息推送' } },
        { path: 'system/PushDevice', name: 'PushDeviceIndex', component: () => import('@/views/system/PushDevice.vue'), meta: { title: '推送设备' } },
        { path: 'system/SseEvent', name: 'SseEventIndex', component: () => import('@/views/system/SseEvent.vue'), meta: { title: 'SSE事件' } },
        { path: 'system/pushConfig', name: 'PushConfigIndex', component: () => import('@/views/system/PushConfig.vue'), meta: { title: '推送配置' } },
        { path: 'system/ServiceVendor', name: 'ServiceVendorIndex', component: () => import('@/views/system/ServiceVendor.vue'), meta: { title: '服务商联系' } },
        { path: 'sms/template', name: 'SmsTemplateIndex', component: () => import('@/views/sms/SmsTemplate.vue'), meta: { title: '短信模板' } },
        { path: 'sms/send', name: 'SmsSendIndex', component: () => import('@/views/sms/SmsSend.vue'), meta: { title: '短信发送' } },

        // ====== 补全缺失模块 ======
        { path: 'charge/dunning', name: 'BillDunningIndex', component: () => import('@/views/charge/BillDunning.vue'), meta: { title: '催缴记录' } },
        { path: 'notice/message', name: 'MessageIndex', component: () => import('@/views/notice/Message.vue'), meta: { title: '消息记录' } },
        { path: 'sms/log', name: 'SmsLogIndex', component: () => import('@/views/sms/SmsLog.vue'), meta: { title: '短信发送日志' } },
        { path: 'owner/signup', name: 'ActivitySignupIndex', component: () => import('@/views/owner/ActivitySignup.vue'), meta: { title: '活动报名' } },

        // ====== 监控管理 ======
        { path: 'monitoring/surveillanceConfig', name: 'SurveillanceConfigIndex', component: () => import('@/views/monitoring/SurveillanceConfig.vue'), meta: { title: '监控管理' } },

        // ====== 装修管理 ======
        { path: 'decoration/apply', name: 'DecorationApply', component: () => import('@/views/decoration/DecorationApply.vue'), meta: { title: '装修申请' } },
        { path: 'decoration/inspect', name: 'DecorationInspect', component: () => import('@/views/decoration/DecorationInspect.vue'), meta: { title: '施工巡查' } },
        { path: 'decoration/violation', name: 'DecorationViolation', component: () => import('@/views/decoration/DecorationViolation.vue'), meta: { title: '违规记录' } },
        { path: 'decoration/worker', name: 'DecorationWorker', component: () => import('@/views/decoration/DecorationWorker.vue'), meta: { title: '施工人员' } },

        // ====== IoT 设备管理（连接3D数字孪生）=======
        { path: 'iot/device', name: 'IotDevice', component: () => import('@/views/iot/IotDevice.vue'), meta: { title: 'IoT设备管理' } },

        // ====== AI 助手管理 ======
        { path: 'ai/assistant', name: 'AiAssistant', component: () => import('@/views/ai/AiAssistant.vue'), meta: { title: 'AI助手管理' } },
      ],
    },

    // ====== 移动端 H5 路由 ======
    // 物业员工端
    { path: '/mobile/staff/login', name: 'StaffLogin', component: () => import('@/views/mobile/StaffLogin.vue') },
    { path: '/mobile/staff/home', name: 'StaffHome', component: () => import('@/views/mobile/staff/StaffHome.vue') },
    { path: '/mobile/staff/repair', name: 'StaffRepair', component: () => import('@/views/mobile/staff/StaffRepair.vue') },
    { path: '/mobile/staff/charge', name: 'StaffCharge', component: () => import('@/views/mobile/staff/StaffCharge.vue') },
    { path: '/mobile/staff/patrol', name: 'StaffPatrol', component: () => import('@/views/mobile/staff/StaffPatrol.vue') },
    // 领导驾驶舱
    { path: '/mobile/manager/login', name: 'ManagerLogin', component: () => import('@/views/mobile/manager/ManagerLogin.vue') },
    { path: '/mobile/manager/dashboard', name: 'ManagerDashboard', component: () => import('@/views/mobile/manager/ManagerDashboard.vue') },
    // 业主端
    { path: '/mobile/owner/login', name: 'OwnerLogin', component: () => import('@/views/mobile/owner/OwnerLogin.vue') },
    { path: '/mobile/owner/home', name: 'OwnerHome', component: () => import('@/views/mobile/owner/OwnerHome.vue') },
    { path: '/mobile/owner/bill', name: 'OwnerBill', component: () => import('@/views/mobile/owner/OwnerBill.vue') },
    { path: '/mobile/owner/repair', name: 'OwnerRepair', component: () => import('@/views/mobile/owner/OwnerRepair.vue') },
    { path: '/mobile/owner/visitor', name: 'OwnerVisitor', component: () => import('@/views/mobile/owner/OwnerVisitor.vue') },
    { path: '/mobile/owner/vehicle', name: 'OwnerVehicle', component: () => import('@/views/mobile/owner/OwnerVehicle.vue') },
    { path: '/mobile/owner/notice', name: 'OwnerNotice', component: () => import('@/views/mobile/owner/OwnerNotice.vue') },
    { path: '/mobile/owner/complaint', name: 'OwnerComplaint', component: () => import('@/views/mobile/owner/OwnerComplaint.vue') },
    // 管理员手机端
    { path: '/mobile/admin/login', name: 'MobileAdminLogin', component: () => import('@/views/mobile/admin/MobileAdminLogin.vue') },
    {
      path: '/mobile/admin',
      component: () => import('@/views/mobile/admin/MobileAdminLayout.vue'),
      redirect: '/mobile/admin/dashboard',
      children: [
        { path: 'dashboard', name: 'MobileAdminDashboard', component: () => import('@/views/mobile/admin/MobileAdminDashboard.vue'), meta: { title: '控制台' } },
        { path: 'menus', name: 'MobileAdminMenus', component: () => import('@/views/mobile/admin/MobileAdminMenus.vue'), meta: { title: '功能菜单' } },
        { path: 'messages', name: 'MobileAdminMessages', component: () => import('@/views/mobile/admin/MobileAdminMessages.vue'), meta: { title: '消息通知' } },
        { path: 'profile', name: 'MobileAdminProfile', component: () => import('@/views/mobile/admin/MobileAdminProfile.vue'), meta: { title: '个人中心' } },
      ],
    },
  ],
})

// 路由守卫 - 认证
router.beforeEach((to, _from, next) => {
  const adminToken = localStorage.getItem('admin_token')
  const staffToken = localStorage.getItem('staff_token')
  const ownerToken = localStorage.getItem('owner_token')
  const managerToken = localStorage.getItem('manager_token')

  // PC端登录页不需要认证
  if (to.path === '/login') return next()

  // 移动端登录页不需要认证
  const mobileLoginPaths = ['/mobile/staff/login', '/mobile/owner/login', '/mobile/manager/login', '/mobile/admin/login']
  if (mobileLoginPaths.includes(to.path)) return next()

  // 移动端其他页面：需要各自端的 token
  if (to.path.startsWith('/mobile/staff/')) {
    if (!staffToken) return next('/mobile/staff/login')
    return next()
  }
  if (to.path.startsWith('/mobile/owner/')) {
    if (!ownerToken) return next('/mobile/owner/login')
    return next()
  }
  if (to.path.startsWith('/mobile/manager/')) {
    if (!managerToken) return next('/mobile/manager/dashboard')
    return next()
  }
  // 管理员手机端需要 admin_token
  if (to.path.startsWith('/mobile/admin/')) {
    if (!adminToken) return next('/mobile/admin/login')
    return next()
  }

  // PC端其他页面需要 admin_token
  if (!adminToken) return next('/login')
  next()
})

// 路由守卫 - 标签页管理：只保留当前页，自动关闭之前的
router.afterEach((to) => {
  if (to.meta.title && to.path !== '/login' && to.path !== '/bigscreen') {
    const title = (to.meta.title as string) || to.name as string || to.path
    useAppStore().addTab({
      title,
      path: to.path,
      name: (to.name as string) || to.path,
      closable: to.path !== '/dashboard',
    })
  }
})

export default router
