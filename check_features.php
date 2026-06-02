<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$central = config('tenancy.database.central_connection', 'mysql');
echo "Central connection: $central\n";

$tenants = DB::connection($central)
    ->table('tenants')
    ->where('id', 'like', '%maramag%')
    ->orWhere('name', 'like', '%maramag%')
    ->get();

echo "Tenants found: " . $tenants->count() . "\n";
foreach ($tenants as $t) {
    echo "ID: {$t->id}, Name: {$t->name}, Plan ID: {$t->plan_id}\n";

    $plan = DB::connection($central)
        ->table('plans')
        ->where('id', $t->plan_id)
        ->first();

    if ($plan) {
        echo "Plan: {$plan->name}\n";
        echo "Features: {$plan->features}\n";
        $features = json_decode($plan->features, true);
        print_r($features);
    }
}
