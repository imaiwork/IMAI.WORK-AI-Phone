<?php


namespace app\adminapi\logic;


use app\common\model\dict\DictData;
use app\common\model\ModelConfig;
use app\common\service\{ConfigService, FileService};

/**
 * 配置类逻辑层
 * Class ConfigLogic
 * @package app\adminapi\logic
 */
class ConfigLogic
{
    /**
     * @notes 获取配置
     * @return array
     * @author 段誉
     * @date 2021/12/31 11:03
     */
    public static function getConfig(): array
    {

        $modelList = ConfigService::get('model', 'list', []);
        $hdList = ConfigService::get('hd', 'list', []);
        $default = ConfigService::get('storage', 'default', 'local');
        $storage = ConfigService::get('storage', $default);
        $ossDomain = $storage ?  $storage['domain'].'/' : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST']).'/';
        $chatModels = ConfigService::get('chat', 'ai_model', []);
        foreach ($chatModels['channel'] as $key=>$value){
            $chatModels['channel'][$key]['logo'] = isset($value['logo']) ? FileService::getFileUrl($value['logo']) : '';
        }
        $banner =  config('app.app_host') . '/static/images/human/banner.png';

        //配置按模块分类，配置放到对应的模块里面，不要单独写，或者写到别的模块里面
        return [
            // 文件域名
//            'oss_domain' => FileService::getFileUrl(),
            'oss_domain' => $ossDomain,
            'is_robot_show' => ConfigService::get('assistants', 'is_robot_show',0),
            // 网站名称
            'web_name' => ConfigService::get('website', 'name'),
            // 网站图标
            'web_favicon' => FileService::getFileUrl(ConfigService::get('website', 'web_favicon')),
            // 网站logo
            'web_logo' => FileService::getFileUrl(ConfigService::get('website', 'web_logo')),
            // 登录页
            'login_image' => FileService::getFileUrl(ConfigService::get('website', 'login_image')),

            // 版权信息
            'copyright_config' => ConfigService::get('copyright', 'config', []),

            //首页配置信息
            'index_config' => ConfigService::get('index', 'config', []),

            //模型秘钥配置
            'model_key' => ConfigService::get('model', 'key', []),

            // 调查问卷
            'survey' => ConfigService::get('website', 'survey', []),

            // banner
            'banner' => FileService::getFileUrl(ConfigService::get('website', 'banner')),

            // 客服配置
            'customer_service' => self::getCustomerService(),

            //会议纪要配置
            'meeting_config' => self::getMeetingConfig(),

            //版本信息
            'version' => ConfigService::get('website', 'version', []),

            //练练
            'lianlian' => self::getLianlianConfig(),
            'digital_human' => [
                'privacy' => ConfigService::get('digital_human', 'privacy', []),
                'channel' => $modelList['channel'] ?? [],
                'voice' => $modelList['voice'] ?? [],
                'shanjian_auth' => ConfigService::get('digital_human', 'shanjian_auth', '闪剪AI'),
                'banner' =>  FileService::getFileUrl(ConfigService::get('digital_human', 'banner', $banner)),
            ],
            'draw' => [
                'channel' => $hdList['channel'] ?? [],
            ],
            'app_config' => ConfigService::get('app_config', 'redbook', []),
            'ai_live' =>  ConfigService::get('ai_live', 'config', []),
            'by_name'=>  self::getByName(),
            'ai_model' =>  $chatModels,
            'wechat_remarks' => ConfigService::get('add_remark', 'wechat', []),
        ];
    }

