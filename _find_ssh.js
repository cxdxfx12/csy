const net = require('net');
const ip = '211.149.181.178';

// 扫描 1-1000 找SSH
const BATCH = 100;
const TIMEOUT = 1000;
let currentPort = 1;
let active = 0;
const MAX_CONCURRENT = 200;
const openPorts = [];

function checkPort(port) {
  const s = new net.Socket();
  s.setTimeout(TIMEOUT);
  s.on('connect', () => {
    // 读取banner判断是否是SSH
    let banner = '';
    s.on('data', (d) => { banner += d.toString(); });
    setTimeout(() => {
      const isSSH = banner.includes('SSH');
      openPorts.push({ port, banner: banner.substring(0, 80), isSSH });
      s.destroy();
      active--;
      schedule();
    }, 500);
  });
  s.on('error', () => { s.destroy(); active--; schedule(); });
  s.on('timeout', () => { s.destroy(); active--; schedule(); });
  s.connect(port, ip);
  active++;
}

function schedule() {
  while (active < MAX_CONCURRENT && currentPort <= 1000) {
    checkPort(currentPort++);
  }
  if (active === 0 && currentPort > 1000) {
    done();
  }
}

function done() {
  console.log('Open ports (1-1000):');
  openPorts.sort((a, b) => a.port - b.port);
  openPorts.forEach(p => {
    console.log('  Port ' + p.port + ': ' + (p.isSSH ? 'SSH' : 'OTHER') + ' - ' + p.banner.trim().replace(/\r?\n/g, ' '));
  });
  if (openPorts.length === 0) console.log('  None');

  // If no SSH found, scan popular high ports
  if (!openPorts.some(p => p.isSSH)) {
    console.log('\nNo SSH in 1-1000. Scanning high ports...');
    scanHighPorts();
  }
}

function scanHighPorts() {
  const highPorts = [];
  for (let i = 2000; i <= 30000; i += 100) highPorts.push(i);
  for (let i = 30000; i <= 50000; i += 500) highPorts.push(i);
  
  let done2 = 0;
  const open2 = [];
  
  highPorts.forEach(p => {
    const s = new net.Socket();
    s.setTimeout(1000);
    s.on('connect', () => { 
      s.destroy(); 
      open2.push({port: p});
      done2++;
      if (done2 >= highPorts.length) finish2();
    });
    s.on('error', () => { done2++; if (done2 >= highPorts.length) finish2(); });
    s.on('timeout', () => { s.destroy(); done2++; if (done2 >= highPorts.length) finish2(); });
    s.connect(p, ip);
  });

  function finish2() {
    console.log('Open high ports:');
    open2.sort((a,b) => a.port - b.port);
    open2.forEach(p => console.log('  Port ' + p.port));
    if (open2.length === 0) console.log('  None found');
  }
}

schedule();
