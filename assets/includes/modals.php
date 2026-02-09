<!-- Global Modals for WSU Booking System -->

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h2 id="confirmTitle"><i class="fas fa-question-circle"></i> Confirm Action</h2>
            <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmButton" onclick="confirmAction()">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal (replaces alert) -->
<div class="modal-overlay" id="messageModal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h2 id="messageTitle"><i class="fas fa-info-circle"></i> Message</h2>
            <button class="modal-close" onclick="closeMessageModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="messageContent"></p>
            <div class="modal-actions" style="justify-content: center;">
                <button type="button" class="btn btn-primary" onclick="closeMessageModal()">
                    <i class="fas fa-check"></i> OK
                </button>
            </div>
        </div>
    </div>
</div>
