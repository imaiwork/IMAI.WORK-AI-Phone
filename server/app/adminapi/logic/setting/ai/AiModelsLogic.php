<?php
namespace app\adminapi\logic\setting\ai;

use app\common\enum\ChatEnum;
use app\common\logic\BaseLogic;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\service\ConfigService;
use app\common\service\FileService;
use Exception;

/**
 * AI模型配置
 */
class AiModelsLogic extends BaseLogic
{
    /**
     * @notes 模型通道
     * @return array
     */
    public static function channel(): array
    {
        $chatModels    = config('ai.ChatModels');
        $vectorModels  = config('ai.VectorModels');
        $rankingModels = config('ai.RankingModels');
        $exampleModels = config('ai.ExampleModels');

        foreach ($chatModels as &$item) {
            $item['logo'] = FileService::getFileUrl($item['logo']);
        }

        foreach ($vectorModels as &$item) {
            $item['logo'] = FileService::getFileUrl($item['logo']);
        }

        return [
            'chatModels'    => $chatModels,
            'vectorModels'  => $vectorModels,
            'rankingModels' => $rankingModels,
            'exampleModels' => $exampleModels
        ];
    }

    /**
     * @notes 模型列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author fzr
     */
    public static function lists(): array
    {
        $chatModels = (new Models())
            ->field(['id,type,channel,logo,name,is_system,is_enable'])
            ->where(['type'=>ChatEnum::MODEL_TYPE_CHAT])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $drawModels = (new Models())
            ->field(['id,type,channel,logo,name,is_enable'])
            ->where(['type'=>ChatEnum::MODEL_TYPE_DRAW])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $humanModels = (new Models())
            ->field(['id,type,channel,logo,name,is_enable'])
            ->where(['type'=>ChatEnum::MODEL_TYPE_HUMAN])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        foreach ($chatModels as &$item) {
            $item['logo'] = FileService::getFileUrl($item['logo']);
        }

        foreach ($drawModels as &$item) {
            $item['logo'] = FileService::getFileUrl($item['logo']);
        }

        foreach ($humanModels as &$item) {
            $item['logo'] = FileService::getFileUrl($item['logo']);
        }

        return [
            'chatModels' => $chatModels,
            'drawModels' => $drawModels,
            'humanModels' => $humanModels
        ];
    }

    /**
     * @notes 模型详情
     * @param int $id
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author fzr
     */
    public static function detail(int $id): array
    {
        $model = new Models();
        $detail = $model->withoutField(['delete_time'])->where(['id'=>$id])->findOrEmpty()->toArray();
        if (!$detail) {
            return [];
        }

        $modelCost = new ModelsCost();
        $subModels = $modelCost
            ->field(['id,name,alias,price,sort,status'])
            ->where(['model_id'=>$detail['id']])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $detail['logo'] = FileService::getFileUrl($detail['logo']);
        $detail['models'] = $subModels;
        return $detail;
    }

