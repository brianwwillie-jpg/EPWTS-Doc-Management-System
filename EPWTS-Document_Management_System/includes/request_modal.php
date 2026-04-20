<!-- View/Delete Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="requestModalLabel">Document Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="submit_request.php" method="POST" id="requestForm">
        <div class="modal-body">
          <input type="hidden" name="doc_id" id="modal_doc_id">
          <input type="hidden" name="request_type" id="modal_request_type">
          
          <div class="mb-3">
            <label for="reason" class="form-label">Reason for Request</label>
            <textarea class="form-control" name="reason" id="reason" rows="3" placeholder="Explain why you need to view/delete this item..." required></textarea>
          </div>
          <p class="text-muted small">Your request will be sent to the Director for approval.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>
