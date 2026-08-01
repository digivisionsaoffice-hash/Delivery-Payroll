<?php
// installer.php
$out = fopen('vendor.zip', 'wb');
for ($i=0; $i<4; $i++) {
    $part = "vendor.zip.part$i";
    if (file_exists($part)) {
        fwrite($out, file_get_contents($part));
        unlink($part);
    }
}
fclose($out);
echo "Combined vendor chunks.<br>";

$files = ['deploy.zip', 'vendor.zip'];
$zip = new ZipArchive;

foreach ($files as $file) {
    if (file_exists($file)) {
        if ($zip->open($file) === TRUE) {
            $zip->extractTo('./');
            $zip->close();
            echo "Extracted $file successfully.<br>";
            unlink($file); // clean up
        } else {
            echo "Failed to extract $file.<br>";
        }
    } else {
        echo "File $file not found.<br>";
    }
}
echo "Installation complete.";
