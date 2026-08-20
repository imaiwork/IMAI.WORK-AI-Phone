<template>
    <view class="space-y-[16rpx]">
        <view v-for="panel in privatePanelList" :key="panel.key" class="prv-acc">
            <view class="prv-acc-hd" @click.stop="togglePrivatePanel(panel.key)">
                <view class="prv-icon">
                    <image :src="panel.icon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                </view>
                <text class="prv-acc-title">{{ panel.title }}</text>
                <view class="ml-auto mr-[20rpx] shrink-0" @click.stop>
                    <u-switch v-model="privatePanelStatus[panel.key]" :size="34" inactive-color="#E5E7EB" />
                </view>
                <u-icon
                    name="arrow-down"
                    color="#C0C8D8"
                    size="22"
                    :custom-style="{
                        transform: openPrivatePanel === panel.key ? 'rotate(180deg)' : 'rotate(0deg)',
                        transition: 'transform 0.28s cubic-bezier(.4,0,.2,1)',
                    }"></u-icon>
            </view>
            <view v-if="openPrivatePanel === panel.key" class="prv-acc-inner">
                <!-- 加好友 -->
                <template v-if="panel.key === 'add'">
                    <view class="flex items-start justify-between mb-[8rpx]">
                        <view>
                            <text class="block private-title">客资线索库</text>
                            <text class="block private-desc">自动提取线索发起好友申请</text>
                        </view>
                        <text class="lead-badge">待添加 {{ privateClueCount }}人</text>
                    </view>
                    <view class="prv-divider"></view>
                    <text class="private-title mb-[20rpx]">好友验证申请话术</text>
                    <textarea v-model="friendApplyText" class="private-textarea" placeholder="请输入好友验证申请话术" />
                </template>

                <!-- 自动加群 -->
                <template v-else-if="panel.key === 'group'">
                    <view class="mb-[32rpx]">
                        <text class="private-title mb-[20rpx]">加群触发模式</text>
                        <view class="trigger-seg">
                            <button
                                class="plain-btn trigger-seg-btn"
                                :class="{ active: groupTriggerMode === 1 }"
                                @click.stop="setGroupTriggerMode(1)">
                                AI 意图识别
                            </button>
                            <button
                                class="plain-btn trigger-seg-btn"
                                :class="{ active: groupTriggerMode === 2 }"
                                @click.stop="setGroupTriggerMode(2)">
                                自定义触发词
                            </button>
                        </view>
                        <view v-if="groupTriggerMode === 1" class="trigger-note">
                            <u-icon name="info-circle" color="#2563EB" size="24"></u-icon>
                            <text class="flex-1">AI 自动识别客户对话中的拉群意图，无需关键词配置。</text>
                        </view>
                        <view v-else class="mt-[20rpx]">
                            <view
                                v-if="groupKeywords.length"
                                class="flex items-center justify-end mb-[16rpx]">
                                <view
                                    class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#FEF2F2] border-[2rpx] border-solid border-[#FECACA]"
                                    @click.stop="clearGroupKeywords">
                                    <u-icon name="trash" size="22" color="#EF4444" />
                                    <text class="text-xs font-semibold text-[#EF4444]">一键清空</text>
                                </view>
                            </view>
                            <view class="tracking-kw-wrap">
                                <view
                                    v-if="!groupKeywords.length"
                                    class="tracking-empty"
                                    @click.stop="openGroupKeywordPopup()">
                                    暂无触发词，点击添加
                                </view>
                                <template v-else>
                                    <view
                                        v-for="(word, index) in visibleGroupKeywords"
                                        :key="`${word}-${index}`"
                                        class="tracking-kw-tag"
                                        @click.stop="openGroupKeywordPopup(index)">
                                        <text class="tracking-kw-text">{{ word }}</text>
                                        <text
                                            class="tracking-kw-remove"
                                            @click.stop="removeKeyword(groupKeywords, word)">
                                            ×
                                        </text>
                                    </view>
                                </template>
                                <button class="plain-btn tracking-kw-add" @click.stop="openGroupKeywordPopup()">
                                    + 添加
                                </button>
                                <button
                                    v-if="hiddenGroupKeywordCount && !showGroupKeywordsMore"
                                    class="plain-btn tracking-kw-more"
                                    @click.stop="showGroupKeywordsMore = true">
                                    {{ `+${hiddenGroupKeywordCount} 个` }}
                                    <u-icon name="arrow-down" color="#6B7280" size="18"></u-icon>
                                </button>
                                <button
                                    v-if="showGroupKeywordsMore && groupKeywords.length > GROUP_KEYWORD_VISIBLE_LIMIT"
                                    class="plain-btn tracking-kw-more"
                                    @click.stop="showGroupKeywordsMore = false">
                                    收起
                                    <u-icon name="arrow-up" color="#6B7280" size="18"></u-icon>
                                </button>
                            </view>
                            <text class="tracking-tip">客户聊天中命中以下任一关键词时触发自动拉群</text>
                        </view>
                    </view>
                    <view class="prv-divider"></view>
                    <view class="flex items-center justify-between mb-[8rpx]">
                        <view class="flex items-center gap-[12rpx]">
                            <view class="mini-icon orange">
                                <image :src="userOrangeIcon" mode="aspectFit" class="w-[24rpx] h-[24rpx]" />
                            </view>
                            <text class="private-title">
                                指定拉入的销售微信
                                <text class="text-[#9CA3AF] font-normal">(真人)</text>
                            </text>
                        </view>
                        <text class="text-[24rpx] text-[#9CA3AF]"> {{ saleWechatList.length }} / 5 </text>
                    </view>
                    <text class="private-desc"> 机器人建群后，会自动将其拉入群聊中作为主理人。 </text>
                    <view class="cfg-inp-row mt-[20rpx] mb-[20rpx]">
                        <input v-model="saleWechatInput" class="cfg-inp" placeholder="请输入微信号并点击添加" />
                        <button class="plain-btn cfg-add-btn" @click.stop="addSaleWechat">添加</button>
                    </view>
                    <view v-if="saleWechatList.length" class="detail-kw-wrap mb-[20rpx]">
                        <view
                            v-for="(wechat, index) in saleWechatList"
                            :key="wechat"
                            class="detail-kw-tag editable"
                            @click.stop="openSaleWechatPopup(index)">
                            {{ wechat }}
                            <text class="rm" @click.stop="removeKeyword(saleWechatList, wechat)"> × </text>
                        </view>
                    </view>
                    <view class="warning-box">
                        <u-icon name="info-circle" color="#F97316" size="24"></u-icon>
                        <text class="flex-1 text-[24rpx] text-[#F97316] leading-[1.6]">
                            强烈建议输入【微信号】或在机器人端统一设置好【备注名】，避免因昵称包含特殊符号导致拉人失败。
                        </text>
                    </view>

                    <view class="prv-divider"></view>
                    <view class="flex items-center justify-between mb-[16rpx]">
                        <text class="private-title">群名称模板</text>
                        <text class="text-[24rpx] text-[#9CA3AF]"> {{ groupNameTemplate.length }} / 32 </text>
                    </view>
                    <textarea v-model="groupNameTemplate" maxlength="32" class="private-textarea" />
                    <view class="flex flex-wrap gap-[14rpx] mt-[20rpx] mb-[32rpx]">
                        <button
                            v-for="tpl in groupNameTemplateTags"
                            :key="tpl"
                            class="plain-btn prv-ins-btn"
                            @click.stop="insertTemplate('groupName', tpl)">
                            + 插入{{ tpl.replace("{", "").replace("}", "") }}
                        </button>
                    </view>

                    <view class="prv-divider"></view>
                    <view class="setting-block mb-[20rpx]">
                        <text class="private-title">建群后自动发送欢迎语</text>
                        <view class="shrink-0" @click.stop>
                            <u-switch v-model="groupWelcomeEnabled" :size="34" inactive-color="#E5E7EB" />
                        </view>
                    </view>
                    <textarea v-model="groupWelcomeText" class="private-textarea" />
                    <view class="flex flex-wrap gap-[14rpx] mt-[20rpx] mb-[32rpx]">
                        <button class="plain-btn prv-ins-btn" @click.stop="insertTemplate('welcome', '{客户名}')">
                            + 插入客户名
                        </button>
                        <button class="plain-btn prv-ins-btn" @click.stop="insertTemplate('welcome', '@客户')">
                            + @客户
                        </button>
                    </view>

                    <view class="prv-divider"></view>
                    <view class="setting-block">
                        <view>
                            <text class="block private-title">携带历史聊天记录</text>
                            <text class="block private-desc leading-[1.5]">
                                建群后，自动将拉群前的单聊历史记录同步转发至新群聊中
                            </text>
                        </view>
                        <view class="shrink-0" @click.stop>
                            <u-switch v-model="carryHistoryEnabled" :size="34" inactive-color="#E5E7EB" />
                        </view>
                    </view>

                </template>

                <!-- 朋友圈 -->
                <template v-else>
                    <text class="private-title mb-[16rpx]">朋友圈每日互动量</text>
                    <view class="circle-count-row">
                        <text class="text-[24rpx] text-[#6B7280]"> 每天点赞/评论好友朋友圈的次数 </text>
                        <view class="flex items-center gap-[20rpx]">
                            <button class="plain-btn circle-count-btn" @click.stop="changeCircleCount(-5)">-</button>
                            <text class="circle-count">{{ circleCount }}</text>
                            <button class="plain-btn circle-count-btn primary" @click.stop="changeCircleCount(5)">
                                +
                            </button>
                        </view>
                    </view>
                    <button class="plain-btn circle-setting-btn" @click.stop="openCircleSetting">
                        <text>朋友圈设置调整</text>
                        <u-icon name="arrow-right" color="#2F73F6" size="22"></u-icon>
                    </button>
                </template>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { PrivateChannelsKey } from "../../../hooks/usePrivateChannels";

