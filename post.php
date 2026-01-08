<?php
// post.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// --- XỬ LÝ ĐĂNG BÀI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content_raw = trim($_POST['content'] ?? '');
    $privacy = $_POST['privacy'] ?? 'public'; // public, friends, private
    $feeling_text = trim($_POST['feeling_text'] ?? ''); // Lấy text cảm xúc (VD: đang cảm thấy Hạnh phúc)
    
    // Gộp cảm xúc vào nội dung bài viết (hoặc lưu cột riêng nếu DB có cột Mood)
    // Ở đây mình gộp vào content để đơn giản hóa hiển thị
    $final_content = $content_raw;
    if (!empty($feeling_text)) {
        // Có thể lưu dạng HTML hoặc text tuỳ ý
        $final_content .= "\n\n[Activity: " . $feeling_text . "]"; 
    }

    if (empty($final_content) && empty($_FILES['image']['name'])) {
        $message = "Vui lòng nhập nội dung hoặc chọn ảnh!";
    } else {
        // --- [FIX 1] XỬ LÝ TRẠNG THÁI (STATUS) ---
        // Mapping: public/friends -> active, private -> private
        $status_db = ($privacy === 'private') ? 'private' : 'active';
        
        // --- [FIX 2] SỬA CÂU QUERY ---
        // Thay đổi: VALUES (?, ?, ?, NOW()) -> 3 dấu hỏi cho 3 biến
        $stmt = mysqli_prepare($conn, "INSERT INTO Posts (FK_UserID, Content, Status, CreatedAt) VALUES (?, ?, ?, NOW())");
        
        // Bind 3 tham số: UserID (i), Content (s), Status (s)
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $final_content, $status_db);
        
        if (mysqli_stmt_execute($stmt)) {
            $post_id = mysqli_insert_id($conn);
            
            // Xử lý ảnh
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    $image_name = uniqid() . '.' . $ext;
                    $upload_path = __DIR__ . "/uploads/posts/" . $image_name;
                    if (!is_dir(dirname($upload_path))) mkdir(dirname($upload_path), 0777, true);
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $img_stmt = mysqli_prepare($conn, "INSERT INTO Post_Images (FK_PostID, ImageUrl) VALUES (?, ?)");
                        mysqli_stmt_bind_param($img_stmt, "is", $post_id, $image_name);
                        mysqli_stmt_execute($img_stmt);
                    }
                }
            }
            header("Location: index.php");
            exit();
        } else {
            $message = "Lỗi hệ thống: " . mysqli_error($conn);
        }
    }
}

