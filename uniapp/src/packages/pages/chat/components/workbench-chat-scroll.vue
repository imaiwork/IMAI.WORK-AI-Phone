<template>
    <view class="h-full flex flex-col min-h-0">
        <view class="flex flex-col flex-1 min-h-0 h-0 py-4 relative" v-if="contentList.length">
            <view class="scroll-view-content flex-1 min-h-0 h-full">
                <scroll-view
                    class="h-full"
                    scroll-y
                    ref="contentRef"
                    :scroll-top="scrollTop"
                    :scroll-with-animation="false"
                    @scroll="handleScroll"
                    @scrolltolower="handleScrollToLower">
                    <view v-if="contentList.length" class="content-box">
                        <view v-for="(item, index) in contentList" :key="`wb-${item.id ?? 'x'}-${index}`">
                            <view class="pb-4">
                                <!-- 纯结果卡/优化卡可不渲染空气泡；用户消息始终展示 -->
                                <chat-record-item
                                    v-if="
                                        item.workbench?.kind !== 'prompt-optimize' &&
                                        (item.type == 1 || item.reply || item.error || item.loading || !item.workbench)
                                    "
                                    :type="item.type"
                                    :avatar="item.form_avatar"
                                    :content="item.type == 1 ? item.message : item.reply"
                                    :error="item.error"
                                    :reasoning-content="item.reasoning_content"
                                    :is-reasoning-finished="item.is_reasoning_finished"
                                    :loading="item.loading"
                                    :consume-tokens="item.consume_tokens"
                                    :file-list="item.fileList || []"
                                    :index="index"
                                    :is-markdown="item.type == 2"
                                    :showCopyBtn="item.type == 2"></chat-record-item>
                                <!-- 图像：优化提示词卡片（对齐 HTML，可编辑后生成） -->
                                <view v-if="item.type == 2 && item.workbench?.kind === 'prompt-optimize'" class="mt-2">
                                    <image-optimize-card
                                        :text="item.workbench.text"
                                        :regenerating="!!item.workbench.regenerating"
                                        :busy="sendDisabled"
                                        @update:text="(v) => emit('optimize-update', index, v)"
                                        @regen="emit('optimize-regen', index)"
                                        @confirm="(t) => emit('optimize-confirm', index, t)"
                                        @cancel="emit('optimize-cancel', index)" />
                                </view>
                                <view
                                    v-else-if="item.type == 2 && item.workbench"
                                    class="mt-2 bg-white rounded-[24rpx] p-[24rpx]">
                                    <workbench-result-card
                                        :kind="item.workbench.kind"
                                        :title="item.workbench.title"
                                        :text="item.workbench.text"
                                        :urls="item.workbench.urls"
                                        :slides="item.workbench.slides"
                                        :cards="item.workbench.cards" />
                                </view>
                            </view>
                        </view>
                    </view>
                    <slot v-else name="empty"></slot>
                </scroll-view>
            </view>
            <!-- 键盘弹起时盖一层，点空白可靠收起（scroll-view 空区域 tap 不可靠） -->
            <view v-show="keyboardOpen" class="absolute inset-0 z-30" @tap.stop="dismissKeyboard"></view>
        </view>
        <view class="grow min-h-0 relative" v-else>
            <slot name="content"></slot>
            <view
                v-if="!isCoze && !isStaff"
                class="absolute bottom-[-40rpx] left-0 right-0 h-20 z-10 pointer-events-none"
                style="background: linear-gradient(360deg, #eef0f6, transparent)">
            </view>
            <view v-show="keyboardOpen" class="absolute inset-0 z-30" @tap.stop="dismissKeyboard"></view>
        </view>
        <view
            class="px-[20rpx] pt-1 relative z-[33] flex-shrink-0"
            :class="[
                isHome ? 'bg-white chat-bottom-white pb-[16rpx]' : '',
                isCoze || isStaff ? 'mb-[40rpx]' : isHome ? '' : 'mb-[20rpx]',
            ]">
            <view class="relative z-[79] chat-bottom-box" @tap.stop>
                <view class="flex flex-col">
                    <!-- 对话工具栏：键盘期折叠隐藏（不卸载，避免丢横向滚动/输入跳动）；收起后延迟展开防点穿 -->
                    <scroll-view
                        v-if="!isCoze && !isStaff && isChatWorkbench"
                        scroll-x
                        class="mb-1"
                        :class="{ 'toolbar-collapsed': !showToolbar }">
                        <view
                            class="flex items-center gap-x-2 whitespace-nowrap pt-1"
                            :class="{ 'pointer-events-none': !showToolbar }">
                            <template v-if="!isAgent">
                                <!-- 非手机操控时始终展示：有模型显示名称，无模型显示「选择模型」（挂载清空后不能丢入口） -->
                                <view
                                    :class="[
                                        isHome
                                            ? tbPill(hasDisplayModel)
                                            : 'text-xs bg-white rounded-[16rpx] px-2 h-[60rpx] inline-flex items-center gap-x-1',
                                        { 'is-hidden': currAgent.id || isPhoneCtrlActive },
                                    ]"
                                    @click="guardToolbarAction(() => { showModel = true; hideKeyboard(); })">
                                    <image
                                        v-show="showDisplayModelAvatar"
                                        :src="displayModel.logo"
                                        class="w-[32rpx] h-[32rpx] rounded-full"
                                        mode="aspectFill"
                                        @error="modelAvatarBroken = true" />
                                    <text class="whitespace-nowrap">{{
                                        currModel.name || getAIModels[0]?.name || toolbarModelName
                                    }}</text>
                                    <u-icon
                                        name="arrow-down"
                                        size="20"
                                        :color="isHome && hasDisplayModel ? '#2563EB' : '#a8abb2'"></u-icon>
                                </view>
                                <view
                                    class="bg-white rounded-[16rpx] px-2 h-[60rpx] gap-x-1 border border-solid border-[#E9EBEC] inline-flex items-center relative"
                                    :class="{ 'is-hidden': !currAgent.id }"
                                    @click="guardToolbarAction(() => openAgentPopup())">
                                    <image
                                        v-show="showCurrAgentAvatar"
                                        :src="currAgent.logo"
                                        class="w-[28rpx] h-[28rpx] rounded-[24rpx]"
                                        mode="aspectFill"
                                        @error="agentAvatarBroken = true" />
                                    <text
                                        class="max-w-[200rpx] text-ellipsis overflow-hidden whitespace-nowrap text-xs">
                                        {{ currAgent.name }}
                                    </text>
                                    <view
                                        class="absolute right-[-10rpx] top-[-10rpx] flex items-center justify-center w-[32rpx] h-[32rpx] rounded-full bg-[#0000004C]"
                                        @click.stop="handleAgentClear">
                                        <u-icon name="close" color="#ffffff" :size="14"></u-icon>
                                    </view>
                                </view>
                            </template>
                            <view
                                :class="[tbPill(mountedDevices.length > 0), { 'is-hidden': !isHome }]"
                                @click="guardToolbarAction(() => openMount())">
                                <u-icon
                                    :name="`/static/images/icons/mount${mountedDevices.length ? '_blue' : ''}.svg`"
                                    :size="24"></u-icon>
                                <text class="text-xs">
                                    {{ mountedDevices.length ? `已挂载 ${mountedDevices.length} 台` : "挂载" }}
                                </text>
                                <u-icon
                                    name="arrow-down"
                                    size="18"
                                    :color="mountedDevices.length ? '#2563EB' : '#999999'"></u-icon>
                            </view>
                            <view
                                :class="[
                                    isHome
                                        ? tbMini(selectedNetwork)
                                        : [pillClassLegacy, { '!bg-primary !text-white': selectedNetwork }],
                                    { 'is-hidden': isPhoneCtrlActive },
                                ]"
                                @click="guardToolbarAction(() => handleNetwork())">
                                <u-icon :name="networkIconName" :size="24"></u-icon>
                                <text v-show="!isHome" class="text-xs">联网</text>
                            </view>
                            <view
                                :class="isHome ? tbPill(false) : pillClassLegacy"
                                @click="
                                    guardToolbarAction(() => {
                                        emit('showHistory');
                                        hideKeyboard();
                                    })
                                ">
                                <u-icon
                                    :name="
                                        isHome ? '/static/images/icons/clock.svg' : '/static/images/icons/history.svg'
                                    "
                                    :size="24"></u-icon>
                                <text class="text-xs">历史</text>
                            </view>
                            <view
                                :class="[
                                    isHome
                                        ? tbMini(false)
                                        : 'flex-shrink-0 leading-[0] h-[60rpx] w-[60rpx] flex items-center justify-center rounded-full bg-white',
                                    { 'is-hidden': isAgent || isPhoneCtrlActive },
                                ]"
                                @click="guardToolbarAction(() => handleSetting())">
                                <u-icon name="/static/images/icons/setting.svg" :size="28"></u-icon>
                            </view>
                            <!-- 工作台模式入口（设计稿：图像/地图；PC：含 PPT/视频） -->
                            <template v-if="!isPhoneCtrlActive">
                                <view
                                    v-for="opt in WORKBENCH_MODE_OPTIONS"
                                    :key="opt.mode"
                                    :class="isHome ? tbPill(false) : pillClassLegacy"
                                    @click.stop="guardToolbarAction(() => onWorkbenchModeClick(opt.mode))">
                                    <u-icon
                                        :name="
                                            opt.mode === 'image'
                                                ? 'photo'
                                                : opt.mode === 'map'
                                                ? 'map'
                                                : opt.mode === 'video'
                                                ? 'play-right'
                                                : 'file-text'
                                        "
                                        :size="24"
                                        color="#6B7280"></u-icon>
                                    <text class="text-xs">{{ opt.label }}</text>
                                </view>
                            </template>
                            <template v-if="isHome">
                                <view
                                    :class="tbPill(false)"
                                    @click="guardToolbarAction(() => toPage('ladder_player'))">
                                    <u-icon name="/static/images/icons/chat_line.svg" :size="24"></u-icon>
                                    <text class="text-xs">AI陪练</text>
                                </view>
                                <view
                                    :class="tbPill(false)"
                                    @click="guardToolbarAction(() => toPage('meeting_minutes'))">
                                    <u-icon name="/static/images/icons/doc_lines.svg" :size="24"></u-icon>
                                    <text class="text-xs">AI会议</text>
                                </view>
                                <!-- #ifdef MP-WEIXIN -->
                                <view
                                    :class="tbPill(false)"
                                    @click="guardToolbarAction(() => toPage('interview'))">
                                    <u-icon
                                        :name="`${config.baseUrl}static/images/mp/interview.svg`"
                                        :size="24"></u-icon>
                                    <text class="text-xs">智能人事</text>
                                </view>
                                <!-- #endif -->
                            </template>
                        </view>
                    </scroll-view>
                    <view v-if="$slots.chatAreaTop && !isChatWorkbench" class="mb-0">
                        <slot name="chatAreaTop"></slot>
                    </view>
                    <view v-if="isPhoneCtrlActive" class="ctrl-mode-bar">
                        <view class="ctrl-dot"></view>
                        <u-icon name="/static/images/icons/phone_control.svg" :size="26"></u-icon>
                        <text class="text-xs leading-none">
                            <text class="font-semibold">一句话操控手机</text>
                            · 已挂载 {{ mountedDevices.length }} 台
                        </text>
                        <view class="ctrl-exit" @click="exitPhoneControlMode">退出</view>
                    </view>
                    <view class="flex-1 flex gap-x-2 items-center">
                        <slot name="sendLeft" v-if="$slots.sendLeft"></slot>
                        <view
                            class="flex-1 bg-[#F7F8FA] rounded-[32rpx] border border-solid border-[#ECECEE] overflow-hidden relative">
                            <!-- 对话附件；图像/视频参考图走专用缩略条，不走这里 -->
                            <view
                                class="p-2 flex"
                                :class="{ 'is-hidden': !isChatWorkbench || !fileList.length }">
                                <view v-for="(item, index) in fileList" :key="index">
                                    <FileItem :item="item" :index="index" @on-delete="deleteFile" />
                                </view>
                            </view>
                            <!-- 竖向 padding 放外层；空态固定单行高，聚焦/有内容再开 auto-height，避免首屏由高变矮 -->
                            <view class="flex items-center pl-2 py-[12rpx]">
                                <view
                                    v-show="!isCoze && !isStaff && !isPhoneCtrlActive && showPlusUpload"
                                    class="flex-shrink-0">
                                    <view
                                        class="w-[60rpx] h-[60rpx] rounded-full border border-solid border-[#D1D5DB] flex items-center justify-center"
                                        hover-class="opacity-70"
                                        :hover-stay-time="80"
                                        @click="handleFileUpload">
                                        <u-icon name="plus" :size="26" color="#9CA3AF"></u-icon>
                                    </view>
                                </view>
                                <textarea
                                    class="chat-textarea flex-1 w-0 min-w-0 min-h-[40rpx] max-h-[140rpx] overflow-y-auto px-2 text-[28rpx] leading-[40rpx] mt-[8rpx]"
                                    ref="textareaRef"
                                    v-model="userInput"
                                    confirm-type="done"
                                    maxlength="-1"
                                    placeholder-style="color: rgba(0, 0, 0, 0.2); font-size: 26rpx; line-height: 40rpx;"
                                    :auto-height="taAutoHeight"
                                    :style="taTextareaStyle"
                                    :adjust-position="false"
                                    :placeholder="currentPlaceholder"
                                    :show-confirm-bar="false"
                                    :disable-default-padding="true"
                                    @focus="onTextareaFocus"
                                    @blur="onTextareaBlur"
                                    @input="handleInput"></textarea>
                                <view class="flex-shrink-0 flex items-center gap-2.5 mr-2">
                                    <view
                                        class="send-btn bg-primary-light-9"
                                        :class="{ 'is-hidden': !isStop }"
                                        @click="chatClose">
                                        <u-icon name="/static/images/icons/chat_stop.svg" :size="36"></u-icon>
                                    </view>
                                    <view
                                        class="send-btn"
                                        :class="[
                                            !isSendDisabled ? 'bg-primary-light-9' : 'bg-[#F2F2F2]',
                                            { 'is-hidden': isStop },
                                        ]"
                                        @click.prevent="onSendClick">
                                        <u-icon
                                            :name="
                                                isSendDisabled
                                                    ? '/static/images/icons/arrow_up.svg'
                                                    : '/static/images/icons/arrow_up_primary.svg'
                                            "
                                            :size="36"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="flex justify-center mt-[20rpx]">
                        <view class="flex items-center rounded-full bg-[#00000008] gap-x-1.5 p-[6rpx]">
                            <u-icon name="/static/images/icons/tips.svg" :size="32"></u-icon>
                            <view class="text-[rgba(0,0,0,0.3)] text-xs">
                                免责声明：内容由AI大模型生成，请仔细甄别。
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view
            class="flex-shrink-0"
            :style="{
                height: spacerHeight + 'rpx',
            }"></view>
    </view>
    <popup-bottom
        v-if="showHumanize"
        v-model="showHumanize"
        title="参数设置"
        height="85%"
        custom-class="bg-[#F4F6FB]"
        is-disabled-touch>
        <template #content>
            <view class="h-[85%] flex flex-col">
                <view class="px-[32rpx] pt-[32rpx] pb-[24rpx] flex items-center gap-x-[12rpx] flex-shrink-0">
                    <view class="w-[8rpx] h-[36rpx] bg-primary rounded-full"></view>
                    <text class="text-[32rpx] font-bold text-[#424242] tracking-tight">参数设置</text>
                </view>

                <view class="grow min-h-0 px-[32rpx]">
                    <scroll-view scroll-y class="h-full">
                        <view class="flex flex-col gap-y-[20rpx] pb-[24rpx]">
                            <view
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between mb-[24rpx]">
                                    <text class="text-[28rpx] font-semibold text-[#424242]">上下文数</text>
                                    <view class="bg-[#EEF4FF] px-[20rpx] py-[6rpx] rounded-full">
                                        <text class="text-[24rpx] font-bold text-primary"
                                            >{{ humanizeParams.context_num }} 条</text
                                        >
                                    </view>
                                </view>
                                <slider
                                    :value="humanizeParams.context_num"
                                    active-color="#0065fb"
                                    background-color="#EEF2FF"
                                    :block-size="18"
                                    :min="0"
                                    :max="5"
                                    @change="changeHumanizeParams($event, 'context_num', 0)" />
                            </view>

                            <view
                                v-if="currModel.model_id != ModelIdEnum.CLAUDE_SONNET_4_5"
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between mb-[24rpx]">
                                    <text class="text-[28rpx] font-semibold text-gray-700">词汇多样性</text>
                                    <view class="bg-[#EEF4FF] px-[20rpx] py-[6rpx] rounded-full">
                                        <text class="text-[24rpx] font-bold text-primary">{{
                                            humanizeParams.top_p
                                        }}</text>
                                    </view>
                                </view>
                                <slider
                                    :value="humanizeParams.top_p"
                                    active-color="#0065fb"
                                    background-color="#EEF2FF"
                                    :block-size="18"
                                    :min="0"
                                    :max="1"
                                    :step="0.1"
                                    @change="changeHumanizeParams($event, 'top_p', 1)" />
                            </view>

                            <view
                                v-if="currModel.model_id != ModelIdEnum.DEEPSEEK"
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between mb-[24rpx]">
                                    <text class="text-[28rpx] font-semibold text-[#424242]">重复词频率</text>
                                    <view class="bg-[#EEF4FF] px-[20rpx] py-[6rpx] rounded-full">
                                        <text class="text-[24rpx] font-bold text-primary">{{
                                            humanizeParams.frequency_penalty
                                        }}</text>
                                    </view>
                                </view>
                                <slider
                                    :value="humanizeParams.frequency_penalty"
                                    active-color="#0065fb"
                                    background-color="#EEF2FF"
                                    :block-size="18"
                                    :min="-2"
                                    :max="2"
                                    :step="0.1"
                                    @change="changeHumanizeParams($event, 'frequency_penalty', 1)" />
                            </view>

                            <view
                                v-if="currModel.model_id != ModelIdEnum.DEEPSEEK"
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between mb-[24rpx]">
                                    <text class="text-[28rpx] font-semibold text-[#424242]">特定词重复率</text>
                                    <view class="bg-[#EEF4FF] px-[20rpx] py-[6rpx] rounded-full">
                                        <text class="text-[24rpx] font-bold text-primary">{{
                                            humanizeParams.presence_penalty
                                        }}</text>
                                    </view>
                                </view>
                                <slider
                                    :value="humanizeParams.presence_penalty"
                                    active-color="#0065fb"
                                    background-color="#EEF2FF"
                                    :block-size="18"
                                    :min="0"
                                    :max="1"
                                    :step="0.1"
                                    @change="changeHumanizeParams($event, 'presence_penalty', 1)" />
                            </view>

                            <view
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between mb-[24rpx]">
                                    <text class="text-[28rpx] font-semibold text-[#424242]">结果相似性</text>
                                    <view class="bg-[#EEF4FF] px-[20rpx] py-[6rpx] rounded-full">
                                        <text class="text-[24rpx] font-bold text-primary">{{
                                            humanizeParams.temperature
                                        }}</text>
                                    </view>
                                </view>
                                <slider
                                    :value="humanizeParams.temperature"
                                    active-color="#0065fb"
                                    background-color="#EEF2FF"
                                    :block-size="18"
                                    :min="0"
                                    :max="getMaxTemperature"
                                    :step="0.1"
                                    @change="changeHumanizeParams($event, 'temperature', 1)" />
                            </view>

                            <view
                                v-if="currModel.model_id != ModelIdEnum.DEEPSEEK"
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between mb-[24rpx]">
                                    <text class="text-[28rpx] font-semibold text-[#424242]">候选词对数概率</text>
                                    <view class="bg-[#EEF4FF] px-[20rpx] py-[6rpx] rounded-full">
                                        <text class="text-[24rpx] font-bold text-primary">{{
                                            humanizeParams.top_logprobs
                                        }}</text>
                                    </view>
                                </view>
                                <slider
                                    :value="humanizeParams.top_logprobs"
                                    active-color="#0065fb"
                                    background-color="#EEF2FF"
                                    :block-size="18"
                                    :min="0"
                                    :max="1"
                                    :step="0.1"
                                    @change="changeHumanizeParams($event, 'top_logprobs', 1)" />
                            </view>

                            <view
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between mb-[24rpx]">
                                    <text class="text-[28rpx] font-semibold text-[#424242]">返回长度</text>
                                    <view class="bg-[#EEF4FF] px-[20rpx] py-[6rpx] rounded-full">
                                        <text class="text-[24rpx] font-bold text-primary">{{
                                            humanizeParams.max_tokens
                                        }}</text>
                                    </view>
                                </view>
                                <slider
                                    :value="humanizeParams.max_tokens"
                                    active-color="#0065fb"
                                    background-color="#EEF2FF"
                                    :block-size="18"
                                    :min="1"
                                    :max="getMaxTokens"
                                    :step="1"
                                    @change="changeHumanizeParams($event, 'max_tokens', 1)" />
                            </view>

                            <view
                                v-if="currModel.model_id != ModelIdEnum.DEEPSEEK"
                                class="bg-white rounded-[28rpx] px-[32rpx] py-[28rpx] shadow-sm border border-solid border-[#f9f9f9]">
                                <view class="flex items-center justify-between">
                                    <text class="text-[28rpx] font-semibold text-[#424242]">显示候选词</text>
                                    <u-switch
                                        v-model="humanizeParams.logprobs"
                                        :active-value="1"
                                        :inactive-value="0"
                                        size="44" />
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="px-[32rpx] pt-[24rpx] pb-[40rpx] flex-shrink-0">
                    <view
                        class="w-full h-[96rpx] bg-primary rounded-full flex items-center justify-center shadow-md active:opacity-90"
                        @click="handelChatConfig">
                        <text class="text-white font-bold text-[30rpx] tracking-wide">保存设置</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <popup-bottom
        v-if="showModel && !isPhoneCtrlActive"
        v-model="showModel"
        title="选择模型"
        height="55%"
        custom-class="bg-[#F4F6FB]">
        <template #content>
            <view class="h-full flex flex-col bg-[#F4F6FB]">
                <view
                    class="px-[32rpx] pt-[32rpx] pb-[24rpx] flex items-center gap-x-[12rpx] flex-shrink-0 bg-[#F4F6FB]">
                    <view class="w-[8rpx] h-[36rpx] bg-primary rounded-full"></view>
                    <text class="text-[32rpx] font-bold text-[#212121] tracking-tight">选择模型</text>
                </view>

                <scroll-view scroll-y class="flex-1 min-h-0">
                    <view class="px-[32rpx] pb-[48rpx]">
                        <view class="grid grid-cols-2 gap-[20rpx]">
                            <view
                                v-for="(item, index) in getAIModels"
                                :key="index"
                                class="bg-white rounded-full px-[28rpx] py-[20rpx] flex items-center gap-x-[16rpx] shadow-sm border border-solid border-[#f9f9f9] transition-all"
                                :class="
                                    isModelActive(item)
                                        ? '!border-primary !bg-[#EEF4FF] shadow-[0_2rpx_12rpx_rgba(59,130,246,0.15)]'
                                        : ''
                                "
                                @click="handleModel(item)">
                                <image
                                    v-if="hasModelLogo(item.logo)"
                                    :src="item.logo"
                                    class="w-[48rpx] h-[48rpx] rounded-[14rpx] flex-shrink-0"
                                    mode="aspectFill"
                                    @error="markModelLogoBroken(item.logo)" />
                                <view class="flex-1 min-w-0 flex items-center justify-between gap-x-[8rpx]">
                                    <text
                                        class="text-[24rpx] font-semibold line-clamp-1"
                                        :class="isModelActive(item) ? 'text-primary' : 'text-[#424242]'">
                                        {{ item.name }}
                                    </text>
                                    <view
                                        v-if="isModelActive(item)"
                                        class="w-[16rpx] h-[16rpx] rounded-full bg-primary flex-shrink-0"></view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>

    <popup-bottom v-model="showAgent" title="选择智能体" height="85%" is-disabled-touch>
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="agentPagingRef"
                    v-model="agentList"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="getAgentList">
                    <view class="flex flex-col gap-4 px-[32rpx] mt-4">
                        <view
                            class="agent-item"
                            :class="{
                                active: currAgent.id == item.id,
                                disabled: !canUseCurrentAgent(item),
                            }"
                            v-for="(item, index) in agentList"
                            :key="index"
                            @click="handleAgent(item)">
                            <view class="flex-shrink-0">
                                <image
                                    :src="item.image"
                                    class="w-[108rpx] h-[108rpx] rounded-[24rpx]"
                                    mode="aspectFill">
                                </image>
                            </view>
                            <view class="flex-1 overflow-hidden">
                                <view class="agent-popup-title-row">
                                    <text class="agent-popup-title">
                                        {{ item.name }}
                                    </text>
                                    <text
                                        v-if="shouldShowAgentAccessTag(item)"
                                        class="agent-access-tag"
                                        :class="getAgentAccessTagClass(item)">
                                        {{ getAgentAccessTagText(item) }}
                                    </text>
                                </view>
                                <view class="text-[#9C9C9E] text-[20rpx] mt-2 line-clamp-2">
                                    {{ item.description }}
                                </view>
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </template>
    </popup-bottom>

    <mount-popup v-if="isHome" v-model="showMount" :mounted-ids="mountedIds" @confirm="handleMountConfirm" />
