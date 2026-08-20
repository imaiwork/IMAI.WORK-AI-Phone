<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoSite;
use think\facade\Cache;

/**
 * 公众号发布通道(二期):作为官网 SEO 框架的站点类型 wechat_oa。
 *
 * 多租户设计:商家把【自己公众号】的 AppID/AppSecret 存在站点上
 * (api_user=AppID,api_key=AppSecret),互不影响;不占用平台 oa_setting。
 *
 * 发布策略:草稿箱(draft/add)必达;随后尝试 freepublish/submit 直接群发 ——
 * 需已认证服务号且开通发布能力,失败不算错误,文章留在草稿箱人工群发(状态 pending)。
 * 前置条件:公众号后台把服务器 IP 加入「IP 白名单」。发布免费,不扣算力不扣钱。
 */
class GeoWechatService extends GeoSiteService
{
    /** 校验凭据并取 access_token(缓存 100 分钟) */
    public static function token(GeoSite $site): string
    {
        $appid = trim((string)$site->api_user);
        $secret = self::plainApiKey($site);
        if ($appid === '' || $secret === '') {
            throw new \Exception('请在站点里填写公众号 AppID(开发者ID)与 AppSecret(开发者密钥)');
        }
        $ck = 'geo_wx_token_' . $site->id . '_' . substr(md5($appid . '|' . $secret), 0, 8);
        $tok = Cache::get($ck);
        if ($tok) return (string)$tok;
        [, $body] = self::httpGet(
            'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . urlencode($appid) . '&secret=' . urlencode($secret),
            []
        );
        $data = json_decode((string)$body, true) ?: [];
        if (empty($data['access_token'])) {
            throw new \Exception('获取公众号 access_token 失败:' . ($data['errcode'] ?? '') . ' ' . ($data['errmsg'] ?? '')
                . '(请核对 AppID/AppSecret,并在公众号后台把服务器 IP 加入 IP 白名单)');
        }
        Cache::set($ck, (string)$data['access_token'], 6000);
        return (string)$data['access_token'];
    }

    /**
     * 推送文章到公众号。
     * @return array ['status'=>'published'|'pending', 'url'=>'', 'note'=>string]
     */
    public static function publish(GeoSite $site, GeoContent $content): array
    {
        $token = self::token($site);
        $thumb = self::thumbMediaId($site, $content, $token);
        $plain = trim((string)preg_replace('/[#*>\[\]()`\-]+/u', ' ', mb_substr((string)$content->body, 0, 300)));
        $payload = json_encode(['articles' => [[
            'title' => mb_substr((string)$content->title, 0, 60),
            'author' => '',
            'digest' => mb_substr($plain, 0, 100),
            'content' => self::localizeContentImages(self::mdToHtml((string)$content->body), $token),
            'content_source_url' => '',
            'thumb_media_id' => $thumb,
            'need_open_comment' => 0,
            'only_fans_can_comment' => 0,
        ]]], JSON_UNESCAPED_UNICODE);
        [$code, $body] = self::httpPost('https://api.weixin.qq.com/cgi-bin/draft/add?access_token=' . $token, $payload, ['Content-Type: application/json']);
        $data = json_decode((string)$body, true) ?: [];
        $mediaId = (string)($data['media_id'] ?? '');
        if ($mediaId === '') {
            throw new \Exception('公众号草稿创建失败:' . self::wxErrHint($data, (string)($data['errmsg'] ?? substr((string)$body, 0, 120))));
        }
        // 尝试直接发布:仅已认证服务号可成功;失败留在草稿箱人工群发,不视为错误
        [, $body2] = self::httpPost('https://api.weixin.qq.com/cgi-bin/freepublish/submit?access_token=' . $token,
            (string)json_encode(['media_id' => $mediaId]), ['Content-Type: application/json']);
        $d2 = json_decode((string)$body2, true) ?: [];
        if ((int)($d2['errcode'] ?? -1) === 0) {
            return ['status' => 'published', 'url' => '', 'note' => '已提交公众号自动发布(文章链接稍后可在公众号后台查看)'];
        }
        return ['status' => 'pending', 'url' => '', 'note' => '已推入公众号草稿箱,请到公众号后台确认群发。自动群发失败原因:' . ($d2['errmsg'] ?? '需已认证服务号')];
    }

