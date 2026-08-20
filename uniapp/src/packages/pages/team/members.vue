<template>
    <view class="h-screen flex flex-col overflow-hidden bg-[#EDF2FA]">
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="memberLists"
                :fixed="false"
                :default-page-size="10"
                :safe-area-inset-bottom="true"
                :auto="false"
                @query="queryList">
                <template #top>
                    <view class="flex flex-col gap-[24rpx] px-[32rpx] pt-[28rpx] pb-[24rpx]">
                        <!-- 席位条 -->
                        <view
                            v-if="isManager"
                            class="flex items-center gap-[20rpx] overflow-hidden rounded-[40rpx] bg-white px-[36rpx] py-[26rpx]">
                            <u-icon name="account" size="34" color="#0065FB" />
                            <view class="min-w-0 flex-1 text-[24rpx] text-[#475569]">
                                席位使用
                                <text class="font-bold text-[#0F172A]">{{ seatUsed }}</text>
                                / {{ seatLimit }} · 剩余
                                <text class="font-bold text-[#16A34A]">{{ seatLeft }}</text>
                                席
                            </view>
                            <view
                                class="flex flex-shrink-0 items-center gap-[8rpx] rounded-full bg-primary px-[28rpx] py-[14rpx]"
                                @click="handleInvite">
                                <u-icon name="plus" size="24" color="#fff" />
                                <text class="text-[24rpx] font-semibold text-white">邀请成员</text>
                            </view>
                        </view>

                        <!-- 搜索 -->
                        <view class="flex items-center gap-[16rpx] rounded-[28rpx] bg-white px-[32rpx] py-[22rpx]">
                            <u-icon name="search" size="32" color="#94A3B8" />
                            <input
                                class="min-w-0 flex-1 text-[26rpx] text-[#0F172A]"
                                type="text"
                                confirm-type="search"
                                placeholder="搜索成员昵称 / 手机号"
                                placeholder-style="color:#94A3B8"
                                :value="keyword"
                                @input="onKeywordInput"
                                @confirm="reloadList" />
                            <view
                                v-if="keyword"
                                class="flex h-[40rpx] w-[40rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#E2E8F0] active:opacity-70"
                                @click.stop="clearKeyword">
                                <u-icon name="close" size="20" color="#64748B" />
                            </view>
                        </view>

                        <!-- 角色筛选 -->
                        <scroll-view scroll-x class="w-full">
                            <view class="inline-flex gap-[16rpx] whitespace-nowrap pr-[8rpx]">
                                <view
                                    v-for="item in ROLE_FILTERS"
                                    :key="item.key"
                                    class="filter-chip"
                                    :class="{ active: roleFilter === item.key }"
                                    @click="handleRoleFilter(item.key)">
                                    {{ item.label }} {{ roleCount(item.key) }}
                                </view>
                            </view>
                        </scroll-view>
                    </view>
                </template>

                <view class="mx-[32rpx] overflow-hidden rounded-[40rpx] bg-white">
                    <view
                        v-for="(item, index) in memberLists"
                        :key="item.id"
                        class="flex items-center gap-[24rpx] px-[24rpx] py-[28rpx]"
                        :class="index < memberLists.length - 1 ? 'row-border' : ''">
                        <view
                            class="flex h-[84rpx] w-[84rpx] flex-shrink-0 items-center justify-center overflow-hidden rounded-full text-[30rpx] font-bold text-white"
                            :style="{ background: avatarColor(index) }">
                            <image v-if="item.avatar" :src="item.avatar" class="h-full w-full" mode="aspectFill" />
                            <text v-else>{{ String(item.nickname || "?").slice(0, 1) }}</text>
                        </view>
                        <view class="min-w-0 flex-1">
                            <view class="mb-[6rpx] flex min-w-0 items-center gap-[14rpx]">
                                <text class="truncate text-[28rpx] font-semibold text-[#0F172A]">
                                    {{ item.nickname
                                    }}<text v-if="isSelf(item)" class="font-medium text-[#94A3B8]">（我）</text>
                                </text>
                                <view
                                    class="flex-shrink-0 rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-bold"
                                    :style="roleStyle(item.team_role)">
                                    {{ roleLabel(item.team_role) }}
                                </view>
                            </view>
                            <text class="block text-[20rpx] text-[#94A3B8] line-clamp-1 break-all">
                                剩余算力
                                <text class="font-semibold text-[#475569]">{{ tokensText(item) }}</text>
                                · 最近使用 {{ item.last_used_time_desc || item.last_used_time || "—" }}
                            </text>
                        </view>
                        <view v-if="canManage(item)" class="act-btn" @click.stop="openAct(item)"> 管理 </view>
                        <view v-else-if="isManager" class="act-btn act-btn--muted" @click.stop="handleViewDetail(item)">
                            明细
                        </view>
                    </view>
                </view>

                <view class="px-[32rpx] pt-[28rpx] pb-[16rpx]">
                    <text class="role-note block text-center text-[22rpx] leading-[1.7] text-[#B0BCCE]">
                        {{ roleNote }}
                    </text>
                </view>

                <template #empty>
                    <view class="py-[80rpx] text-center text-[26rpx] text-[#94A3B8]">未找到相关成员</view>
                </template>
            </z-paging>
        </view>

        <member-act-popup
            v-model="showActPopup"
            :member="actMember"
            :is-owner="isOwner"
            :avatar-color="actAvatarColor"
            @action="handleAct" />
        <member-invite-popup v-model="showInvitePopup" :code="inviteCode" @copy="handleCopyInvite" />
        <member-tokens-popup
            v-model="showTokensPopup"
            v-model:value="editTokensValue"
            :member="actMember"
            :max="editTokensMax"
            :submitting="submitting"
            @confirm="submitTokens"
            @view-detail="openMemberDetail" />
        <member-expire-popup
            v-model="showExpirePopup"
            v-model:date="expireDate"
            :member="actMember"
            :submitting="submitting"
            @confirm="submitExpire" />
        <consume-detail-popup v-model="showDetailPopup" :member="detailMember" />
    </view>
