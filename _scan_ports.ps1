$ip = "211.149.181.178"
$ports = @(22, 2222, 22222, 21, 80, 443, 8080, 8443, 3306)

foreach ($port in $ports) {
    $timeout = 2000
    $tcpClient = New-Object System.Net.Sockets.TcpClient
    $connect = $tcpClient.BeginConnect($ip, $port, $null, $null)
    $wait = $connect.AsyncWaitHandle.WaitOne($timeout, $false)
    if ($wait -and $tcpClient.Connected) {
        Write-Host "Port $port : OPEN"
        $tcpClient.EndConnect($connect)
    } else {
        Write-Host "Port $port : closed"
    }
    $tcpClient.Close()
    $tcpClient.Dispose()
}