</template>

<script lang="ts" setup>
import config from "@/config";
import { getUserChatConfig, saveUserChatConfig } from "@/api/chat";
import { getAllAgentList as getAgentListApi, getAgentDetail } from "@/api/agent";
import { getRect, setFormData } from "@/utils/util";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import { useToolbarRevealGuard } from "../composables/useToolbarRevealGuard";
import { useUploadMisfireGuard } from "../composables/useStudioKeyboardSpacer";
import { useStableTextareaAutoHeight } from "../composables/useStableTextareaAutoHeight";
import { useAppStore } from "@/stores/app";
import { ModelIdEnum } from "@/enums/appEnums";
import FileItem from "@/packages/components/chat-scroll-view/components/file-item.vue";
import MountPopup from "@/packages/components/chat-scroll-view/components/mount-popup.vue";
import WorkbenchResultCard from "./workbench-result-card.vue";
import ImageOptimizeCard from "./image-optimize-card.vue";
import { useUserStore } from "@/stores/user";
import {
    AGENT_UNAVAILABLE_TIP,
    canUseAgent,
    getAgentAccessStatus,
    getAgentAccessTagText as getAgentPermissionTagText,
    shouldShowAgentAccessTag,
} from "@/utils/agentPermission";
import { WORKBENCH_MODE_OPTIONS } from "../enums/workbench";

