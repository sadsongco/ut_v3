let slides;
let interval;
let transitionTime = window.getComputedStyle(document.body).getPropertyValue('--stdTransitionTime');
transitionTime = transitionTime.substring(0, transitionTime.length - 2);
const carouselWaitTime = 7500;

const startCarousel = () => {
  const container = document.getElementById('carousel');
  slides = document.getElementsByClassName('carousel-item');
  if (slides.length == 1) {
    const firstSlide = slides[0];
    firstSlide.classList.add('active');
    firstSlide.style.transition = 'none';
    firstSlide.style.left = '0';
    firstSlide.style.opacity = '1';
    firstSlide.style.zIndex = '2';
    container.style.minHeight = `${firstSlide.scrollHeight}px`;
    return;
  }

  function addActive(slide) {
    slide.classList.add('active');
  }

  function removeActive(slide) {
    slide.classList.remove('active');
  }

  let containerHeight = 0;
  for (let slide of slides) {
    removeActive(slide);
    if (slide.scrollHeight > containerHeight) {
      containerHeight = `${slide.scrollHeight}px`;
    }
  }
  container.style.minHeight = containerHeight;
  addActive(slides[0]);

  interval = setInterval(function () {
    for (let slide of slides) {
      if (slide.classList.contains('active')) {
        slide.style.transition = 'opacity var(--stdTransitionTime) ease-in-out';
        slide.style.zIndex = '-1';
        slide.style.opacity = '0';
        continue;
      }
      slide.style.zIndex = '';
      slide.style.opacity = '';
      slide.style.transition = '';
    }
    for (let i = 0; i < slides.length; i++) {
      if (i + 1 == slides.length) {
        setTimeout(removeActive, transitionTime, slides[i]);
        addActive(slides[0]);
        break;
      }
      if (slides[i].classList.contains('active')) {
        setTimeout(removeActive, transitionTime, slides[i]);
        addActive(slides[i + 1]);
        break;
      }
    }
  }, carouselWaitTime);
};

const stopCarousel = () => {
  clearInterval(interval);
  for (let slide of slides) {
    slide.classList.remove('active');
    slide.style.zIndex = '';
    slide.style.opacity = '';
    slide.style.transition = '';
  }
  slides[0].classList.add('active');
};

window.onload = startCarousel;
