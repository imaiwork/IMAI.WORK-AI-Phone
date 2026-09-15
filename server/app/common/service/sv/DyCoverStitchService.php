<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 置顶封面拼接。只负责拼图，不调 OCR、不计费。
 * direction 仅支持 h（横向）/ v（纵向）；both 由调用方各调一次。
 */
class DyCoverStitchService
{
    /**
     * @param list<string> $imagePaths 本地图片路径
     * @param string $direction h|v
     * @return string 拼接后的本地 jpg 路径；仅 1 张时返回原路径
     * @throws \RuntimeException 失败时消息为「封面拼接失败」
     */
    public function stitch(array $imagePaths, string $direction): string
    {
        try {
            $paths = [];
            foreach ($imagePaths as $path) {
                $resolved = $this->resolveLocalPath((string)$path);
                if ($resolved === '') {
                    throw new \RuntimeException('封面拼接失败');
                }
                $paths[] = $resolved;
            }

            if ($paths === []) {
                throw new \RuntimeException('封面拼接失败');
            }

            if (count($paths) === 1) {
                return $paths[0];
            }

            $direction = strtolower(trim($direction));
            if ($direction !== 'h' && $direction !== 'v') {
                throw new \RuntimeException('封面拼接失败');
            }

            $images = [];
            foreach ($paths as $path) {
                $image = $this->loadImage($path);
                if ($image === false) {
                    $this->destroyImages($images);
                    throw new \RuntimeException('封面拼接失败');
                }
                $images[] = $image;
            }

            $canvas = $this->compose($images, $direction);
            $this->destroyImages($images);
            if ($canvas === false) {
                throw new \RuntimeException('封面拼接失败');
            }

            $output = $this->makeOutputPath();
            $saved = imagejpeg($canvas, $output, 90);
            imagedestroy($canvas);
            if ($saved !== true || !is_file($output)) {
                throw new \RuntimeException('封面拼接失败');
            }

            return $output;
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === '封面拼接失败') {
                throw $e;
            }
            throw new \RuntimeException('封面拼接失败', 0, $e);
        } catch (\Throwable $e) {
            throw new \RuntimeException('封面拼接失败', 0, $e);
        }
    }

    private function resolveLocalPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (is_file($path)) {
            return $path;
        }

        $normalized = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $candidates = [];
        if (function_exists('public_path')) {
            $candidates[] = public_path() . $normalized;
        }
        if (function_exists('root_path')) {
            $candidates[] = root_path() . $normalized;
            $candidates[] = root_path() . 'public' . DIRECTORY_SEPARATOR . $normalized;
        }
        $projectRoot = dirname(__DIR__, 4);
        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . $normalized;
        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $normalized;

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @return \GdImage|resource|false
     */
    private function loadImage(string $path)
    {
        $info = @getimagesize($path);
        $mime = is_array($info) ? (string)($info['mime'] ?? '') : '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($mime === 'image/jpeg' || in_array($ext, ['jpg', 'jpeg'], true)) {
            return @imagecreatefromjpeg($path);
        }
        if ($mime === 'image/png' || $ext === 'png') {
            return @imagecreatefrompng($path);
        }
        if ($mime === 'image/webp' || $ext === 'webp') {
            if (!function_exists('imagecreatefromwebp')) {
                return false;
            }
            return @imagecreatefromwebp($path);
        }

        return false;
    }

    /**
     * @param list<\GdImage|resource> $images
     * @return \GdImage|resource|false
     */
    private function compose(array $images, string $direction)
    {
        $widths = [];
        $heights = [];
        foreach ($images as $image) {
            $widths[] = imagesx($image);
            $heights[] = imagesy($image);
        }

        if ($direction === 'h') {
            $canvasWidth = array_sum($widths);
            $canvasHeight = max($heights);
        } else {
            $canvasWidth = max($widths);
            $canvasHeight = array_sum($heights);
        }

        if ($canvasWidth < 1 || $canvasHeight < 1) {
            return false;
        }

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
        if ($canvas === false) {
            return false;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        if ($white !== false) {
            imagefilledrectangle($canvas, 0, 0, $canvasWidth, $canvasHeight, $white);
        }

        $cursor = 0;
        foreach ($images as $index => $image) {
            $w = $widths[$index];
            $h = $heights[$index];
            if ($direction === 'h') {
                imagecopy($canvas, $image, $cursor, 0, 0, 0, $w, $h);
                $cursor += $w;
            } else {
                imagecopy($canvas, $image, 0, $cursor, 0, 0, $w, $h);
                $cursor += $h;
            }
        }

        return $canvas;
    }

    /**
     * @param list<\GdImage|resource> $images
     */
    private function destroyImages(array $images): void
    {
        foreach ($images as $image) {
            if (is_resource($image) || $image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
    }

    private function makeOutputPath(): string
    {
        if (function_exists('runtime_path')) {
            $dir = rtrim(runtime_path(), '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'dy_stitch';
        } else {
            $dir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'dy_stitch';
        }

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('封面拼接失败');
        }

        return $dir . DIRECTORY_SEPARATOR . 'dy_stitch_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
    }
}
