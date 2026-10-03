<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>{{ $purchase->invoice_no }}</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #333;
    }

    .header {
      margin-bottom: 20px;
    }

    .title {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 6px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
    }

    th {
      width: 30%;
      background: #f5f5f5;
    }
  </style>
</head>

<body>
  <div class="header">
    <div class="title">AI Token Purchase Invoice</div>
    <div><strong>Invoice No:</strong> {{ $purchase->invoice_no }}</div>
    <div><strong>Organizer:</strong> {{ $organizerInfo->name ?? $organizer->username ?? 'Organizer' }}</div>
    <div><strong>Email:</strong> {{ $organizer->email ?? '-' }}</div>
  </div>

  <table>
    <tr>
      <th>Package</th>
      <td>{{ $packageTitle }}</td>
    </tr>
    <tr>
      <th>AI Engine</th>
      <td>{{ strtoupper($purchase->ai_engine) }}</td>
    </tr>
    <tr>
      <th>Content Tokens</th>
      <td>{{ $purchase->ai_token_limit }}</td>
    </tr>
    <tr>
      <th>Image Limit</th>
      <td>{{ $purchase->ai_image_limit }}</td>
    </tr>
    <tr>
      <th>Price</th>
      <td>{{ symbolPrice($purchase->price) }}</td>
    </tr>
    <tr>
      <th>Payment Method</th>
      <td>{{ $purchase->payment_method ?? '-' }}</td>
    </tr>
    <tr>
      <th>Payment Status</th>
      <td>{{ ucfirst($purchase->payment_status) }}</td>
    </tr>
    <tr>
      <th>Approval Status</th>
      <td>{{ ucfirst($purchase->status) }}</td>
    </tr>
    <tr>
      <th>Purchase Date</th>
      <td>{{ \Carbon\Carbon::parse($purchase->created_at)->format('d M, Y h:i A') }}</td>
    </tr>
  </table>
</body>

</html>