</template>

<script setup lang="ts">
import {
    getTeamInfo,
    getTeamMembers,
    inviteTeamMember,
    removeTeamMember,
    setMemberExpire,
    setMemberRole,
    setMemberTokens,
} from "@/api/team";
import { useCopy } from "@/hooks/useCopy";
import { useUserStore } from "@/stores/user";
import { onShow } from "@dcloudio/uni-app";
import { AVATAR_COLORS, ROLE_FILTERS, ROLE_META, TeamRole } from "./_enums";
import ConsumeDetailPopup from "./components/consume-detail-popup.vue";
import MemberActPopup, { type MemberActKey } from "./components/member-act-popup.vue";
import MemberExpirePopup from "./components/member-expire-popup.vue";
import MemberInvitePopup from "./components/member-invite-popup.vue";
import MemberTokensPopup from "./components/member-tokens-popup.vue";

const { copy } = useCopy();
const userStore = useUserStore();
const pagingRef = shallowRef();
const memberLists = ref<any[]>([]);
const info = ref<any>(null);
const keyword = ref("");
const roleFilter = ref<(typeof ROLE_FILTERS)[number]["key"]>("all");
const roleCounts = ref({ all: 0, owner: 0, admin: 0, member: 0 });
const submitting = ref(false);

const showActPopup = ref(false);
const showInvitePopup = ref(false);
const showTokensPopup = ref(false);
const showExpirePopup = ref(false);
const showDetailPopup = ref(false);
const actMember = ref<any>(null);
const detailMember = ref<any>(null);
const actAvatarColor = ref(AVATAR_COLORS[0]);
const inviteCode = ref("");
const editTokensValue = ref("");
const expireDate = ref("");

const myRole = computed(() => Number(info.value?.team_role) || 0);
const isOwner = computed(() => myRole.value === TeamRole.Owner);
const isAdmin = computed(() => myRole.value === TeamRole.Admin);
const isManager = computed(() => isOwner.value || isAdmin.value);
const myUserId = computed(() => Number(userStore.userInfo?.id) || 0);

const seatLimit = computed(() => Number(info.value?.seat_limit) || 0);
const seatUsed = computed(() => Number(info.value?.member_count) || roleCounts.value.all || 0);
const seatLeft = computed(() => Math.max(0, Number(info.value?.seat_left) || seatLimit.value - seatUsed.value));
const editTokensMax = computed(() => Math.floor(Number(info.value?.owner_tokens ?? 0) * 100) / 100);

const roleNote = computed(() => {
    if (isOwner.value) return "你是创始人，可任命管理员、移出成员、分配算力、设置到期";
    if (isAdmin.value) return "你是管理员，可管理创始人以外的普通成员";
    return "";
});

const errText = (e: any) => (typeof e === "string" ? e : e?.msg || e?.message || "操作失败");

const formatNum = (n: number) => {
    if (!Number.isFinite(n)) return "0";
    if (Number.isInteger(n)) return String(n);
    return String(Math.round(n * 100) / 100);
};

