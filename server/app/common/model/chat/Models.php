<?php

namespace app\common\model\chat;

use app\common\model\BaseModel;
use Exception;
use think\model\concern\SoftDelete;
use think\model\relation\HasMany;

class Models extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';

    /**
     * @notes 验证模型
     * @param string $models (6:20074)
     * @return array
     * @throws Exception
     * @author kb
     */
    public static function checkModels(string $models): array
    {
        $array = explode(':', $models);
        $mainId = intval($array[0]??0);
        $costId = intval($array[1]??0);
        if (count($array) < 2) {
            throw new Exception('模型异常,请管理员重新设置!');
        }

        $mainModel = (new self())->where(['id'=>$mainId])->findOrEmpty()->toArray();
        if (!$mainModel) {
            throw new Exception('主模型异常,请管理员检查配置!');
        }

        $costModel = (new ModelsCost())->where(['id'=>$costId])->findOrEmpty()->toArray();
        if (!$costModel) {
            throw new Exception('子模型异常,请管理员检查配置!');
        }

        $config = json_decode($mainModel['configs'], true);
        $config['model_id'] = $costModel['model_id'];
        $config['channel']  = $costModel['channel'];
        $config['alias']    = $costModel['alias'];
        $config['model']    = $costModel['name'];
        $config['isQwenLong'] = false;
        if ($config['channel'] == 'qwen' and $config['model'] == 'qwen-long') {
            $config['isQwenLong'] = true;
        }

        return $config;
    }

    /**
     * @notes 子模型列表
     * @return HasMany
     */
    public function modelsLists(): HasMany
    {
        return $this->hasMany(ModelsCost::class,'model_id','id');
    }
}