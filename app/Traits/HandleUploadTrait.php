<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

trait HandleUploadTrait
{
    // Upload file và giữ nguyên tên file gốc (ví dụ: 1.jpg)
    public function uploadFile($file, $folder)
    {
        if (!$file) return null;

        // Lấy đúng tên file bạn tải lên (ví dụ: 2.jpg)
        // Dùng Str::slug cho phần tên để lỡ có dấu tiếng Việt thì không bị lỗi, giữ nguyên đuôi file
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($name) . '.' . $extension;

        $destinationPath = public_path('uploads/' . $folder);

        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }

        // Chuyển file
        $file->move($destinationPath, $filename);

        // Đảm bảo tuyệt đối có 1 dấu / ở đầu
        $dbPath = '/uploads/' . $folder . '/' . $filename;
        return preg_replace('#/+#', '/', $dbPath); 
    }

    // Hàm xóa file vật lý
    public function deleteFile($path)
    {
        if ($path) {
            $relativePath = ltrim($path, '/');
            $physicalPath = public_path($relativePath);

            if (File::exists($physicalPath)) {
                File::delete($physicalPath);
            }
        }
    }
}