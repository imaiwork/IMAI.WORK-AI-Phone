<?php

namespace app\api\logic;

use app\api\logic\material\FfmpegFileLogic;
use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\file\File;
use app\common\model\human\HumanAnchor;
use app\common\model\material\FfmpegFile;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\db\exception\DbException;
use think\facade\Db;
use think\facade\Log;

/**
 * 数字人形象合并查询逻辑（公共表+渠道表+闪剪表）
 */
class DigitalHumanLogic extends ApiLogic
{
    /**
     * 获取数字人形象列表（合并新旧数据）
     * @param array $params
     * @return array 包含总条数、分页数据的结果集
     * @throws DbException
     */
    public static function getDigitalHumanAnchorList(array $params): array
    {
        $userId        = self::$uid;
        $pageNo        = isset($params['page_no']) && $params['page_no'] > 0 ? (int)$params['page_no'] : 1;
        $pageSize      = isset($params['page_size']) && $params['page_size'] > 0 ? (int)$params['page_size'] : 10;
        $offset        = ($pageNo - 1) * $pageSize;
        $status        = $params['status'] ?? 0; //1 展示成功+生成中 2 只展示成功
        $filter        = $params['filter'] ?? 0; //1 过滤数字人 2 过滤闪剪
        $publicWhere   = [];
        $humanWhere    = [];
        $shanjianWhere = [];

        $commonWhere = [
            ['user_id', '=', $userId],
            ['delete_time', '=', null]
        ];

        // 展示生成中+成功
        if ($status == 1) {
            $publicWhere   = [
                ['status', 'in', [0, 1, 2]]
            ];
            $humanWhere    = [
                ['status', 'in', [0, 1]]
            ];
            $shanjianWhere = [
                ['status', 'in', [1, 3, 4, 5, 6]]
            ];
        }

        // 展示成功
        if ($status == 2) {
            $publicWhere   = [
                ['status', '=', 2]
            ];
            $humanWhere    = [
                ['status', '=', 1]
            ];
            $shanjianWhere = [
                ['status', '=', 6]
            ];
        }

        //公共数字人表（新数据，主表）
        $queryPublic = Db::name('digital_human_anchor') // 对应 iw_digital_human_anchor
                         ->field([
                                     'id',
                                     'user_id',
                                     Db::raw("'' as anchor_id"),
                                     'name',
                                     'image as pic',  // 数字人封面
                                     'status', // 原生状态：0生成中 1部分完成 2已完成 3生成失败
                                     'remark', // 失败原因
                                     'result_url', // 视频链接
                                     'create_time',
                                     'update_time',
                                     Db::raw("'public_anchor' as source_type"), // 标记数据来源：公共表
                                     Db::raw("0 as dh_id"), // 公共表无外键，默认0
                                     Db::raw("0 as model_version"), // 公共表无模型版本，默认0
                                     Db::raw("'' as extra_info") // 扩展字段（存储各表特有信息）
                                 ])
                         ->where($commonWhere)
                         ->where($publicWhere)
                         ->buildSql(); // 生成带括号的子查询字符串

        //数字人形象
        $queryHuman = Db::name('human_anchor') // 对应 iw_human_anchor
                        ->field([
                                    'id',
                                    'user_id',
                                    'anchor_id',
                                    'name',
                                    'pic',
                                    'status',
                                    'remark',
                                    'url as result_url',
                                    'create_time',
                                    'update_time',
                                    Db::raw("'human_anchor' as source_type"),
                                    'dh_id',
                                    'model_version',
                                    // 存储渠道表特有字段（按需扩展）
                                    Db::raw("JSON_OBJECT('type',type, 'width',width, 'height',height) as extra_info")
                                ])
                        ->where($commonWhere)
                        ->where($humanWhere)
                        ->where('model_version', 'in', [1, 7])
                        ->where('dh_id', '=', 0)
                        ->buildSql();
        //闪剪形象
        $queryShanjian = Db::name('shanjian_anchor')
                           ->field([
                                       'id',
                                       'user_id',
                                       'anchor_id',
                                       'name',
                                       'pic',
                                       'status',
                                       'remark',
                                       'anchor_url as result_url',
                                       'create_time',
                                       'update_time',
                                       Db::raw("'shanjian_anchor' as source_type"), // 标记数据来源：闪剪表
                                       'dh_id',
                                       Db::raw("8 as model_version"), // 公共表无模型版本，默认0
                                       // 存储闪剪表特有字段（按需扩展）
                                       Db::raw("JSON_OBJECT('voice_id', voice_id, 'voice_model', voice_model) as extra_info")
                                   ])
                           ->where($commonWhere)
                           ->where($shanjianWhere)
                           ->where('dh_id', '=', 0)
                           ->buildSql();

        // 4. 合并三个子查询（UNION ALL）+ 分页 + 排序
        if ($filter == 1) {
            $unionSql = "{$queryPublic} UNION ALL {$queryShanjian}";
        } else if ($filter == 2) {
            $unionSql = "{$queryPublic} UNION ALL {$queryHuman}";
        } else {
            $unionSql = "{$queryPublic} UNION ALL {$queryHuman} UNION ALL {$queryShanjian}";
        }

        // 派生表别名 `t`，按创建时间倒序（最新数据在前）
        $lists = Db::table("({$unionSql}) AS t")
                   ->order('create_time', 'desc')
                   ->limit($offset, $pageSize)
                   ->select()
                   ->toArray();

        // 5. 计算总条数（三张表符合条件的记录数之和，效率高于UNION后count）
        $total = self::calcTotalCount($userId, $status, $filter);

        // 6. 格式化数据（时间戳转日期、空值处理）
        $lists = self::formatListData($lists);

        // 7. 返回统一格式结果
        return [
            'count'      => $total,
            'lists'      => $lists,
            'page_no'    => $pageNo,
            'page_size'  => $pageSize,
            'total_page' => (int)ceil($total / $pageSize)
        ];
    }