const ctx = inject(PrivateChannelsKey)!;

const {
    privatePanelList,
    privatePanelStatus,
    openPrivatePanel,
    privateClueCount,
    friendApplyText,
    saleWechatList,
    saleWechatInput,
    groupNameTemplate,
    groupNameTemplateTags,
    groupWelcomeEnabled,
    groupWelcomeText,
    carryHistoryEnabled,
    groupTriggerMode,
    groupKeywords,
    userOrangeIcon,
    circleCount,
    togglePrivatePanel,
    removeKeyword,
    addSaleWechat,
    openSaleWechatPopup,
    insertTemplate,
    setGroupTriggerMode,
    clearGroupKeywords,
    openGroupKeywordPopup,
    changeCircleCount,
    openCircleSetting,
} = ctx;

const GROUP_KEYWORD_VISIBLE_LIMIT = 3;
const showGroupKeywordsMore = ref(false);
const visibleGroupKeywords = computed(() =>
    showGroupKeywordsMore.value ? groupKeywords.value : groupKeywords.value.slice(0, GROUP_KEYWORD_VISIBLE_LIMIT),
);
const hiddenGroupKeywordCount = computed(() =>
    Math.max(groupKeywords.value.length - GROUP_KEYWORD_VISIBLE_LIMIT, 0),
);

