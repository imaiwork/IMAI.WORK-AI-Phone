<?php

declare(strict_types=1);

namespace app\common\service\digitalHuman;

use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanAnchor;
use app\common\model\human\HumanVideoTask;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\service\UserDisplaySanitizer;
use think\facade\Log;

/**
 * 公共形象与子任务归属绑定。
 * 子表按 dh_id 查找时必须带公共行 user_id，避免旧行被新用户占用后串写。
 */
class PublicAnchorBindService
{
    public static function ownedPublic(int $dhId, int $userId): ?DigitalHumanAnchor
    {
        if ($dhId <= 0 || $userId <= 0) {
            return null;
        }
        $dh = DigitalHumanAnchor::where('id', $dhId)->find();
        if (empty($dh) || (int)$dh->user_id !== $userId) {
            return null;
        }
        return $dh;
    }

    public static function ownChanjing(int $dhId, int $userId): ?HumanAnchor
    {
        if ($dhId <= 0 || $userId <= 0) {
            return null;
        }
        $row = HumanAnchor::where('model_version', 7)
            ->where('dh_id', $dhId)
            ->where('user_id', $userId)
            ->order('id', 'desc')
            ->find();
        return $row ?: null;
    }

    public static function ownWeiju(int $dhId, int $userId): ?HumanAnchor
    {
        if ($dhId <= 0 || $userId <= 0) {
            return null;
        }
        $row = HumanAnchor::where('model_version', 1)
            ->where('dh_id', $dhId)
            ->where('user_id', $userId)
            ->order('id', 'desc')
            ->find();
        return $row ?: null;
    }

    public static function ownShanjian(int $dhId, int $userId, int $cloneType): ?ShanjianAnchor
    {
        if ($dhId <= 0 || $userId <= 0) {
            return null;
        }
        $row = ShanjianAnchor::where('dh_id', $dhId)
            ->where('user_id', $userId)
            ->where('clone_type', $cloneType)
            ->order('id', 'desc')
            ->find();
        return $row ?: null;
    }

    /**
     * 提交闪剪前处理本用户同 clone_type 记录：进行中/成功则复用，失败则软删以便重提。
     */
    public static function takeOwnShanjianForSubmit(int $dhId, int $userId, int $cloneType): ?ShanjianAnchor
    {
        $exists = self::ownShanjian($dhId, $userId, $cloneType);
        if (empty($exists)) {
            return null;
        }
        $st = (int)$exists->status;
        if (in_array($st, [2, 5], true)) {
            $oldId = (int)$exists->id;
            $exists->delete();
            Log::channel('shanjiannotice')->write(
                '失败闪剪记录已清理，准备重提：dh_id=' . $dhId
                . ' user_id=' . $userId
                . ' clone_type=' . $cloneType
                . ' old_id=' . $oldId
            );
            return null;
        }
        return $exists;
    }

    /** 授权视频成功且闪剪已提交（或本用户已有未失败闪剪）后，4 → 1 */
    public static function markPartialAfterShanjianSubmit(int $dhId, int $userId): void
    {
        $dh = self::ownedPublic($dhId, $userId);
        if (empty($dh) || (int)$dh->status !== 4) {
            return;
        }
        $dh->status = 1;
        $dh->save();
        Log::channel('digital')->write('公共形象 4→1：dh_id=' . $dhId . ' user_id=' . $userId);
    }

    public static function publicStatusText(int $status): string
    {
        return match ($status) {
            0 => '生成中',
            1 => '部分完成',
            2 => '已完成',
            3 => '生成失败',
            4 => 'AI视频合成中',
            default => '未知',
        };
    }

    /** status=4 超时收口：写清卡在哪一步（对外只用模型别名，不出现渠道名） */
    public static function diagnoseStatus4Stuck(array $item): string
    {
        $dhId   = (int)($item['id'] ?? 0);
        $userId = (int)($item['user_id'] ?? 0);
        $cjName = self::modelAlias(7);
        $sjName = self::modelAlias(8);
        $chanjing = self::ownChanjing($dhId, $userId);
        if (empty($chanjing)) {
            return self::stuckRemark('卡在' . $cjName . '形象未回调');
        }
        $cjStatus = (int)$chanjing->status;
        if ($cjStatus === 0) {
            return self::stuckRemark('卡在' . $cjName . '形象未回调');
        }
        if ($cjStatus === 2) {
            return self::stuckRemark('卡在' . $cjName . '形象失败', (string)($chanjing->remark ?? ''));
        }

        $video = HumanVideoTask::where('task_id', $chanjing->task_id)
            ->where('user_id', $userId)
            ->where('is_ai', 1)
            ->order('id', 'desc')
            ->find();
        if (empty($video)) {
            return self::stuckRemark('卡在AI授权视频未完成');
        }
        $vStatus = (int)$video->status;
        if ($vStatus === 0) {
            return self::stuckRemark('卡在AI授权视频未完成');
        }
        if ($vStatus === 2) {
            return self::stuckRemark('卡在AI授权视频合成失败', (string)($video->remark ?? ''));
        }

        $shanjian = self::ownShanjian($dhId, $userId, ShanjianAnchorLogic::CLONE_TYPE_FAST);
        if (empty($shanjian)) {
            return self::stuckRemark('卡在' . $sjName . '未提交');
        }
        $sStatus = (int)$shanjian->status;
        if (in_array($sStatus, [2, 5], true)) {
            return self::stuckRemark('卡在' . $sjName . '失败', (string)($shanjian->remark ?? ''));
        }
        if (in_array($sStatus, [1, 3, 4], true)) {
            return self::stuckRemark('卡在' . $sjName . '训练未完成');
        }
        if ($sStatus === 6) {
            return self::stuckRemark('卡在公共形象未收口（' . $sjName . '已完成）');
        }
        return self::stuckRemark('卡在AI视频合成未完成');
    }

    /** model_version 7 / 8 取 models.name（站长配置的展示名） */
    private static function modelAlias(int $modelVersion): string
    {
        $name = UserDisplaySanitizer::digitalHumanModelNameByVersion($modelVersion);
        return $name !== '' ? $name : '数字人';
    }

    private static function stuckRemark(string $message, string $reason = ''): string
    {
        $reason = trim($reason);
        $text   = $reason !== '' ? ($message . '：' . $reason) : $message;
        return UserDisplaySanitizer::replaceProviderNames($text);
    }

    public static function logSkipUnowned(int $dhId, int $childUserId, string $from): void
    {
        Log::channel('digital')->write(
            '跳过回写公共形象: dh_id=' . $dhId . ' child_uid=' . $childUserId . ' from=' . $from
        );
    }
}
