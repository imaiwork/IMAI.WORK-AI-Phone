import { isCloudRunMode, PersonaIssueEnum } from "@/ai_modules/person/enums";
import type { PersonaSetupSummary } from "./useSetupChecklist";

export interface PersonaIssue {
    key: PersonaIssueEnum;
    /** 写清后果，别只说「异常」 */
    text: string;
    actionText: string;
}

const toCount = (value: unknown): number => Number(value) || 0;

/**
 * 必须项齐过之后仍可能掉链子：设备离线、音色被删、当天合成失败。
 * 这些都会让人设静默停摆，所以在首屏顶一条告警带，而不是等用户自己翻 tab 发现。
 */
export const usePersonaIssues = (summary: Ref<PersonaSetupSummary | null | undefined>) => {
    const issues = computed<PersonaIssue[]>(() => {
        const data = summary.value;
        if (!data) return [];

        const list: PersonaIssue[] = [];

        // 会员到期会把人设整条冻结（status=0），两条 24h 合成 cron 都只捞启用中的人设。
        // 不亮这条，界面上就只剩「等待下一轮 / 今日已生成 0」，用户查不出为什么整天没出片。
        if (data.persona_enabled === false) {
            return [
                {
                    key: PersonaIssueEnum.PERSONA_DISABLED,
                    text: "人设已冻结，自动生成与发布都已停止",
                    actionText: "去恢复",
                },
            ];
        }

        const isCloud = isCloudRunMode(data.run_mode);
        const deviceCount = toCount(data.device_count);
        const offlineCount = toCount(data.device_offline_count);
        const failedCount = toCount(data.today_failed);

        if (!isCloud && deviceCount === 0) {
            list.push({
                key: PersonaIssueEnum.NO_DEVICE,
                text: "还没绑发布设备，成片做好也发不出去",
                actionText: "去绑定",
            });
        } else if (!isCloud && offlineCount > 0) {
            list.push({
                key: PersonaIssueEnum.DEVICE_OFFLINE,
                text: `${offlineCount} 台设备离线，今天的内容可能发不出去`,
                actionText: "去处理",
            });
        }

        if (toCount(data.voice_count) === 0) {
            list.push({
                key: PersonaIssueEnum.VOICE_MISSING,
                text: "音色不可用，新生成的视频会没有人声",
                actionText: "去设置",
            });
        }

        if (toCount(data.avatar_count) === 0) {
            list.push({
                key: PersonaIssueEnum.AVATAR_MISSING,
                text: "数字人形象不可用，合成会被跳过",
                actionText: "去设置",
            });
        }

        if (failedCount > 0) {
            list.push({
                key: PersonaIssueEnum.GENERATE_FAILED,
                text: `今天有 ${failedCount} 条生成失败`,
                actionText: "去看看",
            });
        }

        return list;
    });

    const topIssue = computed<PersonaIssue | undefined>(() => issues.value[0]);
    const hasIssue = computed(() => issues.value.length > 0);

    return { hasIssue, issues, topIssue };
};
