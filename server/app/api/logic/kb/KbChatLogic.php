<?php


namespace app\api\logic\kb;

use app\common\enum\ChatEnum;
use app\common\enum\kb\KnowEnum;
use app\common\enum\notice\NoticeEnum;
use app\common\logic\BaseLogic;
use app\common\model\chat\ChatLog;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowFiles;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotPublish;
use app\common\model\kb\KbRobotRecord;
use app\common\model\kb\KbRobotSession;
use app\common\model\kb\KbRobotVisitor;
use app\common\model\notice\NoticeRecord;
use app\common\model\user\User;
use app\common\pgsql\KbEmbedding;
use app\common\service\FileService;
use app\queue\BaseQueue;
use Exception;
use Ramsey\Uuid\Uuid;
use think\db\exception\DbException;

/**
 * 机器人对话逻辑类
 */
class KbChatLogic extends BaseLogic
{
    /**
     * @notes 机器人对话记录
     * @param array $get
     * @param int $userId
     * @return array
     * @throws DbException
     * @author kb
     */
    public static function chatRecord(array $get, int $userId): array
    {
        // 分页参数
        $pageNo     = intval($get['page_no'] ?? 1);
        $pageSize   = intval($get['page_size'] ?? 25);
        $robotId    = intval($get['robot_id']??0);
        $categoryId = intval($get['category_id']??0);
        $squareId   = intval($get['square_id']??0);

        // 查询分享
        $shareId = 0;
        if (!empty($get['apikey']) || !empty($get['identity'])) {
            $modelKbRobotPublish = new KbRobotPublish();
            $publish = $modelKbRobotPublish
                ->where(['apikey'=>trim($get['apikey'])])
                ->findOrEmpty()
                ->toArray();

            $shareId = $publish['id']??0;
            $robotId = $publish['robot_id']??0;
            if (!$publish || $publish['secret'] !== $get['password']??'') {
                $error = !$publish ? '分享渠道不存在了!' : '访问密码错误!';
                self::setError($error);
                return [];
            }
        }

        // 查询条件
        $where[] = ['robot_id', '=', $robotId];
        if (!empty($get['apikey']) && !empty($get['identity'])) {
            $where[] = ['category_id', '=', 0];
            $where[] = ['share_id', '=', $shareId];
            $where[] = ['share_identity', '=', trim($get['identity'])];
        } else {
            $where[] = ['user_id', '=', $userId];
            $where[] = ['category_id', '=', $categoryId];
        }

        // 广场来的
        if ($squareId) {
            $where[] = ['square_id', '=', $squareId];
        }

        // 查询机器人
        $modelKbRobot = new KbRobot();
        $kbRobot = $modelKbRobot
            ->field(['id,code,is_show_context,is_show_quote'])
            ->where(['id'=>$robotId])
            ->order('id desc')
            ->findOrEmpty()
            ->toArray();

        // 查询记录
        $model = new KbRobotRecord();
        $lists = $model
            ->field([
                'id,ask,reply,reasoning,model,quotes,context,images,video,files',
                'files_plugin,correlation,task_time,feedback,is_feedback,is_flow,create_time'
            ])
            ->where($where)
            ->where(['is_show'=>1])
            ->order('id desc')
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        $data = [];
        $lists['data'] = array_reverse($lists['data']);
        foreach ($lists['data'] as $item) {
            // 引用的内容
            if ($kbRobot['is_show_quote']) {
                $quotes = json_decode($item['quotes'] ?? '[]', true);
            }

            // 对话上下文
            if ($kbRobot['is_show_context']) {
                $context = json_decode($item['context'] ?? '[]', true);
                foreach ($context as &$c) {
                    $c['role'] = match ($c['role']) {
                        'system'    => '系统指令（' . $c['role'] . '）',
                        'user'      => '用户输入（' . $c['role'] . '）',
                        'assistant' => '机器人输出（' . $c['role'] . '）'
                    };
                }
            }

            // 图片处理
            $images = [];
            if ($item['images']) {
                $imageList = json_decode($item['images'], true);
                foreach ($imageList as $img) {
                    $images[] =  ['url'=>FileService::getFileUrl($img['url']), 'name'=>$img['name']];
                }
            }

            // 视频处理
            $videos = [];
            if ($item['video']) {
                $fileList = json_decode($item['video'], true);
                foreach ($fileList as $file) {
                    $videos[] = ['url'=>FileService::getFileUrl($file['url']), 'name'=>$file['name']];
                }
            }

            // 附件处理
            $files = [];
            if ($item['files']) {
                $fileList = json_decode($item['files'], true);
                foreach ($fileList as $file) {
                    $files[] = ['url'=>FileService::getFileUrl($file['url']), 'name'=>$file['name']];
                }
            }

            // 图片理解
            $files_plugin = json_decode($item['files_plugin']??'[]', true);
            if ($files_plugin) {
                foreach ($files_plugin as &$p) {
                    $p['url'] = FileService::getFileUrl($p['url']);
                }
            }

            if ($item['is_flow']) {
                $item['model'] = '';
            }

            // User
            $data[] = [
                'type'    => 1,
                'id'      => $item['id'],
                'content' => $item['ask'],
                'files_plugin' => $files_plugin,
                'create_time' => $item['create_time']
            ];

            // AI
            $data[] = [
                'type'    => 2,
                'id'      => $item['id'],
                'model'   => $item['model'],
                'content' => $item['reply'],
                'reasoning' => $item['reasoning'],
                'quotes'    => $quotes??[],
                'context'   => $context??[],
                'images'    => $images,
                'videos'    => $videos,
                'files'     => $files,
                'correlation' => json_decode($item['correlation']??'[]', true),
                'feedback'    => $item['feedback'],
                'is_feedback' => $item['is_feedback'],
                'create_time' => $item['create_time']
            ];
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'],
            'lists'     => $data
        ] ?? [];
    }

