<!DOCTYPE html>
<html lang="<?= htmlspecialchars($tr->getLang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($event['event_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#000000">
  <style>
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: #0e0e0e;
      color: #fff;
    }
    .header {
      background: url('<?= htmlspecialchars($event['header_image']) ?>') no-repeat center center;
      background-size: cover;
      height: 220px;
      position: relative;
    }
    .header-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
    }
    .event-name {
      position: absolute;
      bottom: 10px;
      left: 20px;
      font-size: 1.5rem;
      font-weight: bold;
    }
    .glass-box {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      padding: 1rem;
      margin: 1rem;
    }
    .upload-btn {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 100;
      border-radius: 50px;
      padding: 0.75rem 2rem;
    }
    .loading-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.5rem;
      z-index: 200;
    }
    .memory img, .memory video {
      width: 100%;
      border-radius: 12px;
    }
    .like-btn.active {
      background-color: #0d6efd;
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="header-overlay"></div>
    <div class="event-name text-white">
      <?= htmlspecialchars($event['event_name']) ?>
    </div>
  </div>

  <div class="text-end px-3 mt-2">
    <small class="text-white"><?= htmlspecialchars($tr->t('language')) ?>:</small>
    <a href="?public_id=<?= urlencode($publicId) ?>&lang=et" class="text-white ms-2">EE</a>
    <a href="?public_id=<?= urlencode($publicId) ?>&lang=es" class="text-white ms-2">ES</a>
    <a href="?public_id=<?= urlencode($publicId) ?>&lang=en" class="text-white ms-2">ES</a>
  </div>

  <div class="container">
    <div class="glass-box text-center">
      <strong><?= htmlspecialchars($event['event_date']) ?></strong><br>
      <?= htmlspecialchars($event['event_location']) ?><br>
      <small><?= htmlspecialchars($event['description']) ?></small>
    </div>

    <div class="glass-box">
      <form method="post" enctype="multipart/form-data" id="postForm">
        <input type="hidden" name="new_post" value="1">
        <input type="file" name="media" id="mediaInput" accept="image/*,video/*" capture="environment" class="d-none" required>
        <div class="d-flex gap-2 mb-2">
          <button type="button" class="btn btn-secondary flex-fill" id="cameraBtn"><?= htmlspecialchars($tr->t('use_camera')) ?></button>
          <button type="button" class="btn btn-secondary flex-fill" id="uploadBtn"><?= htmlspecialchars($tr->t('choose_file')) ?></button>
        </div>
        <div id="filePreview" class="mb-2"></div>
        <textarea name="caption" class="form-control mb-2" placeholder="<?= htmlspecialchars($tr->t('write_message')) ?>"></textarea>
        <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars($tr->t('upload_btn')) ?></button>
      </form>
    </div>

    <div class="glass-box">
      <h5><?= htmlspecialchars($tr->t('memories')) ?></h5>
      <?php foreach ($posts as $p): ?>
      <div class="memory mb-4">
        <?php if (isVideo($p['file_url'])): ?>
          <video src="<?= htmlspecialchars($p['file_url']) ?>" controls></video>
        <?php else: ?>
          <img src="<?= htmlspecialchars($p['file_url']) ?>" alt="Memory">
        <?php endif; ?>
        <?php if (!empty($p['caption'])): ?><p><?= htmlspecialchars($p['caption']) ?></p><?php endif; ?>
        <div class="d-flex align-items-center like-container mt-1">
          <button type="button" class="btn btn-sm btn-outline-light like-btn<?= $p['liked'] ? ' active' : '' ?>" data-post="<?= $p['id'] ?>" data-like="<?= htmlspecialchars($tr->t('like')) ?>" data-unlike="<?= htmlspecialchars($tr->t('unlike')) ?>">
            <span class="like-text"><?= $p['liked'] ? htmlspecialchars($tr->t('unlike')) : htmlspecialchars($tr->t('like')) ?></span>
          </button>
          <span class="ms-2 like-count"><?= $p['likes'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <button class="btn btn-light upload-btn" onclick="document.getElementById('cameraBtn').click()"><?= htmlspecialchars($tr->t('add_memory')) ?></button>

  <div id="loadingOverlay" class="loading-overlay d-none"><?= htmlspecialchars($tr->t('loading')) ?></div>

  <div class="modal fade" id="camModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header border-0">
          <h5 class="modal-title"><?= htmlspecialchars($tr->t('use_camera')) ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <div id="cameraError" class="text-danger mb-2 d-none"></div>
          <select id="cameraSelect" class="form-select mb-2 d-none"></select>
          <video id="cameraPreview" class="w-100 rounded mb-2" autoplay playsinline></video>
          <canvas id="snapshotCanvas" class="w-100 rounded mb-2 d-none"></canvas>
          <div class="btn-group mb-2" id="filterControls" role="group">
            <button type="button" class="btn btn-secondary active" data-filter="none">Normal</button>
            <button type="button" class="btn btn-secondary" data-filter="grayscale(100%)">Grayscale</button>
            <button type="button" class="btn btn-secondary" data-filter="sepia(100%)">Sepia</button>
            <button type="button" class="btn btn-secondary" data-filter="blur(5px)">Blur</button>
          </div>
          <div>
            <button type="button" class="btn btn-light" id="snapBtn"><?= htmlspecialchars($tr->t('take_photo')) ?></button>
            <button type="button" class="btn btn-primary d-none" id="confirmBtn"><?= htmlspecialchars($tr->t('upload_btn')) ?></button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const input = document.getElementById('mediaInput');
    const preview = document.getElementById('filePreview');

    function updatePreview() {
      preview.innerHTML = '';
      const file = input.files && input.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      if (file.type.startsWith('video/')) {
        const v = document.createElement('video');
        v.src = url;
        v.controls = true;
        v.style.maxWidth = '100%';
        v.style.borderRadius = '12px';
        preview.appendChild(v);
      } else if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = url;
        img.style.maxWidth = '100%';
        img.style.borderRadius = '12px';
        preview.appendChild(img);
      }
    }

    const modalEl = document.getElementById('camModal');
    const cameraSelect = document.getElementById('cameraSelect');
    const cameraPreview = document.getElementById('cameraPreview');
    const snapshotCanvas = document.getElementById('snapshotCanvas');
    const snapBtn = document.getElementById('snapBtn');
    const confirmBtn = document.getElementById('confirmBtn');
    const cameraError = document.getElementById('cameraError');
    const filterControls = document.getElementById('filterControls');
    let stream = null;
    let currentFilter = 'none';

    function stopStream() {
      if (stream) {
        stream.getTracks().forEach(t => t.stop());
        stream = null;
      }
    }

    async function startStream(deviceId) {
      stopStream();
      try {
        const constraints = { video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' } };
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        cameraPreview.srcObject = stream;
        cameraPreview.style.filter = currentFilter;
        cameraError.classList.add('d-none');
      } catch (e) {
        cameraError.textContent = 'Camera not available';
        cameraError.classList.remove('d-none');
      }
    }

    document.getElementById('cameraBtn').addEventListener('click', async () => {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
      confirmBtn.classList.add('d-none');
      snapBtn.classList.remove('d-none');
      snapshotCanvas.classList.add('d-none');
      cameraPreview.classList.remove('d-none');

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        cameraError.textContent = 'Camera not supported';
        cameraError.classList.remove('d-none');
        return;
      }

      const devices = await navigator.mediaDevices.enumerateDevices();
      const videoDevices = devices.filter(d => d.kind === 'videoinput');
      cameraSelect.innerHTML = '';
      if (videoDevices.length > 1) {
        cameraSelect.classList.remove('d-none');
        videoDevices.forEach((d, i) => {
          const opt = document.createElement('option');
          opt.value = d.deviceId;
          opt.textContent = d.label || `Camera ${i + 1}`;
          cameraSelect.appendChild(opt);
        });
        cameraSelect.onchange = () => startStream(cameraSelect.value);
        cameraSelect.value = videoDevices[0].deviceId;
        startStream(videoDevices[0].deviceId);
      } else {
        cameraSelect.classList.add('d-none');
        startStream(videoDevices[0] ? videoDevices[0].deviceId : undefined);
      }
    });

    modalEl.addEventListener('hidden.bs.modal', stopStream);

    filterControls.addEventListener('click', e => {
      if (e.target.dataset.filter !== undefined) {
        filterControls.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        currentFilter = e.target.dataset.filter;
        cameraPreview.style.filter = currentFilter;
      }
    });

    snapBtn.addEventListener('click', () => {
      snapshotCanvas.width = cameraPreview.videoWidth;
      snapshotCanvas.height = cameraPreview.videoHeight;
      const ctx = snapshotCanvas.getContext('2d');
      ctx.filter = currentFilter;
      ctx.drawImage(cameraPreview, 0, 0);
      snapshotCanvas.classList.remove('d-none');
      cameraPreview.classList.add('d-none');
      snapBtn.classList.add('d-none');
      confirmBtn.classList.remove('d-none');
    });

    confirmBtn.addEventListener('click', () => {
      snapshotCanvas.toBlob(blob => {
        const file = new File([blob], 'capture.jpg', { type: blob.type });
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        bootstrap.Modal.getInstance(modalEl).hide();
        updatePreview();
      }, 'image/jpeg');
    });

    document.getElementById('uploadBtn').addEventListener('click', () => {
      input.removeAttribute('capture');
      input.click();
    });

    input.addEventListener('change', updatePreview);
    document.querySelectorAll('.like-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.post;
        const form = new URLSearchParams({like_post: 1, post_id: id});
        fetch('', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: form})
          .then(r => r.json())
          .then(d => {
            btn.classList.toggle('active', d.liked);
            const likeText = d.liked ? btn.dataset.unlike : btn.dataset.like;
            btn.querySelector('.like-text').textContent = likeText;
            btn.closest('.like-container').querySelector('.like-count').textContent = d.likes;
          });
      });
    });

    document.getElementById('postForm').addEventListener('submit', () => {
      document.getElementById('loadingOverlay').classList.remove('d-none');
    });

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js');
    }
  </script>
</body>
</html>
