<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvAccount;
use app\common\model\wechat\AiWechat;
use app\api\logic\sv\MessageLogic;
use think\facade\Db;
use app\common\service\FileService;
use app\common\model\kb\KbRobot;

/**
 * 列表
 * Class AllAccountLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class AllAccountLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * 暂时隐藏微信账号：sv_account 的 type=1（微信(视频号)）与 ai_wechat 个微合并段同属微信，
     * 一并关闭。恢复展示时改回 true 即可，两处判断同时生效。
     */
    private const SHOW_WECHAT_ACCOUNT = false;

    /** 微信(视频号)在 sv_account.type 里的取值 */
    private const TYPE_WECHAT = 1;

    private ?array $mergedAccounts = null;

    public function setSearch(): array
    {
        return [
            '=' => ['w.status', 's.takeover_mode', 'w.type', 'w.account', 'nickname', 'w.device_code'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        if ($this->userId <= 0) {
            return [];
        }
        return array_map(fn (array $item) => $this->formatAccount($item), $this->pagedAccounts());
    }

    /**
     * @notes 是否需要合并 ai_wechat 数据（该段按 user_id 过滤，未登录时并进来也是空集）
     */
    private function mergeWechat(): bool
    {
        return self::SHOW_WECHAT_ACCOUNT
            && $this->userId > 0
            && (int)$this->request->get('type', 0) === self::TYPE_WECHAT;
    }

    /**
     * @notes 当前页账号
     * 合并 ai_wechat 时必须先合并去重再分页：ai_wechat 段没有分页条件，逐页追加会让同一账号在第二页后重复出现
     */
    private function pagedAccounts(): array
    {
        if ($this->mergeWechat()) {
            return array_slice($this->getMergedAccounts(), $this->limitOffset, $this->limitLength);
        }

        return $this->uniqueByTypeAccount(
            $this->accountQuery()
                ->order('w.id', 'desc')
                ->limit($this->limitOffset, $this->limitLength)
                ->group('w.type, w.account')
                ->select()
                ->toArray()
        );
    }

    /**
     * @notes sv_account 与 ai_wechat 合并去重后的全量账号（未分页）
     */
    private function getMergedAccounts(): array
    {
        if ($this->mergedAccounts !== null) {
            return $this->mergedAccounts;
        }

        $svAccount = $this->accountQuery()
            ->order('w.id', 'desc')
            ->group('w.type, w.account')
            ->select()
            ->toArray();

        $account = array_column($svAccount, 'account');
        $wechatAccount = AiWechat::alias('w')
            ->field('w.user_id,w.id,w.device_code,w.wechat_id as account,w.wechat_nickname as nickname,w.wechat_avatar as avatar,w.wechat_status as status,w.create_time, s.model,
            s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type, s.robot_id, 1 as type, "wechat" as source')
            ->join('ai_wechat_setting s', 's.wechat_id = w.wechat_id')
            ->where('w.user_id', $this->userId)
            ->where('w.wechat_id', 'not in', $account)
            ->where('w.wechat_no', 'not in', $account)
            ->order(['s.sort' => 'desc', 'w.id' => 'desc'])
            ->group('w.wechat_id')
            ->select()
            ->toArray();

        return $this->mergedAccounts = $this->uniqueByTypeAccount(array_merge($svAccount, $wechatAccount));
    }

    /**
     * @notes 补全展示字段，sv 与 wechat 两个来源的补全口径不同
     */
    private function formatAccount(array $item): array
    {
        if (empty($item['takeover_mode'])) {
            $item['takeover_mode'] = 0;
        }

        if (empty($item['robot_id'])) {
            $item['robot_id'] = 0;
        }

        $item['robot_name'] = KbRobot::where('id', $item['robot_id'])->where('user_id', $this->userId)->value('name', '');

        if (($item['source'] ?? '') === 'wechat') {
            $item['wechat_status'] = 0;
            return $item;
        }

        $item['device_name'] = is_null($item['device_name']) ? $item['device_model'] : $item['device_name'];

        $extraArray = empty($item['extra']) ? [] : json_decode($item['extra'], true);
        foreach ((array)$extraArray as $key => $v) {
            $item[$key] = $v;
        }
        $item['avatar'] = FileService::getFileUrl($item['avatar']);

        return $item;
    }

    /**
     * 只返回当前用户名下的账号，设备和设置也按账号归属隔离。
     * setting / device 用左连接，避免快手等账号缺 sv_setting 时被内连接滤掉。
     */
    private function accountQuery()
    {
        $query = SvAccount::alias('w')
            ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.update_time,w.extra,w.type,
                    s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id, d.device_name,d.device_model, "sv" as source')
            ->leftJoin('sv_device d', 'd.device_code = w.device_code and d.user_id = w.user_id')
            ->leftJoin('sv_setting s', 's.account = w.account and s.user_id = w.user_id')
            ->where($this->searchWhere);

        if (!self::SHOW_WECHAT_ACCOUNT) {
            $query->where('w.type', '<>', self::TYPE_WECHAT);
        }

        if ($this->userId > 0) {
            $query->where('w.user_id', $this->userId);
        } else {
            $query->whereRaw('1=0');
        }

        return $query;
    }

    /**
     * @notes 同一 type 下按 account 去重，保留先出现的记录（已按 id desc，即最新）
     */
    private function uniqueByTypeAccount(array $list): array
    {
        $seen = [];
        $result = [];
        foreach ($list as $item) {
            $key = ($item['type'] ?? '') . '_' . ($item['account'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $item;
        }
        return $result;
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        if ($this->userId <= 0) {
            return 0;
        }
        if ($this->mergeWechat()) {
            return count($this->getMergedAccounts());
        }

        return $this->accountQuery()
            ->group('w.type, w.account')
            ->count();
    }
}
