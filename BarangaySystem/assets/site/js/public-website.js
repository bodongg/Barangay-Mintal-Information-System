// PUBLIC WEBSITE SCRIPTS (FOR DEFENSE)
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
  }
  function closeBg(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => {
        m.classList.remove('open');
        document.body.style.overflow = '';
      });
    }
  });

function openImagePreview(src, title) {
    var img = document.getElementById('imagePreviewElement');
    var caption = document.getElementById('imagePreviewTitle');
    if (img) img.src = src;
    if (caption) caption.textContent = title || 'Template Preview';
    openModal('imagePreviewModal');
  }

  document.addEventListener('DOMContentLoaded', function() {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }

    var revealGroups = [
      { selector: '.hero-badge, .hero h1, .hero-sub', effect: 'reveal-soft', stagger: 90 },
      { selector: '.stats-strip .stat-item', effect: 'reveal-soft', stagger: 70 },
      { selector: '#services .section-tag, #services .section-title, #services .section-sub', effect: 'reveal-soft', stagger: 90 },
      { selector: '#services .service-card', effect: 'reveal-zoom', stagger: 90 },
      { selector: '#agenda .section-tag, #agenda .section-title', effect: 'reveal-soft', stagger: 80 },
      { selector: '#agenda .agenda-item', effect: 'reveal-soft', stagger: 55 },
      { selector: '.offices-text > *', effect: 'reveal-left', stagger: 80 },
      { selector: '#news .section-title', effect: 'reveal-soft', stagger: 0 },
      { selector: '#news .news-card', effect: 'reveal-zoom', stagger: 85 },
      { selector: '#announcements .section-tag, #announcements .section-title, #announcements .section-sub', effect: 'reveal-soft', stagger: 90 },
      { selector: '#announcements .ann-card', effect: 'reveal-soft', stagger: 90 },
      { selector: '#officials .section-tag, #officials .section-title, #officials .section-sub', effect: 'reveal-soft', stagger: 90 },
      { selector: '#officials .official-card', effect: 'reveal-zoom', stagger: 90 },
      { selector: '#contact .section-tag, #contact .section-title, #contact .section-sub', effect: 'reveal-soft', stagger: 90 },
      { selector: '#contact .contact-info', effect: 'reveal-left', stagger: 0 },
      { selector: '#contact .map-box', effect: 'reveal-right', stagger: 0 },
      { selector: '.footer-col, .footer-bottom', effect: 'reveal-soft', stagger: 80 }
    ];

    var revealItems = [];

    revealGroups.forEach(function(group) {
      var nodes = document.querySelectorAll(group.selector);
      nodes.forEach(function(node, index) {
        node.classList.add('reveal-on-scroll');
        if (group.effect) {
          node.classList.add(group.effect);
        }
        node.style.transitionDelay = ((group.stagger || 0) * index) + 'ms';
        revealItems.push(node);
      });
    });

    if (!('IntersectionObserver' in window)) {
      revealItems.forEach(function(node) {
        node.classList.add('reveal-visible');
      });
      return;
    }

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) {
          return;
        }
        entry.target.classList.add('reveal-visible');
        observer.unobserve(entry.target);
      });
    }, {
      threshold: 0.14,
      rootMargin: '0px 0px -8% 0px'
    });

    revealItems.forEach(function(node) {
      observer.observe(node);
    });
  });

