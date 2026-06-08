# 🔧 大圣物业管理系统 — 开发者手册 v3.0

> **阅读对象**：后端/前端程序员、架构师、运维人员  
> **目标**：一文档看懂全部功能、架构、文件、组件，快速上手开发  
> **生成日期**：2026-06-07 · **最后更新**：2026-06-08

---

## 📐 一、项目全景

```
┌──────────────────────────────────────────────────────────────────────┐
│                    大圣物业管理系统 (Dasheng PMS)                       │
├──────────────────────────────────────────────────────────────────────┤
│  5个前端 + 5个API端 + 40+张表 + 116个控制器 + 100+Vue组件               │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │PC管理后台 │ │移动端H5  │ │独立前端  │ │ 3D孪生   │ │ 产品官网  │  │
│  │admin/src/│ │admin/src/│ │frontend/ │ │3d-yanshi/│ │ wg/      │  │
│  │70+页面   │ │15+页面   │ │27个页面  │ │5个小区   │ │4个页面   │  │
│  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘  │
│       └─────────────┴────────────┼────────────┴─────────────┘       │
│                                  │ REST API (JSON)                   │
│                                  ▼                                   │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │       PHP 后端 (ThinkPHP6风格 自研轻量框架)                    │    │
│  │  入口：public/index.php → 5套路由 → 5套中间件 → 控制器         │    │
│  │  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐   │    │
│  │  │ admin/ │ │ api/   │ │ staff/ │ │manager/│ │ device/│   │    │
│  │  │ 85控制 │ │ 16控制 │ │ 11控制 │ │ 4控制  │ │ 2控制  │   │    │
│  │  └────┬───┘ └───┬────┘ └───┬────┘ └───┬────┘ └───┬────┘   │    │
│  │       └──────────┴──────────┴──────────┴──────────┘         │    │
│  └──────────────────────────┬──────────────────────────────────┘    │
│                             │                                       │
│  ┌──────────────────────────▼──────────────────────────────────┐    │
│  │           MySQL 8.0 (dasheng) — 40+ 张表 ds_ 前缀             │    │
│  └──────────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 📂 二、目录结构速查

```
e:\ds\
│
├── 📄 DEVELOPMENT.md          ← 👈 你正在看的文档
├── 📄 .env                     ← 环境变量 (数据库/JWT/微信key)
│
├── 📁 public/                  ← Web根目录 (nginx root)
│   ├── index.php               ← ★ 入口文件 (SPA fallback / 路由分发)
│   ├── admin/                  ← admin前端构建输出 (Vite output)
│   │   ├── index.html
│   │   └── assets/             ← JS/CSS 打包产物
│   ├── 3d-yanshi/ 🆕          ← 数字孪生大屏
│   │   ├── index.html
│   │   ├── main.js             ← Three.js 5小区场景 (~1700行)
│   │   └── ai-chat.js/css      ← AI助手浮窗组件
│   ├── wg/ 🆕                  ← 产品官网
│   │   ├── index.html
│   │   └── css/ js/ images/
│   └── owner.html/staff.html   ← 独立前端构建输出
│
├── 📁 config/                  ← 配置文件
│   ├── app.php                 ← 应用名称 / 域名 / 时区
│   ├── database.php            ← 数据库连接 / Redis
│   ├── jwt.php                 ← JWT密钥 / 过期时间
│   ├── cors.php                ← CORS跨域白名单
│   ├── middleware.php          ← ★ 中间件绑定 (哪个模块用哪些中间件)
│   ├── route.php               ← 路由策略
│   ├── cache.php               ← 缓存配置
│   ├── log.php                 ← 日志配置
│   ├── device.php              ← 设备密钥
│   ├── filesystem.php          ← 上传配置
│   └── staff.php               ← 允许登录员工端的角色
│
├── 📁 route/                   ← ★ 路由表 (6个文件，约150+条路由)
│   ├── admin.php               ← 后台管理路由
│   ├── api.php                 ← 业主端API路由
│   ├── staff.php               ← 物业员工路由
│   ├── manager.php             ← 小区经理路由
│   ├── device.php              ← 硬件设备路由
│   └── home.php                ← 官网路由
│
├── 📁 middleware/               ← ★ 中间件 (6个)
│   ├── AuthMiddleware.php      ← JWT认证(type=admin) + 登录频率限流
│   ├── ApiAuthMiddleware.php   ← JWT认证(type=owner) + 频率限流
│   ├── StaffAuthMiddleware.php ← JWT认证(type=staff|manager)
│   ├── CorsMiddleware.php      ← CORS跨域处理
│   ├── LogMiddleware.php       ← 操作日志 (POST/PUT/DELETE/PATCH)
│   └── DeviceAuthMiddleware.php← 设备密钥认证
│
├── 📁 app/                     ← 应用代码 (MVC - Controller层)
│   ├── BaseController.php      ← 抽象基类 (success/error/table/分页)
│   ├── Request.php             ← 请求对象 (JSON解析/修饰符)
│   ├── ExceptionHandle.php     ← 异常处理
│   ├── common.php              ← 公共函数 (加密/限流/密码/IP等)
│   │
│   ├── 📁 admin/               ← 后台管理 84个控制器
│   │   ├── BaseAdmin.php       ← ★ Admin基类 (JWT/权限/小区隔离)
│   │   └── controller/         ← 各功能控制器
│   │
│   ├── 📁 api/                 ← 业主端API 15个控制器
│   │   └── controller/
│   │
│   ├── 📁 staff/               ← 物业员工端 11个控制器
│   │   └── controller/
│   │
│   ├── 📁 manager/             ← 小区经理端 4个控制器
│   │   └── controller/
│   │
│   └── 📁 device/              ← 硬件设备端 2个控制器
│       └── controller/
│
├── 📁 extend/                  ← 工具/服务类
│   └── service/
│       └── WechatService.php   ← 微信SDK封装 (OAuth/支付/模板消息)
│
├── 📁 admin/                   ← ★ PC管理面板 Vue3 项目
│   ├── package.json            ← 依赖 / 脚本
│   ├── vite.config.ts          ← Vite配置 (代理/构建路径)
│   ├── index.html              ← 入口HTML
│   └── src/
│       ├── main.ts             ← Vue应用入口
│       ├── App.vue             ← 根组件
│       ├── router/index.ts     ← ★ 路由表 (40+PC页 + 15个移动页)
│       ├── stores/             ← Pinia状态管理
│       │   ├── user.ts         ← 用户/Token/菜单/权限
│       │   └── app.ts          ← 侧边栏/标签页
│       ├── utils/request.ts    ← Axios封装 (拦截器/token注入)
│       ├── layout/             ← 布局组件
│       │   ├── MainLayout.vue  ← PC主布局 (侧边栏+顶栏+标签页+内容)
│       │   └── ...
│       └── views/              ← 业务页面组件 (~100个Vue文件)
│           ├── login/          ← 登录页
│           ├── dashboard/      ← 控制台 + 数据大屏
│           ├── system/         ← 系统管理 (用户/角色/菜单/日志等)
│           ├── property/       ← 房产管理 (小区/楼栋/房间)
│           ├── owner/          ← 业主管理
│           ├── charge/         ← 收费管理 (项目/账单/支付/抄表/财务/欠费)
│           ├── repair/         ← 报修管理
│           ├── security/       ← 安保管理 (访客/巡更/门禁)
│           ├── parking/        ← 停车管理 (车位/车辆/记录/费率/缴费)
│           ├── notice/         ← 公告通知
│           ├── equipment/      ← 设备管理 (台账/维保/电梯/硬件)
│           ├── complaint/      ← 投诉管理
│           ├── print/          ← 打印中心
│           ├── staff/          ← 物业人员 (档案/考勤/排班/工资)
│           ├── supplier/       ← 供应商管理
│           ├── lease/          ← 租赁管理
│           ├── sms/            ← 短信管理
│           ├── payment/        ← 支付配置
│           ├── wechat/         ← 微信公众号
│           ├── profile/        ← 个人中心
│           ├── decoration/     ← 装修管理
│           └── mobile/         ← ★ 所有移动端H5页面
│               ├── staff/      ← 员工端移动页
│               ├── manager/    ← 经理端移动页
│               └── owner/      ← 业主端移动页
│
├── 📁 frontend/                ← 独立前端应用 (Vue3多入口构建)
│   ├── owner.html              ← 业主端入口
│   ├── staff.html              ← 员工端入口
│   ├── manager.html            ← 经理端入口
│   ├── vite.config.js          ← 多入口Vite构建
│   └── src/
│       ├── shared/             ← 共享模块 (api.js / utils.js / Toast)
│       ├── owner/              ← 业主端 (15个页面)
│       ├── staff/              ← 员工端 (10个页面)
│       └── manager/            ← 经理端 (2个页面)
│
└── 📁 database/                ← 数据库
    └── dasheng.sql             ← ★ 完整建表+初始数据 (35张表)
