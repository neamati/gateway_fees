# Gateway Fees for WHMCS

## Overview
The **Gateway Fees for WHMCS** module allows administrators to add custom fees based on the payment gateway selected by users. This module is designed to provide flexibility in managing additional costs associated with specific payment methods, ensuring transparency and accurate billing.

## Features
- **Custom Percentage-Based Fees**: Define percentage-based fees for each payment gateway.
- **Dynamic Fee Descriptions**: Customize the text displayed for gateway fees on invoices.
- **Automatic Fee Application**: Automatically apply fees when an invoice is created or when the payment gateway is changed.
- **Admin Configuration Page**: Manage gateway fees and descriptions directly from the WHMCS admin area.
- **Real-Time Updates**: Ensure invoice totals and subtotals are updated dynamically to reflect the applied fees.

## Installation
1. Upload the `gateway_fees` folder to the `modules/addons` directory of your WHMCS installation.
2. Navigate to **Setup > Addon Modules** in the WHMCS admin area.
3. Locate the **Gateway Fees for WHMCS** module and click **Activate**.
4. Configure the module permissions and settings as needed.

## Configuration
### Addon Module Settings
1. Go to **Addons > Gateway Fees for WHMCS** in the WHMCS admin area.
2. Define the percentage fee for each payment gateway.
3. Customize the text for the gateway fee description (e.g., "Gateway Fee (commissions)").
4. Save changes to apply the configuration.

## Usage
### Applying Fees
- When an invoice is created, the module automatically calculates and applies the gateway fee based on the selected payment method.
- If the payment gateway is changed on an existing invoice, the module recalculates the fee and updates the invoice accordingly.

### Invoice Display
- The gateway fee is displayed as a separate line item on the invoice with the custom description defined in the admin settings.
- The subtotal and total amounts are updated dynamically to include the gateway fee.

## Example
### Admin Configuration
- **PayPal Fee (%)**: `2.5`
- **Bank Transfer Fee (%)**: `0.0`
- **BTC Fee (%)**: `1.0`
- **Gateway Fee Description**: `Gateway Fee (commissions)`

### Invoice Display
```
Invoice Items
Description          Amount
Test Product         $100.00 USD
Gateway Fee (commissions) $2.50 USD
Sub Total            $102.50 USD
Credit               $0.00 USD
Total                $102.50 USD
```

## Troubleshooting
- Ensure the module is activated and configured correctly in the WHMCS admin area.
- Check the WHMCS activity log for any errors or issues related to the module.
- Verify that the `tbladdonmodules` table in the database contains the correct settings for the module.

## Support
For support or feature requests, please open an issue on the [GitHub repository](https://github.com/your-repo/gateway_fees).

## License
This module is open-source and distributed under the MIT License. See the [LICENSE](LICENSE) file for more details.