<?php


namespace app\api\logic\kb;

use app\common\enum\kb\RobotEnum;
use app\common\logic\BaseLogic;
use app\common\model\kb\KbDigital;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotInstruct;
use app\common\model\kb\KbRobotPublish;
use app\common\service\FileService;
use Exception;

/**
 * 发布机器人管理
 */
class KbShareLogic extends BaseLogic
{
    /**
     * @notes 机器人发布列表
     * @param array $get
     * @param int $userId
     * @return array
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public static function lists(array $get, int $userId): array
    {
        // 接收参数
        $pageNo   = intval($get['page_no']   ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);
        $robotId  = intval($get['robot_id']  ?? 0);
        $type     = intval($get['type']      ?? 0);

        // 查询条件
        $where[] = ['user_id', '=', $userId];
        $where[] = ['type', '=', $type];
        $where[] = ['robot_id', '=', $robotId];

        // 查询发布
        $modelKbRobotPublish = new KbRobotPublish();
        $lists = $modelKbRobotPublish
            ->field([
                'id,chat_type,name,apikey,secret,use_count,use_time,create_time',
                'context_num,limit_total_chat,limit_today_chat,limit_exceed'
            ])
            ->where($where)
            ->order(['id'=>'desc'])
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        # 查询机器人
        $shareBg = (new KbRobot())->where(['id'=>$robotId])->value('share_bg');

        foreach ($lists['data'] as &$item) {
            $item['use_time'] = $item['use_time'] ? date('Y-m-d H:i:s', $item['use_time']) : '-';
            $item['share_bg'] = FileService::getFileUrl($shareBg);
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'],
            'lists'     => $lists['data']
        ] ?? [];
    }

    /**
     * @notes 机器人分享详情
     * @param string $apiKey
     * @param int $terminal
     * @return array
     * @author kb
     */
    public static function detail(string $apiKey, int $terminal): array
    {
        try {
            $modelKbRobotPublish = new KbRobotPublish();
            $publish = $modelKbRobotPublish
                ->where(['apikey'=>$apiKey])
                ->findOrEmpty()
                ->toArray();

            if (!$publish) {
                throw new Exception('找不到相关分享渠道!');
            }

            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot->where(['id'=>$publish['robot_id']])->findOrEmpty()->toArray();
            if (!$kbRobot || !$kbRobot['is_enable']) {
                $error = !$kbRobot ? '机器人不存在了!' : '机器人已被禁用了!';
                throw new Exception($error);
            }

            $digital = [];
            if ($kbRobot['digital_id'] && $kbRobot['is_digital']) {
                $digital = (new KbDigital())
                    ->withoutField('user_id,channel,dubbing,delete_time')
                    ->where(['id'=>$kbRobot['digital_id']])
                    ->findOrEmpty()
                    ->toArray();
            }

            $modelKbRobotInstruct = new KbRobotInstruct();
            $menus = $modelKbRobotInstruct
                ->field(['keyword'])
                ->where(['robot_id'=>$publish['robot_id']])
                ->order('id asc')
                ->column('keyword');

            KbRobotLogic::addVisit($terminal, $kbRobot['id']);
            return [
                'id'        => $publish['id'],
                'name'      => $publish['name'],
                'pwd'       => (bool) $publish['secret'],
                'chat_type' => $publish['chat_type'],
                'menus'     => $menus,
                'digital'   => $digital,
                'robot' => [
                    'id'                 => $kbRobot['id'],
                    'icons'              => FileService::getFileUrl($kbRobot['icons']),
                    'image'              => FileService::getFileUrl($kbRobot['image']),
                    'name'               => $kbRobot['name'],
                    'intro'              => $kbRobot['intro'],
                    'copyright'          => $kbRobot['copyright'],
                    'welcome_introducer' => $kbRobot['welcome_introducer'],
                    'is_show_feedback'   => $kbRobot['is_show_feedback']??0,
                    'is_show_context'    => $kbRobot['is_show_context'],
                    'is_show_quote'      => $kbRobot['is_show_quote'],
                    'is_digital'         => $kbRobot['is_digital'],
                    'digital_bg'         => $kbRobot['digital_bg'],
                    'create_time'        => $kbRobot['create_time'],
                    'support_file'       => $kbRobot['support_file'] ?? 0,
                ]
            ]??[];
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }

    /**
     * @notes 机器人发布新增
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function add(array $post, int $userId): bool
    {
        $type       = intval($post['type']);
        $robotId    = intval($post['robot_id']);
        $name       = trim($post['name']);
        $password   = $post['password']??'';
        $contextNum = $post['context_num']??3;
        $chatType   = $post['chat_type']??1;

        $supply = generate_random_str(20);
        $suffix = generate_random_str(5);
        $apikey = RobotEnum::getSecretPrefix($type) . '-' . md5($userId.$robotId.$password.time().$supply).$suffix;
        try {
            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot->field(['id,user_id,is_enable'])->where(['id'=>$robotId])->findOrEmpty()->toArray();
            if (!$kbRobot || $kbRobot['user_id'] != $userId) {
                $error = !$kbRobot ? '机器人应用不存在了!' : '您不是机器人的拥有者!';
                throw new Exception($error);
            }

            if (!$kbRobot['is_enable']) {
                $error = '机器人已被禁用,不可操作!';
                throw new Exception($error);
            }

            KbRobotPublish::create([
                'type'        => $type,
                'user_id'     => $userId,
                'robot_id'    => $robotId,
                'name'        => $name,
                'apikey'      => $apikey,
                'secret'      => $password,
                'context_num' => $contextNum,
                'chat_type'   => $chatType
            ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 机器人发布编辑
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function edit(array $post, int $userId): bool
    {
        try {
            $id             = intval($post['id']);
            $limitTotalChat = intval($post['limit_total_chat']??0);
            $limitTodayChat = intval($post['limit_today_chat']??0);
            $limitExceed    = $post['limit_exceed']??'';
            $chatType       = $post['chat_type']??0;

            $modelKbRobotPublish = new KbRobotPublish();
            $publish = $modelKbRobotPublish->where(['id'=>$id, 'user_id'=>$userId])->findOrEmpty()->toArray();
            if (!$publish) {
                throw new Exception('发布记录不存在!');
            }

            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot->field(['id,user_id,is_enable'])->where(['id'=>$publish['robot_id']])->findOrEmpty()->toArray();
            if (!$kbRobot || $kbRobot['user_id'] != $userId) {
                $error = !$kbRobot ? '机器人应用不存在!' : '您不是机器人的拥有者!';
                throw new Exception($error);
            }

            if (!$kbRobot['is_enable']) {
                $error = '机器人已被禁用,不可操作!';
                throw new Exception($error);
            }

            KbRobotPublish::update([
                'limit_total_chat' => $limitTotalChat,
                'limit_today_chat' => $limitTodayChat,
                'limit_exceed'     => $limitExceed,
                'chat_type'        => $chatType ?: $publish['chat_type'],
                'update_time'      => time()
            ], ['id'=>$id]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新信息
     * @param array $post
     * @param int $userId
     * @return bool
     */
    public static function update(array $post, int $userId): bool
    {
        try {
            $id     = intval($post['id']);
            $name   = $post['name']??'';
            $secret = $post['password']??'';

            $modelKbRobotPublish = new KbRobotPublish();
            $publish = $modelKbRobotPublish->where(['id'=>$id, 'user_id'=>$userId])->findOrEmpty()->toArray();
            if (!$publish) {
                throw new Exception('发布记录不存在了!');
            }

            $modelKbRobot = new KbRobot();
            $kbRobot = $modelKbRobot->field(['id,user_id,is_enable'])->where(['id'=>$publish['robot_id']])->findOrEmpty()->toArray();
            if (!$kbRobot || $kbRobot['user_id'] != $userId) {
                $error = !$kbRobot ? '机器人应用不存在了!' : '您不是机器人的拥有者!';
                throw new Exception($error);
            }

            if (!$kbRobot['is_enable']) {
                $error = '机器人已被禁用,不可操作!';
                throw new Exception($error);
            }

            KbRobotPublish::update([
                'name' => $name,
                'secret' => $secret,
                'update_time' => time()
            ], ['id'=>$id]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 机器人发布删除
     * @param int $id
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function del(int $id, int $userId): bool
    {
        try {
            $modelKbRobotPublish = new KbRobotPublish();
            $publish = $modelKbRobotPublish
                ->field(['id,user_id,robot_id'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$publish || $publish['user_id'] !== $userId) {
                $error = !$publish ? '发布记录不存在了!' : '您没有权限操作!';
                throw new Exception($error);
            }

            $modelKbRobot = new KbRobot();
            $robot = $modelKbRobot->field(['id,is_enable'])->where(['id'=>$publish['robot_id']])->findOrEmpty()->toArray();
            if (!$robot || !$robot['is_enable']) {
                $error = !$robot ? '机器人已不存在!' : '机器人被禁用了,不可操作!';
                throw new Exception($error);
            }

            KbRobotPublish::destroy($id);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 设置分享背景图
     * @param int $id
     * @param int $userId
     * @param string $url
     * @return bool
     */
    public static function setBg(int $id, int $userId, string $url): bool
    {
        try {
            $modelKbRobotPublish = new KbRobotPublish();
            $publish = $modelKbRobotPublish
                ->field(['id,user_id,robot_id'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$publish || $publish['user_id'] !== $userId) {
                $error = !$publish ? '发布记录不存在了!' : '您没有权限操作!';
                throw new Exception($error);
            }

            KbRobot::update([
                'share_bg' => FileService::setFileUrl($url)
            ], ['id'=>$publish['id']]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}