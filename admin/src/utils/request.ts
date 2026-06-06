import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'

const service = axios.create({
  baseURL: '/api',
  timeout: 15000,
})

service.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

service.interceptors.response.use(
  (res) => {
    if (res.data.code === 0) return res.data
    // code !== 0 一律视为错误
    ElMessage.error(res.data.msg || `操作失败 (code:${res.data.code})`)
    return Promise.reject(res.data)
  },
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('admin_token')
      router.push('/login')
      ElMessage.error('登录已过期，请重新登录')
    } else {
      ElMessage.error('网络请求失败')
    }
    return Promise.reject(err)
  }
)

export default service

// API工具函数
export const apiGet = <T = any>(url: string, params?: any) => service.get<any, { code: number; msg: string; data: T; count?: number }>(url, { params })
export const apiPost = <T = any>(url: string, data?: any) => service.post<any, { code: number; msg: string; data: T }>(url, data)
export const apiTable = <T = any>(url: string, params?: any) => apiGet<{ list: T[]; total: number }>(url, params)
