// 模拟微信浏览器完全相同的请求
const https = require('https')

async function testClaim() {
  // 先获取一个有效的 owner token
  const tokenRes = await req('POST', '/index.php/api/login', { phone: 'WX_892AD1CA' })
  console.log('=== LOGIN ===')
  console.log('Status:', tokenRes.status)
  console.log('Body:', tokenRes.body.substring(0, 500))
  
  let token = ''
  try {
    const j = JSON.parse(tokenRes.body)
    token = j.data?.token || j.token || ''
  } catch(e) {}
  
  if (!token) {
    console.log('\nNo token from login, trying without token...')
  }
  console.log('Token:', token ? token.substring(0, 50) + '...' : '(none)')

  // 用同样的方式请求 claimProperty
  console.log('\n=== CLAIM PROPERTY ===')
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'User-Agent': 'Mozilla/5.0 (Linux; Android 12; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 MicroMessenger/8.0.44 WeChat/wx'
  }
  if (token) headers['Authorization'] = 'Bearer ' + token

  const claimRes = await req('POST', '/index.php/api/claimProperty', { phone: '19171045360' }, headers)
  console.log('Status:', claimRes.status)
  console.log('Content-Type:', claimRes.contentType)
  console.log('Body:', claimRes.body.substring(0, 1000))
  
  // 验证是否是有效 JSON
  try {
    JSON.parse(claimRes.body)
    console.log('\n✅ Valid JSON')
  } catch(e) {
    console.log('\n❌ NOT valid JSON!')
  }
}

function req(method, path, body, extraHeaders = {}) {
  return new Promise((resolve) => {
    const data = body ? JSON.stringify(body) : undefined
    const opts = {
      hostname: 'www.hbdxm.com',
      port: 443,
      path,
      method,
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': data ? Buffer.byteLength(data) : 0,
        ...extraHeaders
      },
      rejectUnauthorized: false
    }
    
    const r = https.request(opts, (res) => {
      let b = ''
      res.on('data', c => b += c)
      res.on('end', () => resolve({
        status: res.statusCode,
        contentType: res.headers['content-type'] || '',
        body: b
      }))
    })
    r.on('error', e => resolve({ status: 0, contentType: '', body: e.message }))
    if (data) r.write(data)
    r.end()
  })
}

testClaim().catch(console.error)
