(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.artigosRelacionadosCarousel = {
    attach: function (context, settings) {
      var carousels = once('artigos-relacionados-carousel', '.artigos-relacionados__carousel', context);
      
      carousels.forEach(function (carouselElement) {
        var $carousel = $(carouselElement);
        var $track = $carousel.find('.carousel__track');
        var $slides = $track.find('.carousel__slide');
        var $prevBtn = $carousel.find('.carousel__nav--prev');
        var $nextBtn = $carousel.find('.carousel__nav--next');
        var $dotsContainer = $carousel.parent().find('.carousel__dots');
        
        var currentIndex = 0;
        var slidesToShow = 3;
        var totalSlides = $slides.length;
        
        // Calculate slides to show based on screen size
        function updateSlidesToShow() {
          if (window.innerWidth <= 576) {
            slidesToShow = 1;
          } else if (window.innerWidth <= 992) {
            slidesToShow = 2;
          } else {
            slidesToShow = 3;
          }
        }
        
        // Create dots
        function createDots() {
          $dotsContainer.empty();
          var totalPages = Math.ceil(totalSlides / slidesToShow);
          
          for (var i = 0; i < totalPages; i++) {
            var $dot = $('<button class="carousel__dot"></button>');
            $dot.attr('aria-label', 'Go to slide ' + (i + 1));
            $dot.data('index', i);
            $dotsContainer.append($dot);
          }
          
          updateDots();
        }
        
        // Update dots active state
        function updateDots() {
          var currentPage = Math.floor(currentIndex / slidesToShow);
          $dotsContainer.find('.carousel__dot').removeClass('active');
          $dotsContainer.find('.carousel__dot').eq(currentPage).addClass('active');
        }
        
        // Update carousel position
        function updateCarousel() {
          var slideWidth = $slides.first().outerWidth(true);
          var offset = -currentIndex * slideWidth;
          $track.css('transform', 'translateX(' + offset + 'px)');
          
          // Update buttons state
          $prevBtn.prop('disabled', currentIndex === 0);
          $nextBtn.prop('disabled', currentIndex >= totalSlides - slidesToShow);
          
          updateDots();
        }
        
        // Navigate to next slide
        function nextSlide() {
          if (currentIndex < totalSlides - slidesToShow) {
            currentIndex++;
            updateCarousel();
          }
        }
        
        // Navigate to previous slide
        function prevSlide() {
          if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
          }
        }
        
        // Go to specific page
        function goToPage(pageIndex) {
          currentIndex = pageIndex * slidesToShow;
          if (currentIndex > totalSlides - slidesToShow) {
            currentIndex = totalSlides - slidesToShow;
          }
          updateCarousel();
        }
        
        // Event listeners
        $nextBtn.on('click', nextSlide);
        $prevBtn.on('click', prevSlide);
        
        $dotsContainer.on('click', '.carousel__dot', function() {
          var pageIndex = $(this).data('index');
          goToPage(pageIndex);
        });
        
        // Handle window resize
        var resizeTimeout;
        $(window).on('resize', function() {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(function() {
            updateSlidesToShow();
            currentIndex = 0;
            createDots();
            updateCarousel();
          }, 250);
        });
        
        // Initialize
        updateSlidesToShow();
        createDots();
        updateCarousel();
        
        // Keyboard navigation
        $carousel.on('keydown', function(e) {
          if (e.key === 'ArrowLeft') {
            prevSlide();
          } else if (e.key === 'ArrowRight') {
            nextSlide();
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);
