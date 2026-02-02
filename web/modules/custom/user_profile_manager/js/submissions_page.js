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

})(Drupal);
