<?php

namespace app\adminapi\logic\deviceauth;

use app\common\enum\deviceauth\DeviceAuthBatchEnum;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\logic\BaseLogic;
use app\common\model\deviceauth\DeviceAuthBatch;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\deviceauth\DeviceAuthCodeSyncService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

class DeviceAuthCodeLogic extends BaseLogic
{
    public function statistics(): array
    {
        $keys = [
            DeviceAuthCodeEnum::TYPE_FOREVER   => 'forever',
            DeviceAuthCodeEnum::TYPE_WEEK      => 'week',
            DeviceAuthCodeEnum::TYPE_MONTH     => 'month',
            DeviceAuthCodeEnum::TYPE_QUARTER   => 'quarter',
            DeviceAuthCodeEnum::TYPE_HALF_YEAR => 'half_year',
            DeviceAuthCodeEnum::TYPE_YEAR      => 'year',
            DeviceAuthCodeEnum::TYPE_CUSTOM    => 'custom',
        ];
        $result = ['all' => DeviceCdkCode::count()];
        foreach ($keys as $type => $key) {
            $result[$key] = DeviceCdkCode::where('type', $type)->count();
        }
        return $result;
    }

    public function generate(array $post, int $adminId)
    {
        try {
            Db::startTrans();
            $batch = DeviceAuthBatch::create([
                'sn'        => generate_sn(DeviceAuthBatch::class, 'sn', 'DA'),
                'type'      => $post['type'],
                'total_num' => $post['num'],
                'used_num'  => 0,
                'source'    => DeviceAuthBatchEnum::SOURCE_PLATFORM,
                'admin_id'  => $adminId,
                'rule_type' => $post['rule_type'],
                'remark'    => $post['remark'] ?? '',
            ]);
            $now = time();
            $codes = [];
            for ($i = 0; $i < $post['num']; $i++) {
                $codes[] = [
                    'batch_id'      => $batch->id,
                    'code'          => device_auth_sn((int)$post['rule_type']),
                    'type'          => $post['type'],
                    'status'        => DeviceAuthCodeEnum::STATUS_UNUSED,
                    'source'        => DeviceAuthCodeEnum::SOURCE_PLATFORM,
                    'owner_user_id' => 0,
                    'admin_id'      => $adminId,
                    'create_time'   => $now,
                    'update_time'   => $now,
                ];
            }
            (new DeviceCdkCode())->saveAll($codes);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    public function import($file, int $adminId, int $defaultType = 0)
    {
        $filePath = $file->getRealPath();
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $success = 0;
        $fail = 0;
        $failList = [];
        $insertCodes = [];
        $now = time();

        foreach ($rows as $k => $row) {
            if ($k == 1) {
                continue;
            }
            $code = trim((string)($row['A'] ?? ''));
            if ($code === '') {
                continue;
            }
            $type = (int)($row['B'] ?? 0);
            if ($type <= 0) {
                $type = $defaultType > 0 ? $defaultType : DeviceAuthCodeEnum::TYPE_MONTH;
            }
            if (!array_key_exists($type, DeviceAuthCodeEnum::getTypeDesc())) {
                $fail++;
                $failList[] = ['row' => $k, 'code' => $code, 'reason' => '设备CDK类型错误'];
                continue;
            }
            if (DeviceCdkCode::where('code', $code)->find()) {
                $fail++;
                $failList[] = ['row' => $k, 'code' => $code, 'reason' => '设备CDK已存在'];
                continue;
            }
            $insertCodes[] = [
                'batch_id'      => 0,
                'code'          => $code,
                'type'          => $type,
                'status'        => DeviceAuthCodeEnum::STATUS_UNUSED,
                'source'        => DeviceAuthCodeEnum::SOURCE_IMPORT,
                'owner_user_id' => 0,
                'admin_id'      => $adminId,
                'create_time'   => $now,
                'update_time'   => $now,
            ];
            $success++;
        }

        if (empty($insertCodes)) {
            return ['success' => 0, 'fail' => $fail, 'fail_list' => $failList];
        }

        Db::startTrans();
        try {
            $batch = DeviceAuthBatch::create([
                'sn'          => generate_sn(DeviceAuthBatch::class, 'sn', 'DI'),
                'type'        => $defaultType > 0 ? $defaultType : DeviceAuthCodeEnum::TYPE_MONTH,
                'total_num'   => count($insertCodes),
                'used_num'    => 0,
                'source'      => DeviceAuthBatchEnum::SOURCE_IMPORT,
                'admin_id'    => $adminId,
                'import_file' => '',
                'rule_type'   => DeviceAuthBatchEnum::RULE_TYPE_NUMBER,
            ]);
            foreach ($insertCodes as &$item) {
                $item['batch_id'] = $batch->id;
            }
            unset($item);
            (new DeviceCdkCode())->saveAll($insertCodes);
            Db::commit();
            return ['success' => $success, 'fail' => $fail, 'fail_list' => $failList];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function detail(int $id): array
    {
        $code = DeviceCdkCode::alias('c')
            ->leftJoin('user u', 'u.id = c.user_id')
            ->leftJoin('user ou', 'ou.id = c.owner_user_id')
            ->where('c.id', $id)
            ->field('c.*,u.nickname,u.mobile,ou.nickname as owner_nickname')
            ->findOrEmpty();
        if ($code->isEmpty()) {
            return [];
        }
        $data = $code->toArray();
        $data['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($data['type']);
        $data['status_desc'] = DeviceAuthCodeEnum::getStatusDesc($data['status']);
        $data['source_desc'] = DeviceAuthCodeEnum::getSourceDesc($data['source']);
        $data['use_time'] = format_datetime($data['use_time'] ?? '');
        $data['purchase_time'] = format_datetime($data['purchase_time'] ?? '');
        $data['create_time'] = format_datetime($data['create_time'] ?? '');
        return $data;
    }

    public function disable(int $id)
    {
        $code = DeviceCdkCode::findOrEmpty($id);
        if ($code->isEmpty()) {
            throw new \Exception('设备CDK不存在');
        }
        if ($code->status != DeviceAuthCodeEnum::STATUS_UNUSED) {
            throw new \Exception('仅未使用的设备CDK可作废');
        }
        $code->status = DeviceAuthCodeEnum::STATUS_DISABLED;
        $code->save();
    }

    public function del(int $id)
    {
        $code = DeviceCdkCode::findOrEmpty($id);
        if ($code->isEmpty()) {
            throw new \Exception('设备CDK不存在');
        }
        if ($code->status != DeviceAuthCodeEnum::STATUS_UNUSED) {
            throw new \Exception('仅未使用的设备CDK可删除');
        }
        DeviceCdkCode::destroy($id);
    }

    public function getConfig(): array
    {
        return [
            'is_open'     => ConfigService::get('device_auth', 'is_open', 1),
            'code_prefix' => ConfigService::get('device_auth', 'code_prefix', 'CARD'),
        ];
    }

    public function setConfig(array $post)
    {
        ConfigService::set('device_auth', 'is_open', $post['is_open']);
        if (isset($post['code_prefix']) && $post['code_prefix'] !== '') {
            ConfigService::set('device_auth', 'code_prefix', strtoupper(trim($post['code_prefix'])));
        }
    }

    /**
     * 购买前码池库存预检
     */
    public static function assertPoolStock(int $authType, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \Exception('购买数量无效');
        }
        $available = DeviceCdkCode::where([
            ['owner_user_id', '=', 0],
            ['status', '=', DeviceAuthCodeEnum::STATUS_UNUSED],
            ['type', '=', $authType],
        ])->count();
        if ($available < $quantity) {
            throw new \Exception('码池库存不足，请联系站长');
        }
    }

    /**
     * 从码池分配设备CDK给用户（购买回调复用）
     */
    public static function assignCodesFromPool(DeviceAuthOrder $order): void
    {
        $codes = DeviceCdkCode::where([
            ['owner_user_id', '=', 0],
            ['status', '=', DeviceAuthCodeEnum::STATUS_UNUSED],
            ['type', '=', $order->auth_type],
        ])->order('id asc')->limit($order->quantity)->lock(true)->select();

        if ($codes->count() < $order->quantity) {
            throw new \Exception('码池库存不足，请联系站长');
        }

        $now = time();
        DeviceAuthCodeSyncService::assignOnPlatform($codes, (int)$order->user_id, $now);

        foreach ($codes as $code) {
            $code->owner_user_id = $order->user_id;
            $code->purchase_time = $now;
            $code->order_id = $order->id;
            $code->save();
        }
    }

    /**
     * 站长端转移：将未使用CDK划给指定用户（不建订单、不扣算力；可跨用户重划）
     */
    public function transfer(array $params): void
    {
        Db::startTrans();
        try {
            $userId = (int)$params['user_id'];
            $user = User::where('id', $userId)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \Exception('用户不存在');
            }

            $code = DeviceCdkCode::where('id', (int)$params['id'])->lock(true)->findOrEmpty();
            if ($code->isEmpty()) {
                throw new \Exception('设备CDK不存在');
            }
            if ((int)$code->status !== DeviceAuthCodeEnum::STATUS_UNUSED) {
                throw new \Exception('仅未使用的设备CDK可转移');
            }
            if ((int)$code->owner_user_id === $userId) {
                Db::commit();
                return;
            }

            $now = time();
            DeviceAuthCodeSyncService::assignOnPlatform([$code], $userId, $now);

            $code->owner_user_id = $userId;
            $code->purchase_time = $now;
            $code->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
}
