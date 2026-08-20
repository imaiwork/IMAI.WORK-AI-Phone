import { useUserStore } from "@/stores/user";
import { useTeamInfo } from "./useTeamInfo";
import { useTeamMembers } from "./useTeamMembers";
import { useTeamConsumption } from "./useTeamConsumption";
import { useTeamBrand } from "./useTeamBrand";

/**
 * 团队控制台编排层:组合 info / members / consumption / brand 四个领域 hook,
 * 并提供统一 refresh(重载团队信息 + 成员)。由页面创建并 provide,子组件 inject。
 */
export function useTeamConsole() {
    const userStore = useUserStore();
    // refresh 为函数声明(提升),可在下面各 hook 初始化时作为回调传入,实际调用发生在事件触发时
    async function refresh() {
        await info.loadInfo();
        if (info.info.value?.in_team === 1 && info.isManager.value) {
            await members.loadMemberOptions();
            members.getMemberList();
        }
        const snap = info.info.value;
        // 先带快照通知头部(退出后立刻回到「企业管理」),再刷算力
        userStore.notifyTeamChanged({
            in_team: Number(snap?.in_team) === 1 ? 1 : 0,
            name: snap?.name || "",
        });
        await userStore.refreshTokens();
    }

    const info = useTeamInfo(refresh);
    const members = useTeamMembers({
        getInfo: () => info.info.value,
        isOwner: info.isOwner,
        isAdmin: info.isAdmin,
        refresh,
    });
    const consumption = useTeamConsumption();
    const brand = useTeamBrand({ refresh });

    return { info, members, consumption, brand, refresh };
}