const roleLabel = (role: number) => ROLE_META[Number(role)]?.label || "成员";
const roleStyle = (role: number) => {
    const m = ROLE_META[Number(role)] || ROLE_META[TeamRole.Member];
    return { background: m.bg, color: m.color };
};
const roleCount = (key: string) => Number((roleCounts.value as any)[key] || 0);
const avatarColor = (index: number) => AVATAR_COLORS[index % AVATAR_COLORS.length];
const isSelf = (row: any) => Number(row?.id) === myUserId.value;
const tokensText = (row: any) => formatNum(Number(row?.tokens) || 0);

const canManage = (row: any) => {
    if (isSelf(row)) return false;
    if (Number(row?.team_role) === TeamRole.Owner) return false;
    if (isOwner.value) return true;
    if (isAdmin.value) return Number(row?.team_role) === TeamRole.Member;
    return false;
};

const currentRoleParam = computed(() => {
    const hit = ROLE_FILTERS.find((f) => f.key === roleFilter.value);
    return hit?.role || 0;
});

const loadInfo = async () => {
    try {
        info.value = await getTeamInfo();
        if (Number(info.value?.in_team) !== 1) {
            uni.$u.toast("您还未加入团队");
            setTimeout(() => uni.navigateBack(), 400);
            return;
        }
        if (Number(info.value?.expired) === 1) {
            uni.$u.toast("该团队成员资格已过期，无法进入");
            setTimeout(() => uni.navigateBack(), 400);
            return;
        }
        // 与 PC 一致：普通成员无成员管理入口
        if (!isManager.value) {
            uni.$u.toast("暂无权限查看成员管理");
            setTimeout(() => uni.navigateBack(), 400);
            return;
        }
        uni.setNavigationBarTitle({ title: "成员管理" });
    } catch (e: any) {
        uni.$u.toast(errText(e));
    }
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const data: any = await getTeamMembers({
            page_no,
            page_size,
            keyword: keyword.value.trim(),
            team_role: currentRoleParam.value || undefined,
        });
        const lists = data?.lists || [];
        const counts = data?.extend?.role_counts;
        if (counts) {
            roleCounts.value = {
                all: Number(counts.all) || 0,
                owner: Number(counts.owner) || 0,
                admin: Number(counts.admin) || 0,
                member: Number(counts.member) || 0,
            };
        }
        pagingRef.value?.complete(lists);
    } catch (e: any) {
        pagingRef.value?.complete(false);
        uni.$u.toast(errText(e));
    }
};

const reloadList = () => {
    pagingRef.value?.reload();
};

let keywordTimer: ReturnType<typeof setTimeout> | null = null;
const onKeywordInput = (e: any) => {
    keyword.value = String(e?.detail?.value ?? "");
    if (keywordTimer) clearTimeout(keywordTimer);
    keywordTimer = setTimeout(() => reloadList(), 300);
};

const clearKeyword = () => {
    if (keywordTimer) clearTimeout(keywordTimer);
    keyword.value = "";
    reloadList();
};

const handleRoleFilter = (key: (typeof ROLE_FILTERS)[number]["key"]) => {
    if (roleFilter.value === key) return;
    roleFilter.value = key;
    reloadList();
};

const handleInvite = async () => {
    if (seatLimit.value > 0 && seatUsed.value >= seatLimit.value) {
        uni.$u.toast(`席位已满（${seatLimit.value} 席），请升级套餐后再邀请`);
        return;
    }
    try {
        const res: any = await inviteTeamMember({});
        inviteCode.value = res?.code || "";
        if (!inviteCode.value) {
            uni.$u.toast("邀请码生成失败");
            return;
        }
        showInvitePopup.value = true;
    } catch (e: any) {
        uni.$u.toast(errText(e));
    }
};

const handleCopyInvite = () => {
    if (!inviteCode.value) return;
    copy(inviteCode.value);
};

const openAct = (row: any) => {
    actMember.value = row;
    const idx = memberLists.value.findIndex((m) => m.id === row.id);
    actAvatarColor.value = avatarColor(Math.max(0, idx));
    showActPopup.value = true;
};

const handleViewDetail = (row: any) => {
    const uid = Number(row?.id || 0);
    if (!uid) return;
    uni.$u.route({ url: `/packages/pages/team/consumption?user_id=${uid}` });
};

/** 修改算力弹窗内点击「当前算力」→ 展示该成员算力明细 */
const openMemberDetail = () => {
    const row = actMember.value;
    if (!row) return;
    detailMember.value = {
        id: row.id,
        user_id: row.id,
        nickname: row.nickname,
        mobile: row.mobile,
        avatar: row.avatar,
        tokens: row.tokens,
    };
    // 先关编辑弹窗，避免层级遮挡；明细关后再可重新打开编辑
    showTokensPopup.value = false;
    setTimeout(() => {
        showDetailPopup.value = true;
    }, 80);
};

