<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

try {
    $r = Cloudinary::uploadApi()->upload('C:\Users\Legion\.gemini\antigravity\brain\8d7c3f46-0e4b-41d7-8caa-6dd316d535be\test_upload_image_1778897264031.png', ['folder' => 'test']);
    echo "Class: " . get_class($r) . "\n";
    print_r($r->getArrayCopy());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
