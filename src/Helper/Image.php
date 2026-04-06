<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Helper;

use InvalidArgumentException;
use RuntimeException;
use GdImage;

class Image
{
    /** @var string $path Image Path */
    protected ?string $path = null;

    /** @var ?string $mime Image Mime Type */
    protected ?string $mime = null;

    /** @var ?int $width Image Width */
    protected ?int $width = null;

    /** @var ?int $height Image Height */
    protected ?int $height = null;

    /** @var ?GdImage $image */
    protected ?GdImage $image = null;

    /**
     * Set File Path
     * @param string $path Image File Path
     * @return static
     */
    public function path(string $path): static // Must Run First
    {
        // Destroy previous image if present
        if ($this->image) {
            imagedestroy($this->image);
        }
        $this->reset();

        if (!file_exists($path)) {
            throw new InvalidArgumentException("Image File Not Found: [{$path}]");
        }

        $info = getimagesize($path);
        if (!$info) {
            throw new RuntimeException("Not a Valid Image!");
        }

        $this->path = $path;
        $this->mime = $info['mime'];
        $this->width = $info[0];
        $this->height = $info[1];

        $this->image = match ($this->mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($this->path),
            'image/png'               => imagecreatefrompng($this->path),
            'image/gif'               => imagecreatefromgif($this->path),
            'image/webp'              => imagecreatefromwebp($this->path),
            'image/bmp'               => function_exists('imagecreatefrombmp')
                ? imagecreatefrombmp($this->path)
                : throw new RuntimeException("BMP not supported"),
            'image/avif'              => function_exists('imagecreatefromavif')
                ? imagecreatefromavif($this->path)
                : throw new RuntimeException("AVIF not supported"),
            default                   => throw new RuntimeException("Unsupported image type: {$this->mime}")
        };

        return $this;
    }

