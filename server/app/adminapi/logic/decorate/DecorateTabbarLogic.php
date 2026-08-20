<?php

namespace app\adminapi\logic\decorate;

use app\common\logic\BaseLogic;
use app\common\model\decorate\DecorateTabbar;
use app\common\service\ConfigService;
use app\common\service\FileService;


/**
 * 装修配置-底部导航
 * Class DecorateTabbarLogic
 * @package app\adminapi\logic\decorate
 */
class DecorateTabbarLogic extends BaseLogic
{

    /**
     * @notes 获取底部导航详情
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/7 16:58
     */
    public static function detail(): array
    {
        $list = DecorateTabbar::getTabbarLists();
        // 数据库无数据时返回默认底部导航
        if (empty($list)) {
            $list = self::getDefaultTabbar();
        }
        $style = ConfigService::get('tabbar', 'style', config('project.decorate.tabbar_style'));
        return ['style' => $style, 'list' => $list];
    }


    /**
     * @notes 获取默认底部导航数据
     * @return array
     * @author chatgpt
     * @date 2024/06/24
     */
    public static function getDefaultTabbar(): array
    {
        $default = config('project.decorate.tabbar_default') ?: [];
        foreach ($default as &$item) {
            if (!empty($item['selected'])) {
                $item['selected'] = FileService::getFileUrl($item['selected']);
            }
            if (!empty($item['unselected'])) {
                $item['unselected'] = FileService::getFileUrl($item['unselected']);
            }
        }
        return $default;
    }


    /**
     * @notes 底部导航保存
     * @param $params
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2022/9/7 17:19
     */
    public static function save($params): bool
    {
        $model = new DecorateTabbar();
        // 删除旧配置数据
        $model->where('id', '>', 0)->delete();

        // 保存数据
        $tabbars = $params['list'] ?? [];
        $data = [];
        foreach ($tabbars as $item) {
            $data[] = [
                'name' => $item['name'],
                'selected' => FileService::setFileUrl($item['selected']),
                'unselected' => FileService::setFileUrl($item['unselected']),
                'link' => $item['link'],
                'is_show' => $item['is_show'] ?? 0,
            ];
        }
        $model->saveAll($data);

        if (!empty($params['style'])) {
            ConfigService::set('tabbar', 'style', $params['style']);
        }
        return true;
    }
}
