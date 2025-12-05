<?php


namespace app\common\enum\draw;

class DrawRecordEnum
{
    // 文本审核，图片审核
    const TYPE_TEXT = 1;
    const TYPE_IMAGE = 2;

    //审核状态
    const CENSOR_STATUS_WAIT = 0;//未审核
    const CENSOR_STATUS_COMPLIANCE = 1;//合规
    const CENSOR_STATUS_NON_COMPLIANCE = 2;//不合规
    const CENSOR_STATUS_SUSPECTED = 3;//疑似
    const CENSOR_STATUS_FAIL = 4;//审核失败

    /**
     * @notes 审核状态
     * @param $type
     * @return string
     * @author mjf
     * @date 2024/1/26 15:50
     */
    public static function getCensorStatusDesc($type): string
    {
        $desc =  [
            self::CENSOR_STATUS_WAIT => '未审核',
            self::CENSOR_STATUS_COMPLIANCE => '合规',
            self::CENSOR_STATUS_NON_COMPLIANCE => '不合规',
            self::CENSOR_STATUS_SUSPECTED => '疑似',
            self::CENSOR_STATUS_FAIL => '审核失败',
        ];
        return $desc[$type] ?? '';
    }

}