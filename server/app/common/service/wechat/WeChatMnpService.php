<?php

namespace app\common\service\wechat;


use EasyWeChat\Kernel\Exceptions\Exception;
use EasyWeChat\MiniApp\Application;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


/**
 * 微信功能类
 * Class WeChatMnpService
 * @package app\common\service
 */
class WeChatMnpService
{

    protected $app;

    protected $config;

    /** 所属团队 id(0=主站小程序;>0=OEM 团队独立小程序) */
    protected int $teamId = 0;

    /**
     * 按配置复用的 Application 实例,key = 配置 md5
     * EasyWeChat 对象图带循环引用,workerman 常驻进程里每 new 一次就漏 3 个 fd(curl multi 的 socketpair + 到微信的长连接),
     * 攒到进程上限后整个 worker 打不开任何文件/连接;按配置复用后同一配置只建一次,配置变了自然换新实例
     * @var array<string, Application>
     */
    private static array $apps = [];

    public function __construct(int $teamId = 0)
    {
        $this->teamId = $teamId;
        $this->config = $this->getConfig();
        $key = md5((string)json_encode($this->config));
        if (!isset(self::$apps[$key])) {
            self::$apps[$key] = new Application($this->config);
        }
        $this->app = self::$apps[$key];
    }


    /**
     * @notes 配置
     * @return array
     * @throws \Exception
     * @author 段誉
     * @date 2023/2/27 12:03
     */
    protected function getConfig()
    {
        $config = WeChatConfigService::getMnpConfig($this->teamId);
        if (empty($config['app_id']) || empty($config['secret'])) {
            throw new \Exception('请先设置小程序配置');
        }
        return $config;
    }


    /**
     * @notes 本实例(主站或某个 OEM 团队)使用的小程序 appid
     * @return string
     * @remark 换到的 openid 只在该 appid 下有效,写授权表时必须一起记录
     */
    public function getAppId(): string
    {
        return (string)($this->config['app_id'] ?? '');
    }


    /**
     * @notes 小程序-根据code获取微信信息
     * @param string $code
     * @return array
     * @throws Exception
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/2/27 11:03
     */
    public function getMnpResByCode(string $code)
    {
        $utils = $this->app->getUtils();
        try {
            $response = $utils->codeToSession($code);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            // 40029: code 无效/已使用/过期；40163: code 已被使用
            if (str_contains($message, '40029') || str_contains($message, '40163') || str_contains($message, 'invalid code')) {
                throw new Exception('微信登录凭证已失效，请重试');
            }
            throw new Exception($message);
        }

        if (!empty($response['errcode'])) {
            $errcode = (int)$response['errcode'];
            if (in_array($errcode, [40029, 40163], true)) {
                throw new Exception('微信登录凭证已失效，请重试');
            }
            throw new Exception('获取openID失败：' . ($response['errmsg'] ?? $errcode));
        }

        if (!isset($response['openid']) || empty($response['openid'])) {
            throw new Exception('获取openID失败');
        }

        return $response;
    }


    /**
     * @notes 获取手机号
     * @param string $code
     * @return array
     * @throws \Exception
     */
    public function getUserPhoneNumber(string $code)
    {
        try {
            // postJson 里就会去换 access_token;OEM 密钥错误或 IP 未加白名单时,微信在这一步直接拒绝
            $response = $this->app->getClient()->postJson('wxa/business/getuserphonenumber', [
                'code' => $code,
            ]);
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            \think\facade\Log::error('mnp phone token team=' . $this->teamId . ' ' . $e->getMessage());
            throw new \Exception(self::humanizeAccessTokenError($e->getMessage()));
        }

        $errcode = (int)($data['errcode'] ?? 0);
        if ($errcode !== 0) {
            \think\facade\Log::error(
                'mnp phone api team=' . $this->teamId . ' ' . json_encode($data, JSON_UNESCAPED_UNICODE)
            );
            throw new \Exception(self::humanizePhoneApiError($errcode, (string)($data['errmsg'] ?? '')));
        }

        return $data;
    }


