<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php 
require_once __DIR__ . '/../includes/header.php';
$page_title = 'LuxStay Hotel'; 
require_once __DIR__ . '/../api/config/db.php'; 
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .hero-banner {
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                    url('<?= BASE_URL ?>/assets/image/banner.jpg'); 
        background-size: cover;
        background-position: center;
        padding: 100px 0;
        color: white;
        text-align: center;
    }
    .search-box {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin-top: -80px; 
        position: relative;
        z-index: 10;
    }
    .search-box label { color: #333; font-weight: bold; }
    .room-card img { transition: 0.3s; }
    .room-card:hover img { transform: scale(1.05); }
    
    /* Thêm cái này để đảm bảo row luôn hoạt động */
    .row { display: flex; flex-wrap: wrap; }
</style>

<div class="hero-banner">
    <div class="container">
        <h1 class="display-4 fw-bold">Chào mừng đến <strong>LuxStay</strong></h1>
        <p class="lead">Đặt phòng cao cấp với tiện ích 5 sao</p>
    </div>
</div>

<div class="container">
    <div class="search-box">
        <h5 class="mb-3 text-dark"><i class="fas fa-search"></i> Tìm Phòng Có Sẵn</h5>
        <form method="POST" action="<?= BASE_URL ?>/pages/rooms.php">
            <div class="row g-3">
                <div class="col-md-3">
                    <label>Check-in</label>
                    <input type="date" class="form-control" name="checkin" required>
                </div>
                <div class="col-md-3">
                    <label>Check-out</label>
                    <input type="date" class="form-control" name="checkout" required>
                </div>
                <div class="col-md-3">
                    <label>Khách</label>
                    <input type="number" class="form-control" name="guests" min="1" value="2">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100 py-2 text-white shadow-sm"><i class="fas fa-search"></i> Tìm ngay</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container my-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold" style="letter-spacing: 2px;">PHÒNG</h2>
        <div class="mx-auto" style="width: 60px; height: 3px; background: #000;"></div>
    </div>

    <div class="row"> <?php
    $database = new Database(); 
    $db = $database->getConnection(); 
    $rooms = $db->query("SELECT * FROM room_types LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rooms as $room):
    ?>
        <div class="col-md-4 mb-4"> <div class="card h-100 shadow-sm border-0" style="border-radius: 10px; overflow: hidden;">
                <img src="<?= BASE_URL ?>/assets/rooms/<?php echo htmlspecialchars($room['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Room Image">
                
                <div class="card-body"> <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($room['name']); ?></h5>
                    <p class="text-danger fw-bold mb-2">
                        <?php echo number_format($room['price_per_night'], 0, ',', '.'); ?> VND / đêm
                    </p>
                    <p class="small text-secondary mb-0">Cơ sở: LuxStay Center</p>
                    <hr>
                    <a href="<?= BASE_URL ?>/pages/roomdetail.php?id=<?php echo $room['id']; ?>" class="text-dark small fw-bold text-decoration-none">Chi tiết phòng</a>
                </div> </div> </div> <?php endforeach; ?>

    </div> 
    
<div class="container my-5 bg-light p-5 rounded-4">
    <div class="text-center mb-5">
        <h2 class="fw-bold">TIỆN ÍCH TIÊU CHUẨN</h2>
    </div>
    <div class="row">
        <?php 
        try {
            $query = "SELECT * FROM amenities LIMIT 3";
            $stmt = $db->query($query);
            $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($amenities as $a): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 bg-transparent">
                        <div class="card-body text-center">
                            <i class="fas <?php echo $a['icon'] ?? 'fa-star'; ?> fa-3x text-primary mb-3"></i>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($a['name']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars($a['description'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; 
        } catch (Exception $e) {
            echo "<div class='alert alert-danger w-100'>Lỗi CSDL: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';; ?>
