<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($event['event_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

  <div class="container">
    <div class="glass-box text-center">
      <strong><?= htmlspecialchars($event['event_date']) ?></strong><br>
      <?= htmlspecialchars($event['event_location']) ?><br>
      <small>Hosted by <?= htmlspecialchars($event['description']) ?></small>
    </div>

    <div class="glass-box">
      <form method="post" enctype="multipart/form-data" id="postForm">
        <input type="hidden" name="new_post" value="1">
        <input type="file" name="media" id="mediaInput" accept="image/*,video/*" class="d-none" required>
        <div class="d-flex gap-2 mb-2">
          <button type="button" class="btn btn-secondary flex-fill" id="cameraBtn">Use Camera</button>
          <button type="button" class="btn btn-secondary flex-fill" id="uploadBtn">Choose File</button>
        </div>
        <textarea name="caption" class="form-control mb-2" placeholder="Write a message..."></textarea>
        <button type="submit" class="btn btn-primary w-100">Upload</button>
      </form>
    </div>

    <div class="glass-box">
      <h5>Memories</h5>
      <?php foreach ($posts as $p): ?>
      <div class="memory mb-4">
        <?php if (isVideo($p['file_url'])): ?>
          <video src="<?= htmlspecialchars($p['file_url']) ?>" controls></video>
        <?php else: ?>
          <img src="<?= htmlspecialchars($p['file_url']) ?>" alt="Memory">
        <?php endif; ?>
        <?php if (!empty($p['caption'])): ?><p><?= htmlspecialchars($p['caption']) ?></p><?php endif; ?>
        <div class="d-flex align-items-center like-container mt-1">
          <button type="button" class="btn btn-sm btn-outline-light like-btn<?= $p['liked'] ? ' active' : '' ?>" data-post="<?= $p['id'] ?>">
            <span class="like-text"><?= $p['liked'] ? 'Unlike' : 'Like' ?></span>
          </button>
          <span class="ms-2 like-count"><?= $p['likes'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <button class="btn btn-light upload-btn" onclick="document.querySelector('[name=media]').click()">Add Memory</button>

  <script>
    const input = document.getElementById('mediaInput');
    document.getElementById('cameraBtn').addEventListener('click', () => {
      input.setAttribute('capture', 'environment');
      input.click();
    });
    document.getElementById('uploadBtn').addEventListener('click', () => {
      input.removeAttribute('capture');
      input.click();
    });
    document.querySelectorAll('.like-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.post;
        const form = new URLSearchParams({like_post: 1, post_id: id});
        fetch('', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: form})
          .then(r => r.json())
          .then(d => {
            btn.classList.toggle('active', d.liked);
            btn.querySelector('.like-text').textContent = d.liked ? 'Unlike' : 'Like';
            btn.closest('.like-container').querySelector('.like-count').textContent = d.likes;
          });
      });
    });
  </script>
</body>
</html>
