<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Monthly Reports</title>
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
            text-align: center;
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
        LAPORAN BULANAN <br> {{ $summary['month'] }}/{{ $summary['year'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Minggu Ke-</th>
                <th>Total Cup</th>
                <th>Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report as $row)
                <tr>
                    <td>{{ $row->week_number }}</td>
                    <td>{{ $row->total_cups }}</td>
                    <td>Rp {{ number_format($row->total_income, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Ringkasan Bulanan</h3>
    <p>
        Total Cup : {{ $summary['total_cups'] }} <br>
        Total Pendapatan : Rp {{ number_format($summary['total_income'], 0, ',', '.') }}
    </p>

</body>

</html>
