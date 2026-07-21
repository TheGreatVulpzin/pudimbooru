<?php

declare(strict_types=1);

if (preg_match('/_(images|thumbs)\/([0-9a-f]{32})\//', $_SERVER["REQUEST_URI"], $matches)) {
    $silo = $matches[1];
    $hash = $matches[2];
    $ha = substr($hash, 0, 2);
    $path = "data/$silo/$ha/$hash";
    if (!is_file($path)) {
        http_response_code(404);
        return;
    }

    $size = filesize($path);
    $start = 0;
    $end = $size - 1;
    $status = 200;
    if (isset($_SERVER["HTTP_RANGE"])) {
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER["HTTP_RANGE"], $range)) {
            http_response_code(416);
            header("Content-Range: bytes */$size");
            return;
        }
        if ($range[1] === "" && $range[2] !== "") {
            $start = max(0, $size - (int)$range[2]);
        } else {
            $start = (int)$range[1];
            $end = $range[2] === "" ? $end : min($end, (int)$range[2]);
        }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header("Content-Range: bytes */$size");
            return;
        }
        $status = 206;
    }

    $mime = mime_content_type($path) ?: "application/octet-stream";
    http_response_code($status);
    header("Content-Type: $mime");
    header("Accept-Ranges: bytes");
    header("Content-Length: " . ($end - $start + 1));
    if ($status === 206) {
        header("Content-Range: bytes $start-$end/$size");
    }
    if ($_SERVER["REQUEST_METHOD"] !== "HEAD") {
        $file = fopen($path, "rb");
        fseek($file, $start);
        $remaining = $end - $start + 1;
        while ($remaining > 0 && !feof($file)) {
            $chunk = fread($file, min(8192, $remaining));
            echo $chunk;
            $remaining -= strlen($chunk);
        }
        fclose($file);
    }
} elseif (
    preg_match('/.*\.(jpg|jpeg|gif|png|ico|svg|js|css)/', $_SERVER["REQUEST_URI"])
    && file_exists($_SERVER["DOCUMENT_ROOT"] . $_SERVER["REQUEST_URI"])
) {
    return false;
} else {
    require_once("index.php");
}
