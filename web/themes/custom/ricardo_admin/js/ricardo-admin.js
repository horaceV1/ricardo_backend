/**
 * Ricardo Admin Theme - Enhanced Interactions
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ricardoAdminEnhancements = {
    attach: function (context, settings) {
      
      // Add smooth scroll to top button
      once('ricardo-scroll-top', 'body', context).forEach(function (element) {
        const scrollBtn = document.createElement('button');
        scrollBtn.id = 'ricardo-scroll-top';
        scrollBtn.innerHTML = '↑';
        scrollBtn.style.cssText = `
          position: fixed;
          bottom: 30px;
          right: 30px;
          width: 50px;
          height: 50px;
          background: var(--ricardo-primary);
          color: white;
          border: none;
          border-radius: 50%;
          cursor: pointer;
          display: none;
          z-index: 9999;
          font-size: 24px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
          transition: all 0.3s ease;
        `;
        
        document.body.appendChild(scrollBtn);
        
        // Show/hide button on scroll
        window.addEventListener('scroll', function() {
          if (window.scrollY > 300) {
            scrollBtn.style.display = 'block';
          } else {
            scrollBtn.style.display = 'none';
          }
        });
        
        // Smooth scroll to top
        scrollBtn.addEventListener('click', function() {
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
        
        // Hover effect
        scrollBtn.addEventListener('mouseenter', function() {
          scrollBtn.style.transform = 'translateY(-5px)';
          scrollBtn.style.boxShadow = '0 6px 12px rgba(59, 130, 246, 0.4)';
        });
        
        scrollBtn.addEventListener('mouseleave', function() {
          scrollBtn.style.transform = 'translateY(0)';
          scrollBtn.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.3)';
        });
      });

      // Enhanced table row highlighting
      once('ricardo-table-rows', 'table tbody tr', context).forEach(function (row) {
        row.style.transition = 'all 0.2s ease';
      });

      // Add loading animation to forms
      once('ricardo-form-submit', 'form', context).forEach(function (form) {
        form.addEventListener('submit', function(e) {
          const submitButtons = form.querySelectorAll('input[type="submit"], button[type="submit"]');
          submitButtons.forEach(function(btn) {
            if (!btn.classList.contains('button--danger')) {
              btn.innerHTML = '<span>⏳ Processing...</span>';
              btn.disabled = true;
            }
          });
        });
      });

      // Enhance dropbuttons with better UX
      once('ricardo-dropbutton', '.dropbutton-widget', context).forEach(function (element) {
        element.style.transition = 'all 0.2s ease';
      });

      // Add keyboard shortcuts info
      once('ricardo-keyboard-shortcuts', 'body', context).forEach(function (element) {
        document.addEventListener('keydown', function(e) {
          // Ctrl/Cmd + / to show shortcuts help
          if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            alert('Keyboard Shortcuts:\n\n' +
                  'Ctrl+/ - Show this help\n' +
                  'Ctrl+S - Save form (if applicable)\n' +
                  'Esc - Close dialogs');
          }
        });
      });

      // Smooth animations for messages
      once('ricardo-messages', '.messages', context).forEach(function (message) {
        message.style.animation = 'slideIn 0.3s ease-out';
      });
    }
  };

  // Add CSS for animations
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  `;
  document.head.appendChild(style);

})(Drupal, once);
