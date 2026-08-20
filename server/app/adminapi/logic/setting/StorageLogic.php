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
    public const MEDIA_PROCESS_LOCAL = 'local';
    public const MEDIA_PROCESS_OSS = 'oss';

    /**
     * @notes 归一化阿里云媒体处理模式为单值 local/oss，非法值回退 local
     *        兼容历史数组写法（含 oss 视为 oss）。
     */
    public static function normalizeMediaProcess(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }
        if (is_array($value)) {
            $value = in_array(self::MEDIA_PROCESS_OSS, $value, true) ? self::MEDIA_PROCESS_OSS : self::MEDIA_PROCESS_LOCAL;
        }
        $value = (string)$value;
        return $value === self::MEDIA_PROCESS_OSS ? self::MEDIA_PROCESS_OSS : self::MEDIA_PROCESS_LOCAL;
    }

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

        $aliyun = ConfigService::get('storage', 'aliyun', []);
        $aliyunMediaProcess = self::normalizeMediaProcess($aliyun['media_process'] ?? self::MEDIA_PROCESS_LOCAL);
        $aliyunPath = $aliyunMediaProcess === self::MEDIA_PROCESS_OSS
            ? '存储在阿里云；转码+切割：阿里云 OSS(MPS)'
            : '存储在阿里云；转码+切割：本地 FFmpeg';

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
                'path' => '存储在七牛云；媒体处理：本地 ffmpeg（固定，不支持切换）',
                'engine' => 'qiniu',
                'media_process' => self::MEDIA_PROCESS_LOCAL,
                'migration' => $default == 'qiniu' ? $migration : 0,
                'status' => $default == 'qiniu' ? 1 : 0
            ],
            [
                'name' => '阿里云OSS',
                'path' => $aliyunPath,
                'engine' => 'aliyun',
                'media_process' => $aliyunMediaProcess,
                'migration' => $default == 'aliyun' ? $migration : 0,
                'status' => $default == 'aliyun' ? 1 : 0
            ],
            [
                'name' => '腾讯云COS',
                'path' => '存储在腾讯云；媒体处理：本地 ffmpeg（固定，不支持切换）',
                'engine' => 'qcloud',
                'media_process' => self::MEDIA_PROCESS_LOCAL,
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
        // 七牛云存储（媒体处理固定本地，不支持切换）
        $qiniu = ConfigService::get('storage', 'qiniu', [
            'bucket' => '',
            'access_key' => '',
            'secret_key' => '',
            'domain' => '',
            'status' => $default == 'qiniu' ? 1 : 0,
            'migration'=> 0,
        ]);
        $qiniu['media_process'] = self::MEDIA_PROCESS_LOCAL;

        // 阿里云存储（媒体处理默认 local；仅 aliyun 支持 oss 模式）
        $aliyun = ConfigService::get('storage', 'aliyun', [
            'bucket' => '',
            'access_key' => '',
            'secret_key' => '',
            'domain' => '',
            'Location' => '',
            'PipelineId' => '',
            'TemplateId' => '',
            'media_process' => self::MEDIA_PROCESS_LOCAL,
            'status' => $default == 'aliyun' ? 1 : 0,
            'migration'=> 0,
        ]);
        $aliyun['media_process'] = self::normalizeMediaProcess($aliyun['media_process'] ?? self::MEDIA_PROCESS_LOCAL);
        $aliyun['Location'] = (string)($aliyun['Location'] ?? '');
        $aliyun['PipelineId'] = (string)($aliyun['PipelineId'] ?? '');
        $aliyun['TemplateId'] = (string)($aliyun['TemplateId'] ?? '');

        // 腾讯云存储（媒体处理固定本地，不支持切换）
        $qcloud = ConfigService::get('storage', 'qcloud', [
            'bucket' => '',
            'region' => '',
            'access_key' => '',
            'secret_key' => '',
            'domain' => '',
            'status' => $default == 'qcloud' ? 1 : 0,
            'migration'=> 0,
        ]);
        $qcloud['media_process'] = self::MEDIA_PROCESS_LOCAL;

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
                // 七牛仅文件存储；媒体处理固定本地，不接受 cloud media 切换参数
                $existingQiniu = ConfigService::get('storage', 'qiniu', []);
                ConfigService::set('storage', 'qiniu', [
                    'bucket' => $params['bucket'] ?? '',
                    'access_key' => $params['access_key'] ?? '',
                    'secret_key' => $params['secret_key'] ?? '',
                    'domain' => $params['domain'] ?? '',
                    'media_process' => self::MEDIA_PROCESS_LOCAL,
                    'migration' => (int)($existingQiniu['migration'] ?? 0),
                    // 保留历史字段，避免旧数据被清空；页面不再配置
                    'PipelineId' => (string)($existingQiniu['PipelineId'] ?? ''),
                    'Location' => (string)($existingQiniu['Location'] ?? ''),
                    'TemplateId' => (string)($existingQiniu['TemplateId'] ?? ''),
                ]);
                break;
            case 'aliyun':
                $mediaProcess = self::normalizeMediaProcess($params['media_process'] ?? self::MEDIA_PROCESS_LOCAL);
                $pipelineId = trim((string)($params['PipelineId'] ?? ''));
                $location = trim((string)($params['Location'] ?? ''));
                $templateId = trim((string)($params['TemplateId'] ?? ''));

                if ($mediaProcess === self::MEDIA_PROCESS_OSS) {
                    if ($pipelineId === '' || $location === '' || $templateId === '') {
                        return '选择 OSS 媒体处理时，请填写 Location、PipelineId、TemplateId';
                    }
                    if (!preg_match('/^oss-[a-z0-9-]+$/', $location)) {
                        return 'Location 格式不正确，示例：oss-cn-beijing';
                    }
                }

                $existing = ConfigService::get('storage', 'aliyun', []);
                ConfigService::set('storage', 'aliyun', [
                    'bucket' => $params['bucket'] ?? '',
                    'access_key' => $params['access_key'] ?? '',
                    'secret_key' => $params['secret_key'] ?? '',
                    'domain' => $params['domain'] ?? '',
                    'Location' => $location,
                    'PipelineId' => $pipelineId,
                    'TemplateId' => $templateId,
                    'media_process' => $mediaProcess,
                    // 保留迁移状态，避免 setup 覆盖 migration 接口写入的值
                    'migration' => (int)($existing['migration'] ?? 0),
                ]);
                break;
            case 'qcloud':
                // 腾讯仅文件存储；媒体处理固定本地，不接受 cloud media 切换参数
                $existingQcloud = ConfigService::get('storage', 'qcloud', []);
                ConfigService::set('storage', 'qcloud', [
                    'bucket' => $params['bucket'] ?? '',
                    'region' => $params['region'] ?? '',
                    'access_key' => $params['access_key'] ?? '',
                    'secret_key' => $params['secret_key'] ?? '',
                    'domain' => $params['domain'] ?? '',
                    'media_process' => self::MEDIA_PROCESS_LOCAL,
                    'migration' => (int)($existingQcloud['migration'] ?? 0),
                    // 保留历史 TemplateId，页面不再配置
                    'TemplateId' => (string)($existingQcloud['TemplateId'] ?? ''),
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
