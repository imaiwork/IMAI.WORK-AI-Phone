<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="私聊接管"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />
        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="currentStep" @step="handleStep" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <scroll-view scroll-y v-show="currentStep === 1" class="h-full">
                <view class="px-4 pb-[200rpx] flex flex-col gap-[16rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">接管类型</text>
                        </view>
                        <view class="px-[28rpx] py-[24rpx]">
                            <view class="bg-[#F7F9FC] rounded-[20rpx] p-[6rpx] flex">
                                <view
                                    v-for="tab in TAKEOVER_TABS"
                                    :key="tab.value"
                                    class="flex-1 h-[72rpx] flex items-center justify-center rounded-[16rpx] transition-all duration-200"
                                    :class="
                                        takeoverType === tab.value
                                            ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,101,251,0.12)]'
                                            : ''
                                    "
                                    @click="switchTakeoverType(tab.value)">
                                    <text
                                        class="font-bold"
                                        :class="takeoverType === tab.value ? 'text-primary' : 'text-[#9CA3AF]'">
                                        {{ tab.label }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <template v-if="takeoverType === TakeoverTypeEnum.COMMENT">
                        <view
                            class="bg-[#EFF6FF] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[12rpx] border border-solid border-[#BFDBFE]">
                            <u-icon name="info-circle" color="#0065fb" size="28" class="flex-shrink-0 mt-[4rpx]" />
                            <text class="text-xs text-[#1D4ED8] leading-relaxed flex-1">
                                为防止重复执行，RPA 将通过<text class="font-bold">「点赞」</text
                                >动作标记已处理的评论，以下所有互动均默认附带点赞。
                            </text>
                        </view>
                        <view
                            v-if="false"
                            class="bg-[#FFF7ED] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[12rpx] border border-solid border-[#FED7AA]">
                            <u-icon name="play-right-fill" color="#F97316" size="28" class="flex-shrink-0 mt-[4rpx]" />
                            <text class="text-xs text-[#C2410C] leading-relaxed flex-1">
                                由于平台风控原因，<text class="font-bold">视频号</text
                                >平台目前仅支持执行点赞操作，不会进行评论回复。
                            </text>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">互动与回复规则</text>
                            </view>
                            <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[16rpx]">
                                <view
                                    class="rounded-[20rpx] p-[24rpx] border-[2rpx] border-solid transition-all duration-200"
                                    :class="
                                        commentRule == CommentRuleEnum.AI
                                            ? 'border-primary bg-[#F0F6FF]'
                                            : 'border-[#F0F2F5] bg-[#FAFAFA]'
                                    "
                                    @click="commentRule = CommentRuleEnum.AI">
                                    <view class="flex items-start gap-[20rpx]">
                                        <view
                                            class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="commentRule == 1 ? 'bg-[#DBEAFE]' : 'bg-[#F0F2F5]'">
                                            <u-icon
                                                name="android-fill"
                                                :color="commentRule == CommentRuleEnum.AI ? '#0065fb' : '#9CA3AF'"
                                                size="32" />
                                        </view>
                                        <view class="flex-1">
                                            <view class="flex items-center justify-between mb-[8rpx]">
                                                <text class="text-[28rpx] font-bold text-[#0D1117]">AI 智能回复</text>
                                                <view
                                                    class="w-[36rpx] h-[36rpx] rounded-full border-[3rpx] border-solid flex items-center justify-center"
                                                    :class="
                                                        commentRule == CommentRuleEnum.AI
                                                            ? 'border-primary bg-primary'
                                                            : 'border-[#D1D5DB] bg-white'
                                                    ">
                                                    <view
                                                        v-if="commentRule == CommentRuleEnum.AI"
                                                        class="w-[14rpx] h-[14rpx] rounded-full bg-white" />
                                                </view>
                                            </view>
                                            <text class="text-xs text-[#9CA3AF] block mb-[16rpx]"
                                                >调用智能体生成专属回复并点赞</text
                                            >
                                            <view
                                                v-if="commentRule == CommentRuleEnum.AI"
                                                class="bg-white rounded-[16rpx] px-[20rpx] h-[80rpx] flex items-center justify-between border border-solid border-[#E5E9F0]"
                                                @click.stop="handleChooseAgent">
                                                <text class="text-[#4B5563]">关联智能体</text>
                                                <view class="flex items-center gap-[8rpx]">
                                                    <text class="text-xs text-primary">
                                                        {{
                                                            selectedCommentAgent
                                                                ? selectedCommentAgent.name
                                                                : "选择智能体"
                                                        }}
                                                    </text>
                                                    <u-icon name="arrow-right" color="#0065fb" size="24" />
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    class="rounded-[20rpx] p-[24rpx] border-[2rpx] border-solid transition-all duration-200"
                                    :class="
                                        commentRule == CommentRuleEnum.FIXED
                                            ? 'border-primary bg-[#F0F6FF]'
                                            : 'border-[#F0F2F5] bg-[#FAFAFA]'
                                    "
                                    @click="commentRule = CommentRuleEnum.FIXED">
                                    <view class="flex items-start gap-[20rpx]">
                                        <view
                                            class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="
                                                commentRule == CommentRuleEnum.FIXED ? 'bg-[#DBEAFE]' : 'bg-[#F0F2F5]'
                                            ">
                                            <u-icon
                                                name="list"
                                                :color="commentRule == CommentRuleEnum.FIXED ? '#0065fb' : '#9CA3AF'"
                                                size="32" />
                                        </view>
                                        <view class="flex-1">
                                            <view class="flex items-center justify-between mb-[8rpx]">
                                                <text class="text-[28rpx] font-bold text-[#0D1117]">固定话术回复</text>
                                                <view
                                                    class="w-[36rpx] h-[36rpx] rounded-full border-[3rpx] border-solid flex items-center justify-center"
                                                    :class="
                                                        commentRule == CommentRuleEnum.FIXED
                                                            ? 'border-primary bg-primary'
                                                            : 'border-[#D1D5DB] bg-white'
                                                    ">
                                                    <view
                                                        v-if="commentRule == CommentRuleEnum.FIXED"
                                                        class="w-[14rpx] h-[14rpx] rounded-full bg-white" />
                                                </view>
                                            </view>
                                            <text class="text-xs text-[#9CA3AF] block mb-[16rpx]"
                                                >随机抽取一条预设话术并点赞</text
                                            >
                                            <view v-if="commentRule == CommentRuleEnum.FIXED" @click.stop>
                                                <view
                                                    v-if="formData.comment_scripts.length > 0"
                                                    class="flex items-center justify-between mb-[16rpx]">
                                                    <text class="text-xs text-[#9CA3AF]">
                                                        共
                                                        {{ formData.comment_scripts.length }} 条话术
                                                    </text>
                                                    <view
                                                        class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#FEF2F2] border border-solid border-[#FECACA]"
                                                        @click="handleClearAllScripts(ScriptTargetEnum.COMMENT)">
                                                        <u-icon name="trash" size="22" color="#EF4444" />
                                                        <text class="text-xs font-semibold text-[#EF4444]"
                                                            >一键删除</text
                                                        >
                                                    </view>
                                                </view>
                                                <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                                                    <view
                                                        v-for="(item, idx) in visibleCommentScripts"
                                                        :key="idx"
                                                        class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[20rpx] py-1 border border-solid border-[#BFDBFE]"
                                                        @click="openScriptEdit(idx, ScriptTargetEnum.COMMENT)">
                                                        <text class="text-xs font-semibold text-primary">{{
                                                            item
                                                        }}</text>
                                                        <view
                                                            class="shrink-0 w-[28rpx] h-[28rpx] rounded-full bg-[#0065fb]/10 flex items-center justify-center"
                                                            @click.stop="
                                                                handleDeleteScript(ScriptTargetEnum.COMMENT, idx)
                                                            ">
                                                            <u-icon name="close" size="14" color="#0065fb" />
                                                        </view>
                                                    </view>
                                                </view>
                                                <view
                                                    v-if="commentScriptsOverflow"
                                                    class="flex items-center justify-center gap-[8rpx] mb-[16rpx] py-[10rpx] rounded-[16rpx] bg-[#F0F6FF] border border-solid border-[#BFDBFE]"
                                                    @click="toggleCommentScripts">
                                                    <text class="text-xs text-primary font-semibold">
                                                        {{
                                                            commentScriptsExpanded
                                                                ? "收起"
                                                                : `展开全部 ${formData.comment_scripts.length} 条`
                                                        }}
                                                    </text>
                                                    <u-icon
                                                        :name="commentScriptsExpanded ? 'arrow-up' : 'arrow-down'"
                                                        color="#0065fb"
                                                        size="22" />
                                                </view>
                                                <view class="flex gap-[12rpx]">
                                                    <view
                                                        class="flex-1 bg-white rounded-[16rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                                                        <u-input
                                                            v-model="commentScriptInput"
                                                            placeholder="输入话术后点击添加..."
                                                            placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                                            @confirm="
                                                                handleAddScriptByInput(ScriptTargetEnum.COMMENT)
                                                            " />
                                                    </view>
                                                    <view
                                                        class="px-[28rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                                        :style="{
                                                            background:
                                                                'linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)',
                                                        }"
                                                        @click="handleAddScriptByInput(ScriptTargetEnum.COMMENT)">
                                                        <text class="font-semibold text-white">添加</text>
                                                    </view>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    class="rounded-[20rpx] p-[24rpx] border-[2rpx] border-solid transition-all duration-200"
                                    :class="
                                        commentRule == CommentRuleEnum.LIKE_REPLY
                                            ? 'border-primary bg-[#F0F6FF]'
                                            : 'border-[#F0F2F5] bg-[#FAFAFA]'
                                    "
                                    @click="commentRule = CommentRuleEnum.LIKE_REPLY">
                                    <view class="flex items-start gap-[20rpx]">
                                        <view
                                            class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="
                                                commentRule == CommentRuleEnum.LIKE_REPLY
                                                    ? 'bg-[#DBEAFE]'
                                                    : 'bg-[#F0F2F5]'
                                            ">
                                            <u-icon
                                                name="thumb-up"
                                                :color="
                                                    commentRule == CommentRuleEnum.LIKE_REPLY ? '#0065fb' : '#9CA3AF'
                                                "
                                                size="32" />
                                        </view>
                                        <view class="flex-1">
                                            <view class="flex items-center justify-between mb-[8rpx]">
                                                <text class="text-[28rpx] font-bold text-[#0D1117]"
                                                    >仅点赞（不回复）</text
                                                >
                                                <view
                                                    class="w-[36rpx] h-[36rpx] rounded-full border-[3rpx] border-solid flex items-center justify-center"
                                                    :class="
                                                        commentRule == CommentRuleEnum.LIKE_REPLY
                                                            ? 'border-primary bg-primary'
                                                            : 'border-[#D1D5DB] bg-white'
                                                    ">
                                                    <view
                                                        v-if="commentRule == CommentRuleEnum.LIKE_REPLY"
                                                        class="w-[14rpx] h-[14rpx] rounded-full bg-white" />
                                                </view>
                                            </view>
                                            <text class="text-xs text-[#9CA3AF]">仅点赞标记为已读，不发表评论</text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                    <template v-if="takeoverType === TakeoverTypeEnum.PM">
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">接管策略设置</text>
                            </view>
                            <view class="px-[28rpx] pt-[24rpx] pb-[28rpx]">
                                <view class="bg-[#F7F9FC] rounded-[20rpx] p-[6rpx] flex mb-[24rpx]">
                                    <view
                                        v-for="tab in STRATEGY_TABS"
                                        :key="tab.value"
                                        class="flex-1 h-[72rpx] flex items-center justify-center rounded-[16rpx] transition-all duration-200"
                                        :class="
                                            strategyType === tab.value
                                                ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,101,251,0.12)]'
                                                : ''
                                        "
                                        @click="switchStrategy(tab.value)">
                                        <text
                                            class="font-bold"
                                            :class="strategyType === tab.value ? 'text-primary' : 'text-[#9CA3AF]'">
                                            {{ tab.label }}
                                        </text>
                                    </view>
                                </view>

                                <view v-if="strategyType === StrategyTypeEnum.AI">
                                    <text class="text-xs text-[#9CA3AF] block mb-[20rpx]">
                                        调用智能体生成专属回复
                                    </text>
                                    <view
                                        class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] flex items-center justify-between border border-solid border-[#E5E9F0]"
                                        @click="handleChooseAgent">
                                        <text class="text-[#4B5563]">关联智能体</text>
                                        <view class="flex items-center gap-[8rpx]">
                                            <text class="text-xs text-primary">
                                                {{ selectedMessageAgent ? selectedMessageAgent.name : "选择智能体" }}
                                            </text>
                                            <u-icon name="arrow-right" color="#0065fb" size="24" />
                                        </view>
                                    </view>
                                </view>

                                <view v-if="strategyType === StrategyTypeEnum.FIXED">
                                    <text class="text-xs text-[#9CA3AF] block mb-[20rpx]">
                                        系统将从以下话术中随机抽取一条进行回复
                                    </text>
                                    <view
                                        v-if="formData.fixed_scripts.length > 0"
                                        class="flex items-center justify-between mb-[16rpx]">
                                        <text class="text-xs text-[#9CA3AF]">
                                            共 {{ formData.fixed_scripts.length }} 条话术
                                        </text>
                                        <view
                                            class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#FEF2F2] border border-solid border-[#FECACA]"
                                            @click="handleClearAllScripts(ScriptTargetEnum.FIXED)">
                                            <u-icon name="trash" size="22" color="#EF4444" />
                                            <text class="text-xs font-semibold text-[#EF4444]">一键删除</text>
                                        </view>
                                    </view>
                                    <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                                        <view
                                            v-for="(item, idx) in visibleFixedScripts"
                                            :key="idx"
                                            class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[20rpx] py-1 border border-solid border-[#BFDBFE]"
                                            @click="openScriptEdit(idx, ScriptTargetEnum.FIXED)">
                                            <text class="text-xs font-semibold text-primary">{{ item }}</text>
                                            <view
                                                class="shrink-0 w-[28rpx] h-[28rpx] rounded-full bg-[#0065fb]/10 flex items-center justify-center"
                                                @click.stop="handleDeleteScript(ScriptTargetEnum.FIXED, idx)">
                                                <u-icon name="close" size="14" color="#0065fb" />
                                            </view>
                                        </view>
                                    </view>
                                    <view
                                        v-if="fixedScriptsOverflow"
                                        class="flex items-center justify-center gap-[8rpx] mb-[16rpx] py-[10rpx] rounded-[16rpx] bg-[#F0F6FF] border border-solid border-[#BFDBFE]"
                                        @click="toggleFixedScripts">
                                        <text class="text-xs text-primary font-semibold">
                                            {{
                                                fixedScriptsExpanded
                                                    ? "收起"
                                                    : `展开全部 ${formData.fixed_scripts.length} 条`
                                            }}
                                        </text>
                                        <u-icon
                                            :name="fixedScriptsExpanded ? 'arrow-up' : 'arrow-down'"
                                            color="#0065fb"
                                            size="22" />
                                    </view>
                                    <view class="flex gap-[12rpx]">
                                        <view
                                            class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                                            <u-input
                                                v-model="scriptInput"
                                                placeholder="输入话术后点击添加..."
                                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                                @confirm="handleAddScriptByInput(ScriptTargetEnum.FIXED)" />
                                        </view>
                                        <view
                                            class="px-[28rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                            @click="handleAddScriptByInput(ScriptTargetEnum.FIXED)">
                                            <text class="font-semibold text-white">添加</text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">频率与限制</text>
                            </view>
                            <view class="px-[28rpx] py-[24rpx]">
                                <view class="flex items-center justify-between mb-[28rpx]">
                                    <text class="text-[#374151]">私信每接管个用户回复数</text>
                                    <text class="font-bold text-primary">
                                        {{ pmReplyLimit === 0 ? "无限制" : pmReplyLimit + " 条" }}
                                    </text>
                                </view>
                                <slider
                                    :value="pmReplyLimit"
                                    :min="0"
                                    :max="20"
                                    :step="1"
                                    activeColor="#0065fb"
                                    backgroundColor="#F0F2F5"
                                    block-color="#0065fb"
                                    block-size="20"
                                    @change="pmReplyLimit = $event.detail.value" />
                                <view class="flex justify-between mt-[8rpx]">
                                    <text class="text-[22rpx] text-[#9CA3AF]">无限制</text>
                                    <text class="text-[22rpx] text-[#9CA3AF]">20条</text>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>
            </scroll-view>

            <view v-show="currentStep === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                        <base-setting
                            v-model="formData"
                            :show-device="false"
                            :show-accounts="true"
                            :multiple="0"
                            :current-frequency="currentFrequency"
                            :platform-types="[
                                AppTypeEnum.XHS,
                                AppTypeEnum.DOUYIN,
                                AppTypeEnum.KUAISHOU,
                                AppTypeEnum.SPH,
                            ]"
                            @change-frequency="currentFrequency = $event" />

                        <view
                            v-if="taskErrorMsg"
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-[#EF4444] rounded-full" />
                                <u-icon name="warning-fill" size="24" color="#EF4444" />
                                <text class="text-[28rpx] font-bold text-[#EF4444]">任务冲突</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <text class="text-[#EF4444] leading-relaxed">{{ taskErrorMsg }}</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <template v-if="currentStep !== STEPS.length">
                <view
                    v-if="currentStep !== 1"
                    class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center border border-solid border-[#E5E9F0] bg-white"
                    @click="handleStep(currentStep, 'prev')">
                    <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
                </view>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center transition-all duration-300"
                    :class="canNext ? 'shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : ''"
                    :style="
                        canNext
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="handleStep(currentStep, 'next')">
                    <text class="text-[30rpx] font-bold text-white">下一步</text>
                </view>
            </template>
            <template v-else>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateTask">
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide">创建任务</text>
                </view>
            </template>
        </view>
    </view>

    <keywords-edit
        v-model="showScriptEdit"
        :title="scriptEditTitle"
        ref="keywordsEditRef"
        @confirm="handleScriptConfirm" />
    <choose-agent ref="chooseAgentRef" v-model="showChooseAgent" @confirm="handleChooseAgentConfirm" />
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        :show-close="false"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess" />
    <task-conflict-dialog
        v-if="showTaskMsgPop"
        v-model="showTaskMsgPop"
        :messages="taskMsgPopContent"
        @close="showTaskMsgPop = false"
        @confirm="handleTaskMsgPopConfirm" />
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import Steps from "@/ai_modules/device/components/steps/steps.vue";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";
import ChooseAgent from "@/ai_modules/device/components/choose-agent/choose-agent.vue";

import {
    STEPS,
    TAKEOVER_TABS,
    STRATEGY_TABS,
    ScriptTargetEnum,
    CommentRuleEnum,
    StrategyTypeEnum,
    TakeoverTypeEnum,
    createDefaultFormData,
} from "./hooks/types";
import { useTakeoverStep } from "./hooks/useTakeoverStep";
import { useStep } from "./hooks/useStep";
import { useCreateTask } from "./hooks/useCreateTask";

// ── Tab 数据（模板用 v-for，消除重复 view）────────────────────────

// ── 表单数据 ──────────────────────────────────────────────────────
const formData = reactive(createDefaultFormData());

// ── Step1 接管设置 ────────────────────────────────────────────────
const {
    takeoverType,
    strategyType,
    commentRule,
    pmReplyLimit,
    switchTakeoverType,
    switchStrategy,
    showChooseAgent,
    selectedCommentAgent,
    selectedMessageAgent,
    handleChooseAgent,
    handleChooseAgentConfirm,
    scriptInput,
    commentScriptInput,
    handleAddScriptByInput,
    showScriptEdit,
    scriptEditTitle,
    keywordsEditRef,
    openScriptEdit,
    handleScriptConfirm,
    chooseAgentRef,
    // 话术删除
    handleDeleteScript,
    handleClearAllScripts,
    // 话术展开/折叠
    fixedScriptsExpanded,
    commentScriptsExpanded,
    visibleFixedScripts,
    visibleCommentScripts,
    fixedScriptsOverflow,
    commentScriptsOverflow,
    toggleFixedScripts,
    toggleCommentScripts,
    SCRIPT_COLLAPSE_THRESHOLD,
} = useTakeoverStep(formData);

// ── 步骤导航 ──────────────────────────────────────────────────────
const { currentStep, canNext, handleStep } = useStep({
    formData,
    takeoverType,
    strategyType,
    commentRule,
    selectedCommentAgent,
    selectedMessageAgent,
});

// ── 创建任务 ──────────────────────────────────────────────────────

const {
    currentFrequency,
    taskErrorMsg,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask({
    formData,
    takeoverType,
    strategyType,
    commentRule,
    selectedCommentAgent,
    selectedMessageAgent,
    pmReplyLimit,
});

// ── EventBus ──────────────────────────────────────────────────────
const { on } = useEventBusManager();
onLoad(() => {
    on("confirm", (e: any) => {
        const { type, data } = e;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            formData.accounts = data?.length
                ? data.map((item: any) => ({ id: item.id, account: item.account, type: item.type }))
                : [];
            return;
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (!data?.length) {
                currentFrequency.value = 0;
                formData.custom_date = [];
                return;
            }
            formData.custom_date = data;
            currentFrequency.value = 5;
        }
    });
});
</script>
