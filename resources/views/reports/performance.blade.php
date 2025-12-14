<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <style>
        html, body { direction: ltr; text-align: left; }
        body { font-family: sans-serif; font-size: 12px; }
        h1, h2, h3 { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f3f3; }
        .muted { color: #666; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Performance Report</h1>
    <div class="muted">
        Period: {{ $stats['filters']['from'] }} → {{ $stats['filters']['to'] }} |
        Generated at: {{ $stats['generated_at'] }}
    </div>

    <h2>KPIs</h2>
    <table>
        <tbody>
        @foreach($stats['kpis'] as $k => $v)
            <tr>
                <th>{{ $k }}</th>
                <td>{{ is_null($v) ? '-' : $v }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Breakdowns</h2>

    <h3>By Status</h3>
    <table>
        <thead><tr><th>Status</th><th>Count</th><th>Percentage</th></tr></thead>
        <tbody>
        @foreach($stats['breakdowns']['by_status'] as $row)
            <tr>
                <td>{{ $row['label'] ?? $row['key'] }}</td>
                <td>{{ $row['count'] }}</td>
                <td>{{ round($row['percentage'] * 100, 2) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>By Priority</h3>
    <table>
        <thead><tr><th>Priority</th><th>Count</th><th>Percentage</th></tr></thead>
        <tbody>
        @foreach($stats['breakdowns']['by_priority'] as $row)
            <tr>
                <td>{{ $row['label'] ?? $row['key'] }}</td>
                <td>{{ $row['count'] }}</td>
                <td>{{ round($row['percentage'] * 100, 2) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>By Department</h3>
    <table>
        <thead><tr><th>Department</th><th>Count</th><th>Percentage</th></tr></thead>
        <tbody>
        @foreach($stats['breakdowns']['by_department'] as $row)
            <tr>
                <td>{{ $row['name'] ?? ('#'.$row['id']) }}</td>
                <td>{{ $row['count'] }}</td>
                <td>{{ round($row['percentage'] * 100, 2) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Trends</h2>

    <h3>Created Per Day</h3>
    <table>
        <thead><tr><th>Date</th><th>Count</th></tr></thead>
        <tbody>
        @foreach($stats['trends']['created_per_day'] as $row)
            <tr><td>{{ $row['date'] }}</td><td>{{ $row['count'] }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <h3>Resolved Per Day</h3>
    <table>
        <thead><tr><th>Date</th><th>Count</th></tr></thead>
        <tbody>
        @foreach($stats['trends']['resolved_per_day'] as $row)
            <tr><td>{{ $row['date'] }}</td><td>{{ $row['count'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
