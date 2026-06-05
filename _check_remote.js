const https = require('https');

function fetchHTML(url) {
  return new Promise((resolve, reject) => {
    https.get(url, { rejectUnauthorized: false }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });
}

async function main() {
  console.log('=== 线上 owner.html ===');
  try {
    const html = await fetchHTML('https://www.hbdxm.com/owner.html');
    // 提取 script src
    const matches = html.match(/src="([^"]+\.js)"/g);
    console.log('JS引用:');
    if (matches) matches.forEach(m => console.log('  ' + m));
    
    const cssMatches = html.match(/href="([^"]+\.css)"/g);
    console.log('CSS引用:');
    if (cssMatches) cssMatches.forEach(m => console.log('  ' + m));

    console.log('\n=== 本地 build 的 owner.html ===');
    const fs = require('fs');
    const localHtml = fs.readFileSync(__dirname + '/public/owner.html', 'utf8');
    const localJs = localHtml.match(/src="([^"]+\.js)"/g);
    console.log('JS引用:');
    if (localJs) localJs.forEach(m => console.log('  ' + m));
  } catch (e) {
    console.error('Error:', e.message);
  }
}

main();
