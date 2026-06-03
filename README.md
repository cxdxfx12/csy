# 🐒 大圣物业管理系统

> 杭州喵喵至家网络有限公司 · 智慧社区物业管理平台

## 📋 项目概述

大圣物业管理系统是一套基于 ThinkPHP 8.x + LayuiAdmin 开发的现代化小区物业管理平台，采用前后端分离架构，支持多端访问。

### 核心特性

- ✅ **系统管理**: 用户、角色、菜单、配置、日志
- ✅ **房产资源**: 小区、楼栋、房间管理（支持批量生成）
- ✅ **业主管理**: 业主档案、家庭成员、房间绑定
- ✅ **收费管理**: 收费项目、账单生成、缴费记录、抄表、财务流水
- ✅ **报修管理**: 工单流转、派单、维修人员管理
- ✅ **安保管理**: 访客登记、巡更路线、巡更打卡、门禁卡
- ✅ **停车管理**: 车位、车辆、停车记录
- ✅ **公告通知**: 公告发布、置顶、撤回
- ✅ **设备管理**: 设备台账、维保记录、到期提醒
- ✅ **投诉建议**: 投诉处理、满意度评价
- ✅ **打印系统**: 缴费收据、催缴通知
- ✅ **领导驾驶舱**: 数据概览、收入趋势、KPI指标
- ✅ **业主端API**: 账单查询、报修提交、投诉建议、访客预约

## 🚀 快速开始

### 环境要求

- PHP >= 8.1
- MySQL >= 8.0
- Nginx / Apache
- Composer
- Redis（可选，推荐）

### 安装步骤

#### 方式一：Web安装向导（推荐）

1. 将项目部署到Web服务器根目录
2. 访问 `http://your-domain/install.php`
3. 按照向导填写数据库信息和管理员信息
4. 完成安装后删除 `install.php` 文件

#### 方式二：命令行安装

```bash
# 1. 创建数据库
mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS dasheng DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. 导入数据表
mysql -uroot -p dasheng < database/dasheng.sql

# 3. 安装依赖
composer install

# 4. 配置 .env 文件
cp .env.example .env
# 编辑 .env 配置数据库连接信息

# 5. 配置 Web 服务器指向 public 目录
```

### 访问地址

| 端 | 地址 | 说明 |
|---|---|---|
| 管理后台 | `http://your-domain/admin/login.html` | PC端后台管理 |
| 业主API | `http://your-domain/api/` | 业主端RESTful API |
| 员工端 | `http://your-domain/staff/` | 移动端API |
| 领导驾驶舱 | `http://your-domain/manager/` | 数据大屏API |

### 默认账号

| 用户名 | 密码 | 角色 |
|---|---|---|
| admin | admin123 | 超级管理员 |

> **安全提示**: 首次登录后请及时修改默认密码！

## 🏗 技术架构

### 后端技术栈

- **框架**: ThinkPHP 8.x
- **语言**: PHP 8.1+
- **数据库**: MySQL 8.x + Redis
- **认证**: JWT (JSON Web Token)
- **ORM**: ThinkPHP ORM

### 前端技术栈

- **UI框架**: Layui 2.9.x
- **图标**: Layui Icon
- **可视化**: ECharts 5.x
- **交互**: jQuery + Ajax

### 目录结构

```
e:/ds/
├── app/                    # 应用目录
│   ├── admin/             # PC后台管理
│   │   ├── controller/    # 控制器
│   │   ├── model/         # 模型
│   │   ├── validate/      # 验证器
│   │   └── service/       # 服务层
│   ├── api/               # 业主端API
│   ├── staff/             # 物业员工端
│   ├── manager/           # 领导驾驶舱
│   ├── device/            # 设备端
│   ├── common/            # 公共模块
│   └── model/             # 公共模型
├── config/                 # 配置文件
├── database/               # 数据库脚本
├── middleware/              # 中间件
├── route/                  # 路由配置
├── public/                 # 入口和静态资源
│   ├── index.php          # 入口文件
│   ├── install.php        # 安装向导
│   └── admin/             # 前端资源
│       ├── index.html     # 管理后台首页
│       ├── login.html     # 登录页
│       ├── css/           # 样式
│       ├── js/            # 脚本
│       └── page/          # 页面
├── runtime/                # 运行时
└── vendor/                 # Composer依赖
```

## 📊 数据库设计

- **数据库名**: dasheng
- **表前缀**: ds_
- **字符集**: utf8mb4
- **引擎**: InnoDB

### 核心数据表（33张）

| 分类 | 表名 | 说明 |
|---|---|---|
| 系统管理 | admin_user, role, menu, role_menu, config, system_log | 权限、配置、日志 |
| 房产资源 | community, building, room | 小区、楼栋、房间 |
| 业主管理 | owner, owner_family, owner_room | 业主、家庭成员、房产关联 |
| 收费管理 | charge_item, bill, bill_payment, meter_reading, finance_flow | 收费项目、账单、缴费、抄表、流水 |
| 报修管理 | repair_order, repair_worker | 工单、维修人员 |
| 安保管理 | visitor, patrol_route, patrol_record, access_card | 访客、巡更、门禁 |
| 停车管理 | parking_space, vehicle, parking_record | 车位、车辆、记录 |
| 公告设备 | notice, equipment, equipment_maintain | 公告、设备台账、维保 |
| 其他 | complaint, attachment, sms_log, message | 投诉、附件、短信、消息 |

## 🔌 API接口

### 后台管理API（示例）

| 方法 | 路由 | 说明 |
|---|---|---|
| POST | /admin/login | 管理员登录 |
| GET | /admin/dashboard/statistics | 仪表盘数据 |
| GET/POST | /admin/admin/user/list | 管理员列表 |
| GET/POST | /admin/community/list | 小区列表 |
| GET/POST | /admin/room/list | 房间列表 |
| GET/POST | /admin/owner/list | 业主列表 |
| GET/POST | /admin/charge/billList | 账单列表 |
| GET/POST | /admin/repair/orderList | 工单列表 |
| GET/POST | /admin/complaint/list | 投诉列表 |

## 🛠 开发计划

- [x] 系统架构设计
- [x] 数据库设计
- [x] 后台管理API
- [x] 业主端API
- [x] 后台管理前端
- [ ] 微信小程序
- [ ] 短信通知集成
- [ ] 在线支付对接
- [ ] 硬件设备对接
- [ ] 大数据大屏展示

## 📄 许可证

本项目由 **杭州喵喵至家网络有限公司** 版权所有。

---

> 🐒 **大圣物业** - 让物业管理更简单、更智能
