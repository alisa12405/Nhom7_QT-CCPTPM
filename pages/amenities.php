<?php 
require_once '../api/config/db.php';
$page_title = 'Tiện ích LuxStay'; 
include '../includes/header.php'; 
?>
//06062005
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    .amenity-card {
        transition: all 0.3s ease;
        border-radius: 20px;
        overflow: hidden;
    }
    .amenity-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
    .icon-wrapper {
        width: 100px;
        height: 100px;
        background-color: #fff9e6;
        color: #ffc107;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin: 0 auto;
        transition: 0.3s;
    }
    .amenity-card:hover .icon-wrapper {
        background-color: #ffc107;
        color: white;
    }
</style>

<div class="container my-5 py-4">
    <div class="text-center mb-5">
        <h6 class="text-uppercase text-primary fw-bold letter-spacing-2">LuxStay Services</h6>
        <h2 class="display-5 fw-bold mt-2">DỊCH VỤ & TIỆN ÍCH</h2>
        <div class="mx-auto bg-primary mt-3" style="width: 60px; height: 3px; border-radius: 2px;"></div>
        <p class="text-muted mt-3 fs-5">Tận hưởng những giây phút thư giãn tuyệt vời nhất tại hệ thống của chúng tôi</p>
    </div>
    
    

    <div class="row g-4">
        <?php 
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->query("SELECT * FROM amenities ORDER BY id DESC");
            $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($amenities) > 0):
                foreach($amenities as $a): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm p-5 text-center amenity-card">
                            <div class="icon-wrapper mb-4">
                                <i class="fa-solid <?= htmlspecialchars($a['icon'] ?? 'fa-concierge-bell'); ?> fa-3x"></i>
                            </div>
                            <h4 class="fw-bold mb-3"><?= htmlspecialchars($a['name']); ?></h4>
                            <p class="text-secondary line-height-base">
                                <?= htmlspecialchars($a['description']); ?>
                            </p>
                            <div class="mt-auto">
                                <span class="text-primary small fw-bold text-uppercase">Phục vụ 24/7</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; 
            else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Hiện tại chúng tôi đang cập nhật thêm dịch vụ mới...</p>
                </div>
            <?php endif;
        } catch (Exception $e) {
            echo "<div class='alert alert-danger w-100'>Lỗi hệ thống: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