const props = withDefaults(
    defineProps<{
        contentList: any[];
        fileList?: any[];
        placeholder?: string;
        sendDisabled: boolean;
        tokens: number | string;
        isStop: boolean;
        isNetwork?: boolean;
        isCoze?: boolean;
        isStaff?: boolean;
        isAgent?: boolean;
        isHome?: boolean;
        /** 工作台模式：非 chat 时隐藏对话工具栏 */
        workbenchMode?: string;
    }>(),
    {
        contentList: () => [],
        fileList: () => [],
        placeholder: "在这里输入任何问题 ...",
        sendDisabled: false,
        tokens: 0,
        isNetwork: true,
        isCoze: false,
        isStaff: false,
        isAgent: false,
        workbenchMode: "chat",
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: any[]): void;
    (event: "contentPost", value: any): void;
    (event: "close"): void;
    (event: "add-session"): void;
    (event: "update:fileList", value: any): void;
    (event: "update:network", value: boolean): void;
    (event: "showHistory"): void;
    (event: "update:agent", value: any): void;
    (event: "update:model", value: any): void;
    (event: "update:mountedDevices", value: any[]): void;
    (event: "workbench-change", mode: string): void;
    /** 输入框加号：工作台模式下由父级按 accept 处理 */
    (event: "workbench-upload"): void;
    (event: "optimize-update", index: number, text: string): void;
    (event: "optimize-regen", index: number): void;
    (event: "optimize-confirm", index: number, text: string): void;
    (event: "optimize-cancel", index: number): void;
}>();

