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

    // 个人中心
    Route::post('profile/edit', 'api/Profile/edit');
    Route::post('profile/password', 'api/Profile/password');
    Route::get('profile/info', 'api/Profile/info');
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
    // 微信
    Route::get('wechatOAuth', 'admin/Login/wechatOAuth');
    Route::get('wechatCallback', 'admin/Login/wechatCallback');
    Route::post('wechatLogin', 'admin/Login/wechatLogin');
    Route::post('wechatBind', 'admin/Login/wechatBind');

    // 系统管理 - 管理员
    Route::get('user/list', 'admin/AdminUser/lists');
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

    // 公告管理
    Route::get('notice/list', 'admin/Notice/lists');
    Route::post('notice/add', 'admin/Notice/add');
    Route::post('notice/edit', 'admin/Notice/edit');
    Route::post('notice/delete', 'admin/Notice/delete');
    Route::post('notice/publish', 'admin/Notice/publish');

    // 设备管理
    Route::get('equipment/list', 'admin/Equipment/lists');
    Route::post('equipment/add', 'admin/Equipment/add');
    Route::post('equipment/edit', 'admin/Equipment/edit');
    Route::post('equipment/delete', 'admin/Equipment/delete');
    Route::get('equipment/maintainList', 'admin/EquipmentMaintain/lists');
    Route::post('equipment/maintainAdd', 'admin/EquipmentMaintain/add');
    Route::post('equipment/maintainDelete', 'admin/EquipmentMaintain/delete');

    // 投诉管理
    Route::get('complaint/list', 'admin/Complaint/lists');
    Route::post('complaint/handle', 'admin/Complaint/handle');
    Route::post('complaint/delete', 'admin/Complaint/delete');
    Route::get('complaint/detail', 'admin/Complaint/detail');

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
    Route::post('activity/cancelSignup', 'admin/Activity/cancelSignup');

    // 数据概览
    Route::get('dashboard/statistics', 'admin/Dashboard/statistics');
    Route::get('dashboard/incomeChart', 'admin/Dashboard/incomeChart');
    Route::get('dashboard/repairChart', 'admin/Dashboard/repairChart');
    Route::get('dashboard/pieChart', 'admin/Dashboard/pieChart');

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

    // 公众号配置
    Route::get('wechat/configList', 'admin/WechatConfig/lists');
    Route::get('wechat/configDetail', 'admin/WechatConfig/detail');
    Route::post('wechat/configSave', 'admin/WechatConfig/save');
    Route::get('wechat/configTest', 'admin/WechatConfig/test');

    // 短信配置
    Route::get('sms/list', 'admin/Sms/lists');
    Route::get('sms/detail', 'admin/Sms/detail');
    Route::post('sms/save', 'admin/Sms/save');
    Route::get('sms/test', 'admin/Sms/test');
});
