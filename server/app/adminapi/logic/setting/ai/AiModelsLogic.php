<?php

namespace app\adminapi\logic\setting\ai;

use app\common\enum\ChatEnum;
use app\common\logic\BaseLogic;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\service\chat\ChatModelsService;
use app\common\service\ConfigService;
use app\common\service\DigitalHumanModelConfigService;
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
            self::appendChatModelPrice($item);
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
            ->where(['type' => ChatEnum::MODEL_TYPE_CHAT])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $drawModels = (new Models())
            ->field(['id,type,channel,logo,name,is_enable'])
            ->where(['type' => ChatEnum::MODEL_TYPE_DRAW])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $humanModels = (new Models())
            ->field(['id,type,channel,logo,name,is_enable', 'model_version'])
            ->where(['type' => ChatEnum::MODEL_TYPE_HUMAN])
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
        $detail = $model->withoutField(['delete_time'])->where(['id' => $id])->findOrEmpty()->toArray();
        if (!$detail) {
            return [];
        }

        $modelCost = new ModelsCost();
        $subModels = $modelCost
            ->field(['id,name,alias,price,cost_price,model_price,quota_type,sort,status'])
            ->where(['model_id' => $detail['id']])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $mediaType = self::resolveMediaTypeFromModel($detail, $subModels);
        foreach ($subModels as &$subModel) {
            self::appendSubModelPrice($subModel, $mediaType);
        }
        unset($subModel);

        $detail['logo'] = FileService::getFileUrl($detail['logo']);
        $detail['media_type'] = $mediaType;
        $detail['models'] = $subModels;
        return $detail;
    }

    /**
     * 从主模型 configs / 名称 / 子模型 alias 推断 image|video
     * @param array<int, array<string, mixed>> $subModels
     */
    private static function resolveMediaTypeFromModel(array $detail, array $subModels = []): string
    {
        $configs = $detail['configs'] ?? [];
        if (is_string($configs) && $configs !== '') {
            $decoded = json_decode($configs, true);
            $configs = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($configs)) {
            $configs = [];
        }
        $mediaType = strtolower(trim((string)($configs['media_type'] ?? '')));
        if (in_array($mediaType, ['image', 'video'], true)) {
            return $mediaType;
        }

        $guessFrom = strtolower(trim((string)($detail['name'] ?? '')));
        foreach ($subModels as $sub) {
            $guessFrom .= ' ' . strtolower(trim((string)($sub['alias'] ?? '')));
            $guessFrom .= ' ' . strtolower(trim((string)($sub['name'] ?? '')));
        }
        if (
            str_contains($guessFrom, 'seedance')
            || str_contains($guessFrom, 'video')
        ) {
            return 'video';
        }
        if (
            str_contains($guessFrom, 'seedream')
            || str_contains($guessFrom, 'gpt-image')
            || str_contains($guessFrom, 'image')
            || (int)($detail['type'] ?? 0) === ChatEnum::MODEL_TYPE_DRAW
        ) {
            return 'image';
        }

        return '';
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
                'is_enable' => intval($post['is_enable'] ?? 0),
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
                        'price'    => $item['price'] ?? 0,
                        'status'   => intval($item['status'] ?? 0),
                        'sort'     => intval($item['sort'] ?? 0)
                    ]);
                }
            } else if (ChatEnum::MODEL_TYPE_EMB  || ChatEnum::MODEL_TYPE_RANKING) {
                $postM = $post['models'][0];
                $emStatus = ($post['is_enable'] ?? 0) ? 1 : 0;
                ModelsCost::create([
                    'model_id' => $mainModel['id'],
                    'type'     => intval($post['type']),
                    'channel'  => $post['channel'],
                    'name'     => $postM['name'],
                    'alias'    => empty($postM['alias']) ? $postM['name'] : $postM['alias'],
                    'price'    => $item['price'] ?? 0,
                    'status'   => $emStatus,
                    'sort'     => intval($item['sort'] ?? 0)
                ]);
            }

            // 更新默认的模型
            $model->where(['type' => $post['type']])->update(['is_default' => 0]);
            $model->where(['type' => $post['type']])
                ->where(['is_enable' => 1])
                ->order('sort asc, id desc')
                ->update(['is_default' => 1]);

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
            $mainModel = $model->where(['id' => intval($post['id'])])->findOrEmpty()->toArray();
            if (!$mainModel) {
                throw new Exception('模型已不存在了!');
            }
            if (in_array($mainModel['id'], [7, 9, 10]) && $post['is_enable'] == 0) {
                throw new Exception('该模型不可关闭!');
            }
            $models = $model->where(['type' => $mainModel['type'], 'is_enable' => 1])->column('id');
            if ($mainModel['type'] != 1 && count($models) == 2 && in_array($mainModel['id'], $models) && $post['is_enable'] == 0) {
                throw new Exception('请至少保留一个模型!');
            }
            if ($mainModel['type'] == 1 && count($models) == 1 && in_array($mainModel['id'], $models) && $post['is_enable'] == 0) {
                throw new Exception('请至少保留一个聊天模型!');
            }

            Models::update([
                'name'      => $post['name'],
                'logo'      => FileService::setFileUrl($post['logo']),
                'is_enable' => $post['is_enable'],
            ], ['id' => intval($post['id'])]);

            if (
                $mainModel['type'] == ChatEnum::MODEL_TYPE_HUMAN
                && (
                    (int)($mainModel['model_version'] ?? 0) === 7
                    || (int)$mainModel['id'] === 7
                )
            ) {
                DigitalHumanModelConfigService::syncV1ModelConfigNames((string)$post['name']);
            }

            // 对话模型的处理逻辑
            if ($mainModel['type'] == ChatEnum::MODEL_TYPE_CHAT) {
                self::updateChatSubModels($mainModel['id'], $post);
            }

            // 生图/生视频模型的处理逻辑：更新子模型售价
            if ($mainModel['type'] == ChatEnum::MODEL_TYPE_DRAW) {
                self::updateChatSubModels($mainModel['id'], $post);

                // 兼容旧 hd 配置（存在则同步名称/启停，无匹配则无副作用）
                $models = ConfigService::get('hd', 'list', []);
                if (!empty($models['channel']) && is_array($models['channel'])) {
                    foreach ($models['channel'] as $key => $item) {
                        $models['channel'][$key]['name'] = $item['model_id'] == $mainModel['id'] ? $post['name'] : $item['name'];
                        $models['channel'][$key]['status'] = $item['model_id'] == $mainModel['id'] ? $post['is_enable'] : $item['status'];
                    }
                    ConfigService::set('hd', 'list', $models);
                }
            }

            // 数字人模型的处理逻辑
            // if ($mainModel['type'] == ChatEnum::MODEL_TYPE_HUMAN) {
            //     $models = ConfigService::get('model', 'list', []);
            //     foreach ($models['channel'] as $key=>$item){
            //         $models['channel'][$key]['name'] = $item['model_id'] == $mainModel['id'] ? $post['name'] : $item['name'];
            //         $models['channel'][$key]['status'] = $item['model_id'] == $mainModel['id'] ? $post['is_enable'] : $item['status'];
            //     }
            //     ConfigService::set('model', 'list', $models);
            //     // 闪剪处理
            //     if ($mainModel['id'] == 9) {
            //         ConfigService::set('digital_human', 'shanjian_auth', $post['name']);
            //     }
            // }

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function appendChatModelPrice(array &$item): void
    {
        $subModel = ModelsCost::field(['id,price,cost_price,quota_type'])
            ->where(['model_id' => $item['id']])
            ->order('sort asc, id desc')
            ->findOrEmpty()
            ->toArray();

        $quotaType = (int)($subModel['quota_type'] ?? ChatModelsService::QUOTA_TYPE_TOKEN);
        $alias = (string)($subModel['alias'] ?? '');
        $billUnit = ChatModelsService::resolveBillUnit($quotaType, '', $alias);
        $item['model_sub_id'] = (int)($subModel['id'] ?? 0);
        $item['quota_type'] = $quotaType;
        $item['bill_unit'] = $billUnit;
        $item['quota_type_desc'] = ChatModelsService::quotaTypeDesc($quotaType, '', $alias);
        $item['price_unit_label'] = ChatModelsService::priceUnitLabel($quotaType, '', $alias);
        $item['cost_price'] = $subModel['cost_price'] ?? '0.0000';
        $item['sell_price'] = $subModel['price'] ?? '0.0000';
        $item['cost_price_desc'] = ChatModelsService::priceDesc($item['cost_price'], $quotaType, '', $alias);
        $item['sell_price_desc'] = ChatModelsService::priceDesc($item['sell_price'], $quotaType, '', $alias);
    }

    private static function appendSubModelPrice(array &$item, string $mediaType = ''): void
    {
        $quotaType = (int)($item['quota_type'] ?? ChatModelsService::QUOTA_TYPE_TOKEN);
        $alias = (string)($item['alias'] ?? '');

        // 生图/生视频为按量计费；库内未同步到 quota_type=1 时按媒体类型兜底，避免误显按 tokens
        if (
            $quotaType === ChatModelsService::QUOTA_TYPE_TOKEN
            && in_array($mediaType, ['image', 'video'], true)
        ) {
            $quotaType = ChatModelsService::QUOTA_TYPE_TIMES;
        }

        $billUnit = ChatModelsService::resolveBillUnit($quotaType, $mediaType, $alias);
        $item['quota_type'] = $quotaType;
        $item['media_type'] = $mediaType;
        $item['bill_unit'] = $billUnit;
        $item['quota_type_desc'] = ChatModelsService::quotaTypeDesc($quotaType, $mediaType, $alias);
        $item['price_unit_label'] = ChatModelsService::priceUnitLabel($quotaType, $mediaType, $alias);

        $costPrice = $item['cost_price'] ?? '0.0000';
        $sellPrice = $item['price'] ?? '0.0000';
        // 按量模型出售价以 model_price 为准（与扣费字段一致）
        if ($quotaType === ChatModelsService::QUOTA_TYPE_TIMES) {
            $modelPrice = (float)($item['model_price'] ?? 0);
            if ($modelPrice > 0) {
                $sellPrice = $item['model_price'];
            }
        }

        $item['sell_price'] = $sellPrice;
        $item['cost_price'] = $costPrice;
        $item['cost_price_desc'] = ChatModelsService::priceDesc($costPrice, $quotaType, $mediaType, $alias);
        $item['sell_price_desc'] = ChatModelsService::priceDesc($sellPrice, $quotaType, $mediaType, $alias);
    }

    private static function updateChatSubModels(int $modelId, array $post): void
    {
        if (isset($post['models']) && is_array($post['models'])) {
            foreach ($post['models'] as $item) {
                $subModelId = intval($item['id'] ?? $item['model_sub_id'] ?? 0);
                if ($subModelId <= 0) {
                    continue;
                }
                $price = $item['sell_price'] ?? $item['price'] ?? null;
                if ($price === null || $price === '') {
                    continue;
                }
                self::updateSubModelSellPrice($subModelId, $modelId, $price);
            }
            return;
        }

        $price = $post['sell_price'] ?? $post['price'] ?? null;
        if ($price === null || $price === '') {
            return;
        }

        $subModelId = intval($post['model_sub_id'] ?? 0);
        $query = ModelsCost::where(['model_id' => $modelId]);
        if ($subModelId > 0) {
            $query->where(['id' => $subModelId]);
        }
        $subModel = $query->order('sort asc, id desc')->findOrEmpty();
        if ($subModel->isEmpty()) {
            return;
        }
        self::updateSubModelSellPrice((int)$subModel['id'], $modelId, $price);
    }

    /** 按量写 model_price，按 token 写 price */
    private static function updateSubModelSellPrice(int $subModelId, int $modelId, mixed $price): void
    {
        $sub = ModelsCost::where(['id' => $subModelId, 'model_id' => $modelId])->findOrEmpty();
        if ($sub->isEmpty()) {
            return;
        }
        $formatted = self::formatPrice($price);
        $quotaType = (int)($sub['quota_type'] ?? ChatModelsService::QUOTA_TYPE_TOKEN);
        $update = ['price' => $formatted];
        if ($quotaType === ChatModelsService::QUOTA_TYPE_TIMES) {
            $update['model_price'] = $formatted;
        }
        ModelsCost::update($update, ['id' => $subModelId, 'model_id' => $modelId]);
    }

    private static function formatPrice(mixed $price): string
    {
        return number_format(max((float)$price, 0), 4, '.', '');
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
            $detail = $model->where(['id' => $id])->findOrEmpty()->toArray();

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
                ], ['id' => intval($item['id'])]);
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

    /**
     * @notes 一键开关聊天大模型（openai/google/claude）
     * @param array $params
     * @return bool
     */
    public static function switchChatModels(array $params): bool
    {
        $model = new Models();
        $model->startTrans();
        try {
            $isEnable = (int)$params['is_enable'];
            $channels = ['openai', 'google', 'claude'];
            $type = ChatEnum::MODEL_TYPE_CHAT;

            $ids = $model->where(['type' => $type])
                ->whereIn('channel', $channels)
                ->column('id');

            if (!empty($ids)) {
                Models::whereIn('id', $ids)->update(['is_enable' => $isEnable]);
                ModelsCost::whereIn('model_id', $ids)->update(['status' => $isEnable]);
            }

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }
}
