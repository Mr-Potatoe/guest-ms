<div class="row mb-4">
    <!-- Service Type Revenue Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Revenue by Service Type</h5>
            </div>
            <div class="card-body">
                <div id="serviceTypeChart"></div>
            </div>
        </div>
    </div>
    
    <!-- Service Status Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Services by Status</h5>
            </div>
            <div class="card-body">
                <div id="serviceStatusChart"></div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Service ID</th>
                <th>Booking</th>
                <th>Guest</th>
                <th>Service Type</th>
                <th>Price</th>
                <th>Status</th>
                <th>Date</th>
                <th>Processed By</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $services = $service->getServicesReport($start_date, $end_date);
            while ($row = $services->fetch(PDO::FETCH_ASSOC)) {
                $statusClass = match($row['status']) {
                    'pending' => 'warning',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                
                echo "<tr>";
                echo "<td>{$row['service_id']}</td>";
                echo "<td>" . ($row['booking_id'] ? "Booking #{$row['booking_id']}" : "N/A") . "</td>";
                echo "<td>" . ($row['guest_name'] ?? "N/A") . "</td>";
                echo "<td>" . ucfirst(str_replace('_', ' ', $row['service_type'])) . "</td>";
                echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                echo "<td><span class='badge bg-{$statusClass}'>" . ucfirst($row['status']) . "</span></td>";
                echo "<td>" . date('M d, Y H:i', strtotime($row['created_at'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="5" class="text-end"><strong>Total Services Revenue:</strong></td>
                <td colspan="4"><strong>₱<?php echo number_format($service->getTotalRevenue($start_date, $end_date), 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
// Initialize service charts when the services tab is shown
let chartsInitialized = false;
document.querySelector('a[href="#services"]').addEventListener('shown.bs.tab', function (e) {
    if (chartsInitialized) return;
    
    // Service Type Revenue Chart
    const serviceTypeData = <?php 
        $typeData = $service->getServiceRevenueByType($start_date, $end_date);
        $types = [];
        $revenues = [];
        while ($row = $typeData->fetch(PDO::FETCH_ASSOC)) {
            $types[] = ucfirst(str_replace('_', ' ', $row['service_type']));
            $revenues[] = floatval($row['total_revenue']);
        }
        echo json_encode(['labels' => $types, 'values' => $revenues]);
    ?>;

    const serviceTypeOptions = {
        series: [{
            name: 'Revenue',
            data: serviceTypeData.values
        }],
        chart: {
            type: 'bar',
            height: 350
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
                columnWidth: '55%'
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: serviceTypeData.labels
        },
        yaxis: {
            title: {
                text: 'Revenue (₱)'
            },
            labels: {
                formatter: function(value) {
                    return '₱' + value.toLocaleString();
                }
            }
        },
        colors: ['#2E93fA']
    };

    // Service Status Chart
    const serviceStatusData = <?php 
        $statusData = $service->getServiceStatusCounts($start_date, $end_date);
        $statuses = [];
        $counts = [];
        while ($row = $statusData->fetch(PDO::FETCH_ASSOC)) {
            $statuses[] = ucfirst($row['status']);
            $counts[] = intval($row['count']);
        }
        echo json_encode(['labels' => $statuses, 'values' => $counts]);
    ?>;

    const serviceStatusOptions = {
        series: serviceStatusData.values,
        chart: {
            type: 'pie',
            height: 350
        },
        labels: serviceStatusData.labels,
        colors: ['#FFA500', '#17a2b8', '#28a745', '#dc3545', '#6c757d'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    // Render charts
    if (document.querySelector("#serviceTypeChart")) {
        const serviceTypeChart = new ApexCharts(document.querySelector("#serviceTypeChart"), serviceTypeOptions);
        serviceTypeChart.render();
    }

    if (document.querySelector("#serviceStatusChart")) {
        const serviceStatusChart = new ApexCharts(document.querySelector("#serviceStatusChart"), serviceStatusOptions);
        serviceStatusChart.render();
    }
    
    chartsInitialized = true;
});
</script>