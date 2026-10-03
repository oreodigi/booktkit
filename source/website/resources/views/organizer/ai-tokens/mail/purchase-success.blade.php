<p>Hello {{ $organizerName }},</p>

<p>Your AI token package purchase has been recorded successfully.</p>

<p><strong>Invoice No:</strong> {{ $purchase->invoice_no }}</p>
<p><strong>Package:</strong> {{ $packageTitle }}</p>
<p><strong>AI Engine:</strong> {{ strtoupper($purchase->ai_engine) }}</p>
<p><strong>Content Tokens:</strong> {{ $purchase->ai_token_limit }}</p>
<p><strong>Image Limit:</strong> {{ $purchase->ai_image_limit }}</p>
<p><strong>Price:</strong> {{ symbolPrice($purchase->price) }}</p>
<p><strong>Payment Method:</strong> {{ $purchase->payment_method ?? '-' }}</p>
<p><strong>Payment Status:</strong> {{ ucfirst($purchase->payment_status) }}</p>
<p><strong>Approval Status:</strong> {{ ucfirst($purchase->status) }}</p>

<p>Please find the invoice attached with this email.</p>

<p>{{ $websiteTitle }}</p>
