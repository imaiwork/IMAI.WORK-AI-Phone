<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="视频号获客"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <view class="grid grid-cols-4">
                <view
                    v-for="item in STEPS"
                    :key="item.step"
                    class="flex flex-col items-center relative"
                    @click="handleStep(item.step)">
                    <view
                        v-if="item.step !== STEPS.length"
                        class="absolute top-[22rpx] left-[50%] w-full h-[2rpx] transition-all duration-300"
                        :class="step > item.step ? 'bg-primary' : 'bg-[#E5E9F0]'" />
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full flex items-center justify-center z-10 transition-all duration-300 flex-shrink-0"
                        :class="step > item.step ? 'bg-primary' : step === item.step ? 'bg-primary' : 'bg-[#E5E9F0]'">
                        <u-icon v-if="step > item.step" name="checkmark" color="#fff" size="18" />
                        <text
                            v-else
                            class="text-[20rpx] font-bold"
                            :class="step === item.step ? 'text-white' : 'text-[#9CA3AF]'">
                            {{ item.step }}
                        </text>
                    </view>
                    <text
                        class="text-[22rpx] mt-[8rpx] transition-all duration-300"
                        :class="step >= item.step ? 'text-primary font-semibold' : 'text-[#9CA3AF]'">
                        {{ item.title }}
                    </text>
                </view>
            </view>
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <view v-if="step === 1" class="px-4">
                <view class="flex items-center gap-[10rpx] mb-[24rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[30rpx] font-extrabold text-[#0D1117]">选择获客类型</text>
                </view>
                <view class="grid grid-cols-2 gap-[20rpx]">
                    <view
                        v-for="(item, index) in taskTypes"
                        :key="index"
                        class="bg-white h-[180rpx] flex flex-col items-center justify-center rounded-[24rpx] transition-all duration-200"
                        :class="
                            formData.crawl_type == item.value
                                ? 'shadow-[0_0_0_2rpx_#0065fb,0_4rpx_16rpx_rgba(0,101,251,0.12)] bg-[#F0F6FF]'
                                : 'shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]'
                        "
                        @click="formData.crawl_type = item.value">
                        <image
                            :src="formData.crawl_type == item.value ? item.primaryIcon : item.icon"
                            class="w-[56rpx] h-[56rpx]" />
                        <text
                            class="text-[28rpx] font-semibold mt-[16rpx]"
                            :class="formData.crawl_type == item.value ? 'text-primary' : 'text-[#4B5563]'">
                            {{ item.title }}
                        </text>
                    </view>
                </view>
            </view>

            <view v-if="step === 2" class="flex flex-col h-full">
                <view class="px-4 flex flex-col gap-[16rpx]">
                    <view class="flex gap-[16rpx]">
                        <view
                            class="flex-1 flex items-center justify-center gap-[10rpx] h-[96rpx] rounded-[24rpx] bg-white border border-solid border-[#E5E9F0] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]"
                            @click="handleEditClue(-1)">
                            <u-icon name="edit-pen" size="22" color="#4B5563" />
                            <text class="text-[28rpx] font-bold text-[#0D1117]">手动输入</text>
                        </view>
                        <navigator
                            :url="`/ai_modules/sph/pages/task_ai_clue/task_ai_clue?type=${
                                formData.crawl_type == 0 ? 2 : 3
                            }`"
                            hover-class="none"
                            class="flex-1 h-[96rpx] flex items-center justify-center gap-[10rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                            <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]" />
                            <text class="text-[28rpx] font-bold text-white">AI 生成</text>
                        </navigator>
                    </view>
                    <view class="flex items-center gap-[10rpx]">
                        <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                        <text class="text-[28rpx] font-extrabold text-[#0D1117]">线索词列表</text>
                        <text class="text-xs text-[#9CA3AF]">（{{ formData.keywords.length }}）</text>
                    </view>
                </view>

                <view class="grow min-h-0 mt-[16rpx]">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-4 flex flex-wrap gap-[12rpx] pb-[100rpx]">
                            <view
                                v-for="(item, index) in formData.keywords"
                                :key="index"
                                class="flex items-center gap-[10rpx] bg-white rounded-[20rpx] px-[24rpx] py-[14rpx] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                @click="handleEditClue(index)">
                                <text class="text-[#0D1117] font-medium">{{ item }}</text>
                                <view
                                    class="w-[32rpx] h-[32rpx] flex items-center justify-center bg-[#F0F2F5] rounded-full"
                                    @click.stop="handleDeleteClue(index)">
                                    <u-icon name="close" size="14" color="#9CA3AF" />
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view v-if="step === 3" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">线索识别方式</text>
                                <view
                                    class="w-[32rpx] h-[32rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center"
                                    @click="showOCRTip = true">
                                    <u-icon name="info" size="16" color="#0065fb" />
                                </view>
                            </view>
                            <view class="px-[28rpx] py-[24rpx]">
                                <u-radio-group v-model="formData.ocr_type">
                                    <u-radio :name="1" label-size="26" size="28">云端 OCR 识别</u-radio>
                                    <u-radio :name="2" label-size="26" size="28">本地识别</u-radio>
                                </u-radio-group>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <view>
                                        <text class="text-[28rpx] font-extrabold text-[#0D1117] block"
                                            >自动添加好友</text
                                        >
                                        <text class="text-[22rpx] text-[#9CA3AF] mt-[6rpx] block leading-relaxed">
                                            线索采集后将生成加好友任务，根据加微任务时间执行
                                        </text>
                                    </view>
                                </view>
                                <u-switch
                                    v-model="formData.add_type"
                                    :size="32"
                                    :active-value="1"
                                    :inactive-value="0" />
                            </view>

                            <template v-if="formData.add_type == 1">
                                <view class="px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                    <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">加微微信</text>
                                    <data-select
                                        v-model="formData.wechat_id"
                                        multiple
                                        :localdata="optionsData.wechatLists" />
                                </view>
                                <view class="px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                    <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">加微规则</text>
                                    <data-select
                                        v-model="formData.wechat_reg_type"
                                        :clear="false"
                                        :localdata="[
                                            { text: '全部', value: 0 },
                                            { text: '微信号', value: 1 },
                                            { text: '手机号', value: 2 },
                                        ]" />
                                </view>
                                <view class="px-[28rpx] py-[20rpx]">
                                    <view class="flex gap-[24rpx]">
                                        <view class="flex-1">
                                            <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">当前执行</text>
                                            <view class="flex items-center gap-[8rpx]">
                                                <view
                                                    class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] flex items-center border border-solid border-[#E5E9F0]">
                                                    <u-input
                                                        v-model="formData.add_number"
                                                        type="digit"
                                                        placeholder="请输入"
                                                        placeholder-style="font-size:26rpx;color:#C0C4CC" />
                                                </view>
                                                <text class="text-[#9CA3AF] flex-shrink-0">次</text>
                                            </view>
                                        </view>
                                        <view class="flex-1">
                                            <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">每次间隔</text>
                                            <view class="flex items-center gap-[8rpx]">
                                                <view
                                                    class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] flex items-center border border-solid border-[#E5E9F0]">
                                                    <u-input
                                                        v-model="formData.add_interval_time"
                                                        type="digit"
                                                        placeholder="请输入"
                                                        placeholder-style="font-size:26rpx;color:#C0C4CC" />
                                                </view>
                                                <text class="text-[#9CA3AF] flex-shrink-0">分钟</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </template>
                        </view>

                        <view
                            v-if="formData.add_type == 1"
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">加好友备注内容</text>
                                </view>
                                <u-switch
                                    :model-value="formData.add_remark_enable"
                                    :size="32"
                                    active-value="1"
                                    inactive-value="0"
                                    @change="(e: any) => formData.add_remark_enable = e == 1 ? 0 : 1" />
                            </view>

                            <template v-if="formData.add_remark_enable == 0">
                                <view class="px-[28rpx] py-[20rpx]">
                                    <view
                                        class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                        <u-input
                                            v-model="formData.remark"
                                            type="textarea"
                                            height="160"
                                            placeholder="请输入打招呼内容，为了避免封控，系统将自动调用AI进行去重润色"
                                            placeholder-style="font-size:26rpx;color:#C0C4CC" />
                                    </view>
                                    <view class="flex justify-end mt-[20rpx]">
                                        <view
                                            class="flex items-center gap-[8rpx] h-[72rpx] px-[36rpx] rounded-[20rpx] relative overflow-hidden shadow-[0_6rpx_16rpx_rgba(0,101,251,0.25)]"
                                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                            @click="
                                                handleGreetingContentSetting(GreetingContentSettingTypeEnum.ADD_FRIEND)
                                            ">
                                            <text class="text-xs font-bold text-white">AI 提示词设置</text>
                                        </view>
                                    </view>
                                </view>
                            </template>

                            <view v-if="formData.add_remark_enable == 1" class="px-[28rpx] py-[20rpx]">
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <view
                                        v-for="(item, index) in formData.remarks"
                                        :key="index"
                                        class="flex items-center gap-[10rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[12rpx] border border-solid border-[#E5E9F0]"
                                        @click="handleEditRemark(index)">
                                        <text class="text-xs text-[#0D1117]">{{ item }}</text>
                                        <view class="w-[2rpx] h-[24rpx] bg-[#E5E9F0]" />
                                        <view
                                            class="w-[32rpx] h-[32rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                            @click.stop="handleDeleteRemark(index)">
                                            <u-icon name="close" size="14" color="#9CA3AF" />
                                        </view>
                                    </view>
                                </view>
                                <view class="flex justify-end mt-[20rpx]">
                                    <view
                                        class="flex items-center gap-[8rpx] h-[72rpx] px-[36rpx] rounded-[20rpx] relative overflow-hidden shadow-[0_6rpx_16rpx_rgba(0,101,251,0.25)]"
                                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                        @click="showAddRemark = true">
                                        <u-icon name="plus" size="20" color="#fff" />
                                        <text class="text-xs font-bold text-white">新增备注</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <view v-if="step === 4" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">基础设置</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">任务名称</text>
                                <view
                                    class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                                    <u-input
                                        v-model="formData.name"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="请输入任务名称"
                                        maxlength="50" />
                                </view>
                            </view>
                            <navigator
                                :url="`/ai_modules/device/pages/device_choose/device_choose?device=${JSON.stringify(
                                    formData.device_codes,
                                )}`"
                                class="flex items-center justify-between px-[28rpx] h-[100rpx]"
                                hover-class="none">
                                <text class="text-xs text-[#9CA3AF]">设备选择</text>
                                <view class="flex items-center gap-[6rpx]">
                                    <text
                                        class="font-semibold"
                                        :class="formData.device_codes.length ? 'text-primary' : 'text-[#C0C4CC]'">
                                        {{
                                            formData.device_codes.length
                                                ? `${formData.device_codes.length} 个设备`
                                                : "选择设备"
                                        }}
                                    </text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </navigator>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">时间设置</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] mb-[24rpx]">
                                    <view
                                        v-for="(item, index) in TASK_EXEC_TYPE_OPTIONS"
                                        :key="index"
                                        class="flex-1 flex items-center justify-center gap-[8rpx] h-[72rpx] rounded-[16rpx] font-semibold transition-all duration-200"
                                        :class="
                                            formData.task_exec_type === item.value
                                                ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="formData.task_exec_type = item.value">
                                        <u-icon
                                            :name="item.icon"
                                            size="26"
                                            :color="formData.task_exec_type === item.value ? '#0065fb' : '#9CA3AF'" />
                                        <text>{{ item.text }}</text>
                                    </view>
                                </view>

                                <view v-if="formData.task_exec_type === 0" class="mb-[24rpx]">
                                    <text class="text-xs text-[#9CA3AF] block mb-[16rpx]">任务频率</text>
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-for="(item, index) in FREQUENCY_OPTIONS"
                                            :key="index"
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                formData.task_frep == item && currentFrequency != 5
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handleFrequency(item, index)">
                                            <text
                                                class="font-bold"
                                                :class="
                                                    formData.task_frep == item && currentFrequency != 5
                                                        ? 'text-primary'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item }}天
                                            </text>
                                        </view>
                                        <view
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                currentFrequency == 5
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handleCustomDate(1)">
                                            <text
                                                class="font-bold"
                                                :class="currentFrequency == 5 ? 'text-primary' : 'text-[#9CA3AF]'"
                                                >自定义</text
                                            >
                                        </view>
                                    </view>
                                </view>

                                <view v-if="formData.custom_date.length && currentFrequency == 5" class="mb-[24rpx]">
                                    <view class="flex items-center justify-between mb-[12rpx]">
                                        <text class="text-xs text-[#9CA3AF]">任务时间</text>
                                        <view
                                            v-if="formData.custom_date.length > 8"
                                            class="flex items-center gap-[4rpx]"
                                            @click="isExpandDate = !isExpandDate">
                                            <text class="text-xs text-[#9CA3AF]">{{
                                                isExpandDate ? "收起" : "展开"
                                            }}</text>
                                            <u-icon
                                                :name="isExpandDate ? 'arrow-up' : 'arrow-down'"
                                                size="22"
                                                color="#9CA3AF" />
                                        </view>
                                    </view>
                                    <view :class="{ 'max-h-[120rpx] overflow-hidden': !isExpandDate }">
                                        <view class="flex flex-wrap gap-[10rpx]">
                                            <view
                                                v-for="(item, index) in formData.custom_date"
                                                :key="index"
                                                class="px-[16rpx] py-[8rpx] rounded-[12rpx] bg-[#EBF2FF]">
                                                <text class="text-[22rpx] text-primary font-semibold">{{
                                                    formatDate(item)
                                                }}</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="formData.task_exec_type === 1"
                                    class="flex items-center justify-between py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5] mb-[20rpx]">
                                    <view>
                                        <text class="text-[28rpx] font-semibold text-[#0D1117] block mb-[8rpx]"
                                            >任务执行时间</text
                                        >
                                        <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed"
                                            >当内容执行完成后，任务会根据设定时间提前结束</text
                                        >
                                    </view>
                                    <view class="flex items-center gap-[12rpx] flex-shrink-0 ml-[16rpx]">
                                        <view
                                            class="w-[56rpx] h-[56rpx] rounded-[16rpx] border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                            @click="handleExecuteMinuteChange(-1)">
                                            <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                                        </view>
                                        <view
                                            class="w-[100rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                                            <u-input
                                                v-model="formData.minutes"
                                                type="digit"
                                                placeholder=""
                                                :custom-style="{
                                                    color: '#0065fb',
                                                    fontWeight: '800',
                                                    fontSize: '28rpx',
                                                    textAlign: 'center',
                                                }"
                                                input-align="center" />
                                        </view>
                                        <text class="text-xs text-[#9CA3AF]">分钟</text>
                                        <view
                                            class="w-[56rpx] h-[56rpx] rounded-[16rpx] border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                            @click="handleExecuteMinuteChange(1)">
                                            <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                                        </view>
                                    </view>
                                </view>

                                <view>
                                    <text class="text-xs text-[#9CA3AF] block mb-[16rpx]">每日执行时间</text>
                                    <template v-if="formData.task_exec_type == 1">
                                        <view
                                            class="flex items-center justify-between h-[80rpx] bg-[#F0FDF4] rounded-[16rpx] px-[20rpx] border border-solid border-[#BBF7D0]">
                                            <text class="font-semibold text-[#16A34A]">今日发布时间</text>
                                            <view
                                                class="px-[20rpx] py-[8rpx] rounded-full bg-[#DCFCE7] border border-solid border-[#BBF7D0]">
                                                <text class="text-xs font-bold text-[#16A34A]">立即执行</text>
                                            </view>
                                        </view>
                                    </template>
                                    <view v-else class="flex items-center gap-[16rpx]">
                                        <view
                                            class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                            <picker
                                                mode="time"
                                                class="w-full"
                                                :value="formData.time_config[0]"
                                                @change="handleStartTimeChange">
                                                <view class="flex items-center justify-between">
                                                    <text
                                                        class="font-semibold"
                                                        :class="
                                                            formData.time_config[0] ? 'text-primary' : 'text-[#C0C4CC]'
                                                        ">
                                                        {{ formData.time_config[0] || "开始时间" }}
                                                    </text>
                                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                                </view>
                                            </picker>
                                        </view>
                                        <text class="text-xs text-[#9CA3AF] flex-shrink-0">至</text>
                                        <view
                                            class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                            <picker
                                                mode="time"
                                                class="w-full"
                                                :value="formData.time_config[1]"
                                                :disabled="!formData.time_config[0]"
                                                @click="handleEndTimeClick"
                                                @change="handleEndTimeChange">
                                                <view class="flex items-center justify-between">
                                                    <text
                                                        class="font-semibold"
                                                        :class="
                                                            formData.time_config[1] ? 'text-primary' : 'text-[#C0C4CC]'
                                                        ">
                                                        {{ formData.time_config[1] || "结束时间" }}
                                                    </text>
                                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                                </view>
                                            </picker>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="formData.add_type == 1"
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">【加微任务】时间设置</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <u-radio-group v-model="formData.wechat_time_type">
                                    <u-radio :name="0" :size="28" label-size="26">当日获客任务完成后执行</u-radio>
                                    <u-radio :name="1" :size="28" label-size="26">自定义</u-radio>
                                </u-radio-group>

                                <view
                                    v-if="formData.wechat_time_type == 1"
                                    class="mt-[24rpx] flex flex-col gap-[20rpx]">
                                    <view>
                                        <text class="text-xs text-[#9CA3AF] block mb-[16rpx]">任务频率</text>
                                        <view class="flex flex-wrap gap-[12rpx]">
                                            <view
                                                v-for="(item, index) in FREQUENCY_OPTIONS"
                                                :key="index"
                                                class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                                :class="
                                                    formData.wechat_task_frep == item && currentWechatFrequency != 5
                                                        ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                        : 'bg-[#F0F2F5]'
                                                "
                                                @click="handleWechatFrequency(item, index)">
                                                <text
                                                    class="font-bold"
                                                    :class="
                                                        formData.wechat_task_frep == item && currentWechatFrequency != 5
                                                            ? 'text-primary'
                                                            : 'text-[#9CA3AF]'
                                                    ">
                                                    {{ item }}天
                                                </text>
                                            </view>
                                            <view
                                                class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                                :class="
                                                    currentWechatFrequency == 5
                                                        ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                        : 'bg-[#F0F2F5]'
                                                "
                                                @click="handleCustomDate(2)">
                                                <text
                                                    class="font-bold"
                                                    :class="
                                                        currentWechatFrequency == 5 ? 'text-primary' : 'text-[#9CA3AF]'
                                                    "
                                                    >自定义</text
                                                >
                                            </view>
                                        </view>
                                    </view>

                                    <view v-if="formData.wechat_custom_date.length">
                                        <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">任务日期</text>
                                        <view
                                            :class="{
                                                'max-h-[120rpx] overflow-hidden': !isWechatExpandDate,
                                            }">
                                            <view class="flex flex-wrap gap-[10rpx]">
                                                <view
                                                    v-for="(item, index) in formData.wechat_custom_date"
                                                    :key="index"
                                                    class="px-[16rpx] py-[8rpx] rounded-[12rpx] bg-[#EBF2FF]">
                                                    <text class="text-[22rpx] text-primary font-semibold">{{
                                                        formatDate(item)
                                                    }}</text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>

                                    <view>
                                        <text class="text-xs text-[#9CA3AF] block mb-[4rpx]">每日加微执行时间</text>
                                        <text class="text-[22rpx] text-[#C0C4CC] block mb-[16rpx]"
                                            >设定的时间小于获客任务时间时，将在次日执行</text
                                        >
                                        <view class="flex items-center gap-[16rpx]">
                                            <view
                                                class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                                <picker
                                                    mode="time"
                                                    class="w-full"
                                                    :value="formData.wechat_time_config[0]"
                                                    @change="handleWechatStartTimeChange">
                                                    <view class="flex items-center justify-between">
                                                        <text
                                                            class="font-semibold"
                                                            :class="
                                                                formData.wechat_time_config[0]
                                                                    ? 'text-primary'
                                                                    : 'text-[#C0C4CC]'
                                                            ">
                                                            {{ formData.wechat_time_config[0] || "开始时间" }}
                                                        </text>
                                                        <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                                    </view>
                                                </picker>
                                            </view>
                                            <text class="text-xs text-[#9CA3AF] flex-shrink-0">至</text>
                                            <view
                                                class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                                <picker
                                                    mode="time"
                                                    class="w-full"
                                                    :value="formData.wechat_time_config[1]"
                                                    :disabled="!formData.wechat_time_config[0]"
                                                    @click="handleWechatEndTimeClick"
                                                    @change="handleWechatEndTimeChange">
                                                    <view class="flex items-center justify-between">
                                                        <text
                                                            class="font-semibold"
                                                            :class="
                                                                formData.wechat_time_config[1]
                                                                    ? 'text-primary'
                                                                    : 'text-[#C0C4CC]'
                                                            ">
                                                            {{ formData.wechat_time_config[1] || "结束时间" }}
                                                        </text>
                                                        <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                                    </view>
                                                </picker>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

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
            <template v-if="step != STEPS.length">
                <view
                    v-show="step != 1"
                    class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-white"
                    @click="handleStep(step, 'prev')">
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
                    @click="handleStep(step, 'next')">
                    <text class="text-[30rpx] font-bold text-white">下一步</text>
                </view>
            </template>
            <template v-else>
                <view
                    class="flex-1 h-[100rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateTask">
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide">创建任务</text>
                </view>
            </template>
        </view>
    </view>

    <clue-edit ref="clueEditRef" v-model="showClueEdit" @confirm="handleClueConfirm" @close="showClueEdit = false" />

    <u-popup v-model="showOCRTip" mode="bottom" border-radius="24" @close="showOCRTip = false">
        <view class="p-[32rpx]">
            <view class="text-center text-[30rpx] font-bold text-[#0D1117] py-[16rpx]">线索识别方式</view>
            <view class="w-full h-[2rpx] bg-[#F0F2F5] mb-[24rpx]" />
            <view class="flex flex-col gap-[16rpx] pb-[40rpx]">
                <view class="bg-[#F7F9FC] rounded-[20rpx] p-[24rpx] border border-solid border-[#E5E9F0]">
                    <text class="font-semibold text-[#0D1117] block mb-[8rpx]"
                        >本地识别（每条扣 {{ getOCRLocalToken }} 算力）</text
                    >
                    <text class="text-xs text-[#9CA3AF] leading-relaxed"
                        >使用系统内置识别逻辑完成，识别率较依赖本地环境，复杂图片可能不够精准</text
                    >
                </view>
                <view class="bg-[#EBF2FF] rounded-[20rpx] p-[24rpx] border border-solid border-[#BFDBFE]">
                    <text class="font-semibold text-primary block mb-[8rpx]"
                        >云端 OCR 识别（每条扣 {{ getOCRCloudToken }} 算力）</text
                    >
                    <text class="text-xs text-[#4B5563] leading-relaxed"
                        >使用云端 OCR 服务识别微信号，识别率更高，支持更复杂的图片和场景</text
                    >
                </view>
            </view>
        </view>
    </u-popup>

    <u-popup v-model="showAddRemark" mode="center" border-radius="24" width="90%" closeable>
        <view class="p-[32rpx]">
            <text class="text-center text-[30rpx] font-bold text-[#0D1117] block mb-[28rpx]">加好友备注文案</text>
            <view
                class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0] mb-[24rpx]">
                <u-input
                    v-model="addRemarkContent"
                    maxlength="100"
                    placeholder="请输入打招呼内容"
                    placeholder-style="font-size:26rpx;color:#C0C4CC" />
            </view>
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleAddRemark">
                <text class="text-[28rpx] font-bold text-white">立即保存</text>
            </view>
        </view>
    </u-popup>

    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
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
import { getPublishAccountList } from "@/api/device";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { useDictOptions } from "@/hooks/useDictOptions";
import AccountIcon from "@/ai_modules/sph/static/icons/account.svg";
import AccountPrimaryIcon from "@/ai_modules/sph/static/icons/account_primary.svg";
import VideoIcon from "@/ai_modules/sph/static/icons/video.svg";
import VideoPrimaryIcon from "@/ai_modules/sph/static/icons/video_primary.svg";
import { ListenerTypeEnum } from "@/ai_modules/sph/enums";
import { TokensSceneEnum } from "@/enums/appEnums";
import ClueEdit from "@/ai_modules/sph/components/clue-edit/clue-edit.vue";
import TaskConflictDialog from "@/ai_modules/sph/components/task-conflict-dialog/task-conflict-dialog.vue";

// ── 引入拆分的 Hooks & 常量 ──────────────────────────────────────
import {
    CrawlType,
    GreetingContentSettingTypeEnum,
    STEPS,
    TASK_EXEC_TYPE_OPTIONS,
    FREQUENCY_OPTIONS,
} from "./hooks/types";
import type { SphFormData } from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useClueStep } from "./hooks/useClue";
import { useRemarkStep } from "./hooks/useRemark";
import { useTimeConfig } from "./hooks/useTimeConfig";

// ── Store ────────────────────────────────────────────────────────
const appStore = useAppStore();
const userStore = useUserStore();
const { on } = useEventBusManager();

const getWechatRemarks = computed(() => appStore.config.wechat_remarks || []);
const getOCRCloudToken = computed(() => userStore.getTokenByScene(TokensSceneEnum.SPH_OCR)?.score);
const getOCRLocalToken = computed(() => userStore.getTokenByScene(TokensSceneEnum.SPH_LOCAL_OCR)?.score);

const taskTypes = [
    {
        title: "账号获客",
        value: CrawlType.ACCOUNT,
        icon: AccountIcon,
        primaryIcon: AccountPrimaryIcon,
    },
    { title: "视频获客", value: CrawlType.VIDEO, icon: VideoIcon, primaryIcon: VideoPrimaryIcon },
];

// ── 共享 formData ────────────────────────────────────────────────
const formData = reactive<SphFormData>({
    name: `视频号获客任务${uni.$u.timeFormat(Date.now(), "yyyymmddhhMM")}`,
    crawl_type: CrawlType.ACCOUNT,
    chat_type: "0",
    chat_number: 30,
    chat_interval_time: 10,
    greeting_content: "",
    add_type: 1,
    remark: "",
    add_number: 15,
    add_interval_time: 10,
    private_message_prompt: "",
    add_friends_prompt: "",
    wechat_id: "",
    wechat_reg_type: 0,
    add_remark_enable: 1,
    ocr_type: 1,
    remarks: getWechatRemarks.value,
    keywords: [],
    device_codes: [],
    task_frep: 1,
    time_config: ["09:00", "09:15"],
    custom_date: [],
    wechat_time_type: 0,
    wechat_task_frep: 1,
    wechat_time_config: ["09:00", "09:15"],
    wechat_custom_date: [],
    task_exec_type: 1,
    minutes: 15,
    task_ids: [],
});

// ── Hooks ────────────────────────────────────────────────────────
const { step, canNext, handleStep } = useStep(formData);

const { showClueEdit, clueEditRef, handleEditClue, handleClueConfirm, handleDeleteClue } = useClueStep(formData);

const {
    showAddRemark,
    showOCRTip,
    addRemarkContent,
    handleGreetingContentSetting,
    handleAddRemark,
    handleEditRemark,
    handleDeleteRemark,
} = useRemarkStep(formData);

const {
    currentFrequency,
    currentWechatFrequency,
    isExpandDate,
    isWechatExpandDate,
    taskErrorMsg,
    showTaskMsgPop,
    taskMsgPopContent,
    showCreateTaskSuccessDialog,
    formatDate,
    applyCustomDate,
    handleFrequency,
    handleWechatFrequency,
    handleCustomDate,
    handleExecuteMinuteChange,
    handleStartTimeChange,
    handleEndTimeChange,
    handleEndTimeClick,
    handleWechatStartTimeChange,
    handleWechatEndTimeChange,
    handleWechatEndTimeClick,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useTimeConfig(formData);

// ── 字典数据 ─────────────────────────────────────────────────────
const { optionsData } = useDictOptions<{ wechatLists: any[] }>({
    wechatLists: {
        api: getPublishAccountList,
        params: { page_size: 9999, type: 1 },
        transformData: (res: any) => res.lists?.map((item: any) => ({ text: item.nickname, value: item.account })),
    },
});

// ── watch ────────────────────────────────────────────────────────
watch(
    () => appStore.config.wechat_remarks,
    () => {
        formData.remarks = getWechatRemarks.value;
    },
);

// ── onLoad ───────────────────────────────────────────────────────
onLoad(({ type }: any) => {
    if (type) formData.crawl_type = parseInt(type);

    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.TASK_AI_CLUE) {
            if (data.length) formData.keywords.push(...data);
        }
        if (type === ListenerTypeEnum.CHOOSE_DEVICE) {
            if (data.length) formData.device_codes = data;
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            applyCustomDate(data || []);
        }
    });
});
</script>