```

---

## 🏗️ 三、后端架构

### 3.1 框架概述

- **框架风格**：ThinkPHP 6 自研轻量封装（非完整版TP6）
- **入口文件**：`public/index.php` — 自实现路由解析 + 请求分发
- **PHP要求**：7.4+ / 8.x
- **自动加载**：Composer (`vendor/autoload.php`)
- **主要依赖**：`firebase/php-jwt` (JWT)、`topthink/think-orm` (数据库)

### 3.2 请求生命周期

```
浏览器请求
  │
  ▼
┌─────────────────────────────────────────────────┐
│ public/index.php                                 │
│  1. 加载 .env → $_ENV                           │
│  2. CORS头设置 (动态Origin白名单)                │
│  3. 创建 app\Request 对象                       │
│  4. URL路由解析                                  │
│     ├── 剥离 /index.php/ 或 /api.php/ 前缀       │
│     ├── 处理双重 /api/api/ 前缀                  │
│     ├── 路由缓存读取/刷新                        │
│     └── Route::get() 格式解析 → 模块/控制器/方法  │
│  5. 实例化控制器                                 │
│     ├── 执行中间件链 (middleware.php)            │
│     │   ├── CorsMiddleware (跨域)               │
│     │   ├── AuthMiddleware (JWT认证+频率限流)    │
│     │   └── LogMiddleware (操作日志)             │
│     ├── 控制器 initialize() → auth()            │
│     └── 控制器 action()                         │
│  6. 异常捕获 → 友好错误JSON                      │
└─────────────────────────────────────────────────┘
```

### 3.3 中间件绑定关系 (`config/middleware.php`)

| 模块 | URL前缀示例 | 中间件链 |
|------|------------|----------|
| `admin` | `/admin/Login`, `/admin/Bill` | AuthMiddleware → LogMiddleware |
| `api` | `/api/login`, `/api/bill` | ApiAuthMiddleware → CorsMiddleware |
| `staff` | `/staff/login`, `/staff/repair` | StaffAuthMiddleware → CorsMiddleware |
| `manager` | `/manager/login` | StaffAuthMiddleware → CorsMiddleware |
| `device` | `/device/gateway` | DeviceAuthMiddleware |

### 3.4 JWT Token 结构

```json
{
  "iss": "ds_property_manager",
  "iat": 1717564800,
  "exp": 1717651200,
  "sub": 1,
  "type": "admin|owner|staff|manager"
}
```

- **密钥**：`ds_property_manager_jwt_key_2026`（jwt.php 中配置）
- **算法**：HS256
- **有效期**：签发后 24 小时
- **type 字段**：用于跨端越权防护（admin token 不能访问 staff API）

### 3.5 公共函数速查 (`app/common.php`)

| 函数 | 用途 |
|------|------|
| `env($key, $default)` | 获取 .env 环境变量 |
| `request()` | 获取当前请求对象 |
| `json_success($data)` | 成功 JSON `{code:0, msg, data}` |
| `json_error($msg)` | 错误 JSON `{code:1, msg}` |
| `json_table($list, $total)` | 表格数据 `{code:0, data:{list, total}}` |
| `get_admin_id()` | 当前登录管理员ID |
| `build_order_no($prefix)` | 生成唯一订单号 |
| `encrypt_password($pwd)` | bcrypt 加密 (cost=12) |
| `verify_password($pwd, $hash)` | 密码验证 (兼容bcrypt+旧版MD5) |
| `mask_phone($phone)` | 手机号脱敏 `138****1234` |
| `mask_id_card($idCard)` | 身份证脱敏 |
| `tree_list($data, $pid)` | 数据转树形结构 |
| `get_client_ip()` | 获取客户端真实IP |
| `login_rate_limit_check($ip, $username)` | 登录频率校验 (5分钟内最多10次) |

### 3.6 Admin 基类权限机制 (`app/admin/BaseAdmin.php`)

```
auth() 认证流程：
  token → JWT decode → type=admin? → 查 admin_user 表 → status=1? → checkPermission()
                                             ↓
                                      bindCommunityIds (多小区数据隔离)

