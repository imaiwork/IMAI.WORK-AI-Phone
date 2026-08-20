import feedback from "@/utils/feedback";
import {
    getTeamMembers,
    getMemberOptions,
    inviteTeamMember,
    setMemberRole,
    setMemberExpire,
    setMemberTokens,
    allocateTokens,
    removeTeamMember,
} from "@/api/team";
import { usePaging } from "@/composables/usePaging";
import { ROLE_FILTERS, type RoleFilterKey } from "../_enums";
import { errText, formatNum } from "./helpers";

interface UseTeamMembersOptions {
    getInfo: () => any;
    isOwner: Ref<boolean>;
    isAdmin: Ref<boolean>;
    refresh: () => Promise<void>;
}

/** 成员列表(分页/搜索) + 成员管理动作(角色/到期/算力/邀请/划拨/移除) */
export function useTeamMembers({ getInfo, isOwner, isAdmin, refresh }: UseTeamMembersOptions) {
    // 成员下拉全量选项(消耗明细筛选用)
    const members = ref<any[]>([]);
    const loadMemberOptions = async () => {
        members.value = (await getMemberOptions()) || [];
    };

    // 成员列表:分页 + 关键词 + 角色筛选(与小程序一致)
    const roleFilter = ref<RoleFilterKey>("all");
    const memberQuery = reactive<{ keyword: string; team_role?: number }>({ keyword: "" });
    const {
        pager: memberPager,
        getLists: getMemberList,
        resetPage: resetMemberPage,
    } = usePaging({ fetchFun: getTeamMembers, params: memberQuery });

    const roleCounts = computed(() => {
        const c = memberPager.extend?.role_counts || {};
        return {
            all: Number(c.all) || 0,
            owner: Number(c.owner) || 0,
            admin: Number(c.admin) || 0,
            member: Number(c.member) || 0,
        };
    });

    const setRoleFilter = (key: RoleFilterKey) => {
        if (roleFilter.value === key) return;
        roleFilter.value = key;
        const hit = ROLE_FILTERS.find((f) => f.key === key);
        const role = hit?.role || 0;
        if (role > 0) memberQuery.team_role = role;
        else delete memberQuery.team_role;
        resetMemberPage();
    };

    // 邀请码
    const showInvite = ref(false);
    const inviteCode = ref("");
    const { copy } = useCopy();
    const onInvite = async () => {
        // 席位已满不再出邀请码弹窗(与小程序一致)
        const info = getInfo();
        const seatLimit = Number(info?.seat_limit) || 0;
        const memberCount = Number(info?.member_count) || 0;
        if (seatLimit > 0 && memberCount >= seatLimit) {
            feedback.msgWarning(`席位已满（${seatLimit} 席），请升级套餐后再邀请`);
            return;
        }
        try {
            const res: any = await inviteTeamMember({});
            inviteCode.value = res?.code || "";
            showInvite.value = true;
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };
    const copyCode = () => copy(inviteCode.value);

    // 可否操作该成员:超管管所有非创始人;管理员只管普通成员
    const canManage = (row: any) => {
        if (row.team_role === 2) return false;
        if (isOwner.value) return true;
        if (isAdmin.value) return row.team_role === 1;
        return false;
    };

    // 修改角色(仅超管)
    const onChangeRole = async (row: any, role: number) => {
        if (row.team_role === role) return;
        try {
            await setMemberRole({ user_id: row.id, role });
            feedback.msgSuccess(role === 3 ? "已设为管理员" : "已设为成员");
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    // 修改算力(设为目标值,差额与团队长个人算力结算)
    const showEditTokens = ref(false);
    const editTokensRow = ref<any>(null);
    const editTokensValue = ref("");
    const editTokensLoading = ref(false);
    // 可分配上限 = 团队长(超管)个人算力
    const editTokensMax = computed(() => Math.floor(Number(getInfo()?.owner_tokens ?? 0) * 100) / 100);

    const onEditTokens = (row: any) => {
        editTokensRow.value = row;
        editTokensValue.value = String(row.tokens ?? 0);
        showEditTokens.value = true;
    };
    const submitEditTokens = async () => {
        const val = String(editTokensValue.value).trim();

        if (Number(val) > editTokensMax.value) {
            return feedback.msgWarning(`最多可分配 ${formatNum(editTokensMax.value)} 算力`);
        }
        editTokensLoading.value = true;
        try {
            await setMemberTokens({ user_id: editTokensRow.value.id, tokens: Number(val) });
            feedback.msgSuccess("已修改");
            showEditTokens.value = false;
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        } finally {
            editTokensLoading.value = false;
        }
    };

    // 设置到期
    const showExpire = ref(false);
    // ElDatePicker value-format="x" 要求毫秒数字,传字符串不回显
    const expireDate = ref<number | "">("");
    const expireRow = ref<any>(null);
    const onSetExpire = (row: any) => {
        expireRow.value = row;
        // 列表里 team_expire_time 已是格式化文案；编辑用秒级时间戳 expire_time_ts
        const ts = Number(row.expire_time_ts) || 0;
        expireDate.value = ts > 0 ? ts * 1000 : "";
        showExpire.value = true;
    };
    const onSaveExpire = async () => {
        try {
            const expire = expireDate.value ? Math.floor(Number(expireDate.value) / 1000) : 0;
            await setMemberExpire({ user_id: expireRow.value.id, expire });
            feedback.msgSuccess("设置成功");
            showExpire.value = false;
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    // 划拨算力(只划分不增发)
    const onAllocate = async (row: any) => {
        try {
            const { value } = await feedback.prompt(
                `给「${row.nickname}」从你的算力中划出（只划分不增发）`,
                "划拨算力",
                { inputPattern: /^\d+(\.\d{1,2})?$/, inputErrorMessage: "请输入正确的数量" },
            );
            await allocateTokens({ user_id: row.id, tokens: Number(value) });
            feedback.msgSuccess("划拨成功");
            await refresh();
        } catch (e) {
            if (e !== "cancel") feedback.msgError(errText(e));
        }
    };

    // 移出团队:专用确认弹窗(dialog-remove-member.vue)
    const showRemove = ref(false);
    const removeRow = ref<any>(null);
    const removeSubmitting = ref(false);
    const onRemoveMember = (row: any) => {
        removeRow.value = row;
        showRemove.value = true;
    };
    const confirmRemoveMember = async () => {
        const row = removeRow.value;
        if (!row || removeSubmitting.value) return;
        removeSubmitting.value = true;
        try {
            await removeTeamMember({ user_id: row.id });
            showRemove.value = false;
            feedback.msgSuccess("已移出，算力已退回超管");
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        } finally {
            removeSubmitting.value = false;
        }
    };

    return {
        members,
        loadMemberOptions,
        memberQuery,
        memberPager,
        getMemberList,
        resetMemberPage,
        roleFilter,
        roleCounts,
        setRoleFilter,
        showInvite,
        inviteCode,
        onInvite,
        copyCode,
        canManage,
        onChangeRole,
        showEditTokens,
        editTokensRow,
        editTokensValue,
        editTokensLoading,
        editTokensMax,
        onEditTokens,
        submitEditTokens,
        showExpire,
        expireDate,
        expireRow,
        onSetExpire,
        onSaveExpire,
        onAllocate,
        onRemoveMember,
        showRemove,
        removeRow,
        removeSubmitting,
        confirmRemoveMember,
    };
}
