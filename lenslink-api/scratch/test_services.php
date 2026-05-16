<?php

use Illuminate\Support\Facades\Config;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Broadcast;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Services...\n";
echo "-------------------\n";

// 1. Test Stripe
echo "Stripe: ";
try {
    Stripe::setApiKey(config('services.stripe.secret'));
    $intent = PaymentIntent::create(['amount' => 100, 'currency' => 'usd']);
    echo "SUCCESS (Intent ID: {$intent->id})\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "   Tip: Check STRIPE_SECRET in .env. It should be a string starting with sk_test_.\n";
}

// 2. Test Pusher
echo "Pusher: ";
try {
    $pusher = Broadcast::driver('pusher')->getPusher();
    $pusher->get_channels();
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

// 3. Test Cloudinary
echo "Cloudinary: ";
try {
    // Attempt an actual upload or list resources to verify credentials
    // For now, let's just try to resolve the facade which was failing
    $facade = Cloudinary::getFacadeRoot();
    if ($facade) {
        echo "SUCCESS (Facade resolved)\n";
    } else {
        echo "FAILED: Facade returned null\n";
    }
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "   Tip: Ensure 'cloudinary' disk is in config/filesystems.php and CLOUDINARY_URL is correct.\n";
}

// 4. Test Firebase
echo "Firebase: ";
try {
    $auth = Firebase::auth();
    // Try to list a single user or get project info to verify credentials
    $users = $auth->listUsers(1);
    echo "SUCCESS (Authenticated and connected)\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "   Tip: Check FIREBASE_CREDENTIALS path in .env. Current path: " . env('FIREBASE_CREDENTIALS') . "\n";
}

echo "-------------------\n";
echo "Test Finished.\n";
