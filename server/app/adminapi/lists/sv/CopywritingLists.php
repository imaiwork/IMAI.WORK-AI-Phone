<?php
namespace app\adminapi\lists\sv;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCopywriting;
use app\common\model\sv\SvCopywritingContent;
class CopywritingLists extends BaseAdminDataLists implements ListsSearchInterface
{
     /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author Lee
     * @date 2025-05-14 15:15:09
     */
     public function lists(): array
     {
        return SvCopywriting::alias('cw')
            ->field('cw.id,ug.change_amount,cw.keyword')
            ->join('sv_copywriting_task cwt','cwt.copywriting_id = cw.id')
            ->join('user_tokens_log ug','ug.task_id = cwt.task_id')
            ->where($this->searchWhere)
            ->where('cw.type',$this->request->param('type'))
            ->order('cw.id','desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function($item){
                $item['title'] = date('Y-m-d H:i:s');
                $item['copies_num'] = SvCopywritingContent::where('type',3)->where('copywriting_id',$item['id'])->count();
                $item['title_num'] = SvCopywritingContent::where('type',1)->where('copywriting_id',$item['id'])->count();
                $item['subtitle_num'] = SvCopywritingContent::where('type',2)->where('copywriting_id',$item['id'])->count();
            })
            ->toArray();
     }
     /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvCopywriting::alias('cw')
            ->field('cw.id,ug.change_amount')
            ->join('sv_copywriting_task cwt','cwt.copywriting_id = cw.id')
            ->join('user_tokens_log ug','ug.task_id = cwt.task_id')
            ->where($this->searchWhere)
            ->where('cw.type',$this->request->param('type'))
            ->order('cw.id','desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->count();
    }

     /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function setSearch(): array
    {
        return [
            "%like%" =>  ['keyword'],
        ];
    }
}