const appStore = useAppStore();
const userStore = useUserStore();
const { userInfo } = toRefs(userStore);
const isLogin = computed(() => userStore.isLogin);

const currModel = ref<any>({
    id: "",
    name: "",
    model_id: "",
    model_sub_id: "",
    logo: "",
});

const getAIModels = computed(() => appStore.getAllowedChatModel);

const cloneModel = (m: any) => {
    if (!m) return null;
    return {
        id: m.id,
        name: m.name,
        model_id: m.model_id,
        model_sub_id: m.model_sub_id,
        logo: m.logo,
    };
};

const pickDefaultModel = () => {
    if (getAIModels.value[0]) return getAIModels.value[0];
    const channel = appStore.getChatModel || [];
    return channel.find((m: any) => m.status == "1") || channel[0] || null;
};

/** 是否已选中可用模型（channel 项可能只有 model_id） */
const hasCurrModel = computed(() => !!(currModel.value?.id || currModel.value?.model_id));

const findModelInList = (model: any, list: any[]) => {
    if (!model || !list.length) return null;
    const currId = model.id;
    if (currId != null && currId !== "") {
        const byId = list.find((item) => item.id == currId);
        if (byId) return byId;
    }
    if (model.model_id) {
        return (
            list.find(
                (item) =>
                    item.model_id == model.model_id &&
                    String(item.model_sub_id ?? "") == String(model.model_sub_id ?? ""),
            ) ||
            list.find((item) => item.model_id == model.model_id) ||
            null
        );
    }
    return null;
};