    /**
     * Resize Image
     * @param int $width Required Argument. Image Width.
     * @param int $height Required Argument. Image Height.
     * @param bool $keepAspect Optional Argument. Default is true
     * @return static
     */
    public function resize(int $width, int $height, bool $keepAspect = true): static
    {
        // Check Resources
        $this->checkResources();

        if ($keepAspect) {
            $ratio = $this->width / $this->height;
            if ($width / $height > $ratio) {
                $width = (int)($height * $ratio);
            } else {
                $height = (int)($width / $ratio);
            }
        }

        $resized = imagecreatetruecolor($width, $height);
        $this->preserveTransparency($resized);

        imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

        $this->image = $resized;
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * Crop Image
     * @param int $x Required Argument. Position of X.
     * @param int $y Required Argument. Position of Y.
     * @param int $width Required Argument.
     * @param int $height Required Argument.
     * @return static
     */
    public function crop(int $x, int $y, int $width, int $height): static
    {
        // Check Resources
        $this->checkResources();

        $cropped = imagecrop($this->image, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        ]);

        if ($cropped === false) {
            throw new RuntimeException("Failed to crop image.");
        }

        $this->image = $cropped;
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Make Thumbnail
     * @param int $width Required Argument.
     * @param int $height Required Argument.
     * @param string $mode Optional Argument. Default is 'fit'. Acceptable Modes Are 'fit' and 'cover'
     * @return static
     */
    public function thumbnail(int $width, int $height, string $mode = 'fit'): static
    {
        // Check Resources
        $this->checkResources();

        $mode = strtolower($mode);
        // Throw InvalidArgumentException if Mode is not Acceptable.
        if (!in_array($mode, ['fit', 'cover'])) {
            throw new InvalidArgumentException("Unsupported Mode: '{$mode}'");
        }

        if ($mode === 'cover') {
            $originalRatio = $this->width / $this->height;
            $targetRatio = $width / $height;

            if ($originalRatio > $targetRatio) {
                $newHeight = $height;
                $newWidth = (int) ($height * $originalRatio);
            } else {
                $newWidth = $width;
                $newHeight = (int) ($width / $originalRatio);
            }

            $this->resize($newWidth, $newHeight, false);

            $x = (int) (($newWidth - $width) / 2);
            $y = (int) (($newHeight - $height) / 2);

            return $this->crop($x, $y, $width, $height);
        }

        return $this->resize($width, $height, true);
    }

    /**
     * Text Watermark in Image
     * @param string $text Required Argument. Watermark Text.
     * @param int $gdfont Optional Argument. Default is 5.
     * @param array $rgb Optional Argument. Default is null for [255, 255, 255]
     * @param int $x Position of X. Optional Argument. Default is 10
     * @param int $y Position of Y. Optional Argument. Default is 20
     * @return static
     */
    public function watermark(string $text, int $gdfont = 5, ?array $rgb = null, int $x = 10, int $y = 20): static
    {
        // Check Resources
        $this->checkResources();

        $rgb = $rgb ?: [255, 255, 255];
        $gdColor = imagecolorallocate($this->image, ...$rgb);
        imagestring($this->image, max(1, min(5, $gdfont)), $x, $y, $text, $gdColor);
        return $this;
    }

    /**
     * Image Watermark in Image
     * @param string $logoPath Required Argument
     * @param int $x Position of X. Optional Argument. Default is 0
     * @param int $y Position of Y. Optional Argument. Default is 0
     * @param int $opacity Optional Argument. Default is 100
     * @return static
     */
    public function watermarkImage(string $logoPath, int $x = 0, int $y = 0, int $opacity = 100): static
    {
        // Check Resources
        $this->checkResources();

        if (!file_exists($logoPath)) {
            throw new InvalidArgumentException("Watermark image not found: $logoPath");
        }

        $data = file_get_contents($logoPath);
        if ($data === false) {
            throw new RuntimeException("Failed to read watermark image: {$logoPath}");
        }
        $logo = imagecreatefromstring($data);
        if ($logo === false) {
            throw new RuntimeException("Failed to create image resource from watermark: {$logoPath}");
        }
        if ($opacity === 100) {
            imagealphablending($this->image, true);
            imagecopy($this->image, $logo, $x, $y, 0, 0, imagesx($logo), imagesy($logo));
        } else {
            $this->mergeWithAlpha($this->image, $logo, $x, $y, $opacity);
        }
        imagedestroy($logo);

        return $this;
    }

    /**
     * Rotate Image
     * @param float|int $angle Required Argument
     * @return static
     */
    public function rotate(float|int $angle): static
    {
        // Check Resources
        $this->checkResources();

        $this->image = imagerotate($this->image, -$angle, 0);
        // Update dimensions after rotation
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
        return $this;
    }

    /**
     * Horizontal Image Flip
     * @return static
     */
    public function flipHorizontal(): static
    {
        // Check Resources
        $this->checkResources();

        imageflip($this->image, IMG_FLIP_HORIZONTAL);
        return $this;
    }

    /**
     * Vertical Image Flip
     * @return static
     */
    public function flipVertical(): static
    {
        // Check Resources
        $this->checkResources();

        imageflip($this->image, IMG_FLIP_VERTICAL);
        return $this;
    }

    /**
     * Gray Scale Image
     * @return static
     */
    public function grayscale(): static
    {
        // Check Resources
        $this->checkResources();

        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * Convert Image
     * @param string $format File Format
     * @return static
     */
    public function convertTo(string $format): static
    {
        // Check Resources
        $this->checkResources();

        $format = strtolower($format);

        $this->mime = match ($format) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'bmp'         => 'image/bmp',
            'avif'        => 'image/avif',
            default       => throw new InvalidArgumentException("Unsupported conversion format: [{$format}]"),
        };

        return $this;
    }

    /**
     * Save Image
     * @param $path Same Path
     * @param int $quality 0–100. For PNG, higher = less compression (larger file).
     * @return bool
     */
    public function save(string $path, int $quality = 100): bool
    {
        // Check Resources
        $this->checkResources();

        if (in_array($this->mime, ['image/png', 'image/gif'])) {
            imagealphablending($this->image, false);
            imagesavealpha($this->image, true);
        }
        return match ($this->mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($this->image, $path, $quality),
            'image/png'               => imagepng($this->image, $path, (int) round((100 - $quality) * 9 / 100)),
            'image/gif'               => imagegif($this->image, $path),
            'image/webp'              => imagewebp($this->image, $path, $quality),
            'image/bmp'               => function_exists('imagebmp')
                ? imagebmp($this->image, $path)
                : throw new RuntimeException("Cannot save BMP: not supported"),
            'image/avif'              => function_exists('imageavif')
                ? imageavif($this->image, $path, $quality)
                : throw new RuntimeException("Cannot save AVIF: not supported"),
            default                   => throw new RuntimeException("Cannot save unsupported image type: {$this->mime}")
        };
    }

    /**
     * Get Image Raw Data
     * @return void
     */
    public function show(): void
    {
        // Check Resources
        $this->checkResources();

        header("Content-Type: {$this->mime}");
        if (in_array($this->mime, ['image/png', 'image/gif'])) {
            imagealphablending($this->image, false);
            imagesavealpha($this->image, true);
        }

        match ($this->mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($this->image),
            'image/png'               => imagepng($this->image),
            'image/gif'               => imagegif($this->image),
            'image/webp'              => imagewebp($this->image),
            'image/bmp'               => function_exists('imagebmp')
                ? imagebmp($this->image)
                : throw new RuntimeException("Cannot output BMP: not supported"),
            'image/avif'              => function_exists('imageavif')
                ? imageavif($this->image)
                : throw new RuntimeException("Cannot output AVIF: not supported"),
            default                   => throw new RuntimeException("Cannot output unsupported image type: {$this->mime}")
        };

        imagedestroy($this->image);
        $this->reset();
    }

    /**
     * Base64 Image Output
     * @return string
     */
    public function toBase64(): string
    {
        // Check Resources
        $this->checkResources();

        ob_start();

        match ($this->mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($this->image),
            'image/png'               => imagepng($this->image),
            'image/gif'               => imagegif($this->image),
            'image/webp'              => imagewebp($this->image),
            'image/bmp'               => function_exists('imagebmp')
                ? imagebmp($this->image)
                : throw new RuntimeException("Cannot output BMP: not supported"),
            'image/avif'              => function_exists('imageavif')
                ? imageavif($this->image)
                : throw new RuntimeException("Cannot output AVIF: not supported"),
            default                   => throw new RuntimeException("Unsupported image type: {$this->mime}"),
        };

        $imageData = ob_get_clean();
        $base64 = base64_encode($imageData);
        $output = 'data:' . $this->mime . ';base64,' . $base64;
        $this->destroy();
        return $output;
    }

    /**
     * Get Image Info
     * @return array
     */
    public function info(): array
    {
        // Check Resources
        $this->checkResources();
        $result = [
            'width' => $this->width,
            'height' => $this->height,
            'mime' => $this->mime
        ];
        $this->reset();
        return $result;
    }

    /**
     * Destroy Saved GdImage From Memory
     * @return void
     */
    public function destroy(): void
    {
        // Check Resources
        $this->checkResources();

        if ($this->image) {
            imagedestroy($this->image);
        }
        $this->image = null;
    }

    /**
     * Delete the original source image file from disk.
     * @return bool True if deleted successfully, false otherwise.
     */
    public function unlink(): bool
    {
        // Check Resources
        $this->checkResources();

        if (file_exists($this->path)) {
            return unlink($this->path);
        }
        return false;
    }

    /*========================================================================================*/
    /*===================================== INTERNAL API =====================================*/
    /*========================================================================================*/
    /**
     * Keep Transparency for PNG and GIF
     * @param GdImage $image Required Argument
     * @return void
     */
    protected function preserveTransparency(GdImage $image): void
    {
        if (in_array($this->mime, ['image/png', 'image/gif'])) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
        }
    }

