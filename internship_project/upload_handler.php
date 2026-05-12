<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nộp hồ sơ thực tập - Người 2</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fafafa; padding: 40px; }
        .upload-card { max-width: 500px; margin: auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 5px solid #34a853; }
        h2 { text-align: center; color: #333; }
        .file-input-group { border: 2px dashed #34a853; padding: 30px; text-align: center; border-radius: 8px; margin: 20px 0; background: #f9fff9; }
        input[type="file"] { margin-bottom: 10px; }
        .btn-upload { width: 100%; background: #34a853; color: white; border: none; padding: 12px; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-upload:hover { background: #2d8e47; }
        p.note { font-size: 13px; color: #666; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="upload-card">
        <h2> Nộp Tập tin Hồ sơ (Files)</h2>
        <p style="text-align:center; color: #666;">Dành cho Sinh viên đã đăng ký</p>
        
        <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="registration_id" value="1"> <!-- ID từ bảng registrations -->
            
            <div class="file-input-group">
                <input type="file" name="internship_file" id="file" required>
                <label for="file"><br>Chọn CV, Bảng điểm hoặc Minh chứng (PDF, DOCX, PNG)</label>
            </div>
            
            <button type="submit" class="btn-upload">Tải lên hệ thống</button>
        </form>
        
        <p class="note"><b>Lưu ý:</b> Tập tin tải lên sẽ được lưu vào bảng <code>Tap_tin</code> và liên kết trực tiếp với mã đăng ký của bạn.</p>
    </div>
</body>
</html>