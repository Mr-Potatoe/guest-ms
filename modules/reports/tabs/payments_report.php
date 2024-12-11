<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Method</th>
                <th>Date</th>
                <th>Processed By</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $payments = $payment->getPaymentsReport($start_date, $end_date);
            while ($row = $payments->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>{$row['payment_id']}</td>";
                echo "<td>" . htmlspecialchars($row['guest_name'] ?? 'N/A') . 
                     "<br><small class='text-muted'>Booking #{$row['booking_id']}</small></td>";
                echo "<td>" . htmlspecialchars($row['room_number'] ?? 'N/A') . "</td>";
                echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                echo "<td>" . ucfirst($row['payment_type']) . "</td>";
                echo "<td>" . ucfirst($row['payment_method']) . "</td>";
                echo "<td>" . date('M d, Y H:i', strtotime($row['payment_date'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['admin_name'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="3" class="text-end"><strong>Total Payments:</strong></td>
                <td colspan="5"><strong>₱<?php echo number_format($payment->getTotalRevenue($start_date, $end_date), 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div> 