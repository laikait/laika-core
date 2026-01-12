<?php

/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Laika\Core\Helper;

use InvalidArgumentException;
use GdImage;

class Image
{
    /**
     * @var string $path Image Path
     */
    protected string $path;

    /**
     * @var string $mime Image Mime Type
     */
    protected string $mime;

    /**
     * @var int $width Image Width
     */
    protected int $width;

    /**
     * @var int $height Image Height
     */
    protected int $height;

    /**
     * @var GdImage $image Image Height
     */
    protected GdImage $image;

    /**
     * @param string $file Resource Image Filepath
     */
    public function __construct(string $file)
    {
        if (!\file_exists($file)) {
            throw new \InvalidArgumentException("Image File Not Found: [{$file}]");
        }

        $info = \getimagesize($file);
        if (!$info) {
            throw new \RuntimeException("Not a Valid Image!");
        }

        $this->path = $file;
        $this->mime = $info['mime'];
        $this->width = $info[0];
        $this->height = $info[1];

        $this->load();
    }

    /**
     * Resize Image
     * @param int $width Required Argument. Image Width.
     * @param int $height Required Argument. Image Height.
     * @param bool $keepAspect Optional Argument. Default is true
     * @return self
     */
    public function resize(int $width, int $height, bool $keepAspect = true): self
    {
        if ($keepAspect) {
            $ratio = $this->width / $this->height;
            if ($width / $height > $ratio) {
                $width = (int)($height * $ratio);
            } else {
                $height = (int)($width / $ratio);
            }
        }

        $resized = \imagecreatetruecolor($width, $height);
        $this->preserveTransparency($resized);

        \imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

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
     * @return self
     */
    public function crop(int $x, int $y, int $width, int $height): self
    {
        $cropped = \imagecrop($this->image, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        ]);

        if ($cropped === false) {
            throw new \RuntimeException("Failed to crop image.");
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
     * @return self
     */
    public function thumbnail(int $width, int $height, string $mode = 'fit'): static
    {
        $mode = \strtolower($mode);
        // Throw InvalidArgumentException if Mode is not Acceptable.
        if (!\in_array($mode, ['fit', 'cover'])) {
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
     * @return self
     */
    public function watermark(string $text, int $gdfont = 5, ?array $rgb = null, int $x = 10, int $y = 20): self
    {
        $rgb = $rgb ?: [255, 255, 255];
        $gdColor = \imagecolorallocate($this->image, ...$rgb);
        \imagestring($this->image, \max(1, \min(5, $gdfont)), $x, $y, $text, $gdColor);
        return $this;
    }

    /**
     * Image Watermark in Image
     * @param string $logoPath Required Argument
     * @param int $x Position of X. Optional Argument. Default is 0
     * @param int $y Position of Y. Optional Argument. Default is 0
     * @param int $opacity Optional Argument. Default is 0
     * @return self
     */
    public function watermarkImage(string $logoPath, int $x = 0, int $y = 0, int $opacity = 100): self
    {
        if (!\file_exists($logoPath)) {
            throw new \InvalidArgumentException("Watermark image not found: $logoPath");
        }

        $logo = \imagecreatefromstring(\file_get_contents($logoPath));
        \imagecopymerge($this->image, $logo, $x, $y, 0, 0, \imagesx($logo), \imagesy($logo), $opacity);
        \imagedestroy($logo);

        return $this;
    }

    /**
     * Rotate Image
     * @param float|int $angle Required Argument
     * @return self
     */
    public function rotate(float|int $angle): self
    {
        $this->image = \imagerotate($this->image, -$angle, 0);
        return $this;
    }

    /**
     * Horizontal Image Flip
     * @return self
     */
    public function flipHorizontal(): self
    {
        $width = \imagesx($this->image);
        $height = \imagesy($this->image);
        $flipped = \imagecreatetruecolor($width, $height);
        $this->preserveTransparency($flipped);
        for ($x = 0; $x < $width; $x++) {
            \imagecopy($flipped, $this->image, $width - $x - 1, 0, $x, 0, 1, $height);
        }

        $this->image = $flipped;
        return $this;
    }

    /**
     * Vertical Image Flip
     * @return self
     */
    public function flipVertical(): self
    {
        $width = \imagesx($this->image);
        $height = \imagesy($this->image);
        $flipped = \imagecreatetruecolor($width, $height);
        $this->preserveTransparency($flipped);

        for ($y = 0; $y < $height; $y++) {
            \imagecopy($flipped, $this->image, 0, $height - $y - 1, 0, $y, $width, 1);
        }

        $this->image = $flipped;
        return $this;
    }

    /**
     * Gray Scale Image
     * @return self
     */
    public function grayscale(): self
    {
        \imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * Convert Image
     * @return self
     */
    public function convertTo(string $format): self
    {
        $format = \strtolower($format);

        $this->mime = match ($format) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'bmp'         => 'image/bmp',
            'avif'        => 'image/avif',
            default       => throw new \InvalidArgumentException("Unsupported conversion format: [{$format}]"),
        };

        return $this;
    }

    /**
     * Save Image
     * @return bool
     */
    public function save(string $path, int $quality = 100): bool
    {
        if (\in_array($this->mime, ['image/png', 'image/gif'])) {
            \imagealphablending($this->image, false);
            \imagesavealpha($this->image, true);
        }
        return match ($this->mime) {
            'image/jpeg', 'image/jpg' => \imagejpeg($this->image, $path, $quality),
            'image/png'               => \imagepng($this->image, $path, (int) \round((100 - $quality) * 9 / 100)),
            'image/gif'               => \imagegif($this->image, $path),
            'image/webp'              => \imagewebp($this->image, $path, $quality),
            'image/bmp'               => \function_exists('imagebmp')
                ? \imagebmp($this->image, $path)
                : throw new \RuntimeException("Cannot save BMP: not supported"),
            'image/avif'              => function_exists('imageavif')
                ? \imageavif($this->image, $path, $quality)
                : throw new \RuntimeException("Cannot save AVIF: not supported"),
            default                   => throw new \RuntimeException("Cannot save unsupported image type: {$this->mime}")
        };
    }

    /**
     * Get Image Raw Data
     * @return void
     */
    public function output(): void
    {
        \header("Content-Type: {$this->mime}");
        if (in_array($this->mime, ['image/png', 'image/gif'])) {
            \imagealphablending($this->image, false);
            \imagesavealpha($this->image, true);
        }

        match ($this->mime) {
            'image/jpeg', 'image/jpg' => \imagejpeg($this->image),
            'image/png'               => \imagepng($this->image),
            'image/gif'               => \imagegif($this->image),
            'image/webp'              => \imagewebp($this->image),
            'image/bmp'               => \function_exists('imagebmp')
                ? \imagebmp($this->image)
                : throw new \RuntimeException("Cannot output BMP: not supported"),
            'image/avif'              => \function_exists('imageavif')
                ? imageavif($this->image)
                : throw new \RuntimeException("Cannot output AVIF: not supported"),
            default                   => throw new \RuntimeException("Cannot output unsupported image type: {$this->mime}")
        };

        \imagedestroy($this->image);
    }

    /**
     * Base64 Image Output
     * @return string
     */
    public function toBase64(): string
    {
        \ob_start();

        match ($this->mime) {
            'image/jpeg', 'image/jpg' => \imagejpeg($this->image),
            'image/png'               => \imagepng($this->image),
            'image/gif'               => \imagegif($this->image),
            'image/webp'              => \imagewebp($this->image),
            'image/bmp'               => \function_exists('imagebmp')
                ? \imagebmp($this->image)
                : throw new \RuntimeException("Cannot output BMP: not supported"),
            'image/avif'              => \function_exists('imageavif')
                ? \imageavif($this->image)
                : throw new \RuntimeException("Cannot output AVIF: not supported"),
            default                   => throw new \RuntimeException("Unsupported image type: {$this->mime}"),
        };

        $imageData = \ob_get_clean();
        $base64 = \base64_encode($imageData);

        return 'data:' . $this->mime . ';base64,' . $base64;
    }

    /**
     * Get Image Width
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get Image Height
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Get Image Mime Type
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * Destroy Saved GdImage From Memory
     * @return void
     */
    public function destroy(): void
    {
        if ($this->image) {
            \imagedestroy($this->image);
        }
    }

    /**
     * Delete the original source image file from disk.
     * @return bool True if deleted successfully, false otherwise.
     */
    public function unlink(): bool
    {
        if (\file_exists($this->path)) {
            return \unlink($this->path);
        }
        return false;
    }

    /**
     * Load GdImage of Resource Image
     * @return void
     */
    protected function load(): void
    {
        $this->image = match ($this->mime) {
            'image/jpeg', 'image/jpg' => \imagecreatefromjpeg($this->path),
            'image/png'               => \imagecreatefrompng($this->path),
            'image/gif'               => \imagecreatefromgif($this->path),
            'image/webp'              => \imagecreatefromwebp($this->path),
            'image/bmp'               => \function_exists('imagecreatefrombmp')
                ? \imagecreatefrombmp($this->path)
                : throw new \RuntimeException("BMP not supported"),
            'image/avif'              => \function_exists('imagecreatefromavif')
                ? \imagecreatefromavif($this->path)
                : throw new \RuntimeException("AVIF not supported"),
            default                   => throw new \RuntimeException("Unsupported image type: {$this->mime}")
        };
    }

    /**
     * Keep Transparency for PNG and GIF
     * @param GdImage $image Required Argument
     * @return void
     */
    protected function preserveTransparency(GdImage $image): void
    {
        if (\in_array($this->mime, ['image/png', 'image/gif'])) {
            \imagealphablending($image, false);
            \imagesavealpha($image, true);
            $transparent = \imagecolorallocatealpha($image, 0, 0, 0, 127);
            \imagefilledrectangle($image, 0, 0, \imagesx($image), \imagesy($image), $transparent);
        }
    }
}
