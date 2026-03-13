/**
 * @file
 * Tutorial Help Page JavaScript
 */

(function () {
  'use strict';

  // State
  var activeChapter = null;
  var activeVideoIndex = null;
  var watchedVideos = {};
  var totalVideos = 0;

  // Load watched state from localStorage
  try {
    var saved = localStorage.getItem('drupal_tutorial_watched');
    if (saved) {
      watchedVideos = JSON.parse(saved);
    }
  } catch (e) {
    // ignore
  }

  // Count total videos
  document.querySelectorAll('.tutorial-help__video-item').forEach(function () {
    totalVideos++;
  });

  // Save watched state
  function saveWatched() {
    try {
      localStorage.setItem('drupal_tutorial_watched', JSON.stringify(watchedVideos));
    } catch (e) {
      // ignore
    }
  }

  // Mark a video as watched
  function markWatched(chapterId, index) {
    var key = chapterId + '_' + index;
    watchedVideos[key] = true;
    saveWatched();
    updateVideoItemState(chapterId, index);
    updateProgress();
  }

  // Update a single video item's visual state
  function updateVideoItemState(chapterId, index) {
    var key = chapterId + '_' + index;
    var item = document.getElementById('video-item-' + chapterId + '-' + index);
    var icon = document.getElementById('video-icon-' + chapterId + '-' + index);
    if (!item || !icon) return;

    if (watchedVideos[key] && !(activeChapter === chapterId && activeVideoIndex === index)) {
      item.classList.add('tutorial-help__video-item--watched');
      icon.innerHTML = '<span class="material-icons">check_circle</span>';
    }
  }

  // Update all progress indicators
  function updateProgress() {
    var watchedCount = Object.keys(watchedVideos).length;
    var percent = totalVideos > 0 ? Math.round((watchedCount / totalVideos) * 100) : 0;

    var progressText = document.getElementById('progress-text');
    var progressFill = document.getElementById('progress-fill');
    var progressPercent = document.getElementById('progress-percent');

    if (progressText) progressText.textContent = watchedCount + '/' + totalVideos + ' vídeos';
    if (progressFill) progressFill.style.width = percent + '%';
    if (progressPercent) progressPercent.textContent = percent + '%';

    // Update per-chapter progress
    document.querySelectorAll('.tutorial-help__chapter').forEach(function (chapter) {
      var chapterId = chapter.getAttribute('data-chapter-id');
      var items = chapter.querySelectorAll('.tutorial-help__video-item');
      var chapterWatched = 0;

      items.forEach(function (item) {
        var key = item.getAttribute('data-chapter') + '_' + item.getAttribute('data-index');
        if (watchedVideos[key]) chapterWatched++;
      });

      var chapterFill = document.getElementById('chapter-progress-' + chapterId);
      var chapterText = document.getElementById('chapter-progress-text-' + chapterId);

      if (chapterFill) {
        chapterFill.style.width = (items.length > 0 ? (chapterWatched / items.length) * 100 : 0) + '%';
      }
      if (chapterText) {
        chapterText.textContent = chapterWatched + '/' + items.length;
      }
    });
  }

  // Toggle chapter expand/collapse
  window.tutorialToggleChapter = function (chapterId) {
    var list = document.getElementById('chapter-list-' + chapterId);
    var chapter = list ? list.closest('.tutorial-help__chapter') : null;

    if (list) {
      if (list.style.display === 'none') {
        list.style.display = 'block';
        if (chapter) chapter.classList.add('tutorial-help__chapter--expanded');
      } else {
        list.style.display = 'none';
        if (chapter) chapter.classList.remove('tutorial-help__chapter--expanded');
      }
    }
  };

  // Select and play a video
  window.tutorialSelectVideo = function (chapterId, index) {
    var item = document.getElementById('video-item-' + chapterId + '-' + index);
    if (!item) return;

    var url = item.getAttribute('data-url');
    var title = item.getAttribute('data-title');
    var description = item.getAttribute('data-description');
    var chapterTitle = item.getAttribute('data-chapter-title');
    var total = parseInt(item.getAttribute('data-total'), 10);

    // Remove previous active states
    document.querySelectorAll('.tutorial-help__video-item--active').forEach(function (el) {
      el.classList.remove('tutorial-help__video-item--active');
      // Restore watched icon if applicable
      var ch = el.getAttribute('data-chapter');
      var idx = el.getAttribute('data-index');
      var iconEl = document.getElementById('video-icon-' + ch + '-' + idx);
      if (watchedVideos[ch + '_' + idx] && iconEl) {
        iconEl.innerHTML = '<span class="material-icons">check_circle</span>';
        el.classList.add('tutorial-help__video-item--watched');
      } else if (iconEl) {
        iconEl.innerHTML = '<span class="material-icons">play_arrow</span>';
      }
    });

    // Set active
    activeChapter = chapterId;
    activeVideoIndex = index;
    item.classList.add('tutorial-help__video-item--active');
    item.classList.remove('tutorial-help__video-item--watched');

    var icon = document.getElementById('video-icon-' + chapterId + '-' + index);
    if (icon) icon.innerHTML = '<span class="material-icons">play_arrow</span>';

    // Update video player
    var video = document.getElementById('tutorial-video');
    var placeholder = document.getElementById('video-placeholder');
    var infoPanel = document.getElementById('video-info');

    if (placeholder) placeholder.style.display = 'none';
    if (video) {
      video.style.display = 'block';
      video.src = url;
      video.load();
      video.play().catch(function () { /* autoplay may be blocked */ });
    }
    if (infoPanel) infoPanel.style.display = 'block';

    // Update info
    var chapterLabel = document.getElementById('video-chapter-label');
    var numberLabel = document.getElementById('video-number-label');
    var titleEl = document.getElementById('video-title');
    var descEl = document.getElementById('video-description');

    if (chapterLabel) chapterLabel.textContent = chapterTitle;
    if (numberLabel) numberLabel.textContent = 'Vídeo ' + (index + 1) + ' de ' + total;
    if (titleEl) titleEl.textContent = title;
    if (descEl) descEl.textContent = description;

    // Mark as watched on play
    markWatched(chapterId, index);

    // Expand the chapter if collapsed
    var list = document.getElementById('chapter-list-' + chapterId);
    var chapter = list ? list.closest('.tutorial-help__chapter') : null;
    if (list && list.style.display === 'none') {
      list.style.display = 'block';
      if (chapter) chapter.classList.add('tutorial-help__chapter--expanded');
    }
  };

  // Navigate prev/next
  window.tutorialNavigate = function (direction) {
    if (activeChapter === null) return;

    var chapters = [];
    document.querySelectorAll('.tutorial-help__chapter').forEach(function (el) {
      var chId = el.getAttribute('data-chapter-id');
      var items = el.querySelectorAll('.tutorial-help__video-item');
      chapters.push({ id: chId, count: items.length });
    });

    var chapterIdx = -1;
    for (var i = 0; i < chapters.length; i++) {
      if (chapters[i].id === activeChapter) {
        chapterIdx = i;
        break;
      }
    }
    if (chapterIdx === -1) return;

    if (direction === 'next') {
      if (activeVideoIndex < chapters[chapterIdx].count - 1) {
        window.tutorialSelectVideo(activeChapter, activeVideoIndex + 1);
      } else if (chapterIdx < chapters.length - 1) {
        window.tutorialSelectVideo(chapters[chapterIdx + 1].id, 0);
      }
    } else {
      if (activeVideoIndex > 0) {
        window.tutorialSelectVideo(activeChapter, activeVideoIndex - 1);
      } else if (chapterIdx > 0) {
        var prevChapter = chapters[chapterIdx - 1];
        window.tutorialSelectVideo(prevChapter.id, prevChapter.count - 1);
      }
    }
  };

  // Set up video ended handler for auto-advance
  var videoEl = document.getElementById('tutorial-video');
  if (videoEl) {
    videoEl.addEventListener('ended', function () {
      window.tutorialNavigate('next');
    });
  }

  // Initialize: restore watched states and update progress
  document.querySelectorAll('.tutorial-help__video-item').forEach(function (item) {
    var ch = item.getAttribute('data-chapter');
    var idx = parseInt(item.getAttribute('data-index'), 10);
    updateVideoItemState(ch, idx);
  });
  updateProgress();

  // Auto-expand first chapter
  var firstChapter = document.querySelector('.tutorial-help__chapter');
  if (firstChapter) {
    var firstChapterId = firstChapter.getAttribute('data-chapter-id');
    window.tutorialToggleChapter(firstChapterId);
  }

})();
