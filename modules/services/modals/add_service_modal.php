<div class="modal fade" id="addServiceModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Service</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="create.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Booking</label>
                        <select class="form-control" name="booking_id" required>
                            <option value="">Select Booking</option>
                            <?php
                            $bookings = $booking->readActiveBookings();
                            while ($row = $bookings->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . $row['booking_id'] . "'>" .
                                     "Booking #" . $row['booking_id'] . " - " .
                                     htmlspecialchars($row['guest_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Service Type</label>
                        <select class="form-control" name="service_type" required>
                            <?php
                            $service_types = $service->getServiceTypes();
                            foreach ($service_types as $type) {
                                echo '<option value="' . $type . '">' . 
                                     ucwords(str_replace('_', ' ', $type)) . 
                                     '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Price</label>
                        <input type="number" step="0.01" class="form-control" name="price" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div> 