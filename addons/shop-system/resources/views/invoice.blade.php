<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background-color: #fff;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .company-details {
            color: #666;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .invoice-info {
            text-align: right;
        }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .invoice-number {
            font-size: 18px;
            color: #007bff;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .invoice-date {
            color: #666;
            font-size: 13px;
        }
        
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .billing-info {
            flex: 1;
            margin-right: 30px;
        }
        
        .order-info {
            flex: 1;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 100px;
        }
        
        .info-value {
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .status-suspended { background-color: #d1ecf1; color: #0c5460; }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .items-table th,
        .items-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .items-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .items-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        
        .summary-table {
            width: 300px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .summary-row:last-child {
            border-bottom: 2px solid #007bff;
            font-weight: bold;
            font-size: 16px;
            color: #007bff;
            padding-top: 15px;
            margin-top: 10px;
        }
        
        .summary-label {
            font-weight: 500;
            color: #555;
        }
        
        .summary-value {
            font-weight: bold;
            color: #333;
        }
        
        .notes-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .notes-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .notes-content {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .server-details {
            background-color: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .server-details .info-label {
            width: 120px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ config('app.name', 'Pterodactyl Panel') }}</div>
                <div class="company-details">
                    Game Server Hosting Platform<br>
                    Professional Hosting Services<br>
                    support@<?php echo $domain; ?>
                </div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="invoice-date">{{ $order->created_at->format('F j, Y') }}</div>
            </div>
        </div>

        {{-- Billing Information --}}
        <div class="billing-section">
            <div class="billing-info">
                <div class="section-title">Bill To</div>
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Customer:</span>
                        <span class="info-value">{{ $order->user->username }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $order->user->email }}</span>
                    </div>
                    @if($order->billing_details && !empty($order->billing_details['first_name']))
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value">{{ $order->billing_details['first_name'] }} {{ $order->billing_details['last_name'] ?? '' }}</span>
                    </div>
                    @endif
                    @if($order->billing_details && !empty($order->billing_details['company']))
                    <div class="info-row">
                        <span class="info-label">Company:</span>
                        <span class="info-value">{{ $order->billing_details['company'] }}</span>
                    </div>
                    @endif
                    @if($order->getBillingAddress())
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value">{!! nl2br(e($order->getBillingAddress())) !!}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="order-info">
                <div class="section-title">Order Details</div>
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Order ID:</span>
                        <span class="info-value">#{{ $order->id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value">{{ $order->created_at->format('F j, Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Billing:</span>
                        <span class="info-value">{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</span>
                    </div>
                    @if($order->next_due_at)
                    <div class="info-row">
                        <span class="info-label">Next Due:</span>
                        <span class="info-value">{{ $order->next_due_at->format('F j, Y') }}</span>
                    </div>
                    @endif
                    @if($order->payment_method)
                    <div class="info-row">
                        <span class="info-label">Payment:</span>
                        <span class="info-value">{{ ucfirst($order->payment_method) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $order->plan->name }}</strong>
                        @if($order->plan->description)
                        <br><small style="color: #666;">{{ $order->plan->description }}</small>
                        @endif
                        <br><small style="color: #666;">Billing Cycle: {{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</small>
                        
                        {{-- Server Details --}}
                        @if($order->server)
                        <div class="server-details">
                            <div style="font-weight: bold; margin-bottom: 8px; color: #007bff;">Server Information</div>
                            <div class="info-row">
                                <span class="info-label">Server Name:</span>
                                <span class="info-value">{{ $order->server->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Server ID:</span>
                                <span class="info-value">{{ $order->server->uuidShort }}</span>
                            </div>
                            @if($order->server->allocation)
                            <div class="info-row">
                                <span class="info-label">Connection:</span>
                                <span class="info-value">{{ $order->server->allocation->ip }}:{{ $order->server->allocation->port }}</span>
                            </div>
                            @endif
                            <div class="info-row">
                                <span class="info-label">Status:</span>
                                <span class="info-value">{{ ucfirst($order->server->status ?? 'Installing') }}</span>
                            </div>
                        </div>
                        @endif
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right">${{ number_format($order->amount, 2) }}</td>
                    <td class="text-right">${{ number_format($order->amount, 2) }}</td>
                </tr>
                
                @if($order->setup_fee > 0)
                <tr>
                    <td>
                        <strong>Setup Fee</strong>
                        <br><small style="color: #666;">One-time setup charge</small>
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right">${{ number_format($order->setup_fee, 2) }}</td>
                    <td class="text-right">${{ number_format($order->setup_fee, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-section">
            <div class="summary-table">
                <div class="summary-row">
                    <span class="summary-label">Subtotal:</span>
                    <span class="summary-value">${{ number_format($order->amount, 2) }}</span>
                </div>
                
                @if($order->setup_fee > 0)
                <div class="summary-row">
                    <span class="summary-label">Setup Fee:</span>
                    <span class="summary-value">${{ number_format($order->setup_fee, 2) }}</span>
                </div>
                @endif
                
                @if(isset($order->tax_amount) && $order->tax_amount > 0)
                <div class="summary-row">
                    <span class="summary-label">Tax:</span>
                    <span class="summary-value">${{ number_format($order->tax_amount, 2) }}</span>
                </div>
                @endif
                
                @if(isset($order->discount_amount) && $order->discount_amount > 0)
                <div class="summary-row">
                    <span class="summary-label">Discount:</span>
                    <span class="summary-value">-${{ number_format($order->discount_amount, 2) }}</span>
                </div>
                @endif
                
                <div class="summary-row">
                    <span class="summary-label">Total:</span>
                    <span class="summary-value">${{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Payment Information --}}
        @if($order->payments && $order->payments->count() > 0)
        <div class="notes-section">
            <div class="notes-title">Payment Information</div>
            <div class="info-box">
                @foreach($order->payments as $payment)
                <div class="info-row">
                    <span class="info-label">{{ $payment->created_at->format('M j, Y') }}:</span>
                    <span class="info-value">
                        ${{ number_format($payment->amount, 2) }} via {{ ucfirst($payment->gateway) }}
                        @if($payment->status === 'completed')
                            <span style="color: #28a745;">(Paid)</span>
                        @else
                            <span style="color: #dc3545;">({{ ucfirst($payment->status) }})</span>
                        @endif
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Notes --}}
        <div class="notes-section">
            <div class="notes-title">Terms & Conditions</div>
            <div class="notes-content">
                <p>Thank you for choosing {{ config('app.name', 'Pterodactyl Panel') }} for your hosting needs. Your service will remain active as long as payments are made on time according to your billing cycle.</p>
                
                <p style="margin-top: 10px;"><strong>Important:</strong> Please keep this invoice for your records. If you have any questions about this invoice or your service, please contact our support team.</p>
                
                @if($order->status === 'pending')
                <p style="margin-top: 10px; color: #dc3545;"><strong>Payment Required:</strong> This order is pending payment. Please complete payment to activate your service.</p>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
            <p>{{ config('app.name', 'Pterodactyl Panel') }} - Professional Game Server Hosting</p>
            @if(config('app.url'))
            <p>{{ config('app.url') }}</p>
            @endif
        </div>
    </div>
</body>
</html>