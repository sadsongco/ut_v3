const heroVid = document.getElementById('heroVid');
const pageContainer = document.getElementById('pageContainer');
let vidHeight = 0;
let vidDuration = 0;

let set = false;
let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion)');

heroVid.addEventListener('loadedmetadata', (e) => {
  vidHeight = e.target.offsetHeight;
  vidDuration = e.target.duration;
});

const playVideo = () => {
  if (prefersReducedMotion.matches) return;
  let vidPos = window.scrollY / (vidHeight * 2);
  if (vidPos > 1) vidPos = 1;
  heroVid.currentTime = vidDuration * vidPos;
  window.requestAnimationFrame(playVideo);
};

window.addEventListener('scroll', playVideo);
