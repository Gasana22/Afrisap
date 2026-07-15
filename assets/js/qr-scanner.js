/**
 * Camera-based QR scanning for org/{slug}/qr.php, using jsQR (loaded from
 * CDN on demand so pages that only need the manual "enter code" fallback
 * never pay for it). Falls back gracefully if the camera is unavailable.
 */
let sfpJsQrLoaded = null;

function loadJsQR() {
    if (sfpJsQrLoaded) return sfpJsQrLoaded;
    sfpJsQrLoaded = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
        script.onload = () => resolve(window.jsQR);
        script.onerror = reject;
        document.head.appendChild(script);
    });
    return sfpJsQrLoaded;
}

function initQrScanner({ videoId, canvasId, resultInputId, formId, statusId }) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);
    const statusEl = document.getElementById(statusId);
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    let streaming = false;

    const setStatus = (msg) => { if (statusEl) statusEl.textContent = msg; };

    async function start() {
        try {
            await loadJsQR();
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            await video.play();
            streaming = true;
            setStatus('Point the camera at a QR code...');
            requestAnimationFrame(tick);
        } catch (err) {
            setStatus('Camera unavailable - use the code field below instead.');
        }
    }

    function tick() {
        if (!streaming) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = window.jsQR(imageData.data, imageData.width, imageData.height);

            if (code) {
                streaming = false;
                video.srcObject.getTracks().forEach((t) => t.stop());
                setStatus('QR code detected!');
                const input = document.getElementById(resultInputId);
                input.value = code.data;
                document.getElementById(formId).submit();
                return;
            }
        }

        requestAnimationFrame(tick);
    }

    start();
}
