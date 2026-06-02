(function () {
  var canvas = document.querySelector('.js-grain');
  var ctx = canvas.getContext('2d');
  var frames = [];
  var frameCount = 6;
  var w, h;
  var tick = 0;
  var currentFrame = 0;
  var resizeTimer;

  function buildFrames() {
    w = Math.round(window.innerWidth / 3);
    h = Math.round(window.innerHeight / 3);
    canvas.width = w;
    canvas.height = h;
    frames = [];
    for (var f = 0; f < frameCount; f++) {
      var imageData = ctx.createImageData(w, h);
      var data = imageData.data;
      for (var i = 0; i < data.length; i += 4) {
        var v = Math.random() * 255;
        data[i] = v;
        data[i + 1] = v;
        data[i + 2] = v;
        data[i + 3] = 255;
      }
      frames.push(imageData);
    }
  }

  function loop() {
    tick++;
    if (tick % 3 === 0) {
      ctx.putImageData(frames[currentFrame], 0, 0);
      currentFrame = (currentFrame + 1) % frameCount;
    }
    requestAnimationFrame(loop);
  }

  function onResize() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(buildFrames, 200);
  }

  buildFrames();
  loop();
  window.addEventListener('resize', onResize);
})();

/*
<div class="grain" aria-hidden="true">
    <canvas class="js-grain" width="480" height="172"></canvas>
  </div>
  .grain {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    pointer-events: none;
    opacity: 0.03;
}
*/
