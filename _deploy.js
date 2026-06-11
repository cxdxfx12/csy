const Client=require('ssh2').Client;
const c=new Client();
c.on('ready',()=>{
  // 备份原文件，上传极简版本
  c.exec("cp /www/wwwroot/www.hbdxm.com/public/app/staff/controller/StaffLogin.php /www/wwwroot/www.hbdxm.com/public/app/staff/controller/StaffLogin.php.bak",(err,s0)=>{
    s0.on('close',()=>{
      c.sftp((err,sftp)=>{
        sftp.fastPut('e:/ds/_test_login.php','/www/wwwroot/www.hbdxm.com/public/app/staff/controller/StaffLogin.php',err=>{
          console.log(err||'uploaded test version');
          sftp.end();
          c.exec('/etc/init.d/php-fpm-81 restart 2>&1; sleep 2; curl -s -X POST "http://www.hbdxm.com/api/staff/login" -d "username=17771300099&password=cxdxfx12";echo',(err1,stream)=>{
            let out='';
            stream.on('data',d=>out+=d.toString());
            stream.on('close',()=>{console.log(out);c.end();});
          });
        });
      });
    });
  });
}).connect({host:'211.149.181.178',port:22000,username:'root',password:'cxdxfx12'});