    /**
     * @notes 获取配置
     * @return bool
     * @author 段誉
     * @date 2021/12/31 11:03
     */
    public static function setConfig(string $type, string $name, string|array $params): bool
    {

        if ($type == 'website' && $name == 'customer_service') {

            if (isset($params['image'])) {

                $params['image'] = FileService::setFileUrl($params['image']);
            }

            if (isset($params['wx_image'])) {

                $params['wx_image'] = FileService::setFileUrl($params['wx_image']);
            }

            if (isset($params['fs_image'])) {

                $params['fs_image'] = FileService::setFileUrl($params['fs_image']);
            }
        }

        //会议纪要配置
        if ($type == 'meeting' && $name == 'config') {

            if (isset($params['avatars'])) {

                foreach ($params['avatars'] as $key => $value) {

                    $params['avatars'][$key] = FileService::setFileUrl($value);
                }
            }
        }

        //练练配置
        if ($type == 'lianlian' && $name == 'config') {

            if (isset($params['avatars'])) {

                foreach ($params['avatars'] as $key => $value) {

                    $params['avatars'][$key] = FileService::setFileUrl($value);
                }
            }

            if (isset($params['voice'])) {

                foreach ($params['voice'] as $key => $value) {

                    $params['voice'][$key]['logo'] = FileService::setFileUrl($value['logo']);
                }
            }
        }

        ConfigService::set($type, $name, json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return true;
    }

    /**
     * @notes 根据类型获取字典类型
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/27 19:09
     */
    public static function getDictByType($type)
    {
        if (!is_string($type)) {
            return [];
        }

        $type = explode(',', $type);
        $lists = DictData::whereIn('type_value', $type)->select()->toArray();

        if (empty($lists)) {
            return [];
        }

        $result = [];
        foreach ($type as $item) {
            foreach ($lists as $dict) {
                if ($dict['type_value'] == $item) {
                    $result[$item][] = $dict;
                }
            }
        }
        return $result;
    }

    /**
     * 获取模型配置
     * @return array
     * @author L
     * @data 2024/8/1 10:35
     */
    public static function getModelConfig(): array
    {

        $response = \app\common\service\ToolsService::DataCenter()->tokensLists();

        $castLists = $response['data']['cast_list'] ?? [];

        return ModelConfig::select()
            ->each(function ($item) use ($castLists) {

                foreach ($castLists as $key => $value) {

                    if ($value['code'] == $item['scene']) {

                        $item['cast_price'] = $value['cast_price'];
                        $item['cast_unit']  = $value['cast_unit'];
                        $item['description']= $value['description'];
                    }
                }
            })
            ->toArray();
    }

    /**
     * 写入模型配置
     * @param $data
     * @return bool
     * @author L
     * @data 2024/8/1 10:35
     */
    public static function setModelConfig($data): bool
    {

        if (isset($data['id']) && isset($data['score'])) {

            ModelConfig::where('id', $data['id'])->update($data);
        } else {
            foreach ($data as $item) {
                $id = $item['id'];
                unset($item['id'], $item['cast_price'], $item['cast_unit'], $item['create_time'], $item['update_time']);
                ModelConfig::where('id', $id)->update($item);
            }
        }

        return true;
    }

    /**
     * @desc 获取客服信息
     * @return array
     * @date 2024/12/30 10:18
     * @author dagouzi
     */
    public static function getCustomerService()
    {
        $info =  ConfigService::get('website', 'customer_service', []);

        if (isset($info['image'])) {

            $info['image'] = FileService::getFileUrl($info['image']);
        }

        if (isset($info['wx_image'])) {

            $info['wx_image'] = FileService::getFileUrl($info['wx_image']);
        }

        if (isset($info['fs_image'])) {

            $info['fs_image'] = FileService::getFileUrl($info['fs_image']);
        }

        return $info;
    }


    /**
     * @desc 获取会议纪要配置
     * @return array
     * @date 2024/12/30 10:18
     * @author dagouzi
     */
    public static function getMeetingConfig()
    {
        $info =  ConfigService::get('meeting', 'config', []);

        if (isset($info['avatars'])) {

            foreach ($info['avatars'] as $key => $value) {

                $info['avatars'][$key] = FileService::getFileUrl($value);
            }
        }

        if (isset($info['language'])) {

            foreach ($info['language'] as $key => $value) {

                if ($value['status'] != 1) {

                    unset($info['language'][$key]);
                }
            }
        }

        if (isset($info['translation'])) {

            foreach ($info['translation'] as $key => $value) {

                if ($value['status'] != 1) {

                    unset($info['translation'][$key]);
                }
            }
        }

        return $info;
    }


    /**
     * @desc 获取练练配置
     * @return array
     * @date 2024/12/30 10:18
     * @author dagouzi
     */
    public static function getLianlianConfig()
    {
        $info =  ConfigService::get('lianlian', 'config', []);

        if (isset($info['avatars'])) {

            foreach ($info['avatars'] as $key => $value) {

                $info['avatars'][$key] = FileService::getFileUrl($value);
            }
        }

        if (isset($info['voice'])) {

            foreach ($info['voice'] as $key => $value) {

                $info['voice'][$key]['logo']    = FileService::getFileUrl($value['logo']);
            }
        }

        return $info;
    }

    public static function updateByname($params)
    {
        $response =  \app\common\service\ToolsService::Auth()->updateByname($params);;

        return  $response['code'] ?? '';
    }

    public static function getByName()
    {
           $response =  \app\common\service\ToolsService::Auth()->checkby();;

          return  $response['byname'] ?? '';
     }

}