checkPermission() 权限流程：
  role_id=1? → 超管放行
  ↓
  查 ds_role 表 → role code → getRolePermissions()
  ↓
  预定义角色? → 硬编码白名单 + ds_role_menu动态扩展
  自定义角色? → ds_role_menu + ds_menu.permission → buildPermissionMap()
  ↓
  当前控制台名 in allowed? → 放行 / 拒绝
```

**7种角色及权限范围**：

| role_id | code | 权限 |
|---------|------|------|
| 1 | admin | 全控 `*`，所有模块 |
| 2 | super_admin | 同全控 |
| 3 | manager | 小区经理：房产/业主/收费/报修/安保/设备/打印/租赁/装修 (~60个控制器) |
| 4 | service | 客服：投诉/报修/业主/房产/装修 |
| 5 | finance | 财务：账单/支付/收费项目/抄表/欠费/设备/装修 |
| 6 | security | 安保：访客/巡更/门禁/停车/业主/装修 |
| 7 | engineer | 工程：设备台账/维保/采购/供应商/合同/装修 |

---

## 🎮 四、完整功能模块速查

### 4.1 后台管理 Admin (app/admin/controller/) — 85个控制器

| 分类 | 控制器文件 | 主要功能 |
|------|-----------|----------|
| **系统管理** | Login | PC登录(账号密码/验证码/微信OAuth/扫码) |
| | AdminUser | 管理员CRUD |
| | Role | 角色管理 |
| | Menu | 菜单管理（树形） |
| | Config | 系统参数配置（键值对） |
| | Profile | 个人信息/改密码 |
| | Log | 操作日志查看 |
| | PushDevice | 推送设备注册 |
| | SseEvent | SSE实时推送事件 |
| | ServiceVendor | 服务商联系方式 |
| **房产管理** | Community | 小区CRUD（支持多小区数据隔离） |
| | Building | 楼栋 |
| | Room | 房间 |
| **业主管理** | Owner | 业主档案 |
| | Family | 家庭成员 |
| | Vote | 业主投票 |
| | Activity | 社区活动 |
| | Notice | 公告通知 |
| | Complaint | 投诉建议处理 |
| **收费管理** | ChargeItem | 收费项目（物业费/停车费等） |
| | Bill | 账单生成/发送 |
| | Payment | 缴费记录 |
| | Meter | 抄表管理（水/电/气） |
| | Arrears | 欠费管理 |
| | Finance | 财务流水 |
| | Deposit | 押金管理 |
| | Invoice | 发票记录 |
| | InvoiceInfo | 发票抬头 |
| | UnifiedPayment | 统一支付入口 |
| | BillDunning | 催缴记录 |
| **报修管理** | RepairOrder | 工单管理（派单/接单/完工/评价） |
| | RepairWorker | 维修人员管理 |
| **安保管理** | Visitor | 访客登记 |
| | PatrolRoute | 巡更路线 |
| | PatrolRecord | 巡更记录 |
| | AccessCard | 门禁卡管理 |
| | Vehicle | 车辆登记 |
| **停车管理** | ParkingSpace | 车位管理 |
| | ParkingRecord | 停车记录 |
| | ParkingFeeRule | 停车费率 |
| | ParkingPayment | 停车缴费 |
| **设备管理** | Equipment | 设备台账 |
| | EquipmentMaintain | 维保记录 |
| | Device | 硬件设备(IoT) |
| | DeviceEvent | 设备事件 |
| | Elevator | 电梯台账 |
| | ElevatorFault | 电梯故障 |
| | ElevatorInspection | 电梯巡检 |
| **物业人员** | Staff | 员工档案 |
| | Attendance | 考勤管理 |
| | Schedule | 排班管理 |
| | Salary | 工资管理 |
| **供应商** | Supplier | 供应商名录 |
| | Purchase | 采购订单 |
| | Contract | 合同管理 |
| | Evaluation | 供应商评价 |
| **租赁管理** | LeaseProperty | 可租赁房源 |
| | LeaseTenant | 租客信息 |
| | LeaseContract | 租赁合同 |
| | LeasePayment | 租金支付 |
| | LeaseTermination | 退租记录 |
| **打印中心** | Print | 打印功能入口 |
| | PrintTemplate | 打印模板 |
| | PrintLog | 打印日志 |
| **短信管理** | Sms | 短信服务入口 |
| | SmsTemplate | 短信模板 |
| | SmsSend | 短信发送 |
| | SmsLog | 短信日志 |
| | SmsCode | 短信验证码 |
| **支付配置** | PaymentConfig | 微信/支付宝支付参数 |
| | CommunityPaymentConfig | 小区级支付配置 |
| **微信公众号** | WechatConfig | 公众号配置(appId/secret) |
| | CommunityWechatConfig | 小区级公众号配置 |
| | WechatUser | 微信用户管理 |
| | WechatMpFan | 公众号粉丝 |
| | WechatMpTemplate | 模板消息 |
| | WechatTemplate | 消息模板 |
| **IoT+AI** | Iot | IoT设备管理（设备CRUD/类型/协议/实时数据）+ 与3D场景数据联动 |
| | AiAssistant | AI报修助手配置（关键词/欢迎语）+ 对话记录 + 统计图表 |
| **装修管理** | Decoration | 装修申请/审批/巡查/违规 |
| **通用** | Upload | 文件上传 |
| | AdminBadge | 后台角标（待办数量） |

### 4.2 业主端 API (app/api/controller/) — 16个控制器

| 控制器 | 用户操作 |
|--------|----------|
| Login | 手机号登录/注册/改密/微信OAuth/绑定解绑 |
| Index | 首页Banner/公告/我的信息(房产+欠费+报修数) |
| Badge | 角标(未读消息+待处理+待缴费用) |
| Room | 我的房产列表/详情 |
| Bill | 账单列表(按状态筛)/未缴/支付(微信/支付宝/线下)/支付回调 |
| Repair | 提交报修/列表/详情/评价 |
| Complaint | 提交投诉/我的投诉 |
| Notice | 公告列表/详情(阅读计数) |
| Visitor | 预约访客/记录 |
| Vehicle | 车辆添加/列表 |
| Vote | 投票列表/详情/提交(支持多选) |
| Activity | 活动列表/报名/取消/我的报名 |
| Claim | 手机号认领房产(事务转移) |
| Profile | 个人信息/修改/改密码 |
| AiRepair | ★ AI智能报修：chat(对话识别) / submit(提交工单自动派单) / quickTypes |
| IotData | ★ 数字孪生IoT：getDevices(按小区读设备+实时数据) |
| Consultation | ★ 官网留言：add(公开提交/手机校验/IP记录) |
| WechatServer | 公众号服务器回调(验证/消息/关注/取消关注) |

### 4.3 员工端 (app/staff/controller/) — 11个控制器

| 控制器 | 功能 |
|--------|------|
| StaffLogin | 登录/微信OAuth/绑定已有账号 |
| StaffRepair | 维修工单接单/完工(上传照片) |
| StaffCharge | 现场收费 |
| StaffMeter | 抄表录入 |
| StaffPatrol | 巡更打卡 |
| StaffVisitor | 访客登记/核销 |
| StaffComplaint | 投诉处理 |
| StaffOrder | 工单处理 |
| StaffDecoration | 装修巡查 |
| StaffProfile | 个人信息/改密码 |
| StaffBadge | 待办角标 |

### 4.4 经理端 (app/manager/controller/) — 4个控制器

| 控制器 | 功能 |
|--------|------|
| ManagerLogin | 登录/微信OAuth/注册(待审核) |
| Dashboard | 驾驶舱：小区总览/收入统计/欠费/报修/投诉/入住率 |
| ManagerActivity | 活动管理/审批报名 |
| ManagerVote | 投票管理/查看结果 |

---

## 🎨 五、前端架构

### 5.1 Admin 面板 (admin/)

**技术栈**：
- Vue 3.4 + TypeScript 5.0 + Vite 5.0
- Element Plus 2.x (按需自动导入)
- Pinia 2.x 状态管理
- ECharts 5.x 图表
- Axios HTTP请求

**核心文件**：

| 文件 | 作用 |
|------|------|
| `vite.config.ts` | base='/admin/', 代理 /api→127.0.0.1:8000, outDir=../public/admin |
| `src/router/index.ts` | ★ 所有路由定义 (PC 40+页 + 移动端 15页) |
| `src/stores/user.ts` | 登录/Token/用户信息/菜单/权限 |
| `src/stores/app.ts` | 侧边栏折叠/标签页管理(只保留当前页) |
| `src/utils/request.ts` | Axios封装：baseURL='/api', 自动注入 Bearer token, 401跳转登录 |
| `src/layout/MainLayout.vue` | PC主布局：左侧菜单+顶栏+标签页+内容区 |

**路由表 (PC端管理页面，约70个路由)**：

```
/admin/                            → 重定向到 /dashboard
/admin/login                       → 登录页
/admin/dashboard                   → 控制台 (欢迎页+统计卡片)
/admin/bigscreen                   → 数据大屏 (全屏ECharts可视化)
/admin/profile                     → 个人中心

