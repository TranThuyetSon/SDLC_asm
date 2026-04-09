<?php
/**
 * ARIA HOTEL - CONSTANTS CONFIGURATION
 * ============================================
 * Định nghĩa tất cả các hằng số sử dụng trong toàn bộ dự án
 */

// ============================================
// ROLE DEFINITIONS
// ============================================
define('ROLE_ADMIN', 1);
define('ROLE_RECEPTIONIST', 2);
define('ROLE_CUSTOMER', 3);

// Role names for display
define('ROLE_NAMES', [
    ROLE_ADMIN => 'Admin',
    ROLE_RECEPTIONIST => 'Receptionist',
    ROLE_CUSTOMER => 'Customer'
]);

// ============================================
// BOOKING STATUS
// ============================================
define('BOOKING_PENDING', 'pending');
define('BOOKING_CONFIRMED', 'confirmed');
define('BOOKING_CHECKED_IN', 'checked_in');
define('BOOKING_CHECKED_OUT', 'checked_out');
define('BOOKING_CANCELLED', 'cancelled');

// Booking status labels and colors
define('BOOKING_STATUS', [
    BOOKING_PENDING => [
        'label' => 'Pending',
        'badge_class' => 'status-pending',
        'bg_color' => '#fef3c7',
        'text_color' => '#d97706'
    ],
    BOOKING_CONFIRMED => [
        'label' => 'Confirmed',
        'badge_class' => 'status-confirmed',
        'bg_color' => '#d1fae5',
        'text_color' => '#059669'
    ],
    BOOKING_CHECKED_IN => [
        'label' => 'Checked In',
        'badge_class' => 'status-checked_in',
        'bg_color' => '#dbeafe',
        'text_color' => '#2563eb'
    ],
    BOOKING_CHECKED_OUT => [
        'label' => 'Checked Out',
        'badge_class' => 'status-checked_out',
        'bg_color' => '#e2e8f0',
        'text_color' => '#64748b'
    ],
    BOOKING_CANCELLED => [
        'label' => 'Cancelled',
        'badge_class' => 'status-cancelled',
        'bg_color' => '#fee2e2',
        'text_color' => '#dc2626'
    ]
]);

// ============================================
// ROOM STATUS
// ============================================
define('ROOM_AVAILABLE', 'available');
define('ROOM_BOOKED', 'booked');
define('ROOM_OCCUPIED', 'occupied');
define('ROOM_MAINTENANCE', 'maintenance');

// Room status labels and colors
define('ROOM_STATUS', [
    ROOM_AVAILABLE => [
        'label' => 'Available',
        'badge_class' => 'status-available',
        'bg_color' => '#d1fae5',
        'text_color' => '#059669'
    ],
    ROOM_BOOKED => [
        'label' => 'Booked',
        'badge_class' => 'status-booked',
        'bg_color' => '#fef3c7',
        'text_color' => '#d97706'
    ],
    ROOM_OCCUPIED => [
        'label' => 'Occupied',
        'badge_class' => 'status-occupied',
        'bg_color' => '#dbeafe',
        'text_color' => '#2563eb'
    ],
    ROOM_MAINTENANCE => [
        'label' => 'Maintenance',
        'badge_class' => 'status-maintenance',
        'bg_color' => '#fee2e2',
        'text_color' => '#dc2626'
    ]
]);

// ============================================
// PAYMENT STATUS
// ============================================
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_COMPLETED', 'completed');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_REFUNDED', 'refunded');

// Payment status labels
define('PAYMENT_STATUS', [
    PAYMENT_PENDING => [
        'label' => 'Pending',
        'badge_class' => 'status-pending',
        'icon' => 'clock'
    ],
    PAYMENT_COMPLETED => [
        'label' => 'Completed',
        'badge_class' => 'status-success',
        'icon' => 'check-circle'
    ],
    PAYMENT_FAILED => [
        'label' => 'Failed',
        'badge_class' => 'status-danger',
        'icon' => 'times-circle'
    ],
    PAYMENT_REFUNDED => [
        'label' => 'Refunded',
        'badge_class' => 'status-warning',
        'icon' => 'undo'
    ]
]);

