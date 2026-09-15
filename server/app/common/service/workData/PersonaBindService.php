<?php

namespace app\common\service\workData;

use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\service\FileService;

class PersonaBindService
{
    /** @var (callable(int, int, string, ?string): array)|null */
    private static $testResolver = null;

    /** @var (callable(int, int, string): mixed)|null */
    private static $testLister = null;

    public static function setTestResolver(?callable $resolver): void
    {
        self::$testResolver = $resolver;
    }

    public static function setTestLister(?callable $lister): void
    {
        self::$testLister = $lister;
    }

    public static function clearTestHooks(): void
    {
        self::$testResolver = null;
        self::$testLister = null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listPersonas(int $userId): array
    {
        $rows = AiPersona::where('user_id', $userId)
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->select();

        $out = [];
        foreach ($rows as $row) {
            $out[] = self::formatPersona($row);
        }
        return $out;
    }

    /**
     * @return array{persona: AiPersona, account: ?string}
     */
    public static function resolve(int $userId, int $personaId, string $platform, ?string $account = null): array
    {
        if (self::$testResolver !== null) {
            $out = (self::$testResolver)($userId, $personaId, $platform, $account);
            if (!is_array($out)) {
                throw new WorkDataException('人设不存在');
            }
            if (isset($out['accounts']) && is_array($out['accounts'])) {
                $out['account'] = self::selectAccount(
                    self::normalizeBoundRows($out['accounts']),
                    $account,
                    $personaId,
                    $platform
                );
            }
            return $out;
        }

        $persona = AiPersona::where('id', $personaId)
            ->where('user_id', $userId)
            ->whereNull('delete_time')
            ->find();
        if (!$persona) {
            throw new WorkDataException('人设不存在');
        }
        return [
            'persona' => $persona,
            'account' => self::selectAccount(
                self::listBoundAccounts($userId, $personaId, $platform),
                $account,
                $personaId,
                $platform
            ),
        ];
    }

    public static function resolveAccountNo(int $userId, int $personaId, string $platform, ?string $account = null): ?string
    {
        return self::selectAccount(
            self::listBoundAccounts($userId, $personaId, $platform),
            $account,
            $personaId,
            $platform
        );
    }

    /**
     * @return list<array{id:int,account:string,nickname:string,avatar:string,device_code:string}>
     */
    public static function listBoundAccounts(int $userId, int $personaId, string $platform): array
    {
        if (self::$testLister !== null) {
            $out = (self::$testLister)($userId, $personaId, $platform);
            return self::normalizeBoundRows(is_iterable($out) ? $out : []);
        }

        $type = WorkDataSupport::platformType($platform);
        $deviceCodes = SvDevice::where('persona_id', $personaId)
            ->column('device_code');
        if ($deviceCodes === []) {
            return [];
        }
        $rows = SvAccount::where('user_id', $userId)
            ->where('type', $type)
            ->whereIn('device_code', $deviceCodes)
            ->where('account', '<>', '')
            ->field('id,account,nickname,avatar,device_code')
            ->order('id', 'desc')
            ->select();
        return self::normalizeBoundRows($rows);
    }

    /**
     * @param iterable<int, mixed> $rows
     * @return list<array{id:int,account:string,nickname:string,avatar:string,device_code:string}>
     */
    public static function normalizeBoundRows(iterable $rows): array
    {
        $best = [];
        foreach ($rows as $row) {
            $account = trim((string)self::rowValue($row, 'account'));
            if ($account === '') {
                continue;
            }
            $id = (int)self::rowValue($row, 'id');
            $key = strtolower($account);
            if (isset($best[$key]) && (int)$best[$key]['id'] >= $id) {
                continue;
            }
            $best[$key] = [
                'id' => $id,
                'account' => $account,
                'nickname' => (string)self::rowValue($row, 'nickname'),
                'avatar' => FileService::getFileUrl((string)self::rowValue($row, 'avatar')),
                'device_code' => (string)self::rowValue($row, 'device_code'),
            ];
        }
        $list = array_values($best);
        usort($list, static fn(array $a, array $b): int => $b['id'] <=> $a['id']);
        return $list;
    }

    /**
     * @param list<array{id:int,account:string,nickname:string,avatar:string,device_code:string}> $list
     */
    public static function selectAccount(array $list, ?string $wanted, int $personaId, string $platform): ?string
    {
        if ($list === []) {
            return null;
        }
        $wanted = trim((string)$wanted);
        if ($wanted !== '') {
            foreach ($list as $row) {
                $account = (string)($row['account'] ?? '');
                if ($account !== '' && strcasecmp($account, $wanted) === 0) {
                    return $account;
                }
            }
            throw new WorkDataException('该账号未绑定到此人设');
        }

        $first = (string)($list[0]['account'] ?? '');
        if (count($list) > 1) {
            WorkDataLog::write(sprintf(
                '人设绑定多账号，已取最近一条：人设=%d 平台=%s 账号=%s 条数=%d',
                $personaId,
                $platform,
                $first,
                count($list)
            ));
        }
        return $first !== '' ? $first : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatPersona(AiPersona $persona): array
    {
        $deviceNum = (int)SvDevice::where('persona_id', $persona->id)->count();
        $accounts = self::boundAccounts((int)$persona->user_id, (int)$persona->id);
        [$tag, $tagClass] = self::tagOf($persona);
        return [
            'id' => (int)$persona->id,
            'name' => (string)$persona->persona_name,
            'tag' => $tag,
            'tagClass' => $tagClass,
            'status' => self::statusOf($persona, $deviceNum),
            'avatar' => FileService::getFileUrl((string)($persona->avatar_url ?? '')),
            'devices' => $deviceNum > 0 ? $deviceNum . ' 台手机' : '',
            'accounts' => $accounts,
            'auto_sync_enabled' => (int)($persona->work_data_auto_sync ?? 0) === 1,
        ];
    }

    /**
     * @return array{douyin: list<array{id:int,account:string,nickname:string,avatar:string,device_code:string}>, xhs: list<array{id:int,account:string,nickname:string,avatar:string,device_code:string}>}
     */
    public static function boundAccounts(int $userId, int $personaId): array
    {
        return [
            WorkDataSupport::PLATFORM_DOUYIN => self::listBoundAccounts($userId, $personaId, WorkDataSupport::PLATFORM_DOUYIN),
            WorkDataSupport::PLATFORM_XHS => self::listBoundAccounts($userId, $personaId, WorkDataSupport::PLATFORM_XHS),
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    public static function tagOf(AiPersona $persona): array
    {
        if ((int)($persona->is_shopping_cart ?? 0) === 1) {
            return ['电商', 'ecom'];
        }
        return match ((int)$persona->persona_type) {
            2 => ['企业IP', 'corp'],
            3 => ['本地', 'local'],
            default => ['个人IP', 'personal'],
        };
    }

    public static function statusOf(AiPersona $persona, int $deviceNum): string
    {
        if ((int)$persona->status === 0) {
            return 'inactive';
        }
        if ($deviceNum > 0) {
            return 'linked';
        }
        return 'pending';
    }

    private static function rowValue(mixed $row, string $field): mixed
    {
        if (is_array($row)) {
            return $row[$field] ?? '';
        }
        if (is_object($row)) {
            return $row->{$field} ?? '';
        }
        return '';
    }
}
