@php
    $stats = $stats ?? [];

    $stats = array_merge([
        'today' => 0,
        'yesterday' => 0,
        'last7days' => 0,
        'thisMonth' => 0,
        'total' => 0,
        'dashboardFiltered' => null,
        'dailyLimit' => null,
        'monthlyLimit' => null,
    ], $stats);

    $cards = [
        'Leads Created (Today)' => $stats['dailyLimit']
            ? "{$stats['today']} / {$stats['dailyLimit']}"
            : number_format($stats['today']),

        'Leads Created Yesterday' => number_format($stats['yesterday']),
        'Leads Created In Last 7 Days' => number_format($stats['last7days']),

        'Leads Created (This Month)' => $stats['monthlyLimit']
            ? "{$stats['thisMonth']} / {$stats['monthlyLimit']}"
            : number_format($stats['thisMonth']),

        'Total Leads Count' => number_format($stats['total']),

        'Leads Count Based On Dashboard Date' => $stats['dashboardFiltered'] !== null
            ? number_format($stats['dashboardFiltered'])
            : '--',
    ];
@endphp

@foreach($cards as $label => $value)
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 dashboard-card-hover">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold text-primary mb-2">{{ $value }}</div>
                <p class="mb-0 text-muted small">{{ $label }}</p>
            </div>
        </div>
    </div>
@endforeach
