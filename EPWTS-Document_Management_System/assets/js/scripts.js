// Function to handle opening the Request Form Modal
function openRequestModal(docId, type) {
    // Set the document ID and Request Type in the hidden fields
    document.getElementById('modal_doc_id').value = docId;
    document.getElementById('modal_request_type').value = type;
    
    // Update Modal Title based on action
    document.getElementById('requestModalLabel').innerText = type + " Request for ID: " + docId;
    
    // Show the Bootstrap Modal
    var myModal = new bootstrap.Modal(document.getElementById('requestModal'));
    myModal.show();
}

// Optional: AJAX submission to keep the user on the same page
document.getElementById('requestForm').addEventListener('submit', function(e) {
    // You can add logic here to submit via fetch() if you don't want a page reload
    console.log("Submitting " + document.getElementById('modal_request_type').value + " request...");
});
