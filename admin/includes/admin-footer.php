        </div><!-- /.admin-content -->
    </main><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<script>
// Toggle notifications
function toggleNotifications() {
    const menu = document.getElementById('notificationsMenu');
    menu.classList.toggle('show');
}

// Close notifications when clicking outside
document.addEventListener('click', function(e) {
    const notifications = document.querySelector('.notifications-dropdown');
    const menu = document.getElementById('notificationsMenu');
    if (notifications && !notifications.contains(e.target) && menu) {
        menu.classList.remove('show');
    }
});

// Toggle user menu
const userMenu = document.querySelector('.user-menu');
if (userMenu) {
    const trigger = userMenu.querySelector('.user-trigger');
    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu.classList.toggle('active');
    });
    document.addEventListener('click', function() {
        userMenu.classList.remove('active');
    });
}

// Global functions
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
function showToast(message, type) {
    alert(message);
}
</script>

</body>
</html>