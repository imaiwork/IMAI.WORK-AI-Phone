<?php

namespace app\common\model\team;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 团队成员关系模型(多企业:一个用户可属于多个团队,每团队独立企业算力)
 * Class TeamMember
 * @package app\common\model\team
 */
class TeamMember extends BaseModel
{
    use SoftDelete;

    protected $name = 'team_member';
    protected $deleteTime = 'delete_time';
    // 未删除 = 0(配合迁移把历史 NULL 刷成 0)。
    // 注意:不能覆写 withNoTrashed 用普通 where 实现,否则 withTrashed() 无法移除该条件,
    // 会导致查不到软删记录、再次入团时撞 uk_team_user 唯一键。
    protected $defaultSoftDelete = 0;

    /**
     * 新建成员必须写 delete_time=0。
     * SoftDelete 查询条件是 delete_time=0，若落库为 NULL 会被当成已删除，
     * 创建团队后成员列表会看不到创始人、member_count 也会算成 0。
     */
    public static function onBeforeInsert($model): void
    {
        $v = $model->getData('delete_time');
        if ($v === null || $v === '') {
            $model->set('delete_time', 0);
        }
    }
}
