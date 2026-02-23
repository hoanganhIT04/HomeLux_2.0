<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

trait HandleUploadTrait
{
    // Upload file và giữ nguyên tên file gốc (ví dụ: 1.jpg)
    public function uploadFile($file, $folder, $customName = null)
    {
        if (!$file)
            return null;

        $extension = $file->getClientOriginalExtension();

        // Nếu truyền tên tùy chỉnh (ví dụ ID: 1) -> File sẽ là 1.jpg
        // Nếu không truyền -> Giữ nguyên tên file gốc
        if ($customName) {
            $filename = $customName . '.' . $extension;
        } else {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = Str::slug($name) . '.' . $extension;
        }

        $destinationPath = public_path('uploads/' . $folder);

        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }

        // Chuyển file vào thư mục public
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