    public static function createPublicAnchor(array $params)
    {
        try {
            if (empty($params['name']) || empty($params['anchor_url']) || empty($params['authorized_url'])) {
                throw new \Exception('参数错误');
            }
            $res = DigitalHumanAnchor::where('name', $params['name'])->findOrEmpty();
            if (!$res->isEmpty()) {
                throw new \Exception('名称已存在');
            }
            $dhInsert         = [
                'user_id'        => self::$uid,
                'name'           => $params['name'],
                'image'          => $params['pic'] ?? '',
                'task_ids'       => json_encode([
                                                    'shanjian' => ['task_id' => '', 'status' => 0],
                                                    'weiju'    => ['task_id' => '', 'status' => 0],
                                                    'chanjing' => ['task_id' => '', 'status' => 0]
                                                ]),
                'status'         => 0,
                'result_url'     => FileService::setFileUrl($params['anchor_url']),
                'authorized_url' => FileService::setFileUrl($params['authorized_url']),
                'width'          => $params['width'] ?? 0,
                'height'         => $params['height'] ?? 0,
            ];
            $dh               = DigitalHumanAnchor::create($dhInsert);
            self::$returnData = $dh->refresh()->toArray();
            $anchorData       = [
                'user_id' => self::$uid,
                'file_id' => File::where('uri', $dhInsert['result_url'])->value('id'),
                'type'    => 20,
                'uri'     => $params['anchor_url']
            ];
            $authData         = [
                'user_id' => self::$uid,
                'file_id' => File::where('uri', $dhInsert['authorized_url'])->value('id'),
                'type'    => 20,
                'uri'     => $params['authorized_url']
            ];
            FfmpegFileLogic::addFfmpegFile($anchorData);
            FfmpegFileLogic::addFfmpegFile($authData);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function deletePublicAnchor(array $data)
    {
        try {
            if (is_string($data['id'])) {
                DigitalHumanAnchor::destroy(['id' => $data['id']]);
                HumanAnchor::destroy(['dh_id' => $data['id']]);
                ShanjianAnchor::destroy(['dh_id' => $data['id']]);
            } else {
                DigitalHumanAnchor::whereIn('id', $data['id'])->select()->delete();
                HumanAnchor::destroy(['dh_id' => $data['id']]);
                ShanjianAnchor::destroy(['dh_id' => $data['id']]);
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function createDigitalHumanAnchorCron()
    {
        try {
            $lists = DigitalHumanAnchor::where('status', '=', 0)->select();
            if ($lists->isEmpty()) {
                return true;
            }
            $lists = $lists->toArray();
            Log::channel('shanjian')->write('定时任务开启：' . json_encode($lists, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            foreach ($lists as $item) {
                $anchorFile = FfmpegFile::where('uri', '=', FileService::setFileUrl($item['result_url']))->findOrEmpty();
                $authFile   = FfmpegFile::where('uri', '=', $item['authorized_url'])->findOrEmpty();
                if ($anchorFile->isEmpty() || $authFile->isEmpty()) {
                    continue;
                }
                if (in_array($authFile->status, [0, 1, "0", "1"]) || in_array($anchorFile->status, [0, 1, "0", "1"])) {
//                    if ($file->status == 3) {
//                        $update['status'] = 3;
//                        $update['remark'] = '授权文件转码失败，请重试';
//                        DigitalHumanAnchor::update($update, ['id' => $item['id']]);
//                    }
                    continue;
                }
                Log::channel('shanjian')->write('定时任务执行：' . $item['id']);
                $shanjianData = [
                    'user_id'        => $item['user_id'],
                    'dh_id'          => $item['id'],
                    'name'           => $item['name'],
                    'anchor_url'     => FileService::getFileUrl($item['result_url']),
                    'pic'            => FileService::getFileUrl($item['image']),
                    'authorized_url' => FileService::getFileUrl($item['authorized_url']),
                    'authorized_pic' => empty($item['authorized_pic']) ? '' : FileService::getFileUrl($item['authorized_pic']),
                ];
                $weijuData    = [
                    'user_id'       => $item['user_id'],
                    'dh_id'         => $item['id'],
                    'name'          => $item['name'],
                    'url'           => FileService::getFileUrl($item['result_url']),
                    'pic'           => FileService::getFileUrl($item['image']),
                    'width'         => $item['width'],
                    'height'        => $item['height'],
                    'model_version' => 1
                ];
                $chanjingData = [
                    'user_id'       => $item['user_id'],
                    'dh_id'         => $item['id'],
                    'name'          => $item['name'],
                    'url'           => FileService::getFileUrl($item['result_url']),
                    'pic'           => FileService::getFileUrl($item['image']),
                    'width'         => $item['width'],
                    'height'        => $item['height'],
                    'model_version' => 7
                ];
                ShanjianAnchorLogic::add($shanjianData);
                HumanLogic::createAnchor($weijuData);
                HumanLogic::createAnchor($chanjingData);

            }
            return true;
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

    public static function getDigitalHumanAnchorStatusCron()
    {
        $lists = DigitalHumanAnchor::where('status', '=', 1)->select();
        if ($lists->isEmpty()) {
            return true;
        }
        $lists = $lists->toArray();
        foreach ($lists as $item) {
            $task_ids = json_decode($item['task_ids'], true) ?? [];
            if (empty($task_ids)) {
                continue;
            }
            $shanjian                        = ShanjianAnchor::where('dh_id', $item['id'])->find();
            $weiju                           = HumanAnchor::where('model_version', 1)->where('dh_id', $item['id'])->find();
            $chanjing                        = HumanAnchor::where('model_version', 7)->where('dh_id', $item['id'])->find();
            $task_ids['shanjian']['task_id'] = $shanjian['task_id'] ?? '';
            $task_ids['shanjian']['status']  = $shanjian['status'] ?? 0;
            $task_ids['weiju']['task_id']    = $weiju['task_id'] ?? '';
            $task_ids['weiju']['status']     = $weiju['status'] ?? 0;
            $task_ids['chanjing']['task_id'] = $chanjing['task_id'] ?? '';
            $task_ids['chanjing']['status']  = $chanjing['status'] ?? 0;

            $update['task_ids'] = json_encode($task_ids);
            if ($task_ids['shanjian']['status'] == 6 && $task_ids['weiju']['status'] == 1 && $task_ids['chanjing']['status'] == 1) {
                $update['status']   = 2;
                $update['task_ids'] = json_encode($task_ids);
            }
            if ($task_ids['shanjian']['status'] == 5) {
                $update['status']   = 3;
                $update['task_ids'] = json_encode($task_ids);
            }
            DigitalHumanAnchor::where('id', $item['id'])->update($update);
        }
        return true;
    }

    /**
     * 公共数字人失败退费
     */
    public static function digitalHumanAnchorReturnCron()
    {
        $lists = DigitalHumanAnchor::where('status', '=', 3)->select();
        if ($lists->isEmpty()) {
            return true;
        }
        Log::channel('shanjian')->write('退费查询：' . json_encode($lists, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $lists = $lists->toArray();
        foreach ($lists as $item) {
            $order    = false;
            $task_ids = json_decode($item['task_ids'], true) ?? [];
            if (empty($task_ids)) {
                continue;
            }
            $shanjian = ShanjianAnchor::where('dh_id', $item['id'])->find();
            $weiju    = HumanAnchor::where('model_version', 1)->where('dh_id', $item['id'])->find();
            $chanjing = HumanAnchor::where('model_version', 7)->where('dh_id', $item['id'])->find();
            if ($shanjian->isEmpty() || $weiju->isEmpty() || $chanjing->isEmpty()) {
                continue;
            }
            Log::channel('shanjian')->write('退费处理开始：' . $item['id']);

            $task_ids['shanjian']['task_id'] = $shanjian['task_id'] ?? '';
            $task_ids['shanjian']['status']  = $shanjian['status'] ?? 0;
            $task_ids['weiju']['task_id']    = $weiju['task_id'] ?? '';
            $task_ids['weiju']['status']     = $weiju['status'] ?? 0;
            $task_ids['chanjing']['task_id'] = $chanjing['task_id'] ?? '';
            $task_ids['chanjing']['status']  = $chanjing['status'] ?? 0;
            $update['task_ids']              = json_encode($task_ids);

            DigitalHumanAnchor::where('id', $item['id'])->update($update);
            $publicAnchor = DigitalHumanAnchor::where('id', $item['id'])->find();
            $task_ids = json_decode($publicAnchor['task_ids'], true);

            if (($task_ids['shanjian']['status'] == 2 || $task_ids['shanjian']['status'] == 5) && $task_ids['weiju']['status'] == 1 && $task_ids['chanjing']['status'] == 1) {
                // weiju失败退费
                self::refundTokens($weiju->user_id, $weiju->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR);
                $weiju->status = 2;
                $weiju->remark = '公共数字人合成失败';
                $weiju->save();
                // chanjing失败退费
                self::refundTokens($chanjing->user_id, $chanjing->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING);
                $chanjing->status = 2;
                $chanjing->remark = '公共数字人合成失败';
                $chanjing->save();
                $order = true;
            } else
                if ($task_ids['shanjian']['status'] == 6 && $task_ids['weiju']['status'] == 2 && $task_ids['chanjing']['status'] == 1) {
                    // shanjian失败退费
                    self::refundTokens($shanjian->user_id, $shanjian->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN);
                    $shanjian->status = 2;
                    $shanjian->remark = '公共数字人合成失败';
                    $shanjian->save();
                    // chanjing失败退费
                    self::refundTokens($chanjing->user_id, $chanjing->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING);
                    $chanjing->status = 2;
                    $chanjing->remark = '公共数字人合成失败';
                    $chanjing->save();
                    $order = true;
                } else
                    if ($task_ids['shanjian']['status'] == 6 && $task_ids['weiju']['status'] == 1 && $task_ids['chanjing']['status'] == 2) {
                        // shanjian失败退费
                        self::refundTokens($shanjian->user_id, $shanjian->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN);
                        $shanjian->status = 2;
                        $shanjian->remark = '公共数字人合成失败';
                        $shanjian->save();
                        // chanjing失败退费
                        self::refundTokens($weiju->user_id, $weiju->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR);
                        $chanjing->status = 2;
                        $chanjing->remark = '公共数字人合成失败';
                        $chanjing->save();
                        $order = true;
                    }

            if ($order) {
                $task_ids['shanjian']['task_id'] = $shanjian['task_id'] ?? '';
                $task_ids['shanjian']['status']  = $shanjian->status ?? 0;
                $task_ids['weiju']['task_id']    = $weiju['task_id'] ?? '';
                $task_ids['weiju']['status']     = $weiju->status ?? 0;
                $task_ids['chanjing']['task_id'] = $chanjing['task_id'] ?? '';
                $task_ids['chanjing']['status']  = $chanjing->status ?? 0;
                $update['task_ids']              = json_encode($task_ids);
                DigitalHumanAnchor::where('id', $item['id'])->update($update);
            }
            Log::channel('shanjian')->write('退费处理结束：' . $item['id']);

        }
        return true;
    }

    /**
     * @desc 退费
     * @param int $userId
     * @param int $id
     * @param string $taskId
     * @return bool
     */
    public static function refundTokens(int $userId, string $taskId, int $typeID): bool
    {
        $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
        //查询是否已返还
        if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
            $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
        }
        return true;
    }

    /**
     * 计算三张表的总记录数（避免UNION后count效率低）
     * @param int $userId 用户ID
     * @return int 总条数
     */
    private static function calcTotalCount(int $userId, int $status, int $filter): int
    {
        $publicWhere   = [];
        $humanWhere    = [];
        $shanjianWhere = [];
        $where         = [
            ['user_id', '=', $userId],
            ['delete_time', '=', null]
        ];
        if ($status == 1) {
            $publicWhere   = [
                ['status', 'in', [0, 1, 2]]
            ];
            $humanWhere    = [
                ['status', 'in', [0, 1]]
            ];
            $shanjianWhere = [
                ['status', 'in', [1, 3, 4, 5, 6]]
            ];
        }

        // 展示成功
        if ($status == 2) {
            $publicWhere   = [
                ['status', '=', 2]
            ];
            $humanWhere    = [
                ['status', '=', 1]
            ];
            $shanjianWhere = [
                ['status', '=', 6]
            ];
        }

        $countPublic   = Db::name('digital_human_anchor')->where($where)->where($publicWhere)->count();
        $countHuman    = Db::name('human_anchor')->where($where)->where($humanWhere)->where('dh_id', '=', 0)->where('model_version', 'in', [1, 7])->count();
        $countShanjian = Db::name('shanjian_anchor')->where($where)->where($shanjianWhere)->where('dh_id', '=', 0)->count();

        if ($filter == 1) {
            $total = $countPublic + $countShanjian;
        } else if ($filter == 2) {
            $total = $countPublic + $countHuman;
        } else {
            $total = $countPublic + $countHuman + $countShanjian;
        }
        return $total;
    }

    /**
     * 格式化列表数据（时间戳转日期、空值处理）
     * @param array $lists 原始查询数据
     * @return array 格式化后的数据
     */
    private static function formatListData(array $lists): array
    {
        foreach ($lists as &$item) {
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
            $item['update_time'] = $item['update_time'] ? date('Y-m-d H:i:s', $item['update_time']) : '';
            $item['pic']         = !empty($item['pic']) ? FileService::getFileUrl($item['pic']) : '';
            $item['result_url']  = !empty($item['result_url']) ? FileService::getFileUrl($item['result_url']) : '';
            $item['remark']      = $item['remark'] ?? '';
            $item['extra_info']  = !empty($item['extra_info']) ? json_decode($item['extra_info'], true) : '';
            if ($item['model_version'] === 0) {
                $weiju              = HumanAnchor::where('model_version', '=', 1)->where('dh_id', '=', $item['id'])->find();
                $chanjing           = HumanAnchor::where('model_version', '=', 7)->where('dh_id', '=', $item['id'])->find();
                $shanjian           = ShanjianAnchor::where('dh_id', '=', $item['id'])->find();
                $item['anchor_ids'] = [
                    'weiju_anchor_id'    => $weiju->anchor_id ?? '',
                    'chanjing_anchor_id' => $chanjing->anchor_id ?? '',
                    'shanjian_anchor_id' => $shanjian->anchor_id ?? '',
                ];
                $item['extra_info'] = [
                    'width'             => $weiju->width ?? ($chanjing->width ?? ''),
                    'height'            => $weiju->height ?? ($chanjing->width ?? ''),
                    'shanjian_voice_id' => $shanjian->voice_id ?? '',
                ];
            } else {
                $item['anchor_ids'] = [];
            }
        }
        unset($item);

        return $lists;
    }
}