/admin/system/admin                → 用户管理 (后台管理员)
/admin/system/role                 → 角色管理
/admin/system/menu                 → 菜单管理
/admin/system/config               → 系统配置
/admin/system/log                  → 操作日志
/admin/system/PushDevice           → 推送设备
/admin/system/SseEvent             → SSE事件
/admin/system/ServiceVendor        → 服务商联系

/admin/property/community          → 小区管理
/admin/property/building           → 楼栋管理
/admin/property/room               → 房间管理

/admin/owner/index                 → 业主管理
/admin/owner/family                → 家庭成员
/admin/owner/vote                  → 业主投票
/admin/owner/activity              → 社区活动
/admin/owner/notice                → 公告通知
/admin/owner/complaint             → 投诉建议
/admin/owner/signup                → 活动报名

/admin/charge/item                 → 收费项目
/admin/charge/bill                 → 账单管理
/admin/charge/payment              → 缴费记录
/admin/charge/meter                → 抄表管理
/admin/charge/finance              → 财务流水
/admin/charge/arrears              → 欠费管理
/admin/charge/Deposit              → 押金管理
/admin/charge/Invoice              → 发票记录
/admin/charge/InvoiceInfo          → 发票抬头
/admin/charge/UnifiedPayment       → 统一支付
/admin/charge/dunning              → 催缴记录

/admin/repair/order                → 工单管理
/admin/repair/worker               → 维修人员

/admin/security/visitor            → 访客登记
/admin/security/patrol             → 巡更记录
/admin/security/patrol-route       → 巡更路线
/admin/security/access-card        → 门禁卡

/admin/parking/space               → 车位管理
/admin/parking/vehicle             → 车辆管理
/admin/parking/record              → 停车记录
/admin/parking/ParkingFeeRule      → 停车费率
/admin/parking/ParkingPayment      → 停车缴费

/admin/notice/index                → 公告列表
/admin/notice/Notification         → 消息推送
/admin/notice/message              → 消息记录

