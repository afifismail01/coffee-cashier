<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Daily Reports</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #eee;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="title">
        LAPORAN HARIAN <br> {{ $summary['date'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Jumlah Terjual</th>
                <th>Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report as $row)
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->total_quantity }}</td>
                    <td>Rp {{ number_format($row->total_revenue, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Total</h3>
    <p>
        Total Cup : {{ $summary['total_products_sold'] }} <br>
        Total Pendapatan : Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}
    </p>
</body>

</html>
