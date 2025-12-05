<?php


namespace app\api\logic\kb;

use app\api\logic\KnowledgeLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\BaseLogic;
use app\common\logic\NoticeLogic;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\coze\AgentCate;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotCategory;
use app\common\model\kb\KbRobotInstruct;
use app\common\model\kb\KbRobotShareLog;
use app\common\model\kb\KbRobotSquare;
use app\common\model\kb\KbRobotVisitor;
use app\common\model\knowledge\Knowledge;
use app\common\model\knowledge\KnowledgeBind;
use app\common\model\sv\SvReplyStrategy;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\service\ConfigService;
use app\common\service\FileService;
use Exception;
use think\facade\Db;

/**
 * 机器人管理逻辑类
 */
class KbRobotLogic extends BaseLogic
{

    /**
     * @notes 机器人详情
     * @param int $id
     * @param int $userId
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public static function detail(int $id, int $userId): array
    {
        $modelKbRobot = new KbRobot();
        $detail = $modelKbRobot
            ->field('id,user_id,cate_id,kb_type,kb_ids,icons,image,bg_image,name,intro,roles_prompt,model,model_id,model_sub_id,search_mode,search_tokens,search_similar,ranking_status,ranking_score,context_num,is_public,is_enable,optimize_ask,optimize_model,top_p,presence_penalty,frequency_penalty,logprobs,top_logprobs,search_empty_type,search_empty_text,welcome_introducer,copyright,mode_type')
            ->field('threshold')
            ->where(['id' => $id])
            ->findOrEmpty()
            ->toArray();

        if ($detail) {
//            if (!$detail['is_public'] && $detail['user_id'] !== $userId) {
//                throw new Exception('无权限使用');
//            }

            unset($detail['user_id']);
            $detail['kb_ids']    = !empty($detail['kb_ids']) ? explode(',', $detail['kb_ids']) : [];
            $detail['icons']        = FileService::getFileUrl($detail['icons']);
            $detail['model']     = $detail['model'] ?: 'deepseek';
            $detail['model_id']  = $detail['model_id'] ?: 4;
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
                if($detail['kb_type'] == 1){
                    $kbIds = Knowledge::whereIn('id', $detail['kb_ids'])->column('id');
                }else{
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
                    ], ['id'=>$id]);
                }
            }

            // 工作流配置
            if (empty($detail['flow_config'])) {
                $detail['flow_config'] = self::flowConfigDefault();
            }

        }

        self::addVisit(1, $id);
        return $detail;
    }

    /**
     * @ntoes 机器人新增
     * @param array $post
     * @param int $userId
     * @return bool|array
     * @author kb
     */
    public static function add(array $post, int $userId): bool|array
    {
        $model = new KbRobot();
        $model->startTrans();
        try {
            // 默认图标
            $iconImage = '';
            $chatImage = FileService::getFileUrl(ConfigService::get('website', 'shop_logo'));

            // 创建机器人
            $botCode = generate_sn(KbRobot::class, 'code', $userId);
            $robot = KbRobot::create([
                                         'user_id'      => $userId,
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
                                        "user_id"            => $userId,
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
                                        "working_enable"     => 0,
                                    ]);

            $model->commit();
            return ['id'=>$robot['id']]??[];
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
    public static function edit(array $post, int $userId): bool
    {
        $model = new KbRobot();
        $model->startTrans();
        try {
            // 机器人检测
            $robot = $model->field(['id,is_enable'])
                ->where(['id'=>intval($post['id'])])
                ->where(['user_id'=>$userId])
                ->findOrEmpty();
            if (!$robot || !$robot['is_enable']) {
                $errMsg = !$robot ? '机器人不存在了' : '机器人被禁用,禁止操作!';
                throw new Exception($errMsg);
            }
            if (is_string($post['kb_ids'])) {
                $post['kb_ids'] = explode(',', $post['kb_ids']);
            }
            if(count($post['kb_ids']) == 0){
                KnowledgeBind::where('data_id', $robot->id)->where('user_id', $userId)->where('type', 1)->select()->delete();
            }

            // 向量知识库检测
            if (($post['kb_ids']??[]) && $post['kb_type'] == 2) {
                $modelKnow = new KbKnow();
                $knows = $modelKnow
                    ->field(['id,name,embedding_model_id,is_enable'])
                    ->whereIn('id', $post['kb_ids'])
                    ->select()
                    ->toArray();

                if (count($post['kb_ids']) !== count($knows)) {
                    throw new Exception('检测到向量知识库存在变动,请刷新后再试!');
                }

                $vectorModels = $knows[0]['embedding_model_id']??0;
                foreach ($knows as $item) {
                    if (!$item['is_enable']) {
                        throw new Exception($item['name'].': 知识库已被禁用!');
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
            if (($post['kb_ids']??[]) && $post['kb_type'] == 1) {
                $modelKnowledge = new Knowledge();
                $Knowledges = $modelKnowledge
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
                        throw new Exception($item['name'].': 知识库已被禁用!');
                    }
                    //挂载RAG知识库
                    $item['type'] = 1;//小红书
                    KnowledgeLogic::newBind($item, $robot, $post['kb_type']);
                }
            }

            // 主模型检测
            $mainModel = (new Models())->where(['id'=>intval($post['model_id'])])->findOrEmpty();
            if (!$mainModel || !$mainModel['is_enable']) {
                throw new Exception('主模型已被下架!');
            }

            // 子模型检测
            $subModel = (new ModelsCost())->where(['id'=>intval($post['model_sub_id'])])->findOrEmpty();
            if (!$subModel || !$subModel['status']) {
                throw new Exception('子模型已被下架!');
            }

            if ($subModel['model_id'] != $mainModel['id']) {
                throw new Exception('模型匹配关系异常');
            }

            //重排模型
//            $rankingModel = '';
//            $rankingModelId = intval($post['ranking_model']??0);
//            if ($rankingModelId) {
//                $reModelId = (new ModelsCost())->where(['model_id'=>$rankingModelId])->value('id')??0;
//                $rankingModel = $rankingModelId.':'.$reModelId;
//            }
//            if (!empty($post['ranking_status']) and !$rankingModel) {
//                throw new Exception('请配置重排模型');
//            }

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
                'kb_type'            => intval($post['kb_type']??2),
                'kb_ids'             => implode(',', $post['kb_ids']),
                'icons'              => FileService::setFileUrl($post['icons']??''),
                'image'              => $post['image'],
                'bg_image' => $post['bg_image'],
                'name'               => $post['name'],
                'intro'              => $post['intro']??'',
                'model'              => $subModel['name']??'',
                'model_id'           => intval($post['model_id']),
                'model_sub_id'       => intval($post['model_sub_id']),
                'sort'               => intval($post['sort']??0),
                'cate_id'            => intval($post['cate_id']??0),
                'roles_prompt'       => trim($post['roles_prompt']??''),
                'is_public'          => intval($post['is_public']??0),
                'search_mode'        => $post['search_mode'],
                'search_tokens'      => $post['search_tokens']??3000,
                'search_similar'     => $post['search_similar']??0.5,
                'ranking_status'     => $post['ranking_status']??0,
                'ranking_score'      => $post['ranking_score']??0,
                'is_enable'          => $post['is_enable']??1,
                'update_time'        => time(),
                //问题优化模型
                'optimize_ask'       => $post['optimize_ask'] ?? 0,
                'optimize_model'     => $optimizeModel,
                //空搜索
                'search_empty_type' => intval($post['search_empty_type']) ?? 0,
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
                'flow_status' => $post['flow_status'] ?? 0,
                'flow_config' => $post['flow_config'] ?? self::flowConfigDefault(),
                //显示候选词
                'logprobs'           => $post['logprobs'] ?? 0,
                'top_logprobs'       => $post['top_logprobs'] ?? 0,
                //模糊匹配阈值
                'threshold'          => floatval($post['threshold'] ?? 0.7),
                'mode_type'          => $post['mode_type'] ?? 1,
            ], ['id'=>intval($post['id'])]);

            // 自定义菜单
            if (is_array($post['menus'])) {
                $menus = [];
                foreach ($post['menus'] as $item) {
                    $images = [];
                    foreach (($item['images'] ?? []) as $img) {
                        $images[] = FileService::setFileUrl($img);
                    }
                    $menus[] = [
                        'user_id'  => $userId,
                        'robot_id' => $robot['id'],
                        'keyword'  => $item['keyword'],
                        'content'  => $item['content'],
                        'images'   => implode(',', $images)
                    ];
                }
                $modelKbRobotInstruct = new KbRobotInstruct();
                $modelKbRobotInstruct
                    ->where(['user_id' => $userId])
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
     * @param int $id
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function del(int $id, int $userId): bool
    {
        $model = new KbRobot();
        $model->startTrans();
        try {
            // 验证机器人
            $robot = $model->field(['id,name,is_enable'])
                ->where(['id'=>$id])
                ->where(['user_id'=>$userId])
                ->findOrEmpty();

            if (!$robot) {
                throw new Exception('机器人不存在了!');
            }

            // 发起删除
            KbRobot::destroy($id);

            // 删除智能体关联的回复策略
            SvReplyStrategy::where('robot_id', $id)->delete();

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 新增访问记录
     * @param int $terminal
     * @param int $robotId
     * @return bool
     * @author kb
     */
    public static function addVisit(int $terminal, int $robotId): bool
    {
        try {
            $ip =  request()->ip();

            // 一个ip一个终端一天只生成一条记录
            $record = (new KbRobotVisitor())
                ->where(['ip' => $ip])
                ->where(['robot_id' => $robotId])
                ->where(['terminal' => $terminal])
                ->whereDay('create_time')
                ->findOrEmpty();

            // 增加访客在终端的浏览量
            if (!$record->isEmpty()) {
                $record->visit += 1;
                $record->save();
                return true;
            }

            // 生成访客记录
            KbRobotVisitor::create([
                'ip'       => $ip,
                'robot_id' => $robotId,
                'terminal' => $terminal,
                'visit'    => 1
            ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 分类列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author kb
     * @date 2024/7/25 11:29
     */
    public static function categoryLists(){
        $lists = KbRobotCategory::where(['is_enable'=>1])
            ->withoutField('update_time,create_time,delete_time,is_enable,sort')
            ->select()->toArray();
        return $lists;
    }

    /**
     * @notes 机器人分享
     * @param array $params
     * @param array $userInfo
     * @return bool
     * @author kb
     * @date 2024/7/25 10:39
     */
    public static function share(array $params,array $userInfo)
    {
        try {
            Db::startTrans();
            $robotId = $params['id'] ?? 0;
            $cateId = $params['cate_id'] ?? 0;
            if(empty($robotId)){
                throw new Exception('请选择智能体');
            }
            $isOpen     = ConfigService::get('robot_award', 'is_open');
            $autoAudit  = ConfigService::get('robot_award', 'auto_audit');
            $oneAward   = 0;
            if (!$isOpen) {
                throw new Exception('智能体分享未开启，请联系管理员');
            }
            $robot = KbRobot::where(['user_id'=>$userInfo['user_id'],'id'=>$robotId])->findOrEmpty();
            if($robot->isEmpty()){
                throw new Exception('智能体不存在，请重新选择');
            }
            $shareLog = KbRobotShareLog::where(['user_id'=>$userInfo['user_id'],'robot_id'=>$robotId])
                ->findOrEmpty();
            //第一次分享获取的奖励
            if($autoAudit){
                $robot->is_public = 1;
                $robot->save();
                if($shareLog->isEmpty() && $isOpen){
                    $dayNum   = ConfigService::get('robot_award', 'day_num');
                    $oneAward   = ConfigService::get('robot_award', 'one_award');
                    $shareNum = KbRobotSquare::where(['user_id'=>$userInfo['user_id'],'verify_status'=>1])
                        ->whereDay('create_time')
                        ->count();
                    if ($dayNum > $shareNum  && $oneAward) {
                        User::update(['balance'=>['inc',$oneAward]],['id'=>$userInfo['user_id']]);
                        // 记录账户流水
                        UserAccountLog::add(
                            $userInfo['user_id'],
                            AccountLogEnum::UM_INC_ROBTO_SHARE,
                            AccountLogEnum::INC,
                            $oneAward
                        );
                        $unit = ConfigService::get('chat', 'price_unit', '电力值');
                        BaseLogic::$returnData = '分享成功,获取'.format_amount_zero($oneAward).$unit;
                    }

                }
            }


            $square = KbRobotSquare::create([
                'user_id'  => $userInfo['user_id'],
                'robot_id' => $robotId,
                'cate_id'  => $cateId,
                'verify_status'=>$autoAudit,
                'is_show'  => $autoAudit
            ]);
            //记录日志
            KbRobotShareLog::create([
                'square_id' => $square['id'],
                'user_id'  => $userInfo['user_id'],
                'robot_id' => $robotId,
                'channel' => $userInfo['terminal'],
//                'cate_id'  => $cateId,
                'balance'  => $oneAward,
                'is_show'  => 1
            ]);
            if(1 == $autoAudit){
                //添加信息通知
                NoticeLogic::addSquareNotice(
                    $userInfo['user_id'],
                    7,
                    1,
                    [
                        'square_id'     => $square->id,
                        'robot_id'      => $robotId,
                        'verify_status' => 1,
                        'verify_result' => '',
                        'balance'       => $oneAward
                    ]
                );


            }
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消分享
     * @param $params
     * @param $userId
     * @return bool
     * @author kb
     * @date 2024/7/26 16:38
     */
    public static function cancelShare(array $params,int $userId){
        try{
            Db::startTrans();
            (new KbRobotSquare())
                ->where(['robot_id'=>intval($params['id'])])
                ->where(['user_id'=>$userId])
                ->update([
                    'delete_time' => time()
                ]);
            KbRobot::where(['user_id'=>$userId,'id'=>$params['id']])->update(['is_public'=>0]);
            Db::commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
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

}