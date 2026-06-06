const { execSync } = require('child_process')

try {
  const out = execSync('"D:\\BtSoft\\nginx\\nginx.exe" -p "D:\\BtSoft\\nginx" -T', {cwd: 'D:\\BtSoft\\nginx', timeout: 5000, encoding: 'utf8'})
  
  // Find ALL server blocks on port 80
  const regex = /# configuration file ([^\n]+)\n(.*?)(?=# configuration file|\n*$)/gs
  let match
  while ((match = regex.exec(out)) !== null) {
    const file = match[1].trim()
    const content = match[2]
    const listenLines = content.match(/listen\s+[^;]+;/g) || []
    const listen80 = listenLines.filter(l => l.includes(':80') || l.match(/listen\s+80\b/))
    if (listen80.length > 0) {
      const snMatch = content.match(/server_name\s+([^;]+)/)
      console.log(`\nFile: ${file}`)
      console.log(`Listen: ${listen80.join(', ')}`)
      console.log(`ServerName: ${snMatch ? snMatch[1].trim() : 'NONE'}`)
      if (content.includes('default_server')) console.log('*** HAS default_server ***')
    }
  }
} catch(e) {
  console.log('Error:', e.stderr ? e.stderr.substring(0, 500) : e.message)
}
