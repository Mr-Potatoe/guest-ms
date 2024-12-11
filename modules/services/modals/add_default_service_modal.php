<div class="modal fade" id="addDefaultServiceModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Default Service</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="create_default.php" method="POST">
                <div class="modal-body">
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
                        <label>Description</label>
                        <textarea class="form-control" name="description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Default Price</label>
                        <input type="number" step="0.01" class="form-control" name="default_price" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div> 