/** 展示用模型：未选中时回落列表首项（与 PC chat-model-toolbar 一致） */
const displayModel = computed(() => {
    const list = getAIModels.value;
    const fallback = list[0] || pickDefaultModel();
    if (hasCurrModel.value) {
        const matched = findModelInList(currModel.value, list);
        if (matched) return matched;
        if (currModel.value?.name) return currModel.value;
    }
    return fallback || currModel.value;
});

const hasDisplayModel = computed(() => !!(displayModel.value?.model_id || displayModel.value?.name));

/** 底部栏文案：直接读 store 首项，与中间模型 chips 同源 */
const toolbarModelName = computed(() => {
    const fromCurr = String(currModel.value?.name || "").trim();
    if (fromCurr) return fromCurr;
    const fromAllowed = String(getAIModels.value[0]?.name || "").trim();
    if (fromAllowed) return fromAllowed;
    const channel = appStore.getChatModel || [];
    const first = channel.find((m: any) => m.status == "1") || channel[0];
    return String(first?.name || "").trim() || "选择模型";
});

const isModelActive = (item: any) => {
    const d = displayModel.value;
    if (!d || !item) return false;
    if (d.id != null && d.id !== "" && item.id == d.id) return true;
    return item.model_id == d.model_id && String(item.model_sub_id ?? "") == String(d.model_sub_id ?? "");
};

/** 模型 logo：无地址或加载失败不展示占位 */
const modelAvatarBroken = ref(false);
const brokenModelLogos = ref(new Set<string>());
const showDisplayModelAvatar = computed(
    () => hasDisplayModel.value && !!String(displayModel.value?.logo || "").trim() && !modelAvatarBroken.value,
);
const hasModelLogo = (logo?: string) => {
    const url = String(logo || "").trim();
    return !!url && !brokenModelLogos.value.has(url);
};
const markModelLogoBroken = (logo?: string) => {
    const url = String(logo || "").trim();
    if (!url) return;
    brokenModelLogos.value = new Set([...brokenModelLogos.value, url]);
};
watch(
    () => displayModel.value?.logo,
    () => {
        modelAvatarBroken.value = false;
    },
);

appStore.ensureMemberQuota();

const selectedNetwork = ref(false);
const handleNetwork = () => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/packages/pages/login/login" });
        return;
    }
    selectedNetwork.value = !selectedNetwork.value;
    emit("update:network", selectedNetwork.value);
};

// 非首页保持原有白色 chip 样式
const pillClassLegacy =
    "flex-shrink-0 flex items-center justify-center gap-x-1 text-xs bg-white rounded-[16rpx] h-[60rpx] px-2";

// 首页(isHome)工具栏胶囊：参考 HTML，灰色(默认) / 浅蓝(激活)
const tbPill = (active: boolean) =>
    active
        ? "flex-shrink-0 inline-flex items-center justify-center gap-x-1 text-xs h-[60rpx] px-3 rounded-full border border-solid bg-[#EFF6FF] text-[#2563EB] border-[#BFDBFE]"
        : "flex-shrink-0 inline-flex items-center justify-center gap-x-1 text-xs h-[60rpx] px-3 rounded-full border border-solid bg-[#F3F4F6] text-[#555555] border-[transparent]";

// 首页圆形 mini 图标按钮：灰色(默认) / 浅蓝(激活)
const tbMini = (active: boolean) =>
    active
        ? "flex-shrink-0 inline-flex items-center justify-center w-[60rpx] h-[60rpx] rounded-full border border-solid bg-[#EFF6FF] border-[#BFDBFE]"
        : "flex-shrink-0 inline-flex items-center justify-center w-[60rpx] h-[60rpx] rounded-full border border-solid bg-[#F3F4F6] border-[transparent]";

