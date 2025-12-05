<?php


namespace app\api\lists\hd;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hd\HdImageCases;
use app\common\service\FileService;


/**
 * 案例列表
 * Class HdImageCaseLists
 * @package app\api\lists\hd
 */
class HdImageCaseLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
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
        $types = $this->request->get('case_type', '');
        $types = explode(',', $types);

        return HdImageCases::where($this->searchWhere)
            ->when($types, function ($query) use ($types){
                $query->whereIn('case_type', $types);
            })
            ->when($this->request->get('user_type'), function ($query){
                if($this->request->get('user_type') == 1){
                    $query->where('user_id', 0);
                }else{
                    $query->where('user_id', $this->userId);
                }
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->where('status', 1)
            ->where('user_id', 'in', [0, $this->userId])
            ->order(['user_id' => 'desc', 'id' => 'desc'])
            ->select()
            ->each(function ($item) {
                $params = json_decode($item['params'], true);

                foreach ($params['images'] ?? [] as $key => $value) {

                    $params['images'][$key] = $value ? FileService::getFileUrl($value) : "";
                }

                $item['params'] = $params;

                $item['result_image'] = FileService::getFileUrl($item['result_image']);
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
        $types = $this->request->get('case_type', '');
        $types = explode(',', $types);

        return HdImageCases::where($this->searchWhere)
            ->when($types, function ($query) use ($types){
                $query->whereIn('case_type', $types);
            })
            ->when($this->request->get('user_type'), function ($query){
                if($this->request->get('user_type') == 1){
                    $query->where('user_id', 0);
                }else{
                    $query->where('user_id', $this->userId);
                }
            })
            ->where('user_id', 'in', [0, $this->userId])
            ->count();
    }
}
