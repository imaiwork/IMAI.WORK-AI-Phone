<?php

namespace app\common\model\sv;

use app\common\model\aiPersona\AiPersona;

/**
 * 无手机模式的虚拟设备
 *
 * 合成链路（AutoVideoSynthesis → CopywritingAiGenerationLogic 等）一直以设备行为驱动实体，
 * 设备号还充当任务分区键、配额统计键与去重缓存键。无手机模式的人设没有设备行，
 * 这里用一个只存在于内存、与 SvDevice 同形的实例顶上：设备号固定为 cloud:{persona_id}，
 * 永远不写入 sv_device 表；日锁与重试计数落在人设表 cloud_synthesis_date /
 * cloud_retry_count / cloud_retry_date 三列。合成任务表里 device_code 会带上 cloud: 前缀，
 * 设备侧的发布建单按设备表 join 天然取不到这些任务，由无手机模式自己的发布链路接管。
 */
class CloudSvDevice extends SvDevice
{
    /**
     * SvDevice 没有固定表名，ThinkPHP 会按类名推导，子类不写就会指向不存在的 cloud_sv_device。
     * 本类只在内存里用，但任何误用的查询都必须落到真实表上而不是报"表不存在"。
     */
    protected $name = 'sv_device';

    const CODE_PREFIX = 'cloud:';

    public static function codeFor(int $personaId): string
    {
        return self::CODE_PREFIX . $personaId;
    }

    public static function isCloudCode($code): bool
    {
        return is_string($code) && strncmp($code, self::CODE_PREFIX, strlen(self::CODE_PREFIX)) === 0;
    }

    public static function personaIdFromCode(string $code): int
    {
        return self::isCloudCode($code) ? (int)substr($code, strlen(self::CODE_PREFIX)) : 0;
    }

    /**
     * 统一入口：真设备号查 sv_device，cloud: 前缀按人设造虚拟设备。
     * 人设不存在或已不是无手机模式时返回空模型，调用方按 isEmpty() 处理。
     */
    public static function resolve($deviceCode): SvDevice
    {
        $code = (string)$deviceCode;
        if (!self::isCloudCode($code)) {
            return SvDevice::where('device_code', $code)->findOrEmpty();
        }
        $persona = AiPersona::where('id', self::personaIdFromCode($code))->findOrEmpty();
        if ($persona->isEmpty() || (int)$persona->run_mode !== AiPersona::RUN_MODE_CLOUD) {
            return new SvDevice();
        }
        return self::fromPersona($persona);
    }

    public static function fromPersona(AiPersona $persona): self
    {
        $doneDate = (string)($persona->cloud_synthesis_date ?? '');
        return new self([
            'id' => 0,
            'device_code' => self::codeFor((int)$persona->id),
            'user_id' => (int)$persona->user_id,
            'persona_id' => (int)$persona->id,
            'auto_type' => 1,
            'is_first' => 0,
            'status' => 0,
            'synthesis_m' => $doneDate === date('Y-m-d') ? 1 : 0,
            'synthesis_m_date' => $doneDate !== '' ? $doneDate : null,
            'synthesis_m_retry_count' => (int)($persona->cloud_retry_count ?? 0),
            'retry_date' => $persona->cloud_retry_date ?? null,
            'synthesis_w' => 1,
            'persona_type' => (int)$persona->persona_type,
            'publish_mode' => (int)$persona->publish_mode,
        ]);
    }

    /**
     * 合成逻辑里 markSynthesisDone() 之后会 save()：真设备落 sv_device，
     * 虚拟设备改落人设表的完成日期，绝不往设备表插行。
     */
    public function save(array | object $data = [], ?string $sequence = null): bool
    {
        if (!empty($data)) {
            $this->setAttrs(is_object($data) ? get_object_vars($data) : $data);
        }
        if ((int)$this->getAttr('synthesis_m') === 1) {
            self::markPersonaDone((int)$this->getAttr('persona_id'));
        }
        return true;
    }

    /** 标记人设当天云端出片完成 */
    public static function markPersonaDone(int $personaId): void
    {
        AiPersona::where('id', $personaId)->update([
            'cloud_synthesis_date' => date('Y-m-d'),
            'update_time' => time(),
        ]);
    }

    /**
     * 无手机人设「当天未完成」条件（IS NULL 或早于今天）。
     * 没有布尔锁可以退回，所以不受 VIDEO_SYNTHESIS_DATE_MODE 开关影响。
     */
    public static function pendingDateSql(string $alias = ''): string
    {
        $column = ($alias !== '' ? $alias . '.' : '') . 'cloud_synthesis_date';
        return "({$column} IS NULL OR {$column} < CURDATE())";
    }
}
