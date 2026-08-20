<template>
    <view>
        <!-- ① 社媒平台：评论 + 私信共用回复配置；智能体按平台独立 -->
        <view class="cs-block">
            <view class="cs-sec-title">
                <text>社媒平台</text>
                <text class="cs-sec-sub">（适用评论和私信）</text>
            </view>

            <!-- 平台选择器：3 选 1，激活态描蓝；已选过智能体或填过话术的平台右上角带圆点 -->
            <view class="cs-plat-grid mb-[24rpx]">
                <view
                    v-for="plat in csSocialPlatforms"
                    :key="plat.key"
                    class="cs-plat-btn"
                    :class="{ active: csSocial.activePlatform === plat.key }"
                    @click.stop="csSocial.activePlatform = plat.key">
                    <text class="cs-plat-label">{{ plat.label }}</text>
                    <view
                        v-if="
                            csSocial.platforms[plat.key].dmAgentId ||
                            csSocial.platforms[plat.key].commentAgentId ||
                            csSocial.platforms[plat.key].dmScripts.length ||
                            csSocial.platforms[plat.key].commentScripts.length
                        "
                        class="cs-plat-dot"></view>
                </view>
            </view>

            <view class="cs-seg mb-[24rpx]">
                <view
                    class="cs-seg-btn"
                    :class="{ active: csSocialCurrent.replyMode === CsReplyMode.AI }"
                    @click.stop="csSocialCurrent.replyMode = CsReplyMode.AI">
                    智能体自动回复
                </view>
                <view
                    class="cs-seg-btn"
                    :class="{ active: csSocialCurrent.replyMode === CsReplyMode.FIXED }"
                    @click.stop="csSocialCurrent.replyMode = CsReplyMode.FIXED">
                    预设话术
                </view>
            </view>

            <view v-if="csSocialCurrent.replyMode === CsReplyMode.AI">
                <text class="field-label">
                    选择私信智能体（当前：{{
                        csSocialPlatforms.find((p) => p.key === csSocial.activePlatform)?.label
                    }}）
                </text>
                <view
                    class="cfg-inp picker-field flex items-center justify-between"
                    :class="{ 'cfg-inp-warn': csSocialCurrent.dmAgentStatus !== 'ok' }"
                    @click.stop="emit('open-agent', 'social-dm')">
                    <text
                        class="text-[24rpx] line-clamp-1"
                        :class="agentNameClass(csSocialCurrent.dmAgentId, csSocialCurrent.dmAgentStatus)">
                        {{
                            formatBoundAgentLabel(csSocialCurrent.dmAgentName, csSocialCurrent.dmAgentStatus || "ok")
                        }}
                    </text>
                    <u-icon name="arrow-right" color="#C0C8D8" size="22"></u-icon>
                </view>
                <text
                    v-if="csSocialCurrent.dmAgentId && csSocialCurrent.dmAgentStatus !== 'ok'"
                    class="cs-agent-warn">
                    {{ csSocialCurrent.dmAgentStatusText || "请重新绑定智能体" }}
                </text>
                <text class="field-label mt-[24rpx]">选择评论智能体</text>
                <view
                    class="cfg-inp picker-field flex items-center justify-between"
                    :class="{ 'cfg-inp-warn': csSocialCurrent.commentAgentStatus !== 'ok' }"
                    @click.stop="emit('open-agent', 'social-comment')">
                    <text
                        class="text-[24rpx] line-clamp-1"
                        :class="agentNameClass(csSocialCurrent.commentAgentId, csSocialCurrent.commentAgentStatus)">
                        {{
                            formatBoundAgentLabel(
                                csSocialCurrent.commentAgentName,
                                csSocialCurrent.commentAgentStatus || "ok"
                            )
                        }}
                    </text>
                    <u-icon name="arrow-right" color="#C0C8D8" size="22"></u-icon>
                </view>
                <text
                    v-if="csSocialCurrent.commentAgentId && csSocialCurrent.commentAgentStatus !== 'ok'"
                    class="cs-agent-warn">
                    {{ csSocialCurrent.commentAgentStatusText || "请重新绑定智能体" }}
                </text>
            </view>
            <view v-else>
                <text class="field-label">私信预设话术</text>
                <cs-script-list
                    :scripts="csSocialCurrent.dmScripts"
                    :maxlength="100"
                    :max-items="10"
                    @add="(v) => emit('add-script', { scripts: csSocialCurrent.dmScripts }, v, 10)"
                    @edit="(i, max) => emit('open-script-editor', csSocialCurrent.dmScripts, i, '社媒私信回复', max)"
                    @remove="(i) => emit('remove-script', { scripts: csSocialCurrent.dmScripts }, i)"
                    @move="(i, dir) => emit('move-script', { scripts: csSocialCurrent.dmScripts }, i, dir)" />
                <text class="field-label mt-[24rpx]">评论预设话术</text>
                <cs-script-list
                    :scripts="csSocialCurrent.commentScripts"
                    :maxlength="100"
                    :max-items="10"
                    @add="(v) => emit('add-script', { scripts: csSocialCurrent.commentScripts }, v, 10)"
                    @edit="
                        (i, max) => emit('open-script-editor', csSocialCurrent.commentScripts, i, '社媒评论回复', max)
                    "
                    @remove="(i) => emit('remove-script', { scripts: csSocialCurrent.commentScripts }, i)"
                    @move="(i, dir) => emit('move-script', { scripts: csSocialCurrent.commentScripts }, i, dir)" />
            </view>

            <view class="cs-divider"></view>
            <view class="setting-block">
                <view class="flex-1 pr-[24rpx]">
                    <text class="block setting-title">评论区仅点赞</text>
                    <text class="block setting-desc"> 开启后，当前平台评论区只点赞不评论，私信仍可正常回复 </text>
                </view>
                <view class="shrink-0" @click.stop>
                    <u-switch v-model="csSocialCurrent.commentLikeOnly" :size="30" inactive-color="#E5E7EB" />
                </view>
            </view>
        </view>

        <!-- ② 微信私信接管 -->
        <view class="cs-block">
            <view class="cs-sec-title"><text>微信私信接管</text></view>
            <view class="cs-seg mb-[24rpx]">
                <view
                    class="cs-seg-btn"
                    :class="{ active: csWechat.replyMode === CsReplyMode.AI }"
                    @click.stop="csWechat.replyMode = CsReplyMode.AI">
                    智能体自动回复
                </view>
                <view
                    class="cs-seg-btn"
                    :class="{ active: csWechat.replyMode === CsReplyMode.FIXED }"
                    @click.stop="csWechat.replyMode = CsReplyMode.FIXED">
                    预设话术
                </view>
            </view>
            <view v-if="csWechat.replyMode === CsReplyMode.AI">
                <text class="field-label">选择智能体</text>
                <view
                    class="cfg-inp picker-field flex items-center justify-between"
                    :class="{ 'cfg-inp-warn': csWechat.agentStatus !== 'ok' }"
                    @click.stop="emit('open-agent', 'wechat')">
                    <text
                        class="text-[24rpx] line-clamp-1"
                        :class="agentNameClass(csWechat.agentId, csWechat.agentStatus)">
                        {{ formatBoundAgentLabel(csWechat.agentName, csWechat.agentStatus || "ok") }}
                    </text>
                    <u-icon name="arrow-right" color="#C0C8D8" size="22"></u-icon>
                </view>
                <text v-if="csWechat.agentId && csWechat.agentStatus !== 'ok'" class="cs-agent-warn">
                    {{ csWechat.agentStatusText || "请重新绑定智能体" }}
                </text>
            </view>
            <view v-else>
                <text class="field-label">预设话术内容</text>
                <cs-script-list
                    :scripts="csWechat.scripts"
                    :maxlength="200"
                    :max-items="10"
                    @add="(v) => emit('add-script', csWechat, v, 10)"
                    @edit="(i, max) => emit('open-script-editor', csWechat.scripts, i, '微信私信', max)"
                    @remove="(i) => emit('remove-script', csWechat, i)"
                    @move="(i, dir) => emit('move-script', csWechat, i, dir)" />
            </view>
        </view>

        <!-- ③ 朋友圈互动接管 -->
        <view class="cs-block">
            <view class="cs-sec-title"><text>朋友圈互动接管</text></view>
            <view class="cs-seg mb-[24rpx]">
                <view
                    class="cs-seg-btn"
                    :class="{ active: csMoments.action === MomentsAction.LIKE_ONLY }"
                    @click.stop="csMoments.action = MomentsAction.LIKE_ONLY">
                    仅点赞
                </view>
                <view
                    class="cs-seg-btn"
                    :class="{ active: csMoments.action === MomentsAction.COMMENT_ONLY }"
                    @click.stop="csMoments.action = MomentsAction.COMMENT_ONLY">
                    仅评论
                </view>
                <view
                    class="cs-seg-btn"
                    :class="{ active: csMoments.action === MomentsAction.BOTH }"
                    @click.stop="csMoments.action = MomentsAction.BOTH">
                    评论+点赞
                </view>
            </view>

            <view v-if="csMoments.action === MomentsAction.LIKE_ONLY" class="cs-tip">
                当前模式下，系统仅执行点赞操作，不发表评论
            </view>
            <template v-else>
                <text class="field-label">回复方式</text>
                <view class="cs-seg mb-[24rpx]">
                    <view
                        class="cs-seg-btn"
                        :class="{ active: csMoments.replyMode === CsReplyMode.AI }"
                        @click.stop="csMoments.replyMode = CsReplyMode.AI">
                        AI 智能
                    </view>
                    <view
                        class="cs-seg-btn"
                        :class="{ active: csMoments.replyMode === CsReplyMode.FIXED }"
                        @click.stop="csMoments.replyMode = CsReplyMode.FIXED">
                        固定话术
                    </view>
                </view>
                <view v-if="csMoments.replyMode === CsReplyMode.AI">
                    <text class="field-label">选择智能体</text>
                    <view
                        class="cfg-inp picker-field flex items-center justify-between"
                        :class="{ 'cfg-inp-warn': csMoments.agentStatus !== 'ok' }"
                        @click.stop="emit('open-agent', 'moments')">
                        <text
                            class="text-[24rpx] line-clamp-1"
                            :class="agentNameClass(csMoments.agentId, csMoments.agentStatus)">
                            {{ formatBoundAgentLabel(csMoments.agentName, csMoments.agentStatus || "ok") }}
                        </text>
                        <u-icon name="arrow-right" color="#C0C8D8" size="22"></u-icon>
                    </view>
                    <text v-if="csMoments.agentId && csMoments.agentStatus !== 'ok'" class="cs-agent-warn">
                        {{ csMoments.agentStatusText || "请重新绑定智能体" }}
                    </text>
                </view>
                <view v-else>
                    <text class="field-label">话术列表</text>
                    <cs-script-list
                        :scripts="csMoments.scripts"
                        :maxlength="50"
                        :max-items="8"
                        @add="(v) => emit('add-script', csMoments, v, 8)"
                        @edit="(i, max) => emit('open-script-editor', csMoments.scripts, i, '朋友圈', max)"
                        @remove="(i) => emit('remove-script', csMoments, i)"
                        @move="(i, dir) => emit('move-script', csMoments, i, dir)" />
                </view>
            </template>
        </view>

        <!-- ④ 截流转化 -->
        <view class="cs-block">
            <view class="cs-sec-title"><text>截流转化</text></view>
            <view class="cs-jl-acc">
                <view
                    class="cs-jl-row"
                    @click.stop="emit('update:csShutoffOpen', csShutoffOpen === 'comment' ? '' : 'comment')">
                    <text class="cs-jl-name">截流评论</text>
                    <view class="cs-jl-val">
                        <text>{{ csShutoffComment.scripts.length }}条</text>
                        <u-icon
                            name="arrow-down"
                            color="#2F73F6"
                            size="22"
                            :custom-style="{
                                transform: csShutoffOpen === 'comment' ? 'rotate(180deg)' : 'rotate(0deg)',
                                transition: 'transform 0.25s',
                            }"></u-icon>
                    </view>
                </view>
                <view v-show="csShutoffOpen === 'comment'" class="pt-[8rpx]">
                    <cs-script-list
                        :scripts="csShutoffComment.scripts"
                        :maxlength="100"
                        :max-items="15"
                        @add="(v) => emit('add-script', csShutoffComment, v, 15)"
                        @edit="(i, max) => emit('open-script-editor', csShutoffComment.scripts, i, '截流评论', max)"
                        @remove="(i) => emit('remove-script', csShutoffComment, i)"
                        @move="(i, dir) => emit('move-script', csShutoffComment, i, dir)" />
                </view>
            </view>
            <view class="cs-jl-acc">
                <view class="cs-jl-row" @click.stop="emit('update:csShutoffOpen', csShutoffOpen === 'dm' ? '' : 'dm')">
                    <text class="cs-jl-name">截流私信</text>
                    <view class="cs-jl-val">
                        <text>{{ csShutoffMsg.scripts.length }}条</text>
                        <u-icon
                            name="arrow-down"
                            color="#2F73F6"
                            size="22"
                            :custom-style="{
                                transform: csShutoffOpen === 'dm' ? 'rotate(180deg)' : 'rotate(0deg)',
                                transition: 'transform 0.25s',
                            }"></u-icon>
                    </view>
                </view>
                <view v-show="csShutoffOpen === 'dm'" class="pt-[8rpx]">
                    <cs-script-list
                        :scripts="csShutoffMsg.scripts"
                        :maxlength="200"
                        :max-items="15"
                        @add="(v) => emit('add-script', csShutoffMsg, v, 15)"
                        @edit="(i, max) => emit('open-script-editor', csShutoffMsg.scripts, i, '截流私信', max)"
                        @remove="(i) => emit('remove-script', csShutoffMsg, i)"
                        @move="(i, dir) => emit('move-script', csShutoffMsg, i, dir)" />
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import CsScriptList from "../../cs-script-list.vue";
import {
    CsReplyMode,
    MomentsAction,
    formatBoundAgentLabel,
    type AgentBindStatus,
    type SocialPlatform,
} from "../../../hooks/useCustomerServiceConfig";

