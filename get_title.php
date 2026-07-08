<?php
// Izinkan akses dari domain web Anda (Mengatasi CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$stream_url = 'https://stream.denger.in/dmi';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Icy-MetaData: 1\r\n",
        'timeout' => 5
    ]
]);

$stream = @fopen($stream_url, 'r', false, $context);

if ($stream) {
    $metaHeaders = stream_get_meta_data($stream);
    $wrapperData = $metaHeaders['wrapper_data'] ?? [];
    $metaint = 0;

    // Cari interval header icy-metaint
    foreach ($wrapperData as $header) {
        if (stristr($header, 'icy-metaint:')) {
            $metaint = (int)trim(explode(':', $header)[1]);
            break;
        }
    }

    if ($metaint > 0) {
        // Lewati data audio seukuran $metaint
        fread($stream, $metaint);
        
        // Baca 1 byte untuk mengetahui panjang string metadata
        $lenByte = fread($stream, 1);
        if ($lenByte !== false) {
            $length = ord($lenByte) * 16;
            
            if ($length > 0) {
                $metadata = fread($stream, $length);
                // Ambil string StreamTitle='...';
                if (preg_match("/StreamTitle='(.*?)';/i", $metadata, $matches)) {
                    echo json_encode(["status" => "success", "title" => $matches[1]]);
                    fclose($stream);
                    exit;
                }
            }
        }
    }
    fclose($stream);
}

// Jika metadata tidak ditemukan
echo json_encode(["status" => "error", "title" => "Live Stream DMI"]);
?>
