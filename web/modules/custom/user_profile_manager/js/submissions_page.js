(function (Drupal) {
  'use strict';

  window.confirmDelete = function(element, submissionId) {
    if (!confirm('Are you sure you want to delete this submission?')) {
      return false;
    }

    // Show loading state
    element.textContent = '⏳ Deleting...';
    element.style.pointerEvents = 'none';

    // Get the URL and add ajax parameter
    const url = element.getAttribute('href') + '?ajax=1';
    
    fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Find and remove the table row
        const row = element.closest('tr');
        if (row) {
          row.style.transition = 'opacity 0.3s ease-out';
          row.style.opacity = '0';
          setTimeout(() => {
            row.remove();
            
            // Check if there are no more submissions
            const table = document.querySelector('.submissions-table tbody');
            if (table && table.querySelectorAll('tr').length === 0) {
              // Reload to show "no submissions" message
              window.location.reload();
            }
          }, 300);
        }
      } else {
        alert('Failed to delete submission: ' + (data.message || 'Unknown error'));
        element.textContent = '🗑️ Delete';
        element.style.pointerEvents = 'auto';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
      element.textContent = '🗑️ Delete';
      element.style.pointerEvents = 'auto';
    });

    return false;
  };

  window.quickApprove = function(submissionId, newStatus) {
    const confirmMsg = newStatus === 'approved' 
      ? 'Are you sure you want to approve this submission?' 
      : 'Are you sure you want to deny this submission?';
    
    if (!confirm(confirmMsg)) {
      return;
    }

    // Build the approval API URL
    const url = `/formulario-candidatura-dinamico/submission/${submissionId}/quick-approve`;
    
    fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        status: newStatus,
        note: newStatus === 'approved' ? 'Approved by admin' : 'Denied by admin'
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Reload the page to show updated status
        window.location.reload();
      } else {
        alert('Failed to update submission: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
    });
  };

})(Drupal);
