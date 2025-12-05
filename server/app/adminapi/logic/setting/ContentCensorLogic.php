<?php


namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

class ContentCensorLogic extends BaseLogic
{
    /**
     * @notes 审核配置详情
     * @return array
     * @author kb
     */
    public static function detail(): array
    {
        return [
            'is_open'    => ConfigService::get('content_censor', 'is_open', 0),
            'app_id'     => ConfigService::get('content_censor', 'app_id', ''),
            'api_key'    => ConfigService::get('content_censor', 'api_key', ''),
            'secret_key' => ConfigService::get('content_censor', 'secret_key', ''),
            'ask_open'   => ConfigService::get('content_censor', 'ask_open', 0),
            'prompt_open' => ConfigService::get('content_censor','prompt_open',0), // 绘画提示词审核
            'image_open' => ConfigService::get('content_censor','image_open',0), // 绘画图片审核
            'user_open' => ConfigService::get('content_censor','user_open',0), //  用户信息审核
            'upload_image_open' => ConfigService::get('content_censor','upload_image_open',0), //  上传图片审核
        ] ?? [];
    }

    /**
     * @notes 审核配置保存
     * @param array $params
     * @author kb
     */
    public static function save(array $params)
    {
        ConfigService::set('content_censor','is_open', $params['is_open']??0);
        ConfigService::set('content_censor','ask_open', $params['ask_open']??0);
        ConfigService::set('content_censor','app_id', $params['app_id']??'');
        ConfigService::set('content_censor','api_key', $params['api_key']??'');
        ConfigService::set('content_censor','secret_key', $params['secret_key']??'');
        ConfigService::set('content_censor','prompt_open',$params['prompt_open'] ?? 0);
        ConfigService::set('content_censor','image_open',$params['image_open'] ?? 0);
        ConfigService::set('content_censor','user_open',$params['user_open'] ?? 0);
        ConfigService::set('content_censor','upload_image_open',$params['upload_image_open'] ?? 0);
    }
}