    /**
     * Reset All Properties
     * @return void
     */
    protected function reset(): void
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
        $this->path   = null;
        $this->mime   = null;
        $this->width  = null;
        $this->height = null;
        $this->image  = null;
    }

    /**
     * Check File & Resources is Valid
     * @return void
     * @throws RuntimeException
     */
    protected function checkResources(): void
    {
        if (in_array(null, [$this->path, $this->mime, $this->width, $this->height, $this->image])) {
            throw new RuntimeException("Run Image::path(\$path) To Set Image File Path");
        }
    }

    /**
     * Alpha-safe opacity merge for true-color images
     * @param GdImage $dst Destination image
     * @param GdImage $src Watermark image
     * @param int $x Position of X
     * @param int $y Position of Y
     * @param int $opacity 0–99
     * @return void
     */
    protected function mergeWithAlpha(GdImage $dst, GdImage $src, int $x, int $y, int $opacity): void
    {
        $w = imagesx($src);
        $h = imagesy($src);

        // Create a temp canvas matching the watermark size
        $cut = imagecreatetruecolor($w, $h);
        imagealphablending($cut, false);
        imagesavealpha($cut, true);

        // Copy the destination background into the temp canvas
        imagecopy($cut, $dst, 0, 0, $x, $y, $w, $h);

        // Copy the watermark on top
        imagecopy($cut, $src, 0, 0, 0, 0, $w, $h);

        // Merge the temp canvas back onto the destination with the desired opacity
        imagecopymerge($dst, $cut, $x, $y, 0, 0, $w, $h, $opacity);

        imagedestroy($cut);
    }
}
