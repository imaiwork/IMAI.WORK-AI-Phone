<?php

namespace app\adminapi\validate\lianlian;

use app\common\validate\BaseValidate;


/**
 * 场景验证器
 * Class LlSceneValidate
 * @package app\adminapi\lianlian\validate
 */
class LlSceneValidate extends BaseValidate
{
    protected $rule = [
        'id'    => 'require',
        'logo' => 'require',
        'name' => 'require',
        'description' => 'require',
        'coach_name' => 'require',
        'coach_persona' => 'require',
        'coach_voice' => 'require',
        'coach_emotion' => 'require',
        'coach_intensity' => 'require',
    ];


    protected $message = [
        'id.require'      => '主键参数缺失',
        'logo.require' => 'logo不能为空',
        'name.require' => '名称不能为空',
        'description.require' => '描述不能为空',
        'coach_name.require' => '陪练者名称不能为空',
        'coach_persona.require' => '陪练者人设不能为空',
        'coach_voice.require' => '陪练者音色不能为空',
        'coach_emotion.require' => '陪练者情感不能为空',
        'coach_intensity.require' => '陪练者情感程度不能为空',
    ];
    protected $scene = [
        'add' => [
            'logo' => 'require',
            'name' => 'require',
            'description' => 'require',
            'coach_name' => 'require',
            'coach_persona' => 'require',
            'coach_voice' => 'require',
            'coach_emotion' => 'require',
            'coach_intensity' => 'require',
        ],
        'delete' => [
            "id",
        ],
        'detail' => [
            "id",
        ],
        'edit' => [
            "id",
            'logo' => 'require',
            'name' => 'require',
            'description' => 'require',
            'coach_name' => 'require',
            'coach_persona' => 'require',
            'coach_voice' => 'require',
            'coach_emotion' => 'require',
            'coach_intensity' => 'require',
        ],
        'changeStatus' => [
            "id",
        ],
    ];
}
