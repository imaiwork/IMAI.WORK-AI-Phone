<?php


namespace app\adminapi\logic\setting;


use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use think\facade\Cache;


/**
 * 存储设置逻辑层
 * Class ShopStorageLogic
 * @package app\adminapi\logic\setting\
 */
class StorageLogic extends BaseLogic
{

    /**
     * @notes 存储引擎列表
     * @return array[]
     * @author 段誉
     * @date 2022/4/20 16:14
     */
    public static function lists()
    {

        $default = ConfigService::get('storage', 'default', 'local');
        $migration = 0;
        if( $default != 'local'){
            $migration = ConfigService::get('storage', $default)['migration'] ?? 0 ;
        }

        $data = [
            [
                'name' => '本地存储',
                'path' => '存储在本地服务器',
                'engine' => 'local',
                'migration' => $default == 'local' ? $migration : 0,
                'status' => $default == 'local' ? 1 : 0
            ],
            [
                'name' => '七牛云存储',
                'path' => '存储在七牛云，请前往七牛云开通存储服务',
                'engine' => 'qiniu',
                'migration' => $default == 'qiniu' ? $migration : 0,
                'status' => $default == 'qiniu' ? 1 : 0
            ],
            [
                'name' => '阿里云OSS',
                'path' => '存储在阿里云，请前往阿里云开通存储服务',
                'engine' => 'aliyun',
                'migration' => $default == 'aliyun' ? $migration : 0,
                'status' => $default == 'aliyun' ? 1 : 0
            ],
            [
                'name' => '腾讯云COS',
                'path' => '存储在腾讯云，请前往腾讯云开通存储服务',
                'engine' => 'qcloud',
                'migration' => $default == 'qcloud' ? $migration : 0,
                'status' => $default == 'qcloud' ? 1 : 0
            ]
        ];
        return $data;
    }


    /**
     * @notes 存储设置详情
     * @param $param
     * @return mixed
     * @author 段誉
     * @date 2022/4/20 16:15
     */
    public static function detail($param)
    {

        $default = ConfigService::get('storage', 'default', '');

        // 本地存储
        $local = ['status' => $default == 'local' ? 1 : 0];
        // 七牛云存储
        $qiniu = ConfigService::get('storage', 'qiniu', [
            'bucket' => '',
            'access_key' => '',
            'secret_key' => '',
            'domain' => '',
            'status' => $default == 'qiniu' ? 1 : 0,
            'migration'=> 0,
        ]);

        // 阿里云存储
        $aliyun = ConfigService::get('storage', 'aliyun', [
            'bucket' => '',
            'access_key' => '',
            'secret_key' => '',
            'domain' => '',
            'status' => $default == 'aliyun' ? 1 : 0,
            'migration'=> 0,
        ]);

        // 腾讯云存储
        $qcloud = ConfigService::get('storage', 'qcloud', [
            'bucket' => '',
            'region' => '',
            'access_key' => '',
            'secret_key' => '',
            'domain' => '',
            'status' => $default == 'qcloud' ? 1 : 0,
            'migration'=> 0,
        ]);

        $data = [
            'local' => $local,
            'qiniu' => $qiniu,
            'aliyun' => $aliyun,
            'qcloud' => $qcloud
        ];
        $result = $data[$param['engine']];
        if ($param['engine'] == $default) {
            $result['status'] = 1;
        } else {
            $result['status'] = 0;
        }
        return $result;
    }


    /**
     * @notes 设置存储参数
     * @param $params
     * @return bool|string
     * @author 段誉
     * @date 2022/4/20 16:16
     */
    public static function setup($params)
    {
        if ($params['status'] == 1) { //状态为开启
            ConfigService::set('storage', 'default', $params['engine']);
        } else {
            ConfigService::set('storage', 'default', 'local');
        }

        switch ($params['engine']) {
            case 'local':
                ConfigService::set('storage', 'local', []);
                break;
            case 'qiniu':
                ConfigService::set('storage', 'qiniu', [
                    'PipelineId' => $params['PipelineId'] ?? '',
                    'Location' => $params['Location'] ?? '',
                    'TemplateId' => $params['TemplateId'] ?? '',
                    'bucket' => $params['bucket'] ?? '',
                    'access_key' => $params['access_key'] ?? '',
                    'secret_key' => $params['secret_key'] ?? '',
                    'domain' => $params['domain'] ?? ''
                ]);
                break;
            case 'aliyun':
                ConfigService::set('storage', 'aliyun', [
                    'PipelineId' => $params['PipelineId'] ?? '',
                    'Location' => $params['Location'] ?? '',
                    'TemplateId' => $params['TemplateId'] ?? '',
                    'bucket' => $params['bucket'] ?? '',
                    'access_key' => $params['access_key'] ?? '',
                    'secret_key' => $params['secret_key'] ?? '',
                    'domain' => $params['domain'] ?? ''
                ]);
                break;
            case 'qcloud':
                ConfigService::set('storage', 'qcloud', [
                    'bucket' => $params['bucket'] ?? '',
                    'region' => $params['region'] ?? '',
                    'access_key' => $params['access_key'] ?? '',
                    'secret_key' => $params['secret_key'] ?? '',
                    'domain' => $params['domain'] ?? '',
                    'TemplateId' => $params['TemplateId'] ?? '',
                ]);
                break;
        }

        Cache::delete('STORAGE_DEFAULT');
        Cache::delete('STORAGE_ENGINE');
        if ($params['engine'] == 'local' && $params['status'] == 0) {
            return '默认开启本地存储';
        } else {
            return true;
        }
    }


    /**
     * @notes 切换状态
     * @param $params
     * @author 段誉
     * @date 2022/4/20 16:17
     */
    public static function change($params)
    {
        $default = ConfigService::get('storage', 'default', '');
        if ($default == $params['engine']) {
            ConfigService::set('storage', 'default', 'local');
        } else {
            ConfigService::set('storage', 'default', $params['engine']);
        }
        Cache::delete('STORAGE_DEFAULT');
        Cache::delete('STORAGE_ENGINE');
    }



    /**
     * @notes 设置存储参数
     * @param $params
     * @return bool|string
     * @author 段誉
     * @date 2022/4/20 16:16
     */
    public static function migration($params)
    {
        if ($params['status'] == 1) { //状态为开启
            ConfigService::set('storage', 'default', $params['engine']);
        } else {
            ConfigService::set('storage', 'default', 'local');
        }

        if (!in_array($params['engine'], ['local', 'qiniu', 'aliyun', 'qcloud'])) {
            return '存储配置错误';
        }
        $data = ConfigService::get('storage',$params['engine']);
        $data['migration'] = $params['migration'];
        switch ($params['engine']) {
            case 'local':
                ConfigService::set('storage', 'local', []);
                break;
           default:
                ConfigService::set('storage', $params['engine'],   $data);
                break;
        }

        Cache::delete('STORAGE_DEFAULT');
        Cache::delete('STORAGE_ENGINE');
        if ($params['engine'] == 'local' && $params['status'] == 0) {
            return '默认开启本地存储';
        } else {
            return true;
        }
    }

}
