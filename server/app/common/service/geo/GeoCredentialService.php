<?php

namespace app\common\service\geo;

/**
 * GEO 授权账号凭据加密(AES-256-GCM)。
 *
 * geo_auth_account.credentials 里的敏感字段(token/secret/password 类,按
 * GeoAuthLogic::PLATFORMS 字段定义的 secret=1 判定)加密后入库,读取时经
 * GeoAuthPublishService::cred() 统一解密;非敏感字段(用户名/地址等)保持明文。
 *
 * 密文格式:v1:base64(iv).base64(tag).base64(ciphertext)
 *  - iv 12 字节(GCM 推荐长度),tag 16 字节,均为随机生成、随密文走;
 *  - v1 前缀供 isCipher() 识别,便于密文与明文混存时逐值判定。
 *
 * 密钥约定:env('GEO.CREDENTIAL_KEY')(.env 的 [GEO] 段 CREDENTIAL_KEY),可选:
 *  - 未配置:不加密,凭据明文落库(2026-08-19 产品决策:免去逐环境配钥的部署负担,
 *    换取开箱即用;读取端按 isCipher 逐值判定,明文/密文混存均可正常工作);
 *  - 已配置:64 位 hex(openssl random 32 字节后 bin2hex)或 32 字节原文,新写入加密;
 *    配置了但格式错误仍抛异常(手误必须显式暴露,不静默降级);
 *  - 已有密文数据的环境不要清掉该配置:v1: 密文行离开原密钥永远解不开。
 * GCM 认证失败(密钥错/密文被改)抛异常。读到明文值 cred() 原样放行(isCipher=false)。
 */
class GeoCredentialService
{
    protected const CIPHER = 'aes-256-gcm';
    protected const PREFIX = 'v1:';
    protected const IV_LEN = 12;
    protected const TAG_LEN = 16;

    /** 加密;未配置密钥时明文原样返回(读取端按 isCipher 判定) @throws \Exception 密钥格式错误 */
    public static function encrypt(string $plain): string
    {
        $key = self::key();
        if ($key === null) {
            return $plain;
        }
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);
        if ($cipher === false) {
            throw new \Exception('凭据加密失败');
        }
        return self::PREFIX . base64_encode($iv) . '.' . base64_encode($tag) . '.' . base64_encode($cipher);
    }

    /** 解密 @throws \Exception 格式非法/密钥错误/GCM 认证失败 */
    public static function decrypt(string $cipher): string
    {
        if (!self::isCipher($cipher)) {
            throw new \Exception('凭据密文格式非法');
        }
        $parts = explode('.', substr($cipher, strlen(self::PREFIX)));
        if (count($parts) !== 3) {
            throw new \Exception('凭据密文格式非法');
        }
        [$iv, $tag, $data] = array_map('base64_decode', $parts);
        if ($iv === false || $tag === false || $data === false) {
            throw new \Exception('凭据密文格式非法');
        }
        $key = self::key();
        if ($key === null) {
            throw new \Exception('该凭据是加密存储的,但当前环境未配置 GEO.CREDENTIAL_KEY:请恢复原密钥,或让用户重新保存授权');
        }
        $plain = openssl_decrypt($data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new \Exception('凭据解密失败:密钥不匹配或密文已被篡改');
        }
        return $plain;
    }

    /** 是否为本服务产出的密文(v1: 前缀识别) */
    public static function isCipher(string $value): bool
    {
        return strncmp($value, self::PREFIX, strlen(self::PREFIX)) === 0;
    }

    /**
     * 取密钥:hex(64 字符)或 32 字节原文,统一归一到 32 字节。
     * @return string|null 未配置返回 null(加密降级明文);配置了但格式错误抛异常
     * @throws \Exception 长度/格式错误
     */
    protected static function key(): ?string
    {
        $raw = trim((string)env('GEO.CREDENTIAL_KEY', ''));
        if ($raw === '') {
            return null;
        }
        if (strlen($raw) === 64 && ctype_xdigit($raw)) {
            $raw = (string)hex2bin($raw);
        }
        if (strlen($raw) !== 32) {
            throw new \Exception('GEO.CREDENTIAL_KEY 必须为 32 字节(64 位 hex 或 32 字节原文)');
        }
        return $raw;
    }
}
