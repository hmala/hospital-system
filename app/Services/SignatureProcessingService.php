<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class SignatureProcessingService
{
    /**
     * معالجة وتفريغ وحفظ صورة التوقيع كـ PNG شفاف عالي الدقة
     *
     * @param UploadedFile|string $input إما ملف مرفوع أو Base64 Data URL
     * @param int|string $doctorId معرف الطبيب
     * @param int $threshold حساسية إزالة البياض (0 - 255، الافتراضي 200)
     * @return string|null المسار النسبي العام للصورة المحفوظة
     */
    public static function processAndSave($input, $doctorId, int $threshold = 200): ?string
    {
        $rawImageString = null;

        if ($input instanceof UploadedFile) {
            $rawImageString = file_get_contents($input->getRealPath());
        } elseif (is_string($input)) {
            if (str_starts_with($input, 'data:image')) {
                // Base64 data URI
                $parts = explode(',', $input, 2);
                $rawImageString = base64_decode($parts[1] ?? '');
            } elseif (file_exists($input)) {
                $rawImageString = file_get_contents($input);
            }
        }

        if (empty($rawImageString)) {
            return null;
        }

        // إنشاء صورة من السلسلة الخام
        $source = @imagecreatefromstring($rawImageString);
        if (!$source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // إنشاء صورة جديدة تدعم الشفافية الكاملة (TrueColor)
        $output = imagecreatetruecolor($width, $height);
        imagealphablending($output, false);
        imagesavealpha($output, true);

        // تعبئة الصورة بلون شفاف بالكامل
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefilledrectangle($output, 0, 0, $width, $height, $transparent);

        // متغيرات للقص التلقائي (Bounding Box)
        $minX = $width;
        $maxX = 0;
        $minY = $height;
        $maxY = 0;
        $hasVisiblePixels = false;

        // فحص البكسلات وعزل الخلفية البيضاء/الفاتحة
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($source, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $sourceAlpha = ($rgba >> 24) & 0x7F;

                // إذا كانت الصورة الأصلية تحتوي بالفعل على بكسل شفاف
                if ($sourceAlpha >= 120) {
                    continue;
                }

                // حساب لمعان البكسل (Luminance)
                $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;

                // إذا كان البكسل أبيض أو فاتحاً جداً (خلفية الورقة)
                if ($lum >= $threshold) {
                    // خلفية شفافة تماماً
                    continue;
                }

                // حساب الشفافية التدريجية للحواف لتكون ناعمة (Anti-Aliasing)
                // بين ($threshold - 40) و $threshold
                $featherStart = max(50, $threshold - 50);
                if ($lum > $featherStart) {
                    $ratio = ($lum - $featherStart) / ($threshold - $featherStart);
                    $alpha = (int) round($ratio * 127);
                } else {
                    $alpha = 0; // معتم تماماً (حبر قوي)
                }

                // تعزيز تباين لون الحبر (إذا كان أزرق أو داكن لجعله نقياً في الطباعة)
                $darkR = (int) max(0, $r * 0.85);
                $darkG = (int) max(0, $g * 0.85);
                $darkB = (int) max(0, $b * 0.95);

                $pixelColor = imagecolorallocatealpha($output, $darkR, $darkG, $darkB, $alpha);
                imagesetpixel($output, $x, $y, $pixelColor);

                // تحديث حدود القص
                if ($alpha < 110) {
                    $hasVisiblePixels = true;
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }

        imagedestroy($source);

        // مسار حفظ التوقيع
        $dirPath = public_path('images/signatures');
        if (!File::exists($dirPath)) {
            File::makeDirectory($dirPath, 0755, true, true);
        }

        $fileName = 'doctor_' . $doctorId . '.png';
        $fullPath = $dirPath . DIRECTORY_SEPARATOR . $fileName;

        // إذا وُجدت بكسلات للتوقيع، نقوم بالقص التلقائي لإزالة الهوامش الفارغة
        if ($hasVisiblePixels && $maxX > $minX && $maxY > $minY) {
            $padding = 10;
            $cropX = max(0, $minX - $padding);
            $cropY = max(0, $minY - $padding);
            $cropWidth = min($width - $cropX, ($maxX - $minX) + ($padding * 2));
            $cropHeight = min($height - $cropY, ($maxY - $minY) + ($padding * 2));

            $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
            imagefilledrectangle($cropped, 0, 0, $cropWidth, $cropHeight, $transparent);

            imagecopy($cropped, $output, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);
            imagedestroy($output);

            imagepng($cropped, $fullPath, 9);
            imagedestroy($cropped);
        } else {
            imagepng($output, $fullPath, 9);
            imagedestroy($output);
        }

        return 'images/signatures/' . $fileName;
    }
}
