<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="个微接管"
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
                            class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">分段回复</text>
                            </view>
                            <u-switch
                                v-model="formData.stage_reply_switch"
                                :active-value="1"
                                :inactive-value="0"
                                :size="36" />
                        </view>

                        <view class="px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <text class="text-[#9CA3AF] block mb-[20rpx]">多条消息回复设置</text>
                            <u-radio-group v-model="formData.multi_message_type" class="w-full">
                                <view class="flex flex-wrap gap-[16rpx]">
                                    <view
                                        v-for="item in [
                                            { value: 0, label: '逐条回复' },
                                            { value: 1, label: '合并回复' },
                                            { value: 2, label: '只回复最后一条' },
                                        ]"
                                        :key="item.value"
                                        class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                        :class="
                                            formData.multi_message_type === item.value
                                                ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                : 'bg-[#F0F2F5]'
                                        "
                                        @click="formData.multi_message_type = item.value as 0 | 1 | 2">
                                        <text
                                            class="font-bold"
                                            :class="
                                                formData.multi_message_type === item.value
                                                    ? 'text-primary'
                                                    : 'text-[#9CA3AF]'
                                            ">
                                            {{ item.label }}
                                        </text>
                                    </view>
                                </view>
                            </u-radio-group>
                        </view>

                        <view class="px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <text class="text-[#9CA3AF] block mb-[20rpx]">图片回复设置</text>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <view
                                    v-for="item in [
                                        { value: 1, label: '固定回复' },
                                        { value: 2, label: 'AI识别回复' },
                                        { value: 3, label: '不回复' },
                                    ]"
                                    :key="item.value"
                                    class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        formData.image_reply_type === item.value
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="formData.image_reply_type = item.value as 1 | 2 | 3">
                                    <text
                                        class="font-bold"
                                        :class="
                                            formData.image_reply_type === item.value ? 'text-primary' : 'text-[#9CA3AF]'
                                        ">
                                        {{ item.label }}
                                    </text>
                                </view>
                            </view>
                            <view v-if="formData.image_reply_type == 1" class="mt-[20rpx]">
                                <text class="text-xs text-primary font-semibold block mb-[12rpx]">固定回复内容</text>
                                <view
                                    class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                    <u-input
                                        v-model="formData.image_reply_content"
                                        placeholder="请输入固定回复内容"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC"
                                        maxlength="200"
                                        type="textarea" />
                                </view>
                            </view>
                        </view>

                        <view class="px-[28rpx] py-[24rpx]">
                            <view class="flex items-center justify-between mb-[20rpx]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">敏感词停止回复</text>
                                </view>
                                <u-switch
                                    v-model="formData.sensitive_word_switch"
                                    :active-value="1"
                                    :inactive-value="0"
                                    :size="36" />
                            </view>
                            <view v-if="formData.sensitive_word_switch == 1" class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="(item, index) in formData.sensitive_word"
                                    :key="index"
                                    class="flex items-center gap-[10rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]"
                                    @click="handleSensitiveWordEdit(index)">
                                    <text class="text-xs text-[#0D1117]">{{ item }}</text>
                                    <view
                                        class="w-[32rpx] h-[32rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                        @click.stop="handleSensitiveWordDelete(index)">
                                        <u-icon name="close" size="14" color="#9CA3AF" />
                                    </view>
                                </view>
                                <view
                                    class="flex items-center gap-[6rpx] h-[60rpx] px-[24rpx] rounded-[16rpx] border border-dashed border-[#BFDBFE] bg-[#F0F6FF]"
                                    @click="handleSensitiveWordEdit(-1)">
                                    <u-icon name="plus" color="#0065fb" size="18" />
                                    <text class="text-xs text-primary font-semibold">添加</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center justify-between px-[28rpx] py-[24rpx]"
                            :class="
                                formData.is_auto_group == 1 ? 'border-[0] border-b border-solid border-[#F0F2F5]' : ''
                            ">
                            <view>
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <view>
                                        <text class="text-[28rpx] font-extrabold text-[#0D1117] block">自动加群</text>
                                    </view>
                                </view>
                                <text class="text-[22rpx] text-[#9CA3AF] mt-[6rpx] block"
                                    >在执行微信平台任务时将对新好友进行拉群</text
                                >
                            </view>
                            <u-switch
                                v-model="formData.is_auto_group"
                                :active-value="1"
                                :inactive-value="0"
                                :size="36" />
                        </view>

                        <view
                            v-if="formData.is_auto_group == 1"
                            class="px-[28rpx] pb-[24rpx] flex flex-col gap-[24rpx]">
                            <view class="pt-[20rpx]">
                                <text class="font-semibold text-[#0D1117] block mb-[16rpx]">加群触发模式</text>
                                <view class="flex bg-[#F3F4F6] rounded-[20rpx] p-[6rpx] gap-[6rpx]">
                                    <view
                                        class="flex-1 h-[64rpx] rounded-[16rpx] flex items-center justify-center"
                                        :class="
                                            formData.group_trigger_mode === 1
                                                ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                                : ''
                                        "
                                        @click="setGroupTriggerMode(1)">
                                        <text
                                            class="text-[24rpx] font-bold"
                                            :class="
                                                formData.group_trigger_mode === 1 ? 'text-primary' : 'text-[#9CA3AF]'
                                            ">
                                            AI 意图识别
                                        </text>
                                    </view>
                                    <view
                                        class="flex-1 h-[64rpx] rounded-[16rpx] flex items-center justify-center"
                                        :class="
                                            formData.group_trigger_mode === 2
                                                ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                                : ''
                                        "
                                        @click="setGroupTriggerMode(2)">
                                        <text
                                            class="text-[24rpx] font-bold"
                                            :class="
                                                formData.group_trigger_mode === 2 ? 'text-primary' : 'text-[#9CA3AF]'
                                            ">
                                            自定义触发词
                                        </text>
                                    </view>
                                </view>
                                <view
                                    v-if="formData.group_trigger_mode === 1"
                                    class="mt-[20rpx] bg-[#EFF6FF] border border-solid border-[#BFDBFE] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[12rpx]">
                                    <u-icon name="info-circle" color="#2563EB" size="24" />
                                    <text class="flex-1 text-[24rpx] text-[#1E40AF] leading-relaxed">
                                        AI 自动识别客户对话中的拉群意图，无需关键词配置。
                                    </text>
                                </view>
                                <view v-else class="mt-[20rpx]">
                                    <view
                                        v-if="formData.group_trigger_keywords.length > 0"
                                        class="flex items-center justify-end mb-[16rpx]">
                                        <view
                                            class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#FEF2F2] border border-solid border-[#FECACA]"
                                            @click="handleClearAllGroupTriggerKeywords">
                                            <u-icon name="trash" size="22" color="#EF4444" />
                                            <text class="text-xs font-semibold text-[#EF4444]">一键清空</text>
                                        </view>
                                    </view>
                                    <view class="flex flex-wrap gap-[14rpx]">
                                        <view
                                            v-if="!formData.group_trigger_keywords.length"
                                            class="w-full min-h-[88rpx] rounded-[24rpx] bg-white border border-dashed border-[#DDE6F5] flex items-center justify-center"
                                            @click="handleGroupTriggerKeywordEdit(-1)">
                                            <text class="text-[24rpx] text-[#9CA3AF]">暂无触发词，点击添加</text>
                                        </view>
                                        <view
                                            v-for="(word, index) in visibleGroupTriggerKeywords"
                                            :key="`${word}-${index}`"
                                            class="inline-flex items-center gap-[10rpx] min-h-[56rpx] max-w-full rounded-full bg-[#F8FAFF] border border-solid border-[#E7EEFC] py-[10rpx] pl-[22rpx] pr-[14rpx]"
                                            @click="handleGroupTriggerKeywordEdit(index)">
                                            <text class="min-w-0 break-all text-[24rpx] font-medium text-[#1D2129]">
                                                {{ word }}
                                            </text>
                                            <view
                                                class="w-[28rpx] h-[28rpx] rounded-full bg-[#EEF2F8] flex items-center justify-center"
                                                @click.stop="handleRemoveGroupTriggerKeyword(index)">
                                                <u-icon name="close" color="#9CA3AF" size="14" />
                                            </view>
                                        </view>
                                        <view
                                            class="inline-flex items-center gap-[8rpx] min-h-[56rpx] rounded-full bg-white border border-dashed border-[#BAD4FF] py-[10rpx] px-[24rpx]"
                                            @click="handleGroupTriggerKeywordEdit(-1)">
                                            <u-icon name="plus" color="#0065fb" size="16" />
                                            <text class="text-[24rpx] font-medium text-primary">添加</text>
                                        </view>
                                        <view
                                            v-if="hiddenGroupTriggerKeywordCount && !showGroupTriggerKeywordsMore"
                                            class="inline-flex items-center gap-[8rpx] min-h-[56rpx] rounded-full bg-[#F3F4F6] border border-solid border-[#E5E7EB] py-[10rpx] px-[24rpx]"
                                            @click="showGroupTriggerKeywordsMore = true">
                                            <text class="text-[24rpx] font-medium text-[#6B7280]">
                                                +{{ hiddenGroupTriggerKeywordCount }} 个
                                            </text>
                                            <u-icon name="arrow-down" color="#6B7280" size="18" />
                                        </view>
                                        <view
                                            v-if="
                                                showGroupTriggerKeywordsMore &&
                                                formData.group_trigger_keywords.length >
                                                    GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT
                                            "
                                            class="inline-flex items-center gap-[8rpx] min-h-[56rpx] rounded-full bg-[#F3F4F6] border border-solid border-[#E5E7EB] py-[10rpx] px-[24rpx]"
                                            @click="showGroupTriggerKeywordsMore = false">
                                            <text class="text-[24rpx] font-medium text-[#6B7280]">收起</text>
                                            <u-icon name="arrow-up" color="#6B7280" size="18" />
                                        </view>
                                    </view>
                                    <text class="block text-[24rpx] text-[#9CA3AF] mt-[20rpx]">
                                        客户聊天中命中以下任一关键词时触发自动拉群
                                    </text>
                                </view>
                            </view>

                            <view class="pt-[20rpx]">
                                <view class="flex items-center justify-between mb-[12rpx]">
                                    <view class="flex items-center gap-[10rpx]">
                                        <view
                                            class="w-[36rpx] h-[36rpx] rounded-full bg-[#FFF3E0] flex items-center justify-center">
                                            <u-icon name="account" color="#FF8C00" size="20" />
                                        </view>
                                        <text class="font-semibold text-[#0D1117]">指定拉入的销售微信（真人）</text>
                                    </view>
                                    <text class="text-[22rpx] text-[#9CA3AF]"
                                        >{{ formData.sales_wechat.length }} / 5</text
                                    >
                                </view>
                                <text class="text-[22rpx] text-[#9CA3AF] block mb-[16rpx]"
                                    >机器人建群后，会自动将其拉入群聊中作为主理人。</text
                                >
                                <view
                                    class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] flex items-center border border-solid border-[#E5E9F0]">
                                    <view class="flex-1">
                                        <u-input
                                            v-model="groupSalesInput"
                                            placeholder="请输入微信号并按确认键添加"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                            :border="false"
                                            maxlength="50"
                                            confirm-type="done"
                                            @confirm="handleAddGroupSales" />
                                    </view>
                                    <view @click="handleAddGroupSales">
                                        <u-icon name="plus" color="#0065fb" size="18" />
                                        <text class="text-[22rpx] text-primary font-semibold">添加</text>
                                    </view>
                                </view>
                                <view
                                    class="flex flex-wrap gap-[12rpx] mt-[16rpx]"
                                    v-if="formData.sales_wechat.length > 0">
                                    <view
                                        v-for="(item, index) in formData.sales_wechat"
                                        :key="index"
                                        class="flex items-center gap-[10rpx] bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[10rpx] border border-solid border-[#BFDBFE]">
                                        <text class="text-xs text-primary font-semibold">{{ item }}</text>
                                        <view
                                            class="w-[28rpx] h-[28rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center"
                                            @click="handleRemoveGroupSales(index)">
                                            <u-icon name="close" color="#0065fb" size="14" />
                                        </view>
                                    </view>
                                </view>
                                <view
                                    class="mt-[16rpx] bg-[#FFF8F0] rounded-[16rpx] px-[20rpx] py-[16rpx] flex items-start gap-[10rpx] border border-solid border-[#FFE4C4]">
                                    <u-icon name="info-circle" color="#F59E0B" size="24" />
                                    <text class="text-[22rpx] text-[#B45309] leading-relaxed flex-1">
                                        强烈建议输入【微信号】或在机器人端统一设置好【备注名】，避免因昵称包含特殊符号导致拉人失败。
                                    </text>
                                </view>
                            </view>

                            <view class="border-[0] border-t border-solid border-[#F0F2F5] pt-[20rpx]">
                                <view class="flex items-center justify-between mb-[12rpx]">
                                    <text class="font-semibold text-[#0D1117]">群名称模板</text>
                                    <text class="text-[22rpx] text-[#9CA3AF]"
                                        >{{ formData.group_name_template.length }} / 32</text
                                    >
                                </view>
                                <view
                                    class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                    <u-input
                                        v-model="formData.group_name_template"
                                        placeholder="请输入群名称模板"
                                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                        :border="false"
                                        maxlength="32"
                                        type="textarea"
                                        :auto-height="true" />
                                </view>
                                <view class="flex flex-wrap gap-[12rpx] mt-[16rpx]">
                                    <view
                                        v-for="tpl in ['{客户名}', '{销售名}', '{日期}']"
                                        :key="tpl"
                                        class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] bg-[#EBF2FF] rounded-[14rpx] border border-solid border-[#BFDBFE]"
                                        @click="insertGroupNameTemplate(tpl)">
                                        <u-icon name="plus" color="#0065fb" size="18" />
                                        <text class="text-[22rpx] text-primary font-semibold"
                                            >插入{{ tpl.replace(/[{}]/g, "") }}</text
                                        >
                                    </view>
                                </view>
                            </view>

                            <view class="border-[0] border-t border-solid border-[#F0F2F5] pt-[20rpx]">
                                <view class="flex items-center justify-between mb-[16rpx]">
                                    <text class="font-semibold text-[#0D1117]">建群后自动发送欢迎语</text>
                                    <u-switch
                                        v-model="formData.is_greeting"
                                        :active-value="1"
                                        :inactive-value="0"
                                        :size="36" />
                                </view>
                                <view v-if="formData.is_greeting == 1">
                                    <view
                                        class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                        <u-input
                                            v-model="formData.greeting_text"
                                            placeholder="请输入建群欢迎语"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                            :border="false"
                                            maxlength="500"
                                            type="textarea"
                                            :auto-height="true" />
                                    </view>
                                    <view class="flex flex-wrap gap-[12rpx] mt-[16rpx]">
                                        <view
                                            class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] bg-[#EBF2FF] rounded-[14rpx] border border-solid border-[#BFDBFE]"
                                            @click="insertWelcomeContent('{客户名}')">
                                            <u-icon name="plus" color="#0065fb" size="18" />
                                            <text class="text-[22rpx] text-primary font-semibold">插入客户名</text>
                                        </view>
                                        <view
                                            class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] bg-[#EBF2FF] rounded-[14rpx] border border-solid border-[#BFDBFE]"
                                            @click="insertWelcomeContent('@{客户}')">
                                            <u-icon name="plus" color="#0065fb" size="18" />
                                            <text class="text-[22rpx] text-primary font-semibold">@客户</text>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="border-[0] border-t border-solid border-[#F0F2F5] pt-[20rpx]">
                                <view class="flex items-center justify-between">
                                    <view class="flex-1 pr-[24rpx]">
                                        <text class="font-semibold text-[#0D1117] block">携带历史聊天记录</text>
                                        <text class="text-[22rpx] text-[#9CA3AF] mt-[8rpx] block leading-relaxed">
                                            建群后，自动将拉群前的单聊历史记录同步转发至新群聊中
                                        </text>
                                    </view>
                                    <u-switch
                                        v-model="formData.is_share_chats"
                                        :active-value="1"
                                        :inactive-value="0"
                                        :size="36" />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <view v-show="currentStep === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                        <base-setting
                            v-model="formData"
                            :show-device="false"
                            :show-accounts="true"
                            :current-frequency="currentFrequency"
                            :platform-types="[AppTypeEnum.WECHAT]"
                            :multiple="1"
                            is-wechat-private
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
            <template v-if="currentStep != STEPS.length">
                <view
                    v-if="currentStep != 1"
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
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        :title="keywordEditTitle"
        :maxlength="keywordEditMaxlength"
        @confirm="handleKeywordsConfirm" />
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

import { STEPS, DEFAULT_FORM_DATA } from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useReplySettings } from "./hooks/useReplySettings";
import { useCreateTask } from "./hooks/useCreateTask";

