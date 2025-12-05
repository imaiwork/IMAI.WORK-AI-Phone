<?php


namespace app\adminapi\logic\kb;

use app\adminapi\logic\knowledge\KnowledgeLogic;
use app\common\enum\kb\RobotEnum;
use app\common\logic\BaseLogic;
use app\common\model\chat\ChatLog;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\coze\AgentCate;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotInstruct;
use app\common\model\kb\KbRobotRecord;
use app\common\model\knowledge\Knowledge;
use app\common\model\knowledge\KnowledgeBind;
use app\common\model\sv\SvReplyStrategy;
use app\common\service\ConfigService;
use app\common\service\FileService;
use Exception;
use function Symfony\Component\Translation\t;

/**
 * 机器人逻辑类
 */
class KbRobotLogic extends BaseLogic
{
    /**
     * @notes 机器人详情
     * @param int $id
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public static function detail(int $id): array
    {
        $modelKbRobot = new KbRobot();
        $detail = $modelKbRobot
            ->field('id,user_id,cate_id,kb_type,kb_ids,icons,image,bg_image,name,intro,roles_prompt,model,model_id,model_sub_id,search_mode,search_tokens,search_similar,ranking_status,ranking_score,context_num,is_public,is_enable,optimize_ask,optimize_model,top_p,presence_penalty,frequency_penalty,logprobs,top_logprobs,search_empty_type,search_empty_text,welcome_introducer,copyright,threshold,mode_type')
            ->field('threshold')
            ->where(['id'=>$id])
            ->findOrEmpty()
            ->toArray();

        if ($detail) {
            $detail['icons']  = FileService::getFileUrl($detail['icons']) ?? '';

            // 知识库
            $detail['knows'] = [];
            if ($detail['kb_ids']) {
                $kbIds = explode(',', $detail['kb_ids']);
                $modelKbKnow = new KbKnow();
                $detail['knows'] = $modelKbKnow->field(['id,name'])->whereIn('id', $kbIds)->select()->toArray();
            }
            $detail['kb_ids'] = !empty($detail['kb_ids']) ? explode(',', $detail['kb_ids']) : [];
            // 模型
            $mainModel           = (new Models())->where(['id' => $detail['model_id']])->value('name');
            $subModel            = (new ModelsCost())->where(['id' => $detail['model_sub_id']])->value('name');
//            $detail['models'] = $mainModel . '('.$subModel.')';
            $detail['model']        = $subModel ?: 'deepseek';
            $detail['model_id']     = $detail['model_id'] ?: 4;
            $detail['model_sub_id'] = $detail['model_sub_id'] ?: 4;
            $detail['cate_id']   = $detail['cate_id'] ?: '';
            $detail['cate_name'] = $detail['cate_id'] ? AgentCate::where('id', $detail['cate_id'])->value('name') : '';

            // 快捷菜单
            $modelKbRobotInstruct = new KbRobotInstruct();
            $detail['menus']      = $modelKbRobotInstruct
                ->field(['keyword,content,images'])
                ->where(['robot_id' => $id])
                ->order('id asc')
                ->select()
                ->each(function ($item) {
                    $item['images'] = empty($item['images']) ? [] : explode(',', $item['images']);
                })
                ->toArray();

            // 问题模型
            $optimizeModel           = explode(':', $detail['optimize_model']);
            $detail['optimize_m_id'] = (intval($optimizeModel[0] ?? 0)) ?: '';
            $detail['optimize_s_id'] = (intval($optimizeModel[1] ?? 0)) ?: '';
            unset($detail['optimize_model']);

            // 关联知识库
            if ($detail['kb_ids']) {
                $kbIds = [];
                if ($detail['kb_type'] == 1) {
                    $kbIds = Knowledge::whereIn('id', $detail['kb_ids'])->column('id');
                } else {
                    $kbIds = KbKnow::whereIn('id', $detail['kb_ids'])->column('id');
                }
                $okExistIds = [];
                $noExistIds = [];
                foreach ($detail['kb_ids'] as $kid) {
                    if (!in_array($kid, $kbIds)) {
                        $noExistIds[] = $kid;
                    } else {
                        $okExistIds[] = $kid;
                    }
                }
                $detail['kb_ids'] = $okExistIds;
                if ($noExistIds) {
                    KbRobot::update([
                                        'kb_ids' => implode(',', $okExistIds)
                                    ], ['id' => $id]);
                }
            }

            // 工作流配置
            if (empty($detail['flow_config'])) {
                $detail['flow_config'] = self::flowConfigDefault();
            }
        }

        return $detail;
    }

    /**
     * @ntoes 机器人新增
     * @param array $post
     * @param int $userId
     * @return bool|array
     * @author kb
     */
    public static function add(array $post, int $userId = 0): bool|array
    {
        $model = new KbRobot();
        $model->startTrans();
        try {
            // 默认图标
            $iconImage = '';
            $chatImage = FileService::getFileUrl(ConfigService::get('website', 'shop_logo'));

            // 创建机器人
            $botCode = generate_sn(KbRobot::class, 'code', 0);
            $robot   = KbRobot::create([
                                           'user_id'      => 0,
                                           'code'         => $botCode,
                                           'kb_ids'       => '',
                                           'icons'        => $iconImage,
                                           'image'        => $chatImage,
                                           'name'         => $post['name'] ?? '默认助理',
                                           'intro'        => $post['intro'] ?? '默认助理简介',
                                           'sort'         => 0,
                                           'roles_prompt' => '',
                                           'is_public'    => 0,
                                           'context_num'  => $post['context_num'] ?? 0, // 上下文数量
                                           'create_time'  => time(),
                                           'update_time'  => time()
                                       ]);

            // 创建智能体默认回复策略
            SvReplyStrategy::create([
                                        "user_id"            => 0,
                                        "multiple_type"      => 0,
                                        "robot_id"           => $robot['id'],
                                        "number_chat_rounds" => 3,
                                        "voice_enable"       => 0,
                                        "image_enable"       => 0,
                                        "image_reply"        => "",
                                        "stop_enable"        => 0,
                                        "stop_keywords"      => "",
                                        "bottom_enable"      => 0,
                                        "bottom_reply"       => "",
                                        "paragraph_enable"   => 0,
                                        "non_working_reply"  => "",
                                        "working_enable"     => 0
                                    ]);

            $model->commit();
            return ['id' => $robot['id']] ?? [];
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 机器人编辑
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function edit(array $post, int $userId = 0): bool
    {
        $model = new KbRobot();
        $model->startTrans();
        try {
            // 机器人检测
            $robot = $model->field(['id,is_enable'])
                           ->where(['id' => intval($post['id'])])
                           ->findOrEmpty();
            if (is_string($post['kb_ids'])) {
                $post['kb_ids'] = explode(',', $post['kb_ids']);
            }
            if (count($post['kb_ids']) == 0) {
                KnowledgeBind::where('data_id', $robot->id)->where('user_id', 0)->where('type', 1)->select()->delete();
            }

            // 向量知识库检测
            if (($post['kb_ids'] ?? []) && $post['kb_type'] == 2) {
                $modelKnow = new KbKnow();
                $knows     = $modelKnow
                    ->field(['id,name,embedding_model_id,is_enable'])
                    ->whereIn('id', $post['kb_ids'])
                    ->select()
                    ->toArray();

                if (count($post['kb_ids']) !== count($knows)) {
                    throw new Exception('检测到向量知识库存在变动,请刷新后再试!');
                }

                $vectorModels = $knows[0]['embedding_model_id'] ?? 0;
                foreach ($knows as $item) {
                    if (!$item['is_enable']) {
                        throw new Exception($item['name'] . ': 知识库已被禁用!');
                    }
                    if ($item['embedding_model_id'] !== $vectorModels) {
                        throw new Exception('请选择相同向量模型的知识库!');
                    }
                    //挂载向量知识库
                    $item['type'] = 1;//小红书
                    KnowledgeLogic::newBind($item, $robot, $post['kb_type']);
                }
            }

            // RAG知识库检测
            if (($post['kb_ids'] ?? []) && $post['kb_type'] == 1) {
                $modelKnowledge = new Knowledge();
                $Knowledges     = $modelKnowledge
                    ->field(['id,name,status, index_id'])
                    ->whereIn('id', $post['kb_ids'])
                    ->select()
                    ->toArray();

                if (count($post['kb_ids']) > 1) {
                    throw new Exception('RAG知识库只可挂载一个!');
                }

                if (count($post['kb_ids']) !== count($Knowledges)) {
                    throw new Exception('检测到RAG知识库存在变动,请刷新后再试!');
                }

                foreach ($Knowledges as $item) {
                    if (!$item['status']) {
                        throw new Exception($item['name'] . ': 知识库已被禁用!');
                    }
                    //挂载RAG知识库
                    $item['type'] = 1;//小红书
                    KnowledgeLogic::newBind($item, $robot, $post['kb_type']);
                }
            }

            // 主模型检测
            $mainModel = (new Models())->where(['id' => intval($post['model_id'])])->findOrEmpty();
            if (!$mainModel || !$mainModel['is_enable']) {
                throw new Exception('主模型已被下架!');
            }

            // 子模型检测
            $subModel = (new ModelsCost())->where(['id' => intval($post['model_sub_id'])])->findOrEmpty();
            if (!$subModel || !$subModel['status']) {
                throw new Exception('子模型已被下架!');
            }

            if ($subModel['model_id'] != $mainModel['id']) {
                throw new Exception('模型匹配关系异常');
            }

            // 问题优化模型
            $optimizeModel = '';
            if ($post['optimize_m_id'] ?? '') {
                $optimizeModel = ($post['optimize_m_id'] ?: '') . ':' . ($post['optimize_s_id'] ?? '') ?: '';
            }
            if (($post['optimize_ask'] ?? 0) and !$optimizeModel) {
                throw new Exception('请配置问题优化模型');
            }

            if (isset($post['top_p']) && ($post['top_p'] > 1 || $post['top_p'] <= 0)) {
                throw new \Exception('词汇多样性取值范围 0.01到1');
            }
            if (isset($post['temperature']) && ($post['temperature'] > 1 || $post['temperature'] < 0)) {
                throw new \Exception('结果相似性取值范围 0到1');
            }
            if (isset($post['presence_penalty']) && ($post['presence_penalty'] > 1 || $post['presence_penalty'] < 0)) {
                throw new \Exception('特定词重复率取值范围 0到1');
            }
            if (isset($post['frequency_penalty']) && ($post['frequency_penalty'] > 2 || $post['frequency_penalty'] < -2)) {
                throw new \Exception('重复词频率取值范围 -2到2');
            }
            if (isset($post['context_num']) && ($post['context_num'] > 5 || $post['context_num'] < 0)) {
                throw new \Exception('上下文数量取值范围 0到5');
            }

            KbRobot::update([
                                'kb_type'            => intval($post['kb_type'] ?? 2),
                                'kb_ids'             => implode(',', $post['kb_ids']),
                                'icons'              => FileService::setFileUrl($post['icons'] ?? ''),
                                'image'              => $post['image'],
                                'bg_image'           => $post['bg_image'],
                                'name'               => $post['name'],
                                'intro'              => $post['intro'] ?? '',
                                'model'              => $subModel['name'] ?? '',
                                'model_id'           => intval($post['model_id']),
                                'model_sub_id'       => intval($post['model_sub_id']),
                                'sort'               => intval($post['sort'] ?? 0),
                                'cate_id'            => intval($post['cate_id'] ?? 0),
                                'roles_prompt'       => trim($post['roles_prompt'] ?? ''),
                                'is_public'          => intval($post['is_public'] ?? 0),
                                'search_mode'        => $post['search_mode'],
                                'search_tokens'      => $post['search_tokens'] ?? 3000,
                                'search_similar'     => $post['search_similar'] ?? 0.5,
                                'ranking_status'     => $post['ranking_status'] ?? 0,
                                'ranking_score'      => $post['ranking_score'] ?? 0,
                                'is_enable'          => $post['is_enable'] ?? 1,
                                'update_time'        => time(),
                                //问题优化模型
                                'optimize_ask'       => $post['optimize_ask'] ?? 0,
                                'optimize_model'     => $optimizeModel,
                                //空搜索
                                'search_empty_type'  => intval($post['search_empty_type']) ?? 0,
                                'search_empty_text'  => trim($post['search_empty_text'] ?? ''),
                                'welcome_introducer' => trim($post['welcome_introducer'] ?? ''),
                                'copyright'          => $post['copyright'] ?? '',
                                //拟人化
                                'context_num'        => $post['context_num'] ?? 3,
                                'top_p'       => floatval($post['top_p'] ?? 0.5),
                                'frequency_penalty'  => floatval($post['frequency_penalty'] ?? 0.3),
                                'presence_penalty'   => floatval($post['presence_penalty'] ?? 0.2),
                                'temperature' => floatval($post['temperature'] ?? 1.0),
                                //工作流
                                'flow_status'        => $post['flow_status'] ?? 0,
                                'flow_config'        => $post['flow_config'] ?? self::flowConfigDefault(),
                                //显示候选词
                                'logprobs'           => $post['logprobs'] ?? 0,
                                'top_logprobs'       => $post['top_logprobs'] ?? 0,
                                //模糊匹配阈值
                                'threshold'          => floatval($post['threshold'] ?? 0.7),
                                'mode_type'          => $post['mode_type'] ?? 1,
                            ], ['id' => intval($post['id'])]);

            // 自定义菜单
            if (is_array($post['menus'])) {
                $menus = [];
                foreach ($post['menus'] as $item) {
                    $images = [];
                    foreach (($item['images'] ?? []) as $img) {
                        $images[] = FileService::setFileUrl($img);
                    }
                    $menus[] = [
                        'user_id'  => 0,
                        'robot_id' => $robot['id'],
                        'keyword'  => $item['keyword'],
                        'content'  => $item['content'],
                        'images'   => implode(',', $images)
                    ];
                }
                $modelKbRobotInstruct = new KbRobotInstruct();
                $modelKbRobotInstruct
                    ->where(['robot_id' => $robot['id']])
                    ->delete();
                $modelKbRobotInstruct->saveAll($menus);
            }

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 机器人删除
     * @param array $ids
     * @return bool
     * @author kb
     */
    public static function del(array $ids): bool
    {
        try {
            $modelKbRobot = new KbRobot();
            foreach ($ids as $id) {
                $detail = $modelKbRobot
                    ->field(['id'])
                    ->where(['id' => $id])
                    ->findOrEmpty()
                    ->toArray();
                if (!$detail) {
                    throw new Exception('该机器人应用已不存在了，请刷新重试！');
                }
            }

            KbRobot::destroy($ids);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改机器人状态
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function changeStatus(int $id): bool
    {
        try {
            $modelKbRobot = new KbRobot();
            $detail = $modelKbRobot
                ->field(['id,is_enable'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$detail) {
                throw new Exception('该机器人应用已不存在了!');
            }

            KbRobot::update([
                'is_enable'   => !$detail['is_enable'],
                'update_time' => time()
            ], ['id'=>$id]);

            if ($detail['is_enable']) {
                self::setError('禁用成功');
            } else {
                self::setError('启用成功');
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改广场的状态
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function changePublic(int $id): bool
    {
        try {
            $modelKbRobot = new KbRobot();
            $detail = $modelKbRobot
                ->field(['id,is_public'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$detail) {
                throw new Exception('该机器人应用已不存在了!');
            }

            KbRobot::update([
                'is_public'   => !$detail['is_public'],
                'update_time' => time()
            ], ['id'=>$id]);

            if ($detail['is_public']) {
                self::setError('停用成功');
            } else {
                self::setError('公开成功');
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 机器人问答记录
     * @param array $get
     * @return array
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public static function chatRecord(array $get): array
    {
        $pageNo   = intval($get['page_no']   ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);

        $where = [];
        $where[] = ['is_show', '=', 1];
        if (isset($get['user']) && $get['user'] != '') {
            $where[] = ['u.sn|u.nickname|kr.share_identity', 'like', '%'.$get['user'].'%'];
        }

        if (isset($get['keyword']) && $get['keyword'] != '') {
            $where[] = ['kr.ask', 'like', '%'.$get['keyword'].'%'];
        }

        if (isset($get['start_time']) && $get['start_time'] != '') {
            $where[] = ['kr.create_time', '>=', strtotime($get['start_time'])];
        }

        if (isset($get['end_time']) && $get['end_time'] != '') {
            $where[] = ['kr.create_time', '<=', strtotime($get['end_time'])];
        }

        if (isset($get['censor_status']) && $get['censor_status'] != '') {
            $where[] = ['kr.censor_status', '=', $get['censor_status']];
        }

        if (isset($get['robot']) && $get['robot']) {
            $where[] = ['kb.name', 'like', '%'.$get['robot'].'%'];
        }

        $modelKbRobotRecord = new KbRobotRecord();
        $lists = $modelKbRobotRecord
            ->alias('kr')
            ->field([
                'kr.id,kr.ask,kr.reply,kr.tokens,kr.task_time,kr.create_time',
                'kr.model,kr.censor_status,kr.censor_num,kr.censor_result',
                'kr.user_id,u.sn,u.nickname,u.avatar',
                'kr.share_apikey,kr.share_identity,kb.name as robot_name'
            ])
            ->where($where)
            ->leftJoin('user u', 'u.id = kr.user_id')
            ->leftJoin('kb_robot kb', 'kb.id = kr.robot_id')
            ->append(['censor_status_desc', 'censor_result_desc'])
            ->order('kr.id desc')
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        foreach ($lists['data'] as &$item) {
            $item['reply'] = is_array($item['reply']) ? $item['reply'][0]??'' :  $item['reply'];

            if (!$item['share_apikey']) {
                $item['channel'] = '前台';
            } else {
                $key = explode('-', $item['share_apikey'])[0]??'';
                $item['channel'] = RobotEnum::getSecretDesc($key);
            }


            $item['user'] = [
                'id'       => $item['user_id']  ?? 0,
                'sn'       => $item['sn']       ?? '',
                'nickname' => $item['nickname'] ?? $item['share_identity'],
                'avatar'   => FileService::getFileUrl($item['avatar']??''),
            ];

            $item['tokens'] = format_amount_zero($item['tokens']);
            unset($item['user_id']);
            unset($item['sn']);
            unset($item['nickname']);
            unset($item['mobile']);
            unset($item['avatar']);
            unset($item['know_id']);
            unset($item['share_apikey']);
            unset($item['share_identity']);
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'],
            'lists'     => $lists['data']
        ] ?? [];
    }

    /**
     * @notes 问答记录清空
     * @param array $ids
     * @return bool
     * @author kb
     */
    public static function chatClean(array $ids): bool
    {
        try {
            if (!$ids) {
                throw new Exception('请选择要删除的数据');
            }

            KbRobotRecord::update([
                'is_show'    => 0,
                'update_time' => time()
            ], [['id', 'in', $ids]]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 工作流默认配置
     * @return string[]
     * @author mjf
     * @date 2025/4/14 14:24
     */
    public static function flowConfigDefault(): array
    {
        return [
            'workflow_id' => '',
            'bot_id'      => '',
            'app_id'      => '',
            'api_token'   => '',
        ];
    }

    /**
     * @desc 删除聊天记录
     * @param array $ids
     * @return true
     */
    public static function deleteChatLog(array $ids): bool
    {
        try {
            ChatLog::whereIn('id', $ids)->select()->delete();
            return true;
        } catch (\Throwable $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }
}