/**
 * Ricardo Professional Frontend Theme - JavaScript Enhancements
 */

(function () {
  'use strict';

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    addScrollToTop();
    enhanceTables();
    addFormValidationFeedback();
    addSmoothScrolling();
    enhanceMessages();
  }

  // Scroll to Top Button
  function addScrollToTop() {
    const button = document.createElement('button');
    button.innerHTML = '↑';
    button.className = 'scroll-to-top';
    button.setAttribute('aria-label', 'Scroll to top');
    button.style.cssText = `
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
      border: none;
      border-radius: 50%;
      font-size: 24px;
      cursor: pointer;
      display: none;
      z-index: 1000;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
      transition: all 0.3s ease;
    `;

    document.body.appendChild(button);

    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
      if (window.scrollY > 400) {
        button.style.display = 'block';
      } else {
        button.style.display = 'none';
      }
    });

    // Scroll to top on click
    button.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // Hover effects
    button.addEventListener('mouseenter', function() {
      button.style.transform = 'scale(1.1) translateY(-3px)';
      button.style.boxShadow = '0 6px 16px rgba(59, 130, 246, 0.5)';
    });

    button.addEventListener('mouseleave', function() {
      button.style.transform = 'scale(1) translateY(0)';
      button.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.4)';
    });
  }

  // Enhance tables with better interactivity
  function enhanceTables() {
    const tables = document.querySelectorAll('table');
    tables.forEach(function(table) {
      // Make tables responsive
      if (!table.parentElement.classList.contains('table-wrapper')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'table-wrapper';
        wrapper.style.cssText = 'overflow-x: auto; margin-bottom: 20px;';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
      }

      // Add row highlighting
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(function(row) {
        row.style.cursor = 'pointer';
      });
    });
  }

  // Add visual feedback for form validation
  function addFormValidationFeedback() {
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
      form.addEventListener('submit', function(e) {
        const submitButton = form.querySelector('input[type="submit"], button[type="submit"]');
        if (submitButton && !submitButton.disabled) {
          submitButton.disabled = true;
          submitButton.style.opacity = '0.6';
          submitButton.style.cursor = 'not-allowed';
          
          const originalText = submitButton.value || submitButton.textContent;
          if (submitButton.tagName === 'INPUT') {
            submitButton.value = '⏳ Processing...';
          } else {
            submitButton.textContent = '⏳ Processing...';
          }

          // Re-enable after 30 seconds as fallback
          setTimeout(function() {
            submitButton.disabled = false;
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
            if (submitButton.tagName === 'INPUT') {
              submitButton.value = originalText;
            } else {
              submitButton.textContent = originalText;
            }
          }, 30000);
        }
      });

      // Real-time validation feedback
      const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
      inputs.forEach(function(input) {
        input.addEventListener('blur', function() {
          if (input.validity.valid) {
            input.style.borderColor = '#10b981';
          } else if (input.value) {
            input.style.borderColor = '#ef4444';
          }
        });

        input.addEventListener('input', function() {
          if (input.validity.valid && input.value) {
            input.style.borderColor = '#10b981';
          }
        });
      });
    });
  }

  // Smooth scrolling for anchor links
  function addSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(function(link) {
      link.addEventListener('click', function(e) {
        const href = link.getAttribute('href');
        if (href !== '#' && href.length > 1) {
          const target = document.querySelector(href);
          if (target) {
            e.preventDefault();
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        }
      });
    });
  }

  // Enhance messages with auto-dismiss
  function enhanceMessages() {
    const messages = document.querySelectorAll('.messages--status');
    messages.forEach(function(message) {
      // Add close button
      const closeBtn = document.createElement('button');
      closeBtn.innerHTML = '×';
      closeBtn.setAttribute('aria-label', 'Close message');
      closeBtn.style.cssText = `
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        color: inherit;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        line-height: 1;
        opacity: 0.7;
        transition: opacity 0.2s ease;
      `;

      message.style.position = 'relative';
      message.appendChild(closeBtn);

      closeBtn.addEventListener('mouseenter', function() {
        closeBtn.style.opacity = '1';
      });

      closeBtn.addEventListener('mouseleave', function() {
        closeBtn.style.opacity = '0.7';
      });

      closeBtn.addEventListener('click', function() {
        message.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(function() {
          message.remove();
        }, 300);
      });

      // Auto-dismiss after 10 seconds
      setTimeout(function() {
        if (message.parentElement) {
          message.style.animation = 'fadeOut 0.5s ease-out';
          setTimeout(function() {
            if (message.parentElement) {
              message.remove();
            }
          }, 500);
        }
      }, 10000);
    });
  }

  // Add fade out animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeOut {
      from {
        opacity: 1;
        transform: translateY(0);
      }
      to {
        opacity: 0;
        transform: translateY(-20px);
      }
    }
  `;
  document.head.appendChild(style);

})();
