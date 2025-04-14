<!DOCTYPE html>
<html>
<head>
    <title>Earnings Report</title>
    <style>
        /* Basic styling for the PDF */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        h1 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ $earningDetailsHeading }}</h1>  <!-- Heading based on the timeframe -->

    <table>
        <thead>
            <tr>
                <th>SR</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $key => $product)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $product['product_name'] }}</td>
                    <td>{{ $product['totalQuantity'] }}</td>
                    <td>{{ $product['price'] }}</td>
                    <td>{{ $product['totalEarnings'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4"><strong>Total Earnings</strong></td>
                <td>{{ $totalearnings }}</td>
            </tr>
            <tr>
                <td colspan="4"><strong>Total Commission</strong></td>
                <td>{{ $totalcommission }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
