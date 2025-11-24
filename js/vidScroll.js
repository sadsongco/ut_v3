const heroVid = document.getElementById('heroVid');
const pageContainer = document.getElementById('pageContainer');
let vidHeight = 0;
let vidDuration = 0;

let set = false;

heroVid.addEventListener('loadedmetadata', (e) => {
  vidHeight = e.target.offsetHeight;
  vidDuration = e.target.duration;
});

const playVideo = () => {
  let vidPos = window.scrollY / (vidHeight * 2);
  if (vidPos > 1) vidPos = 1;
  heroVid.currentTime = vidDuration * vidPos;
  window.requestAnimationFrame(playVideo);
};

window.addEventListener('scroll', playVideo);
