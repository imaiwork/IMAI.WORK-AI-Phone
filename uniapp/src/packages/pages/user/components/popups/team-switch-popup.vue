<template>
    <popup-bottom
        :model-value="modelValue"
        title="切换团队"
        height="70%"
        custom-class="bg-white"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #content>
            <view class="flex h-full min-h-0 flex-col px-[40rpx] pt-2">
                <text class="mb-[24rpx] block flex-shrink-0 text-[24rpx] text-[#94A3B8]">
                    选择个人空间或团队，数据将按所选空间展示
                </text>

                <view class="min-h-0 flex-1">
                    <scroll-view scroll-y class="h-full w-full">
                        <view class="flex flex-col gap-[20rpx] pb-[20rpx]">
                            <!-- 有团队时才展示个人空间入口，便于从团队模式切回 -->
                            <view
                                v-if="teams.length"
                                class="flex items-center gap-[24rpx] rounded-[28rpx] border-[2rpx] border-solid px-[28rpx] py-[28rpx]"
                                :class="
                                    isPersonalCurrent
                                        ? 'border-primary bg-[#EEF4FF]'
                                        : 'border-[#EEF2F7] bg-[#F8FAFC]'
                                "
                                @click="handleSelectPersonal">
                                <view
                                    class="flex h-[84rpx] w-[84rpx] flex-shrink-0 items-center justify-center rounded-[28rpx] bg-[#64748B] shadow-[0_6rpx_20rpx_rgba(100,116,139,0.28)]">
                                    <u-icon name="account" color="#fff" size="40" />
                                </view>
                                <view class="min-w-0 flex-1">
                                    <text class="mb-[6rpx] block text-[30rpx] font-bold text-[#0F172A]">个人空间</text>
                                    <text class="block text-[22rpx] text-[#94A3B8]">
                                        个人算力 {{ formatTokens(personalTokens) }}
                                    </text>
                                </view>
                                <view
                                    v-if="isPersonalCurrent"
                                    class="flex h-[36rpx] w-[36rpx] flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                    <u-icon name="checkmark" color="#fff" size="18" />
                                </view>
                            </view>

                            <view
                                v-for="item in teams"
                                :key="item.team_id"
                                class="flex items-center gap-[24rpx] rounded-[28rpx] border-[2rpx] border-solid px-[28rpx] py-[28rpx]"
                                :class="[
                                    item.is_current === 1
                                        ? 'border-primary bg-[#EEF4FF]'
                                        : 'border-[#EEF2F7] bg-[#F8FAFC]',
                                    Number(item.expired) === 1 ? 'opacity-60' : '',
                                ]"
                                @click="handleSelect(item)">
                                <view
                                    class="flex h-[84rpx] w-[84rpx] flex-shrink-0 items-center justify-center rounded-[28rpx] bg-primary shadow-[0_6rpx_20rpx_rgba(37,99,235,0.22)]">
                                    <image :src="usersIcon" class="h-[40rpx] w-[40rpx]" />
                                </view>
                                <view class="min-w-0 flex-1">
                                    <view class="mb-[6rpx] flex min-w-0 items-center gap-[12rpx]">
                                        <text class="block min-w-0 truncate text-[30rpx] font-bold text-[#0F172A]">
                                            {{ item.name }}
                                        </text>
                                        <view
                                            class="flex-shrink-0 rounded-full px-[14rpx] py-[4rpx] text-[20rpx] font-bold"
                                            :class="roleTagClass(item.role)">
                                            {{ roleLabel(item.role) }}
                                        </view>
                                    </view>
                                    <text class="block text-[22rpx] text-[#94A3B8]">
                                        团队算力
                                        {{
                                            formatTokens(
                                                item.is_owner === 1 ? item.owner_tokens : item.team_tokens
                                            )
                                        }}
                                        ·
                                        {{
                                            item.is_owner === 1
                                                ? "我创建的"
                                                : Number(item.expired) === 1
                                                  ? "已过期"
                                                  : "已加入"
                                        }}
                                    </text>
                                </view>
                                <view
                                    v-if="Number(item.expired) === 1"
                                    class="flex-shrink-0 rounded-full bg-[#F1F5F9] px-[16rpx] py-[6rpx] text-[20rpx] font-bold text-[#94A3B8]">
                                    已过期
                                </view>
                                <view
                                    v-else-if="item.is_current === 1"
                                    class="flex h-[36rpx] w-[36rpx] flex-shrink-0 items-center justify-center rounded-full bg-primary">
                                    <u-icon name="checkmark" color="#fff" size="18" />
                                </view>
                            </view>

                            <view
                                v-if="!teams.length"
                                class="rounded-[28rpx] bg-[#F8FAFC] px-[28rpx] py-[48rpx] text-center text-[26rpx] text-[#94A3B8]">
                                暂无团队，可创建或加入
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view
                    class="grid flex-shrink-0 grid-cols-2 gap-[20rpx] border-[0] border-t-[2rpx] border-solid border-[#F1F5F9] pt-[24rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]">
                    <view
                        class="flex items-center justify-center gap-[12rpx] rounded-[28rpx] border-[2rpx] border-solid border-[#E2E8F0] bg-[#F1F5F9] py-[26rpx] text-[28rpx] font-semibold text-[#475569]"
                        @click="emit('create')">
                        <u-icon name="plus" color="#2563EB" size="28" />
                        <text>创建团队</text>
                    </view>
                    <view
                        class="flex items-center justify-center gap-[12rpx] rounded-[28rpx] bg-primary py-[26rpx] text-[28rpx] font-semibold text-white shadow-[0_8rpx_28rpx_rgba(37,99,235,0.3)]"
                        @click="emit('join')">
                        <image :src="logInIcon" class="h-[30rpx] w-[30rpx]" />
                        <text>加入团队</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
type TeamItem = {
    team_id: number;
    name: string;
    role: number;
    is_owner: number;
    is_current: number;
    owner_tokens?: number | string;
    team_tokens?: number | string;
    /** 成员资格是否已过期(后端 myTeams 下发) */
    expired?: number;
};

defineProps<{
    modelValue: boolean;
    teams: TeamItem[];
    usersIcon: string;
    logInIcon: string;
    /** 当前是否已在个人空间 */
    isPersonalCurrent?: boolean;
    /** 个人算力余额 */
    personalTokens?: number | string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "select", team: TeamItem): void;
    (e: "create"): void;
    (e: "join"): void;
}>();

const roleLabel = (role: number) => {
    if (role === 2) return "创始人";
    if (role === 3) return "管理员";
    return "成员";
};

const roleTagClass = (role: number) => {
    if (role === 2) return "bg-[#DBEAFE] text-[#1D4ED8]";
    if (role === 3) return "bg-[#EDE9FE] text-[#6D28D9]";
    return "bg-[#F1F5F9] text-[#64748B]";
};

const formatTokens = (value: number | string | undefined) => {
    const n = Number(value ?? 0);
    if (!Number.isFinite(n)) return "0";
    return n.toFixed(2);
};

const handleSelect = (item: TeamItem) => {
    emit("select", item);
};

const handleSelectPersonal = () => {
    emit("select", {
        team_id: 0,
        name: "个人空间",
        role: 0,
        is_owner: 0,
        is_current: 0,
    });
};
</script>
