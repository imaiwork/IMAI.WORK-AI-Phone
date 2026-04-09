<?php

namespace app\adminapi\lists\aiPersona;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\service\FileService;
use think\db\exception\DbException;

/**
 * AI人设数字人形象配置列表
 */
class DigitalVoiceLists extends BaseAdminDataLists implements ListsSearchInterface
{

    /**
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['ad.persona_id', 'ad.user_id'],
        ];
    }

    /**
     * 基础条件
     * @return array
     */
    public function where(): array
    {
        return [
            ['ad.delete_time', '=', null],
        ];
    }

    /**
     * 获取列表
     * @return array
     * @throws DbException
     */
    public function lists(): array
    {
        return AiPersonaDigitalVoice::alias('ad')
                                     ->with([
                                                // 关联人设主表
                                                'persona'     => function ($query) {
                                                    $query->field('id,persona_name,persona_type,avatar_url');
                                                },
                                                // 关联音色表
                                                'humanVoice' => function ($query) {
                                                    $query->field('id,model_version,gender,name,voice_id,voice_urls,status');
                                                }
                                            ])
                                     ->field([
                                                 'ad.id',
                                                 'ad.user_id',
                                                 'ad.persona_id',
                                                 'ad.voice_id',
                                                 'ad.voice_name',
                                                 'ad.provider',
                                                 'ad.preview_audio_url',
                                                 'ad.third_voice_id',
                                                 'ad.create_time'
                                             ])
                                     ->where($this->where())
                                     ->where($this->searchWhere)
                                     ->order('ad.create_time desc')
                                     ->limit($this->limitOffset, $this->limitLength)
                                     ->select()
                                     ->each(function ($item) {
                                         $item['preview_audio_url'] = FileService::getFileUrl($item['preview_audio_url']);
                                         if (!empty($item['persona'])) {
                                             $item['persona']['avatar_url'] = FileService::getFileUrl($item['persona']['avatar_url'] ?? '');
                                         }
                                         if (!empty($item['humanVoice'])) {
                                             $item['humanVoice']['voice_urls'] = FileService::getFileUrl($item['humanAnchor']['voice_urls'] ?? '');
                                         }
                                     })
                                     ->toArray();
    }

    /**
     * 统计数量
     * @return int
     * @throws DbException
     */
    public function count(): int
    {
        return AiPersonaDigitalVoice::alias('ad')
                                     ->where($this->where())
                                     ->where($this->searchWhere)
                                     ->count('ad.id');
    }
}