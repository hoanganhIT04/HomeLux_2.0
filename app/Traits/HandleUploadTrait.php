<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

trait HandleUploadTrait
{
    // Hàm upload file trực tiếp vào public/uploads
    public function uploadFile($file, $folder)
    {
        if (!$file) return null;

        // 1. Tạo tên file duy nhất
        $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();

        // 2. Đường dẫn vật lý lưu file (D:\MyCode\TMDT\ecommerce_new\public\uploads\...)
        $destinationPath = public_path('uploads/' . $folder);

        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }

        // 3. Chuyển file vào thư mục public
        $file->move($destinationPath, $filename);

        // 4. CHUẨN HÓA ĐƯỜNG DẪN LƯU VÀO DB (Tuyệt đối luôn có dấu / ở đầu)
        $dbPath = '/uploads/' . $folder . '/' . $filename;
        
        // Dùng preg_replace để gộp các dấu /// thừa (nếu có) thành 1 dấu / duy nhất
        return preg_replace('#/+#', '/', $dbPath); 
    }

    // Hàm xóa file trực tiếp trong public/uploads
    public function deleteFile($path)
    {
        if ($path) {
            // QUAN TRỌNG CHO WINDOWS: Phải cắt bỏ dấu / ở đầu tiên đi (thành 'uploads/...') 
            // Nếu không hàm public_path() trên Windows sẽ tìm nhầm ra ổ D:\uploads\...
            $relativePath = ltrim($path, '/');
            
            // Tìm đúng đường dẫn D:\MyCode\TMDT\ecommerce_new\public\uploads\...
            $physicalPath = public_path($relativePath);

            // Kiểm tra và xóa file vật lý
            if (File::exists($physicalPath)) {
                File::delete($physicalPath);
            }
        }
    }
}