/admin/equipment/index             → 设备台账
/admin/equipment/maintain          → 维保记录
/admin/equipment/Device            → 硬件设备
/admin/equipment/DeviceEvent       → 设备事件
/admin/equipment/Elevator          → 电梯台账
/admin/equipment/ElevatorFault     → 电梯故障
/admin/equipment/ElevatorInspection→ 电梯巡检

/admin/complaint/index             → 投诉管理

/admin/print/receipt               → 收据打印
/admin/print/notice                → 催缴通知
/admin/print/PrintTemplate         → 打印模板
/admin/print/PrintLog              → 打印日志

/admin/staff/index                 → 员工档案
/admin/staff/attendance            → 考勤管理
/admin/staff/schedule              → 排班管理
/admin/staff/salary                → 工资管理

/admin/supplier/index              → 供应商名录
/admin/supplier/purchase           → 采购订单
/admin/supplier/contract           → 合同管理
/admin/supplier/evaluation         → 服务评价

/admin/lease/LeaseProperty         → 可租赁房源
/admin/lease/LeaseTenant           → 租客信息
/admin/lease/LeaseContract         → 租赁合同
/admin/lease/LeasePayment          → 租金支付
/admin/lease/LeaseTermination      → 退租记录

/admin/sms/config                  → 短信服务商配置
/admin/sms/template                → 短信模板
/admin/sms/send                    → 短信发送
/admin/sms/log                     → 短信日志

/admin/payment/config              → 支付配置
/admin/wechat/config               → 公众号配置
/admin/wechat/user                 → 微信用户

/admin/decoration/apply            → 装修申请
/admin/decoration/inspect          → 施工巡查
/admin/decoration/violation        → 违规记录
/admin/decoration/worker           → 施工人员

/admin/iot/device                  → IoT设备管理 (4Tab:设备/类型/协议/数据)
/admin/ai/assistant                → AI助手管理 (3Tab:配置/对话/统计)
```

**移动端 H5 路由 (内嵌在 admin 项目内)**：

```
/admin/mobile/staff/login          → 员工端登录
/admin/mobile/staff/home           → 员工首页
/admin/mobile/staff/repair         → 维修工单
/admin/mobile/staff/charge         → 现场收费
/admin/mobile/staff/patrol         → 巡更打卡

/admin/mobile/manager/login        → 经理端登录
/admin/mobile/manager/dashboard    → 经理驾驶舱

/admin/mobile/owner/login          → 业主端登录
/admin/mobile/owner/home           → 业主首页
/admin/mobile/owner/bill           → 我的账单
/admin/mobile/owner/repair         → 我要报修
/admin/mobile/owner/visitor        → 访客预约
/admin/mobile/owner/vehicle        → 我的车辆
/admin/mobile/owner/notice         → 小区公告
/admin/mobile/owner/complaint      → 投诉建议
```

### 5.2 独立前端应用 (frontend/)

- **构建方式**：Vue3 + Vite 多入口构建
- **入口文件**：`owner.html` / `staff.html` / `manager.html`
- **部署路径**：构建输出到对应目录，nginx 直接提供
- **API地址**：通过 vite.config.js 代理到 PHP 后端

**业主端 (owner) — 15个页面**：

| 页面 | 组件 |
|------|------|
| 登录 | LoginView.vue |
| 注册 | RegisterView.vue |
| 首页(含Banner/公告/快捷入口) | HomeView.vue |
| 我的房产 | RoomView.vue |
| 账单缴费 | BillView.vue |
| 报修 | RepairView.vue |
| 投诉 | ComplaintView.vue |
| 公告 | NoticeView.vue |
| 访客预约 | VisitorView.vue |
| 车辆管理 | VehicleView.vue |
| 投票 | VoteView.vue |
| 社区活动 | ActivityView.vue |
| 房产认领 | ClaimView.vue |
| AI助手 🆕 | AiChatWidget.vue (全局浮窗组件，登录后所有页面显示) |

**员工端 (staff) — 10个页面**：

| 页面 | 组件 |
|------|------|
| 登录 | LoginView.vue |
| 首页 | HomeView.vue |
| 报修处理 | RepairView.vue |
| 抄表 | MeterView.vue |
| 收费 | ChargeView.vue |
| 巡更 | PatrolView.vue |
| 访客 | VisitorView.vue |
| 工单 | OrderView.vue |
| 投诉 | ComplaintView.vue |
| 个人中心 | ProfileView.vue |

**经理端 (manager) — 2个页面**：

| 页面 | 组件 |
|------|------|
| 登录 | LoginView.vue |
| 数据仪表盘 | DashboardView.vue (41KB，最复杂视图) |

---

## 🏙️ 五.5、3D数字孪生 + IoT + AI 架构 🆕

### 5.5.1 整体数据流

```
┌─────────────────────────────────────────────────────────────┐
│  3D数字孪生大屏 (public/3d-yanshi/)                          │
│  main.js (67KB) · Three.js 0.160 · 5小区实时渲染             │
│                                                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                  │
│  │ buildIot  │  │ AI Chat   │  │ Community │                 │
│  │ Layer()   │  │ Widget    │  │ Switch    │                 │
│  │ 5s poll   │  │ 右下浮窗   │  │ 5 keys    │                 │
│  └─────┬─────┘  └─────┬─────┘  └──────────┘                  │
│        │              │                                       │
└────────┼──────────────┼───────────────────────────────────────┘
         │              │
         ▼              ▼
  GET /api/iot/devices  POST /api/ai/chat
  ?community=feicui     POST /api/ai/submit
         │              │
         ▼              ▼
  ┌──────────────────────────────────────────────────┐
  │          PHP 后端                                  │
  │  IotData::getDevices()    AiRepair::chat()        │
  │    ↓ 读这4张表              ↓ 词库匹配              │
  │  ds_iot_device              ai_type_keywords      │
  │  ds_iot_device_type         ai_urgent_keywords    │
  │  ds_iot_protocol          → 自动派单 RANDOM()      │
  │  ds_iot_device_data       → ds_repair_order       │
  └──────────────────────────────────────────────────┘
         ▲
         │ 管理端 CRUD (同一数据源)
  ┌──────┴───────────────────────────────────────────┐
  │  PC管理后台 Iot.vue / AiAssistant.vue              │
  │  Iot::deviceAdd/Edit/Delete                       │
  │  AiAssistant::config/chatHistory/stats            │
  └──────────────────────────────────────────────────┘
