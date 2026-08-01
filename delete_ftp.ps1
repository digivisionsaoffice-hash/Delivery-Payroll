$ftpServer = "ftp://ftpupload.net/htdocs"
$username = "if0_42292084"
$password = "Bwyzwq2m72X6"

try {
    $request = [System.Net.FtpWebRequest]::Create($ftpServer)
    $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $files = $reader.ReadToEnd() -split "`r`n" | Where-Object { $_ -ne "" }
    $reader.Close()
    $response.Close()

    foreach ($file in $files) {
        if ($file -ne "." -and $file -ne "..") {
            $fileUrl = "$ftpServer/$file"
            Write-Host "Deleting $fileUrl"
            try {
                $delRequest = [System.Net.FtpWebRequest]::Create($fileUrl)
                $delRequest.Credentials = New-Object System.Net.NetworkCredential($username, $password)
                $delRequest.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
                $delResponse = $delRequest.GetResponse()
                $delResponse.Close()
                Write-Host "Deleted $file" -ForegroundColor Green
            } catch {
                Write-Host "Failed to delete $file. Error: $_" -ForegroundColor Red
            }
        }
    }
} catch {
    Write-Host "Error listing directory: $_"
}
