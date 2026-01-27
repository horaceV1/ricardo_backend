(function (Drupal) {
  'use strict';

  Drupal.behaviors.rgpdModal = {
    attach: function (context, settings) {
      // Get modal elements
      var modal = document.getElementById('rgpd-modal');
      var btns = document.querySelectorAll('.open-rgpd-modal');
      var span = document.querySelector('.rgpd-modal-close');

      if (!modal || !btns.length || !span) {
        return;
      }

      // Open modal when clicking any link with class open-rgpd-modal
      btns.forEach(function(btn) {
        btn.onclick = function(e) {
          e.preventDefault();
          modal.style.display = 'block';
        };
      });

      // Close modal when clicking the X
      span.onclick = function() {
        modal.style.display = 'none';
      };

      // Close modal when clicking outside
      window.onclick = function(event) {
        if (event.target == modal) {
          modal.style.display = 'none';
        }
      };
    }
  };

})(Drupal);
