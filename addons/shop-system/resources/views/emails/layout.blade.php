<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('subject')</title>
    
    <style>
        /* Reset styles */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            background-color: #f4f4f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #51545e;
        }
        
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
        }
        
        .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
        }
        
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }
        
        .email-masthead_logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            text-decoration: none;
        }
        
        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            border-top: 1px solid #edeff2;
            border-bottom: 1px solid #edeff2;
        }
        
        .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            background-color: #ffffff;
        }
        
        .email-body_cell {
            padding: 35px;
        }
        
        .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            text-align: center;
        }
        
        .email-footer p {
            color: #a8aaaf;
            font-size: 12px;
            text-align: center;
        }
        
        /* Content styles */
        h1, h2, h3 {
            margin-top: 0;
            color: #2f3133;
            font-size: 22px;
            font-weight: bold;
            line-height: 1.25;
        }
        
        h2 {
            font-size: 18px;
        }
        
        h3 {
            font-size: 16px;
        }
        
        p {
            margin-top: 0;
            color: #51545e;
            font-size: 16px;
            line-height: 1.625;
        }
        
        p.sub {
            font-size: 13px;
        }
        
        .align-center {
            text-align: center;
        }
        
        .align-right {
            text-align: right;
        }
        
        /* Button styles */
        .button {
            background-color: #007bff;
            border-radius: 3px;
            color: #fff;
            display: inline-block;
            text-decoration: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .button--green {
            background-color: #28a745;
        }
        
        .button--red {
            background-color: #dc3545;
        }
        
        .button--orange {
            background-color: #fd7e14;
        }
        
        /* Panel styles */
        .panel {
            border-left: #007bff solid 4px;
            margin: 21px 0;
        }
        
        .panel-content {
            background-color: #f4f4f7;
            padding: 16px;
        }
        
        .panel-item {
            padding: 0;
            margin: 0;
        }
        
        /* Table styles */
        .table {
            width: 100%;
            margin: 30px 0;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #edeff2;
        }
        
        .table th {
            background-color: #f4f4f7;
            font-weight: bold;
            color: #74787e;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .table td {
            color: #74787e;
            font-size: 15px;
        }
        
        .content-cell {
            max-width: 100vw;
            padding: 35px;
        }
        
        /* Invoice styles */
        .invoice {
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border: 1px solid #edeff2;
            border-radius: 3px;
        }
        
        .invoice-header {
            border-bottom: 1px solid #edeff2;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        
        .invoice-number {
            color: #007bff;
            font-size: 18px;
            font-weight: bold;
        }
        
        .invoice-date {
            color: #74787e;
            font-size: 14px;
        }
        
        .invoice-total {
            text-align: right;
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        
        /* Status Panels */
        .panel.success {
            border-left: 4px solid #28a745;
            background-color: #f8fff9;
        }
        
        .panel.info {
            border-left: 4px solid #007bff;
            background-color: #f8f9ff;
        }
        
        .panel.warning {
            border-left: 4px solid #ffc107;
            background-color: #fffdf8;
        }
        
        .panel.error {
            border-left: 4px solid #dc3545;
            background-color: #fff8f8;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
            
            .content-cell {
                padding: 20px !important;
            }
        }
    </style>
</head>

<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    {{-- Header --}}
                    <tr>
                        <td class="email-masthead">
                            <a href="{{ config('app.url') }}" class="email-masthead_logo">
                                {{ config('app.name', 'Pterodactyl Panel') }}
                            </a>
                        </td>
                    </tr>
                    
                    {{-- Body --}}
                    <tr>
                        <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    {{-- Footer --}}
                    <tr>
                        <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell" align="center">
                                        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                                        <p>
                                            If you're having trouble with the buttons above, copy and paste the URL below into your web browser.
                                        </p>
                                        @yield('footer-links')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
