<?php

namespace app\api\logic;

use app\common\model\hd\HdCueImage;
use app\common\model\hd\HdCueImageCategory;
use app\common\model\hd\HdCueWordCategory;

class HdCueLogic extends ApiLogic
{
    /**
     * @desc 文字提示词
     * @return true
     * @date 2024/7/26 17:45
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public static function word()
    {
        $categorys = HdCueWordCategory::with(['subs'])->where(['status' => 1])->select()->toArray();
        self::$returnData = $categorys;
        return true;
    }

    /**
     * @desc 图片提示词
     * @param $params
     * @return true
     * @date 2024/7/26 17:45
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public static function image($params)
    {
        $cid = $params['cid'] ?? 0;
        $page = $params['page_no'] ?? 1;
        $limit = $params['page_size'] ?? 15;

        $where = ['status' => 1];
        if (!empty($cid))
        {
            $where['cid'] = $cid;
        }
        $where1 = [];
        if (!empty($params['title']))
        {
            $where1[] = ['title', 'like', '%' . $params['title'] . '%'];
        }
        $data = [
            'lists' => [],
            'count' => 0,
            'page_no' => $page,
            'page_size' => $limit,
        ];

        $offset = max(($page - 1), 0) * $limit;

        $lists = HdCueImage::where($where)
            ->where($where1)
            ->order('sort DESC')
            ->limit($offset, $limit)
            ->select()
            ->toArray();
        $count = HdCueImage::where($where)->where($where1)->count();
        $data['lists'] = $lists;
        $data['count'] = $count;

        self::$returnData = $data;
        return true;
    }

    public static function imageCategory()
    {
        $result = HdCueImageCategory::where(['status' => 1])->select()->toArray();
        self::$returnData = $result;
        return true;
    }
}