```

### 5.5.2 3D 数字孪生技术细节

**场景配置** (`main.js` 第24行 `communities` 对象)：

| 场景ID | 名称 | 建筑 | 户型 | 渲染函数 | 行号 |
|--------|------|------|------|----------|------|
| `feicui` | 翡翠名苑 | 14栋26-33层 | 高层住宅 | `buildFeicui()` | ~400 |
| `yunqi` | 云栖别墅 | 48栋3层 | 独栋别墅 | `buildYunqi()` | ~600 |
| `zhongliang` | 中粮壹号 | 6栋42-55层 | 超高层 | `buildZhongliang()` | ~800 |
| `shanshui` | 山水居 | 12栋3层 | 花园洋房 | `buildShanshui()` | ~1000 |
| `yifeng` | 怡丰城 | 9栋8-12层 | 商业住宅 | `buildYifeng()` | ~1200 |

**IoT 设备层** (第1115行起)：
- 设备坐标：x/z (平面位置) + y (高度，来自 device 表或 device_type.y_height)
- 发光球体：0.2~0.3 半径，颜色根据 device_status 变化
- 轮询机制：`setInterval(buildIotLayer, 5000)` — 每5秒拉取一次
- 场景切换：`clearScene()` → `stopIotPolling()` → 重建 → `buildIotLayer()`

**AI 助手** (`3d-yanshi/ai-chat.js` + `ai-chat.css`)：
- 右下角浮动按钮 (🤖图标)
- 展开聊天面板：欢迎语 → 快速类型按钮 → 打字动画
- 对话流程：用户输入 → `POST /api/ai/chat` → 后端识别 → 返回确认 → 用户确认 → `POST /api/ai/submit` 提交工单

### 5.5.3 小区编码映射

| 3D场景 | 数据库 ds_community.code | 备注 |
|--------|--------------------------|------|
| `feicui` | YG001 | 与怡丰城共用小区 |
| `yunqi` | CY001 | |
| `zhongliang` | 0004 | |
| `shanshui` | 001 | |
| `yifeng` | YG001 | 与翡翠名苑共用小区 |

> ⚠️ `feicui` 和 `yifeng` 映射到同一个数据库小区(YG001)，因此 IoT 设备数据一致

### 5.5.4 AI 关键词配置

**报修类型词库** (`ds_config.value`，key=`ai_type_keywords`)：
```json
{
  "水电": ["漏水","水管","水龙头","下水道","马桶","堵塞","停水","跳闸","停电","灯泡","灯","开关","插座"],
  "空调": ["空调","制冷","不热","不冷","暖风","通风","出风口"],
  "门窗": ["门","窗","门锁","把手","钥匙","门禁","窗户","玻璃"],
  "墙面": ["墙","裂缝","漏水","发霉","起皮","掉皮","墙纸"],
  "燃气": ["煤气","天然气","燃气","灶","打不着","漏气"],
  "电梯": ["电梯","困人","停运","抖动","异响"],
  "安保": ["监控","摄像头","门禁","可视","对讲","报警","消防"],
  "卫生": ["垃圾","清扫","保洁","卫生","异味","蟑螂","老鼠"],
  "停车": ["车位","停车","车库","道闸","车牌","充电桩"]
}
```

**紧急关键词** (`ai_urgent_keywords`)：`紧急 马上 立刻 赶紧 快 爆 着火 漏气 触电 困人 坍塌 危机 严重`

**派单逻辑**：`AiRepair::submit()` → 插入 `ds_repair_order` → 如果有紧急关键词设置 `is_urgent=1` → `RAND()` 随机匹配在线维修工 → 自动分配 `worker_id`

---



### 6.1 系统管理 (8张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_admin_user` | 后台管理员 | id, username, password(bcrypt), openid, role_id, community_ids, status |
| `ds_role` | 角色 | id, name, code, status |
| `ds_menu` | 菜单 | id, parent_id, title, icon, path, permission, sort, status |
| `ds_role_menu` | 角色-菜单关联 | role_id, menu_id |
| `ds_config` | 系统配置 | id, key, value, description |
| `ds_system_log` | 操作日志 | id, admin_id, action, method, url, ip, request(截断), response(截断) |
| `ds_push_device` | 推送设备 | id, user_type, user_id, platform, token |
| `ds_sse_event` | SSE实时事件 | id, event, data, created_at |

### 6.2 房产资源 (3张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_community` | 小区 | id, name, address, area, build_area, total_households |
| `ds_building` | 楼栋 | id, community_id, name, floors, units |
| `ds_room` | 房间 | id, community_id, building_id, unit, floor, room_no, area, status |

### 6.3 业主管理 (3张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_owner` | 业主 | id, community_id, name, phone(登录账号), password, openid, id_card, room_no |
| `ds_owner_family` | 家庭成员 | id, owner_id, name, phone, relation |
| `ds_owner_room` | 业主-房间关联 | id, owner_id, room_id, relation_type |

### 6.4 收费管理 (7张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_charge_item` | 收费项目 | id, community_id, name, unit_price, unit, cycle |
| `ds_bill` | 账单 | id, owner_id, room_id, charge_item_id, amount, paid_amount, status, due_date |
| `ds_bill_payment` | 缴费记录 | id, bill_id, amount, pay_method, pay_time, transaction_id |
| `ds_meter_reading` | 抄表读数 | id, room_id, meter_type, reading, reading_date |
| `ds_finance_flow` | 财务流水 | id, type(income/expense), amount, related_type, related_id |
| `ds_bill_dunning` | 催缴记录 | id, bill_id, method, content, dunning_time |
| `ds_deposit` | 押金 | id, owner_id, room_id, amount, status |

### 6.5 支付配置 (2张)

