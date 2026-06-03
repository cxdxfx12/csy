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
        { path: 'payment/record', name: 'PaymentRecord', component: () => import('@/views/charge/Payment.vue'), meta: { title: '缴费记录' } },
        { path: 'wechat/config', name: 'WechatConfig', component: () => import('@/views/wechat/WechatConfig.vue'), meta: { title: '公众号配置' } },
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
  ],
})

// 路由守卫 - 认证
router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('admin_token')
  const isMobileLogin = to.path.startsWith('/mobile/')
  // 移动端登录页和PC登录页不需要认证
  if (isMobileLogin || to.path === '/login') return next()
  // PC端其他页面需要token
  if (!token) return next('/login')
  next()
})

// 路由守卫 - 标签页管理：只保留当前页，自动关闭之前的
router.afterEach((to) => {
  if (to.meta.title && to.path !== '/login') {
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
