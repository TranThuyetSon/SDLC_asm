<?php
/**
 * ARIA HOTEL - MAIN FOOTER
 * ============================================
 */
?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="index.php">Home</a>
                    <a href="rooms.php">Rooms</a>
                    <a href="restaurant.php">Restaurant</a>
                    <a href="services.php">Services</a>
                    <a href="contact.php">Contact</a>
                </div>
                <p>&copy; <?php echo date('Y'); ?> Aria Hotel. All rights reserved.</p>
                <p>123 Luxury Street, District 1, Ho Chi Minh City, Vietnam | +84 28 1234 5678</p>
            </div>
        </div>
    </footer>
    
    <!-- Main JavaScript -->
    <script src="assets/js/app.js"></script>
    
    <!-- Extra JavaScript (if any) -->
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>