const agentNameClass = (agentId: string, status: AgentBindStatus = "ok") => {
    if (status && status !== "ok") return "text-[#E34D59]";
    return agentId ? "text-[#1D2129]" : "text-[#C0C8D8]";
};

interface SocialPlatformMeta {
    key: SocialPlatform;
    label: string;
    type: number;
}

// 父层把 hook 返回的 reactive 状态、计算属性以 props 形式注入；
// 子组件只负责 view 渲染，所有操作通过 emit 回流到父层处理。
type AgentTarget = "social-dm" | "social-comment" | "wechat" | "moments";

defineProps<{
    csSocial: any;
    csSocialPlatforms: SocialPlatformMeta[];
    csSocialCurrent: any;
    csWechat: any;
    csMoments: any;
    csShutoffComment: any;
    csShutoffMsg: any;
    csShutoffOpen: "comment" | "dm" | "";
}>();

const emit = defineEmits<{
    (e: "update:csShutoffOpen", v: "comment" | "dm" | ""): void;
    (e: "open-agent", target: AgentTarget): void;
    (e: "add-script", target: any, value: string, max: number): void;
    (e: "open-script-editor", scripts: string[], index: number, title: string, max: number): void;
    (e: "remove-script", target: any, index: number): void;
    (e: "move-script", target: any, index: number, dir: "up" | "down"): void;
}>();
</script>

