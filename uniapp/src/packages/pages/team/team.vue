<template>
    <view class="min-h-screen bg-[#EDF2FA]">
        <team-skeleton v-if="showSkeleton" />
        <view v-else class="flex flex-col gap-[28rpx] px-[32rpx] pt-[28rpx] pb-[120rpx]">
            <!-- 团队信息卡 -->
            <view class="overflow-hidden rounded-[40rpx] bg-white">
                <view class="team-hero relative overflow-hidden px-[40rpx] py-[40rpx]">
                    <view class="team-hero-orb team-hero-orb--a" />
                    <view class="team-hero-orb team-hero-orb--b" />
                    <view class="relative flex items-center gap-[28rpx]">
                        <view
                            class="flex h-[104rpx] w-[104rpx] flex-shrink-0 items-center justify-center rounded-[32rpx]"
                            style="border: 2rpx solid rgba(255, 255, 255, 0.25); background: rgba(255, 255, 255, 0.18)">
                            <image :src="icon('users')" class="h-[52rpx] w-[52rpx]" mode="aspectFit" />
                        </view>
                        <view class="min-w-0 flex-1">
                            <view class="mb-[8rpx] flex min-w-0 items-center gap-[16rpx]">
                                <text class="block min-w-0 truncate text-[36rpx] font-extrabold text-white">
                                    {{ info?.name || "-" }}
                                </text>
                                <view
                                    class="flex-shrink-0 rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-bold"
                                    :class="roleTagClass">
                                    {{ roleLabel }}
                                </view>
                            </view>
                            <view class="flex flex-wrap items-center gap-[8rpx]" @click="handleCopyTeamId">
                                <text class="text-[22rpx]" style="color: rgba(255, 255, 255, 0.72)">
                                    团队ID：{{ info?.team_id || "-" }}
                                </text>
                                <text class="text-[22rpx]" style="color: rgba(255, 255, 255, 0.72)">复制</text>
                                <text class="text-[22rpx]" style="color: rgba(255, 255, 255, 0.72)">
                                    创始人：{{ info?.owner_nickname || "-" }}
                                </text>
                            </view>
                        </view>
                        <view
                            v-if="isOwner"
                            class="flex h-[64rpx] w-[64rpx] flex-shrink-0 items-center justify-center rounded-[20rpx]"
                            style="background: rgba(255, 255, 255, 0.18)"
                            @click="openRename">
                            <u-icon name="edit-pen" size="28" color="#fff" />
                        </view>
                    </view>
                </view>

                <!-- 与 PC 一致：成员管理/消耗统计仅创始人、管理员可见 -->
                <view v-if="isManager" class="flex">
                    <view class="flex-1 py-[28rpx] text-center" @click="handleGoMembers">
                        <text class="block text-[36rpx] font-bold text-[#0F172A]">
                            {{ memberCount }}
                        </text>
                        <view class="mt-[6rpx] flex items-center justify-center gap-[4rpx]">
                            <text class="text-[22rpx] text-[#94A3B8]">团队成员</text>
                            <u-icon name="arrow-right" size="18" color="#94A3B8" />
                        </view>
                    </view>
                    <view class="w-[2rpx] bg-[#F0F4FB]" />
                    <view class="flex-1 py-[28rpx] text-center">
                        <text class="block text-[36rpx] font-bold text-[#0F172A]">
                            {{ adminCount }}
                        </text>
                        <text class="mt-[6rpx] block text-[22rpx] text-[#94A3B8]">管理员</text>
                    </view>
                    <view class="w-[2rpx] bg-[#F0F4FB]" />
                    <view class="flex-1 py-[28rpx] text-center" @click="handleGoConsume">
                        <view class="flex items-center justify-center gap-[6rpx]">
                            <image :src="teamIcon('zap_amber')" class="h-[28rpx] w-[28rpx]" mode="aspectFit" />
                            <text class="text-[36rpx] font-bold text-[#F59E0B]">
                                {{ todayCostText }}
                            </text>
                        </view>
                        <view class="mt-[6rpx] flex items-center justify-center gap-[4rpx]">
                            <text class="text-[22rpx] text-[#94A3B8]">今日消耗</text>
                            <u-icon name="arrow-right" size="18" color="#94A3B8" />
                        </view>
                        <text class="mt-[4rpx] block text-[18rpx] text-[#CBD5E1]">不包含算力划拨</text>
                    </view>
                </view>
            </view>

            <!-- 成员：到期状态（对齐 PC 组织信息） -->
            <view v-if="!isOwner" class="overflow-hidden rounded-[40rpx] bg-white px-[36rpx] py-[32rpx]">
                <view
                    v-if="Number(info?.expired) === 1"
                    class="rounded-[24rpx] bg-[#FEF2F2] px-[28rpx] py-[24rpx] text-[26rpx] font-medium leading-[1.6] text-[#EF4444]">
                    团队权益已到期，请联系团队主续期
                </view>
                <view
                    v-else
                    class="rounded-[24rpx] bg-[#F0FDF4] px-[28rpx] py-[24rpx] text-[26rpx] font-medium leading-[1.6] text-[#16A34A]">
                    团队权益有效，到期时间：
                    <text class="font-bold">{{ info?.team_expire_time_desc || "永久" }}</text>
                </view>
            </view>

            <!-- 邀请：团队内全员可分享 -->
            <view class="overflow-hidden rounded-[40rpx] bg-white">
                <view
                    class="flex items-center gap-[28rpx] px-[36rpx] py-[32rpx] active:bg-[#F6F9FF]"
                    @click="handleInvite">
                    <view
                        class="flex h-[90rpx] w-[90rpx] flex-shrink-0 items-center justify-center rounded-[28rpx] bg-primary-light-9">
                        <u-icon name="plus" size="44" color="#0065FB" />
                    </view>
                    <view class="min-w-0 flex-1">
                        <text class="mb-[4rpx] block text-[30rpx] font-bold text-[#0F172A]">邀请成员</text>
                        <text class="block text-[24rpx] text-[#94A3B8] line-clamp-1">
                            生成邀请码，分享给同事加入团队
                        </text>
                    </view>
                    <u-icon name="arrow-right" size="28" color="#CBD5E1" />
                </view>
            </view>

            <!-- 事务入口：仅创始人/管理员（对齐 PC 侧栏） -->
            <view v-if="isManager" class="overflow-hidden rounded-[40rpx] bg-white">
                <view
                    class="flex items-center gap-[28rpx] px-[36rpx] py-[32rpx] active:bg-[#F6F9FF]"
                    @click="handleGoMembers">
                    <view
                        class="flex h-[90rpx] w-[90rpx] flex-shrink-0 items-center justify-center rounded-[28rpx] bg-primary-light-9">
                        <u-icon name="account" size="44" color="#0065FB" />
                    </view>
                    <view class="min-w-0 flex-1">
                        <text class="mb-[4rpx] block text-[30rpx] font-bold text-[#0F172A]">成员管理</text>
                        <text class="block text-[24rpx] text-[#94A3B8] line-clamp-1">
                            {{ memberCount }} 名成员 · 任命管理员、分配算力
                        </text>
                    </view>
                    <u-icon name="arrow-right" size="28" color="#CBD5E1" />
                </view>
                <view class="mx-[36rpx] h-[2rpx] bg-[#F0F4FB]" />
                <view
                    class="flex items-center gap-[28rpx] px-[36rpx] py-[32rpx] active:bg-[#F6F9FF]"
                    @click="handleGoConsume">
                    <view
                        class="flex h-[90rpx] w-[90rpx] flex-shrink-0 items-center justify-center rounded-[28rpx] bg-[#FFF7ED]">
                        <image :src="teamIcon('receipt_amber')" class="h-[44rpx] w-[44rpx]" mode="aspectFit" />
                    </view>
                    <view class="min-w-0 flex-1">
                        <text class="mb-[4rpx] block text-[30rpx] font-bold text-[#0F172A]">消耗明细</text>
                        <text class="block text-[24rpx] text-[#94A3B8] line-clamp-1">
                            今日消耗 {{ todayCostText }} 算力（不含划拨）· 按明细查看
                        </text>
                    </view>
                    <u-icon name="arrow-right" size="28" color="#CBD5E1" />
                </view>
            </view>

            <!-- 套餐与算力：创始人/管理员可看席位；创始人算力与充值仅创始人 -->
            <view v-if="isManager" class="overflow-hidden rounded-[40rpx] bg-white">
                <view class="flex items-center gap-[16rpx] px-[36rpx] pt-[32rpx] pb-[8rpx]">
                    <view class="h-[32rpx] w-[8rpx] flex-shrink-0 rounded-full bg-primary" />
                    <text class="flex-1 text-[30rpx] font-bold text-[#0F172A]">套餐与算力</text>
                    <view
                        class="mr-[8rpx] text-[22rpx] font-semibold text-primary"
                        @click="showBenefitsPopup = true">
                        查看权益
                    </view>
                    <view
                        class="inline-flex items-center gap-[8rpx] rounded-full px-[20rpx] py-[6rpx] text-[22rpx] font-bold"
                        :class="oemBadgeClass">
                        <view class="h-[10rpx] w-[10rpx] flex-shrink-0 rounded-full" :class="oemDotClass" />
                        <text>{{ oemBadgeText }}</text>
                    </view>
                </view>
                <view class="flex py-[16rpx]">
                    <view v-if="isOwner" class="flex-[1.2] px-[8rpx] py-[20rpx] text-center">
                        <view class="flex items-center justify-center gap-[6rpx]">
                            <image :src="icon('zap')" class="h-[26rpx] w-[26rpx]" mode="aspectFit" />
                            <text class="text-[34rpx] font-bold text-[#0F172A]">{{ powerText }}</text>
                        </view>
                        <view class="mt-[6rpx] flex items-center justify-center gap-[8rpx]">
                            <text class="text-[22rpx] text-[#94A3B8]">剩余算力</text>
                            <text class="text-[22rpx] font-semibold text-primary" @click="handleRecharge">充值</text>
                        </view>
                    </view>
                    <view v-if="isOwner" class="w-[2rpx] bg-[#F0F4FB]" />
                    <view class="flex-1 py-[20rpx] text-center">
                        <view class="flex items-baseline justify-center">
                            <text class="text-[34rpx] font-bold text-[#0F172A]">{{ memberCount }}</text>
                            <text class="text-[24rpx] font-medium text-[#94A3B8]"> / {{ seatLimit }} </text>
                        </view>
                        <text class="mt-[6rpx] block text-[22rpx] text-[#94A3B8]">席位使用</text>
                    </view>
                    <view class="w-[2rpx] bg-[#F0F4FB]" />
                    <view class="flex-1 py-[20rpx] text-center">
                        <text class="block text-[34rpx] font-bold text-[#0F172A]">{{ seatLeft }}</text>
                        <text class="mt-[6rpx] block text-[22rpx] text-[#94A3B8]">剩余席位</text>
                    </view>
                </view>
                <view class="px-[36rpx] pb-[32rpx]">
                    <view class="h-[12rpx] overflow-hidden rounded-full bg-[#EEF2F8]">
                        <view class="seat-bar-fill h-full rounded-full" :style="{ width: seatPct + '%' }" />
                    </view>
                </view>
            </view>

            <!-- 授权功能 -->
            <view class="overflow-hidden rounded-[40rpx] bg-white">
                <view class="flex items-center gap-[16rpx] px-[36rpx] pt-[32rpx] pb-[12rpx]">
                    <view class="h-[32rpx] w-[8rpx] flex-shrink-0 rounded-full bg-primary" />
                    <text class="flex-1 text-[30rpx] font-bold text-[#0F172A]">授权功能</text>
                    <view class="rounded-full bg-[#EEF4FF] px-[20rpx] py-[6rpx] text-[22rpx] font-bold text-primary">
                        已启用 {{ enabledCount }} / {{ FEATURE_APPS.length }}
                    </view>
                </view>
                <view class="grid grid-cols-3 gap-[8rpx] px-[24rpx] pb-[32rpx] pt-[12rpx]">
                    <view
                        v-for="app in FEATURE_APPS"
                        :key="app.key"
                        class="flex flex-col items-center gap-[14rpx] px-[8rpx] py-[24rpx]">
                        <view
                            class="flex h-[88rpx] w-[88rpx] items-center justify-center rounded-[28rpx]"
                            :class="isFeatureEnabled(app.key) ? 'bg-primary-light-9' : 'bg-[#F1F5F9]'">
                            <u-icon
                                :name="app.icon"
                                size="40"
                                :color="isFeatureEnabled(app.key) ? '#0065FB' : '#94A3B8'" />
                        </view>
                        <text
                            class="text-[22rpx] font-medium"
                            :class="isFeatureEnabled(app.key) ? 'text-[#475569]' : 'text-[#94A3B8]'">
                            {{ app.label }}
                        </text>
                    </view>
                </view>
            </view>

            <!-- 危险操作 -->
            <view class="rounded-[32rpx] bg-[#FFF1F0] py-[28rpx] text-center active:opacity-80" @click="handleDanger">
                <text class="text-[30rpx] font-semibold tracking-wide text-[#EF4444]">
                    {{ isOwner ? "解散团队" : "退出团队" }}
                </text>
            </view>

            <text class="role-note px-[16rpx] pb-[16rpx] text-center text-[22rpx] leading-[1.7] text-[#B0BCCE]">
                {{ roleNote }}
            </text>
        </view>

        <team-benefits-popup
            v-model="showBenefitsPopup"
            :oem-status="oemStatus"
            :seat-limit="seatLimit"
            :enabled-count="enabledCount" />

        <member-invite-popup v-model="showInvitePopup" :code="inviteCode" @copy="handleCopyInvite" />

        <!-- 重命名 -->
        <u-popup v-model="showRenamePopup" mode="center" width="90%" :border-radius="32">
            <view class="rounded-[32rpx] bg-white p-[40rpx]">
                <view class="mb-[32rpx] flex items-center gap-x-[12rpx]">
                    <view class="h-[32rpx] w-[6rpx] rounded-full bg-primary" />
                    <text class="text-[30rpx] font-bold text-[#212121]">修改团队名称</text>
                </view>
                <view class="mb-[40rpx] rounded-[20rpx] bg-[#F4F6FB] px-[28rpx] py-[16rpx]">
                    <u-input
                        v-model="renameName"
                        placeholder="请输入团队名称"
                        maxlength="30"
                        clearable
                        placeholder-style="color: #9CA3AF; font-size: 26rpx;" />
                </view>
                <view class="flex items-center gap-[16rpx]">
                    <view
                        class="flex h-[88rpx] flex-1 items-center justify-center rounded-full bg-[#F4F6FB] active:opacity-70"
                        @click="showRenamePopup = false">
                        <text class="text-[28rpx] font-semibold text-[#676767]">取消</text>
                    </view>
                    <view
                        class="flex h-[88rpx] flex-1 items-center justify-center rounded-full bg-primary shadow-sm active:opacity-90"
                        @click="handleRenameConfirm">
                        <text class="text-[28rpx] font-bold text-white">确定</text>
                    </view>
                </view>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { disbandTeam, getTeamInfo, inviteTeamMember, leaveTeam, setTeamName, switchTeam } from "@/api/team";
