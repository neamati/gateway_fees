<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function update_invoice_subtotal($invoiceId)
{
    // Calculate the new subtotal including all items and fees
    $newSubTotal = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->sum('amount');

    // Update the subtotal in the invoice
    Capsule::table('tblinvoices')->where('id', $invoiceId)->update([
        'subtotal' => $newSubTotal
    ]);

    logActivity("Invoice #$invoiceId subtotal updated to $newSubTotal.");
}

function remove_old_gateway_fee_items($invoiceId)
{
    // Fetch the current fee description from the configuration
    $currentFeeDescription = Capsule::table('tbladdonmodules')
        ->where('module', 'gateway_fees')
        ->where('setting', 'fee_description')
        ->value('value') ?? 'Gateway Fee';

    // Remove any items that do not match the current fee description
    Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->where('description', '!=', $currentFeeDescription)
        ->where('description', 'like', 'Gateway%')
        ->delete();

    logActivity("Removed old gateway fee items from Invoice #$invoiceId.");
}

function remove_existing_gateway_fee($invoiceId, $feeDescription)
{
    // Remove any existing gateway fee items with the current description
    Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->where('description', $feeDescription)
        ->delete();

    logActivity("Removed existing gateway fee items from Invoice #$invoiceId.");
}

function update_gateway_fee($vars)
{
    $invoiceId = $vars['invoiceid'];
    logActivity("update_gateway_fee triggered for Invoice #$invoiceId.");

    $invoice = Capsule::table('tblinvoices')->find($invoiceId);
    if (!$invoice) {
        logActivity("Invoice #$invoiceId not found when updating gateway fee.");
        return;
    }

    $gateway = $invoice->paymentmethod;
    logActivity("Payment method for Invoice #$invoiceId changed to: $gateway.");

    $feePercentage = Capsule::table('tbladdonmodules')
        ->where('module', 'gateway_fees')
        ->where('setting', 'fee_' . $gateway)
        ->value('value');

    $feeDescription = Capsule::table('tbladdonmodules')
        ->where('module', 'gateway_fees')
        ->where('setting', 'fee_description')
        ->value('value') ?? 'Gateway Fee';

    // Remove existing gateway fee items to avoid duplication
    remove_existing_gateway_fee($invoiceId, $feeDescription);

    if (!$feePercentage || $feePercentage <= 0) {
        logActivity("No fee percentage configured for gateway $gateway on Invoice #$invoiceId. Gateway fee removed.");

        // Recalculate the invoice total after removing the fee
        $newTotal = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->sum('amount');

        Capsule::table('tblinvoices')->where('id', $invoiceId)->update([
            'total' => $newTotal
        ]);

        // Update the invoice subtotal to include all items and fees
        update_invoice_subtotal($invoiceId);

        logActivity("Invoice #$invoiceId total updated to $newTotal after removing gateway fee.");
        return;
    }

    $invoiceTotal = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->sum('amount');

    logActivity("Invoice #$invoiceId total before fee update: $invoiceTotal.");

    $fee = ($invoiceTotal * $feePercentage) / 100;

    Capsule::table('tblinvoiceitems')->insert([
        'userid' => $invoice->userid,
        'invoiceid' => $invoiceId,
        'description' => $feeDescription,
        'amount' => $fee,
        'taxed' => 0,
    ]);

    // Update the invoice total directly
    $newTotal = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->sum('amount');

    Capsule::table('tblinvoices')->where('id', $invoiceId)->update([
        'total' => $newTotal
    ]);

    // Update the invoice subtotal to include all items and fees
    update_invoice_subtotal($invoiceId);

    logActivity("Gateway Fee of $fee applied to Invoice #$invoiceId using $gateway. New total: $newTotal.");
}
add_hook('InvoiceChangeGateway', 1, 'update_gateway_fee');

function apply_gateway_fee_on_invoice_create($vars)
{
    $invoiceId = $vars['invoiceid'];
    logActivity("apply_gateway_fee_on_invoice_create triggered for Invoice #$invoiceId.");

    $invoice = Capsule::table('tblinvoices')->find($invoiceId);
    if (!$invoice) {
        logActivity("Invoice #$invoiceId not found when applying gateway fee.");
        return;
    }

    $gateway = $invoice->paymentmethod;
    logActivity("Payment method for Invoice #$invoiceId: $gateway.");

    $feePercentage = Capsule::table('tbladdonmodules')
        ->where('module', 'gateway_fees')
        ->where('setting', 'fee_' . $gateway)
        ->value('value');

    if (!$feePercentage || $feePercentage <= 0) {
        logActivity("No fee percentage configured for gateway $gateway on Invoice #$invoiceId.");
        return;
    }

    $feeDescription = Capsule::table('tbladdonmodules')
        ->where('module', 'gateway_fees')
        ->where('setting', 'fee_description')
        ->value('value') ?? 'Gateway Fee';

    // Remove existing gateway fee items to avoid duplication
    remove_existing_gateway_fee($invoiceId, $feeDescription);

    $invoiceTotal = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->sum('amount');

    logActivity("Invoice #$invoiceId total before fee: $invoiceTotal.");

    $fee = ($invoiceTotal * $feePercentage) / 100;

    Capsule::table('tblinvoiceitems')->insert([
        'userid' => $invoice->userid,
        'invoiceid' => $invoiceId,
        'description' => $feeDescription,
        'amount' => $fee,
        'taxed' => 0,
    ]);

    // Update the invoice total directly
    $newTotal = $invoiceTotal + $fee;

    Capsule::table('tblinvoices')->where('id', $invoiceId)->update([
        'total' => $newTotal
    ]);

    // Update the invoice subtotal to include all items and fees
    update_invoice_subtotal($invoiceId);

    logActivity("Gateway Fee of $fee applied to Invoice #$invoiceId using $gateway. New total: $newTotal.");
}
add_hook('InvoiceCreated', 1, 'apply_gateway_fee_on_invoice_create');

function calculate_fee($feeSetting, $invoiceId)
{
    $invoiceTotal = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->sum('amount');

    if ($feeSetting->feetype === 'percent') {
        return ($invoiceTotal * $feeSetting->feeamount) / 100;
    }

    return $feeSetting->feeamount;
}
