const Client=require('ssh2').Client;
const c=new Client();
c.on('ready',()=>{
  c.exec(`
echo "=== staff 模块所有控制器 ==="
ls -la /www/wwwroot/www.hbdxm.com/public/app/staff/controller/ 2>&1

echo ""
echo "=== 路由缓存 ==="
find /www/wwwroot/www.hbdxm.com/public/runtime -name '*route*' 2>/dev/null

echo ""
echo "=== index.php ==="
head -30 /www/wwwroot/www.hbdxm.com/public/index.php

echo ""
echo "=== thinkPHP config ==="
grep -r 'url_route\\|auto_route' /www/wwwroot/www.hbdxm.com/config/ 2>/dev/null | head -5
`,(err,stream)=>{
    let out='';
    stream.on('data',d=>out+=d.toString());
    stream.on('close',()=>{console.log(out);c.end();});
  });
}).connect({host:'211.149.181.178',port:22000,username:'root',password:'cxdxfx12'});