// Payment methods
define('PAYMENT_METHODS', [
    'cash' => 'Cash',
    'credit_card' => 'Credit Card',
    'bank_transfer' => 'Bank Transfer',
    'momo' => 'Momo',
    'vnpay' => 'VNPay'
]);

// ============================================
// CONTACT STATUS
// ============================================
define('CONTACT_UNREAD', 'unread');
define('CONTACT_READ', 'read');
define('CONTACT_REPLIED', 'replied');

define('CONTACT_STATUS', [
    CONTACT_UNREAD => [
        'label' => 'Unread',
        'badge_class' => 'status-warning'
    ],
    CONTACT_READ => [
        'label' => 'Read',
        'badge_class' => 'status-info'
    ],
    CONTACT_REPLIED => [
        'label' => 'Replied',
        'badge_class' => 'status-success'
    ]
]);

// Contact subjects
define('CONTACT_SUBJECTS', [
    'Room Booking' => 'Room Booking',
    'Restaurant Reservation' => 'Restaurant Reservation',
    'Service Inquiry' => 'Service Inquiry',
    'Feedback' => 'Feedback',
    'Complaint' => 'Complaint',
    'Other' => 'Other'
]);

// ============================================
// RESTAURANT RESERVATION STATUS
// ============================================
define('RESERVATION_PENDING', 'pending');
define('RESERVATION_CONFIRMED', 'confirmed');
define('RESERVATION_CANCELLED', 'cancelled');
define('RESERVATION_COMPLETED', 'completed');

define('RESERVATION_STATUS', [
    RESERVATION_PENDING => [
        'label' => 'Pending',
        'badge_class' => 'status-pending'
    ],
    RESERVATION_CONFIRMED => [
        'label' => 'Confirmed',
        'badge_class' => 'status-success'
    ],
    RESERVATION_CANCELLED => [
        'label' => 'Cancelled',
        'badge_class' => 'status-danger'
    ],
    RESERVATION_COMPLETED => [
        'label' => 'Completed',
        'badge_class' => 'status-info'
    ]
]);

// Table locations
define('TABLE_LOCATIONS', [
    'indoor' => 'Indoor',
    'outdoor' => 'Outdoor',
    'vip' => 'VIP Room'
]);

// ============================================
// MENU CATEGORIES
// ============================================
define('MENU_CATEGORIES', [
    'appetizer' => 'Appetizers',
    'main_course' => 'Main Course',
    'dessert' => 'Desserts',
    'beverage' => 'Beverages',
    'breakfast' => 'Breakfast'
]);

// ============================================
// SERVICE BOOKING STATUS
// ============================================
define('SERVICE_PENDING', 'pending');
define('SERVICE_CONFIRMED', 'confirmed');
define('SERVICE_COMPLETED', 'completed');
define('SERVICE_CANCELLED', 'cancelled');

define('SERVICE_STATUS', [
    SERVICE_PENDING => [
        'label' => 'Pending',
        'badge_class' => 'status-pending'
    ],
    SERVICE_CONFIRMED => [
        'label' => 'Confirmed',
        'badge_class' => 'status-success'
    ],
    SERVICE_COMPLETED => [
        'label' => 'Completed',
        'badge_class' => 'status-info'
    ],
    SERVICE_CANCELLED => [
        'label' => 'Cancelled',
        'badge_class' => 'status-danger'
    ]
]);

// ============================================
// HOTEL INFORMATION
// ============================================
define('HOTEL_NAME', 'Aria Hotel');
define('HOTEL_SLOGAN', 'Luxury Stay in the Heart of the City');
define('HOTEL_PHONE', '+84 28 1234 5678');
define('HOTEL_HOTLINE', '+84 903 123 456');
define('HOTEL_EMAIL', 'reservations@ariahotel.com');
define('HOTEL_SUPPORT_EMAIL', 'support@ariahotel.com');
define('HOTEL_ADDRESS', '123 Luxury Street, District 1, Ho Chi Minh City, Vietnam');
define('HOTEL_CITY', 'Ho Chi Minh City');
define('HOTEL_COUNTRY', 'Vietnam');
define('HOTEL_POSTAL_CODE', '700000');