import { useCopy } from "@/hooks/useCopy";
import { useUserStore } from "@/stores/user";
import { TEAM_DISBAND_BIND_TIP, TEAM_LEAVE_BIND_TIP } from "@/utils/teamSwitchTip";
import { onShow } from "@dcloudio/uni-app";
import MemberInvitePopup from "./components/member-invite-popup.vue";
import TeamBenefitsPopup from "./components/team-benefits-popup.vue";
import TeamSkeleton from "./components/team-skeleton.vue";

const { copy } = useCopy();
const userStore = useUserStore();
/** 退出/解散进行中：避免 onShow→loadInfo 再次 navigateBack 叠成 404 */
const leaving = ref(false);

const goPersonalHome = () => {
    // 与底部「我的」一致用 reLaunch，避免 navigateBack 失败/连退落到未知路由
    uni.$u.route({ url: "/packages/pages/user/user", type: "reLaunch" });
};

const FEATURE_APPS = [
    { key: "digital_human", label: "数字人", icon: "account" },
    { key: "video_mix", label: "数字人混剪", icon: "play-right" },
    { key: "gaode_lead", label: "高德获客", icon: "map" },
    { key: "ai_phone", label: "AI手机", icon: "phone" },
    { key: "ai_draw", label: "AI作图", icon: "photo" },
    { key: "ai_ppt", label: "AI PPT", icon: "order" },
    { key: "sph_lead", label: "视频号获客", icon: "play-circle" },
    { key: "ai_agent", label: "AI智能体", icon: "integral" },
    { key: "llm_chat", label: "大模型对话", icon: "chat" },
] as const;

