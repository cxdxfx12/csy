const net = require('net');
const ip = '211.149.181.178';
const ports = [];
for (let i = 1; i <= 65535; i++) {
  ports.push(i);
}
// 分批扫描常见端口
const commonPorts = [22, 2121, 2222, 22222, 8022, 9022, 10022, 20022, 30022, 40022, 50022];

let done = 0;
const results = [];
commonPorts.forEach(p => {
  const s = new net.Socket();
  s.setTimeout(2000);
  s.on('connect', () => { 
    s.write('SSH-2.0-test\r\n');
    setTimeout(() => {
      const data = s.read();
      const isSSH = data ? data.toString().includes('SSH') : false;
      results.push({port:p, status:isSSH ? 'SSH' : 'OPEN-unknown'});
      s.destroy();
      checkDone();
    }, 800);
  });
  s.on('error', () => { results.push({port:p, status:'closed'}); checkDone(); });
  s.on('timeout', () => { results.push({port:p, status:'timeout'}); s.destroy(); checkDone(); });
  s.connect(p, ip);
});

function checkDone() {
  done++;
  if (done >= commonPorts.length) {
    results.sort((a,b) => a.port - b.port);
    const open = results.filter(r => r.status !== 'closed' && r.status !== 'timeout');
    console.log('Open ports:');
    open.forEach(r => console.log('  Port', r.port, ':', r.status));
    if (open.length === 0) {
      console.log('  None found in common SSH ports');
    }
  }
}
