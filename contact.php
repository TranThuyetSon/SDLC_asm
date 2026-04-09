<?php
/**
 * ARIA HOTEL - CONTACT PAGE
 * ============================================
 */

$page_title = 'Contact Us - Aria Hotel';
require_once 'includes/header.php';

$contact_error = '';
$contact_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    // Verify CSRF
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $contact_error = 'Invalid security token. Please try again.';
    } else {
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!Security::checkRateLimit('contact_' . $ip, 5, 3600)) {
            $contact_error = 'Too many messages. Please try again later.';
        } else {
            $name = Security::sanitize($_POST['name']);
            $email = Security::sanitize($_POST['email']);
            $phone = Security::sanitize($_POST['phone']);
            $subject = Security::sanitize($_POST['subject']);
            $message = Security::sanitize($_POST['message']);
            
            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                $contact_error = 'Please fill in all required fields.';
            } elseif (!Security::validateEmail($email)) {
                $contact_error = 'Please enter a valid email address.';
            } else {
                $stmt = $conn->prepare("INSERT INTO contacts (name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, 'unread')");
                $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
                
                if ($stmt->execute()) {
                    $contact_success = 'Thank you for your message! We will get back to you within 24 hours.';
                    $_POST = array();
                } else {
                    $contact_error = 'Failed to send message. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}

$csrf_token = Security::generateCSRFToken();

$hotel_info = [
    'address' => '123 Luxury Street, District 1, Ho Chi Minh City, Vietnam',
    'phone' => '+84 28 1234 5678',
    'hotline' => '+84 903 123 456',
    'email' => 'reservations@ariahotel.com',
    'support_email' => 'support@ariahotel.com'
];
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We're here to help and answer any questions you might have</p>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>Whether you have a question about our rooms, want to check availability, or need assistance with your booking, our team is ready to help.</p>
                
                <div class="info-card">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="info-details">
                            <h4>Our Address</h4>
                            <p><?php echo $hotel_info['address']; ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="info-details">
                            <h4>Phone Numbers</h4>
                            <p>Reservations: <?php echo $hotel_info['phone']; ?><br>
                            Hotline: <?php echo $hotel_info['hotline']; ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-details">
                            <h4>Email Addresses</h4>
                            <p>Reservations: <?php echo $hotel_info['email']; ?><br>
                            Support: <?php echo $hotel_info['support_email']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            
            <div class="contact-form-wrapper">
                <h2>Send Us a Message</h2>
                <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                
                <?php if ($contact_error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($contact_error); ?></div>
                <?php endif; ?>
                
                <?php if ($contact_success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($contact_success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" data-validate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ($is_logged_in ? $user_fullname : '')); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ($is_logged_in ? $user_email : '')); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject <span class="required">*</span></label>
                            <select name="subject" class="form-select" required>
                                <option value="">Select a subject</option>
                                <option value="Room Booking">Room Booking</option>
                                <option value="Restaurant Reservation">Restaurant Reservation</option>
                                <option value="Service Inquiry">Service Inquiry</option>
                                <option value="Feedback">Feedback</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Message <span class="required">*</span></label>
                        <textarea name="message" class="form-textarea" rows="5" 
                                  placeholder="Please describe your inquiry in detail..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="send_message" class="btn btn-primary">Send Message <i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <h4>How do I book a room?</h4>
                <p>You can book a room directly on our website by visiting the Rooms page, selecting your preferred room, and filling out the booking form.</p>
            </div>
            <div class="faq-item">
                <h4>What is your cancellation policy?</h4>
                <p>Free cancellation up to 48 hours before check-in. Cancellations within 48 hours may incur a charge of the first night's stay.</p>
            </div>
            <div class="faq-item">
                <h4>Do you offer airport transfer?</h4>
                <p>Yes, we offer airport transfer service. You can book this service on our Services page.</p>
            </div>
            <div class="faq-item">
                <h4>What are check-in/check-out times?</h4>
                <p>Check-in is from 2:00 PM and check-out is until 12:00 PM.</p>
            </div>
            <div class="faq-item">
                <h4>Is breakfast included?</h4>
                <p>Breakfast is included in some room packages. Please check your booking details.</p>
            </div>
            <div class="faq-item">
                <h4>Do you have parking facilities?</h4>
                <p>Yes, we offer free on-site parking for all hotel guests.</p>
            </div>
        </div>
    </div>
</section>

<?php
$extra_css = '
<style>
.contact-section {
    padding: 3rem 0;
}
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}
.contact-info h2, .contact-form-wrapper h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}
.contact-info > p {
    color: var(--gray-600);
    margin-bottom: 2rem;
}
.info-card {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.info-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.info-item:last-child {
    margin-bottom: 0;
}
.info-icon {
    width: 48px;
    height: 48px;
    background: var(--primary);
    color: white;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.info-details h4 {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    color: var(--gray-700);
}
.info-details p {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin-bottom: 0;
}
.social-links {
    display: flex;
    gap: 1rem;
}
.social-link {
    width: 40px;
    height: 40px;
    background: var(--primary);
    color: white;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition-base);
}
.social-link:hover {
    background: var(--primary-light);
    color: white;
}
.contact-form-wrapper {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 2rem;
}
.faq-section {
    padding: 3rem 0;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}
.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}
.faq-item {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
    padding: 1.5rem;
}
.faq-item h4 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}
.faq-item p {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin-bottom: 0;
}
@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
    .faq-grid {
        grid-template-columns: 1fr;
    }
}
</style>
';

require_once 'includes/footer.php';
?>