    /**
     * EasyWeChat 原文是 Failed to get access_token: {errcode,errmsg},小程序 toast 会截断成英文残句。
     */
    private static function humanizeAccessTokenError(string $message): string
    {
        if (!str_contains($message, 'Failed to get access_token')
            && !str_contains($message, 'Failed to get stable access_token')) {
            return $message;
        }

        $data = [];
        $pos = strpos($message, '{');
        if ($pos !== false) {
            $decoded = json_decode(substr($message, $pos), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $errcode = (int)($data['errcode'] ?? 0);
        $errmsg = (string)($data['errmsg'] ?? '');

        if ($errcode === 40164 || str_contains($errmsg, 'not in whitelist') || str_contains($errmsg, 'invalid ip')) {
            $ip = '';
            if (preg_match('/invalid ip[:\s]+([0-9.]+)/i', $errmsg, $matches)) {
                $ip = $matches[1];
            }
            return '服务器IP未加白名单' . ($ip !== '' ? '：' . $ip : '');
        }
        if ($errcode === 40125 || str_contains($errmsg, 'invalid appsecret')) {
            return '小程序AppSecret不正确';
        }
        if ($errcode === 40013 || str_contains($errmsg, 'invalid appid')) {
            return '小程序AppID不正确';
        }

        return '微信授权失败，请检查小程序配置';
    }


    private static function humanizePhoneApiError(int $errcode, string $errmsg): string
    {
        if (in_array($errcode, [40029, 40163], true) || str_contains($errmsg, 'invalid code')) {
            return '微信授权已失效，请重试';
        }
        if ($errcode === 40013) {
            return '小程序AppID不正确';
        }

        return '获取手机号码失败';
    }


    /**
     * @notes 获取小程序码
     * @param string $page
     * @param int $width
     * @return string
     * @throws \Exception
     */
    public function getMnpCodeUrl(string $page, int $width = 430, $savePath = '/tmp/mnp-wxacode-123.png', $params = [])
    {
        try {
            if (isset($params['auth_key'])){
                $scene = '1011&auth_key=' . $params['auth_key'];
            }

            if (isset($params['sn'])){
                $scene = '1011&sn=' . $params['sn'];
            }

            $response = $this->app->getClient()->postJson('/wxa/getwxacodeunlimit', [
                'page' => $page,
                'scene' => $scene ?? '1011',
                'width' => $width,
                'check_path' => true
            ]);

            // 获取响应头的Content-Type
            $contentType = $response->getHeaderLine('Content-Type');

            // 如果是图片类型
            if (strpos($contentType, 'image') !== false) {
                $response->saveAs($savePath);
                return $savePath;
            }

            // 如果不是图片，尝试解析JSON错误信息
            $jsonData = json_decode($response->getContent(), true);
            if (isset($jsonData['errcode']) && $jsonData['errcode'] != 0) {
                throw new \Exception('获取小程序码失败: ' . ($jsonData['errmsg'] ?? '未知错误'));
            }

            throw new \Exception('获取小程序码失败: 响应格式错误');
        } catch (\Throwable $e) {
            throw new \Exception('获取小程序码失败: ' . $e->getMessage());
        }
    }

    /**
     * @desc 获取短链
     * https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-link/url-link/generateUrlLink.html
     * @param string $path
     * @param string $query
     * @return array|mixed[]
     * @date 2025/2/20 11:07
     * @throws \EasyWeChat\Kernel\Exceptions\BadResponseException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author dagouzi
     */
    public function urlLink(string $path, string $query)
    {
        return $this->app->getClient()->post('wxa/generate_urllink', [
            'json' => [
                'path' => $path,
                'query' => $query
            ],
        ])->toArray();

    }

    /**
     * @desc 发送订阅消息
     * https://developers.weixin.qq.com/miniprogram/dev/server/API/mp-message-management/subscribe-message/api_sendmessage.html
     * @param $openid
     * @param $template_id
     * @param $page
     * @param $data
     * @date 2026/1/22 11:07
     * @throws TransportExceptionInterface
     * @author Rick
     */
    public function sendTemplateMessage($openid, $template_id, $page, $data)
    {
        return $this->app->getClient()->postJson('cgi-bin/message/subscribe/send', [
            'touser' => $openid,
            'template_id' => $template_id,
            'page' => $page,
            'data' => $data,
            'miniprogram_state' => 'formal',
            'lang' => 'zh_CN',
        ]);
    }
}
