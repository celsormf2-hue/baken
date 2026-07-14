// ============================================================
// BAKEN CONSTRUTORA — Main JavaScript
// ============================================================

(function () {
  'use strict';

  /* ── 1. HEADER SCROLL BEHAVIOR ─────────────────────────── */
  const header = document.getElementById('site-header');

  function handleScroll() {
    if (!header) return;
    if (window.scrollY > 60) {
      header.classList.remove('transparent');
      header.classList.add('scrolled');
    } else {
      header.classList.add('transparent');
      header.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll(); // run once on load

  /* ── 2. MOBILE MENU ────────────────────────────────────── */
  const navToggle = document.getElementById('nav-toggle');
  const mobileNav = document.getElementById('mobile-nav');

  if (navToggle && mobileNav) {
    navToggle.addEventListener('click', function () {
      const isOpen = mobileNav.classList.toggle('open');
      navToggle.classList.toggle('open', isOpen);
      navToggle.setAttribute('aria-expanded', isOpen.toString());
    });

    // Close on link click
    mobileNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileNav.classList.remove('open');
        navToggle.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!navToggle.contains(e.target) && !mobileNav.contains(e.target)) {
        mobileNav.classList.remove('open');
        navToggle.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ── 3. HERO VIDEO SCROLL CONTROL ───────────────────────── */
  const scrollContainer = document.querySelector('.hero-scroll-container');
  const video = document.getElementById('hero-vid');
  const heroContent = document.querySelector('.hero-video__content');
  const scrollIndicator = document.querySelector('.hero-video__scroll');

  let videoDuration = 0;
  let targetTime = 0;
  let currentTime = 0;
  const lerpFactor = 0.08; // Suavização (lerp) do tempo do vídeo ao rolar

  if (video) {
    // Garante que o vídeo não comece tocando sozinho
    video.removeAttribute('autoplay');
    video.removeAttribute('loop');
    video.pause();

    video.addEventListener('loadedmetadata', function () {
      videoDuration = video.duration;
      handleVideoScroll();
    });

    if (video.readyState >= 1) {
      videoDuration = video.duration;
    }
  }

  function handleVideoScroll() {
    if (!scrollContainer || !video || !videoDuration) return;

    const rect = scrollContainer.getBoundingClientRect();
    const containerHeight = rect.height;
    const viewportHeight = window.innerHeight;

    // Distância percorrida desde o topo do contêiner
    const scrolled = -rect.top;
    const maxScroll = containerHeight - viewportHeight;

    let progress = scrolled / maxScroll;
    progress = Math.max(0, Math.min(1, progress));

    // Mapeia o progresso (0 a 1) para o tempo do vídeo (0 a duration)
    targetTime = progress * videoDuration;

    // Efeito de fade-out do conteúdo central da Hero (título, subtítulo, botões)
    if (heroContent) {
      const fadeEnd = 0.25; // Conclui o fade nos primeiros 25% de rolagem
      if (progress <= fadeEnd) {
        const opacity = 1 - (progress / fadeEnd);
        heroContent.style.opacity = opacity.toFixed(3);
        heroContent.style.transform = `translateY(${-progress * 60}px)`;
        heroContent.style.pointerEvents = 'auto';
      } else {
        heroContent.style.opacity = '0';
        heroContent.style.transform = 'translateY(-60px)';
        heroContent.style.pointerEvents = 'none';
      }
    }

    // Efeito de fade-out rápido do scroll indicator ("Explorar") nos primeiros 10%
    if (scrollIndicator) {
      const indicatorFadeEnd = 0.10;
      if (progress <= indicatorFadeEnd) {
        const opacity = 1 - (progress / indicatorFadeEnd);
        scrollIndicator.style.opacity = opacity.toFixed(3);
      } else {
        scrollIndicator.style.opacity = '0';
      }
    }
  }

  // Loop requestAnimationFrame para seeking interpolado e fluido
  function smoothSeekLoop() {
    if (video && videoDuration) {
      // Interpolação linear suave (lerp)
      currentTime += (targetTime - currentTime) * lerpFactor;
      currentTime = Math.max(0, Math.min(videoDuration, currentTime));

      // seek apenas se a diferença for relevante para otimizar desempenho
      if (Math.abs(targetTime - currentTime) > 0.005) {
        video.currentTime = currentTime;
      }
    }
    requestAnimationFrame(smoothSeekLoop);
  }

  if (scrollContainer && video) {
    window.addEventListener('scroll', handleVideoScroll, { passive: true });
    window.addEventListener('resize', handleVideoScroll, { passive: true });
    requestAnimationFrame(smoothSeekLoop);
    handleVideoScroll();
  }

  /* ── 4. HERO SCROLL CTA ────────────────────────────────── */
  const heroScroll = document.getElementById('hero-scroll');

  if (heroScroll) {
    heroScroll.addEventListener('click', function () {
      const target = document.getElementById('sobre') || document.getElementById('sobre-section');
      if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  }

  /* ── 5. FADE-IN ON SCROLL (Intersection Observer) ──────── */
  const fadeEls = document.querySelectorAll('.fade-up');

  if (fadeEls.length > 0) {
    const fadeObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            fadeObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );

    fadeEls.forEach(function (el) {
      fadeObserver.observe(el);
    });
  }

  /* ── 6. COUNT-UP ANIMATION (STATS) ─────────────────────── */
  const statNums = document.querySelectorAll('.stat__num[data-target]');

  if (statNums.length > 0) {
    const countObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCount(entry.target);
            countObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.5 }
    );

    statNums.forEach(function (el) {
      countObserver.observe(el);
    });
  }

  function animateCount(el) {
    const target = parseInt(el.getAttribute('data-target'), 10);
    const duration = 1800;
    const start = performance.now();
    const suffix = target >= 1000 ? '' : (target === 15 ? '' : '');

    function easeOut(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    function tick(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const value = Math.round(easeOut(progress) * target);

      // Format with locale-style separator
      el.textContent = (target >= 10000
        ? value.toLocaleString('pt-BR')
        : value) + (progress < 1 ? '' : (target >= 1000 ? '' : '+'));

      if (progress < 1) {
        requestAnimationFrame(tick);
      } else {
        // Final value with prefix
        if (target === 85000) {
          el.textContent = '85k';
        } else {
          el.textContent = '+' + target.toLocaleString('pt-BR');
        }
      }
    }

    requestAnimationFrame(tick);
  }

  /* ── 7. ACTIVE NAV LINK ─────────────────────────────────── */
  (function setActiveNav() {
    const path = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(function (link) {
      link.classList.remove('active');
      const href = link.getAttribute('href');
      if (
        (path === '/' && (href === '/' || href === '/index.html')) ||
        (path !== '/' && href !== '/' && path.includes(href.replace('.html', '')))
      ) {
        link.classList.add('active');
      }
    });
  })();

  /* ── 8. SMOOTH ANCHOR LINKS ─────────────────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const id = this.getAttribute('href').slice(1);
      const target = document.getElementById(id);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

})();
