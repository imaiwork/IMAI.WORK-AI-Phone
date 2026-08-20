<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\ClueCustomerLists;
use app\api\lists\aiPersona\GroupBuyReportLists;
use app\api\lists\aiPersona\LeadScrapingReportLists;
use app\api\lists\aiPersona\MessageTaskLists;
use app\api\lists\aiPersona\PublishTaskLists;
use app\api\lists\aiPersona\SameCityTouchLists;
use app\api\lists\aiPersona\WechatCircleInteractionLists;
use app\api\lists\aiPersona\WechatCreateGroupLists;
use app\api\lists\aiPersona\WechatCustomerLists;
use app\api\lists\aiPersona\WechatMessageReplyLists;
use app\api\validate\aiPersona\TaskValidate;
use think\exception\HttpResponseException;

class TaskController extends BaseApiController
{
    public function publish()
    {
        try {
            (new TaskValidate())->get()->goCheck('publish');
            return $this->dataLists(new PublishTaskLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function message()
    {
        try {
            (new TaskValidate())->get()->goCheck('message');
            return $this->dataLists(new MessageTaskLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function messageStatistics()
    {
        try {
            (new TaskValidate())->get()->goCheck('messageStatistics');
            return $this->data((new MessageTaskLists())->statistics());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function leadScrapingReport()
    {
        try {
            (new TaskValidate())->get()->goCheck('leadScrapingReport');
            return $this->dataLists(new LeadScrapingReportLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function sameCityTouch()
    {
        try {
            (new TaskValidate())->get()->goCheck('sameCityTouch');
            return $this->dataLists(new SameCityTouchLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function groupBuyReport()
    {
        try {
            (new TaskValidate())->get()->goCheck('groupBuyReport');
            return $this->dataLists(new GroupBuyReportLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function clueCustomer()
    {
        try {
            (new TaskValidate())->get()->goCheck('clueCustomer');
            return $this->dataLists(new ClueCustomerLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function wechatCustomer()
    {
        try {
            (new TaskValidate())->get()->goCheck('wechatCustomer');
            return $this->dataLists(new WechatCustomerLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function wechatCreateGroup()
    {
        try {
            (new TaskValidate())->get()->goCheck('wechatCreateGroup');
            return $this->dataLists(new WechatCreateGroupLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function wechatCircleInteraction()
    {
        try {
            (new TaskValidate())->get()->goCheck('wechatCircleInteraction');
            return $this->dataLists(new WechatCircleInteractionLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function wechatStatistics()
    {
        try {
            (new TaskValidate())->get()->goCheck('wechatStatistics');

            $newFriendCount = (new WechatCustomerLists())->count();
            $autoReplyCount = (new WechatMessageReplyLists())->count();
            $autoGroupCount = (new WechatCreateGroupLists())->count();

            return $this->data([
                'title' => '帮我管理微信客户',
                'subtitle' => '私信回复 · 自动加好友 · 自动拉群',
                'summary' => [
                    'new_friend_count' => $newFriendCount,
                    'auto_reply_count' => $autoReplyCount,
                    'auto_group_count' => $autoGroupCount,
                ],
                'cards' => [
                    [
                        'key' => 'new_friend',
                        'name' => '新增好友',
                        'count' => $newFriendCount,
                        'api' => '/api/aiPersona.task/wechatCustomer',
                    ],
                    [
                        'key' => 'auto_reply',
                        'name' => '自动回复',
                        'count' => $autoReplyCount,
                        'api' => '/api/aiPersona.task/wechatMessageReply',
                    ],
                    [
                        'key' => 'auto_group',
                        'name' => '自动拉群',
                        'count' => $autoGroupCount,
                        'api' => '/api/aiPersona.task/wechatCreateGroup',
                    ],
                ],
                'tabs' => [
                    [
                        'key' => 'private_reply',
                        'name' => '私信回复',
                        'count' => $autoReplyCount,
                        'api' => '/api/aiPersona.task/wechatMessageReply',
                    ],
                    [
                        'key' => 'new_friend',
                        'name' => '新好友',
                        'count' => $newFriendCount,
                        'api' => '/api/aiPersona.task/wechatCustomer',
                    ],
                    [
                        'key' => 'group_record',
                        'name' => '拉群记录',
                        'count' => $autoGroupCount,
                        'api' => '/api/aiPersona.task/wechatCreateGroup',
                    ],
                ],
            ]);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function wechatMessageReply()
    {
        try {
            (new TaskValidate())->get()->goCheck('wechatMessageReply');
            return $this->dataLists(new WechatMessageReplyLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
