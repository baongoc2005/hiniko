INTERNSHIP MANAGER SYSTEM - PHP + MYSQL

1. Copy folder internship_manager_project vào:
   C:\xampp\htdocs\internship_manager_project

2. Mở XAMPP:
   - Start Apache
   - Start MySQL

3. Import database:
   - Mở http://localhost/phpmyadmin
   - Chọn Import
   - Chọn file database.sql
   - Bấm Go

4. Mở website:
   http://localhost/internship_manager_project/index.php

4b. Tài khoản demo (mật khẩu chung: 123456):
   - admin@ischool.edu.vn      (Admin)     - quản lý toàn bộ hệ thống
   - lecturer1@ischool.edu.vn  (Giảng viên)- xem SV được phân công, chấm điểm GV
   - student1@ischool.edu.vn   (Sinh viên) - đăng ký vị trí, nộp nhật ký/hồ sơ, xem điểm
   - fpt@example.com           (Doanh nghiệp)- mở vị trí công ty mình, chấm điểm DN

   Phân quyền theo vai trò:
   - Admin: tất cả các trang (users, companies, positions, periods, duyệt đăng ký,
     phân công GVHD, tính điểm tổng kết, ghi compliance log).
   - Giảng viên: xem SV mình phụ trách (assignments, journals, files), chấm điểm GV
     trong evaluations, xem final grades và compliance của SV phụ trách.
   - Sinh viên: đăng ký vị trí ĐÚNG NGÀNH và còn quota; nộp nhật ký/hồ sơ; xem điểm
     và cảnh báo compliance của chính mình.
   - Doanh nghiệp: mở/xóa vị trí của công ty mình; chấm điểm DN cho SV đăng ký vào công ty.

   Tính năng backend chính:
   - Matching & Approval: kiểm tra đúng ngành (users.major vs positions.major) + quota.
   - Dual Evaluation: điểm cuối = Điểm DN x 0.6 + Điểm GV x 0.4 (cấu hình trong final_grades.php).
   - Compliance Check: tự động liệt kê SV thiếu nhật ký tuần / nộp trễ theo lịch đợt thực tập.

5. Chia theo thành viên:
   Member 1:
   - users.php
   - companies.php
   - positions.php
   - periods.php

   Member 2:
   - registrations.php
   - assignments.php
   - journals.php
   - upload_file.php

   Member 3:
   - evaluations.php
   - final_grades.php
   - compliance.php

6. File dùng chung:
   - config/db.php
   - includes/header.php
   - includes/footer.php
   - assets/style.css
   - index.php

7. Demo flow đề xuất:
   - Thêm user sinh viên / giảng viên
   - Thêm công ty
   - Thêm vị trí thực tập
   - Tạo đợt thực tập
   - Sinh viên đăng ký vị trí
   - Admin duyệt Approved
   - Phân công GVHD
   - Sinh viên nộp weekly journal / upload file
   - Nhập điểm doanh nghiệp + giảng viên
   - Tính final grade
   - Ghi nhận compliance log nếu có vi phạm
