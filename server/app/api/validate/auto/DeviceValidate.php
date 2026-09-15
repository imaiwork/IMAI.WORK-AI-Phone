<?php

namespace app\api\validate\auto;

use app\common\enum\DeviceEnum;
use app\common\validate\BaseValidate;

/**
 * 设备自动任务校验
 * Class DeviceValidate
 * @package app\api\validate\auto
 * @author Qasim
 */
class DeviceValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require|max:64',
        'human_image' => 'array',
        'clip_material' =>  'array',
        'image_material' => 'array',
        // 'clue_theme' => 'require|string|max:255',
        // 'video_theme' => 'require|string|max:255',
        // 'text_theme' => 'require|string|max:255',
        'source' => 'require|checkOptSource',
        'account_type' => 'require|in:1,3,4,5',
        'start_time' => 'checkOptTime',
        'end_time' => 'checkOptTime',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备编码',
        'human_image.require' => '请选择数字人形象',
        'human_image.array' => '数字人形象必须是数组',
        'clip_material.require' => '请选择剪辑素材',
        'clip_material.array' => '剪辑素材必须是数组',
        'image_material.require' => '请选择图文内容',
        'image_material.array' => '图文内容必须是数组',
        'clue_theme.require' => '请输入线索主题',
        'clue_theme.max' => '线索主题最多255个字符',
        'video_theme.require' => '请输入视频营销主题',
        'video_theme.max' => '视频营销主题最多255个字符',
        'text_theme.require' => '请输入图文营销主题',
        'text_theme.max' => '图文营销主题最多255个字符',
        'account_type.require' => '请选择账号类型',
        'account_type.in' => '账号类型错误',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'device_code', 'character_image', 'clip_material', 'image_material', 'customer_data', ]);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'device_code', 'character_image', 'clip_material', 'image_material', 'customer_data']);
    }
    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['device_code']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['device_code']);
    }
    /**
     * @notes 操作
     * @return Validate
     */
    public function sceneOpt()
    {
        return $this->only(['device_code', 'account_type', 'source', 'start_time', 'end_time']);
    }

    protected function checkOptSource($value, $rule = null, array $data = []): bool|string
    {
        $source = filter_var($value, FILTER_VALIDATE_INT);
        if (false === $source) {
            return '任务类型错误';
        }

        $allowScenes = [
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT,
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG,
            DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE,
            DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE,
            DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
            DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER,
            DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
            DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT,
            DeviceEnum::AUTO_TASK_SCENE_FRIENDS,
            DeviceEnum::AUTO_TASK_SCENE_ACTIVE,
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER,
            DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_EXPOSURE,
            DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF,
            DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY,
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE,
            DeviceEnum::AUTO_TASK_SCENE_VIRAL_REWRITER,
            DeviceEnum::AUTO_TASK_SCENE_PRECISE_CLUES,
        ];

        return in_array($source, $allowScenes, true) ? true : '任务类型错误';
    }

    protected function checkOptTime($value, $rule = null, array $data = []): bool|string
    {
        if (null === $value || '' === $value) {
            return true;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return '时间格式错误';
        }

        $value = trim((string)$value);
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
            return '时间格式错误';
        }

        $hour = (int)$matches[1];
        $minute = (int)$matches[2];
        $second = isset($matches[3]) ? (int)$matches[3] : 0;

        if ($hour > 23 || $minute > 59 || $second > 59) {
            return '时间格式错误';
        }

        return true;
    }
}

