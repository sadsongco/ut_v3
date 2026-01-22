const heroVid = document.getElementById('heroVid');
const pageContainer = document.getElementById('pageContainer');
let vidHeight = 0;
let vidDuration = 0;

function isMobile() {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

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

if (!isMobile()) window.addEventListener('scroll', playVideo);
