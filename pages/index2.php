<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ PHP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            text-align: center;
            width: 450px;
        }

        h1 {
            color: #007BFF;
        }

        p {
            font-size: 18px;
        }

        .time {
            color: #555;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Xin chào!</h1>

    <p>
        Chào mừng bạn đến với website PHP.

	Trang này du?c t?o ra d? debug l?i d?ng b? du?ng dâxn file



	Hej hej
	thêm dòng này
	<?php for (int $i = 0; $i < 10; $i++)
		echo $i; ?>
	Thêm dòng này d? debug
    </p>

    <p>
        Phiên bản PHP:
        <strong><?php echo phpversion(); ?></strong>
    </p>

    <p class="time">
        Thời gian hiện tại:
        <strong>
            <?php
                date_default_timezone_set('Asia/Ho_Chi_Minh');
                echo date('d/m/Y H:i:s');
            ?>
        </strong>
    </p>
</div>

</body>
</html>