const networkIconName = computed(() => {
    if (props.isHome) {
        return selectedNetwork.value
            ? "/static/images/icons/globe_blue.svg"
            : "/static/images/icons/globe.svg";
    }
    return selectedNetwork.value ? "/static/images/icons/deep_white.svg" : "/static/images/icons/deep.svg";
});

// 挂载设备
const showMount = ref(false);
const mountedDevices = ref<any[]>([]);
const mountedIds = computed(() => mountedDevices.value.map((d) => d.id));

// 一句话操控手机模式：挂载设备后自动开启
const isPhoneCtrlActive = computed(() => mountedDevices.value.length > 0);
const currentPlaceholder = computed(() =>
    isPhoneCtrlActive.value ? "一句话告诉我，要在手机上做什么…" : props.placeholder,
);

/** 已加载过的模型设置 key，避免切换/退出反复打 getUserModelsSetting */
let humanizeParamsLoadedKey = "";
let humanizeParamsLoading: Promise<void> | null = null;
let humanizeParamsLoadingKey = "";

const getModelSettingKey = (model: any) => {
    if (model?.model_id == null || model?.model_id === "") return "";
    return `${model.model_id}_${model.model_sub_id ?? ""}`;
};

/**
 * 拉取模型参数设置；同模型默认复用缓存，force 时强制刷新（如打开设置弹窗）
 */
const loadHumanizeParams = async (force = false) => {
    const model = displayModel.value;
    if (!model?.model_id) return;
    const key = getModelSettingKey(model);
    if (!force && key && key === humanizeParamsLoadedKey) return;
    if (!force && humanizeParamsLoading && key === humanizeParamsLoadingKey) {
        return humanizeParamsLoading;
    }

    humanizeParamsLoadingKey = key;
    humanizeParamsLoading = (async () => {
        try {
            const res = await getUserChatConfig({
                model_id: model.model_id,
                model_sub_id: model.model_sub_id,
            });
            Object.keys(res).forEach((k) => {
                res[k] = parseFloat(res[k]);
            });
            setFormData(res, humanizeParams);
            humanizeParamsLoadedKey = key;
        } catch {
            // 参数拉取失败不影响模型展示
        } finally {
            humanizeParamsLoading = null;
            humanizeParamsLoadingKey = "";
        }
    })();
    return humanizeParamsLoading;
};

/** 将 currModel 与允许列表对齐；无选中/无名称时回落首项（不 emit 父级，保持首页 isHome） */
const syncDefaultModel = (force = false) => {
    if (props.isAgent) return;
    if (!force && isPhoneCtrlActive.value) return;
    const list = getAIModels.value;
    const fallback = list[0] || pickDefaultModel();
    if (!fallback) return;
    const needSync =
        force ||
        !currModel.value?.name ||
        !hasCurrModel.value ||
        !findModelInList(currModel.value, list.length ? list : [fallback]);
    if (needSync) {
        currModel.value = cloneModel(fallback);
    }
};

/** 退出一句话模式后只同步模型展示；设置接口按同模型缓存跳过 */
const restoreBottomBarAfterPhoneCtrl = () => {
    syncDefaultModel(true);
    loadHumanizeParams(false);
};

// 退出即清空已挂载设备；恢复交给父层统一处理，避免与 watch/父层重复请求
const exitPhoneControlMode = () => {
    mountedDevices.value = [];
    emit("update:mountedDevices", []);
};

const openMount = () => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/packages/pages/login/login" });
        return;
    }
    hideKeyboard();
    showMount.value = true;
};

const handleMountConfirm = (devices: any[]) => {
    if (devices.length) {
        showModel.value = false;
        // 挂载时不清空 currModel，退出后直接显示
        if (!hasCurrModel.value) {
            const target = pickDefaultModel();
            if (target) currModel.value = cloneModel(target);
        }
        if (fileList.value.length) {
            fileList.value = [];
        }
    }
    mountedDevices.value = devices;
    // 清空设备时只 emit，由父层统一 restore，避免重复请求
    emit("update:mountedDevices", mountedDevices.value);
};

const fileList = computed({
    get() {
        return props.fileList;
    },
    set(value) {
        emit("update:fileList", value);
    },
});

const isSendDisabled = computed(() => {
    const flag = fileList.value.length === 0 ? !userInput.value : false;
    return props.sendDisabled || flag;
});

const isChatWorkbench = computed(() => props.workbenchMode === "chat" || !props.workbenchMode);
/** 仅对话模式显示输入框加号；图像/视频/PPT 走工具栏专用上传 */
const showPlusUpload = computed(() => isChatWorkbench.value);

const handleFileUpload = () => {
    if (isUploadMisfire()) return;
    checkLogin();
    // 工作台非对话模式：交由父级按模式 accept 处理（image/* / 文档等）
    if (!isChatWorkbench.value) {
        emit("workbench-upload");
        return;
    }
    uni.$u.route({
        url: "/packages/pages/choose_file/choose_file",
        params: { limit: 1 },
    });
};

const showModel = ref(false);
const handleModel = (item: any) => {
    if (isPhoneCtrlActive.value) {
        showModel.value = false;
        uni.$u.toast("已挂载设备时不能选择大模型");
        return;
    }
    const nextKey = getModelSettingKey(item);
    const prevKey = getModelSettingKey(currModel.value);
    currModel.value = JSON.parse(JSON.stringify(item));
    showModel.value = false;
    // 仅模型变化时拉设置，避免同模型重复请求
    if (nextKey !== prevKey) {
        loadHumanizeParams(false);
    }
    emit("update:model", item);
};

const handleModelClear = () => {
    currModel.value = {};
    showModel.value = false;
    humanizeParamsLoadedKey = "";
    emit("update:model", null);
};

const showAgent = ref(false);
const currAgent = reactive({
    id: "",
    name: "",
    logo: "",
    intro: "",
    source: undefined as any,
    permissions: undefined as any,
    member_level_ids: undefined as any,
    accessLoaded: false,
});

/** 无有效 logo / 加载失败时不展示头像 */
const agentAvatarBroken = ref(false);
const showCurrAgentAvatar = computed(() => !!String(currAgent.logo || "").trim() && !agentAvatarBroken.value);
watch(
    () => currAgent.logo,
    () => {
        agentAvatarBroken.value = false;
    },
);

const canUseCurrentAgent = (item: any) => canUseAgent(item, userInfo.value);

const ensureAgentAvailable = (item: any) => {
    if (canUseCurrentAgent(item)) return true;
    uni.$u.toast(AGENT_UNAVAILABLE_TIP);
    return false;
};

const getAgentAccessTagText = (item: any) => getAgentPermissionTagText(item, userInfo.value);

const getAgentAccessTagClass = (item: any) =>
    getAgentAccessStatus(item, userInfo.value) === "free" ? "agent-access-tag--free" : "agent-access-tag--member";

