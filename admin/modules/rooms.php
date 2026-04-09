<?php
/**
 * ROOM MANAGEMENT MODULE
 * ============================================
 */

$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = ["1=1"];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($type_filter !== 'all') {
    $where[] = "r.room_type_id = ?";
    $params[] = (int)$type_filter;
    $types .= "i";
}

if (!empty($search)) {
    $where[] = "r.room_number LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$where_clause = implode(" AND ", $where);

// Get rooms
$rooms_sql = "SELECT r.*, rt.name as room_type_name, rt.base_price, rt.max_adults, rt.max_children,
                     (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.id AND b.status IN ('confirmed', 'checked_in')) as active_bookings
              FROM rooms r
              JOIN room_types rt ON r.room_type_id = rt.id
              WHERE $where_clause
              ORDER BY r.room_number";

$stmt = $conn->prepare($rooms_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rooms = $stmt->get_result();
$stmt->close();

// Get room types for filter
$room_types = $conn->query("SELECT id, name FROM room_types ORDER BY name");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add_room') {
            $room_number = trim($_POST['room_number']);
            $room_type_id = (int)$_POST['room_type_id'];
            $floor = (int)$_POST['floor'];
            $status = $_POST['status'];
            
            $check = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
            $check->bind_param("s", $room_number);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'Room number already exists.';
            } else {
                $insert = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, floor, status) VALUES (?, ?, ?, ?)");
                $insert->bind_param("siis", $room_number, $room_type_id, $floor, $status);
                if ($insert->execute()) {
                    logActivity($conn, $_SESSION['user_id'], "Added room $room_number", 'rooms', $insert->insert_id);
                    $success = 'Room added successfully.';
                } else {
                    $error = 'Failed to add room.';
                }
                $insert->close();
            }
            $check->close();
        } elseif ($action == 'edit_room') {
            $room_id = (int)$_POST['room_id'];
            $room_number = trim($_POST['room_number']);
            $room_type_id = (int)$_POST['room_type_id'];
            $floor = (int)$_POST['floor'];
            $status = $_POST['status'];
            
            $update = $conn->prepare("UPDATE rooms SET room_number = ?, room_type_id = ?, floor = ?, status = ? WHERE id = ?");
            $update->bind_param("siisi", $room_number, $room_type_id, $floor, $status, $room_id);
            if ($update->execute()) {
                logActivity($conn, $_SESSION['user_id'], "Updated room #$room_id", 'rooms', $room_id);
                $success = 'Room updated successfully.';
            } else {
                $error = 'Failed to update room.';
            }
            $update->close();
        } elseif ($action == 'delete_room') {
            $room_id = (int)$_POST['room_id'];
            
            $check_bookings = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE room_id = ?");
            $check_bookings->bind_param("i", $room_id);
            $check_bookings->execute();
            $has_bookings = $check_bookings->get_result()->fetch_assoc()['count'] > 0;
            $check_bookings->close();
            
            if ($has_bookings) {
                $error = 'Cannot delete room with existing bookings.';
            } else {
                $delete = $conn->prepare("DELETE FROM rooms WHERE id = ?");
                $delete->bind_param("i", $room_id);
                if ($delete->execute()) {
                    logActivity($conn, $_SESSION['user_id'], "Deleted room #$room_id", 'rooms', $room_id);
                    $success = 'Room deleted successfully.';
                } else {
                    $error = 'Failed to delete room.';
                }
                $delete->close();
            }
        } elseif ($action == 'bulk_update') {
            $room_ids = $_POST['room_ids'] ?? [];
            $new_status = $_POST['bulk_status'];
            
            if (!empty($room_ids) && $new_status) {
                $placeholders = implode(',', array_fill(0, count($room_ids), '?'));
                $sql = "UPDATE rooms SET status = ? WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                $types = "s" . str_repeat("i", count($room_ids));
                $params = array_merge([$new_status], $room_ids);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $success = 'Rooms updated successfully.';
                } else {
                    $error = 'Failed to update rooms.';
                }
                $stmt->close();
            }
        }
    }
}

$csrf_token = Security::generateCSRFToken();
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Room Management</h3>
        <button class="btn btn-primary btn-sm" onclick="openModal('addRoomModal')">
            <i class="fas fa-plus"></i> Add Room
        </button>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form">
            <input type="hidden" name="module" value="rooms">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Rooms</option>
                        <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="booked" <?php echo $status_filter == 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="occupied" <?php echo $status_filter == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo $status_filter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Room Type</label>
                    <select name="type" class="form-select">
                        <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php while ($type = $room_types->fetch_assoc()): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $type_filter == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group flex-1">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Room number..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=rooms" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Actions -->
