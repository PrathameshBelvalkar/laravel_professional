<!DOCTYPE html>
<html lang="zxx" class="js">

<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="author" content="Softnio">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description"
        content="A powerful and conceptual apps base dashboard template that especially build for developers and programmers.">
    <!-- Fav Icon  -->
    <link rel="shortcut icon" href="./images/favicon.png">
    <!-- Page Title  -->
    <title>{{ $page_title }}</title>
    <!-- StyleSheets  -->
    <link rel="stylesheet" href="../../css/dashlite.css">
    <link id="skin-default" rel="stylesheet" href="./../css/theme.css">
</head>

<body class="bg-white" onload="printPromot()">
    <div class="nk-block">
        <div class="invoice invoice-print">
            <div class="invoice-wrap">
                <div class="invoice-brand text-center">
                    <img src="{{ $logo }}" srcset="{{ $logo }}" alt="">
                </div>
                <div class="invoice-head">
                    <div class="invoice-contact">
                        <span class="overline-title">Invoice To</span>
                        <div class="invoice-contact-info">
                            <h4 class="title">{{ $username }}</h4>
                            <ul class="list-plain">
                                <li><em class="icon ni ni-mail fs-14px"></em><span>{{ $email }}</span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="invoice-desc">
                        <h3 class="title">Invoice</h3>
                        <ul class="list-plain">
                            <li class="invoice-id"><span>Invoice ID</span>:<span>{{ $invoice_id }}</span></li>
                            <li class="invoice-date"><span>Date</span>:<span>{{ $invoice_date }}</span></li>
                        </ul>
                    </div>
                </div><!-- .invoice-head -->
                <div class="invoice-bills">
                    <div class="table-responsive">
                        <table class="table table-striped">
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
                                        <td>{{ $service['service_id'] }}</td>
                                        <td>{{ $service['service_name'] }}</td>
                                        <td>{{ $service['price'] }}</td>
                                        <td>1</td>
                                        <td>{{ $service['price'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2"></td>
                                    <td colspan="2">Subtotal</td>
                                    <td>${{ number_format($subtotal, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2"></td>
                                    <td colspan="2">Auger fee</td>
                                    <td>${{ number_format($auger_fee, 2, ',', '.') }}</td>
                                </tr>
                                <?php
                                $discountPercent = (($subtotal - $grand_total) / $subtotal) * 100;
                                if($discountPercent > 0) { ?>
                                <tr>
                                    <td colspan="2"></td>
                                    <td colspan="2">Discount({{ $discountPercent }}%)</td>
                                    <td>${{ number_format($subtotal - $grand_total, 2, ',', '.') }}</td>
                                </tr>
                                <?php }
                                ?>
                                <tr>
                                    <td colspan="2"></td>
                                    <td colspan="2">Grand Total</td>
                                    <td>{{ number_format($grand_total, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div><!-- .invoice-bills -->
            </div><!-- .invoice-wrap -->
        </div><!-- .invoice -->
    </div><!-- .nk-block -->
</body>

</html>
