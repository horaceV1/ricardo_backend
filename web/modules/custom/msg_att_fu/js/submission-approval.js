/**
 * Submission Approval JavaScript
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.submissionApproval = {
    attach: function (context, settings) {
      // Add visual feedback for radio button selection
      const radios = context.querySelectorAll('.approval-form-section input[type="radio"]');
      
      radios.forEach(function(radio) {
        if (radio.dataset.initialized) {
          return;
        }
        radio.dataset.initialized = 'true';
        
        radio.addEventListener('change', function() {
          // Remove selected class from all labels
          const allLabels = context.querySelectorAll('.approval-form-section .form-radios label');
          allLabels.forEach(function(label) {
            label.classList.remove('selected');
          });
          
          // Add selected class to current label
          if (this.checked) {
            const label = this.nextElementSibling;
            if (label && label.tagName === 'LABEL') {
              label.classList.add('selected');
            }
          }
        });
      });
      
      // Confirm before submitting denial
      const form = context.querySelector('.approval-form-section form');
      if (form && !form.dataset.initialized) {
        form.dataset.initialized = 'true';
        
        form.addEventListener('submit', function(e) {
          const statusRadio = form.querySelector('input[name="approval_status"]:checked');
          if (statusRadio && statusRadio.value === 'denied') {
            const note = form.querySelector('textarea[name="approval_note"]');
            if (!note || !note.value.trim()) {
              e.preventDefault();
              alert('Please add a note explaining the reason for denial.');
              if (note) {
                note.focus();
              }
              return false;
            }
            
            if (!confirm('Are you sure you want to deny this submission?')) {
              e.preventDefault();
              return false;
            }
          }
        });
      }
    }
  };

})(Drupal);
