<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js?v=<?= time(); ?>"></script>
<script>
$(function() {
  $('#guestSelect').select2({
    theme: 'bootstrap4', // Use 'bootstrap4' here!
    placeholder: "Type guest name or email…",
    width: '100%'
  });
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