watch(groupTriggerMode, () => {
    showGroupKeywordsMore.value = false;
});

watch(
    () => groupKeywords.value.length,
    (len) => {
        if (!len) showGroupKeywordsMore.value = false;
    },
);
</script>

<style scoped lang="scss">
.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.cfg-inp {
    @apply w-full min-h-[88rpx] bg-[#F9FAFB] border-[3rpx] border-solid border-[transparent] rounded-[24rpx] px-[28rpx] text-[24rpx] text-[#1d2129] box-border;
}

.cfg-inp-row {
    @apply flex gap-[16rpx] items-stretch;

    .cfg-inp {
        @apply flex-1;
    }
}

.cfg-add-btn {
    @apply bg-[#2f73f6] text-white text-[26rpx] font-bold rounded-[24rpx] px-[36rpx] min-h-[88rpx] flex items-center justify-center shrink-0;
}

.setting-block {
    @apply flex items-center justify-between gap-[24rpx];
}

.trigger-seg {
    @apply flex bg-[#f3f4f6] rounded-[20rpx] p-[6rpx] gap-[6rpx];
}

.trigger-seg-btn {
    @apply flex-1 min-h-[64rpx] rounded-[16rpx] text-[24rpx] font-bold text-[#9ca3af] flex items-center justify-center text-center;

    &.active {
        @apply bg-white text-primary;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
    }
}

.trigger-note {
    @apply mt-[20rpx] bg-[#eff6ff] border-[2rpx] border-solid border-[#bfdbfe] rounded-[20rpx] py-[20rpx] px-[24rpx] flex gap-[12rpx] items-start text-[24rpx] text-[#1e40af] leading-[1.55];
}

.tracking-kw-wrap {
    @apply flex flex-wrap items-center gap-[14rpx];
}

.tracking-kw-tag {
    @apply inline-flex items-center gap-[10rpx] min-h-[56rpx] max-w-full rounded-full bg-[#f8faff] border-[2rpx] border-solid border-[#e7eefc] py-[10rpx] pl-[22rpx] pr-[14rpx] text-[24rpx] font-medium text-[#1d2129] box-border;
}

.tracking-kw-text {
    @apply min-w-0 break-all;
}

.tracking-kw-remove {
    @apply w-[28rpx] h-[28rpx] rounded-full bg-[#eef2f8] text-[#9ca3af] text-[22rpx] leading-[28rpx] text-center shrink-0;
}

.tracking-empty {
    @apply w-full min-h-[88rpx] rounded-[24rpx] bg-white border-[2rpx] border-dashed border-[#dde6f5] text-[#9ca3af] text-[24rpx] flex items-center justify-center;
}

.tracking-kw-add,
.tracking-kw-more {
    @apply inline-flex items-center gap-[8rpx] min-h-[56rpx] rounded-full py-[10rpx] px-[24rpx] text-[24rpx] font-medium box-border;
}

.tracking-kw-add {
    @apply bg-white border-[2rpx] border-dashed border-[#bad4ff] text-[#2f73f6];
}

.tracking-kw-more {
    @apply bg-[#f3f4f6] border-[2rpx] border-solid border-[#e5e7eb] text-[#6b7280];
}

.tracking-tip {
    @apply block text-[24rpx] text-[#9ca3af] mt-[20rpx];
}

.private-title {
    @apply block text-[26rpx] font-bold text-[#1d2129];
}

.private-desc {
    @apply block text-[22rpx] text-[#9ca3af] mt-[4rpx];
}

.private-textarea {
    @apply w-full min-h-[300rpx] bg-[#F4F7FC] rounded-[24rpx] py-[30rpx] px-[32rpx] text-[26rpx] text-[#1d2129] leading-[1.7] box-border;
}

.detail-kw-wrap {
    @apply flex flex-wrap gap-[16rpx];
}

.detail-kw-tag {
    @apply inline-flex items-center gap-[10rpx] text-xs text-[#1d2129] font-medium bg-white border-[3rpx] border-solid border-[#e5e7eb] py-[12rpx] px-[24rpx] rounded-full;

    &.editable {
        @apply pr-[18rpx];
    }

    .rm {
        @apply leading-[0];
    }
}

.detail-kw-add {
    @apply inline-flex items-center gap-[8rpx] text-[24rpx] font-medium py-[12rpx] px-[26rpx] rounded-full text-[#2f73f6] border-[3rpx] border-dashed border-[#93bbfd];
}

// private-only
.prv-acc {
    @apply bg-white rounded-[28rpx] border-[3rpx] border-solid border-[#e5e7eb] overflow-hidden;
}

.prv-acc-hd {
    @apply flex items-center gap-[20rpx] py-[26rpx] px-[28rpx];
}

.prv-acc-inner {
    @apply pt-[24rpx] px-[28rpx] pb-[32rpx] border-0 border-t-[2rpx] border-solid border-[#f0f4fa];
}

.prv-icon {
    @apply w-[68rpx] h-[68rpx] rounded-[22rpx] bg-[#e8f8f0] flex items-center justify-center shrink-0;
}

.prv-acc-title {
    @apply text-[28rpx] font-bold text-[#1d2129];
}

.prv-divider {
    @apply h-[2rpx] bg-[#f0f4fa] my-[24rpx];
}

.prv-ins-btn {
    @apply text-[24rpx] font-medium text-[#2f73f6] bg-[#ebf2ff] rounded-full py-[12rpx] px-[28rpx];
}

.lead-badge {
    @apply text-[24rpx] font-semibold text-[#2f73f6] bg-[#ebf2ff] rounded-full py-[8rpx] px-[24rpx] whitespace-nowrap shrink-0;
}

.mini-icon {
    @apply w-[44rpx] h-[44rpx] rounded-[14rpx] flex items-center justify-center;

    &.orange {
        @apply bg-[#fff3e0];
    }
}

.warning-box {
    @apply bg-[#fff8f0] border-[3rpx] border-solid border-[#fed7aa] rounded-[24rpx] py-[22rpx] px-[26rpx] mb-[32rpx] flex gap-[16rpx] items-start;
}

.circle-count-row {
    @apply flex items-center justify-between gap-[20rpx] bg-[#f9fafb] rounded-[24rpx] py-[24rpx] px-[32rpx];
}

.circle-count-btn {
    @apply w-[56rpx] h-[56rpx] rounded-full bg-white border-[2rpx] border-solid border-[#e5e7eb] text-[#6b7280] text-[28rpx] flex items-center justify-center;

    &.primary {
        @apply bg-[#2f73f6] text-white border-[#2f73f6];
    }
}

.circle-count {
    @apply w-[56rpx] text-center text-[28rpx] font-bold text-[#1d2129];
}

.circle-setting-btn {
    @apply mt-[24rpx] w-full min-h-[84rpx] rounded-[24rpx] bg-[#ebf2ff] text-[#2f73f6] text-[26rpx] font-bold flex items-center justify-center gap-[8rpx] border-[2rpx] border-solid border-[#bad4ff];
}
</style>
