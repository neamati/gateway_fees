<?php

if (!defined("WHMCS")) die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;

function gateway_fees_config()
{
    $configarray = [
        "name" => "Gateway Fees for WHMCS",
        "description" => "Add fees based on the gateway being used.",
        "version" => "1.0.0",
        "author" => "Nabi Neamati",
        "fields" => [
            "fee_description" => [
                "FriendlyName" => "Gateway Fee Description",
                "Type" => "text",
                "Default" => "Gateway Fee",
                "Description" => "Enter the text to display for the gateway fee on the invoice."
            ]
        ]
    ];

    return $configarray;
}

function gateway_fees_activate()
{
    $gateways = Capsule::table('tblpaymentgateways')->distinct()->pluck('gateway');
    foreach ($gateways as $gateway) {
        Capsule::table('tbladdonmodules')->insert([
            ["module" => "gateway_fees", "setting" => "fee_" . $gateway, "value" => "0.00"]
        ]);
    }
}

function gateway_fees_create_table()
{
    try {
        if (!Capsule::schema()->hasTable('mod_gateway_fees')) {
            Capsule::schema()->create('mod_gateway_fees', function ($table) {
                $table->increments('id');
                $table->string('gateway');
                $table->enum('feetype', ['fixed', 'percent']);
                $table->decimal('feeamount', 10, 2);
                $table->timestamps();
            });
        }
    } catch (Exception $e) {
        logActivity('Failed to create mod_gateway_fees table: ' . $e->getMessage());
    }
}

function gateway_fees_output($vars)
{
    echo '<h2>Gateway Fees Configuration</h2>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_fees'])) {
        foreach ($_POST['fees'] as $gateway => $fee) {
            Capsule::table('tbladdonmodules')
                ->updateOrInsert(
                    ['module' => 'gateway_fees', 'setting' => 'fee_' . $gateway],
                    ['value' => $fee]
                );
        }

        if (isset($_POST['fee_description'])) {
            Capsule::table('tbladdonmodules')
                ->updateOrInsert(
                    ['module' => 'gateway_fees', 'setting' => 'fee_description'],
                    ['value' => $_POST['fee_description']]
                );
        }

        echo '<div class="alert alert-success">Fees updated successfully!</div>';
    }

    $gateways = Capsule::table('tblpaymentgateways')->distinct()->pluck('gateway');
    $feeDescription = Capsule::table('tbladdonmodules')
        ->where('module', 'gateway_fees')
        ->where('setting', 'fee_description')
        ->value('value') ?? 'Gateway Fee';

    echo '<form method="post">';
    echo '<input type="hidden" name="update_fees" value="1">';
    echo '<table class="table table-bordered">
            <thead>
                <tr>
                    <th>Gateway</th>
                    <th>Fee (%)</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($gateways as $gateway) {
        $fee = Capsule::table('tbladdonmodules')
            ->where('module', 'gateway_fees')
            ->where('setting', 'fee_' . $gateway)
            ->value('value') ?? '0.00';

        echo '<tr>
                <td>' . ucfirst($gateway) . '</td>
                <td><input type="text" name="fees[' . $gateway . ']" value="' . $fee . '" class="form-control"></td>
                <td>Enter the percentage fee for the ' . $gateway . ' gateway.</td>
              </tr>';
    }

    echo '</tbody></table>';
    echo '<div class="form-group">
            <label for="fee_description">Gateway Fee Description</label>
            <input type="text" name="fee_description" id="fee_description" value="' . $feeDescription . '" class="form-control">
            <small class="form-text text-muted">Enter the text to display for the gateway fee on the invoice.</small>
          </div>';
    echo '<button type="submit" class="btn btn-primary">Save Changes</button>';
    echo '</form>';
}

gateway_fees_create_table();

?>