// Social media
define('HOTEL_FACEBOOK', 'https://facebook.com/ariahotel');
define('HOTEL_INSTAGRAM', 'https://instagram.com/ariahotel');
define('HOTEL_TWITTER', 'https://twitter.com/ariahotel');
define('HOTEL_YOUTUBE', 'https://youtube.com/ariahotel');

// Business hours
define('CHECK_IN_TIME', '14:00');
define('CHECK_OUT_TIME', '12:00');
define('RESTAURANT_BREAKFAST_START', '06:00');
define('RESTAURANT_BREAKFAST_END', '10:00');
define('RESTAURANT_LUNCH_START', '11:30');
define('RESTAURANT_LUNCH_END', '14:30');
define('RESTAURANT_DINNER_START', '18:00');
define('RESTAURANT_DINNER_END', '22:00');

// ============================================
// PAGINATION
// ============================================
define('DEFAULT_PAGE_SIZE', 12);
define('DEFAULT_PAGE', 1);
define('MAX_PAGE_SIZE', 100);

// ============================================
// FILE UPLOADS
// ============================================
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('ROOM_IMAGE_PATH', UPLOAD_PATH . 'rooms/');
define('SERVICE_IMAGE_PATH', UPLOAD_PATH . 'services/');
define('MENU_IMAGE_PATH', UPLOAD_PATH . 'menu/');

// ============================================
// TIME ZONE & LOCALE
// ============================================
define('TIMEZONE', 'Asia/Ho_Chi_Minh');
define('LOCALE', 'vi_VN');
define('CURRENCY', 'VND');
define('CURRENCY_SYMBOL', '₫');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('TIME_FORMAT', 'H:i');

// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', 'Aria Hotel Management System');
define('APP_VERSION', '2.0.0');
define('APP_DEBUG', true); // Set to false in production
define('APP_URL', 'http://localhost'); // Change in production

// Session settings
define('SESSION_LIFETIME', 7200); // 2 hours
define('REMEMBER_ME_DAYS', 30);

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT_MINUTES', 15);
define('CSRF_TOKEN_NAME', 'csrf_token');

// Rate limiting
define('RATE_LIMIT_MAX_REQUESTS', 100);
define('RATE_LIMIT_TIME_WINDOW', 60); // seconds

// ============================================
// LOYALTY PROGRAM
// ============================================
define('LOYALTY_POINTS_PER_NIGHT', 100);
define('LOYALTY_POINTS_PER_SPENT', 10000); // 1 point per 10,000 VND
define('LOYALTY_REDEMPTION_RATE', 1000); // 1000 points = 100,000 VND discount

// ============================================
// CANCELLATION POLICY
// ============================================
define('FREE_CANCELLATION_HOURS', 48); // Free cancellation up to 48 hours before check-in
define('LATE_CANCELLATION_FEE_PERCENT', 50); // 50% of first night
define('NO_SHOW_FEE_PERCENT', 100); // 100% of first night

// ============================================
// TAX & FEES
// ============================================
define('VAT_PERCENT', 10); // 10% VAT
define('SERVICE_CHARGE_PERCENT', 5); // 5% service charge

// ============================================
// ROOM TYPE FEATURES (Default)
// ============================================
define('ROOM_AMENITIES', [
    'wifi' => 'Free Wi-Fi',
    'ac' => 'Air Conditioning',
    'tv' => 'Flat-screen TV',
    'minibar' => 'Minibar',
    'safe' => 'Safety Deposit Box',
    'hairdryer' => 'Hairdryer',
    'toiletries' => 'Free Toiletries',
    'bathtub' => 'Bathtub',
    'shower' => 'Rain Shower',
    'desk' => 'Work Desk',
    'balcony' => 'Private Balcony',
    'city_view' => 'City View',
    'river_view' => 'River View',
    'pool_view' => 'Pool View',
    'coffee_maker' => 'Coffee Maker',
    'iron' => 'Iron & Ironing Board',
    'bathrobe' => 'Bathrobe & Slippers',
    'soundproof' => 'Soundproofing',
    'sofa' => 'Sofa Bed'
]);