const iconMap = import.meta.glob("../../static/icons/user_center/*.svg", {
    eager: true,
    import: "default",
}) as Record<string, string>;

const teamIconMap = import.meta.glob("../../static/icons/team/*.svg", {
    eager: true,
    import: "default",
}) as Record<string, string>;

const icon = (name: string) => {
    const key = Object.keys(iconMap).find((k) => k.endsWith(`/${name}.svg`));
    return key ? iconMap[key] : "";
};

const teamIcon = (name: string) => {
    const key = Object.keys(teamIconMap).find((k) => k.endsWith(`/${name}.svg`));
    return key ? teamIconMap[key] : "";
};

const info = ref<any>(null);
const loading = ref(false);
const pageReady = ref(false);
const submitting = ref(false);
const showRenamePopup = ref(false);
const showBenefitsPopup = ref(false);
const showInvitePopup = ref(false);
const inviteCode = ref("");
const renameName = ref("");
const showSkeleton = computed(() => !pageReady.value);

const role = computed(() => Number(info.value?.team_role) || 0);
const isOwner = computed(() => role.value === 2);
const isAdmin = computed(() => role.value === 3);
const isManager = computed(() => isOwner.value || isAdmin.value);

const roleLabel = computed(() => {
    if (isOwner.value) return "创始人";
    if (isAdmin.value) return "管理员";
    return "成员";
});

