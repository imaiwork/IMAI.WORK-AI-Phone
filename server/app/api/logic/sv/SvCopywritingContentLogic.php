<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvCopywriting;
use app\common\model\sv\SvCopywritingContent;

/**
 * SvCopywritingContentLogic
 * @desc 文案内容逻辑处理
 */
class SvCopywritingContentLogic extends SvBaseLogic
{
    /**
     * @desc 添加文案内容
     * @param array $params
     * @return bool
     */
    public static function addSvCopywritingContent(array $params)
    {
        try {

            $params['user_id'] = self::$uid;
            // 检查文案是否已存在
            $copywriting = SvCopywriting::where('id', $params['copywriting_id'])->where('user_id',self::$uid)->findOrEmpty();
            if ($copywriting->isEmpty()) {
                self::setError('文案不存在');
                return false;
            }

            // 添加文案内容
            $content = SvCopywritingContent::create($params);
            self::$returnData = $content->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取文案内容详情
     * @param array $params
     * @return bool
     */
    public static function detailSvCopywritingContent(array $params)
    {
        try {
            // 检查文案内容是否存在
            $content = SvCopywritingContent::where('id', $params['id'])->where('user_id',self::$uid)->findOrEmpty();
            if (!$content) {
                self::setError('文案内容不存在');
                return false;
            }

            // 返回文案内容信息
            self::$returnData = $content->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新文案内容
     * @param array $params
     * @return bool
     */
    public static function updateSvCopywritingContent(array $params)
    {
        try {
            // 检查文案内容是否存在
            $content = SvCopywritingContent::where('id', $params['id'])->where('user_id',self::$uid)->find();
            if (!$content) {
                self::setError('文案内容不存在');
                return false;
            }
            $res = SvCopywritingContent::where('id', $params['id'])->update($params);
            if ($res){
                return true;
            }
            // 更新文案内容信息
            self::setError('更新失败');
            return false;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除文案内容
     * @param array $params
     * @return bool
     */
    public static function deleteSvCopywritingContent(array $params)
    {
        try {
            // 检查文案内容是否存在
            $content = SvCopywritingContent::where('id', $params['id'])->where('user_id',self::$uid)->findOrEmpty();
            if (!$content) {
                self::setError('文案内容不存在');
                return false;
            }

            // 删除文案内容
            SvCopywritingContent::destroy($params['id']);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

}