const setAgentDetail = (item: any, detail: any = {}) => {
    currAgent.id = item.id;
    currAgent.name = detail.name || item.name;
    currAgent.logo = detail.image || detail.logo || item.image || item.logo;
    currAgent.intro = detail.intro || item.intro || "";
    currAgent.source = detail.source ?? item.source;
    currAgent.permissions = detail.permissions ?? item.permissions;
    currAgent.member_level_ids = detail.member_level_ids ?? item.member_level_ids;
    currAgent.accessLoaded = true;
};

const loadCurrAgentAccess = async () => {
    if (!currAgent.id || currAgent.accessLoaded) return true;
    try {
        const detail = await getAgentDetail({ id: currAgent.id });
        setAgentDetail({ ...currAgent }, detail);
        return true;
    } catch (error) {
        console.error("获取智能体权限信息失败:", error);
        return false;
    }
};

const ensureCurrAgentAvailable = async () => {
    if (!currAgent.id) return true;
    const isAccessLoaded = await loadCurrAgentAccess();
    if (!isAccessLoaded) {
        uni.$u.toast("智能体信息获取失败，请稍后再试");
        return false;
    }
    return ensureAgentAvailable(currAgent);
};

const handleAgent = async (item: any) => {
    if (!ensureAgentAvailable(item)) return;
    const detail = await getAgentDetail({ id: item.id });
    if (!ensureAgentAvailable({ ...item, ...detail })) return;
    setAgentDetail(item, detail);
    userInput.value = "";
    showAgent.value = false;
    emit("update:agent", currAgent);
};

const handleAgentClear = () => {
    currAgent.id = "";
    currAgent.name = "";
    currAgent.logo = "";
    currAgent.source = undefined;
    currAgent.permissions = undefined;
    currAgent.member_level_ids = undefined;
    currAgent.accessLoaded = false;
    emit("update:agent", null);
};

const showHumanize = ref(false);
const humanizeParams = reactive({
    top_p: 0.5,
    temperature: 1,
    presence_penalty: 0.1,
    frequency_penalty: 2,
    context_num: 3,
    top_logprobs: 10,
    logprobs: 0,
    max_tokens: 4096,
});

const getMaxTemperature = computed(() => {
    return 1;
});

const getMaxTokens = computed(() => {
    return 10000;
});

const changeHumanizeParams = (event: any, key: string, step: number) => {
    const { value } = event.detail;
    if (step == 0) {
        humanizeParams[key as keyof typeof humanizeParams] = value;
    } else {
        if (Number.isInteger(value)) {
            humanizeParams[key as keyof typeof humanizeParams] = value;
        } else {
            humanizeParams[key as keyof typeof humanizeParams] = value.toFixed(step);
        }
    }
};

const handleSetting = () => {
    if (isPhoneCtrlActive.value) {
        uni.$u.toast("已挂载设备时不能设置大模型参数");
        return;
    }
    checkLogin();
    // 打开设置时强制刷新一次参数
    loadHumanizeParams(true);
    showHumanize.value = true;
    hideKeyboard();
};

const handelChatConfig = async () => {
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await saveUserChatConfig({
            model_id: currModel.value.model_id,
            model_sub_id: currModel.value.model_sub_id,
            ...humanizeParams,
        });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        showHumanize.value = false;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
    }
};

const contentRef = shallowRef();
const userInput = ref("");
const {
    autoHeight: taAutoHeight,
    textareaStyle: taTextareaStyle,
    onTextareaFocus,
    onTextareaBlur,
} = useStableTextareaAutoHeight(userInput);
const scrollTop = ref<number>(0);

// ===== 滚动控制相关变量 =====
const disabledScroll = ref(false);
const previousScrollTop = ref(0);
const isProgrammaticScroll = ref(false);
let scrollToLowerTimer: ReturnType<typeof setTimeout> | null = null;

const { dynamicHeight, hideKeyboard } = useKeyboardHeight();
const { safeAreaInsets, windowWidth, platform } = uni.getSystemInfoSync();

const tabbarHeight = computed(() => {
    const fixedHeight = platform === "android" ? 95 : 125;
    return fixedHeight + (safeAreaInsets?.bottom ?? 0);
});

const bottomOffset = computed(() => {
    const otherHeight = 70 + (props.isStaff ? 20 : 0);
    return props.isHome ? tabbarHeight.value + otherHeight : otherHeight;
});

const keyboardOpen = computed(() => dynamicHeight.value > 0);

const spacerHeight = computed(() => {
    if (!keyboardOpen.value) return 0;
    return Math.max(0, (dynamicHeight.value * 750) / windowWidth - bottomOffset.value);
});

const { showToolbar, guardToolbarAction } = useToolbarRevealGuard(keyboardOpen);
const { markSent, isUploadMisfire } = useUploadMisfireGuard();

/** 点空白收键盘：只调系统收起，高度交给 onKeyboardHeightChange */
const dismissKeyboard = () => {
    uni.hideKeyboard();
};

const handleInput = (e: any) => {
    if (props.isAgent) return;
    if (userInput.value.indexOf("@") == 0 && userInput.value.length == 1) {
        openAgentPopup();
        uni.hideKeyboard();
    }
};

const handleScroll = (e: any) => {
    const currentScrollTop = e.detail.scrollTop;

    if (isProgrammaticScroll.value) {
        return;
    }

    if (currentScrollTop < previousScrollTop.value - 50) {
        disabledScroll.value = true;
    }

    previousScrollTop.value = currentScrollTop;
};

/**
 * scrolltolower 事件：用户真正滚动到底部时才恢复自动滚动
 * 加防抖避免边界抖动反复触发
 */
const handleScrollToLower = () => {
    if (scrollToLowerTimer) clearTimeout(scrollToLowerTimer);
    scrollToLowerTimer = setTimeout(() => {
        disabledScroll.value = false;
    }, 100);
};

/** 切工作室前先收键盘并 stop，避免点穿到底下发送按钮 */
const onWorkbenchModeClick = (mode: string) => {
    hideKeyboard();
    uni.hideKeyboard();
    emit("workbench-change", mode);
};

const contentPost = async () => {
    if (!isLogin.value) {
        checkLogin();
        return;
    }
    if (userInput.value.replace(/(^\s*)|(\s*$)/g, "") == "" && fileList.value.length == 0) {
        uni.$u.toast("输入为空");
        return;
    }
    if (!(await ensureCurrAgentAvailable())) return;
    if (props.sendDisabled) return;
    markSent();
    emit("contentPost", userInput.value);

    // 发送消息时重置滚动禁用状态，确保新消息可以自动滚到底部
    disabledScroll.value = false;
    previousScrollTop.value = 0;

    nextTick(() => {
        scrollToBottom();
    });
    inputBlur();
    userInput.value = "";
    fileList.value = [];
    emit("update:fileList", []);
};

/** 空内容时按钮为禁用态：避免键盘收起等误触点击弹出“输入为空” */
const onSendClick = () => {
    if (isSendDisabled.value) return;
    contentPost();
};

