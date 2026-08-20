<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\service\aiPersona\DigitalAssetUsageService;
use app\common\service\FileService;
use think\db\exception\DbException;

/**
 * AI人设数字人形象配置列表
 */
class DigitalAvatarLists extends BaseApiDataLists implements ListsSearchInterface
{

    /**
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['ad.persona_id'],
        ];
    }

    /**
     * 基础条件
     * @return array
     */
    public function where(): array
    {
        return [
            ['ad.user_id', '=', $this->userId],
            ['ad.delete_time', '=', null]
        ];
    }

    /**
     * 获取列表
     * @return array
     * @throws DbException
     */
    public function lists(): array
    {
        $list = AiPersonaDigitalAvatar::availableQuery()
                                     ->with([
                                                // 关联人设主表
                                                'persona'     => function ($query) {
                                                    $query->field('id,persona_name,persona_type,avatar_url');
                                                },
                                                // 关联公共数字人形象表
                                                'humanAnchor' => function ($query) {
                                                    $query->field('id,name,image,result_url,width,height,status');
                                                },
                                     ])
                                     ->field([
                                                 'ad.id',
                                                 'ad.user_id',
                                                 'ad.persona_id',
                                                 'ad.dh_id',
                                                 'ad.avatar_name',
                                                 'ad.cover_url',
                                                 'ad.video_url',
                                                 'ad.duration',
                                                 'ad.width',
                                                 'ad.height',
                                                 'ad.third_avatar_id',
                                                 'ad.third_voice_id',
                                                 'ad.is_original_voice',
                                                 'ad.voice_url',
                                                 'ad.voice_name',
                                                 'ad.create_time'
                                             ])
                                     ->where($this->where())
                                     ->where($this->searchWhere)
                                     ->order('ad.create_time desc')
                                     ->limit($this->limitOffset, $this->limitLength)
                                     ->select()
                                     ->each(function ($item) {
                                         $item['cover_url'] = FileService::getFileUrl($item['cover_url']);
                                         $item['video_url'] = FileService::getFileUrl($item['video_url']);
                                         $item['voice_url'] = FileService::getFileUrl($item['voice_url']);
                                         if (!empty($item['persona'])) {
                                             $item['persona']['avatar_url'] = FileService::getFileUrl($item['persona']['avatar_url'] ?? '');
                                         }
                                         if (!empty($item['humanAnchor'])) {
                                             $item['humanAnchor']['image'] = FileService::getFileUrl($item['humanAnchor']['image'] ?? '');
                                         }
                                         if ($item['is_original_voice'] == 1){
                                             $item['bind_desc'] = '已绑：形象原音';
                                         }else{
                                             $item['bind_desc'] = '已绑：'. $item['voice_name'];
                                         }
                                     })
                                     ->toArray();

        $useCountMap = DigitalAssetUsageService::getAvatarUseCountMap($list);
        foreach ($list as &$item) {
            $item['use_count'] = DigitalAssetUsageService::getUseCount(
                $useCountMap,
                $item['persona_id'] ?? 0,
                $item['third_avatar_id'] ?? ''
            );
        }
        unset($item);

        return $list;
    }

    /**
     * 统计数量
     * @return int
     * @throws DbException
     */
    public function count(): int
    {
        return AiPersonaDigitalAvatar::availableQuery()
                                     ->where($this->where())
                                     ->where($this->searchWhere)
                                     ->count('ad.id');
    }
}
