<?php


namespace app\api\lists\audio;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\audio\AudioInfo;
use app\common\model\audio\Audio;
use app\common\service\FileService;
use app\common\service\ConfigService;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class AudioLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AudioInfo::field('id,name,task_id,task_type,user_id,url,response,status,translation,speaker,remark,text,create_time,language,ws_url')
            ->where($this->searchWhere)
            ->when($this->request->get('status') != null, function ($query) {
                $query->where('status', $this->request->get('status'));
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['url']        = FileService::getFileUrl($item['url']);
                $item['ws_url']     = $item['ws_url'] ?: '';
                $item['text']       = $item['text'] ?: '';

                $meetingConfig = ConfigService::get('meeting', 'config', []);

                $item['language_name'] = '';
                $item['translation_name'] = '';

                foreach ($meetingConfig['language'] as $key => $value) {

                    if($value['code'] == $item['language']){

                        $item['language_name'] = $value['name'];
                    }
                }

                foreach ($meetingConfig['translation'] as $key => $value) {

                    if($value['code'] == $item['translation']){

                        $item['translation_name'] = $value['name'];
                    }
                }
            })
            ->toArray();
    }

    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AudioInfo::field('id,name,task_id,task_type,user_id,url,response,status,translation,speaker,remark,text,create_time,language,ws_url')
            ->where($this->searchWhere)
            ->when($this->request->get('status') != null, function ($query) {
                $query->where('status', $this->request->get('status'));
            }) 
            ->count();
    }
}
