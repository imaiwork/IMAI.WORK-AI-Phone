<?php


namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\notice\NoticeRecord;
use app\common\model\notice\NoticeSetting;
use app\common\model\user\UserAuth;
use app\common\service\FileService;
use app\common\service\wechat\WeChatMnpService;
use app\common\service\wechat\WeChatOaService;
use EasyWeChat\Kernel\Exceptions\Exception;
use think\helper\Str;

/**
 * 微信
 * Class WechatLogic
 * @package app\api\logic
 */
class WechatLogic extends BaseLogic
{

    /**
     * @notes 微信JSSDK授权接口
     * @param $params
     * @return false|mixed[]
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/3/1 11:49
     */
    public static function jsConfig($params)
    {
        try {
            $url = urldecode($params['url']);
            return (new WeChatOaService())->getJsConfig($url, [
                'onMenuShareTimeline',
                'onMenuShareAppMessage',
                'onMenuShareQQ',
                'onMenuShareWeibo',
                'onMenuShareQZone',
                'openLocation',
                'getLocation',
                'chooseWXPay',
                'updateAppMessageShareData',
                'updateTimelineShareData',
                'openAddress',
                'scanQRCode'
            ]);
        } catch (Exception $e) {
            self::setError('获取jssdk失败:' . $e->getMessage());
            return false;
        }
    }


    /**
     * 获取小程序码
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024/7/1 15:30
     */
    public static function getMnpCodeUrl(array $postData)
    {
        try {

            $wechatMnpService = new WeChatMnpService();

            $path = public_path() . 'uploads/images/' . md5($postData['path']) . '.png';
            $params = [];
            $authKey = '';

            if (isset($postData['mnp_auth'])){
                $authKey = Str::random(16);
                $params = ['auth_key' => $authKey];
                $path = public_path() . 'uploads/images/mnpqrcode/' . md5(time().$authKey) . '.png';
            }

            if (!is_dir(dirname($path))) {
                umask(0);
                mkdir(dirname($path), 0777, true);
            }

            if (!file_exists($path)) {
                $wechatMnpService->getMnpCodeUrl($postData['path'], 430, $path, $params);
            }

            self::$returnData = ['url' => FileService::getFileUrl(str_replace(public_path(), '', $path)),'auth_key' => $authKey];
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 小程序通知
     * @param array $params
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/7/1 15:30
     * 401 RPA相关任务 参数
     * $params['openid']     = 'oOLMR0agq2XV1vcy932jbjRtgIOw';
     * $params['scene_id']   = 401;
     * $params['name']       = 'RPA发布任务';  //长度20个字符
     * $params['start_time'] = '2026-01-22 14:00:00'; //必须时间格式
     * $params['end_time']   = '2026-01-23 14:00:00'; //必须时间格式
     * $params['status']     = '创建成功'; //长度5个字符
     * 402 视频生成相关任务 参数不同
     * $params['openid']   = 'oOLMR0agq2XV1vcy932jbjRtgIOw';
     * $params['scene_id'] = 402;
     * $params['name']     = '数字人视频生成任务'; //长度20个字符
     * $params['time']     = date('Y-m-d H:i:s', time()); //必须时间格式
     * $params['status']   = '生成失败'; //长度5个字符
     */
    public static function sendMnpMessage(array $params): bool
    {
        try {
            $wechatMnpService = new WeChatMnpService();
            $openid           = $params['openid'];
            $userId           = UserAuth::where('openid', $openid)->value('user_id', 0);
            if (empty($userId)) {
                throw new \Exception('用户不存在');
            }

            $mnpNoticeSetting = NoticeSetting::where('scene_id', $params['scene_id'])->findOrEmpty();
            if ($mnpNoticeSetting->isEmpty()) {
                throw new \Exception('小程序通知设置不存在');
            }
            $mnpNotice   = $mnpNoticeSetting->mnp_notice;
            $template_id = $mnpNotice['template_id'];
            $page        = 'pages/index/index';
            $len         = mb_strlen($params['name']);
            if ($len > 20) {
//                $params['name'] = trim(preg_replace('/\d+/', '', $params['name']));
                $params['name'] = mb_substr($params['name'], 0, 18) . '..';
            }
            if ($params['scene_id'] == 401) {
                $data = [
                    'thing1'  => [
                        'value' => $params['name']
                    ],
                    'time4'   => [
                        'value' => $params['start_time']
                    ],
                    'time5'   => [
                        'value' => $params['end_time']
                    ],
                    'phrase2' => [
                        'value' => $params['status']
                    ]
                ];
            } else if ($params['scene_id'] == 402) {
                $data = [
                    'thing1'  => [
                        'value' => $params['name']
                    ],
                    'time3'  => [
                        'value' => $params['time']
                    ],
                    'phrase4' => [
                        'value' => $params['status']
                    ]
                ];
            } else {
                throw new \Exception('小程序通知设置不存在');
            }

            $result = $wechatMnpService->sendTemplateMessage($openid, $template_id, $page, $data);
            \think\facade\Log::channel('notice')->write('任务:'.$params['name'].'推送结果:'.$result);
            $result = json_decode($result, true);
            if ($result['errcode'] === 0) {
                $insert = [
                    'user_id'     => $userId,
                    'title'       => '小程序通知：' . $params['name'],
                    'content'     => '通知成功：' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'scene_id'    => $params['scene_id'],
                    'read'        => 1,
                    'recipient'   => 1,
                    'send_type'   => 4,
                    'notice_type' => 1,
                    'extra'       => '请求参数：' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',返回信息：' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                NoticeRecord::create($insert);
                return true;
            }
            //通知失败记录
            $insert = [
                'user_id'     => $userId,
                'title'       => '小程序通知：' . $params['name'],
                'content'     => '通知失败：' . $result['errmsg'],
                'scene_id'    => $params['scene_id'],
                'read'        => 1,
                'recipient'   => 1,
                'send_type'   => 4,
                'notice_type' => 1,
                'extra'       => '请求参数：' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',返回信息：' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
            NoticeRecord::create($insert);
            throw new \Exception($result['errmsg']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