const handleAct = async (key: MemberActKey) => {
    const row = actMember.value;
    if (!row) return;
    if (key === "detail") {
        showActPopup.value = false;
        // 弹层关闭后同一次点击会穿透到下方列表，延迟再跳转
        setTimeout(() => handleViewDetail(row), 80);
        return;
    }
    if (key === "tokens") {
        showActPopup.value = false;
        editTokensValue.value = String(row.tokens ?? 0);
        showTokensPopup.value = true;
        return;
    }
    if (key === "expire") {
        showActPopup.value = false;
        const ts = Number(row.expire_time_ts) || 0;
        if (ts > 0) {
            const d = new Date(ts * 1000);
            const p = (n: number) => String(n).padStart(2, "0");
            expireDate.value = `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
        } else {
            expireDate.value = "";
        }
        showExpirePopup.value = true;
        return;
    }
    if (key === "setAdmin" || key === "unsetAdmin") {
        const role = key === "setAdmin" ? TeamRole.Admin : TeamRole.Member;
        submitting.value = true;
        try {
            await setMemberRole({ user_id: row.id, role });
            showActPopup.value = false;
            uni.$u.toast(role === TeamRole.Admin ? "已设为管理员" : "已取消管理员");
            await loadInfo();
            reloadList();
        } catch (e: any) {
            uni.$u.toast(errText(e));
        } finally {
            submitting.value = false;
        }
        return;
    }
    if (key === "remove") {
        uni.showModal({
            title: "移出团队",
            content: `确定将「${row.nickname}」移出团队吗？其剩余算力将退回给超级管理员。`,
            confirmColor: "#EF4444",
            success: async (res) => {
                if (!res.confirm) return;
                submitting.value = true;
                try {
                    await removeTeamMember({ user_id: row.id });
                    showActPopup.value = false;
                    uni.$u.toast("已移出团队");
                    await loadInfo();
                    reloadList();
                } catch (e: any) {
                    uni.$u.toast(errText(e));
                } finally {
                    submitting.value = false;
                }
            },
        });
    }
};

const submitTokens = async (val: string) => {
    if (submitting.value || !actMember.value) return;
    const text = String(val).trim();
    if (!/^\d+(\.\d{1,2})?$/.test(text)) {
        uni.$u.toast("请输入正确的算力数量（最多两位小数）");
        return;
    }
    if (Number(text) > editTokensMax.value) {
        uni.$u.toast(`最多可分配 ${formatNum(editTokensMax.value)} 算力`);
        return;
    }
    submitting.value = true;
    try {
        await setMemberTokens({ user_id: actMember.value.id, tokens: Number(text) });
        showTokensPopup.value = false;
        uni.$u.toast("已修改");
        await loadInfo();
        reloadList();
    } catch (e: any) {
        uni.$u.toast(errText(e));
    } finally {
        submitting.value = false;
    }
};

const submitExpire = async () => {
    if (submitting.value || !actMember.value) return;
    let expire = 0;
    if (expireDate.value) {
        // 兼容 "YYYY-MM-DD HH:mm:ss" / "YYYY-MM-DD"；iOS 需用 / 分隔
        const normalized = String(expireDate.value).trim().replace(/-/g, "/");
        const ms = new Date(normalized).getTime();
        if (!Number.isFinite(ms)) {
            uni.$u.toast("请选择正确的日期时间");
            return;
        }
        expire = Math.floor(ms / 1000);
    }
    submitting.value = true;
    try {
        await setMemberExpire({ user_id: actMember.value.id, expire });
        showExpirePopup.value = false;
        uni.$u.toast("设置成功");
        reloadList();
    } catch (e: any) {
        uni.$u.toast(errText(e));
    } finally {
        submitting.value = false;
    }
};

onShow(async () => {
    await loadInfo();
    nextTick(() => reloadList());
});
</script>

<style lang="scss" scoped>
.filter-chip {
    @apply inline-flex items-center rounded-full border-[2rpx] border-solid border-[#E5EAF3] bg-white px-[28rpx] py-[12rpx] text-[24rpx] font-semibold text-[#64748B];

    &.active {
        @apply border-primary bg-primary text-white;
    }
}

.act-btn {
    @apply flex-shrink-0 rounded-full bg-primary-light-9 px-[24rpx] py-[12rpx] text-[22rpx] font-semibold text-primary;

    &--muted {
        @apply bg-[#F1F5F9] text-[#475569];
    }
}

.row-border {
    @apply border-0 border-b-[2rpx] border-solid border-[#F0F4FB];
}

.role-note {
    white-space: pre-line;
}
</style>
