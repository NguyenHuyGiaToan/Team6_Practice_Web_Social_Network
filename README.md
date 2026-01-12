📱 Mạng Xã Hội TSix (Social Network Project)
Dự án phát triển nền tảng mạng xã hội cơ bản, hỗ trợ kết nối người dùng, tương tác bài viết và quản trị nội dung.

📁 Cấu trúc thư mục (Folder Structure)
Dự án được tổ chức theo cấu trúc module để tránh xung đột:

/admin: Chứa các tính năng dành cho quản trị viên (Thanh Ngọc, Trọng Minh).

/assets: Tài nguyên tĩnh gồm /css và /images.

/includes: Chứa các file logic dùng chung (config.php, db.php, functions.php).

/uploads: Thư mục lưu trữ hình ảnh tải lên (avatars, bài viết).

index.php: Trang chủ hiển thị Bảng tin (News Feed).

login.php / register.php: Hệ thống xác thực người dùng.

🛠️ Công nghệ sử dụng
Ngôn ngữ: PHP (mô hình truyền thống).

Cơ sở dữ liệu: MySQL (sử dụng thư viện kết nối PDO).

Giao diện: HTML5, CSS3, JavaScript (AJAX).

Quản lý mã nguồn: Git/GitHub.

👥 Phân công nhiệm vụ (Task Assignment)
Dựa trên kế hoạch đã thống nhất:

Gia Toàn (Leader): Phân tích yêu cầu, thiết kế DB, thiết lập Core (config, db, functions) và hệ thống thông báo.

Duy Long: Xử lý xác thực (Login/Register), quản lý tài khoản, tương tác (Like/Comment).

Thị Như: Thực hiện Bảng tin (News Feed), bài viết (Post) và logic hiển thị.

Bích Ngân: Thiết kế UI/UX cho toàn bộ website.

Thanh Ngọc: Quản trị người dùng và nội dung vi phạm.

Trọng Minh: Báo cáo, Dashboard Admin và kiểm duyệt từ khóa nhạy cảm.

🚀 Quy trình làm việc với Git (Team Workflow)
Để tránh xung đột code, tất cả thành viên bắt buộc tuân thủ quy trình sau:

Bước 1: Chuẩn bị
Mời thành viên vào Collaborators trên GitHub.

Clone dự án về máy: git clone [(https://github.com/NguyenHuyGiaToan/Team6_Practice_Web_Social_Network.git)].

Bước 2: Thực hiện Task hàng ngày
Cập nhật code mới nhất: Luôn chạy git pull origin main trước khi bắt đầu code.

Tạo nhánh riêng: Không code trực tiếp trên main. Sử dụng lệnh: git checkout -b feature/ten-task.

Lưu tiến độ: git add . và git commit -m "Mô tả công việc đã làm".

Đẩy code: git push origin feature/ten-task.

Bước 3: Gộp code (Review)
Thành viên tạo Pull Request (PR) trên GitHub.

Trưởng nhóm (Gia Toàn) kiểm tra code và thực hiện Merge vào nhánh main.

⚠️ Lưu ý quan trọng
Thứ tự ưu tiên: Phải xong hệ thống Xác thực (Duy Long) mới thực hiện News Feed (Thị Như) để có dữ liệu Session.

Bảo mật: Luôn dùng hàm sanitizeInput() trong functions.php để xử lý dữ liệu từ Form.

Database: Khi thay đổi cấu trúc bảng, phải báo cho Leader để cập nhật file SQL chung.