<style scoped lang="scss">
// 与主文件保持一致的基础工具类（scoped 不串）
.field-label {
    @apply block text-[20rpx] text-[#9ca3af] mb-[8rpx];
}

.cfg-inp {
    @apply w-full min-h-[88rpx] bg-[#F9FAFB] border-[3rpx] border-solid border-[transparent] rounded-[24rpx] px-[28rpx] text-[24rpx] text-[#1d2129] box-border;
}

.cfg-inp-warn {
    border-color: #fecaca !important;
    background: #fff7f7 !important;
}

.cs-agent-warn {
    @apply block text-[20rpx] text-[#E34D59] mt-[8rpx] leading-snug;
}

.picker-field {
    @apply flex items-center;
}

.setting-block {
    @apply flex items-center justify-between gap-[24rpx];
}

.setting-title {
    @apply block text-[26rpx] font-bold text-[#1d2129];
}

.setting-desc {
    @apply block text-[22rpx] text-[#9ca3af] mt-[4rpx];
}

// cs-* 专属（与主文件原版严格一致）
.cs-block {
    @apply bg-white rounded-[28rpx] border border-solid border-[#EAEEF4] p-[28rpx] mb-[24rpx];
    box-shadow: 0 4rpx 20rpx rgba(23, 43, 77, 0.04);

    &:last-child {
        @apply mb-0;
    }
}

.cs-sec-title {
    @apply flex items-center text-[30rpx] font-extrabold text-[#1D2129] mb-[24rpx];

    &::before {
        content: "";
        @apply w-[6rpx] h-[28rpx] rounded-full bg-primary mr-[16rpx] shrink-0;
    }
}

.cs-sec-sub {
    @apply text-[22rpx] font-normal text-[#9CA3AF] ml-[8rpx];
}

.cs-plat-grid {
    @apply grid grid-cols-3 gap-[16rpx];
}

.cs-plat-btn {
    @apply relative h-[88rpx] rounded-[20rpx] bg-[#F5F6F8] border border-solid border-[#E9EDF4] flex items-center justify-center;

    &.active {
        @apply bg-[#EFF5FF] border-primary;

        .cs-plat-label {
            @apply text-primary;
        }
    }
}

.cs-plat-label {
    @apply text-[26rpx] font-bold text-[#9CA3AF];
}

.cs-plat-dot {
    @apply absolute top-[10rpx] right-[10rpx] w-[12rpx] h-[12rpx] rounded-full bg-primary;
}

.cs-seg {
    @apply flex bg-[#f3f4f6] rounded-[20rpx] p-[6rpx] gap-[6rpx];
}

.cs-seg-btn {
    @apply flex-1 min-h-[64rpx] rounded-[16rpx] text-[24rpx] font-bold text-[#9ca3af] flex items-center justify-center text-center;

    &.active {
        @apply bg-white text-primary;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
    }
}

.cs-divider {
    @apply h-[2rpx] bg-[#EEF1F6] my-[28rpx];
}

.cs-tip {
    @apply bg-[#F4F6FA] rounded-[20rpx] py-[32rpx] px-[24rpx] text-center text-[24rpx] font-semibold text-[#9CA3AF] leading-[1.5];
}

.cs-jl-acc {
    & + .cs-jl-acc {
        @apply border-0 border-t border-solid border-[#EEF1F6] mt-[8rpx] pt-[8rpx];
    }
}

.cs-jl-row {
    @apply flex items-center justify-between py-[24rpx] active:opacity-70;
}

.cs-jl-name {
    @apply text-[28rpx] font-bold text-[#1D2129];
}

.cs-jl-val {
    @apply flex items-center gap-[6rpx] text-[26rpx] font-semibold text-primary;
}
</style>