| 表名 | 说明 |
|------|------|
| `ds_community_payment_config` | 小区支付配置 (微信appId/mchId/key/支付宝appId等) |
| `ds_invoice_info` | 发票抬头信息 |

### 6.6 微信公众号 (2张)

| 表名 | 说明 |
|------|------|
| `ds_community_wechat_config` | 小区公众号配置 (app_id/app_secret/token/aes_key) |
| `ds_wechat_mp_fan` | 公众号粉丝 (openid/subscribe_status) |

### 6.7 报修管理 (2张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_repair_order` | 报修工单 | id, owner_id, room_id, type, description, images, status, worker_id, result, score |
| `ds_repair_worker` | 维修人员 | id, name, phone, specialty |

### 6.8 安保管理 (4张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_visitor` | 访客登记 | id, owner_id, visitor_name, visitor_phone, plate_no, visit_time, leave_time |
| `ds_patrol_route` | 巡更路线 | id, community_id, name, checkpoints(JSON) |
| `ds_patrol_record` | 巡更记录 | id, route_id, staff_id, checkpoint, scan_time, images |
| `ds_access_card` | 门禁卡 | id, card_no, owner_id, room_id, status |

### 6.9 停车管理 (3张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_parking_space` | 车位 | id, community_id, space_no, type, status |
| `ds_vehicle` | 车辆 | id, owner_id, plate_no, brand, color |
| `ds_parking_record` | 停车记录 | id, plate_no, entry_time, exit_time, fee |

### 6.10 设备管理 (2张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_equipment` | 设备台账 | id, community_id, name, type, location, status |
| `ds_equipment_maintain` | 维保记录 | id, equipment_id, maintain_type, maintain_date, description |

### 6.11 物业人员 (4张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_staff` | 员工档案 | id, community_id, name, phone, position |
| `ds_staff_attendance` | 考勤记录 | id, staff_id, date, check_in, check_out |
| `ds_staff_schedule` | 排班记录 | id, staff_id, date, shift |
| `ds_staff_salary` | 工资记录 | id, staff_id, month, base_salary, bonus, deduction |

### 6.12 供应商 (4张)

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_supplier` | 供应商 | id, name, contact, phone, type |
| `ds_purchase_order` | 采购订单 | id, supplier_id, items, amount, status |
| `ds_contract` | 合同 | id, supplier_id, title, amount, start_date, end_date |
| `ds_supplier_evaluation` | 供应商评价 | id, supplier_id, score, comment |

### 6.13 其他 (6张)

| 表名 | 说明 |
|------|------|
| `ds_notice` | 公告通知 (title/content/publish_time/read_count) |
| `ds_complaint` | 投诉建议 |
| `ds_attachment` | 附件 (file_path/file_type/related_type/related_id) |
| `ds_sms_log` | 短信发送记录 |
| `ds_message` | 消息推送记录 |
| `ds_community_activity` | 社区活动 (投票/报名) |

### 6.14 IoT 设备管理 (4张) 🆕

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_iot_device_type` | 设备类型 | id, name, code, category, unit, y_height, sort, status |
| `ds_iot_protocol` | 通信协议 | id, name, code, type, transport, port, frequency_band, data_rate, range, sort, status |
| `ds_iot_device` | 设备台账 | id, community_id, device_type_id, protocol_id, code, name, x, y, z, install_location, floor, building, battery_level, firmware_ver, status, last_online, install_date, delete_time |
| `ds_iot_device_data` | 实时数据 | id, device_id, raw_value, unit, is_online, device_status(normal/warning/alarm), alarm_msg, data_time |

> **数据联动**：3D 场景通过 `IotData::getDevices()` 读取这 4 张表 → 5 秒轮询刷新 → 管理端 `Iot::deviceAdd/Edit/Delete` 直接写入，两者共享同一数据源

### 6.15 AI 助手 + 官网 (2张) 🆕

| 表名 | 说明 | 关键字段 |
|------|------|----------|
| `ds_ai_chat_log` | AI 对话记录 | id, owner_id, message, reply, action, repair_type, create_time |
| `ds_consultation` | 官网咨询留言 | id, name, phone, type, content, ip, user_agent, status(read/unread), create_time |

> **AI配置**：存在 `ds_config` 表中 (key=ai_type_keywords / ai_urgent_keywords / ai_greeting / ai_welcome_tips)

---

## 🔐 七、安全机制

| 层级 | 机制 | 实现位置 |
|------|------|----------|
| **传输** | HTTPS + SSL证书验证 (微信API) | WechatService.php |
| **认证** | JWT (HS256, 24h过期) + Token type校验 | AuthMiddleware / StaffAuthMiddleware / ApiAuthMiddleware |
| **跨端** | admin token ≠ staff token ≠ owner token | 各中间件的 type 校验 |
| **频率** | 登录IP限流 (5分钟 ≤10次) | common.php → login_rate_limit_check() |
| **密码** | bcrypt (cost=12) + 兼容旧版MD5 | common.php → encrypt_password/verify_password |
| **上传** | 类型白名单 / 大小限制20MB | Upload.php + filesystem.php |
| **CORS** | 动态Origin白名单 (localhost:5173/3000) | CorsMiddleware.php + index.php |
| **权限** | 基于角色的控制器访问控制 (RBAC) | BaseAdmin.php → checkPermission() |
| **数据隔离** | 多小区角色只能操作绑定的小区数据 | BaseAdmin.php → getCommunityFilter() |
| **日志** | 操作日志记录 (脱敏密码/截断) | LogMiddleware.php |
| **SQL注入** | PDO参数绑定 + 错误信息生产脱敏 | index.php → ExceptionHandle |
| **微信注册** | 新注册经理 status=0 需管理员审核 | ManagerLogin.php → wechatRegister() |

---

## 📡 八、外部集成

### 8.1 微信生态

