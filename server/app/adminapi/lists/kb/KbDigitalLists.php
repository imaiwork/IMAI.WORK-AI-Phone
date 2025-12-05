<?php


namespace app\adminapi\lists\kb;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\VoiceEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\kb\KbDigital;
use app\common\service\FileService;

/**
 * 数字人列表
 */
class KbDigitalLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $where = [];
        if (!empty($this->params['user']) && $this->params['user']) {
            $where[] = ['U.sn|U.nickname', 'like', '%'.$this->params['user'].'%'];
        }

        $model = new KbDigital();
        $lists = $model
            ->alias('D')
            ->field('D.*,U.sn,U.nickname')
            ->join('user U', 'U.id = D.user_id')
            ->where($where)
            ->where($this->searchWhere)
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['user'] = [
                'id'       => $item['user_id'],
                'sn'       => $item['sn'],
                'nickname' => $item['nickname'],
            ];

            $item['dubbing'] = match ($item['channel']) {
                VoiceEnum::KDXF   => VoiceEnum::getKdxfPronounceList($item['dubbing']),
                VoiceEnum::OPENAI => VoiceEnum::getOpenAiPronounceList($item['dubbing']),
                VoiceEnum::DOUBAO => VoiceEnum::getDoubaoPronounceList($item['dubbing']),
                default => '-',
            };

            unset($item['sn']);
            unset($item['user_id']);
            unset($item['nickname']);
            unset($item['delete_time']);
        }

        return $lists;
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $where = [];
        if (!empty($this->params['user']) && $this->params['user']) {
            $where[] = ['U.sn|U.nickname', 'like', '%'.$this->params['user'].'%'];
        }

        $model = new KbDigital();
        return $model
            ->alias('D')
            ->field('D.*,U.sn,U.nickname')
            ->join('user U', 'U.id = D.user_id')
            ->where($where)
            ->where($this->searchWhere)
            ->count();
    }

    /**
     * @notes 搜索
     * @return array[]
     * @author kb
     */
    public function setSearch(): array
    {
        return [
            '='      => ['D.is_disable'],
            '%like%' => ['D.name']
        ];
    }
}