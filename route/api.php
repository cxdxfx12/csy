<?php
// 业主端 API 路由
use think\facade\Route;

Route::group('api', function () {
    // 认证
    Route::post('ownerLogin', 'api/Login/login');
    Route::post('register', 'api/Login/register');
    Route::post('sendSms', 'api/Login/sendSms');
    Route::post('resetPassword', 'api/Login/resetPassword');
    // 微信认证
    Route::get('wechatOAuth', 'api/Login/wechatOAuth');
    Route::get('wechatCallback', 'api/Login/wechatCallback');
    Route::post('wechatLogin', 'api/Login/wechatLogin');
    Route::post('wechatBind', 'api/Login/wechatBind');
    Route::post('wechatUnbind', 'api/Login/wechatUnbind');
    // 小区列表（公开，供登录页小区选择下拉）
    Route::get('communityList', 'api/Login/communityList');

    // 经理端微信认证（复用 manager 控制器）
    Route::get('manager/wechatOAuth', 'manager/ManagerLogin/wechatOAuth');
    Route::get('manager/wechatCallback', 'manager/ManagerLogin/wechatCallback');
    Route::post('manager/wechatLogin', 'manager/ManagerLogin/wechatLogin');
    Route::post('manager/wechatRegister', 'manager/ManagerLogin/wechatRegister');

    // 首页
    Route::get('index/banner', 'api/Index/banner');
    Route::get('index/notice', 'api/Index/notice');
    Route::get('index/myInfo', 'api/Index/myInfo');

    // 角标 & 消息提醒
    Route::get('badge/counts', 'api/Badge/counts');

    // 微信服务器消息回调（关注/取消关注事件）
    Route::get('wechat/server/<cid>', 'api/WechatServer/index');
    Route::post('wechat/server/<cid>', 'api/WechatServer/index');

    // 自助认领房产
    Route::post('claimProperty', 'api/Claim/claim');

    // 房产
    Route::get('room/list', 'api/Room/lists');
    Route::get('room/detail', 'api/Room/detail');

    // 账单
    Route::get('bill/list', 'api/Bill/lists');
    Route::get('bill/detail', 'api/Bill/detail');
    Route::post('bill/pay', 'api/Bill/pay');
    Route::get('bill/unpaid', 'api/Bill/unpaid');
    Route::get('bill/payConfig', 'api/Bill/payConfig');
    Route::get('bill/payStatus', 'api/Bill/payStatus');
    // 支付回调
    Route::post('bill/wechatNotify', 'api/Bill/wechatNotify');
    Route::post('bill/alipayNotify', 'api/Bill/alipayNotify');

    // 报修
    Route::post('repair/add', 'api/Repair/add');
    Route::get('repair/list', 'api/Repair/lists');
    Route::get('repair/detail', 'api/Repair/detail');
    Route::post('repair/evaluate', 'api/Repair/evaluate');

    // 投诉
    Route::post('complaint/add', 'api/Complaint/add');
    Route::get('complaint/list', 'api/Complaint/lists');

    // 访客
    Route::post('visitor/add', 'api/Visitor/add');
    Route::get('visitor/list', 'api/Visitor/lists');

    // 车辆
    Route::get('vehicle/list', 'api/Vehicle/lists');
    Route::post('vehicle/add', 'api/Vehicle/add');

    // 公告
    Route::get('notice/list', 'api/Notice/lists');
    Route::get('notice/detail', 'api/Notice/detail');

    // 业主投票
    Route::get('vote/list', 'api/Vote/lists');
    Route::get('vote/detail', 'api/Vote/detail');
    Route::post('vote/vote', 'api/Vote/vote');

    // 社区活动
    Route::get('activity/list', 'api/Activity/lists');
    Route::get('activity/detail', 'api/Activity/detail');
    Route::post('activity/signup', 'api/Activity/signup');
    Route::post('activity/cancelSignup', 'api/Activity/cancelSignup');
    Route::get('activity/mySignups', 'api/Activity/mySignups');

    // 个人中心
    Route::post('profile/edit', 'api/Profile/edit');
    Route::post('profile/password', 'api/Profile/password');
    Route::get('profile/info', 'api/Profile/info');

    // 官网咨询提交（公开，无需认证）
    Route::post('consultation/add', 'api/Consultation/add');

    // 数字孪生 IoT 设备数据（公开）
    Route::get('iot/devices', 'api/IotData/getDevices');

    // AI 智能报修助手（公开）
    Route::post('ai/chat', 'api/AiRepair/chat');
    Route::post('ai/submit', 'api/AiRepair/submit');
    Route::get('ai/quickTypes', 'api/AiRepair/quickTypes');
    Route::get('ai/query', 'api/AiRepair/query');
});

