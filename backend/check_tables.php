<?php
require __DIR__."/vendor/autoload.php"; 
$app = require_once __DIR__."/bootstrap/app.php"; 
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); 
$kernel->bootstrap(); 
var_dump(\Illuminate\Support\Facades\Schema::hasTable('cache'));
var_dump(\Illuminate\Support\Facades\Schema::hasTable('sessions'));
