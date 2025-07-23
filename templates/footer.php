<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Choices.js JS -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('guestSelect');
    if (select) {
        new Choices(select, {
            removeItemButton: true,
            searchPlaceholderValue: 'Type guest name or email…'
        });
    }
});

// Sidebar mobile toggle logic unchanged
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.sidebar');
    var toggle = document.getElementById('sidebarToggle');
    if(toggle && sidebar) {
        toggle.onclick = function(e) {
            e.preventDefault();
            sidebar.classList.toggle('open');
        };
    }
    document.addEventListener('click', function(e) {
        if(window.innerWidth < 992 && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
});
</script>
</body>
</html>
