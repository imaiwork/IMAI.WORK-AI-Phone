<?php


namespace app\api\validate\kb;

use app\common\model\user\User;
use app\common\pgsql\KbEmbedding;
use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 知识库训练数据参数验证器
 */
class KbTeachValidate extends BaseValidate
{
    protected $rule = [
        'uuids'     => 'require|array|isArrUuid',
        'uuid'      => 'require|isUUID',
        'kb_id'     => 'require|number',
        'fd_id'     => 'require|number',
        'tr_id'     => 'require|number',
        'method'    => 'require|in:1,2,3,4',
        'documents' => 'require|array|checkContent',
        'question'  => 'require|max:5000',
        'answer'    => 'max:3000',
        'files'     => 'array',
        'images'    => 'array'
    ];

    protected $scene = [
        'uuids'  => ['uuids'],
        'uuid'   => ['uuid'],
        'kb_id'  => ['kb_id'],
        'fd_id'  => ['fd_id'],
        'tr_id'  => ['tr_id'],

        'update' => ['uuid', 'question', 'answer', 'images', 'files'],
        'delete' => ['uuids', 'kb_id'],
        'reset'  => ['uuids', 'kb_id'],

        'insert' => ['kb_id', 'fd_id', 'question', 'answer', 'images', 'files'],
        'import' => ['kb_id', 'method', 'documents'],
        'tests'  => ['kb_id', 'question'],

        'check'  => ['kb_id', 'fd_id', 'uuids']
    ];

    protected $message = [
        'uuids.require'     => '请选择数据',
        'uuid.require'      => '请选择数据',
        'kb_id.require'     => '知识库异常',
        'fd_id.require'     => '文件库异常',
        'tr_id.require'     => '搜索测试数据异常',
        'method.require'    => '请选择导入类型',
        'method.in'         => '导入类型不支持',
        'documents.require' => '请选择导入的文件',
        'documents.array'   => '导入文件格式错误'
    ];

    protected function isUUID($value): bool|string
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $result = preg_match($pattern, $value) === 1;

        if (!$result) {
            return 'uuid参数格式错误';
        }
        return true;
    }

    protected function isArrUuid($value): bool|string
    {
        foreach ($value as $v) {
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
            $result = preg_match($pattern, $v) === 1;

            if (!$result) {
                return 'uuid参数格式错误';
            }
        }

        return true;
    }

    protected function checkContent($value, $rule, $data): bool|string
    {
        if (empty($value)) {
            return '至少需要1条数据';
        }

//        $paragraph = 0;
        foreach ($value as $item) {
//            if (isset($item['name']) && mb_strlen($item['name']) > 5000) {
//                return '导入的文件名称不能超过5000个字符';
//            }

            if (empty($item['data'])) {
                return '导入的数据不可为空';
            }

            foreach ($item['data'] as $m) {
//                $paragraph += 1;
                if (!isset($m['q']) || !isset($m['a'])) {
                    return '数据格式不正确,缺失q或a';
                }

                if (mb_strlen($m['q']) > 30000) {
                    return '问题内容长度不可超过30000个字符每段';
                }

                if (mb_strlen($m['a']) > 8000) {
                    return '补充内容长度不可超过8000个字符每段';
                }
            }
        }

        // 为-1时则不限制
//        $totalSpace = (new User())->where(['id' => $data['user_id']])->value('total_space');
//        if ($totalSpace >= 0) {
//            $useSpace = (new KbEmbedding())->where(['user_id' => $data['user_id'], 'is_delete' => 0])->count();
//            $surplus = $totalSpace - $useSpace;
//            if ($surplus < $paragraph) {
//                return '知识库存储空间不足: ' . $surplus;
//            }
//        }

        return true;
    }
}