const roleTagClass = computed(() => {
    if (isOwner.value) return "bg-[#DBEAFE] text-[#1D4ED8]";
    if (isAdmin.value) return "bg-[#FEF3C7] text-[#B45309]";
    return "bg-[#F1F5F9] text-[#64748B]";
});

const memberCount = computed(() => Number(info.value?.member_count) || 0);
const adminCount = computed(() => Number(info.value?.admin_count) || 0);
const seatLimit = computed(() => Number(info.value?.seat_limit) || 0);
const seatLeft = computed(() => Number(info.value?.seat_left) || Math.max(0, seatLimit.value - memberCount.value));
const seatPct = computed(() => {
    if (!seatLimit.value) return 0;
    return Math.min(100, Math.round((memberCount.value / seatLimit.value) * 100));
});

const todayCost = computed(() => Number(info.value?.today_cost) || 0);
const todayCostText = computed(() => formatPower(todayCost.value));

const powerText = computed(() => {
    // 仅创始人展示个人算力；管理员勿回落到 owner_tokens
    if (isOwner.value) {
        const v = info.value?.owner_tokens ?? info.value?.tokens;
        return formatPower(Number(v) || 0);
    }
    return formatPower(Number(info.value?.team_tokens) || 0);
});

const oemStatus = computed(() => Number(info.value?.oem_status) || 0);
const oemBadgeText = computed(() => {
    if (oemStatus.value === 2) return "企业OEM · 生效中";
    if (oemStatus.value === 1) return "免费版 · 升级审核中";
    return "免费版";
});
const oemBadgeClass = computed(() => {
    if (oemStatus.value === 2) return "bg-[#F0FDF4] text-[#16A34A]";
    if (oemStatus.value === 1) return "bg-[#FFF7ED] text-[#B45309]";
    return "bg-[#F1F5F9] text-[#64748B]";
});
const oemDotClass = computed(() => {
    if (oemStatus.value === 2) return "bg-[#22C55E]";
    if (oemStatus.value === 1) return "bg-[#F59E0B]";
    return "bg-[#94A3B8]";
});

