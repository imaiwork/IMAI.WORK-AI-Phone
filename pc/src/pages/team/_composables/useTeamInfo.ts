import feedback from "@/utils/feedback";
import {
    getTeamInfo,
    createTeam,
    joinTeam,
    leaveTeam,
    disbandTeam,
    setTeamName,
    requestFeature,
    switchTeam,
} from "@/api/team";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { FEATURE_APPS } from "../_enums";
import { errText } from "./helpers";
/**
 * 团队基础信息 + 角色 + OEM 状态 + 授权功能 + 权益/入团出团等动作。
 * @param refresh 统一刷新(重载 info + 成员),由编排层注入
 */
export function useTeamInfo(refresh: () => Promise<void>) {
    const userStore = useUserStore();
    const isLogin = computed(() => userStore.isLogin);

    const info = ref<any>(null);

    // 角色
    const isOwner = computed(() => info.value?.team_role === 2);
    const isAdmin = computed(() => info.value?.team_role === 3);
    const isManager = computed(() => isOwner.value || isAdmin.value);
    const ownerName = computed(
        () => info.value?.owner_nickname || (isOwner.value ? (userStore as any).userInfo?.nickname || "我" : "-"),
    );

    // OEM 状态:0=免费版 1=待审核 2=已开通
    const oemStatus = computed(() => Number(info.value?.oem_status ?? 0));
    const oemActive = computed(() => oemStatus.value === 2);
    const planName = computed(() =>
        oemStatus.value === 2 ? "企业OEM" : oemStatus.value === 1 ? "免费版(升级审核中)" : "免费版",
    );

    const seatPct = computed(() => {
        const limit = Number(info.value?.seat_limit) || 0;
        if (!limit) return 0;
        return Math.min(100, Math.round(((Number(info.value?.member_count) || 0) / limit) * 100));
    });

    // 授权功能:info.features 未返回时视为全部启用
    const isFeatureEnabled = (key: string) => {
        const list = info.value?.features;
        if (!Array.isArray(list)) return true;
        return list.includes(key);
    };
    const isFeatureRequested = (key: string) => (info.value?.feature_requests || []).includes(key);
    const enabledCount = computed(() => FEATURE_APPS.filter((a) => isFeatureEnabled(a.key)).length);

    // 套餐权益弹窗
    const showBenefits = ref(false);
    const benefitGroups = computed(() => [
        {
            title: "基础 AI 能力",
            rows: [
                { name: "大模型对话 / AI智能体", personal: true, oem: true },
                { name: "AI作图 / AI PPT", personal: true, oem: true },
                { name: "数字人 / 数字人混剪", personal: true, oem: true },
                { name: "获客工具(高德 / 视频号 / AI手机)", personal: true, oem: true },
            ],
        },
        {
            title: "企业品牌",
            rows: [
                { name: "独立站点域名", personal: false, oem: true },
                { name: "品牌 LOGO / 站点标题自定义", personal: false, oem: true },
                { name: "自有小程序(配置 / 发版 / 审核开关)", personal: false, oem: true },
                { name: "访客归属(自有域名注册用户归属企业)", personal: false, oem: true },
            ],
        },
        {
            title: "组织管理",
            rows: [
                { name: "成员席位", personal: false, oem: `${info.value?.seat_limit ?? "-"} 个` },
                { name: "邀请码入团 / 移除成员", personal: false, oem: true },
                { name: "成员到期管控(到期停用)", personal: false, oem: true },
                { name: "成员算力消耗明细", personal: false, oem: true },
            ],
        },
        {
            title: "经营能力",
            rows: [
                { name: "算力划拨给成员", personal: false, oem: true },
                { name: "自有卡密套餐 / 制卡", personal: false, oem: true },
                { name: "授权功能应用", personal: "按算力使用", oem: `${enabledCount.value} 项已启用` },
            ],
        },
    ]);

    // 入团/建团表单
    const teamName = ref("");
    const joinCode = ref("");

    /** 成员/管理员资格过期：切回个人空间并离开控制台(防地址栏/历史回退) */
    let kickingExpired = false;
    const kickIfExpiredMembership = async () => {
        if (Number(info.value?.expired) !== 1) return false;
        if (kickingExpired) return true;
        kickingExpired = true;
        feedback.msgWarning("你在该企业的成员资格已过期，请联系管理员续期");
        info.value = { in_team: 0, team_role: 0 };
        userStore.notifyTeamChanged({ in_team: 0, name: "" });
        try {
            await switchTeam({ team_id: 0 });
        } catch {
            // 已在个人空间时忽略
        }
        try {
            await userStore.getUser();
            await useAppStore().getConfig();
        } catch {
            // 刷新失败仍继续离开页面
        }
        if (import.meta.client) {
            const path = useRoute().path;
            if (path === "/team" || path.startsWith("/team/")) {
                await navigateTo("/", { replace: true });
            }
            location.reload();
        }
        return true;
    };

    const loadInfo = async () => {
        if (!isLogin.value) return;
        try {
            info.value = await getTeamInfo();
            await kickIfExpiredMembership();
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    const onCreate = async () => {
        if (!teamName.value.trim()) return feedback.msgWarning("请输入团队名称");
        try {
            await createTeam({ name: teamName.value.trim() });
            feedback.msgSuccess("开通成功");
            teamName.value = "";
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    const onJoin = async () => {
        if (!joinCode.value.trim()) return feedback.msgWarning("请输入邀请码");
        try {
            await joinTeam({ code: joinCode.value.trim() });
            feedback.msgSuccess("加入成功");
            joinCode.value = "";
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    // 修改企业名称:专用弹窗(dialog-rename.vue)
    const showRename = ref(false);
    const renameValue = ref("");
    const renameSubmitting = ref(false);
    const onRename = () => {
        renameValue.value = String(info.value?.name || "");
        showRename.value = true;
    };
    const confirmRename = async () => {
        const name = renameValue.value.trim();
        if (!name || name.length > 100) {
            feedback.msgWarning("名称为 1-100 个字符");
            return;
        }
        if (renameSubmitting.value) return;
        renameSubmitting.value = true;
        try {
            await setTeamName({ name });
            showRename.value = false;
            feedback.msgSuccess("修改成功");
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        } finally {
            renameSubmitting.value = false;
        }
    };

    /** 退出/解散后强制回到个人空间并刷新全站配置(与头部切换个人空间一致) */
    const switchToPersonalSpace = async (toast: string) => {
        info.value = { in_team: 0, team_role: 0 };
        userStore.notifyTeamChanged({ in_team: 0, name: "" });
        try {
            await switchTeam({ team_id: 0 });
        } catch {
            // 后端 leave/disband 已清 team_id 时可能幂等失败,忽略
        }
        try {
            await userStore.getUser();
            await useAppStore().getConfig();
        } catch {
            // 刷新失败仍继续 reload
        }
        feedback.msgSuccess(toast);
        // 清企业空间残留(品牌/会员模型等),与手动切换个人空间保持一致
        location.reload();
    };

    // 退出团队:专用确认弹窗(dialog-leave.vue)
    const showLeave = ref(false);
    const leaveSubmitting = ref(false);
    const onLeaveTeam = () => {
        showLeave.value = true;
    };
    const confirmLeave = async () => {
        if (leaveSubmitting.value) return;
        leaveSubmitting.value = true;
        try {
            await leaveTeam();
            showLeave.value = false;
            await switchToPersonalSpace("已退出团队，已切换到个人空间");
        } catch (e) {
            feedback.msgError(errText(e));
            leaveSubmitting.value = false;
        }
    };

    // 解散企业:专用确认弹窗(dialog-disband.vue)
    const showDisband = ref(false);
    const disbanding = ref(false);
    const onDisband = () => {
        showDisband.value = true;
    };
    const confirmDisband = async () => {
        if (disbanding.value) return;
        disbanding.value = true;
        try {
            await disbandTeam();
            showDisband.value = false;
            await switchToPersonalSpace("企业已解散，已切换到个人空间");
        } catch (e) {
            feedback.msgError(errText(e));
            disbanding.value = false;
        }
    };

    // 请求开通功能:专用弹窗(dialog-feature.vue)
    const showFeature = ref(false);
    const featureApp = ref<{ key: string; label: string } | null>(null);
    const featureSubmitting = ref(false);
    const onRequestFeature = (app: { key: string; label: string }) => {
        featureApp.value = app;
        showFeature.value = true;
    };
    const confirmRequestFeature = async () => {
        const app = featureApp.value;
        if (!app || featureSubmitting.value) return;
        featureSubmitting.value = true;
        try {
            await requestFeature({ key: app.key });
            showFeature.value = false;
            feedback.msgSuccess("已提交开通申请");
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        } finally {
            featureSubmitting.value = false;
        }
    };

    return {
        isLogin,
        info,
        isOwner,
        isAdmin,
        isManager,
        ownerName,
        oemStatus,
        oemActive,
        planName,
        seatPct,
        isFeatureEnabled,
        isFeatureRequested,
        enabledCount,
        showBenefits,
        benefitGroups,
        teamName,
        joinCode,
        loadInfo,
        onCreate,
        onJoin,
        onRename,
        showRename,
        renameValue,
        renameSubmitting,
        confirmRename,
        onLeaveTeam,
        showLeave,
        leaveSubmitting,
        confirmLeave,
        onDisband,
        showDisband,
        disbanding,
        confirmDisband,
        onRequestFeature,
        showFeature,
        featureApp,
        featureSubmitting,
        confirmRequestFeature,
    };
}