const chatClose = () => {
    emit("close");
};

const openAgentPopup = () => {
    showAgent.value = true;
};

const checkLogin = () => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/packages/pages/login/login" });
        return;
    }
};

const { proxy }: any = getCurrentInstance();

const applyScrollTop = (targetTop: number) => {
    isProgrammaticScroll.value = true;
    const top = Math.max(0, targetTop) + 80;
    if (scrollTop.value === top) {
        scrollTop.value = Math.max(0, top - 1);
        nextTick(() => {
            scrollTop.value = top;
        });
    } else {
        scrollTop.value = top;
    }
    setTimeout(() => {
        isProgrammaticScroll.value = false;
        previousScrollTop.value = top;
    }, 300);
};

const scrollToBottom = async (retries: number[] = [0, 80, 220, 500, 900]) => {
    if (disabledScroll.value) return;

    const measure = async () => {
        await nextTick();
        try {
            const res: any = await getRect(".content-box", false, proxy);
            applyScrollTop(Number(res?.height) || 0);
        } catch {
            /* ignore */
        }
    };

    await measure();
    retries.slice(1).forEach((delay) => {
        setTimeout(() => {
            if (!disabledScroll.value) measure();
        }, delay);
    });
};

const deleteFile = (index: number) => {
    fileList.value.splice(index, 1);
};

const setUserInput = (value = "") => {
    userInput.value = value;
};

const textareaRef = shallowRef();
const inputBlur = () => {
    textareaRef.value?.blur && textareaRef.value?.blur();
    uni.hideKeyboard();
};

const agentList = ref<any[]>([]);
const agentPagingRef = shallowRef();
const getAgentList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getAgentListApi({ page_no, page_size });
        agentPagingRef.value?.complete(lists);
    } catch (error: any) {
        agentPagingRef.value?.complete([]);
    }
};

const toPage = (page: string) => {
    uni.$u.route({ url: `/ai_modules/${page}/pages/index/index` });
};

// 仅在允许列表从空→有数据时拉一次设置，避免 deep watch 反复触发
watch(
    getAIModels,
    (list, prev) => {
        if (!list.length) return;
        const prevLen = prev?.length || 0;
        syncDefaultModel();
        const matched = findModelInList(currModel.value, list);
        if (!matched) {
            const first = list[0];
            currModel.value = cloneModel(first);
            loadHumanizeParams(false);
            return;
        }
        // 列表首次就绪时补一次；之后同模型不重复请求
        if (prevLen === 0) {
            loadHumanizeParams(false);
        }
    },
    { immediate: true },
);

watch(
    () => props.contentList,
    (newVal) => {
        if (newVal.length === 0) {
            disabledScroll.value = false;
            previousScrollTop.value = 0;
            isProgrammaticScroll.value = false;
            scrollTop.value = 0;
        }
    },
    { deep: false },
);

onUnmounted(() => {
    chatClose();
    hideKeyboard();
    if (scrollToLowerTimer) clearTimeout(scrollToLowerTimer);
});

defineExpose({
    scrollToBottom,
    resetScroll: () => {
        disabledScroll.value = false;
        previousScrollTop.value = 0;
        isProgrammaticScroll.value = false;
        scrollTop.value = 0;
    },
    setUserInput,
    getChatConfig: () => {
        const model = displayModel.value;
        if (model?.model_id && !currModel.value?.model_id) {
            currModel.value = cloneModel(model);
        }
        return {
            model_id: model?.model_id || undefined,
            model_sub_id: model?.model_sub_id || undefined,
            robot_id: currAgent.id || undefined,
            ...humanizeParams,
        };
    },
    setAgentConfig: (params: any) => {
        setFormData(params, currAgent);
        currAgent.accessLoaded = false;
        loadCurrAgentAccess().then((isAccessLoaded) => {
            if (isAccessLoaded) emit("update:agent", currAgent);
        });
    },
    handleAgent,
    handleAgentClear,
    handleModel,
    handleModelClear,
    syncDefaultModel,
    restoreBottomBarAfterPhoneCtrl,
    getMountedDevices: () => mountedDevices.value,
    clearMountedDevices: exitPhoneControlMode,
    hideKeyboard,
    openKeyboard: () => undefined,
});
</script>

<style lang="scss" scoped>
.chat-bottom-white {
    box-shadow: 0 -2rpx 16rpx rgba(0, 0, 0, 0.05);
}
.toolbar-collapsed {
    @apply h-0 mb-0 opacity-0 overflow-hidden pointer-events-none;
}
.ctrl-mode-bar {
    @apply flex items-center gap-x-2 mb-2 px-[20rpx] py-[12rpx] rounded-[20rpx] border border-solid text-[#4338CA];
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border-color: #c7d2fe;
    animation: ctrlBarIn 0.26s ease;
}
@keyframes ctrlBarIn {
    from {
        opacity: 0;
        transform: translateY(4rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.ctrl-dot {
    @apply w-[14rpx] h-[14rpx] rounded-full flex-shrink-0;
    background: #6366f1;
    animation: ctrlPulse 1.6s ease-in-out infinite;
}
@keyframes ctrlPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.55);
    }
    70% {
        box-shadow: 0 0 0 16rpx rgba(99, 102, 241, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
    }
}
.ctrl-exit {
    @apply ml-auto flex-shrink-0 text-[22rpx] px-[16rpx] py-[6rpx] rounded-full text-[#6366F1];
    background: rgba(255, 255, 255, 0.67);
}
.ctrl-exit:active {
    background: #fff;
}
.send-btn {
    @apply w-[60rpx] h-[60rpx] rounded-full flex items-center justify-center;
}
.agent-item {
    @apply flex gap-x-4 items-center bg-white rounded-[24rpx] p-[24rpx] border border-solid border-[#EFEFEF];
    box-shadow: 0px 2px 4px #eff3f8;
    &.active {
        @apply border-primary bg-primary-light-9;
    }
    &.disabled {
        @apply opacity-70;
    }
}
.agent-popup-title-row {
    @apply flex min-w-0 items-center gap-x-[8rpx];
}
.agent-popup-title {
    @apply min-w-0 flex-1 text-[28rpx] line-clamp-1 font-semibold text-[#212121];
}
.agent-access-tag {
    @apply shrink-0 rounded-full border border-solid px-[12rpx] py-[4rpx] text-[20rpx] font-semibold leading-none;
}
.agent-access-tag--free {
    @apply border-[#BBF7D0] bg-[#F0FDF4] text-[#16A34A];
}
.agent-access-tag--member {
    @apply border-[#DDD6FE] bg-[#F5F3FF] text-[#8B5CF6];
}
.chat-textarea {
    min-height: 40rpx;
    line-height: 40rpx;
    padding-top: 0;
    padding-bottom: 0;
    margin-top: 8rpx;
    box-sizing: border-box;
}
</style>
