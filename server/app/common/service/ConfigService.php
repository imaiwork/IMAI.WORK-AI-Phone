<?php


declare(strict_types=1);

namespace app\common\service;

use app\common\model\Config;

class ConfigService
{
    /**
     * @notes 设置配置值
     * @param $type
     * @param $name
     * @param $value
     * @return mixed
     * @author 段誉
     * @date 2021/12/27 15:00
     */
    public static function set(string $type, string $name, $value, int $teamId = 0)
    {
        $original = $value;
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        // team_id=0 为平台全局配置;>0 为团队(企业OEM)独立配置,按团队隔离
        $data = Config::where(['type' => $type, 'name' => $name, 'team_id' => $teamId])->findOrEmpty();

        if ($data->isEmpty()) {
            Config::create([
                'type' => $type,
                'name' => $name,
                'value' => $value,
                'team_id' => $teamId,
            ]);
        } else {
            $data->value = $value;
            $data->save();
        }

        // 返回原始值
        return $original;
    }

    /**
     * @notes 获取配置值
     * @param $type
     * @param string $name
     * @param null $default_value
     * @return array|int|mixed|string
     * @author Tab
     * @date 2021/7/15 15:16
     */
    public static function get(string $type, string $name = '', $default_value = null, int $teamId = 0)
    {
        if (!empty($name)) {
            // team_id=0 平台全局;>0 团队独立配置(未配置则不跨团队回退,由 default_value 兜底,保证白标隔离)
            $value = Config::where(['type' => $type, 'name' => $name, 'team_id' => $teamId])->value('value');
            if (!is_null($value)) {
                $json = json_decode($value, true);
                $value = json_last_error() === JSON_ERROR_NONE ? $json : $value;
            }

            if ($type == 'index' && $name == 'config') {

                return self::convertImage($value);
            }

            if ($value) {
                return $value;
            }
            // 返回特殊值 0 '0'
            if ($value === 0 || $value === '0') {
                return $value;
            }
            // 返回默认值
            if ($default_value !== null) {
                return $default_value;
            }
            // 返回本地配置文件中的值
            return config('project.' . $type . '.' . $name);
        }

        // 取某个类型下的所有name的值(按 team_id 隔离)
        $data = Config::where(['type' => $type, 'team_id' => $teamId])->column('value', 'name');
        foreach ($data as $k => $v) {
            $json = json_decode($v, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$k] = $json;
            }
        }

        if ($data) {
            return self::convertImage($data);
        }
    }

    //转换图片
    public static function convertImage(array $values): array
    {

        foreach ($values ?? [] as $key => $value) {

            foreach ($value['lists'] ?? [] as $k1 => $v1) {

                $values[$key]['lists'][$k1]['pic'] = FileService::getFileUrl($v1['pic']);

                if (isset($v1['data']['pic'])) {

                    $values[$key]['lists'][$k1]['data']['pic'] = FileService::getFileUrl($v1['data']['pic']);
                }
            }
        }

        return $values;
    }
}
