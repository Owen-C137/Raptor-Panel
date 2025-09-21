@extends('layouts.admin')

@section('title')
    Revenue Report
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Revenue Report
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Detailed revenue analytics
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.reports.index') }}">Reports</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Revenue
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-dollar-sign me-1"></i>Revenue Analytics
                </h3>
                <div class="block-options">
                    <form method="GET" class="d-inline-block">
                        <select name="period" onchange="this.form.submit()" class="form-select form-select-sm">
                            <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last Year</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="block-content">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="block block-rounded text-center bg-success-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-success">
                                    <i class="fa fa-dollar-sign"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Total Revenue</div>
                                <div class="fs-3 fw-bold text-dark">${{ number_format($totalRevenue, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="block block-rounded text-center bg-primary-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-primary">
                                    <i class="fa fa-chart-line"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Average Order Value</div>
                                <div class="fs-3 fw-bold text-dark">${{ number_format($averageOrderValue, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="block block-rounded text-center bg-warning-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-warning">
                                    <i class="fa fa-calendar-day"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Daily Average</div>
                                <div class="fs-3 fw-bold text-dark">${{ number_format($totalRevenue / $period, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">
                                    <i class="fa fa-chart-area me-1"></i>Revenue Trend
                                </h3>
                            </div>
                            <div class="block-content">
                                <div class="row">
                                    <div class="col-12">
                                        <canvas id="revenueChart" height="120"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <h4 class="fw-bold mb-3">
                            <i class="fa fa-table me-1"></i>Revenue Breakdown by Date
                        </h4>
                        <div class="block block-rounded">
                            <div class="block-content block-content-full">
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Revenue</th>
                                                <th>Orders</th>
                                                <th>Average Order Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($revenueData as $data)
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($data->date)->format('M d, Y') }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success fs-sm">${{ number_format($data->revenue, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold">{{ $data->orders }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary fs-sm">${{ number_format($data->orders > 0 ? $data->revenue / $data->orders : 0, 2) }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="fa fa-info-circle me-1"></i>
                                                            No revenue data available for this period
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($revenueData->pluck('date')->map(function($date) { return \Carbon\Carbon::parse($date)->format('M d'); })),
                    datasets: [{
                        label: 'Revenue',
                        data: @json($revenueData->pluck('revenue')),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#198754',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#198754',
                            borderWidth: 1,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: $' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                callback: function(value, index, values) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverBackgroundColor: '#198754'
                        }
                    }
                }
            });
        });
    </script>
@endsection