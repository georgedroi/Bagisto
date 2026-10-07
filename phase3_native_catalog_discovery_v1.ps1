$ErrorActionPreference = "Stop"

$Compose = "compose.local.yaml"

function Invoke-Tinker([string]$Code) {
    docker compose -f $Compose exec -T app php artisan tinker --execute=$Code

    if ($LASTEXITCODE -ne 0) {
        throw "Tinker command failed."
    }
}

Write-Host "=== PHASE 3 NATIVE CATALOG DISCOVERY ==="

Write-Host "`n[1/7] Categories..."
Invoke-Tinker @'
echo 'Categories: '.\Webkul\Category\Models\Category::count().PHP_EOL;
echo 'Root Categories: '.\Webkul\Category\Models\Category::whereNull('parent_id')->count().PHP_EOL;
'@

Write-Host "`n[2/7] Attributes..."
Invoke-Tinker @'
echo 'Attributes: '.\Webkul\Attribute\Models\Attribute::count().PHP_EOL;
echo 'Families: '.\Webkul\Attribute\Models\AttributeFamily::count().PHP_EOL;
'@

Write-Host "`n[3/7] Attribute Families Detail..."
Invoke-Tinker @'
foreach (\Webkul\Attribute\Models\AttributeFamily::all() as $family) {
    echo 'Family: '.$family->name.PHP_EOL;
}
'@

Write-Host "`n[4/7] Product Types..."
Invoke-Tinker @'
echo 'Product Types:'.PHP_EOL;
foreach (['simple', 'configurable', 'virtual', 'grouped', 'downloadable', 'booking'] as $type) {
    echo $type.PHP_EOL;
}
'@

Write-Host "`n[5/7] Channels..."
Invoke-Tinker @'
echo 'Channels: '.\Webkul\Core\Models\Channel::count().PHP_EOL;
foreach (\Webkul\Core\Models\Channel::all() as $channel) {
    echo $channel->code.' - '.$channel->name.PHP_EOL;
}
'@

Write-Host "`n[6/7] Inventory Sources..."
Invoke-Tinker @'
echo 'Inventory Sources: '.\Webkul\Inventory\Models\InventorySource::count().PHP_EOL;
foreach (\Webkul\Inventory\Models\InventorySource::all() as $source) {
    echo $source->code.' - '.$source->name.PHP_EOL;
}
'@

Write-Host "`n[7/7] Products Baseline..."
Invoke-Tinker @'
echo 'Products: '.\Webkul\Product\Models\Product::count().PHP_EOL;
'@

Write-Host "`n=== END PHASE 3 DISCOVERY ==="
