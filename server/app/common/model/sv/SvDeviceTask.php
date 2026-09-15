<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use app\common\service\FileService;

use think\model\concern\SoftDelete;
class SvDeviceTask extends BaseModel {

    protected $deleteTime = 'delete_time';

    /**
     * 创建任务时自动记录当时所在企业空间(0=个人空间)。
     * 异步执行(私信回复等)扣费按该归属结算,避免用户切换团队后扣错主体。
     */
    public static function onBeforeInsert($model)
    {
        // getData('字段') 对不存在的字段会抛 property not exists,须取全量数组判断
        $data = $model->getData();
        if (!array_key_exists('team_id', $data) || $data['team_id'] === null) {
            $userId = (int)($data['user_id'] ?? 0);
            if ($userId > 0) {
                $model->set('team_id', \app\common\service\TeamContextService::currentTeamId($userId));
            }
        }
    }

     /**
     * @notes 公共处理图片,补全路径
     * @param $value
     * @return string
     * @author 张无忌
     * @date 2021/9/10 11:02
     */
    public function getAvatarAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共图片处理,去除图片域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setAvatarAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}