<div class="admin-card">
    <div class="admin-card-body">
        <form method="POST" id="bulkForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="bulk_update">
            
            <div class="bulk-actions">
                <select name="bulk_status" class="form-select" style="width: 200px;">
                    <option value="">Bulk Update Status</option>
                    <option value="available">Set Available</option>
                    <option value="maintenance">Set Maintenance</option>
                </select>
                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Update selected rooms?')">Apply</button>
                <span class="selected-count">0 rooms selected</span>
            </div>
        </form>
    </div>
</div>

<!-- Rooms Grid -->
<div class="rooms-grid">
    <?php while ($room = $rooms->fetch_assoc()): ?>
    <div class="room-card-admin <?php echo $room['status']; ?>">
        <div class="room-card-header">
            <label class="checkbox">
                <input type="checkbox" name="room_ids[]" value="<?php echo $room['id']; ?>" form="bulkForm" class="room-checkbox">
            </label>
            <span class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></span>
            <?php echo getStatusBadge($room['status'], 'room'); ?>
        </div>
        <div class="room-card-body">
            <h4><?php echo htmlspecialchars($room['room_type_name']); ?></h4>
            <div class="room-info">
                <div><i class="fas fa-building"></i> Floor: <?php echo $room['floor']; ?></div>
                <div><i class="fas fa-tag"></i> <?php echo formatCurrency($room['base_price']); ?>/night</div>
                <div><i class="fas fa-users"></i> Max <?php echo $room['max_adults'] + $room['max_children']; ?> guests</div>
                <?php if ($room['active_bookings'] > 0): ?>
                    <div><i class="fas fa-calendar"></i> <?php echo $room['active_bookings']; ?> active booking(s)</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="room-card-footer">
            <button class="btn btn-outline-primary btn-sm" onclick="editRoom(<?php echo $room['id']; ?>, '<?php echo $room['room_number']; ?>', <?php echo $room['room_type_id']; ?>, <?php echo $room['floor']; ?>, '<?php echo $room['status']; ?>')">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo $room['room_number']; ?>')">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Add Room Modal -->
<div id="addRoomModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Room</h3>
            <button class="modal-close" onclick="closeModal('addRoomModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="add_room">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Room Number *</label>
                    <input type="text" name="room_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Room Type *</label>
                    <select name="room_type_id" class="form-select" required>
                        <option value="">Select type...</option>
                        <?php 
                        $room_types->data_seek(0);
                        while ($type = $room_types->fetch_assoc()): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" name="floor" class="form-control" value="1">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addRoomModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<div id="editRoomModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Room</h3>
            <button class="modal-close" onclick="closeModal('editRoomModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="edit_room">
            <input type="hidden" name="room_id" id="edit_room_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Room Number *</label>
                    <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Room Type *</label>
                    <select name="room_type_id" id="edit_room_type_id" class="form-select" required>
                        <?php 
                        $room_types->data_seek(0);
                        while ($type = $room_types->fetch_assoc()): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" name="floor" id="edit_floor" class="form-control">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="available">Available</option>
                        <option value="booked">Booked</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editRoomModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Room Form -->
<form id="deleteRoomForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete_room">
    <input type="hidden" name="room_id" id="delete_room_id">
</form>

<script>
function editRoom(id, number, typeId, floor, status) {
    document.getElementById('edit_room_id').value = id;
    document.getElementById('edit_room_number').value = number;
    document.getElementById('edit_room_type_id').value = typeId;
    document.getElementById('edit_floor').value = floor;
    document.getElementById('edit_status').value = status;
    openModal('editRoomModal');
}

function deleteRoom(id, number) {
    if (confirm('Are you sure you want to delete Room ' + number + '?')) {
        document.getElementById('delete_room_id').value = id;
        document.getElementById('deleteRoomForm').submit();
    }
}

// Update selected count
document.querySelectorAll('.room-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const count = document.querySelectorAll('.room-checkbox:checked').length;
        document.querySelector('.selected-count').textContent = count + ' room(s) selected';
    });
});
</script>

<style>
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}
.room-card-admin {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}
.room-card-admin:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.room-card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.room-card-header .room-number {
    font-weight: 600;
    flex: 1;
}
.room-card-body {
    padding: 1rem;
}
.room-card-body h4 {
    margin-bottom: 0.75rem;
}
.room-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.room-info div {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #64748b;
}
.room-info i {
    width: 16px;
    color: #0a192f;
}
.room-card-footer {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.5rem;
}
.bulk-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.selected-count {
    color: #64748b;
    font-size: 0.9rem;
}
.room-card-admin.available .room-card-header {
    border-left: 4px solid #059669;
}
.room-card-admin.booked .room-card-header {
    border-left: 4px solid #d97706;
}
.room-card-admin.occupied .room-card-header {
    border-left: 4px solid #2563eb;
}
.room-card-admin.maintenance .room-card-header {
    border-left: 4px solid #dc2626;
}
</style>