    /**
     * @notes 机器人对话清空
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function chatClean(array $post, int $userId): bool
    {
        try {
            $robotId    = intval($post['robot_id']??0);
            $categoryId = intval($post['category_id']??0);
            $squareId   = intval($post['square_id']??0);

            // 查询分享
            $shareId = 0;
            if (!empty($post['apikey']) || !empty($post['identity'])) {
                $modelKbRobotPublish = new KbRobotPublish();
                $publish = $modelKbRobotPublish
                    ->where(['apikey'=>trim($post['apikey'])])
                    ->findOrEmpty()
                    ->toArray();

                $shareId = $publish['id']??0;
                $robotId = $publish['robot_id']??0;
                if (!$publish || $publish['secret'] !== $post['password']??'') {
                    $error = !$publish ? '分享渠道不存在了!' : '访问密码错误!';
                    self::setError($error);
                    return false;
                }
            }

            // 查询条件
            $where[] = ['robot_id', '=', $robotId];
            if (!empty($post['apikey']) || !empty($post['identity'])) {
                $where[] = ['share_id', '=', $shareId];
                $where[] = ['share_identity', '=', trim($post['identity'])];
                $where[] = ['category_id', '=', 0];
            } else {
                $where[] = ['user_id', '=', $userId];
                $where[] = ['category_id', '=', $categoryId];
            }

            // 删除指定ID记录
            if (isset($params['id']) && $params['id']) {
                $where[] = ['id', '=', intval($params['id'])];
            }

            // 广场来的
            if ($squareId) {
                $where[] = ['square_id', '=', $squareId];
            }

            // 删除记录
            $model = new KbRobotRecord();
            $model
                ->where($where)
                ->update([
                    'is_show'    => 0,
                    'update_time' => time()
                ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修正对话数据
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function chatCorrect(array $post, int $userId): bool
    {
        try {
            $kbId   = intval($post['kb_id'] ?? 0);
            $ask    = $post['ask']    ?? '';
            $reply  = $post['reply']  ?? '';
            $images = $post['images'] ?? [];
            $files  = $post['files']  ?? [];

            if (!$ask) {
                throw new Exception('提问内容不能为空');
            }

            // 处理附件
            foreach ($files as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 处理图片
            foreach ($images as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 查询知识库
            $know = (new KbKnow())
                ->where(['id'=>$kbId])
                ->where(['user_id'=>$userId])
                ->findOrEmpty();

            // 验证知识库
            if ($know->isEmpty()) {
                throw new Exception('知识库不存在,请刷新页面!');
            }

            // 验证禁用值
            if (!$know->is_enable) {
                throw new Exception('知识库被禁用,禁止操作!');
            }

            // 查询文件夹
            $knowFile = (new KbKnowFiles())
                ->where(['know_id'=>$kbId])
                ->where(['user_id'=>$userId])
                ->order('is_default desc')
                ->findOrEmpty();

            // 验证文件夹
            if ($knowFile->isEmpty()) {
                throw new Exception('该知识库没有存储文件!');
            }

//            // 验证模型
//            $modelModelsCost = new ModelsCost();
//            $models = $modelModelsCost->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'name'=>$know['embedding_model']])->findOrEmpty()->toArray();
//            if (!$models || !$models['status']) {
//                $error = !$models ? '训练模型已下架了,无法再训练!' : '训练模型已被停用,无法再训练!';
//                throw new Exception($error);
//            }
//            $embModel = $models['channel'];

            // 验证主模型
            $mainModel = (new Models())->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'id'=>$know['embedding_model_id']])->findOrEmpty();
            if ($mainModel->isEmpty() || !$mainModel->is_enable) {
                throw new Exception('训练模型已被下架了!');
            }

            // 验证模型
            $subModels = (new ModelsCost())->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'id'=>$know['embedding_model_sub_id']])->findOrEmpty()->toArray();
            if (!$subModels) {
                throw new Exception('训练模型已下架,无法再训练!');
            }

            $uuid = (Uuid::uuid4())->toString();
            //$vectorModels = config('ai.VectorModels');
            $modelKbEmbedding = new KbEmbedding();
            $modelKbEmbedding
                ->insert([
                    'uuid'        => $uuid,
                    'user_id'     => $userId,
                    'kb_id'       => $kbId,
                    'fd_id'       => $knowFile['id'],
                    'salt'        => md5($ask),
                    'emb_model_id'  => $mainModel['id'],
                    'channel'       => $subModels['channel'],
                    'model'         => $subModels['name'],
                    //'dimension'   => $vectorModels[$embModel]['dimension'],
                    'question'    => $ask,
                    'answer'      => $reply,
                    'annex'       => json_encode(['images'=>$images, 'files'=>$files], JSON_UNESCAPED_UNICODE),
                    'status'      => KnowEnum::RUN_WAIT,
                    'create_time' => time(),
                    'update_time' => time(),
                    'delete_time' => 0
                ]);

            BaseQueue::pushEM(['uuid'=>$uuid]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 对话分类列表
     * @param int $robotId
     * @param int $userId
     * @return array
     * @throws DbException
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public static function cateLists(int $robotId, int $userId): array
    {
        $modelKbRobotSession = new KbRobotSession();
        return $modelKbRobotSession
            ->withoutField('user_id,robot_id,delete_time')
            ->where(['user_id'=>$userId])
            ->where(['robot_id'=>$robotId])
            ->where(['square_id'=>0])
            ->order('id desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 对话分类创建
     * @param int $robotId
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function cateAdd(int $robotId, int $userId): bool
    {
        try {
            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot
                ->field(['id,user_id,is_enable,create_time'])
                ->where(['id'=>$robotId])
                ->findOrEmpty()
                ->toArray();

            if (!$kbRobot || !$kbRobot['is_enable']) {
                $error = !$kbRobot ? '机器人不存在了' : '机器人已被禁用,禁止操作!';
                throw new Exception($error);
            }

            KbRobotSession::create([
                'user_id'   => $userId,
                'robot_id'  => $robotId,
                'square_id' => 0,
                'name'      => '新的会话'
            ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 对话分类编辑
     * @param int $id
     * @param int $robotId
     * @param string $name
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function cateEdit(int $id, int $robotId, string $name, int $userId): bool
    {
        try {
            if (!$id)      { throw new Exception('请选择会话窗口'); }
            if (!$robotId) { throw new Exception('机器人选择异常'); }
            if (!$name)    { throw new Exception('请填写名称'); }

            // 机器人验证
            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot
                ->field(['id,user_id,is_enable,create_time'])
                ->where(['id'=>$robotId])
                ->findOrEmpty()
                ->toArray();

            if (!$kbRobot || !$kbRobot['is_enable']) {
                $error = !$kbRobot ? '机器人不存在了' : '机器人已被禁用,禁止操作!';
                throw new Exception($error);
            }

            // 会话验证
            $modelKbRobotSession = new KbRobotSession();
            $category = $modelKbRobotSession
                ->where(['id' => $id])
                ->where(['robot_id' => $robotId])
                ->where(['user_id' => $userId])
                ->where(['square_id' => 0])
                ->findOrEmpty();

            if ($category->isEmpty()) {
                throw new Exception('会话已不存在!');
            }

            KbRobotSession::update([
                'name' => mb_substr($name, 0, 20, "utf-8"),
                'update_time' => time()
            ], ['id'=>$id]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 对话分类删除
     * @param int $id
     * @param int $robotId
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function cateDel(int $id, int $robotId, int $userId): bool
    {
        try {
            if (!$id)      { throw new Exception('请选择会话窗口'); }
            if (!$robotId) { throw new Exception('机器人选择异常'); }

            // 机器人验证
            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot
                ->field(['id,user_id,is_enable,create_time'])
                ->where(['id'=>$robotId])
                ->findOrEmpty()
                ->toArray();

            if (!$kbRobot || !$kbRobot['is_enable']) {
                $error = !$kbRobot ? '机器人不存在了' : '机器人已被禁用了,禁止操作!';
                throw new Exception($error);
            }

            // 分类验证
            $modelSession = new KbRobotSession();
            $category = $modelSession
                ->where(['id'=>$id])
                ->where(['robot_id'=>$robotId])
                ->where(['user_id'=>$userId])
                ->where(['square_id' => 0])
                ->findOrEmpty()
                ->toArray();

            if ($category) {
                KbRobotSession::destroy($id);

                $count = $modelSession->where(['user_id'=>$userId, 'robot_id'=>$robotId, 'square_id'=>0])->count();
                if (0 == $count) {
                    KbRobotSession::create([
                        'user_id'  => $userId,
                        'robot_id' => $robotId,
                        'name'     => '新的会话'
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 对话分类清空
     * @param int $robotId
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function cateClear(int $robotId, int $userId): bool
    {
        try {
            if (!$robotId) { throw new Exception('机器人选择异常'); }

            // 机器人验证
            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot
                ->field(['id,user_id,is_enable,create_time,update_time'])
                ->where(['id'=>$robotId])
                ->findOrEmpty()
                ->toArray();

            if (!$kbRobot || !$kbRobot['is_enable']) {
                $error = !$kbRobot ? '机器人不存在了' : '机器人已被禁用了,禁止操作!';
                throw new Exception($error);
            }

            // 会话验证
            $modelSession = new KbRobotSession();
            $modelSession
                ->where(['robot_id'=>$robotId])
                ->where(['user_id'=>$userId])
                ->where(['square_id'=>0])
                ->update([
                    'delete_time' => time()
                ]);

            KbRobotSession::create([
                'user_id'  => $userId,
                'robot_id' => $robotId,
                'name'     => '新的会话'
            ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 对话数据统计
     * @param int $robotId
     * @param int $userId
     * @return array
     * @throws DbException
     * @throws Exception
     */
    public static function dataCount(int $robotId, int $userId): array
    {
        $modelKbRobot = new KbRobot();
        $modelKbRobotRecord = new ChatLog();
        $modelKbRobotVisitor = new KbRobotVisitor();

        $kbRobot = $modelKbRobot
            ->field(['id,user_id,is_enable,create_time'])
            ->where(['id'=>$robotId])
            ->findOrEmpty()
            ->toArray();

        if (!$kbRobot || !$kbRobot['is_enable']) {
            $error = !$kbRobot ? '机器人不存在了' : '机器人已被禁用,禁止操作!';
            throw new Exception($error);
        }

        if ($kbRobot['user_id'] !== $userId) {
            throw new Exception('您不是拥有者,无权限操作!');
        }

        // 获取本周时间
        list($y, $m, $d, $w) = explode('-', date('Y-m-d-w'));
        if($w == 0) $w = 7;
        $week = [mktime(0, 0, 0, $m, $d - $w + 1, $y), mktime(23, 59, 59, $m, $d - $w + 7, $y)];

        // 今天对话次数
        $todayChatCount     = $modelKbRobotRecord->where(['robot_id'=>$robotId])->whereDay('create_time')->count();
        // 昨天对话次数
        $yesterdayChatCount = $modelKbRobotRecord->where(['robot_id'=>$robotId])->whereDay('create_time', 'yesterday')->count();
        // 本周对话次数
        $weekChatCount      = $modelKbRobotRecord->where(['robot_id'=>$robotId])->whereTime('create_time', 'between', [$week[0], $week[1]])->count();
        // 全部对话次数
        $wholeChatCount     = $modelKbRobotRecord->where(['robot_id'=>$robotId])->count();

        // 昨天访问用户
        $yesterdayVisitorCount = $modelKbRobotVisitor->where(['robot_id'=>$robotId])->whereDay('create_time', 'yesterday')->count();
        // 今天访问用户
        $todayVisitorCount     = $modelKbRobotVisitor->where(['robot_id'=>$robotId])->whereDay('create_time')->count();
        // 本周访问用户
        $weekVisitorCount      = $modelKbRobotVisitor->where(['robot_id'=>$robotId])->whereTime('create_time', 'between', [$week[0], $week[1]])->count();
        // 全部访问用户
        $wholeVisitorCount     = $modelKbRobotVisitor->where(['robot_id'=>$robotId])->count();

        return [
            'robot' => [
                'todayChatCount'     => $todayChatCount,
                'yesterdayChatCount' => $yesterdayChatCount,
                'weekChatCount'      => $weekChatCount,
                'wholeChatCount'     => $wholeChatCount
            ],
            'visitor' => [
                'todayVisitorCount'     => $todayVisitorCount,
                'yesterdayVisitorCount' => $yesterdayVisitorCount,
                'weekVisitorCount'      => $weekVisitorCount,
                'wholeVisitorCount'     => $wholeVisitorCount
            ]
        ]??[];
    }

