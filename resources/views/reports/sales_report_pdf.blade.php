<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="utf-8" />
    <title>تقرير المبيعات</title>
    <style>
        body {

            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>SALES REPORTS</h1>
    <p>Report Type : {{ $report->report_type }}</p>
    <p>Total Sales: {{ $report->total_sales }} </p>
    <p>Top Medicien: {{ $report->top_medicine }}</p>
    <p>Total Bills: {{ $report->total_bills }}</p>
    <p>Notes: {{ $report->notes ?? 'Not Foind' }}</p>
    <p>Created At : {{ $report->created_at->format('Y-m-d') }}</p>
</body>
</html>
