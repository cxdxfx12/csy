const net = require('net');
const ip = '211.149.181.178';
const ports = [22, 2222, 22222, 21, 80, 443, 8080, 8443, 3306];
const results = [];

let done = 0;
ports.forEach(p => {
  const s = new net.Socket();
  s.setTimeout(3000);
  s.on('connect', () => { results.push({port:p, status:'OPEN'}); s.destroy(); checkDone(); });
  s.on('error', () => { results.push({port:p, status:'closed'}); checkDone(); });
  s.on('timeout', () => { results.push({port:p, status:'timeout'}); s.destroy(); checkDone(); });
  s.connect(p, ip);
});

function checkDone() {
  done++;
  if (done >= ports.length) {
    results.sort((a,b) => a.port - b.port);
    results.forEach(r => console.log('Port', r.port, ':', r.status));
  }
}
