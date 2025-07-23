<?php
session_start();
$config = require __DIR__ . '/../config/config.php';

$is_staff = isset($_SESSION['user_id']);
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
$role_name = strtolower($_SESSION['role_name'] ?? '');

$has_org_access = $is_staff && in_array($role_name, ['organizer', 'admin']);

if (!$has_org_access) {
    header('Location: /login');
    exit;
}

$page_title = "Dashboard";
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-cards">
        <div class="card-stat yellow">
            <span class="stat-label">Total Events</span>
            <span class="stat-value">12</span>
            <span class="stat-icon"><i class="bi bi-calendar-event"></i></span>
        </div>
        <div class="card-stat green">
            <span class="stat-label">Total Revenue</span>
            <span class="stat-value">€7,320</span>
            <span class="stat-icon"><i class="bi bi-currency-euro"></i></span>
        </div>
        <div class="card-stat blue">
            <span class="stat-label">Total Attendees</span>
            <span class="stat-value">438</span>
            <span class="stat-icon"><i class="bi bi-people-fill"></i></span>
        </div>
        <div class="card-stat purple">
            <span class="stat-label">Tasks</span>
            <span class="stat-value">17</span>
            <span class="stat-icon"><i class="bi bi-list-check"></i></span>
        </div>
    </div>

    <div class="dashboard-section mb-4">
        <div class="section-title">Popular Events</div>
        <div class="event-card">
            <img src="https://source.unsplash.com/300x140/?event,party" alt="Event">
            <div style="flex:1;">
                <div class="fw-bold mb-1">Summer Gala 2025</div>
                <div class="text-secondary mb-1" style="font-size:.93em;">Tallinn, Estonia &bull; 25 Aug 2025</div>
                <div><span class="badge bg-success">Featured</span> <span class="ms-2">154 attendees</span></div>
            </div>
        </div>
        <!-- Add more .event-card blocks as needed -->
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="dashboard-section">
                <div class="section-title">Attendance Activity</div>
                <div style="height: 120px; display: flex; align-items: center; justify-content: center; color: #999;">
                    (Chart or summary goes here)
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="dashboard-section">
                <div class="section-title">Tasks Status</div>
                <div style="height: 120px; display: flex; align-items: center; justify-content: center; color: #999;">
                    (Status cards or chart)
                </div>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
