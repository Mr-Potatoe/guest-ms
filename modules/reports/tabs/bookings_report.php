<?php
// Debug information
if (empty($start_date) || empty($end_date)) {
    echo '<div class="alert alert-warning">Date range not properly set</div>';
}
?>
<div class="row mb-4">
    <!-- Booking Revenue Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Revenue by Date</h5>
            </div>
            <div class="card-body">
                <div id="bookingRevenueChart"></div>
            </div>
        </div>
    </div>
    
    <!-- Booking Status Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Bookings by Status</h5>
            </div>
            <div class="card-body">
                <div id="bookingStatusChart"></div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Status</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
            try {
                $bookings = $booking->getBookingsReport($start_date, $end_date);
                while ($row = $bookings->fetch(PDO::FETCH_ASSOC)) {
                    $statusClass = match($row['status']) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'checked_in' => 'info',
                        'checked_out' => 'secondary',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    };
                    
                    echo "<tr>";
                    echo "<td>{$row['booking_id']}</td>";
                    echo "<td>" . htmlspecialchars($row['guest_name']) . "</td>";
                    echo "<td>Room {$row['room_number']}</td>";
                    echo "<td>" . date('M d, Y', strtotime($row['check_in_date'])) . "</td>";
                    echo "<td>" . date('M d, Y', strtotime($row['check_out_date'])) . "</td>";
                    echo "<td><span class='badge bg-{$statusClass}'>" . ucfirst($row['status']) . "</span></td>";
                    echo "<td>₱" . number_format($row['total_price'], 2) . "</td>";
                    echo "</tr>";
                }
            } catch (PDOException $e) {
                echo "<tr><td colspan='7' class='text-center text-danger'>Error loading bookings data</td></tr>";
            }
            ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="6" class="text-end"><strong>Total Revenue:</strong></td>
                <td><strong>₱<?php echo number_format($booking->getTotalRevenue($start_date, $end_date), 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch booking data for charts
    const bookingRevenueData = <?php 
        $revenueData = [];
        $result = $booking->getBookingRevenueByDate($start_date, $end_date);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $revenueData[] = [
                'x' => date('M d', strtotime($row['booking_date'])),
                'y' => floatval($row['total_revenue'])
            ];
        }
        echo json_encode($revenueData);
    ?>;

    const bookingStatusData = <?php 
        $statusData = ['labels' => [], 'values' => []];
        $result = $booking->getBookingStatusCounts($start_date, $end_date);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $statusData['labels'][] = ucfirst($row['status']);
            $statusData['values'][] = intval($row['count']);
        }
        echo json_encode($statusData);
    ?>;

    // Configure charts
    const bookingRevenueOptions = {
        series: [{
            name: 'Revenue',
            data: bookingRevenueData
        }],
        chart: {
            type: 'bar',
            height: 350
        },
        plotOptions: {
            bar: {
                borderRadius: 4
            }
        },
        xaxis: {
            type: 'category'
        },
        yaxis: {
            labels: {
                formatter: function(value) {
                    return '₱' + value.toFixed(2);
                }
            }
        }
    };

    const bookingStatusOptions = {
        series: bookingStatusData.values,
        chart: {
            type: 'pie',
            height: 350
        },
        labels: bookingStatusData.labels,
        colors: ['#28a745', '#ffc107', '#17a2b8', '#6c757d', '#dc3545'],
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
    if (document.querySelector("#bookingRevenueChart")) {
        const bookingRevenueChart = new ApexCharts(document.querySelector("#bookingRevenueChart"), bookingRevenueOptions);
        bookingRevenueChart.render();
    }

    if (document.querySelector("#bookingStatusChart")) {
        const bookingStatusChart = new ApexCharts(document.querySelector("#bookingStatusChart"), bookingStatusOptions);
        bookingStatusChart.render();
    }
});
</script> 