<?php 
session_start();
require_once '../api/config/db.php'; 

// 1. Kiểm tra quyền Admin
// 1. Kiểm tra quyền Admin
// 1. Kiểm tra quyền Admin
// 1. Kiểm tra quyền Admin
// 1. Kiểm tra quyền Admin
// 1. Kiểm tra quyền Admin
// 1. Kiểm tra quyền Admin
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: ../pages/login.php'); exit;
}

$database = new Database();
$db = $database->getConnection();

// --- LOGIC THUẦN PHP: LẤY DỮ LIỆU CŨ KHI BẤM NÚT SỬA ---
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $stmt_edit = $db->prepare("SELECT * FROM amenities WHERE id = ?");
    $stmt_edit->execute([$_GET['edit_id']]);
    $edit_data = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}

$page_title = 'Quản Lý Tiện Ích';
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Quản Lý Tiện Ích (Admin)</h2>

    <div class="card mb-5 border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold <?= $edit_data ? 'text-warning' : 'text-primary' ?>">
            <i class="fas <?= $edit_data ? 'fa-edit' : 'fa-plus-circle' ?> me-2"></i>
            <?= $edit_data ? 'Chỉnh Sửa Tiện Ích: ' . htmlspecialchars($edit_data['name']) : 'Thêm Tiện Ích Mới' ?>
        </h5>
    </div>
    <div class="card-body p-4">
        <form action="../api/amenities.php?action=<?= $edit_data ? 'edit' : 'add' ?>" method="POST">
            
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-secondary">Tên tiện ích</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-tag"></i></span>
                        <input type="text" name="name" class="form-control" 
                               placeholder="VD: Wifi, Hồ bơi..." 
                               value="<?= $edit_data['name'] ?? '' ?>" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold text-secondary">Icon (FontAwesome)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-icons"></i></span>
                        <input type="text" name="icon" class="form-control" 
                               placeholder="fa-wifi" 
                               value="<?= $edit_data['icon'] ?? 'fa-wifi' ?>">
                    </div>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-semibold text-secondary">Mô tả ngắn gọn</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-comment-alt"></i></span>
                        <input type="text" name="description" class="form-control" 
                               placeholder="Mô tả dịch vụ này..." 
                               value="<?= $edit_data['description'] ?? '' ?>">
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <?php if ($edit_data): ?>
                    <a href="amenities.php" class="btn btn-light border px-4">Hủy bỏ</a>
                    <button type="submit" class="btn btn-warning px-4 fw-bold shadow-sm">Cập nhật ngay</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">Lưu mới tiện ích</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Icon</th>
                        <th>Tên</th>
                        <th>Mô Tả</th>
                        <th class="text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $stmt = $db->query("SELECT * FROM amenities ORDER BY id DESC");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="ps-3">#<?= $row['id'] ?></td>
                        <td><i class="fas <?= $row['icon'] ?>"></i></td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td class="text-center">
                            <a href="amenities.php?edit_id=<?= $row['id'] ?>" class="btn btn-sm btn-info text-white">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            
                            <a href="../api/amenities.php?action=delete&id=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Chắc chắn xóa?')">
                                <i class="fas fa-trash"></i> Xóa
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
