<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        /* Add your custom styles here */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        h1,
        h2,
        h3 {
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <header>
        <img src="{{ $logo }}" alt="Company Logo" style="width: 150px;">
        <h1>{{ config('app.app_name') }}</h1>
    </header>

    <main>
        <h2>Invoice</h2>
        <p><strong>Invoice Number:</strong> #{{ $invoice_id }}</p>
        <p><strong>Invoice Date:</strong> {{ $invoice_date }}</p>

        <table cellspacing="0">
            <thead>
                <tr>
                    <th>Bill To</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <b>Username</b>:-{{ ucfirst($username) }}<br>
                        <b>Email</b>:-{{ $email }}<br>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2>Items</h2>
        <table cellspacing="0">
            <thead>
                <tr>
                    <th class="w-150px">Item ID</th>
                    <th class="w-60">Description</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $subtotal = 0; ?>
                @foreach ($services as $service)
                    <?php $subtotal += $service['price']; ?>
                    <tr>
                        <td class="text-right">{{ $service['service_id'] }}</td>
                        <td>{{ $service['service_name'] }}</td>
                        <td class="text-right">${{ $service['price'] }}</td>
                        <td class="text-right">1</td>
                        <td class="text-right">${{ $service['price'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2">Subtotal</td>
                    <td class="text-right">${{ number_format($subtotal, 2, '.', ',') }}</td>
                </tr>
                <?php
                $discountPercent = (($subtotal - $grand_total) / $subtotal) * 100;
                if($discountPercent > 0) { ?>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2">Discount({{ number_format($discountPercent, 2, '.', ',') }}%)</td>
                    <td class="text-right">${{ number_format($subtotal - $grand_total, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2">Discount Price</td>
                    <td class="text-right">${{ number_format($grand_total, 2, '.', ',') }}</td>
                </tr>
                <?php }
                ?>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2">Auger fee</td>
                    <td class="text-right">${{ number_format($auger_fee, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2">Grand Total</td>
                    <td class="text-right">${{ number_format($grand_total + $auger_fee, 2, '.', ',') }}</td>
                </tr>
            </tfoot>
        </table>
    </main>

    <footer>
        <p>Thank you for your business!</p>
    </footer>
</body>

</html>
