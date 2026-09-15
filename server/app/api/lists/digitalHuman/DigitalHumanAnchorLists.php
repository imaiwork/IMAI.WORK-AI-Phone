<?php

namespace app\api\lists\digitalHuman;

use app\api\lists\BaseApiDataLists;
use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\service\digitalHuman\PublicAnchorBindService;
use app\common\service\FileService;
use app\common\service\UserDisplaySanitizer;

class DigitalHumanAnchorLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != ''){
            $this->searchWhere[] = ['status', 'in', explode(',',$status)];
        }
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list                = DigitalHumanAnchor::where($this->searchWhere)
                                                 ->order(['id' => 'desc'])
                                                 ->limit($this->limitOffset, $this->limitLength)
                                                 ->select()
                                                 ->toArray();

        foreach ($list as &$item) {
            $item['pic']         = !empty($item['image']) ? FileService::getFileUrl($item['image']) : '';
            $item['result_url']  = !empty($item['result_url']) ? FileService::getFileUrl($item['result_url']) : '';
            $item['create_time'] = !empty($item['create_time']) ? $item['create_time'] : '';
            $item['update_time'] = !empty($item['update_time']) ? $item['update_time'] : '';
            $ownerId            = (int)($item['user_id'] ?? 0);
            $weiju              = PublicAnchorBindService::ownWeiju((int)$item['id'], $ownerId);
            $chanjing           = PublicAnchorBindService::ownChanjing((int)$item['id'], $ownerId);
            $cloneMode          = (int)($item['clone_mode'] ?? 2);
            $shanjianFast       = PublicAnchorBindService::ownShanjian((int)$item['id'], $ownerId, ShanjianAnchorLogic::CLONE_TYPE_FAST);
            $shanjianPro        = $cloneMode === 3
                ? PublicAnchorBindService::ownShanjian((int)$item['id'], $ownerId, ShanjianAnchorLogic::CLONE_TYPE_PRO)
                : null;
            $shanjianDisplay = ($cloneMode === 3 && !empty($shanjianPro) && !empty($shanjianPro->anchor_id))
                ? $shanjianPro
                : $shanjianFast;
            $item['anchor_ids'] = [
                'weiju_anchor_id'    => $weiju->anchor_id ?? '',
                'chanjing_anchor_id' => $chanjing->anchor_id ?? '',
                'shanjian_anchor_id' => $shanjianDisplay->anchor_id ?? '',
            ];
            $item['extra_info'] = [
                'width'  => $weiju->width ?? ($chanjing->width ?? ''),
                'height' => $weiju->height ?? ($chanjing->height ?? ''),
                'shanjian_voice_id' => !empty($shanjianFast) ? ($shanjianFast->voice_id ?? '') : '',
            ];
            $item['status_text'] = PublicAnchorBindService::publicStatusText((int)$item['status']);
            $item = UserDisplaySanitizer::digitalHumanAnchorItem($item);
        }
        return $list;
    }

    public function count(): int
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != ''){
            $this->searchWhere[] = ['status', 'in', explode(',',$status)];
        }
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return DigitalHumanAnchor::where($this->searchWhere)->count();
    }
}
