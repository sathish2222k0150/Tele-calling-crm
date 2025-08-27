<?php
require '../config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}
$user_id = $_SESSION['user_id'] ?? null;
$sales_chart_data = [
    'months' => [],
    'leads' => [],
    'converted' => []
];

$totalLeads = 1;
$totalConverted = $totalFollowUp = $totalInProgress = $totalNotInterested = $totalToday = 0;

if ($user_id) {
    // From January to current month
    $startMonth = strtotime(date('Y') . '-01-01');
    $endMonth = strtotime(date('Y-m') . '-01');
    while ($startMonth <= $endMonth) {
        $month = date('Y-m', $startMonth);
        $sales_chart_data['months'][] = $month;

        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ? AND created_at BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start, $end]);
        $sales_chart_data['leads'][] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ? AND status = 'converted' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start, $end]);
        $sales_chart_data['converted'][] = (int)$stmt->fetchColumn();

        $startMonth = strtotime('+1 month', $startMonth);
    }

    // Goal Completion Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ?");
    $stmt->execute([$user_id]);
    $totalLeads = max(1, (int)$stmt->fetchColumn());

    $statuses = [
        'converted' => &$totalConverted,
        'follow_up' => &$totalFollowUp,
        'in_progress' => &$totalInProgress,
        'not_interested' => &$totalNotInterested,
    ];

    foreach ($statuses as $status => &$count) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ? AND status = ?");
        $stmt->execute([$user_id, $status]);
        $count = (int)$stmt->fetchColumn();
    }

    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ? AND DATE(created_at) = ?");
    $stmt->execute([$user_id, $today]);
    $totalToday = (int)$stmt->fetchColumn();

    // Percentages
    $convertedPercent = round(($totalConverted / $totalLeads) * 100);
    $followUpPercent = round(($totalFollowUp / $totalLeads) * 100);
    $inProgressPercent = round(($totalInProgress / $totalLeads) * 100);
    $notInterestedPercent = round(($totalNotInterested / $totalLeads) * 100);
    $todayPercent = round(($totalToday / $totalLeads) * 100);

    // Get first and last date for the report header
    $firstDate = $sales_chart_data['months'][0] ?? date('Y-m');
    $lastDate = end($sales_chart_data['months']) ?? date('Y-m');
    $firstDateFormatted = date('M Y', strtotime($firstDate . '-01'));
    $lastDateFormatted = date('M Y', strtotime($lastDate . '-01'));
}
?>

<div class="container-fluid">
    <form method="GET" class="mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <button type="button" id="downloadCSV" class="btn btn-success mt-4">Download CSV</button>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Monthly Recap Report</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="text-center">
                                <strong>Sales: <?= $firstDateFormatted ?> - <?= $lastDateFormatted ?></strong>
                            </p>
                            <div id="sales-chart"></div>
                        </div>
                        <div class="col-md-4">
                            <p class="text-center"><strong>Goal Completion</strong></p>
                            <?php
                            $progress = [
                                ['label' => 'Converted Leads', 'value' => $totalConverted, 'percent' => $convertedPercent, 'class' => 'bg-success'],
                                ['label' => 'Leads with Follow-up', 'value' => $totalFollowUp, 'percent' => $followUpPercent, 'class' => 'bg-info'],
                                ['label' => 'In Progress', 'value' => $totalInProgress, 'percent' => $inProgressPercent, 'class' => 'bg-primary'],
                                ['label' => 'Not Interested', 'value' => $totalNotInterested, 'percent' => $notInterestedPercent, 'class' => 'bg-danger'],
                                ['label' => 'Uploaded Today', 'value' => $totalToday, 'percent' => $todayPercent, 'class' => 'bg-warning'],
                            ];
                            ?>
                            <?php foreach ($progress as $item): ?>
                                <div class="progress-group mb-3">
                                    <?= $item['label'] ?>
                                    <span class="float-end"><b><?= $item['value'] ?></b> / <?= $totalLeads ?></span>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar <?= $item['class'] ?>" style="width: <?= $item['percent'] ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
<script>
    const sales_chart_options = {
        series: [
            {
                name: 'Leads',
                data: <?= json_encode($sales_chart_data['leads'] ?? []) ?>
            },
            {
                name: 'Converted',
                data: <?= json_encode($sales_chart_data['converted'] ?? []) ?>
            }
        ],
        chart: {
            height: 300,
            type: 'area',
            toolbar: { show: false }
        },
        colors: ['#0d6efd', '#20c997'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth' },
        xaxis: {
            categories: <?= json_encode(array_map(fn($m) => date('M Y', strtotime($m . '-01')), $sales_chart_data['months'] ?? [])) ?>
        },
        tooltip: {
            x: { format: 'MMMM yyyy' }
        }
    };

    <?php if ($user_id): ?>
    const sales_chart = new ApexCharts(document.querySelector('#sales-chart'), sales_chart_options);
    sales_chart.render();

    // CSV Download
    document.getElementById('downloadCSV').addEventListener('click', function () {
        const months = <?= json_encode(array_map(fn($m) => date('M Y', strtotime($m . '-01')), $sales_chart_data['months'] ?? [])) ?>;
        const leads = <?= json_encode($sales_chart_data['leads'] ?? []) ?>;
        const converted = <?= json_encode($sales_chart_data['converted'] ?? []) ?>;

        let csv = 'Month,Leads,Converted\n';
        for (let i = 0; i < months.length; i++) {
            csv += `"${months[i]}",${leads[i]},${converted[i]}\n`;
        }

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'monthly_report.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
    <?php endif; ?>
</script>