    /**
     * 封面素材:优先品牌 Logo,其次文章封面/正文首图。
     * 先走永久素材;订阅号/未认证号常无此权限(48001),再兜底临时素材(3 天有效)。
     */
    protected static function thumbMediaId(GeoSite $site, GeoContent $content, string $token): string
    {
        $img = '';
        $project = GeoProject::findOrEmpty((int)$content->project_id);
        if (!$project->isEmpty() && (string)$project->logo !== '') $img = (string)$project->logo;
        if ($img === '' && (string)$content->cover_url !== '') $img = (string)$content->cover_url;
        if ($img === '' && preg_match('/!\[[^\]]*\]\((https?:[^)\s]+)\)/', (string)$content->body, $m)) $img = $m[1];
        if ($img === '') {
            throw new \Exception('公众号草稿需要封面图:请在「设置-品牌画像」上传品牌 Logo,或在文章编辑里插入一张图片');
        }
        $ck = 'geo_wx_thumb_' . $site->id . '_' . md5($img);
        $mid = Cache::get($ck);
        if ($mid) return (string)$mid;

        $bin = self::downloadBin($img);
        if ($bin === '') throw new \Exception('封面图下载失败:' . $img);
        [$bin, $mime, $ext] = self::normalizeCoverImage($bin);
        // 不拼后缀:tempnam 已创建原文件,拼后缀会泄漏一个空文件
        $tmp = tempnam(sys_get_temp_dir(), 'geo_wx_');
        file_put_contents($tmp, $bin);
        try {
            $data = self::wxUploadMedia($token, $tmp, $mime, 'cover.' . $ext, 'forever');
            $ttl = 86400 * 30;
            if (empty($data['media_id']) && self::wxUnauthorized($data)) {
                $data = self::wxUploadMedia($token, $tmp, $mime, 'cover.' . $ext, 'temp');
                $ttl = 86400 * 2;
            }
            if (empty($data['media_id'])) {
                throw new \Exception('封面素材上传失败:' . self::wxErrHint($data, (string)($data['errmsg'] ?? '请检查公众号素材权限与封面图格式')));
            }
            Cache::set($ck, (string)$data['media_id'], $ttl);
            return (string)$data['media_id'];
        } finally {
            @unlink($tmp);
        }
    }

