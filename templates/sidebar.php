<?php
if (!isset($is_staff)) $is_staff = false;
if (!isset($display_name)) $display_name = '';
?>
<?php if($is_staff): ?>
<div class="sidebar">
    <div class="sidebar-title">Event Admin</div>
    <div>
        <a href="/dashboard" class="nav-link<?= $_SERVER['REQUEST_URI']=='/dashboard'?' active':'' ?>"><i class="bi bi-house-door"></i> <span class="ms-2">Dashboard</span></a>
        <a href="/events" class="nav-link<?= strpos($_SERVER['REQUEST_URI'],'/events')===0?' active':'' ?>"><i class="bi bi-calendar-event"></i> <span class="ms-2">Events</span></a>
        <a href="/guests" class="nav-link<?= strpos($_SERVER['REQUEST_URI'],'/guests')===0?' active':'' ?>"><i class="bi bi-people"></i> <span class="ms-2">Guests</span></a>
        <a href="/uploads" class="nav-link<?= strpos($_SERVER['REQUEST_URI'],'/uploads')===0?' active':'' ?>"><i class="bi bi-cloud-arrow-up"></i> <span class="ms-2">Uploads</span></a>
        <a href="/tasks" class="nav-link<?= strpos($_SERVER['REQUEST_URI'],'/tasks')===0?' active':'' ?>"><i class="bi bi-list-task"></i> <span class="ms-2">Tasks</span></a>
    </div>
    <div class="sidebar-bottom">
        <span>Logged in as <b><?= htmlspecialchars($display_name) ?></b></span>
    </div>
</div>

<?php endif; ?>
