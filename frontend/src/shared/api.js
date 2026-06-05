// 共享 API 请求封装
// API路径映射：Vue组件传入的baseURL会自动映射到正确的后端路由
//   /api/staff   → /index.php/staff
//   /api/manager → /index.php/manager
//   /api/api     → /index.php/api

export function createApi(baseURL, tokenKey) {
  // 去掉多余的 /api 前缀，统一走 /index.php/ 入口
  const realBase = '/index.php' + baseURL.replace(/^\/api/, '')

  return async function api(path, options = {}) {
    const token = localStorage.getItem(tokenKey)
    const headers = { 'Content-Type': 'application/json', ...options.headers }
    if (token) headers['Authorization'] = 'Bearer ' + token

    const url = realBase + path
    try {
      const res = await fetch(url, { ...options, headers })

      // 始终尝试解析 JSON（微信浏览器可能返回特殊的 content-type）
      // 先克隆一份，如果 JSON 解析失败可以用原始 response 读文本
      let data
      try {
        data = await res.clone().json()
      } catch (jsonErr) {
        // JSON 解析失败，读取原始文本用于调试
        const text = await res.text().catch(() => '')
        console.error('JSON parse error:', url, res.status, text.substring(0, 500))
        // 显示具体错误信息便于排查
        const preview = text.substring(0, 200).replace(/</g,'&lt;')
        return { code: -1, msg: '服务器响应异常(' + res.status + ')：' + preview, data: null }
      }

      // 401 未登录：清除 token
      if (data.code === 401 || (res.status === 401 && data.msg && data.msg.includes('登录'))) {
        localStorage.removeItem(tokenKey)
        return { code: 401, msg: '登录已过期，请重新登录', data: null }
      }

      return data
    } catch (e) {
      // 网络错误：区分具体类型
      let errMsg = '网络连接失败，请检查网络'
      if (e.name === 'TypeError' && e.message === 'Failed to fetch') {
        errMsg = '网络连接失败，请检查网络'
      } else if (e.name === 'AbortError') {
        errMsg = '请求已超时，请重试'
      } else {
        errMsg = '请求失败：' + (e.message || '未知错误')
      }
      console.error('API error:', url, e.name, e.message)
      return { code: -1, msg: errMsg, data: null }
    }
  }
}

// 通用认证
export function createAuth(tokenKey) {
  return {
    getToken() { return localStorage.getItem(tokenKey) },
    setToken(t) { localStorage.setItem(tokenKey, t) },
    removeToken() { localStorage.removeItem(tokenKey) },
    isLogin() { return !!localStorage.getItem(tokenKey) }
  }
}
