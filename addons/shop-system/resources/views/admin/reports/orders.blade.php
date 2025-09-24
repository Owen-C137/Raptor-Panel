@extends('layouts.admin')

@section('title')
    Orders Report
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Orders Report
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Detailed order analytics
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
            Orders
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
                    <i class="fa fa-shopping-cart me-1"></i>Order Analytics
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
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-primary-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-primary">
                                    <i class="fa fa-shopping-cart"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Total Orders</div>
                                <div class="fs-3 fw-bold text-dark">{{ $orderStats['total_orders'] }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-success-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-success">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Completed</div>
                                <div class="fs-3 fw-bold text-dark">{{ $orderStats['completed_orders'] }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-warning-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-warning">
                                    <i class="fa fa-clock"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Pending</div>
                                <div class="fs-3 fw-bold text-dark">{{ $orderStats['pending_orders'] }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-danger-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-danger">
                                    <i class="fa fa-times-circle"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Failed</div>
                                <div class="fs-3 fw-bold text-dark">{{ $orderStats['failed_orders'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">
                                    <i class="fa fa-chart-pie me-1"></i>Order Status Breakdown
                                </h3>
                            </div>
                            <div class="block-content">
                                <canvas id="statusChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">
                                    <i class="fa fa-chart-line me-1"></i>Order Trends
                                </h3>
                            </div>
                            <div class="block-content">
                                <canvas id="trendsChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <h4 class="fw-bold mb-3">
                            <i class="fa fa-list me-1"></i>Order Status Details
                        </h4>
                        <div class="block block-rounded">
                            <div class="block-content block-content-full">
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statusBreakdown as $status => $count)
                                                <tr>
                                                    <td>
                                                        <span class="badge 
                                                            @if($status === 'completed') bg-success
                                                            @elseif($status === 'pending') bg-warning
                                                            @elseif($status === 'failed') bg-danger
                                                            @else bg-secondary
                                                            @endif
                                                        ">
                                                            {{ ucfirst($status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold">{{ $count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $orderStats['total_orders'] > 0 ? round(($count / $orderStats['total_orders']) * 100, 1) : 0 }}%</span>
                                                    </td>
                                                    <td>
                                                        <div class="fs-sm text-muted">
                                                            @if($status === 'completed')
                                                                Orders that have been successfully processed and fulfilled
                                                            @elseif($status === 'pending')
                                                                Orders awaiting payment or processing
                                                            @elseif($status === 'failed')
                                                                Orders that encountered errors during processing
                                                            @elseif($status === 'cancelled')
                                                                Orders that were cancelled by user or admin
                                                            @else
                                                                {{ ucfirst(str_replace('_', ' ', $status)) }} orders
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
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
            // Status breakdown pie chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: @json(array_map('ucfirst', array_keys($statusBreakdown))),
                    datasets: [{
                        data: @json(array_values($statusBreakdown)),
                        backgroundColor: [
                            '#198754', // completed - success green
                            '#ffc107', // pending - warning yellow  
                            '#dc3545', // failed - danger red
                            '#6c757d', // cancelled - secondary gray
                            '#0d6efd', // processing - primary blue
                            '#20c997'  // other - teal
                        ],
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });

            // Order trends line chart
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            const trendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: @json(array_keys($trendData ?? [])),
                    datasets: [{
                        label: 'Orders',
                        data: @json(array_values($trendData ?? [])),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#0d6efd',
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
                            borderColor: '#0d6efd',
                            borderWidth: 1,
                            cornerRadius: 6,
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
                                stepSize: 1
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverBackgroundColor: '#0d6efd'
                        }
                    }
                }
            });
        });
    </script>
@endsection