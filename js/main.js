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

    video.addEventListener('loadeddata', function () {
      video.currentTime = 0.001; // Força a renderização do primeiro frame
    });

    if (video.readyState >= 1) {
      videoDuration = video.duration;
      video.currentTime = 0.001;
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

    const block1 = document.querySelector('.hero-video__block--1');
    const block2 = document.querySelector('.hero-video__block--2');
    const overlay = document.querySelector('.hero-video__overlay');

    // Controle da Animação do Bloco 1 (some nos primeiros 30%)
    if (block1) {
      const fadeEnd1 = 0.30;
      if (progress <= fadeEnd1) {
        const p1 = progress / fadeEnd1;
        const opacity = 1 - p1;
        block1.style.opacity = opacity.toFixed(3);
        block1.style.transform = `translateY(${-p1 * 50}px)`;
        block1.style.pointerEvents = opacity > 0.15 ? 'auto' : 'none';
      } else {
        block1.style.opacity = '0';
        block1.style.transform = 'translateY(-50px)';
        block1.style.pointerEvents = 'none';
      }
    }

    // Controle da Animação do Bloco 2 (surge a partir de 30%, permanece e some no final do scroll)
    if (block2) {
      const startFade2 = 0.30;
      const endFade2 = 0.50;
      const exitStart2 = 0.93;

      if (progress < startFade2) {
        // Antes de surgir
        block2.style.opacity = '0';
        block2.style.transform = 'translateY(50px)';
        block2.style.pointerEvents = 'none';
      } else if (progress >= startFade2 && progress <= endFade2) {
        // Surgindo (fade-in + slide-up)
        const p2 = (progress - startFade2) / (endFade2 - startFade2);
        block2.style.opacity = p2.toFixed(3);
        block2.style.transform = `translateY(${50 * (1 - p2)}px)`;
        block2.style.pointerEvents = p2 > 0.15 ? 'auto' : 'none';
      } else if (progress > endFade2 && progress <= exitStart2) {
        // Estável (permanece fixo)
        block2.style.opacity = '1';
        block2.style.transform = 'translateY(0)';
        block2.style.pointerEvents = 'auto';
      } else {
        // Sumindo no final (ao sair da seção sticky)
        const p3 = (progress - exitStart2) / (1 - exitStart2);
        const opacity = 1 - p3;
        block2.style.opacity = opacity.toFixed(3);
        block2.style.transform = `translateY(${-p3 * 50}px)`;
        block2.style.pointerEvents = opacity > 0.15 ? 'auto' : 'none';
      }
    }

    // Controle da Opacidade do Overlay Branco (aparece apenas para ler o Texto 2)
    if (overlay) {
      const startFade2 = 0.30;
      const endFade2 = 0.50;
      const exitStart2 = 0.93;
      const maxOverlayOpacity = 0.28; // Muito suave, mantendo o vídeo 100% visível por trás

      if (progress < startFade2) {
        overlay.style.opacity = '0';
      } else if (progress >= startFade2 && progress <= endFade2) {
        const p2 = (progress - startFade2) / (endFade2 - startFade2);
        overlay.style.opacity = (p2 * maxOverlayOpacity).toFixed(3);
      } else if (progress > endFade2 && progress <= exitStart2) {
        overlay.style.opacity = maxOverlayOpacity.toString();
      } else {
        const p3 = (progress - exitStart2) / (1 - exitStart2);
        const op = (1 - p3) * maxOverlayOpacity;
        overlay.style.opacity = Math.max(0, op).toFixed(3);
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

  /* ── 3.1 OBRAS HORIZONTAL SCROLL SLIDER ─────────────────── */
  const obrasContainer = document.querySelector('.obras-scroll-container');
  const obrasSticky = document.querySelector('.obras-sticky');
  const obrasTrack = document.querySelector('.obras-track');
  const slides = document.querySelectorAll('.obra-slide');
  const dotIndicators = document.querySelectorAll('.obras-nav__dot');
  const navNumStart = document.getElementById('obras-nav-num-start');
  const obrasHeader = document.querySelector('.obras-sticky__header');

  let targetX = 0;
  let currentX = 0;
  const obrasLerpFactor = 0.08; // Interpolação suave para a track horizontal
  let isDesktop = window.innerWidth >= 992;

  function handleResize() {
    isDesktop = window.innerWidth >= 992;
    if (!isDesktop && obrasTrack) {
      // Limpa transformações e garante visibilidade no mobile
      obrasTrack.style.transform = 'none';
      slides.forEach(function (slide) {
        slide.classList.add('active');
      });
    }
  }

  function handleObrasScroll() {
    if (!obrasContainer || !obrasTrack || !isDesktop) return;

    const rect = obrasContainer.getBoundingClientRect();
    const containerHeight = rect.height;
    const viewportHeight = window.innerHeight;

    // Distância de scroll percorrida dentro do contêiner
    const scrolled = -rect.top;
    const maxScroll = containerHeight - viewportHeight;

    let progress = scrolled / maxScroll;
    progress = Math.max(0, Math.min(1, progress));

    // Translação máxima de 300vw para 4 slides (0vw a -300vw)
    const maxTranslation = 300;
    targetX = -progress * maxTranslation;

    // Determina o índice do slide ativo
    const numSlides = 4;
    const activeIndex = Math.min(
      numSlides - 1,
      Math.floor(progress * numSlides)
    );

    // Ativa/Desativa classes nos slides para animar os cards
    slides.forEach(function (slide, idx) {
      if (idx === activeIndex) {
        slide.classList.add('active');
      } else {
        slide.classList.remove('active');
      }
    });

    // Atualiza os dots de navegação
    if (dotIndicators.length > 0) {
      dotIndicators.forEach(function (dot, idx) {
        if (idx === activeIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    }

    if (navNumStart) {
      navNumStart.textContent = '0' + (activeIndex + 1);
    }

    // Fade-out sutil do header fixo "Nossas Obras" após o primeiro slide
    if (obrasHeader) {
      if (progress > 0.22) {
        obrasHeader.style.opacity = '0';
        obrasHeader.style.pointerEvents = 'none';
      } else {
        const headerOpacity = 1 - (progress / 0.22);
        obrasHeader.style.opacity = headerOpacity.toFixed(3);
        obrasHeader.style.pointerEvents = 'auto';
      }
    }
  }

  // Loop requestAnimationFrame para translação horizontal suave
  function smoothObrasLoop() {
    if (obrasTrack && isDesktop && obrasContainer) {
      currentX += (targetX - currentX) * obrasLerpFactor;

      // seek apenas se a diferença for relevante para otimizar desempenho
      if (Math.abs(targetX - currentX) > 0.01) {
        obrasTrack.style.transform = `translateX(${currentX.toFixed(2)}vw)`;
      }
    }
    requestAnimationFrame(smoothObrasLoop);
  }

  if (obrasContainer && obrasTrack) {
    window.addEventListener('scroll', handleObrasScroll, { passive: true });
    window.addEventListener('resize', function () {
      handleResize();
      handleObrasScroll();
    }, { passive: true });

    // Habilita navegação clicável nos dots
    if (dotIndicators.length > 0) {
      dotIndicators.forEach(function (dot) {
        dot.addEventListener('click', function () {
          const slideIdx = parseInt(this.getAttribute('data-slide'), 10);
          if (!isNaN(slideIdx) && obrasContainer) {
            const containerHeight = obrasContainer.getBoundingClientRect().height;
            const viewportHeight = window.innerHeight;
            const maxScroll = containerHeight - viewportHeight;
            
            // Mapeamento proporcional de scroll vertical
            const targetProgress = slideIdx / 3;
            const targetScrollTop = window.scrollY + obrasContainer.getBoundingClientRect().top + (targetProgress * maxScroll);
            
            window.scrollTo({
              top: targetScrollTop,
              behavior: 'smooth'
            });
          }
        });
      });
    }

    requestAnimationFrame(smoothObrasLoop);
    handleResize();
    handleObrasScroll();
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
