<?php
declare (strict_types=1);

namespace app\api\validate\wechat\sop;

use app\common\model\wechat\sop\AiWechatSopPushContent;
use app\common\validate\BaseValidate;

class PushContentValidate extends BaseValidate
{
    protected $rule = [
        'content' => 'require',
        'time'    => 'require',
        'push_id' => 'integer',
    ];

    protected $message = [
        'content.require'                    => '内容不能为空',
        'push_id.require'                    => '推送ID不能为空',
        'time.require'                       => '日期错误',
        'content_id.require'                 => '内容ID不能为空',
        'content.checkUniqueContent'         => '相同的推送内容已存在',
        'content.checkUniqueContentOnUpdate' => '相同的推送内容已存在',
    ];

    public function sceneCreatePushContent()
    {
        return $this->only(['content', 'time', 'push_id'])
//                    ->append('content', 'checkUniqueContent')
                    ->append('push_id', 'require');

    }

    public function sceneUpdatePushContent()
    {
        return $this->only(['content_id', 'content'])
//                    ->append('content', 'checkUniqueContentOnUpdate')
                    ->append('content_id', 'require');
    }

    public function sceneDeletePushContent()
    {
        return $this->only(['content_id'])->append('content_id', 'require');
    }

    // 自定义验证方法
    protected function checkUniqueContent($value, $rule, $data)
    {
        $exists = AiWechatSopPushContent::where([
                                                    'content'     => json_encode($value),
                                                    'delete_time' => null // 确保未被逻辑删除
                                                ])->value('id');
        return !$exists; // 如果存在，返回 false，表示验证失败
    }

    // 自定义更新时的唯一性验证
    protected function checkUniqueContentOnUpdate($value, $rule, $data)
    {
        $exists = AiWechatSopPushContent::where([
                                                    'id'          => $data['content_id'],
                                                    'content'     => json_encode($value),
                                                    'delete_time' => null // 确保未被逻辑删除
                                                ])->value('id'); // 排除当前更新的内容

        if ($exists) {
            return false;
        }
        return true; // 如果存在，返回 false，表示验证失败
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

} 