<?php
if (!isset($is_staff)) $is_staff = false;
if (!isset($display_name)) $display_name = '';
?>
<nav class="topbar">
    <div class="d-flex align-items-center">
        <button class="hamburger d-lg-none" id="sidebarToggle" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <input type="search" class="form-control ms-0 ms-lg-3" style="width: 220px; background: #fff;" placeholder="Search...">
    </div>
    <div class="d-flex align-items-center">
        <span class="me-3 d-none d-sm-block">Hello, <?= htmlspecialchars($display_name) ?></span>
        <a href="/logout" class="btn btn-accent me-3">Logout</a>
        <span class="rounded-circle" style="width:36px;height:36px;display:inline-block;overflow:hidden;background:#6a4af3;">
            <img src="https://api.dicebear.com/7.x/identicon/svg?seed=<?= urlencode($display_name) ?>" alt="avatar" style="width:100%;height:100%;">
        </span>
    </div>
</nav>

