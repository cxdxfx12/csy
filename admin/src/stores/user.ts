import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiPost, apiGet } from '@/utils/request'
import type { UserInfo, MenuItem, LoginResult } from '@/types/api'

export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('admin_token') || '')
  const userInfo = ref<UserInfo | null>(null)
  const menus = ref<MenuItem[]>([])
  const permissions = ref<string[]>([])

  async function login(username: string, password: string, captcha?: string, captchaKey?: string) {
    const res = await apiPost<LoginResult>('/admin/login', { username, password, captcha, captchaKey })
    token.value = res.data.token
    userInfo.value = res.data.userInfo
    menus.value = res.data.menus
    localStorage.setItem('admin_token', res.data.token)
    return res.data
  }

  async function fetchInfo() {
    const res = await apiGet<{ userInfo: UserInfo; menus: MenuItem[]; permissions: string[] }>('/admin/login/info')
    userInfo.value = res.data.userInfo
    menus.value = res.data.menus
    permissions.value = res.data.permissions
    return res.data
  }

  function logout() {
    token.value = ''
    userInfo.value = null
    menus.value = []
    permissions.value = []
    localStorage.removeItem('admin_token')
  }

  return { token, userInfo, menus, permissions, login, fetchInfo, logout }
})
