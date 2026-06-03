// 通用API响应
export interface ApiResponse<T = any> {
  code: number
  msg: string
  data: T
  count?: number
  time?: number
}

export interface PageData<T> {
  list: T[]
  total: number
}

// 用户
export interface UserInfo {
  id: number
  username: string
  nickname: string
  avatar: string
  role: string
  role_id: number
}

// 菜单
export interface MenuItem {
  id: number
  parent_id: number
  name: string
  icon: string
  route: string
  permission: string
  type: number
  sort: number
  status: number
  children?: MenuItem[]
}

// 登录响应
export interface LoginResult {
  token: string
  userInfo: UserInfo
  menus: MenuItem[]
}
