/**
 * @file
 * The hero slider: Pause, Previous, Next and the dots, and the slides
 * changing on their own.
 *
 * Without this script, the slides are a row that scrolls sideways, and that
 * still works. The script only adds to it:
 * - it labels each slide ("1 of 3") and adds a dot for each, as buttons;
 * - it moves to the next slide every few seconds (7 by default), but never
 *   while the mouse is over the slider or the focus is in it, never after
 *   the visitor has pressed Pause, and never with reduced motion;
 * - it makes the slides you can't see inert, so Tab skips their links.
 * The row still scrolls under a finger or a trackpad, and the script
 * follows it: a scroll is a change of slide like any other.
 */
(function (Drupal, once) {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function initSlider(slider) {
    const track = slider.querySelector('.hero-slider__slides');
    const slides = [...track.children];
    const controls = slider.querySelector('.hero-slider__controls');
    // One slide is a hero, not a slider.
    if (slides.length < 2) {
      return;
    }
    const playButton = controls.querySelector('.hero-slider__play');
    const dotsBox = controls.querySelector('.hero-slider__dots');
    const interval = Math.max(7, Number(slider.dataset.interval) || 7) * 1000;
    const label = (text, number) => text.replace('@number', number).replace('@total', slides.length);

    let current = 0;
    let timer = null;
    // Stopped: the visitor pressed Pause, or asked their system for less
    // movement. Held: for a moment, while the mouse or the focus is here.
    let stopped = reducedMotion.matches;
    let hovered = false;
    let focused = false;

    slides.forEach((slide, index) => {
      slide.setAttribute('role', 'group');
      slide.setAttribute('aria-roledescription', slider.dataset.slide);
      slide.setAttribute('aria-label', label(slider.dataset.slideLabel, index + 1));
    });
    const dots = slides.map((slide, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'hero-slider__dot';
      dot.setAttribute('aria-label', label(dotsBox.dataset.label, index + 1));
      dot.addEventListener('click', () => show(index));
      dotsBox.append(dot);
      return dot;
    });

    // The next slide in a few seconds, unless something says not now.
    function schedule() {
      clearTimeout(timer);
      if (!stopped && !hovered && !focused) {
        timer = setTimeout(() => show(current + 1), interval);
      }
    }

    // Scrolls to a slide: smoothly, or at once with reduced motion.
    function show(index) {
      const target = (index + slides.length) % slides.length;
      track.scrollTo({
        left: target * track.clientWidth,
        behavior: reducedMotion.matches ? 'instant' : 'smooth',
      });
      setCurrent(target);
    }

    // Marks a slide as the current one: its dot, and the others inert.
    function setCurrent(index) {
      current = index;
      slides.forEach((slide, i) => {
        slide.inert = i !== index;
      });
      dots.forEach((dot, i) => {
        if (i === index) {
          dot.setAttribute('aria-current', 'true');
        }
        else {
          dot.removeAttribute('aria-current');
        }
      });
      schedule();
    }

    function setStopped(value) {
      stopped = value;
      playButton.dataset.state = stopped ? 'stopped' : 'playing';
      playButton.querySelector('.visually-hidden').textContent = stopped ? playButton.dataset.play : playButton.dataset.pause;
      // A screen reader announces a new slide only when it's the visitor's
      // doing, not every few seconds.
      track.setAttribute('aria-live', stopped ? 'polite' : 'off');
      schedule();
    }

    playButton.addEventListener('click', () => setStopped(!stopped));
    controls.querySelector('.hero-slider__previous').addEventListener('click', () => show(current - 1));
    controls.querySelector('.hero-slider__next').addEventListener('click', () => show(current + 1));

    slider.addEventListener('mouseenter', () => {
      hovered = true;
      schedule();
    });
    slider.addEventListener('mouseleave', () => {
      hovered = false;
      schedule();
    });
    slider.addEventListener('focusin', () => {
      focused = true;
      schedule();
    });
    slider.addEventListener('focusout', (event) => {
      if (!slider.contains(event.relatedTarget)) {
        focused = false;
        schedule();
      }
    });

    // A swipe or a trackpad scroll changes the slide too. Once the row stops
    // moving, the slide that's in view is the current one.
    let settle = null;
    track.addEventListener('scroll', () => {
      clearTimeout(settle);
      settle = setTimeout(() => {
        const index = Math.round(track.scrollLeft / track.clientWidth);
        if (index !== current) {
          setCurrent(index);
        }
      }, 100);
    }, { passive: true });

    // Reduced motion turned on while the page is open: stop.
    reducedMotion.addEventListener('change', () => {
      if (reducedMotion.matches) {
        setStopped(true);
      }
    });

    controls.hidden = false;
    setCurrent(0);
    setStopped(stopped);
  }

  Drupal.behaviors.lensTrailHeroSlider = {
    attach(context) {
      once('hero-slider', '.hero-slider', context).forEach(initSlider);
    },
  };
})(Drupal, once);
