const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  conn.exec('cat /www/wwwroot/www.hbdxm.com/.env', (e,s) => {
    let d=''; s.on('data',dd=>d+=dd); s.on('close',()=>{console.log('SERVER .env:\n'+d); conn.end();});
  });
}).connect({host:'211.149.181.178',port:22000,username:'root',password:'cxdxfx12',readyTimeout:15000});
setTimeout(()=>process.exit(0),15000);
