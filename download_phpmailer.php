<?php
// Create the directory structure
$base_dir = 'vendor/phpmailer/phpmailer/src';
if (!file_exists($base_dir)) {
    mkdir($base_dir, 0777, true);
}

// Define the files to download
$files = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.9.1/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.9.1/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.9.1/src/Exception.php'
];

// Download each file
foreach ($files as $filename => $url) {
    // Use cURL instead of file_get_contents for better error handling
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification for now
    $content = curl_exec($ch);

    if ($content !== false) {
        $file_path = $base_dir . '/' . $filename;
        file_put_contents($file_path, $content);
        echo "Downloaded $filename successfully to $file_path\n";
    } else {
        echo "Failed to download $filename: " . curl_error($ch) . "\n";
    }
    curl_close($ch);
}

echo "Done!\n";