| 功能 | 实现 | 接口 |
|------|------|------|
| 公众号OAuth登录 | WechatService.php | snsapi_userinfo → openid |
| 公众号消息回调 | WechatServer.php | 验证URL(POST token验证) / 关注/取消关注事件 |
| 微信支付(JSAPI) | Bill.php | 统一下单 → JSAPI参数 → 前端调起 |
| 微信支付回调 | Bill.php | wechatNotify() → 验签 → 更新订单 |
| 支付宝支付 | Bill.php | 统一下单 → 跳转 → 同步/异步回调 |

### 8.2 SMS短信

- 支持阿里云/腾讯云短信
- 验证码发送 → ds_sms_log 记录
- 模板管理 → ds_sms_template

### 8.3 设备IoT

- 设备认证：`X-Device-Key` + `X-Device-Id` 头
- 设备网关：心跳/数据上报/配置下发
- 摄像头：识别结果回调

---

## 🚀 九、开发指南

### 9.1 本地开发启动

```bash
# 1. 启动PHP后端
cd e:\ds
php -S 127.0.0.1:8000 public/index.php

# 2. 启动Admin前端 (开发模式)
cd e:\ds\admin
npm install
npm run dev          # → http://localhost:3000/admin/

# 3. 启动独立前端应用 (开发模式)
cd e:\ds\frontend
npm install
npm run dev          # → 多入口开发服务器

# 4. 构建Admin前端
cd e:\ds\admin
npm run build        # → 输出到 ../public/admin/

# 5. 数据库
mysql -u root -p <database/dasheng.sql
# 默认管理员: admin / password (首次登录后修改)
```

### 9.2 添加新功能的标准流程

```
1. 建表
   └── database/dasheng.sql 添加 CREATE TABLE
   └── 表前缀 ds_

2. 后端控制器
   └── app/admin/controller/NewFeature.php
       extends app\admin\BaseAdmin
       └── public function lists() → $this->table()
       └── public function add() → $this->success()
       └── public function edit() → $this->success()
       └── public function delete() → $this->success()

3. 注册路由
   └── route/admin.php
       Route::any('/admin/NewFeature/lists', 'admin/NewFeature/lists')
       Route::post('/admin/NewFeature/add', 'admin/NewFeature/add')
       ...

4. 权限配置
   └── ds_menu 表插入新菜单记录 (填写 permission 字段如 'new:feature')
   └── BaseAdmin.php → buildPermissionMap() 添加 'new:feature' => 'NewFeature'
   └── BaseAdmin.php → getRolePermissions() 在对应角色白名单加入 'NewFeature'

5. 前端页面
   └── admin/src/views/newfeature/NewFeature.vue
       ├── 搜索表单
       ├── 数据表格 (Element Plus)
       └── 增/改弹窗

6. 注册路由
   └── admin/src/router/index.ts
       { path: 'new/feature', name: 'NewFeature', component: ..., meta: { title: '新功能' } }

7. 构建部署
   └── cd admin && npm run build
   └── 上传 PHP 文件 + public/admin/ 到服务器
```

### 9.3 关键编码规范

- **响应格式**：`{code:0, msg:'success', data:{...}}` (code=0成功，非0错误)
- **分页格式**：`{code:0, msg:'success', data:{list:[...], total:100}}`
- **控制器方法**：`lists()`列表 / `add()`新增 / `edit()`修改 / `delete()`删除 / `detail()`详情
- **小区隔离**：查询时调用 `$this->getCommunityFilter()` 获取过滤条件
- **小区权限**：写操作调用 `$this->validateCommunityAccess($communityId)` 校验
- **密码处理**：永远用 `encrypt_password()` 加密，`verify_password()` 验证
- **错误返回**：用 `$this->throwError('错误信息')` 而非直接 `echo`
- **前端API**：用 `apiGet/ apiPost/ apiTable` 而非直接 axios

### 9.4 部署路径说明

| 路径 | 用途 |
|------|------|
| `/www/wwwroot/www.hbdxm.com/` | Nginx root |
| `/www/wwwroot/www.hbdxm.com/public/index.php` | PHP入口 |
| `/www/wwwroot/www.hbdxm.com/public/admin/` | ★ Admin前端 (必须是 public/admin/!) |
| `/www/wwwroot/www.hbdxm.com/public/admin/assets/` | JS/CSS 静态资源 |
| `/www/wwwroot/www.hbdxm.com/public/3d-yanshi/` | 🆕 数字孪生大屏 (index.html + main.js + ai-chat.js + style.css) |
| `/www/wwwroot/www.hbdxm.com/public/wg/` | 🆕 产品官网 (index.html + system.html + scene.html + contact.html) |
| `/www/wwwroot/www.hbdxm.com/app/` | PHP控制器 |
| `/www/wwwroot/www.hbdxm.com/config/` | 配置文件 |
| `/www/wwwroot/www.hbdxm.com/route/` | 路由文件 |

### 9.5 项目版本信息

| 项 | 值 |
|----|-----|
| 后台管理面板 | http://127.0.0.1/admin/ (开发) / https://www.hbdxm.com/admin/ (生产) |
| 管理员账号 | admin |
| 管理员密码 | cxdxfx12 |
| 数据库host | 127.0.0.1 |
| 数据库账号 | root |
| 数据库密码 | cxdxfx12 |
| 数据库名 | dasheng |
| 表前缀 | ds_ |
| Node.js路径 | C:\Program Files\nodejs\ (v24.16.0) |
| Git仓库 | origin → GitHub master 分支 |

---

## 📋 十、最后更新记录

| 日期 | 内容 |
|------|------|
| 2026-06-08 | 🆕 新增 3D数字孪生(5小区) · IoT设备管理 · AI报修助手 · 产品官网 · 官网留言 · 数据联动打通 |
| 2026-06-08 | 侧边栏菜单系统 · 端到端测试数据 · Request::isPost()修复 |
| 2026-06-07 | 深度安全修复：Token校验/字段白名单/频率限制/SSL/CORS/上传/日志截断 |
| 2026-06-07 | 权限修复：BaseAdmin + 菜单permission + 角色分配 |
| 2026-06-07 | 生成本开发者手册 |

---

> **提示**：本手册建议随代码一起维护。新增功能时同步更新对应章节。
