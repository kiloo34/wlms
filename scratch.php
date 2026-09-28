<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$connection = DB::connection('pgsql');
$blueprint = new \Illuminate\Database\Schema\Blueprint($connection, 'org_units');
$blueprint->create();
$blueprint->uuid('id')->primary();
$blueprint->uuid('parent_id')->nullable();
$blueprint->foreign('parent_id')->references('id')->on('org_units')->onDelete('restrict');

$grammar = $connection->getSchemaGrammar();

foreach ($blueprint->toSql($connection, $grammar) as $sql) {
    echo $sql . "\n";
}