    /** @return array{0:string,1:string,2:string} [二进制, mime, 扩展名] */
    protected static function normalizeCoverImage(string $bin): array
    {
        $mime = self::imageMime($bin);
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/bmp'], true)) {
            return [$bin, $mime, self::imageExt($mime)];
        }
        if (function_exists('imagecreatefromstring')) {
            $im = @imagecreatefromstring($bin);
            if ($im) {
                ob_start();
                imagejpeg($im, null, 88);
                imagedestroy($im);
                $out = (string)ob_get_clean();
                if ($out !== '') return [$out, 'image/jpeg', 'jpg'];
            }
        }
        throw new \Exception('封面图格式不受公众号支持,请改用 JPG 或 PNG');
    }

    protected static function imageMime(string $bin): string
    {
        $head = substr($bin, 0, 16);
        if (strncmp($head, "\xFF\xD8\xFF", 3) === 0) return 'image/jpeg';
        if (strncmp($head, "\x89PNG\r\n\x1A\n", 8) === 0) return 'image/png';
        if (strncmp($head, 'GIF87a', 6) === 0 || strncmp($head, 'GIF89a', 6) === 0) return 'image/gif';
        if (strncmp($head, 'BM', 2) === 0) return 'image/bmp';
        if (strncmp($head, 'RIFF', 4) === 0 && substr($bin, 8, 4) === 'WEBP') return 'image/webp';
        return '';
    }

    protected static function imageExt(string $mime): string
    {
        return ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/bmp' => 'bmp'][$mime] ?? 'jpg';
    }

    /**
     * 正文图片本地化:微信图文只渲染 mmbiz.qpic.cn 域内图片,外链在草稿/群发里
     * 会被剥离(后台正文"图片不显示"的根因)。逐张经 media/uploadimg 换成微信内链。
     * 单张失败保留原链接并继续,不阻断发布;uploadimg 仅收 jpg/png 且 <1MB,
     * 超限图片跳过(该图在微信端不显示)。
     */
    protected static function localizeContentImages(string $html, string $token): string
    {
        if (!preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $m)) {
            return $html;
        }
        $map = [];
        foreach (array_unique($m[1]) as $src) {
            $url = html_entity_decode($src, ENT_QUOTES);
            if (!preg_match('#^https?://#i', $url) || str_contains($url, 'mmbiz.qpic.cn')) {
                continue;
            }
            try {
                $bin = GeoSiteService::safeDownload($url);
                if ($bin === '') continue;
                $mime = self::imageMime($bin);
                if ($mime === '') continue;
                // 超限/非 jpg-png 的图先规格化(转码+缩边+降质),不再直接放弃
                $norm = self::normalizeForUploadImg($bin, $mime);
                if ($norm === null) continue;
                $bin = $norm['bin'];
                $mime = $norm['mime'];
                $tmp = tempnam(sys_get_temp_dir(), 'wximg');
                file_put_contents($tmp, $bin);
                $res = self::wxUploadImg($token, $tmp, $mime, 'geo.' . self::imageExt($mime));
                @unlink($tmp);
                $wx = (string)($res['url'] ?? '');
                if ($wx !== '') $map[$src] = $wx;
            } catch (\Throwable $e) { /* 单图失败不阻断发布 */ }
        }
        foreach ($map as $old => $new) {
            $html = str_replace('src="' . $old . '"', 'src="' . $new . '"', $html);
        }
        return $html;
    }

    /**
     * 把任意图片规格化成 uploadimg 可收的格式(jpg/png 且 <1MB):
     * webp/gif/bmp 用 GD 转成 jpg;超 1MB 先缩边(最长边 1080,公众号正文标准宽)
     * 再逐级降质(85→75→60)。透明底转 jpg 前铺白,避免变黑。
     * GD 不可用/解码失败/极端大图仍超限时返回 null,调用方保留外链跳过该图。
     * 注意:动图 GIF 只保留首帧(uploadimg 本身不支持动图)。
     */
    protected static function normalizeForUploadImg(string $bin, string $mime): ?array
    {
        if (in_array($mime, ['image/jpeg', 'image/png'], true) && strlen($bin) < 1024 * 1024) {
            return ['bin' => $bin, 'mime' => $mime];
        }
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $im = @imagecreatefromstring($bin);
        if ($im === false) {
            return null;
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $max = 1080;
        if (max($w, $h) > $max) {
            $ratio = $max / max($w, $h);
            $scaled = imagescale($im, max(1, (int)round($w * $ratio)), max(1, (int)round($h * $ratio)));
            if ($scaled !== false) {
                imagedestroy($im);
                $im = $scaled;
            }
        }
        $out = imagecreatetruecolor(imagesx($im), imagesy($im));
        $white = imagecolorallocate($out, 255, 255, 255);
        imagefill($out, 0, 0, $white);
        imagecopy($out, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
        imagedestroy($im);
        foreach ([85, 75, 60] as $q) {
            ob_start();
            imagejpeg($out, null, $q);
            $jpg = (string)ob_get_clean();
            if ($jpg !== '' && strlen($jpg) < 1024 * 1024) {
                imagedestroy($out);
                return ['bin' => $jpg, 'mime' => 'image/jpeg'];
            }
        }
        imagedestroy($out);
        return null;
    }

    /** 图文消息内图片上传(media/uploadimg):不占素材库额度,返回微信域 URL */
    protected static function wxUploadImg(string $token, string $path, string $mime, string $filename): array
    {
        $ch = curl_init('https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=' . urlencode($token));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => ['media' => new \CURLFile($path, $mime, $filename)],
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) return ['errcode' => -1, 'errmsg' => '请求 uploadimg 失败:' . $err];
        return json_decode((string)$resp, true) ?: ['errcode' => -1, 'errmsg' => substr((string)$resp, 0, 120)];
    }

    /** @param 'forever'|'temp' $mode */
    protected static function wxUploadMedia(string $token, string $path, string $mime, string $filename, string $mode): array
    {
        $api = $mode === 'forever'
            ? 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=' . urlencode($token) . '&type=image'
            : 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . urlencode($token) . '&type=image';
        $ch = curl_init($api);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => ['media' => new \CURLFile($path, $mime, $filename)],
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) return ['errcode' => -1, 'errmsg' => '请求公众号素材接口失败:' . $err];
        return json_decode((string)$resp, true) ?: ['errcode' => -1, 'errmsg' => substr((string)$resp, 0, 120)];
    }

    protected static function wxUnauthorized(array $data): bool
    {
        $code = (int)($data['errcode'] ?? 0);
        $msg = (string)($data['errmsg'] ?? '');
        return $code === 48001 || stripos($msg, 'api unauthorized') !== false;
    }

    protected static function wxErrHint(array $data, string $fallback = ''): string
    {
        $code = (int)($data['errcode'] ?? 0);
        $msg = trim((string)($data['errmsg'] ?? $fallback));
        $map = [
            48001 => '公众号未开通该接口(48001)。永久素材仅认证服务号可用;请在公众号后台「设置与开发-接口权限」确认已开通素材管理,或改用已认证服务号',
            40128 => '封面必须是永久素材。当前公众号没有素材管理权限,请使用已认证服务号',
            40001 => 'access_token 无效,请到「设置-授权账号」重新检测公众号凭据',
            42001 => 'access_token 已过期,请重试发布',
            40164 => '服务器 IP 未加入公众号后台「IP 白名单」',
            40004 => '封面图类型不被接受,请改用 JPG 或 PNG',
            40005 => '封面图格式不被接受,请改用 JPG 或 PNG',
            40006 => '封面图过大,请压缩到 10MB 以内',
        ];
        if (isset($map[$code])) return $map[$code];
        if (self::wxUnauthorized($data)) return $map[48001];
        return $msg !== '' ? $msg : '公众号接口返回异常';
    }

    protected static function downloadBin(string $url): string
    {
        // 封面 URL 来自用户可控的 logo/正文首图,必须走 SSRF 防护下载
        try {
            return GeoSiteService::safeDownload($url);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
