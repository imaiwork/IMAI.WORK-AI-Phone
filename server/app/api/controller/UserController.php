<?php

namespace app\api\controller;


use app\api\logic\UserLogic;
use app\api\validate\PasswordValidate;
use app\api\validate\SetUserInfoValidate;
use app\api\validate\UserValidate;
use app\common\model\ModelConfig;
use app\common\model\user\Group;
use app\common\service\MemberService;
use think\facade\Db;
use think\response\Json;

/**
 * 用户控制器
 * Class UserController
 * @package app\api\controller
 */
class UserController extends BaseApiController
{
    public array $notNeedLogin = ['resetPassword', 'getModelConfigList'];


    /**
     * @notes 获取个人中心
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 18:19
     */
    public function center()
    {
        $data = UserLogic::center($this->userInfo);
        return $this->success('', $data);
    }


    /**
     * @notes 获取个人信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/20 19:46
     */
    public function info()
    {
        $result = UserLogic::info($this->userId);
        return $this->data($result);
    }


    /**
     * @notes 重置密码
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/16 18:06
     */
    public function resetPassword()
    {
        $params = (new PasswordValidate())->post()->goCheck('resetPassword');
        $result = UserLogic::resetPassword($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(UserLogic::getError());
    }


    /**
     * @notes 修改密码
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/20 19:16
     */
    public function changePassword()
    {
        $params = (new PasswordValidate())->post()->goCheck('changePassword');
        $result = UserLogic::changePassword($params, $this->userId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(UserLogic::getError());
    }


    /**
     * @notes 获取小程序手机号
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/21 16:46
     */
    public function getMobileByMnp()
    {
        $params = (new UserValidate())->post()->goCheck('getMobileByMnp');
        $params['user_id'] = $this->userId;
        $result = UserLogic::getMobileByMnp($params);
        if ($result === false) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('绑定成功', [], 1, 1);
    }


    /**
     * @notes 编辑用户信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/21 17:01
     */
    public function setInfo()
    {
        $params = (new SetUserInfoValidate())->post()->goCheck(null, ['id' => $this->userId]);
        $result = UserLogic::setInfo($this->userId, $params);
        if (false === $result) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 绑定/变更 手机号
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/21 17:29
     */
    public function bindMobile()
    {
        $params = (new UserValidate())->post()->goCheck('bindMobile');
        $params['user_id'] = $this->userId;
        $result = UserLogic::bindMobile($params);
        if ($result) {
            return $this->success('绑定成功', [], 1, 1);
        }
        return $this->fail(UserLogic::getError());
    }

    /**
     * 返回部门列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author L
     * @data 2024/6/14 14:18
     */
    public function getGroupList(): Json
    {
        return $this->success(data: Group::where('status', 1)->field('id,name')->select()->toArray(), show: 0);
    }

    /**
     * 返回部门列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author L
     * @data 2024/6/14 14:18
     */
    public function getModelConfigList(): Json
    {
        $list = ModelConfig::where('status', 1)->order('code', 'asc')->select()->toArray();
        $del = [
            'human_avatar_pro',  'human_voice_pro',  'human_audio_pro',  'human_video_pro',
            'human_copywriting','shanjian_copywriting_create','news_mixcut_title','sora_copywriting_create','video_copywriting_imitation',
            'sora_draw_avatar','human_avatar_sora','sora_video_create','sora_pro_video_create','automation_social_media_obtain',
        ];
        foreach ($list as $k => $v) {
            if (in_array($v['scene'], $del)) {
                unset($list[$k]);
            }
        }
        $list = array_values($list);
        return $this->success(data: $list, show: 0);
    }



    /**
     * 获取用户设备绑定二维码
     * @return Json
     * @author L
     * @data 2025/11/4 10:00
     */
    public function getDeviceBindCode(): Json
    {
        $params['user_id'] = $this->userId;
        $res = UserLogic::getDeviceBindCode($params);
        if ($res) {
            return $this->success(data: UserLogic::getReturnData());
        }
        return $this->fail(UserLogic::getError());
    }

    /**
     * 获取用户设备绑定状态
     * @return Json
     * @author L
     * @data 2025/11/4 18:00
     */
    public function getDeviceBindStatus(): Json
    {
        $params['user_id'] = $this->userId;
        $res = UserLogic::getDeviceBindStatus($params);
        if ($res) {
            return $this->success(data: UserLogic::getReturnData());
        }
        return $this->fail(UserLogic::getError());
    }

    /**
     * 绑定上级用户
     * @return Json
     */
    public function bindUser(): Json
    {
        $params = $this->request->post();
        $params['user_id'] = $this->userId;
        $res = UserLogic::bindUser($params);
        if ($res) {
            return $this->success('绑定成功');
        }
        return $this->fail(UserLogic::getError());
    }

    /**
     * 获取当前用户的会员等级 + 配额 + 当前用量
     */
    public function memberQuota(): Json
    {
        $member = MemberService::getActiveMembership($this->userId);
        $quota = MemberService::getQuota($this->userId);

        $usage = [
            // 智能体：普通智能体(KB) + 扣子智能体 + 扣子工作流
            'robots' => MemberService::countQuotaSmartAgents($this->userId),
            // 知识库：与 /api/kb.know/lists 同表 kb_know，排除系统“模型大管家”
            'knowledges' => MemberService::countQuotaKnowledges($this->userId),
            'personas' => (int)Db::name('ai_persona')->where('user_id', $this->userId)
                ->where('status', 1)->whereNull('delete_time')->count(),
            'digital_humans' => MemberService::countQuotaDigitalHumans($this->userId),
            'voices' => MemberService::countQuotaVoices($this->userId),
            // 设备：用户绑定的设备数量
            'mobiles' => (int)Db::name('sv_device')->where('user_id', $this->userId)->count(),
        ];

        return $this->data([
            'is_member' => !empty($quota['is_member']),
            'level_name' => $quota['name'] ?? '普通用户',
            'start_time' => $member?->start_time ?? 0,
            'end_time'   => $member?->end_time ?? 0,
            'quota' => [
                'max_robots' => (int)($quota['max_robots'] ?? 0),
                'max_knowledges' => (int)($quota['max_knowledges'] ?? 0),
                'max_personas' => (int)($quota['max_personas'] ?? 0),
                'max_mobiles' => (int)($quota['max_mobiles'] ?? 0),
                'max_digital_humans' => (int)($quota['max_digital_humans'] ?? 0),
                'max_voices' => (int)($quota['max_voices'] ?? 0),
                'grant_tokens' => (float)($quota['grant_tokens'] ?? 0),
                'grant_cycle' => (int)($quota['grant_cycle'] ?? 0),
                'allowed_models' => $quota['allowed_models'] ?? [],
            ],
            'usage' => $usage,
        ]);
    }
}