// ── 共享表单数据 ──────────────────────────────────────────────────
const formData = reactive(DEFAULT_FORM_DATA());

// ── Hook 组合 ─────────────────────────────────────────────────────
const { step: currentStep, canNext, handleStep } = useStep(formData);
const currentFrequency = ref(0);
const {
    showKeywordsEdit,
    keywordsEditRef,
    keywordEditTitle,
    keywordEditMaxlength,
    handleSensitiveWordEdit,
    handleSensitiveWordDelete,
    handleKeywordsConfirm,
    groupSalesInput,
    GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT,
    showGroupTriggerKeywordsMore,
    visibleGroupTriggerKeywords,
    hiddenGroupTriggerKeywordCount,
    setGroupTriggerMode,
    queryGroupTriggerKeywords,
    handleGroupTriggerKeywordEdit,
    handleRemoveGroupTriggerKeyword,
    handleClearAllGroupTriggerKeywords,
    handleAddGroupSales,
    handleRemoveGroupSales,
    insertGroupNameTemplate,
    insertWelcomeContent,
} = useReplySettings(formData);
const {
    taskErrorMsg,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask(formData, currentFrequency);

// ── EventBus ──────────────────────────────────────────────────────
const { on } = useEventBusManager();

onLoad(() => {
    queryGroupTriggerKeywords();
    on("confirm", (e: any) => {
        const { type, data } = e;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (!data.length) {
                formData.accounts = [];
                return;
            }
            formData.accounts = data.map((item: any) => ({
                id: item.id,
                account: item.account,
                type: item.type,
            }));
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (!data.length) {
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
