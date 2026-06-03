<?php
// 后台管理路由
use think\facade\Route;

// 认证
Route::post('admin/login', 'admin/Login/login');
Route::get('admin/captcha', 'admin/Login/captcha');
Route::post('admin/logout', 'admin/Login/logout');

// 微信登录
Route::get('admin/wechatOAuth', 'admin/Login/wechatOAuth');
Route::get('admin/wechatCallback', 'admin/Login/wechatCallback');
Route::post('admin/wechatLogin', 'admin/Login/wechatLogin');
Route::post('admin/wechatBind', 'admin/Login/wechatBind');

// 系统管理
Route::group('admin', function () {
    // 管理员管理
    Route::get('admin/user/list', 'admin/AdminUser/lists');
    Route::post('admin/user/add', 'admin/AdminUser/add');
    Route::post('admin/user/edit', 'admin/AdminUser/edit');
    Route::post('admin/user/delete', 'admin/AdminUser/delete');
    Route::post('admin/user/status', 'admin/AdminUser/status');
    Route::post('admin/user/changePassword', 'admin/AdminUser/changePassword');

    // 角色管理
    Route::get('admin/role/list', 'admin/Role/lists');
    Route::post('admin/role/add', 'admin/Role/add');
    Route::post('admin/role/edit', 'admin/Role/edit');
    Route::post('admin/role/delete', 'admin/Role/delete');
    Route::post('admin/role/status', 'admin/Role/status');
    Route::get('admin/role/permission', 'admin/Role/permission');
    Route::post('admin/role/savePermission', 'admin/Role/savePermission');

    // 菜单管理
    Route::get('admin/menu/list', 'admin/Menu/lists');
    Route::post('admin/menu/add', 'admin/Menu/add');
    Route::post('admin/menu/edit', 'admin/Menu/edit');
    Route::post('admin/menu/delete', 'admin/Menu/delete');

    // 系统配置
    Route::get('admin/config/list', 'admin/Config/lists');
    Route::post('admin/config/save', 'admin/Config/save');

    // 操作日志
    Route::get('admin/log/list', 'admin/Log/lists');
    Route::get('admin/log/detail', 'admin/Log/detail');
    Route::get('admin/log/export', 'admin/Log/export');

    // 小区管理
    Route::get('admin/community/list', 'admin/Community/lists');
    Route::post('admin/community/add', 'admin/Community/add');
    Route::post('admin/community/edit', 'admin/Community/edit');
    Route::post('admin/community/delete', 'admin/Community/delete');

    // 楼栋管理
    Route::get('admin/building/list', 'admin/Building/lists');
    Route::post('admin/building/add', 'admin/Building/add');
    Route::post('admin/building/edit', 'admin/Building/edit');
    Route::post('admin/building/delete', 'admin/Building/delete');
    Route::get('admin/building/select', 'admin/Building/select');

    // 房间管理
    Route::get('admin/room/list', 'admin/Room/lists');
    Route::post('admin/room/add', 'admin/Room/add');
    Route::post('admin/room/edit', 'admin/Room/edit');
    Route::post('admin/room/delete', 'admin/Room/delete');
    Route::post('admin/room/batchAdd', 'admin/Room/batchAdd');
    Route::get('admin/room/select', 'admin/Room/select');

    // 业主管理
    Route::get('admin/owner/list', 'admin/Owner/lists');
    Route::post('admin/owner/add', 'admin/Owner/add');
    Route::post('admin/owner/edit', 'admin/Owner/edit');
    Route::post('admin/owner/delete', 'admin/Owner/delete');
    Route::get('admin/owner/detail', 'admin/Owner/detail');
    Route::get('admin/owner/rooms', 'admin/Owner/rooms');
    Route::post('admin/owner/unbindWechat', 'admin/Owner/unbindWechat');

    // 家庭成员
    Route::get('admin/owner/familyList', 'admin/Family/lists');
    Route::post('admin/owner/familyAdd', 'admin/Family/add');
    Route::post('admin/owner/familyEdit', 'admin/Family/edit');
    Route::post('admin/owner/familyDelete', 'admin/Family/delete');
    Route::post('admin/owner/familyUnbindWechat', 'admin/Family/unbindWechat');

    // 收费项目
    Route::get('admin/charge/itemList', 'admin/ChargeItem/lists');
    Route::post('admin/charge/itemAdd', 'admin/ChargeItem/add');
    Route::post('admin/charge/itemEdit', 'admin/ChargeItem/edit');
    Route::post('admin/charge/itemDelete', 'admin/ChargeItem/delete');
    Route::get('admin/charge/itemSelect', 'admin/ChargeItem/select');

    // 账单管理
    Route::get('admin/charge/billList', 'admin/Bill/lists');
    Route::post('admin/charge/billAdd', 'admin/Bill/add');
    Route::post('admin/charge/billGenerate', 'admin/Bill/generate');
    Route::post('admin/charge/billDelete', 'admin/Bill/delete');
    Route::get('admin/charge/billDetail', 'admin/Bill/detail');
    Route::get('admin/charge/billExport', 'admin/Bill/export');

    // 缴费记录
    Route::get('admin/charge/paymentList', 'admin/Payment/lists');
    Route::post('admin/charge/paymentAdd', 'admin/Payment/add');
    Route::post('admin/charge/paymentDelete', 'admin/Payment/delete');
    Route::get('admin/charge/paymentExport', 'admin/Payment/export');

    // 抄表管理
    Route::get('admin/charge/meterList', 'admin/Meter/lists');
    Route::post('admin/charge/meterAdd', 'admin/Meter/add');
    Route::post('admin/charge/meterEdit', 'admin/Meter/edit');
    Route::post('admin/charge/meterDelete', 'admin/Meter/delete');
    Route::get('admin/charge/meterExport', 'admin/Meter/export');

    // 财务流水
    Route::get('admin/charge/financeList', 'admin/Finance/lists');
    Route::get('admin/charge/financeExport', 'admin/Finance/export');

    // 欠费管理
    Route::get('admin/charge/arrearsList', 'admin/Arrears/lists');
    Route::post('admin/charge/arrearsDunning', 'admin/Arrears/dunning');
    Route::post('admin/charge/arrearsSmsDunning', 'admin/Arrears/smsDunning');
    Route::post('admin/charge/arrearsWechatDunning', 'admin/Arrears/wechatDunning');
    Route::get('admin/charge/arrearsHistory', 'admin/Arrears/history');

    // 报修管理
    Route::get('admin/repair/orderList', 'admin/RepairOrder/lists');
    Route::post('admin/repair/orderAdd', 'admin/RepairOrder/add');
    Route::post('admin/repair/orderAssign', 'admin/RepairOrder/assign');
    Route::post('admin/repair/orderClose', 'admin/RepairOrder/close');
    Route::get('admin/repair/orderDetail', 'admin/RepairOrder/detail');
    Route::post('admin/repair/orderDelete', 'admin/RepairOrder/delete');
    Route::get('admin/repair/orderExport', 'admin/RepairOrder/export');

    // 维修人员
    Route::get('admin/repair/workerList', 'admin/RepairWorker/lists');
    Route::post('admin/repair/workerAdd', 'admin/RepairWorker/add');
    Route::post('admin/repair/workerEdit', 'admin/RepairWorker/edit');
    Route::post('admin/repair/workerDelete', 'admin/RepairWorker/delete');

    // 访客登记
    Route::get('admin/security/visitorList', 'admin/Visitor/lists');
    Route::post('admin/security/visitorAdd', 'admin/Visitor/add');
    Route::post('admin/security/visitorEdit', 'admin/Visitor/edit');
    Route::post('admin/security/visitorDelete', 'admin/Visitor/delete');
    Route::post('admin/security/visitorLeave', 'admin/Visitor/leave');

    // 巡更管理
    Route::get('admin/security/patrolRouteList', 'admin/PatrolRoute/lists');
    Route::post('admin/security/patrolRouteAdd', 'admin/PatrolRoute/add');
    Route::post('admin/security/patrolRouteEdit', 'admin/PatrolRoute/edit');
    Route::post('admin/security/patrolRouteDelete', 'admin/PatrolRoute/delete');
    Route::get('admin/security/patrolRecordList', 'admin/PatrolRecord/lists');

    // 门禁卡
    Route::get('admin/security/accessCardList', 'admin/AccessCard/lists');
    Route::post('admin/security/accessCardAdd', 'admin/AccessCard/add');
    Route::post('admin/security/accessCardEdit', 'admin/AccessCard/edit');
    Route::post('admin/security/accessCardDelete', 'admin/AccessCard/delete');

    // 车位管理
    Route::get('admin/parking/spaceList', 'admin/ParkingSpace/lists');
    Route::post('admin/parking/spaceAdd', 'admin/ParkingSpace/add');
    Route::post('admin/parking/spaceEdit', 'admin/ParkingSpace/edit');
    Route::post('admin/parking/spaceDelete', 'admin/ParkingSpace/delete');

    // 车辆管理
    Route::get('admin/parking/vehicleList', 'admin/Vehicle/lists');
    Route::post('admin/parking/vehicleAdd', 'admin/Vehicle/add');
    Route::post('admin/parking/vehicleEdit', 'admin/Vehicle/edit');
    Route::post('admin/parking/vehicleDelete', 'admin/Vehicle/delete');

    // 停车记录
    Route::get('admin/parking/recordList', 'admin/ParkingRecord/lists');
    Route::post('admin/parking/recordAdd', 'admin/ParkingRecord/add');
    Route::post('admin/parking/recordDelete', 'admin/ParkingRecord/delete');

    // 公告管理
    Route::get('admin/notice/list', 'admin/Notice/lists');
    Route::post('admin/notice/add', 'admin/Notice/add');
    Route::post('admin/notice/edit', 'admin/Notice/edit');
    Route::post('admin/notice/delete', 'admin/Notice/delete');
    Route::post('admin/notice/publish', 'admin/Notice/publish');

    // 设备管理
    Route::get('admin/equipment/list', 'admin/Equipment/lists');
    Route::post('admin/equipment/add', 'admin/Equipment/add');
    Route::post('admin/equipment/edit', 'admin/Equipment/edit');
    Route::post('admin/equipment/delete', 'admin/Equipment/delete');
    Route::get('admin/equipment/maintainList', 'admin/EquipmentMaintain/lists');
    Route::post('admin/equipment/maintainAdd', 'admin/EquipmentMaintain/add');
    Route::post('admin/equipment/maintainDelete', 'admin/EquipmentMaintain/delete');

    // 投诉管理
    Route::get('admin/complaint/list', 'admin/Complaint/lists');
    Route::post('admin/complaint/handle', 'admin/Complaint/handle');
    Route::post('admin/complaint/delete', 'admin/Complaint/delete');
    Route::get('admin/complaint/detail', 'admin/Complaint/detail');

    // 业主投票
    Route::get('admin/vote/list', 'admin/Vote/lists');
    Route::post('admin/vote/add', 'admin/Vote/add');
    Route::post('admin/vote/edit', 'admin/Vote/edit');
    Route::post('admin/vote/delete', 'admin/Vote/delete');
    Route::get('admin/vote/detail', 'admin/Vote/detail');
    Route::post('admin/vote/publish', 'admin/Vote/publish');
    Route::post('admin/vote/close', 'admin/Vote/close');
    Route::get('admin/vote/result', 'admin/Vote/result');

    // 社区活动
    Route::get('admin/activity/list', 'admin/Activity/lists');
    Route::post('admin/activity/add', 'admin/Activity/add');
    Route::post('admin/activity/edit', 'admin/Activity/edit');
    Route::post('admin/activity/delete', 'admin/Activity/delete');
    Route::get('admin/activity/detail', 'admin/Activity/detail');
    Route::post('admin/activity/publish', 'admin/Activity/publish');
    Route::post('admin/activity/start', 'admin/Activity/start');
    Route::post('admin/activity/complete', 'admin/Activity/complete');
    Route::post('admin/activity/cancel', 'admin/Activity/cancel');
    Route::get('admin/activity/signups', 'admin/Activity/signups');
    Route::post('admin/activity/cancelSignup', 'admin/Activity/cancelSignup');

    // 数据概览
    Route::get('admin/dashboard/statistics', 'admin/Dashboard/statistics');
    Route::get('admin/dashboard/incomeChart', 'admin/Dashboard/incomeChart');
    Route::get('admin/dashboard/repairChart', 'admin/Dashboard/repairChart');
    Route::get('admin/dashboard/pieChart', 'admin/Dashboard/pieChart');

    // 公共接口
    Route::get('admin/upload/image', 'admin/Upload/image');
    Route::post('admin/upload/file', 'admin/Upload/file');
    Route::get('admin/upload/delete', 'admin/Upload/delete');

    // 个人中心
    Route::get('admin/profile/info', 'admin/Profile/info');
    Route::post('admin/profile/edit', 'admin/Profile/edit');
    Route::post('admin/profile/password', 'admin/Profile/password');

    // 打印
    Route::get('admin/print/receipt', 'admin/Print/receipt');
    Route::get('admin/print/notice', 'admin/Print/notice');

    // 物业人员
    Route::get('admin/staff/lists', 'admin/Staff/lists');
    Route::post('admin/staff/add', 'admin/Staff/add');
    Route::post('admin/staff/edit', 'admin/Staff/edit');
    Route::post('admin/staff/delete', 'admin/Staff/delete');
    Route::get('admin/staff/detail', 'admin/Staff/detail');

    // 考勤管理
    Route::get('admin/attendance/lists', 'admin/Attendance/lists');
    Route::post('admin/attendance/add', 'admin/Attendance/add');
    Route::post('admin/attendance/edit', 'admin/Attendance/edit');
    Route::post('admin/attendance/delete', 'admin/Attendance/delete');
    Route::post('admin/attendance/batch', 'admin/Attendance/batch');

    // 排班管理
    Route::get('admin/schedule/lists', 'admin/Schedule/lists');
    Route::post('admin/schedule/add', 'admin/Schedule/add');
    Route::post('admin/schedule/edit', 'admin/Schedule/edit');
    Route::post('admin/schedule/delete', 'admin/Schedule/delete');
    Route::post('admin/schedule/batch', 'admin/Schedule/batch');

    // 工资管理
    Route::get('admin/salary/lists', 'admin/Salary/lists');
    Route::post('admin/salary/add', 'admin/Salary/add');
    Route::post('admin/salary/edit', 'admin/Salary/edit');
    Route::post('admin/salary/delete', 'admin/Salary/delete');
    Route::post('admin/salary/pay', 'admin/Salary/pay');
    Route::post('admin/salary/batchGenerate', 'admin/Salary/batchGenerate');

    // 外部供应商
    Route::get('admin/supplier/lists', 'admin/Supplier/lists');
    Route::post('admin/supplier/add', 'admin/Supplier/add');
    Route::post('admin/supplier/edit', 'admin/Supplier/edit');
    Route::post('admin/supplier/delete', 'admin/Supplier/delete');
    Route::get('admin/supplier/detail', 'admin/Supplier/detail');

    // 采购订单
    Route::get('admin/purchase/lists', 'admin/Purchase/lists');
    Route::post('admin/purchase/add', 'admin/Purchase/add');
    Route::post('admin/purchase/edit', 'admin/Purchase/edit');
    Route::post('admin/purchase/delete', 'admin/Purchase/delete');
    Route::post('admin/purchase/approve', 'admin/Purchase/approve');
    Route::post('admin/purchase/complete', 'admin/Purchase/complete');

    // 合同管理
    Route::get('admin/contract/lists', 'admin/Contract/lists');
    Route::post('admin/contract/add', 'admin/Contract/add');
    Route::post('admin/contract/edit', 'admin/Contract/edit');
    Route::post('admin/contract/delete', 'admin/Contract/delete');
    Route::post('admin/contract/expire', 'admin/Contract/expire');

    // 供应商评价
    Route::get('admin/evaluation/lists', 'admin/Evaluation/lists');
    Route::post('admin/evaluation/add', 'admin/Evaluation/add');
    Route::post('admin/evaluation/edit', 'admin/Evaluation/edit');
    Route::post('admin/evaluation/delete', 'admin/Evaluation/delete');
    Route::get('admin/evaluation/stats', 'admin/Evaluation/stats');

    // 支付配置
    Route::get('admin/payment/configList', 'admin/PaymentConfig/lists');
    Route::get('admin/payment/configDetail', 'admin/PaymentConfig/detail');
    Route::post('admin/payment/configSave', 'admin/PaymentConfig/save');
    Route::get('admin/payment/configTest', 'admin/PaymentConfig/test');

    // 公众号配置
    Route::get('admin/wechat/configList', 'admin/WechatConfig/lists');
    Route::get('admin/wechat/configDetail', 'admin/WechatConfig/detail');
    Route::post('admin/wechat/configSave', 'admin/WechatConfig/save');
    Route::get('admin/wechat/configTest', 'admin/WechatConfig/test');

    // 短信配置
    Route::get('admin/sms/list', 'admin/Sms/lists');
    Route::get('admin/sms/detail', 'admin/Sms/detail');
    Route::post('admin/sms/save', 'admin/Sms/save');
    Route::get('admin/sms/test', 'admin/Sms/test');
});
