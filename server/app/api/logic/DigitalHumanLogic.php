<?php

namespace app\api\logic;

use app\api\logic\material\FfmpegFileLogic;
use app\api\logic\service\TokenLogService;
use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\file\File;
use app\common\model\human\HumanAnchor;
use app\common\model\material\FfmpegFile;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use app\common\service\UserDisplaySanitizer;
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
        // is_pro：0=全部 1=普通数字人形象（含一克三中的标准形象） 2=专业数字人形象（一克三）
        $isPro = (int)($params['is_pro'] ?? 0);
        if ($isPro === 2) {
            return self::getDigitalHumanAnchorProList($params);
        }
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
                                     'width',
                                     'height',
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
                                    'width',
                                    'height',
                                    Db::raw("'human_anchor' as source_type"),
                                    'dh_id',
                                    'model_version',
                                    // 存储渠道表特有字段（按需扩展）
                                    Db::raw("JSON_OBJECT('type',type, 'width',width, 'height',height) as extra_info")
                                ])
                        ->where($commonWhere)
                        ->where($humanWhere)
                        //隐藏微聚
//                        ->where('model_version', 'in', [1, 7])
                        ->where('model_version', '=', 7)
                        ->where('dh_id', '=', 0)
                        ->where('create_time', '<', 1767249134) //只兼容2026年1月1日前的旧数据
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
                                       Db::raw("0 as width"),
                                       Db::raw("0 as height"),
                                       Db::raw("'shanjian_anchor' as source_type"), // 标记数据来源：闪剪表
                                       'dh_id',
                                       Db::raw("8 as model_version"), // 公共表无模型版本，默认0
                                       // 存储闪剪表特有字段（按需扩展）
                                       Db::raw("JSON_OBJECT('voice_id', voice_id, 'voice_model', voice_model) as extra_info")
                                   ])
                           ->where($commonWhere)
                           ->where($shanjianWhere)
                           ->where('dh_id', '=', 0)
                           ->where('create_time', '<', 1767249134) //只兼容2026年1月1日前的旧数据
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
        $total = self::calcTotalCount($userId, $status, $filter, $isPro);

        // 6. 格式化数据：is_pro=1 取一克三中的标准（极速）形象
        $lists = self::formatListData(
            $lists,
            $isPro === 1 ? ShanjianAnchorLogic::CLONE_TYPE_FAST : 0
        );

        // 7. 返回统一格式结果
        return [
            'count'      => $total,
            'lists'      => $lists,
            'page_no'    => $pageNo,
            'page_size'  => $pageSize,
            'total_page' => (int)ceil($total / $pageSize)
        ];
    }

    /**
     * 获取专业数字人形象列表（一克三公共形象，仅闪剪专业克隆）
     * @param array $params
     * @return array 包含总条数、分页数据的结果集
     * @throws DbException
     */
    public static function getDigitalHumanAnchorProList(array $params): array
    {
        $userId   = self::$uid;
        $pageNo   = isset($params['page_no']) && $params['page_no'] > 0 ? (int)$params['page_no'] : 1;
        $pageSize = isset($params['page_size']) && $params['page_size'] > 0 ? (int)$params['page_size'] : 10;
        $offset   = ($pageNo - 1) * $pageSize;
        $status   = $params['status'] ?? 0; //1 展示成功+生成中 2 只展示成功

        // 专业克隆仅一克三公共形象才有，无旧渠道数据合并
        $publicWhere = [
            ['user_id', '=', $userId],
            ['delete_time', '=', null],
            ['clone_mode', '=', 3],
        ];

        // 展示生成中+成功
        if ($status == 1) {
            $publicWhere[] = ['status', 'in', [0, 1, 2]];
        }

        // 展示成功
        if ($status == 2) {
            $publicWhere[] = ['status', '=', 2];
        }

        // 字段投影与 anchorLists 公共表保持一致，另补 clone_mode 供格式化取专业形象
        $field = [
            'id',
            'user_id',
            Db::raw("'' as anchor_id"),
            'name',
            'image as pic',
            'status',
            'remark',
            'result_url',
            'create_time',
            'update_time',
            'width',
            'height',
            'clone_mode',
            Db::raw("'public_anchor' as source_type"),
            Db::raw("0 as dh_id"),
            Db::raw("0 as model_version"),
            Db::raw("'' as extra_info")
        ];

        $lists = Db::name('digital_human_anchor')
                   ->field($field)
                   ->where($publicWhere)
                   ->order('create_time', 'desc')
                   ->limit($offset, $pageSize)
                   ->select()
                   ->toArray();

        $total = Db::name('digital_human_anchor')->where($publicWhere)->count();

        // 强制取专业克隆形象 anchor_id；音色仍取极速记录
        $lists = self::formatListData($lists, ShanjianAnchorLogic::CLONE_TYPE_PRO);

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
            $ai_type = $params['ai_type'] ?? 0;
            if (empty($params['name']) || empty($params['anchor_url'])|| empty($params['pic']) ) {
                throw new \Exception('缺少形象视频或者图片');
            }
            if ($ai_type == 0 && (empty($params['authorized_url']) || empty($params['authorized_pic']))) {
                throw new \Exception('缺少授权形象视频或者授权图片');
            }
            Log::channel('digital')->write('公共形象创建参数'.json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $unit = 0;
            if ($ai_type == 1){
                $unit = TokenLogService::checkToken(self::$uid, 'ai_shanjian_authorized_video');
            }
            //无需名称验证
//            $res = DigitalHumanAnchor::where('name', $params['name'])->findOrEmpty();
//            if (!$res->isEmpty()) {
//                throw new \Exception('名称已存在');
//            }
            $task_id = generate_unique_task_id();
            $cloneMode = (int)($params['clone_mode'] ?? 2);
            if (!in_array($cloneMode, [2, 3], true)) {
                $cloneMode = 2;
            }
            $dhInsert         = [
                'user_id'        => self::$uid,
                'name'           => $params['name'],
                'image'          => $params['pic'] ?? '',
                'task_ids'       => json_encode([
                                                    'shanjian'     => ['task_id' => '', 'status' => 0],
                                                    'shanjian_pro' => ['task_id' => '', 'status' => 0],
                                                    'weiju'        => ['task_id' => '', 'status' => 0],
                                                    'chanjing'     => ['task_id' => '', 'status' => 0]
                                                ]),
                'status'         => 0,
                'task_id'        => '',
                'ai_type'        => $ai_type,
                'clone_mode'     => $cloneMode,
                'result_url'     => FileService::setFileUrl($params['anchor_url']),
                'width'          => $params['width'] ?? 0,
                'height'         => $params['height'] ?? 0,
            ];

            if ($ai_type == 0) {
                $dhInsert['authorized_url'] = FileService::setFileUrl($params['authorized_url']);
                $dhInsert['authorized_pic'] = FileService::setFileUrl($params['authorized_pic']);
                $authData         = [
                    'user_id' => self::$uid,
                    'file_id' => File::where('uri', $dhInsert['authorized_url'])->value('id'),
                    'type'    => 20,
                    'uri'     => $params['authorized_url']
                ];
                FfmpegFileLogic::addFfmpegFile($authData);
            }else{
                $response = \app\common\service\ToolsService::Shanjian()->aiAuthoried($dhInsert);
                if (isset($response['code']) && $response['code'] == 10000) {
                    $points = $unit;
                    if ($points > 0) {
                        $extra = [];
                        //token扣除
                        User::userTokensChange(self::$uid, $points);
                        //记录日志
                        AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_AI_SHANJIAN_AUTHORIZED_VIDEO, $points,  $task_id, $extra);
                    }
                }
            }
            $dh               = DigitalHumanAnchor::create($dhInsert);
            Log::channel('digital')->write('公共形象创建id'.$dh->id);
            $anchorData       = [
                'user_id' => self::$uid,
                'file_id' => File::where('uri', $dhInsert['result_url'])->value('id'),
                'type'    => 20,
                'uri'     => $params['anchor_url']
            ];
            Log::channel('ffmpeg')->write('ffmpeg转码1'.json_encode($anchorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            FfmpegFileLogic::addFfmpegFile($anchorData);
            self::$returnData = $dh->refresh()->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function deletePublicAnchor(array $data)
    {
        Db::startTrans();
        try {
            $ids = self::normalizeDeleteIds($data);
            if (empty($ids)) {
                throw new \Exception('请选择要删除的形象');
            }
            $now = time();

            $thirdVoiceIds = ShanjianAnchor::whereIn('dh_id', $ids)
                ->whereNull('delete_time')
                ->where('voice_id', '<>', '')
                ->column('voice_id');
            $deleted = DigitalHumanAnchor::whereIn('id', $ids)->whereNull('delete_time')->update([
                'delete_time' => $now,
                'update_time' => $now,
            ]);
            $deleted += HumanAnchor::whereIn('dh_id', $ids)->whereNull('delete_time')->update([
                'delete_time' => $now,
                'update_time' => $now,
            ]);
            $deleted += ShanjianAnchor::whereIn('dh_id', $ids)->whereNull('delete_time')->update([
                'delete_time' => $now,
                'update_time' => $now,
            ]);
            $deleted += AiPersonaDigitalAvatar::whereIn('dh_id', $ids)->whereNull('delete_time')->update([
                'delete_time' => $now,
                'update_time' => $now,
            ]);
            if (!empty($thirdVoiceIds)) {
                $deleted += AiPersonaDigitalVoice::whereIn('third_voice_id', $thirdVoiceIds)
                    ->where('voice_id', 0)
                    ->whereNull('delete_time')
                    ->update([
                        'delete_time' => $now,
                        'update_time' => $now,
                    ]);
            }
            if (!$deleted) {
                throw new \Exception('形象不存在或已删除');
            }
            Db::commit();
            return true;
        } catch (\Exception $exception) {
            Db::rollback();
            self::setError($exception->getMessage());
            return false;
        }
    }

    private static function normalizeDeleteIds(array $data): array
    {
        $value = $data['id'] ?? ($data['ids'] ?? ($data['dh_id'] ?? []));
        if (is_string($value)) {
            $value = explode(',', $value);
        } elseif (!is_array($value)) {
            $value = [$value];
        }
        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    public static function createDigitalHumanAnchorCron()
    {
        try {
            $lists = DigitalHumanAnchor::where('status', 0)->where('ai_type',0)->select();
            if ($lists->isEmpty()) {
                return true;
            }
            $lists = $lists->toArray();
            Log::channel('digital')->write('定时任务开启：' . json_encode($lists, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
                Log::channel('digital')->write('定时任务执行：' . $item['id']);
                $shanjianData = [
                    'user_id'        => $item['user_id'],
                    'dh_id'          => $item['id'],
                    'name'           => $item['name'],
                    'anchor_url'     => FileService::getFileUrl($item['result_url']),
                    'pic'            => FileService::getFileUrl($item['image']),
                    'authorized_url' => FileService::getFileUrl($item['authorized_url']),
                    'authorized_pic' => empty($item['authorized_pic']) ? '' : FileService::getFileUrl($item['authorized_pic']),
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
                $bool = HumanLogic::createAnchor($chanjingData);
                if ($bool){
                    ShanjianAnchorLogic::add($shanjianData);
                    if ((int)($item['clone_mode'] ?? 2) === 3) {
                        $shanjianData['resolution'] = self::resolveShanjianResolution($item['width'] ?? 0, $item['height'] ?? 0);
                        ShanjianAnchorLogic::addPro($shanjianData);
                    }
                }
            }
            return true;
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

    /**
     * 按公共形象宽高短边判定闪剪专业克隆 resolution
     */
    public static function resolveShanjianResolution($width, $height): int
    {
        $w = (int)$width;
        $h = (int)$height;
        if ($w <= 0) {
            $w = 1080;
        }
        if ($h <= 0) {
            $h = 1080;
        }
        return min($w, $h) >= 2160 ? 2160 : 1080;
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
            $cloneMode = (int)($item['clone_mode'] ?? 2);
            $shanjian  = ShanjianAnchor::where('dh_id', $item['id'])
                ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_FAST)
                ->find();
            $shanjianPro = null;
            if ($cloneMode === 3) {
                $shanjianPro = ShanjianAnchor::where('dh_id', $item['id'])
                    ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_PRO)
                    ->find();
            }
            //隐藏微聚
//            $weiju                           = HumanAnchor::where('model_version', 1)->where('dh_id', $item['id'])->find();
            $chanjing                        = HumanAnchor::where('model_version', 7)->where('dh_id', $item['id'])->find();
            $task_ids['shanjian']['task_id'] = $shanjian['task_id'] ?? '';
            $task_ids['shanjian']['status']  = $shanjian['status'] ?? 0;
            $task_ids['weiju']['task_id']    = $weiju['task_id'] ?? '';
            $task_ids['weiju']['status']     = $weiju['status'] ?? 0;
            $task_ids['chanjing']['task_id'] = $chanjing['task_id'] ?? '';
            $task_ids['chanjing']['status']  = $chanjing['status'] ?? 0;
            if ($cloneMode === 3) {
                $task_ids['shanjian_pro'] = $task_ids['shanjian_pro'] ?? ['task_id' => '', 'status' => 0];
                $task_ids['shanjian_pro']['task_id'] = $shanjianPro['task_id'] ?? '';
                $task_ids['shanjian_pro']['status']  = $shanjianPro['status'] ?? 0;
            }

            $update['task_ids'] = json_encode($task_ids);
            //隐藏微聚
//            if ($task_ids['shanjian']['status'] == 6 && $task_ids['weiju']['status'] == 1 && $task_ids['chanjing']['status'] == 1) {
            $fastOk = ($task_ids['shanjian']['status'] == 6);
            $chanjingOk = ($task_ids['chanjing']['status'] == 1);
            $proOk = $cloneMode !== 3 || (($task_ids['shanjian_pro']['status'] ?? 0) == 3);
            if ($fastOk && $chanjingOk && $proOk) {
                $update['status']   = 2;
                $update['task_ids'] = json_encode($task_ids);
            }
            if ($task_ids['shanjian']['status'] == 5 || ($cloneMode === 3 && ($task_ids['shanjian_pro']['status'] ?? 0) == 2)) {
                $update['status']   = 3;
                $update['task_ids'] = json_encode($task_ids);
            }
            DigitalHumanAnchor::where('id', $item['id'])->update($update);
        }
        return true;
    }

    //1小时以上的失败任务处理
    public static function getDigitalHumanAnchorFailedStatusCron()
    {
        $lists = DigitalHumanAnchor::where('status', 'in', [0,1])->where('create_time', '<', time() - 3600)->select();
        if ($lists->isEmpty()) {
            return true;
        }
        $lists = $lists->toArray();
        foreach ($lists as $item) {
            $task_ids = json_decode($item['task_ids'], true) ?? [];
            if (empty($task_ids)) {
                continue;
            }
            $cloneMode = (int)($item['clone_mode'] ?? 2);
            $shanjian = ShanjianAnchor::where('dh_id', $item['id'])
                ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_FAST)
                ->findOrEmpty();
            $shanjianPro = ShanjianAnchor::where('dh_id', $item['id'])
                ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_PRO)
                ->findOrEmpty();
            //隐藏微聚
//            $weiju    = HumanAnchor::where('model_version', 1)->where('dh_id', $item['id'])->findOrEmpty();
            $chanjing = HumanAnchor::where('model_version', 7)->where('dh_id', $item['id'])->findOrEmpty();
            // ai_type=1 只走蝉镜；ai_type=0 需蝉镜+闪剪都有子任务；一克三还需专业子任务
            $aiType = (int)($item['ai_type'] ?? 0);
            $failed = $aiType === 1
                ? $chanjing->isEmpty()
                : ($chanjing->isEmpty() || $shanjian->isEmpty() || ($cloneMode === 3 && $shanjianPro->isEmpty()));
            if ($failed) {
                $update['status'] = 3;
                $update['remark'] = '形象生成失败';
                DigitalHumanAnchor::where('id', $item['id'])->update($update);
            }
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
        Log::channel('digital')->write('退费查询：' . json_encode($lists, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $lists = $lists->toArray();
        foreach ($lists as $item) {
            $order    = false;
            $task_ids = json_decode($item['task_ids'], true) ?? [];
            if (empty($task_ids)) {
                continue;
            }
            $cloneMode = (int)($item['clone_mode'] ?? 2);
            $shanjian = ShanjianAnchor::where('dh_id', $item['id'])
                ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_FAST)
                ->findOrEmpty();
            $shanjianPro = ShanjianAnchor::where('dh_id', $item['id'])
                ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_PRO)
                ->findOrEmpty();
//            $weiju    = HumanAnchor::where('model_version', 1)->where('dh_id', $item['id'])->findOrEmpty();
            $chanjing = HumanAnchor::where('model_version', 7)->where('dh_id', $item['id'])->findOrEmpty();
//            if ($shanjian->isEmpty() || $weiju->isEmpty() || $chanjing->isEmpty()) {
            if ($shanjian->isEmpty() || $chanjing->isEmpty()) {
                continue;
            }
            if ($cloneMode === 3 && $shanjianPro->isEmpty()) {
                continue;
            }
            Log::channel('digital')->write('退费处理开始：' . $item['id']);

            $task_ids['shanjian']['task_id'] = $shanjian['task_id'] ?? '';
            $task_ids['shanjian']['status']  = $shanjian['status'] ?? 0;
            $task_ids['weiju']['task_id']    = $weiju['task_id'] ?? '';
            $task_ids['weiju']['status']     = $weiju['status'] ?? 0;
            $task_ids['chanjing']['task_id'] = $chanjing['task_id'] ?? '';
            $task_ids['chanjing']['status']  = $chanjing['status'] ?? 0;
            if ($cloneMode === 3) {
                $task_ids['shanjian_pro'] = $task_ids['shanjian_pro'] ?? ['task_id' => '', 'status' => 0];
                $task_ids['shanjian_pro']['task_id'] = $shanjianPro['task_id'] ?? '';
                $task_ids['shanjian_pro']['status']  = $shanjianPro['status'] ?? 0;
            }
            $update['task_ids']              = json_encode($task_ids);

            DigitalHumanAnchor::where('id', $item['id'])->update($update);
            $publicAnchor = DigitalHumanAnchor::where('id', $item['id'])->find();
            $task_ids = json_decode($publicAnchor['task_ids'], true);

//            if (($task_ids['shanjian']['status'] == 2 || $task_ids['shanjian']['status'] == 5) && $task_ids['weiju']['status'] == 1 && $task_ids['chanjing']['status'] == 1) {
            if (($task_ids['shanjian']['status'] == 2 || $task_ids['shanjian']['status'] == 5) && $task_ids['chanjing']['status'] == 1) {
                // weiju失败退费
//                self::refundTokens($weiju->user_id, $weiju->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR);
//                $weiju->status = 2;
//                $weiju->remark = '公共数字人合成失败';
//                $weiju->save();
                // shanjian失败退费
                self::refundTokens($shanjian->user_id, $shanjian->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN);
                $shanjian->status = 2;
                if ($item['ai_type'] == 0){
                    $chanjing->remark = '公共数字人合成失败';
                    $shanjian->remark = '公共数字人合成失败';
                }
                $shanjian->save();
                // chanjing失败退费
                self::refundTokens($chanjing->user_id, $chanjing->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING);
                $chanjing->status = 2;
                $chanjing->save();
                if ($cloneMode === 3 && !$shanjianPro->isEmpty()) {
                    self::refundTokens($shanjianPro->user_id, $shanjianPro->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN_PRO);
                    $shanjianPro->status = 2;
                    if ($item['ai_type'] == 0) {
                        $shanjianPro->remark = '公共数字人合成失败';
                    }
                    $shanjianPro->save();
                }
                $order = true;
            } else
//                if ($task_ids['shanjian']['status'] == 6 && $task_ids['weiju']['status'] == 2 && $task_ids['chanjing']['status'] == 1) {
                if ($task_ids['shanjian']['status'] == 6 && $task_ids['chanjing']['status'] == 1) {
                    // shanjian失败退费
                    self::refundTokens($shanjian->user_id, $shanjian->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN);
                    $shanjian->status = 2;
                    if ($item['ai_type'] == 0){
                        $chanjing->remark = '公共数字人合成失败';
                        $shanjian->remark = '公共数字人合成失败';
                    }

                    $shanjian->save();
                    // chanjing失败退费
                    self::refundTokens($chanjing->user_id, $chanjing->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING);
                    $chanjing->status = 2;
                    $chanjing->save();
                    if ($cloneMode === 3 && !$shanjianPro->isEmpty()) {
                        self::refundTokens($shanjianPro->user_id, $shanjianPro->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN_PRO);
                        $shanjianPro->status = 2;
                        if ($item['ai_type'] == 0) {
                            $shanjianPro->remark = '公共数字人合成失败';
                        }
                        $shanjianPro->save();
                    }
                    $order = true;
                } else if ($cloneMode === 3 && (($task_ids['shanjian_pro']['status'] ?? 0) == 2) && $task_ids['chanjing']['status'] == 1) {
                    // 专业克隆失败时退专业费，并连带退极速/禅镜（对齐现有公共失败退费策略）
                    self::refundTokens($shanjianPro->user_id, $shanjianPro->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN_PRO);
                    $shanjianPro->status = 2;
                    if ($item['ai_type'] == 0) {
                        $chanjing->remark = '公共数字人合成失败';
                        $shanjian->remark = '公共数字人合成失败';
                        $shanjianPro->remark = '公共数字人合成失败';
                    }
                    $shanjianPro->save();
                    self::refundTokens($shanjian->user_id, $shanjian->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN);
                    $shanjian->status = 2;
                    $shanjian->save();
                    self::refundTokens($chanjing->user_id, $chanjing->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING);
                    $chanjing->status = 2;
                    $chanjing->save();
                    $order = true;
                }
//                else if ($task_ids['shanjian']['status'] == 6 && $task_ids['weiju']['status'] == 1 && $task_ids['chanjing']['status'] == 2) {
//                        // shanjian失败退费
//                        self::refundTokens($shanjian->user_id, $shanjian->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN);
//                        $shanjian->status = 2;
//                        $shanjian->remark = '公共数字人合成失败';
//                        $shanjian->save();
//                        // chanjing失败退费
//                        self::refundTokens($weiju->user_id, $weiju->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR);
//                        $chanjing->status = 2;
//                        $chanjing->remark = '公共数字人合成失败';
//                        $chanjing->save();
//                        $order = true;
//                    }

            if ($order) {
                $task_ids['shanjian']['task_id'] = $shanjian['task_id'] ?? '';
                $task_ids['shanjian']['status']  = $shanjian->status ?? 0;
                $task_ids['weiju']['task_id']    = $weiju['task_id'] ?? '';
                $task_ids['weiju']['status']     = $weiju->status ?? 0;
                $task_ids['chanjing']['task_id'] = $chanjing['task_id'] ?? '';
                $task_ids['chanjing']['status']  = $chanjing->status ?? 0;
                if ($cloneMode === 3 && !$shanjianPro->isEmpty()) {
                    $task_ids['shanjian_pro'] = $task_ids['shanjian_pro'] ?? ['task_id' => '', 'status' => 0];
                    $task_ids['shanjian_pro']['task_id'] = $shanjianPro->task_id ?? '';
                    $task_ids['shanjian_pro']['status']  = $shanjianPro->status ?? 0;
                }
                $update['task_ids']              = json_encode($task_ids);
                DigitalHumanAnchor::where('id', $item['id'])->update($update);
            }
            Log::channel('digital')->write('退费处理结束：' . $item['id']);

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
    private static function calcTotalCount(int $userId, int $status, int $filter, int $isPro = 0): int
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
        //隐藏微聚
//        $countHuman    = Db::name('human_anchor')->where($where)->where($humanWhere)->where('dh_id', '=', 0)->where('create_time', '<', 1767249134)->where('model_version', 'in', [1, 7])->count();
        $countHuman    = Db::name('human_anchor')->where($where)->where($humanWhere)->where('dh_id', '=', 0)->where('create_time', '<', 1767249134)->where('model_version', '=', 7)->count();
        $countShanjian = Db::name('shanjian_anchor')->where($where)->where($shanjianWhere)->where('dh_id', '=', 0)->where('create_time', '<', 1767249134)->count();

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
    private static function formatListData(array $lists, int $cloneType = 0): array
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
                $cloneMode          = (int)($item['clone_mode'] ?? 2);
                $shanjianFast       = ShanjianAnchor::where('dh_id', '=', $item['id'])
                    ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_FAST)
                    ->find();
                $shanjianPro        = $cloneMode === 3
                    ? ShanjianAnchor::where('dh_id', '=', $item['id'])
                        ->where('clone_type', ShanjianAnchorLogic::CLONE_TYPE_PRO)
                        ->find()
                    : null;
                // 指定 cloneType 时强制取对应渠道形象（1极速/2专业）；默认一克三优先专业 anchor_id
                // 音色始终取极速记录（专业不串联音色）
                $shanjianDisplay = match (true) {
                    $cloneType === ShanjianAnchorLogic::CLONE_TYPE_PRO  => $shanjianPro,
                    $cloneType === ShanjianAnchorLogic::CLONE_TYPE_FAST => $shanjianFast,
                    default => ($cloneMode === 3 && !empty($shanjianPro) && !empty($shanjianPro->anchor_id))
                        ? $shanjianPro
                        : $shanjianFast,
                };
                $item['anchor_ids'] = [
                    'weiju_anchor_id'    => $weiju->anchor_id ?? '',
                    'chanjing_anchor_id' => $chanjing->anchor_id ?? '',
                    'shanjian_anchor_id' => !empty($shanjianDisplay) ? ($shanjianDisplay->anchor_id ?? '') : '',
                ];
                $item['extra_info'] = [
                    'width'             => $weiju->width ?? ($chanjing->width ?? ''),
                    'height'            => $weiju->height ?? ($chanjing->height ?? ''),
                    'shanjian_voice_id' => !empty($shanjianFast) ? ($shanjianFast->voice_id ?? '') : '',
                ];
                // 普通列表：一克三只要标准渠道（极速+蝉镜）就绪，就按已完成展示
                if ($cloneType === ShanjianAnchorLogic::CLONE_TYPE_FAST) {
                    $fastOk     = !empty($shanjianFast) && (int)($shanjianFast->status ?? 0) === 6;
                    $chanjingOk = !empty($chanjing) && (int)($chanjing->status ?? 0) === 1;
                    if ($fastOk && $chanjingOk) {
                        $item['status'] = 2;
                    }
                }
            } else {
                $item['anchor_ids'] = [];
            }
            $item = UserDisplaySanitizer::digitalHumanAnchorItem($item);
        }
        unset($item);

        return $lists;
    }

    public static function createDigitalHumanAnchorAiCron()
    {
        try {
            $lists = DigitalHumanAnchor::where('status', 0)->where('ai_type',1)->limit(6)->select();
            if ($lists->isEmpty()) {
                return true;
            }
            $lists = $lists->toArray();
            Log::channel('digital')->write('ai授权视频生成定时任务开启：' . json_encode($lists, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            foreach ($lists as $item) {
                $anchorFile = FfmpegFile::where('uri', '=', FileService::setFileUrl($item['result_url']))->findOrEmpty();
                if ($anchorFile->isEmpty()) {
                    continue;
                }

                if ( in_array($anchorFile->status, [0, 1, "0", "1"])) {
                    continue;
                }
                if ($anchorFile->status == 3){
                    $anchorFile->width = $item['width'];
                    $anchorFile->height = $item['height'];
                }

                Log::channel('digital')->write('ai授权视频定时任务执行：' . $item['id']);

                $chanjingData = [
                    'user_id'       => $item['user_id'],
                    'dh_id'         => $item['id'],
                    'name'          => $item['name'],
                    'url'           => FileService::getFileUrl($item['result_url']),
                    'pic'           => FileService::getFileUrl($item['image']),
                    'width'         => $anchorFile->width,
                    'height'        => $anchorFile->height,
                    'model_version' => 7
                ];

                // 仅成功提交蝉镜后置 status=4；失败时 createAnchor 已写 status=3，不可再覆盖成生成中
                $ok = HumanLogic::createAnchor($chanjingData);
                $update = [
                    'width'  => $anchorFile->width,
                    'height' => $anchorFile->height,
                ];
                if ($ok) {
                    $update['status'] = 4;
                } else {
                    Log::channel('digital')->write(
                        'ai授权视频蝉镜形象创建失败: id=' . $item['id'] . ' err=' . (HumanLogic::getError() ?: '')
                    );
                    $current = DigitalHumanAnchor::where('id', $item['id'])->find();
                    if ($current && (int)$current->status === 0) {
                        $update['status'] = 3;
                        $update['remark'] = HumanLogic::getError() ?: '形象合成失败';
                    }
                }
                DigitalHumanAnchor::where('id', $item['id'])->update($update);
            }
            return true;
        } catch (\Exception $exception) {
            Log::channel('digital')->write('ai授权视频定时失败：' . $exception->getMessage());
            echo $exception->getMessage();
            return false;
        }
    }

    public static function supplement()
    {
        try {
            $lists = DigitalHumanAnchor::where('status', 2)->where('width', '=', '')->select();
            if ($lists->isEmpty()){
                return true;
            }
            $lists = $lists->toArray();

            foreach ($lists as $item){
                $item['task_ids'] = json_decode($item['task_ids'], true);
                $weiju_task_id = $item['task_ids']['weiju']['task_id'];
                $chanjing_task_id = $item['task_ids']['chanjing']['task_id'];
                if (!empty($weiju_task_id)){
                    $weiju = HumanAnchor::where('task_id', $weiju_task_id)->findOrEmpty();
                    if (!$weiju->isEmpty()){
                        $weiju_width = $weiju->width;
                        $weiju_height = $weiju->height;
                    }
                }
                if (!empty($chanjing_task_id)){
                    $chanjing = HumanAnchor::where('task_id', $chanjing_task_id)->findOrEmpty();
                    if (!$chanjing->isEmpty()){
                        $chanjing_width = $chanjing->width;
                        $chanjing_height = $chanjing->height;
                    }
                }
                $update = [
                    'width' => $chanjing_width ?? $weiju_width ?? '',
                    'height' => $chanjing_height ?? $weiju_height ?? '',
                ];
                DigitalHumanAnchor::where('id', $item['id'])->update($update);
            }
            return true;
        } catch (\Exception $exception) {
            Log::channel('digital')->write('补充视频宽高失败：' . $exception->getMessage());
            echo $exception->getMessage();
            return false;
        }
    }
}