    /**
     * @notes 模型创建
     * @param array $post
     * @return bool
     * @author fzr
     */
    public static function add(array $post): bool
    {
        $model = new Models();
        $model->startTrans();
        try {
            $channelName = match (intval($post['type'])) {
                ChatEnum::MODEL_TYPE_CHAT => 'ai.ChatModels',
                ChatEnum::MODEL_TYPE_RANKING => 'ai.RankingModels',
                default => 'ai.VectorModels',
            };

            $configs = [];
            $setting = config($channelName)[$post['channel']]['configs'];
            foreach ($setting as $conf) {
                if (!empty($conf['config'])) {
                    foreach ($conf['config'] as $item) {
                        $key = $item['key'];
                        $configs[$key] = empty($post['configs'][$key]) ? $item['default'] : $post['configs'][$key];
                    }
                } else {
                    $key = $conf['key'];
                    $configs[$key] = empty($post['configs'][$key]) ? $conf['default'] : $post['configs'][$key];
                }
            }

            $mainModel = Models::create([
                'type'      => $post['type'],
                'channel'   => $post['channel'],
                'name'      => $post['name'],
                'logo'      => FileService::setFileUrl($post['logo']),
                'is_enable' => intval($post['is_enable']??0),
                'configs'   => json_encode($configs, JSON_UNESCAPED_UNICODE)
            ]);

            if (ChatEnum::MODEL_TYPE_CHAT) {
                foreach ($post['models'] as $item) {
                    ModelsCost::create([
                        'model_id' => $mainModel['id'],
                        'type'     => $post['type'],
                        'channel'  => $post['channel'],
                        'name'     => $item['name'],
                        'alias'    => empty($item['alias']) ? $item['name'] : $item['alias'],
                        'price'    => $item['price']??0,
                        'status'   => intval($item['status']??0),
                        'sort'     => intval($item['sort']??0)
                    ]);
                }
            } else if (ChatEnum::MODEL_TYPE_EMB  || ChatEnum::MODEL_TYPE_RANKING) {
                $postM = $post['models'][0];
                $emStatus = ($post['is_enable']??0) ? 1 : 0;
                ModelsCost::create([
                    'model_id' => $mainModel['id'],
                    'type'     => intval($post['type']),
                    'channel'  => $post['channel'],
                    'name'     => $postM['name'],
                    'alias'    => empty($postM['alias']) ? $postM['name'] : $postM['alias'],
                    'price'    => $item['price']??0,
                    'status'   => $emStatus,
                    'sort'     => intval($item['sort']??0)
                ]);
            }

            // 更新默认的模型
            $model->where(['type'=>$post['type']])->update(['is_default'=>0]);
            $model->where(['type'=>$post['type']])
                ->where(['is_enable'=>1])
                ->order('sort asc, id desc')
                ->update(['is_default'=>1]);

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 模型编辑
     * @param array $post
     * @return bool
     * @author fzr
     */
    public static function edit(array $post): bool
    {
        $model = new Models();
        $model->startTrans();
        try {
            $mainModel = $model->where(['id'=>intval($post['id'])])->findOrEmpty()->toArray();
            if (!$mainModel) {
                throw new Exception('模型已不存在了!');
            }
            if (in_array($mainModel['id'],[7,9,10]) && $post['is_enable'] == 0) {
                throw new Exception('该模型不可关闭!');
            }
            $models = $model->where(['type'=>$mainModel['type'],'is_enable'=>1])->column('id');
            if ($mainModel['type'] != 1 && count($models) == 2 && in_array($mainModel['id'], $models) && $post['is_enable'] == 0){
                throw new Exception('请至少保留一个模型!');
            }
            if ($mainModel['type'] == 1 && count($models) == 1 && in_array($mainModel['id'], $models) && $post['is_enable'] == 0){
                throw new Exception('请至少保留一个聊天模型!');
            }

            Models::update([
                               'name'      => $post['name'],
                               'logo'      => FileService::setFileUrl($post['logo']),
                               'is_enable' => $post['is_enable'],
                           ], ['id'=>intval($post['id'])]);

            // 对话模型的处理逻辑
            if ($mainModel['type'] == ChatEnum::MODEL_TYPE_CHAT) {
                $models = ConfigService::get('chat', 'ai_model', []);
                foreach ($models['channel'] as $key=>$item){
                    $models['channel'][$key]['name'] = $item['model_id'] == $mainModel['id'] ? $post['name'] : $item['name'];
                    $models['channel'][$key]['status'] = $item['model_id'] == $mainModel['id'] ? $post['is_enable'] : $item['status'];
                }
                ConfigService::set('chat', 'ai_model', $models);
            }

            // 生图模型的处理逻辑
            if ($mainModel['type'] == ChatEnum::MODEL_TYPE_DRAW) {
                $models = ConfigService::get('hd', 'list', []);
                foreach ($models['channel'] as $key=>$item){
                    $models['channel'][$key]['name'] = $item['model_id'] == $mainModel['id'] ? $post['name'] : $item['name'];
                    $models['channel'][$key]['status'] = $item['model_id'] == $mainModel['id'] ? $post['is_enable'] : $item['status'];
                }
                ConfigService::set('hd', 'list', $models);
            }

            // 数字人模型的处理逻辑
            if ($mainModel['type'] == ChatEnum::MODEL_TYPE_HUMAN && $mainModel['id'] != 9) {
                $models = ConfigService::get('model', 'list', []);
                foreach ($models['channel'] as $key=>$item){
                    $models['channel'][$key]['name'] = $item['model_id'] == $mainModel['id'] ? $post['name'] : $item['name'];
                    $models['channel'][$key]['status'] = $item['model_id'] == $mainModel['id'] ? $post['is_enable'] : $item['status'];
                }
                ConfigService::set('model', 'list', $models);
            }

            // 闪剪处理
            if ($mainModel['type'] == ChatEnum::MODEL_TYPE_HUMAN && $mainModel['id'] == 9) {
                ConfigService::set('digital_human', 'shanjian_auth', $post['name']);
            }

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 模型删除
     * @param int $id
     * @return bool
     * @author fzr
     */
    public static function del(int $id): bool
    {
        try {
            $model = new Models();
            $detail = $model->where(['id'=>$id])->findOrEmpty()->toArray();

            if (!$detail) {
                throw new Exception('模型已不存在!');
            }

            Models::destroy($id);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 模型排序
     * @param array $post
     * @return bool
     */
    public static function sort(array $post): bool
    {
        try {
            foreach ($post['orders'] as $item) {
                Models::update([
                    'sort' => $item['sort']
                ], ['id'=>intval($item['id'])]);
            }

            if (!empty($post['orders'][0]['id'])) {
                $model = new Models();
                $mainModel = $model->where(['id' => $post['orders'][0]['id']])->findOrEmpty()->toArray();
       
                $model->where(['type' => $mainModel['type']])->update(['is_default' => 0]);
                $model->where(['type' => $mainModel['type']])
                    ->where(['is_enable' => 1])
                    ->order('sort asc, id desc')
                    ->update(['is_default' => 1]);
            }
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}