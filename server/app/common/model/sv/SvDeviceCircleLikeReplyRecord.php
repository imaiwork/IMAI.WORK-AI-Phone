<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceCircleLikeReplyRecord extends BaseModel
{



    public static function getStatisticType($like_reply_account = '', $type = '')
    {
        $fields = [
            'like_reply_account',
            'account',
            'nickname',
            'content',
            'hash',
            'comment',
            'type',
            'image',
            'create_time',
        ];

        // type=1 时添加统计字段
        if ($type == 1) {
            $fields[] = 'sum(case when type=1 then 1 else 0 end) as like_count';
            // $fields[] = 'sum(case when type=2 then 1 else 0 end) as comment_count';
            // $fields[] = 'sum(case when type=3 then 1 else 0 end) as both_count';
        }

        $query = self::field($fields);

        if ($type == 1) {
            $query->group('like_reply_account, account');
        }



        if (!empty($like_reply_account)) {
            $query->where('like_reply_account', $like_reply_account);
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }
        $result = $query->select()->each(function ($item) {
            $accountInfo = SvDeviceCircleLikeReplyAccount::where('id', $item->like_reply_account)->findOrEmpty()->toArray();
             $item->execute_name  = $accountInfo['nickname'] ?? '';
              $item->execute_avatar  = $accountInfo['avatar'] ?? "";
               $item->execute_account = $accountInfo['account'] ?? '';
        });
       
        return $result;
    }
}
