<?php

use Webkul\Shipping\Support\ZoneRateTable;

function malaysiaTable(): ZoneRateTable
{
    return new ZoneRateTable(
        "east: MY: 87000-91999, 93000-98999, Sabah, Sarawak, Labuan\nwest: MY",
        "west, 0, 200, 15\nwest, 200.01, 300, 18\nwest, 300.01, *, 0\neast, 0, 200, 30\neast, 200.01, 300, 40\neast, 300.01, *, 0"
    );
}

it('charges West Malaysia by order subtotal', function (float $subtotal, float $fee) {
    expect(malaysiaTable()->quote('MY', '50150', 'Kuala Lumpur', $subtotal))
        ->toBe(['zone' => 'west', 'rate' => $fee]);
})->with([
    'lowest order' => [1, 15.0],
    'inside first tier' => [100, 15.0],
    'top of first tier' => [200, 15.0],
    'just above first tier' => [200.01, 18.0],
    'top of second tier' => [300, 18.0],
    'just above second tier' => [300.01, 0.0],
    'large order ships free' => [900, 0.0],
]);

it('charges East Malaysia by order subtotal', function (float $subtotal, float $fee) {
    expect(malaysiaTable()->quote('MY', '88000', 'Sabah', $subtotal))
        ->toBe(['zone' => 'east', 'rate' => $fee]);
})->with([
    'inside first tier' => [100, 30.0],
    'top of first tier' => [200, 30.0],
    'second tier' => [250, 40.0],
    'above second tier' => [301, 0.0],
]);

it('recognises East Malaysia by postcode', function (string $postcode) {
    expect(malaysiaTable()->zoneFor('MY', $postcode, null))->toBe('east');
})->with(['Labuan' => '87000', 'Sabah' => '89500', 'Sabah upper' => '91999', 'Sarawak' => '93450', 'Sarawak upper' => '98999']);

it('recognises West Malaysia by postcode', function (string $postcode) {
    expect(malaysiaTable()->zoneFor('MY', $postcode, null))->toBe('west');
})->with(['Kuala Lumpur' => '50150', 'Penang' => '10200', 'Johor' => '80000', 'between the East ranges' => '92000', 'Perlis' => '01000']);

it('falls back to the state name when the postcode is unknown', function (string $state) {
    expect(malaysiaTable()->zoneFor('MY', null, $state))->toBe('east');
})->with(['Sabah', 'SARAWAK', 'W.P. Labuan', 'Wilayah Persekutuan Labuan']);

it('treats a destination without postcode or state as the country-wide zone', function () {
    expect(malaysiaTable()->zoneFor('MY', null, null))->toBe('west');
});

it('does not serve countries without a zone', function (?string $country) {
    expect(malaysiaTable()->quote($country, '018956', null, 100))->toBeNull();
})->with(['Singapore' => 'SG', 'Indonesia' => 'ID', 'France' => 'FR', 'empty' => '', 'missing' => null]);

it('does not quote an order that no rate row covers', function () {
    $table = new ZoneRateTable('west: MY', 'west, 0, 200, 15');

    expect($table->quote('MY', '50150', null, 250))->toBeNull();
});

it('lets a new country be added from configuration only', function () {
    $table = new ZoneRateTable(
        "west: MY\nsingapore: SG",
        "west, 0, *, 15\nsingapore, 0, 100, 25\nsingapore, 100.01, *, 0"
    );

    expect($table->quote('SG', '018956', null, 50))->toBe(['zone' => 'singapore', 'rate' => 25.0])
        ->and($table->quote('SG', '018956', null, 150))->toBe(['zone' => 'singapore', 'rate' => 0.0])
        ->and($table->quote('MY', '50150', null, 50))->toBe(['zone' => 'west', 'rate' => 15.0]);
});

it('ignores blank lines, comments and malformed lines', function () {
    $table = new ZoneRateTable(
        "# zones\n\nwest: MY\nbroken line\nxx:\n",
        "# rates\n\nwest, 0, 200, 15\nwest, abc, 200, 15\nwest, 0, 200\nwest, 200.01, , 18"
    );

    expect($table->quote('MY', '50150', null, 100))->toBe(['zone' => 'west', 'rate' => 15.0])
        ->and($table->quote('MY', '50150', null, 999))->toBe(['zone' => 'west', 'rate' => 18.0]);
});

it('reports an empty configuration', function () {
    expect((new ZoneRateTable(null, null))->isEmpty())->toBeTrue()
        ->and((new ZoneRateTable('west: MY', 'west, 0, *, 15'))->isEmpty())->toBeFalse()
        ->and((new ZoneRateTable(null, null))->quote('MY', '50150', null, 100))->toBeNull();
});