// Lấy thông tin user
$stmt = mysqli_prepare($conn, "SELECT FullName, Avatar FROM Users WHERE UserID = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$current_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$avatar_url = !empty($current_user['Avatar']) 
    ? 'uploads/avatars/' . htmlspecialchars($current_user['Avatar']) 
    : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['FullName']) . '&background=8B1E29&color=fff&size=200';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài viết</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        
        body { 
            background: rgba(244, 244, 244, 0.8);
            height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; 
        }

        .post-wrapper {
            background: #fff; width: 500px; max-width: 95%; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex; flex-direction: column;
            animation: fadeIn 0.2s ease-out; position: relative; max-height: 90vh;
        }

        @keyframes fadeIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        /* Header */
        .modal-header {
            padding: 15px 20px; border-bottom: 1px solid #e4e6eb; position: relative; text-align: center;
        }
        .modal-header h2 { font-size: 1.25rem; font-weight: 700; color: #050505; margin: 0; }
        .close-btn {
            position: absolute; right: 15px; top: 12px; width: 36px; height: 36px;
            background: #e4e6eb; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.2rem; color: #606770; text-decoration: none;
        }
        .close-btn:hover { background: #d8dadf; }

        /* Body */
        .modal-body { padding: 15px; overflow-y: auto; }

        .user-profile { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .user-profile img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        
        .user-meta { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; font-size: 0.95rem; }
        .feeling-display { font-weight: 400; color: #65676b; font-size: 0.9rem; }

        /* Dropdown Privacy Style */
        .privacy-select {
            margin-top: 4px; border: none; background: #e4e6eb; padding: 4px 8px;
            border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; color: #050505; outline: none;
            width: fit-content;
        }

        .input-area {
            width: 100%; border: none; outline: none; font-size: 1.5rem; resize: none; min-height: 100px;
            font-family: inherit; margin-bottom: 10px;
        }
        .input-area::placeholder { color: #65676b; }

        /* Image Preview */
        .img-preview-box { display: none; position: relative; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; padding: 5px; }
        .img-preview-box img { width: 100%; max-height: 250px; object-fit: cover; border-radius: 6px; }
        .remove-img { position: absolute; top: 10px; right: 10px; background: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center; font-weight: bold; }

        /* Add to Post Strip */
        .add-to-post {
            border: 1px solid #e4e6eb; border-radius: 8px; padding: 10px 15px;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .add-text { font-weight: 600; font-size: 0.95rem; color: #050505; }
        .add-icons { display: flex; gap: 8px; }
        .icon-item {
            width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.3rem; transition: 0.2s;
        }
        .icon-item:hover { background: #f2f2f2; }
        
        /* Feeling Picker Grid (Giống ảnh 1) */
        .feeling-picker {
            display: none; /* Mặc định ẩn */
            grid-template-columns: 1fr 1fr; gap: 10px; padding: 10px; border: 1px solid #e4e6eb;
            border-radius: 8px; margin-bottom: 15px; max-height: 200px; overflow-y: auto;
        }
        .feeling-item {
            display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 6px; cursor: pointer;
        }
        .feeling-item:hover { background: #f0f2f5; }
        .feeling-icon { font-size: 1.5rem; width: 30px; text-align: center; }
        .feeling-name { font-size: 0.9rem; font-weight: 500; color: #050505; }

        /* Submit Button */
        .btn-submit {
            width: 100%; padding: 12px; background: #8B1E29; color: white; border: none;
            border-radius: 6px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: 0.2s;
        }
        .btn-submit:disabled { background: #e4e6eb; color: #bcc0c4; cursor: not-allowed; }
        .alert-error { color: #d32f2f; font-size: 0.9rem; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>

    <div class="post-wrapper">
        <div class="modal-header">
            <h2>Tạo bài viết</h2>
            <a href="index.php" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <?php if($message): ?>
                    <div class="alert-error"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="user-profile">
                    <img src="<?php echo $avatar_url; ?>" alt="Avatar">
                    <div class="user-meta">
                        <div class="user-name">
                            <?php echo htmlspecialchars($current_user['FullName']); ?>
                            <span class="feeling-display" id="feelingDisplay"></span>
                        </div>
                        
                        <select name="privacy" class="privacy-select">
                            <option value="public" selected>&#xf0ac; Công khai</option>
                            <option value="friends">&#xf500; Bạn bè (Hạn chế)</option>
                            <option value="private">&#xf023; Chỉ mình tôi</option>
                        </select>
                    </div>
                </div>

                <textarea name="content" id="postContent" class="input-area" 
                    placeholder="Bạn đang nghĩ gì, <?php echo explode(' ', $current_user['FullName'])[0]; ?>?" 
                    oninput="checkInput()"></textarea>

                <div id="imgPreviewArea" class="img-preview-box">
                    <img src="" id="previewImgSrc">
                    <div class="remove-img" onclick="removeImage()">&times;</div>
                </div>

                <input type="hidden" name="feeling_text" id="feelingInput">

                <div class="feeling-picker" id="feelingPicker">
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy Hạnh phúc', '🙂')">
                        <span class="feeling-icon">🙂</span> <span class="feeling-name">Hạnh phúc (Happy)</span>
                    </div>
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy Được yêu', '🥰')">
                        <span class="feeling-icon">🥰</span> <span class="feeling-name">Được yêu (Loved)</span>
                    </div>
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy Đáng yêu', '😚')">
                        <span class="feeling-icon">😚</span> <span class="feeling-name">Đáng yêu (Lovely)</span>
                    </div>
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy Hào hứng', '🤩')">
                        <span class="feeling-icon">🤩</span> <span class="feeling-name">Hào hứng (Excited)</span>
                    </div>
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy Điên rồ', '🤪')">
                        <span class="feeling-icon">🤪</span> <span class="feeling-name">Điên rồ (Crazy)</span>
                    </div>
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy May mắn', '😇')">
                        <span class="feeling-icon">😇</span> <span class="feeling-name">May mắn (Blessed)</span>
                    </div>
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy Buồn', '😞')">
                        <span class="feeling-icon">😞</span> <span class="feeling-name">Buồn (Sad)</span>
                    </div>
                    <div class="feeling-item" onclick="selectFeeling('đang cảm thấy Biết ơn', '😄')">
                        <span class="feeling-icon">😄</span> <span class="feeling-name">Biết ơn (Thankful)</span>
                    </div>
                </div>

                <div class="add-to-post">
                    <div class="add-text">Thêm vào bài viết của bạn</div>
                    <div class="add-icons">
                        <div class="icon-item" onclick="document.getElementById('imageInput').click()">
                            <i class="fa-solid fa-image" style="color:#45bd62"></i>
                        </div>
                        <div class="icon-item" onclick="triggerTag()">
                            <i class="fa-solid fa-user-tag" style="color:#1877f2"></i>
                        </div>
                        <div class="icon-item" onclick="toggleFeelingPicker()">
                            <i class="fa-regular fa-face-smile" style="color:#f7b928"></i>
                        </div>
                    </div>
                </div>

                <input type="file" name="image" id="imageInput" accept="image/*" hidden onchange="previewImage(event)">

                <button type="submit" name="create_post" id="btnSubmit" class="btn-submit" disabled>Đăng</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('postContent').focus();

        // Xử lý xem trước ảnh
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                document.getElementById('previewImgSrc').src = URL.createObjectURL(file);
                document.getElementById('imgPreviewArea').style.display = 'block';
                checkInput();
            }
        }
        function removeImage() {
            document.getElementById('imageInput').value = '';
            document.getElementById('imgPreviewArea').style.display = 'none';
            checkInput();
        }

        // Bật tắt bảng chọn cảm xúc
        function toggleFeelingPicker() {
            const picker = document.getElementById('feelingPicker');
            picker.style.display = (picker.style.display === 'grid') ? 'none' : 'grid';
        }

        // Chọn cảm xúc
        function selectFeeling(text, icon) {
            // Hiển thị cạnh tên người dùng
            document.getElementById('feelingDisplay').innerHTML = ` ${icon} ${text}`;
            // Lưu vào input hidden
            document.getElementById('feelingInput').value = `${icon} ${text}`;
            // Ẩn bảng chọn
            document.getElementById('feelingPicker').style.display = 'none';
            checkInput();
        }

        // Chức năng Tag
        function triggerTag() {
            const textarea = document.getElementById('postContent');
            textarea.focus();
            textarea.value += " @"; // Thêm ký tự @ để tag
            checkInput();
        }

        function checkInput() {
            const content = document.getElementById('postContent').value.trim();
            const hasImage = document.getElementById('imageInput').files.length > 0;
            const hasFeeling = document.getElementById('feelingInput').value.length > 0;
            const btn = document.getElementById('btnSubmit');

            if (content.length > 0 || hasImage || hasFeeling) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") {
                window.location.href = 'index.php';
            }
        });
    </script>
</body>
</html>