    /**
     * @notes 对话记录列表
     * @param array $get
     * @return array
     * @throws DbException
     * @author kb
     */
    public static function dataRecord(array $get): array
    {
        // 接收参数
        $pageNo   = intval($get['page_no']   ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);
        $robotId  = intval($get['robot_id']  ?? 0);
        $isFeedback = $get['is_feedback'] ?? -1;

        // 查询条件
        $where[] = ['robot_id', '=', $robotId];
        $where[] = ['is_show', '=', 1];

        if (is_numeric($isFeedback) and $isFeedback >= 0) {
            $where[] = ['is_feedback', '=', intval($isFeedback)];
        }

        // 查询机记录
        $modelKbRobotRecord = new KbRobotRecord();
        $lists = $modelKbRobotRecord
            ->field([
                'id,user_id,ask,reply,quotes,tokens,task_time,create_time',
                'share_id,share_identity,feedback,is_feedback'
            ])
            ->where($where)
            ->order('id desc')
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        $modelUser = new User();
        foreach ($lists['data'] as &$item) {
            if (!$item['user_id'] && $item['share_identity']) {
                $item['user'] = [
                    'avatar'   => '',
                    'nickname' => $item['share_identity']
                ];
            } else {
                $item['user'] = $modelUser
                    ->field(['avatar,nickname'])
                    ->where(['id'=>$item['user_id']])
                    ->findOrEmpty()
                    ->toArray();
            }
            $item['quotes'] = json_decode($item['quotes'], true);
            $item['feedback'] = $item['feedback'] ?: '';
            unset($item['user_id']);
            unset($item['share_id']);
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
     * @notes 对话数据修正
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function dataRevise(array $post, int $userId): bool
    {
        try {
            $kbId   = (int) ($post['kb_id'] ?? 0);
            $ask    = $post['ask']    ?? '';
            $reply  = $post['reply']  ?? '';
            $images = $post['images'] ?? [];
            $files  = $post['files']  ?? [];

            if (!$ask) {
                throw new Exception('提问内容不能为空!');
            }

            // 处理附件
            foreach ($files as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 处理图片
            foreach ($images as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 查询知识库
            $know = (new KbKnow())
                ->field(['id,embedding_model,is_enable'])

                ->where(['user_id'=>$userId])
                ->where(['id'=>$kbId])
                ->findOrEmpty();

            // 验证知识库
            if ($know->isEmpty()) {
                throw new Exception('知识库不存在,请刷新页面');
            }

            // 验证禁用值
            if (!$know->is_enable) {
                throw new Exception('知识库被禁用,禁止操作');
            }

            // 查询文件夹
            $knowFile = (new KbKnowFiles())
                ->where(['user_id'=>$userId])
                ->where(['know_id'=>$kbId])
                ->order('is_default desc')
                ->findOrEmpty();

            // 验证文件夹
            if ($knowFile->isEmpty()) {
                throw new Exception('该知识库没有存储文件哦!');
            }

            // 验证模型
            $modelModelsCost = new ModelsCost();
            $models = $modelModelsCost->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'name'=>$know['embedding_model']])->findOrEmpty()->toArray();
            if (!$models || !$models['status']) {
                $error = !$models ? '训练模型已下架,无法再训练!' : '训练模型已被停用,无法再训练';
                throw new Exception($error);
            }
            $embModel = $models['channel'];

            $uuid = (Uuid::uuid4())->toString();
            $vectorModels = config('ai.VectorModels');
            $modelKbEmbedding = new KbEmbedding();
            $modelKbEmbedding
                ->insert([
                    'uuid'        => $uuid,
                    'kb_id'       => $kbId,
                    'user_id'     => $userId,
                    'fd_id'       => $knowFile['id'],
                    'salt'        => md5($ask),
                    'model'       => $embModel,
                    'dimension'   => $vectorModels[$embModel]['dimension'],
                    'question'    => $ask,
                    'answer'      => $reply,
                    'annex'       => json_encode(['images'=>$images, 'files'=>$files], JSON_UNESCAPED_UNICODE),
                    'status'      => KnowEnum::RUN_WAIT,
                    'create_time' => time(),
                    'update_time' => time(),
                    'delete_time' => 0
                ]);

            BaseQueue::pushEM(['uuid'=>$uuid]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 对话数据删除
     * @param int $robotId
     * @param array $ids
     * @param int $userId
     * @return false|void
     */
    public static function dataDelete(int $robotId, array $ids, int $userId)
    {
        try {
            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot
                ->field(['id,user_id,is_enable'])
                ->where(['id'=>$robotId])
                ->findOrEmpty()
                ->toArray();

            if (!$kbRobot || !$kbRobot['is_enable']) {
                $error = !$kbRobot ? '机器人不存在了' : '机器人已被禁用,禁止操作!';
                throw new Exception($error);
            }

            if ($kbRobot['user_id'] !== $userId) {
                throw new Exception('您不是拥有者,无权限操作!');
            }

            $modelKbRobotRecord = new KbRobotRecord();
            $modelKbRobotRecord
                ->whereIn('id', $ids)
                ->where(['robot_id'=>$robotId])
                ->update([
                    'is_show'     => 0,
                    'update_time' => time()
                ]);

        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 对话记录反馈
     * @param int $robotId
     * @param int $recordId
     * @param int $sendUid
     * @param string $content
     * @return bool
     */
    public static function feedback(int $robotId, int $recordId, int $sendUid, string $content): bool
    {
        try {
            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot
                ->field(['id,user_id,is_enable'])
                ->where(['id'=>$robotId])
                ->findOrEmpty()
                ->toArray();

            if (!$kbRobot || !$kbRobot['is_enable']) {
                $error = !$kbRobot ? '机器人不存在了' : '机器人已被禁用,禁止操作!';
                throw new Exception($error);
            }

            $modelKbRobotRecord = new KbRobotRecord();
            $chatRecord = $modelKbRobotRecord
                ->where(['id'=>$recordId])
                ->where(['robot_id'=>$robotId])
                ->findOrEmpty()
                ->toArray();

            if (!$chatRecord) {
                throw new Exception('对话记录不存在了!');
            }

            $chatRecord2 = $modelKbRobotRecord
                ->where(['id'=>$recordId])
                ->where(['robot_id'=>$robotId])
                ->where(['is_feedback'=>1])
                ->findOrEmpty()
                ->toArray();

            if ($chatRecord2) {
                throw new Exception('您已对此记录反馈过了,请勿重复操作!');
            }

            KbRobotRecord::update([
                'feedback' => $content,
                'is_feedback' => 1
            ], ['id'=>$chatRecord['id']]);

            NoticeRecord::create([
                'user_id'     => $kbRobot['user_id'],
                'robot_id'    => $kbRobot['id'],
                'send_uid'    => $sendUid,
                'title'       => '反馈通知',
                'content'     => $content,
                'scene_id'    => NoticeEnum::CHAT_FEEDBACK,
                'send_type'   => NoticeEnum::SYSTEM,
                'read'        => 0,
                'recipient'   => 1,
                'notice_type' => 3
            ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}