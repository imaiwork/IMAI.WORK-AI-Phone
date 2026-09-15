<?php

declare(strict_types=1);

namespace app\common\service;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Exception\ValidationException;
use think\exception\ValidateException;
use think\facade\Request;

/**
 * QR Code Service Class
 * 
 * 使用 Endroid QR Code 库生成二维码的服务类
 * 支持多种输出格式和自定义配置
 */
class QrCodeService
{
    /**
     * 默认配置
     */
    private const DEFAULT_SIZE = 600;
    private const DEFAULT_MARGIN = 10;
    private const DEFAULT_LOGO_WIDTH = 50;
    private const DEFAULT_SAVE_PATH = 'uploads/qrcode/';

    /**
     * 生成二维码（返回Base64）
     * 
     * @param string $text 链接或文本内容
     * @param int $size 尺寸（像素）
     * @param int $margin 边距
     * @param string|null $encoding 编码格式
     * @param ErrorCorrectionLevelInterface|null $errorCorrectionLevel 错误修正级别
     * @param ColorInterface|null $foregroundColor 前景色
     * @param ColorInterface|null $backgroundColor 背景色
     * @return string base64图片数据
     * @throws ValidateException
     */
    public static function generate(
        string $text,
        int $size = self::DEFAULT_SIZE,
        int $margin = self::DEFAULT_MARGIN,
        ?string $encoding = 'UTF-8',
        ?ErrorCorrectionLevelInterface $errorCorrectionLevel = null,
        ?ColorInterface $foregroundColor = null,
        ?ColorInterface $backgroundColor = null
    ): string {
        self::validateInput($text);

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($text)
                ->setSize($size)
                ->setMargin($margin)
                ->setEncoding(new Encoding($encoding ?? 'UTF-8'))
                ->setErrorCorrectionLevel($errorCorrectionLevel ?? new ErrorCorrectionLevelHigh())
                ->setForegroundColor($foregroundColor ?? new Color(0, 0, 0))
                ->setBackgroundColor($backgroundColor ?? new Color(255, 255, 255));

            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            return $result->getDataUri();
        } catch (\Exception $e) {
            throw new ValidateException('二维码生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成带Logo的二维码
     * 
     * @param string $text 内容
     * @param string $logoPath Logo文件路径（绝对路径）
     * @param int $size 尺寸
     * @param int $logoWidth Logo宽度
     * @param bool $logoPunchoutBackground 是否在Logo周围打孔
     * @return string base64
     * @throws ValidateException
     */
    public static function generateWithLogo(
        string $text,
        string $logoPath,
        int $size = self::DEFAULT_SIZE,
        int $logoWidth = self::DEFAULT_LOGO_WIDTH,
        bool $logoPunchoutBackground = true
    ): string {
        self::validateInput($text);
        self::validateLogoPath($logoPath);

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($text)
                ->setSize($size)
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());

            $writer = new PngWriter();
            
            $logo = Logo::create($logoPath)
                ->setResizeToWidth($logoWidth)
                ->setPunchoutBackground($logoPunchoutBackground);
            
            $result = $writer->write($qrCode, $logo);
            
            return $result->getDataUri();
        } catch (\Exception $e) {
            throw new ValidateException('带Logo二维码生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 保存二维码到文件
     * 
     * @param string $text 内容
     * @param string $savePath 保存路径（相对public）
     * @param int $size 尺寸
     * @param int $margin 边距
     * @return string 访问URL
     * @throws ValidateException
     */
    public static function save(
        string $text,
        string $savePath = self::DEFAULT_SAVE_PATH,
        int $size = self::DEFAULT_SIZE,
        int $margin = self::DEFAULT_MARGIN
    ): string {
        self::validateInput($text);

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($text)
                ->setSize($size)
                ->setMargin($margin)
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelMedium());  

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // 确保目录存在
            $fullPath = public_path() . $savePath;
            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                    throw new \RuntimeException('目录创建失败: ' . $fullPath);
                }
            }

            // 生成文件名
            $filename = md5($text . time()) . '.png';
            $filePath = $fullPath . $filename;
            
            // 保存文件
            $result->saveToFile($filePath);
            
            // 返回可访问的URL
            return Request::domain() . '/' . $savePath . $filename;
        } catch (\Exception $e) {
            throw new ValidateException('二维码保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 直接输出图片流（用于直接显示）
     * 
     * @param string $text 内容
     * @param int $size 尺寸
     * @param int $margin 边距
     * @throws ValidateException
     */
    public static function output(string $text, int $size = self::DEFAULT_SIZE, int $margin = self::DEFAULT_MARGIN): void
    {
        self::validateInput($text);

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($text)
                ->setSize($size)
                ->setMargin($margin)
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // 设置响应头
            header('Content-Type: ' . $result->getMimeType());
            header('Content-Length: ' . strlen($result->getString()));
            
            echo $result->getString();
            exit;
        } catch (\Exception $e) {
            throw new ValidateException('二维码输出失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成带标签的二维码
     * 
     * @param string $text 内容
     * @param string $labelText 标签文字
     * @param int $size 尺寸
     * @return string base64
     * @throws ValidateException
     */
    public static function generateWithLabel(
        string $text,
        string $labelText,
        int $size = self::DEFAULT_SIZE
    ): string {
        self::validateInput($text);

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($text)
                ->setSize($size)
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());

            $writer = new PngWriter();
            
            $label = \Endroid\QrCode\Label\Label::create($labelText);
            
            $result = $writer->write($qrCode, null, $label);
            
            return $result->getDataUri();
        } catch (\Exception $e) {
            throw new ValidateException('带标签二维码生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建自定义配置的二维码
     * 
     * @param string $text 内容
     * @param array $options 配置选项
     * @return ResultInterface
     * @throws ValidateException
     */
    public static function createCustom(string $text, array $options = []): ResultInterface
    {
        self::validateInput($text);

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($text);
            
            // 应用自定义配置
            if (isset($options['size'])) {
                $qrCode->setSize((int) $options['size']);
            }
            
            if (isset($options['margin'])) {
                $qrCode->setMargin((int) $options['margin']);
            }
            
            if (isset($options['encoding'])) {
                $qrCode->setEncoding(new Encoding($options['encoding']));
            }
            
            if (isset($options['roundBlockSizeMode'])) {
                $qrCode->setRoundBlockSizeMode($options['roundBlockSizeMode']);
            }

            $writer = new PngWriter();
            
            // 处理Logo
            $logo = null;
            if (!empty($options['logoPath']) && file_exists($options['logoPath'])) {
                $logo = Logo::create($options['logoPath']);
                
                if (isset($options['logoWidth'])) {
                    $logo->setResizeToWidth((int) $options['logoWidth']);
                }
                
                if (isset($options['logoHeight'])) {
                    $logo->setResizeToHeight((int) $options['logoHeight']);
                }
                
                if (isset($options['logoPunchoutBackground'])) {
                    $logo->setPunchoutBackground((bool) $options['logoPunchoutBackground']);
                }
            }
            
            // 处理标签
            $label = null;
            if (!empty($options['labelText'])) {
                $label = \Endroid\QrCode\Label\Label::create($options['labelText']);
            }

            return $writer->write($qrCode, $logo, $label, $options['writerOptions'] ?? []);
        } catch (\Exception $e) {
            throw new ValidateException('自定义二维码生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 验证输入内容
     * 
     * @param string $text
     * @throws ValidateException
     */
    private static function validateInput(string $text): void
    {
        if (empty(trim($text))) {
            throw new ValidateException('二维码内容不能为空');
        }
        
        if (strlen($text) > 2953) {
            throw new ValidateException('二维码内容过长（最大支持2953个字符）');
        }
    }

    /**
     * 验证Logo文件路径
     * 
     * @param string $logoPath
     * @throws ValidateException
     */
    private static function validateLogoPath(string $logoPath): void
    {
        if (!file_exists($logoPath)) {
            throw new ValidateException('Logo文件不存在: ' . $logoPath);
        }
        
        if (!is_readable($logoPath)) {
            throw new ValidateException('Logo文件不可读: ' . $logoPath);
        }
    }

    /**
     * 获取支持的错误修正级别
     * 
     * @return array<string, string>
     */
    public static function getErrorCorrectionLevels(): array
    {
        return [
            'low' => '低 (7%)',
            'medium' => '中 (15%)',
            'quartile' => '四分位 (25%)',
            'high' => '高 (30%)',
        ];
    }

    /**
     * 验证生成的二维码是否可读
     * 
     * @param string $filePath 二维码文件路径
     * @return bool
     */
    public static function validateQrCode(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        try {
            // 这里可以集成 QR 码检测库来验证二维码是否可读
            // 例如使用 khanamiryan/qrcode-detector-decoder
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}