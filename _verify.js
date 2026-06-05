const https = require('https');

function fetch(path) {
  return new Promise((resolve, reject) => {
    https.get('https://www.hbdxm.com' + path, { rejectUnauthorized: false }, res => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => resolve(d));
    }).on('error', reject);
  });
}

async function main() {
  const expected = {
    owner: 'owner-wsvGkWrd.js',
    staff: 'staff-Cn5g9Yxy.js',
    manager: 'manager-BDosXLxi.js'
  };

  for (const [name, path] of Object.entries({ owner: '/owner.html', staff: '/staff.html', manager: '/manager.html' })) {
    try {
      const html = await fetch(path);
      const jsMatch = html.match(/src="([^"]+\.js)"/);
      if (jsMatch) {
        const file = jsMatch[1].split('/').pop();
        const ok = file === expected[name];
        console.log((ok ? '✓' : '✗') + ' ' + name + ': ' + file + (ok ? '' : ' (expected ' + expected[name] + ')'));
      } else {
        console.log('✗ ' + name + ': JS not found in HTML');
      }
    } catch (e) {
      console.log('✗ ' + name + ': ' + e.message);
    }
  }
}

main();
