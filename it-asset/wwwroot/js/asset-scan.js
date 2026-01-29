// wwwroot/js/asset-scan.js
(async () => {
  const video = document.getElementById("video");
  const output = document.getElementById("output");
  const startBtn = document.getElementById("startBtn");

  let stream;
  let scanning = false;

  startBtn.addEventListener("click", async () => {
    if (scanning) {
      stream.getTracks().forEach(t => t.stop());
      scanning = false;
      startBtn.textContent = "Start Camera";
      output.textContent = "Stopped";
      return;
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
      video.srcObject = stream;
      scanning = true;
      startBtn.textContent = "Stop Camera";
      output.textContent = "Scanning...";
      tick();
    } catch (err) {
      output.textContent = "Cannot access camera: " + err;
    }
  });

  function tick() {
    if (!scanning) return;
    if (video.readyState !== video.HAVE_ENOUGH_DATA) {
      requestAnimationFrame(tick);
      return;
    }
    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth || 320;
    canvas.height = video.videoHeight || 240;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, imageData.width, imageData.height);
    if (code) {
      scanning = false;
      stream.getTracks().forEach(t => t.stop());
      output.textContent = "QR detected: " + code.data;
      try {
        const u = new URL(code.data);
        window.location.href = u.href;
      } catch {
        const text = code.data;
        const match = text.match(/\/\?p=asset_details&id=(\d+)/i) || text.match(/\/Assets\/Details\/(\d+)/i);
        if (match) {
          let href = match[0];
          if (!href.startsWith("http")) href = window.location.origin + match[0];
          window.location.href = href;
        } else {
          alert("QR content: " + text);
        }
      }
      return;
    }
    requestAnimationFrame(tick);
  }
})();