// =================================================================
// 后台管理 API 路由（Vue SPA 使用 /api/admin/* 前缀）
// =================================================================
Route::group('api/admin', function () {
    // 认证
    Route::get('captcha', 'admin/Login/captcha');
    Route::post('login', 'admin/Login/login');
    Route::post('logout', 'admin/Login/logout');
    Route::get('info', 'admin/Login/info');
    Route::get('login/info', 'admin/Login/info'); // 兼容前端旧版错误 URL
    // 微信
    Route::get('wechatOAuth', 'admin/Login/wechatOAuth');
    Route::get('wechatCallback', 'admin/Login/wechatCallback');
    Route::get('wechatLoginStatus', 'admin/Login/wechatLoginStatus');
    Route::post('wechatLogin', 'admin/Login/wechatLogin');
    Route::post('wechatBind', 'admin/Login/wechatBind');

    // 系统管理 - 管理员
    Route::get('user/list', 'admin/AdminUser/lists');
    Route::get('AdminUser/lists', 'admin/AdminUser/lists'); // 兼容前端旧版路径
    Route::post('user/add', 'admin/AdminUser/add');
    Route::post('user/edit', 'admin/AdminUser/edit');
    Route::post('user/delete', 'admin/AdminUser/delete');
    Route::post('user/status', 'admin/AdminUser/status');
    Route::post('user/changePassword', 'admin/AdminUser/changePassword');

    // 系统管理 - 角色
    Route::get('role/list', 'admin/Role/lists');
    Route::post('role/add', 'admin/Role/add');
    Route::post('role/edit', 'admin/Role/edit');
    Route::post('role/delete', 'admin/Role/delete');
    Route::post('role/status', 'admin/Role/status');
    Route::get('role/permission', 'admin/Role/permission');
    Route::post('role/savePermission', 'admin/Role/savePermission');

    // 系统管理 - 菜单
    Route::get('menu/list', 'admin/Menu/lists');
    Route::post('menu/add', 'admin/Menu/add');
    Route::post('menu/edit', 'admin/Menu/edit');
    Route::post('menu/delete', 'admin/Menu/delete');

    // 系统管理 - 配置
    Route::get('config/list', 'admin/Config/lists');
    Route::post('config/save', 'admin/Config/save');

    // 系统管理 - 操作日志
    Route::get('log/list', 'admin/Log/lists');
    Route::get('log/detail', 'admin/Log/detail');
    Route::get('log/export', 'admin/Log/export');

    // 小区管理
    Route::get('community/list', 'admin/Community/lists');
    Route::get('community/listAll', 'admin/Community/lists'); // 兼容前端旧版路径
    Route::post('community/add', 'admin/Community/add');
    Route::post('community/edit', 'admin/Community/edit');
    Route::post('community/delete', 'admin/Community/delete');

    // 楼栋管理
    Route::get('building/list', 'admin/Building/lists');
    Route::post('building/add', 'admin/Building/add');
    Route::post('building/edit', 'admin/Building/edit');
    Route::post('building/delete', 'admin/Building/delete');
    Route::get('building/select', 'admin/Building/select');

    // 房间管理
    Route::get('room/list', 'admin/Room/lists');
    Route::post('room/add', 'admin/Room/add');
    Route::post('room/edit', 'admin/Room/edit');
    Route::post('room/delete', 'admin/Room/delete');
    Route::post('room/batchAdd', 'admin/Room/batchAdd');
    Route::get('room/select', 'admin/Room/select');

    // 业主管理
    Route::get('owner/list', 'admin/Owner/lists');
    Route::post('owner/add', 'admin/Owner/add');
    Route::post('owner/edit', 'admin/Owner/edit');
    Route::post('owner/delete', 'admin/Owner/delete');
    Route::get('owner/detail', 'admin/Owner/detail');
    Route::get('owner/rooms', 'admin/Owner/rooms');
    Route::post('owner/unbindWechat', 'admin/Owner/unbindWechat');

    // 家庭成员
    Route::get('owner/familyList', 'admin/Family/lists');
    Route::post('owner/familyAdd', 'admin/Family/add');
    Route::post('owner/familyEdit', 'admin/Family/edit');
    Route::post('owner/familyDelete', 'admin/Family/delete');
    Route::post('owner/familyUnbindWechat', 'admin/Family/unbindWechat');

    // 收费项目
    Route::get('charge/itemList', 'admin/ChargeItem/lists');
    Route::post('charge/itemAdd', 'admin/ChargeItem/add');
    Route::post('charge/itemEdit', 'admin/ChargeItem/edit');
    Route::post('charge/itemDelete', 'admin/ChargeItem/delete');
    Route::get('charge/itemSelect', 'admin/ChargeItem/select');

    // 账单管理
    Route::get('charge/billList', 'admin/Bill/lists');
    Route::post('charge/billAdd', 'admin/Bill/add');
    Route::post('charge/billGenerate', 'admin/Bill/generate');
    Route::post('charge/billDelete', 'admin/Bill/delete');
    Route::get('charge/billDetail', 'admin/Bill/detail');
    Route::get('charge/billExport', 'admin/Bill/export');

    // 缴费记录
    Route::get('charge/paymentList', 'admin/Payment/lists');
    Route::post('charge/paymentAdd', 'admin/Payment/add');
    Route::post('charge/paymentDelete', 'admin/Payment/delete');
    Route::get('charge/paymentExport', 'admin/Payment/export');

    // 抄表管理
    Route::get('charge/meterList', 'admin/Meter/lists');
    Route::post('charge/meterAdd', 'admin/Meter/add');
    Route::post('charge/meterEdit', 'admin/Meter/edit');
    Route::post('charge/meterDelete', 'admin/Meter/delete');
    Route::get('charge/meterExport', 'admin/Meter/export');

    // 财务流水
    Route::get('charge/financeList', 'admin/Finance/lists');
    Route::get('charge/financeExport', 'admin/Finance/export');

    // 欠费管理
    Route::get('charge/arrearsList', 'admin/Arrears/lists');
    Route::post('charge/arrearsDunning', 'admin/Arrears/dunning');
    Route::post('charge/arrearsSmsDunning', 'admin/Arrears/smsDunning');
    Route::post('charge/arrearsWechatDunning', 'admin/Arrears/wechatDunning');
    Route::get('charge/arrearsHistory', 'admin/Arrears/history');

    // 催缴记录
    Route::get('charge/dunningList', 'admin/BillDunning/lists');

    // 押金管理
    Route::get('charge/depositList', 'admin/Deposit/lists');
    Route::post('charge/depositAdd', 'admin/Deposit/add');
    Route::post('charge/depositEdit', 'admin/Deposit/edit');
    Route::post('charge/depositDelete', 'admin/Deposit/delete');

    // 发票记录
    Route::get('charge/invoiceList', 'admin/Invoice/lists');
    Route::post('charge/invoiceAdd', 'admin/Invoice/add');
    Route::post('charge/invoiceEdit', 'admin/Invoice/edit');
    Route::post('charge/invoiceDelete', 'admin/Invoice/delete');

    // 发票抬头
    Route::get('charge/invoiceInfoList', 'admin/InvoiceInfo/lists');
    Route::post('charge/invoiceInfoAdd', 'admin/InvoiceInfo/add');
    Route::post('charge/invoiceInfoEdit', 'admin/InvoiceInfo/edit');
    Route::post('charge/invoiceInfoDelete', 'admin/InvoiceInfo/delete');

    // 统一支付
    Route::get('charge/unifiedPaymentList', 'admin/UnifiedPayment/lists');
    Route::post('charge/unifiedPaymentAdd', 'admin/UnifiedPayment/add');
    Route::post('charge/unifiedPaymentEdit', 'admin/UnifiedPayment/edit');
    Route::post('charge/unifiedPaymentDelete', 'admin/UnifiedPayment/delete');

    // 报修管理
    Route::get('repair/orderList', 'admin/RepairOrder/lists');
    Route::post('repair/orderAdd', 'admin/RepairOrder/add');
    Route::post('repair/orderAssign', 'admin/RepairOrder/assign');
    Route::post('repair/orderClose', 'admin/RepairOrder/close');
    Route::get('repair/orderDetail', 'admin/RepairOrder/detail');
    Route::post('repair/orderDelete', 'admin/RepairOrder/delete');
    Route::get('repair/orderExport', 'admin/RepairOrder/export');

    // 维修人员
    Route::get('repair/workerList', 'admin/RepairWorker/lists');
    Route::get('repair/staffList', 'admin/RepairWorker/staffList');
    Route::post('repair/workerAdd', 'admin/RepairWorker/add');
    Route::post('repair/workerEdit', 'admin/RepairWorker/edit');
    Route::post('repair/workerDelete', 'admin/RepairWorker/delete');

    // 访客登记
    Route::get('security/visitorList', 'admin/Visitor/lists');
    Route::post('security/visitorAdd', 'admin/Visitor/add');
    Route::post('security/visitorEdit', 'admin/Visitor/edit');
    Route::post('security/visitorDelete', 'admin/Visitor/delete');
    Route::post('security/visitorLeave', 'admin/Visitor/leave');

    // 巡更管理
    Route::get('security/patrolRouteList', 'admin/PatrolRoute/lists');
    Route::post('security/patrolRouteAdd', 'admin/PatrolRoute/add');
    Route::post('security/patrolRouteEdit', 'admin/PatrolRoute/edit');
    Route::post('security/patrolRouteDelete', 'admin/PatrolRoute/delete');
    Route::get('security/patrolRecordList', 'admin/PatrolRecord/lists');

    // 门禁卡
    Route::get('security/accessCardList', 'admin/AccessCard/lists');
    Route::post('security/accessCardAdd', 'admin/AccessCard/add');
    Route::post('security/accessCardEdit', 'admin/AccessCard/edit');
    Route::post('security/accessCardDelete', 'admin/AccessCard/delete');

    // 车位管理
    Route::get('parking/spaceList', 'admin/ParkingSpace/lists');
    Route::post('parking/spaceAdd', 'admin/ParkingSpace/add');
    Route::post('parking/spaceEdit', 'admin/ParkingSpace/edit');
    Route::post('parking/spaceDelete', 'admin/ParkingSpace/delete');

    // 车辆管理
    Route::get('parking/vehicleList', 'admin/Vehicle/lists');
    Route::post('parking/vehicleAdd', 'admin/Vehicle/add');
    Route::post('parking/vehicleEdit', 'admin/Vehicle/edit');
    Route::post('parking/vehicleDelete', 'admin/Vehicle/delete');

    // 停车记录
    Route::get('parking/recordList', 'admin/ParkingRecord/lists');
    Route::post('parking/recordAdd', 'admin/ParkingRecord/add');
    Route::post('parking/recordDelete', 'admin/ParkingRecord/delete');

    // 停车费率
    Route::get('parking/parkingFeeRuleList', 'admin/ParkingFeeRule/lists');
    Route::post('parking/parkingFeeRuleAdd', 'admin/ParkingFeeRule/add');
    Route::post('parking/parkingFeeRuleEdit', 'admin/ParkingFeeRule/edit');
    Route::post('parking/parkingFeeRuleDelete', 'admin/ParkingFeeRule/delete');

    // 停车缴费
    Route::get('parking/parkingPaymentList', 'admin/ParkingPayment/lists');

    // 公告管理
    Route::get('notice/list', 'admin/Notice/lists');
    Route::post('notice/add', 'admin/Notice/add');
    Route::post('notice/edit', 'admin/Notice/edit');
    Route::post('notice/delete', 'admin/Notice/delete');
    Route::post('notice/publish', 'admin/Notice/publish');

    // 消息记录
    Route::get('notice/messageList', 'admin/Message/lists');
    Route::post('notice/messageAdd', 'admin/Message/add');
    Route::post('notice/messageDelete', 'admin/Message/delete');

    // 设备管理
    Route::get('equipment/list', 'admin/Equipment/lists');
    Route::post('equipment/add', 'admin/Equipment/add');
    Route::post('equipment/edit', 'admin/Equipment/edit');
    Route::post('equipment/delete', 'admin/Equipment/delete');
    Route::get('equipment/maintainList', 'admin/EquipmentMaintain/lists');
    Route::post('equipment/maintainAdd', 'admin/EquipmentMaintain/add');
    Route::post('equipment/maintainDelete', 'admin/EquipmentMaintain/delete');

    // 硬件设备
    Route::get('equipment/deviceList', 'admin/Device/lists');
    Route::post('equipment/deviceAdd', 'admin/Device/add');
    Route::post('equipment/deviceEdit', 'admin/Device/edit');
    Route::post('equipment/deviceDelete', 'admin/Device/delete');

    // 设备事件
    Route::get('equipment/deviceEventList', 'admin/DeviceEvent/lists');

    // 电梯台账
    Route::get('equipment/elevatorList', 'admin/Elevator/lists');
    Route::post('equipment/elevatorAdd', 'admin/Elevator/add');
    Route::post('equipment/elevatorEdit', 'admin/Elevator/edit');
    Route::post('equipment/elevatorDelete', 'admin/Elevator/delete');

    // 电梯故障
    Route::get('equipment/elevatorFaultList', 'admin/ElevatorFault/lists');
    Route::post('equipment/elevatorFaultAdd', 'admin/ElevatorFault/add');
    Route::post('equipment/elevatorFaultEdit', 'admin/ElevatorFault/edit');
    Route::post('equipment/elevatorFaultDelete', 'admin/ElevatorFault/delete');

    // 电梯巡检
    Route::get('equipment/elevatorInspectionList', 'admin/ElevatorInspection/lists');
    Route::post('equipment/elevatorInspectionAdd', 'admin/ElevatorInspection/add');
    Route::post('equipment/elevatorInspectionEdit', 'admin/ElevatorInspection/edit');
    Route::post('equipment/elevatorInspectionDelete', 'admin/ElevatorInspection/delete');

    // 投诉管理
    Route::get('complaint/list', 'admin/Complaint/lists');
    Route::post('complaint/handle', 'admin/Complaint/handle');
    Route::post('complaint/delete', 'admin/Complaint/delete');
    Route::get('complaint/detail', 'admin/Complaint/detail');

    // 官网咨询管理
    Route::get('consultation/list', 'admin/Consultation/lists');
    Route::get('consultation/detail', 'admin/Consultation/detail');
    Route::post('consultation/delete', 'admin/Consultation/delete');
    Route::post('consultation/batchDelete', 'admin/Consultation/batchDelete');
    Route::post('consultation/markRead', 'admin/Consultation/markRead');
    Route::get('consultation/unreadCount', 'admin/Consultation/unreadCount');

    // 业主投票
    Route::get('vote/list', 'admin/Vote/lists');
    Route::post('vote/add', 'admin/Vote/add');
    Route::post('vote/edit', 'admin/Vote/edit');
    Route::post('vote/delete', 'admin/Vote/delete');
    Route::get('vote/detail', 'admin/Vote/detail');
    Route::post('vote/publish', 'admin/Vote/publish');
    Route::post('vote/close', 'admin/Vote/close');
    Route::get('vote/result', 'admin/Vote/result');

    // 社区活动
    Route::get('activity/list', 'admin/Activity/lists');
    Route::post('activity/add', 'admin/Activity/add');
    Route::post('activity/edit', 'admin/Activity/edit');
    Route::post('activity/delete', 'admin/Activity/delete');
    Route::get('activity/detail', 'admin/Activity/detail');
    Route::post('activity/publish', 'admin/Activity/publish');
    Route::post('activity/start', 'admin/Activity/start');
    Route::post('activity/complete', 'admin/Activity/complete');
    Route::post('activity/cancel', 'admin/Activity/cancel');
    Route::get('activity/signups', 'admin/Activity/signups');
    Route::get('activity/signupList', 'admin/Activity/signupList');
    Route::post('activity/cancelSignup', 'admin/Activity/cancelSignup');

    // 角标通知
    Route::get('badge/counts', 'admin/AdminBadge/counts');

    // 数据概览
    Route::get('dashboard/statistics', 'admin/Dashboard/statistics');
    Route::get('dashboard/incomeChart', 'admin/Dashboard/incomeChart');
    Route::get('dashboard/repairChart', 'admin/Dashboard/repairChart');
    Route::get('dashboard/pieChart', 'admin/Dashboard/pieChart');
    Route::get('dashboard/bigscreen', 'admin/Dashboard/bigscreen');

    // 公共接口
    Route::get('upload/image', 'admin/Upload/image');
    Route::post('upload/file', 'admin/Upload/file');
    Route::get('upload/delete', 'admin/Upload/delete');

    // 个人中心
    Route::get('profile/info', 'admin/Profile/info');
    Route::post('profile/edit', 'admin/Profile/edit');
    Route::post('profile/password', 'admin/Profile/password');

    // 打印
    Route::get('print/receipt', 'admin/Print/receipt');
    Route::get('print/notice', 'admin/Print/notice');

    // 打印模板
    Route::get('print/printTemplateList', 'admin/PrintTemplate/lists');
    Route::post('print/printTemplateAdd', 'admin/PrintTemplate/add');
    Route::post('print/printTemplateEdit', 'admin/PrintTemplate/edit');
    Route::post('print/printTemplateDelete', 'admin/PrintTemplate/delete');

    // 打印日志
    Route::get('print/logList', 'admin/PrintLog/lists');

    // 物业人员
    Route::get('staff/lists', 'admin/Staff/lists');
    Route::post('staff/add', 'admin/Staff/add');
    Route::post('staff/edit', 'admin/Staff/edit');
    Route::post('staff/delete', 'admin/Staff/delete');
    Route::get('staff/detail', 'admin/Staff/detail');

    // 考勤管理
    Route::get('attendance/lists', 'admin/Attendance/lists');
    Route::post('attendance/add', 'admin/Attendance/add');
    Route::post('attendance/edit', 'admin/Attendance/edit');
    Route::post('attendance/delete', 'admin/Attendance/delete');
    Route::post('attendance/batch', 'admin/Attendance/batch');

    // 排班管理
    Route::get('schedule/lists', 'admin/Schedule/lists');
    Route::post('schedule/add', 'admin/Schedule/add');
    Route::post('schedule/edit', 'admin/Schedule/edit');
    Route::post('schedule/delete', 'admin/Schedule/delete');
    Route::post('schedule/batch', 'admin/Schedule/batch');

    // 工资管理
    Route::get('salary/lists', 'admin/Salary/lists');
    Route::post('salary/add', 'admin/Salary/add');
    Route::post('salary/edit', 'admin/Salary/edit');
    Route::post('salary/delete', 'admin/Salary/delete');
    Route::post('salary/pay', 'admin/Salary/pay');
    Route::post('salary/batchGenerate', 'admin/Salary/batchGenerate');

    // 外部供应商
    Route::get('supplier/lists', 'admin/Supplier/lists');
    Route::post('supplier/add', 'admin/Supplier/add');
    Route::post('supplier/edit', 'admin/Supplier/edit');
    Route::post('supplier/delete', 'admin/Supplier/delete');
    Route::get('supplier/detail', 'admin/Supplier/detail');

    // 采购订单
    Route::get('purchase/lists', 'admin/Purchase/lists');
    Route::post('purchase/add', 'admin/Purchase/add');
    Route::post('purchase/edit', 'admin/Purchase/edit');
    Route::post('purchase/delete', 'admin/Purchase/delete');
    Route::post('purchase/approve', 'admin/Purchase/approve');
    Route::post('purchase/complete', 'admin/Purchase/complete');

    // 合同管理
    Route::get('contract/lists', 'admin/Contract/lists');
    Route::post('contract/add', 'admin/Contract/add');
    Route::post('contract/edit', 'admin/Contract/edit');
    Route::post('contract/delete', 'admin/Contract/delete');
    Route::post('contract/expire', 'admin/Contract/expire');

    // 供应商评价
    Route::get('evaluation/lists', 'admin/Evaluation/lists');
    Route::post('evaluation/add', 'admin/Evaluation/add');
    Route::post('evaluation/edit', 'admin/Evaluation/edit');
    Route::post('evaluation/delete', 'admin/Evaluation/delete');
    Route::get('evaluation/stats', 'admin/Evaluation/stats');

    // 支付配置
    Route::get('payment/configList', 'admin/PaymentConfig/lists');
    Route::get('payment/configDetail', 'admin/PaymentConfig/detail');
    Route::post('payment/configSave', 'admin/PaymentConfig/save');
    Route::get('payment/configTest', 'admin/PaymentConfig/test');

    // 社区支付配置
    Route::get('payment/communityConfigList', 'admin/CommunityPaymentConfig/lists');

    // 公众号配置
    Route::get('wechat/configList', 'admin/WechatConfig/lists');
    Route::get('wechat/configDetail', 'admin/WechatConfig/detail');
    Route::post('wechat/configSave', 'admin/WechatConfig/save');
    Route::get('wechat/configTest', 'admin/WechatConfig/test');

    // 社区公众号配置
    Route::get('wechat/communityConfigList', 'admin/CommunityWechatConfig/lists');

    // 短信配置
    Route::get('sms/list', 'admin/Sms/lists');
    Route::get('sms/detail', 'admin/Sms/detail');
    Route::post('sms/save', 'admin/Sms/save');
    Route::get('sms/test', 'admin/Sms/test');

    // 短信模板
    Route::get('sms/smsTemplateList', 'admin/SmsTemplate/lists');
    Route::post('sms/smsTemplateAdd', 'admin/SmsTemplate/add');
    Route::post('sms/smsTemplateEdit', 'admin/SmsTemplate/edit');
    Route::post('sms/smsTemplateDelete', 'admin/SmsTemplate/delete');

    // 短信发送
    Route::post('sms/send', 'admin/SmsSend/send');
    Route::post('sms/resend', 'admin/SmsSend/resend');

    // 短信发送日志
    Route::get('sms/smsLogList', 'admin/SmsLog/lists');
    Route::get('sms/smsLogStats', 'admin/SmsLog/stats');

    // 通知推送
    Route::get('notice/notificationList', 'admin/Notification/lists');
    Route::post('notice/notificationAdd', 'admin/Notification/add');
    Route::post('notice/notificationEdit', 'admin/Notification/edit');
    Route::post('notice/notificationDelete', 'admin/Notification/delete');

    // 推送设备
    Route::get('system/pushDeviceList', 'admin/PushDevice/lists');
    Route::post('system/pushDeviceAdd', 'admin/PushDevice/add');
    Route::post('system/pushDeviceEdit', 'admin/PushDevice/edit');
    Route::post('system/pushDeviceDelete', 'admin/PushDevice/delete');

    // SSE事件
    Route::get('system/sseEventList', 'admin/SseEvent/lists');
    // SSE 实时推送流
    Route::get('sse/stream', 'admin/AdminSse/stream');

    // 服务商
    Route::get('system/serviceVendorList', 'admin/ServiceVendor/lists');
    Route::post('system/serviceVendorAdd', 'admin/ServiceVendor/add');
    Route::post('system/serviceVendorEdit', 'admin/ServiceVendor/edit');
    Route::post('system/serviceVendorDelete', 'admin/ServiceVendor/delete');

    // 租凭管理 - 房源
    Route::get('lease/leasePropertyList', 'admin/LeaseProperty/lists');
    Route::post('lease/leasePropertyAdd', 'admin/LeaseProperty/add');
    Route::post('lease/leasePropertyEdit', 'admin/LeaseProperty/edit');
    Route::post('lease/leasePropertyDelete', 'admin/LeaseProperty/delete');

    // 租凭管理 - 租客
    Route::get('lease/leaseTenantList', 'admin/LeaseTenant/lists');
    Route::post('lease/leaseTenantAdd', 'admin/LeaseTenant/add');
    Route::post('lease/leaseTenantEdit', 'admin/LeaseTenant/edit');
    Route::post('lease/leaseTenantDelete', 'admin/LeaseTenant/delete');

    // 租凭管理 - 合同
    Route::get('lease/leaseContractList', 'admin/LeaseContract/lists');
    Route::post('lease/leaseContractAdd', 'admin/LeaseContract/add');
    Route::post('lease/leaseContractEdit', 'admin/LeaseContract/edit');
    Route::post('lease/leaseContractDelete', 'admin/LeaseContract/delete');

    // 租凭管理 - 支付
    Route::get('lease/leasePaymentList', 'admin/LeasePayment/lists');

    // 租凭管理 - 退租
    Route::get('lease/leaseTerminationList', 'admin/LeaseTermination/lists');
    Route::post('lease/leaseTerminationAdd', 'admin/LeaseTermination/add');
    Route::post('lease/leaseTerminationEdit', 'admin/LeaseTermination/edit');
    Route::post('lease/leaseTerminationDelete', 'admin/LeaseTermination/delete');

    // IoT 设备管理（连接3D数字孪生）
    Route::get('iot/deviceList', 'admin/Iot/deviceList');
    Route::post('iot/deviceAdd', 'admin/Iot/deviceAdd');
    Route::post('iot/deviceEdit', 'admin/Iot/deviceEdit');
    Route::post('iot/deviceDelete', 'admin/Iot/deviceDelete');
    Route::get('iot/stats', 'admin/Iot/stats');
    // IoT 设备类型
    Route::get('iot/typeList', 'admin/Iot/typeList');
    Route::get('iot/typeAll', 'admin/Iot/typeAll');
    Route::post('iot/typeAdd', 'admin/Iot/typeAdd');
    Route::post('iot/typeEdit', 'admin/Iot/typeEdit');
    Route::post('iot/typeDelete', 'admin/Iot/typeDelete');
    // IoT 协议
    Route::get('iot/protocolList', 'admin/Iot/protocolList');
    Route::get('iot/protocolAll', 'admin/Iot/protocolAll');
    Route::post('iot/protocolAdd', 'admin/Iot/protocolAdd');
    Route::post('iot/protocolEdit', 'admin/Iot/protocolEdit');
    Route::post('iot/protocolDelete', 'admin/Iot/protocolDelete');
    // IoT 数据查看
    Route::get('iot/dataList', 'admin/Iot/dataList');

    // AI 助手管理
    Route::get('aiAssistant/config', 'admin/AiAssistant/config');
    Route::post('aiAssistant/config', 'admin/AiAssistant/config');
    Route::get('aiAssistant/chatHistory', 'admin/AiAssistant/chatHistory');
    Route::get('aiAssistant/stats', 'admin/AiAssistant/stats');
});
