document.addEventListener('DOMContentLoaded', function() {
  // Print report functionality
  document.getElementById('printReportBtn').addEventListener('click', function() {
    window.print();
  });

  // Attach handlers to all approval buttons
  document.querySelectorAll('.save-remarks').forEach(button => {
    button.addEventListener('click', handleApproveRemarks);
  });
  
  // Attach handlers to all revert buttons
  document.querySelectorAll('.uncheck-remarks').forEach(button => {
    button.addEventListener('click', handleRevertRemarks);
  });
  
  // Handler function for approving remarks (excluding offline events)
  function handleApproveRemarks() {
    const dayId = this.getAttribute('data-day-id');
    const date = this.getAttribute('data-date');
    const reportId = this.getAttribute('data-report-id');
    
    // Get all checkboxes for this day
    const row = document.getElementById(dayId);
    const checkboxes = row.querySelectorAll('.remark-checkbox:not(:disabled)');
    
    // Check if any remarks are checked
    let checkedCount = 0;
    const checkedRemarks = [];
    
    checkboxes.forEach(cb => {
      if (cb.checked) {
        checkedCount++;
        // Get the time from the label (in "12:57pm – 11:57pm" format)
        const label = cb.nextElementSibling;
        const timeSpan = label.querySelector('.remark-time');
        const remarkId = cb.getAttribute('data-remark-id');
        
        if (timeSpan) {
          const timeText = timeSpan.textContent.trim();
          const remarkText = label.querySelector('.remark-text').textContent.trim();
          checkedRemarks.push({
            id: remarkId || cb.id,
            time: timeText,
            remark: remarkText
          });
        } else {
          checkedRemarks.push({id: remarkId || cb.id});
        }
      }
    });
    
    // Allow approving if at least one checkbox is checked
    if (checkedCount > 0) {
      // Create form data
      const formData = new FormData();
      formData.append('date', date);
      formData.append('report_id', reportId);
      formData.append('action', 'clear_offline');
      formData.append('checked_remarks', JSON.stringify(checkedRemarks));
      
      // Show loading indicator
      const approveButton = this;
      const originalText = approveButton.innerHTML;
      approveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
      approveButton.disabled = true;
      
      // Make AJAX call to save the update
      fetch('update_offline_events.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to show updated data
          window.location.reload();
        } else {
          // Restore the button
          approveButton.innerHTML = originalText;
          approveButton.disabled = false;
          showAlert('danger', 'Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Request failed:', error);
        // Restore the button
        approveButton.innerHTML = originalText;
        approveButton.disabled = false;
        showAlert('danger', 'An error occurred while processing.');
      });
    } else {
      showAlert('warning', 'Please check at least one remark to approve.');
    }
  }
  
  // Handler function for reverting remarks (restoring offline events)
  function handleRevertRemarks() {
    const dayId = this.getAttribute('data-day-id');
    const date = this.getAttribute('data-date');
    const reportId = this.getAttribute('data-report-id');
    
    // Show loading indicator
    const revertButton = this;
    const originalText = revertButton.innerHTML;
    revertButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    revertButton.disabled = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('date', date);
    formData.append('report_id', reportId);
    formData.append('action', 'restore_offline');
    
    // Make AJAX call to restore the offline events
    fetch('update_offline_events.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Reload the page to show updated data
        window.location.reload();
      } else {
        // Restore the button
        revertButton.innerHTML = originalText;
        revertButton.disabled = false;
        showAlert('danger', 'Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Request failed:', error);
      // Restore the button
      revertButton.innerHTML = originalText;
      revertButton.disabled = false;
      showAlert('danger', 'An error occurred while processing.');
    });
  }
  
  function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert alert at top of card body
    const cardBody = document.querySelector('.card-body');
    cardBody.insertBefore(alertDiv, cardBody.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alertDiv);
      bsAlert.close();
    }, 5000);
  }
});