// ============================================
// MESSAGES (Vietnamese & English)
// ============================================
define('MESSAGES', [
    // Success messages
    'login_success' => 'Đăng nhập thành công! / Login successful!',
    'register_success' => 'Đăng ký thành công! Vui lòng đăng nhập. / Registration successful! Please login.',
    'logout_success' => 'Đăng xuất thành công! / Logout successful!',
    'profile_updated' => 'Cập nhật thông tin thành công! / Profile updated successfully!',
    'password_changed' => 'Đổi mật khẩu thành công! / Password changed successfully!',
    'booking_created' => 'Đặt phòng thành công! / Booking created successfully!',
    'booking_cancelled' => 'Hủy đặt phòng thành công! / Booking cancelled successfully!',
    'contact_sent' => 'Tin nhắn đã được gửi! Chúng tôi sẽ phản hồi sớm nhất. / Message sent! We will respond shortly.',
    'reservation_created' => 'Đặt bàn thành công! / Table reserved successfully!',
    'service_booked' => 'Đặt dịch vụ thành công! / Service booked successfully!',
    
    // Error messages
    'login_failed' => 'Tên đăng nhập hoặc mật khẩu không đúng. / Invalid username or password.',
    'account_inactive' => 'Tài khoản đã bị khóa. Vui lòng liên hệ hỗ trợ. / Account is inactive. Please contact support.',
    'email_exists' => 'Email đã được sử dụng. / Email already in use.',
    'username_exists' => 'Tên đăng nhập đã tồn tại. / Username already exists.',
    'password_mismatch' => 'Mật khẩu xác nhận không khớp. / Passwords do not match.',
    'invalid_email' => 'Email không hợp lệ. / Invalid email address.',
    'invalid_phone' => 'Số điện thoại không hợp lệ. / Invalid phone number.',
    'weak_password' => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa và số. / Password must be at least 8 characters with uppercase and number.',
    'room_unavailable' => 'Phòng không còn trống trong thời gian đã chọn. / Room is not available for selected dates.',
    'booking_not_found' => 'Không tìm thấy đơn đặt phòng. / Booking not found.',
    'unauthorized' => 'Bạn không có quyền truy cập. / Unauthorized access.',
    'csrf_invalid' => 'Token bảo mật không hợp lệ. Vui lòng thử lại. / Invalid security token. Please try again.',
    'rate_limit' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau. / Too many requests. Please try again later.',
    'required_fields' => 'Vui lòng điền đầy đủ thông tin bắt buộc. / Please fill in all required fields.'
]);

// ============================================
// API ENDPOINTS (if needed)
// ============================================
define('API_BASE_URL', '/api');
define('API_VERSION', 'v1');

// ============================================
// EMAIL TEMPLATES
// ============================================
define('EMAIL_FROM', HOTEL_EMAIL);
define('EMAIL_FROM_NAME', HOTEL_NAME);
define('EMAIL_REPLY_TO', HOTEL_SUPPORT_EMAIL);

// ============================================
// MISC
// ============================================
define('DEFAULT_AVATAR', 'https://ui-avatars.com/api/?background=0a192f&color=ffffff&size=128&bold=true');
define('DEFAULT_ROOM_IMAGE', 'https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?w=600');
define('DEFAULT_SERVICE_IMAGE', 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?w=400');

// Google Maps
define('GOOGLE_MAPS_EMBED_URL', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.123456789012!2d106.700000!3d10.800000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3175294bfd0d1b3d%3A0x7c9b8b6c8b7c9b8b!2sDistrict%201%2C%20Ho%20Chi%20Minh%20City!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s');