<?php

namespace app\adminapi\logic\sv;


use app\common\logic\BaseLogic;
use app\common\model\sv\SvCopywriting;
use app\common\model\sv\SvCopywritingContent;

/**
 * SvCopywritingLogic
 * @desc 文案逻辑处理
 */
class SvCopywritingLogic extends BaseLogic
{



    /**
     * @desc 获取文案详情
     * @param array $params
     * @return bool
     */
    public static function detailSvCopywriting(array $params)
    {
        try {
            // 检查文案是否存在
            $copywriting = SvCopywriting::where('id', $params['id'])->findOrEmpty();
            if (!$copywriting) {
                self::setError('文案不存在');
                return false;
            }

            $params['writingtype'] = $params['writingtype'] ?? 1;
            $SvCopywritingContent =SvCopywritingContent::where('copywriting_id', $params['id'])
            ->where('type', $params['writingtype'])->select();
         
            // 返回文案信息
            self::$returnData = $SvCopywritingContent->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 删除文案
     * @param array $params
     * @return bool
     */
    public static function deleteSvCopywriting(array $params)
    {
        try {
            if (is_string($params['id'])) {
                SvCopywriting::destroy(['id' => $params['id']]);
            } else {
                SvCopywriting::destroy($params['id']);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }





}