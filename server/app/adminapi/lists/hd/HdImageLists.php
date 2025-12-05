<?php


namespace app\adminapi\lists\hd;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hd\HdLog;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\model\hd\HdImage;


/**
 * HdImage列表
 * Class HdImageLists
 * @package app\adminapi\listsmp
 */
class HdImageLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @desc 设置搜索条件
     * @return array[]
     * @date 2024/5/23 11:52
     * @author dagouzi
     */
    public function setSearch(): array
    {

        return [];
    }


    /**
     * @desc 获取列表
     * @return array
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public function lists(): array
    {
        return HdLog::alias('l')
            ->leftJoin('user u', 'u.id = l.user_id and l.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('type', []), function ($query) {
                $query->whereIn('l.type', $this->request->get('type'));
            })
            ->when($this->request->get('model_type'), function ($query) {
                $query->where('l.model_type', $this->request->get('model_type'));
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->whereIn('l.type', [1, 2, 3, 4, 5])
            ->field('l.id,l.user_id,l.type,l.create_time,u.nickname,u.avatar,l.task_id,l.params,l.model_type')
            ->order('l.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['avatar']     = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
                // 获取对应列表
                $item['images'] = HdImage::where('log_id', $item['id'])->field('image, task_status')->select()->toArray();

                $points = 0;
                $images = 0;

                //转换
                switch ($item['type']) {
                    case 1:
                        $scene = AccountLogEnum::TOKENS_DEC_GOODS_IMAGE;
                        $typeName = '商品图';
                        break;
                    case 2:
                        $scene = AccountLogEnum::TOKENS_DEC_MODEL_IMAGE;
                        $typeName = '模特换衣图';
                        break;
                    case 3:
                        $scene = [AccountLogEnum::TOKENS_DEC_TEXT_TO_IMAGE,AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_IMAGE, AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE];
                        $typeName = '文生图';
                        break;
                    case 4:
                        $scene = [AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE, AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE];
                        $typeName = '图生图';
                        break;
                    case 5:
                        $scene = AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE;
                        $typeName = '海报图';
                        break;
                }

                $item['type_name'] = $typeName;
                $item['params']    = json_decode($item['params'], true) ?? [];

                //扣费记录
                UserTokensLog::where('user_id', $item['user_id'])
                    ->whereIn('change_type', $scene)
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type')
                    ->select()
                    ->each(function ($item) use (&$points, &$images) {
                        $info = json_decode($item['extra'], true);

                        $images += $info['生成图片数'] ?? 0;
                        $points += $info['实际消耗算力'] ?? 0;
                    });

                $item['points']          = $points;
                $item['image_count']     = $images;
            })
            ->toArray();
    }


    /**
     * @desc 获取数量
     * @return int
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DbException
     * @author dagouzi
     */
    public function count(): int
    {
        return HdLog::alias('l')
            ->leftJoin('user u', 'u.id = l.user_id and l.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('type', []), function ($query) {
                $query->whereIn('l.type', $this->request->get('type'));
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->whereIn('l.type', [1, 2, 3, 4])
            ->count();
    }
}