const isFeatureEnabled = (key: string) => {
    const list = info.value?.features;
    if (!Array.isArray(list)) return true;
    return list.includes(key);
};

const enabledCount = computed(() => FEATURE_APPS.filter((a) => isFeatureEnabled(a.key)).length);

const roleNote = computed(() => {
    if (isOwner.value) {
        return "你是本团队的创建者（创始人）\n可邀请成员、任命管理员、移出成员、分配算力、设置到期、查看消耗明细";
    }
    if (isAdmin.value) {
        return "你是本团队的管理员\n可邀请成员、管理创始人以外的成员、查看消耗明细";
    }
    return "你以成员身份加入本团队\n可邀请同事加入；如需管理权限请联系团队创始人";
});

const formatPower = (n: number) => {
    if (!Number.isFinite(n)) return "0";
    if (Number.isInteger(n)) return String(n);
    return String(Math.round(n * 100) / 100);
};

const errText = (e: any) => (typeof e === "string" ? e : e?.msg || e?.message || "操作失败");

const loadInfo = async () => {
    if (loading.value || leaving.value) return;
    loading.value = true;
    try {
        const data = await getTeamInfo();
        info.value = data;
        if (Number(data?.in_team) !== 1) {
            // 主动退出/解散时由 goPersonalHome 接管，勿再 toast + 二次跳转
            if (leaving.value) return;
            uni.$u.toast("您还未加入团队");
            setTimeout(() => goPersonalHome(), 400);
            return;
        }
        // 成员/管理员资格过期：不允许停留在团队页(含分享/历史栈直进)
        if (Number(data?.expired) === 1) {
            leaving.value = true;
            uni.$u.toast("该团队成员资格已过期，无法进入");
            try {
                await switchTeam({ team_id: 0 });
            } catch {
                // 已在个人空间时忽略
            }
            try {
                await userStore.getUser();
            } catch {
                // 忽略
            }
            goPersonalHome();
        }
    } catch (e: any) {
        if (!leaving.value) uni.$u.toast(errText(e));
    } finally {
        loading.value = false;
        pageReady.value = true;
    }
};

