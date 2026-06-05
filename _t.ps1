$b='{"phone":"19171045360"}'
try{$r=Invoke-WebRequest -Uri 'https://www.hbdxm.com/index.php/api/claimProperty' -Method POST -Body $b -ContentType 'application/json';Write-Host "OK:" $r.Content}catch{Write-Host "ERR:" $_.Exception.Message}
