param (
    [string]$ftpServer = "ftp://ftpupload.net/htdocs",
    [string]$username = "if0_42292084",
    [string]$password = "Bwyzwq2m72X6",
    [string[]]$filesToUpload = @("database.sql", "deploy.zip", "vendor.zip.part0", "vendor.zip.part1", "vendor.zip.part2", "vendor.zip.part3", "installer.php", "db_import.php", ".env", ".htaccess")
)

foreach ($file in $filesToUpload) {
    if (Test-Path $file) {
        $filePath = Resolve-Path $file
        $fileName = Split-Path $filePath -Leaf
        $ftpUrl = "$ftpServer/$fileName"
        
        Write-Host "Uploading $fileName to $ftpUrl..."
        
        $webClient = New-Object System.Net.WebClient
        $webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        
        try {
            $webClient.UploadFile($ftpUrl, "STOR", $filePath)
            Write-Host "Successfully uploaded $fileName" -ForegroundColor Green
        } catch {
            Write-Host "Failed to upload $fileName. Error: $_" -ForegroundColor Red
        }
    } else {
        Write-Host "File $file does not exist locally." -ForegroundColor Yellow
    }
}