const handleCopyTeamId = () => {
    const id = info.value?.team_id;
    if (!id) return;
    copy(String(id));
};

const openRename = () => {
    renameName.value = String(info.value?.name || "");
    showRenamePopup.value = true;
};

const handleRenameConfirm = async () => {
    if (submitting.value) return;
    const name = renameName.value.trim();
    if (!name) {
        uni.$u.toast("请输入团队名称");
        return;
    }
    submitting.value = true;
    try {
        await setTeamName({ name });
        showRenamePopup.value = false;
        uni.$u.toast("团队名称已更新");
        await loadInfo();
    } catch (e: any) {
        uni.$u.toast(errText(e));
    } finally {
        submitting.value = false;
    }
};

const handleGoMembers = () => {
    if (!isManager.value) return;
    uni.$u.route({ url: "/packages/pages/team/members" });
};

const handleGoConsume = () => {
    if (!isManager.value) return;
    uni.$u.route({ url: "/packages/pages/team/consumption" });
};

const handleInvite = async () => {
    if (seatLimit.value > 0 && memberCount.value >= seatLimit.value) {
        uni.$u.toast(`席位已满（${seatLimit.value} 席），请联系创始人升级后再邀请`);
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

const handleRecharge = () => {
    if (!isOwner.value) return;
    uni.$u.route({ url: "/packages/pages/recharge/recharge" });
};

const finishLeaveOrDisband = async (toastText: string) => {
    try {
        await switchTeam({ team_id: 0 });
    } catch {
        // 已在个人空间时忽略
    }
    try {
        await userStore.getUser();
    } catch {
        // 忽略刷新失败，仍回「我的」
    }
    uni.$u.toast(toastText);
    goPersonalHome();
};

const handleDanger = () => {
    if (isOwner.value) {
        uni.showModal({
            title: "解散团队",
            content: `解散后所有成员与归属用户将被释放，相关配置将被清除，且无法恢复。确认解散「${
                info.value?.name || ""
            }」？\n\n${TEAM_DISBAND_BIND_TIP}`,
            confirmColor: "#EF4444",
            success: async (res) => {
                if (!res.confirm || submitting.value) return;
                submitting.value = true;
                leaving.value = true;
                try {
                    await disbandTeam();
                    await finishLeaveOrDisband("团队已解散，已切换到个人空间");
                } catch (e: any) {
                    leaving.value = false;
                    uni.$u.toast(errText(e));
                } finally {
                    submitting.value = false;
                }
            },
        });
        return;
    }
    uni.showModal({
        title: "退出团队",
        content: `确认退出「${info.value?.name || ""}」？退出后需重新邀请才能加入。\n\n${TEAM_LEAVE_BIND_TIP}`,
        confirmColor: "#EF4444",
        success: async (res) => {
            if (!res.confirm || submitting.value) return;
            submitting.value = true;
            leaving.value = true;
            try {
                await leaveTeam();
                await finishLeaveOrDisband("已退出团队，已切换到个人空间");
            } catch (e: any) {
                leaving.value = false;
                uni.$u.toast(errText(e));
            } finally {
                submitting.value = false;
            }
        },
    });
};

onShow(() => {
    if (leaving.value) return;
    loadInfo();
});
</script>

<style lang="scss" scoped>
.team-hero {
    background: linear-gradient(135deg, #2563eb, #4f8bff);
}

.team-hero-orb {
    @apply pointer-events-none absolute rounded-full;

    &--a {
        top: -60rpx;
        right: -48rpx;
        width: 260rpx;
        height: 260rpx;
        background: rgba(255, 255, 255, 0.1);
    }

    &--b {
        right: 72rpx;
        bottom: -92rpx;
        width: 200rpx;
        height: 200rpx;
        background: rgba(255, 255, 255, 0.06);
    }
}

.seat-bar-fill {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.role-note {
    white-space: pre-line;
}
</style>
