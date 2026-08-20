<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            :title="isCollect ? '留痕获客' : '截流获客'"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="currentStep" @step="handleStep" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <scroll-view class="h-full" scroll-y v-show="currentStep === 1">
                <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">获客方式</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <u-radio-group v-model="formData.customer_type">
                                <view class="flex flex-wrap gap-[16rpx]">
                                    <view
                                        v-for="item in [
                                            { value: 0, label: '自由获客' },
                                            { value: 1, label: '同城获客' },
                                        ]"
                                        :key="item.value"
                                        class="flex items-center gap-[8rpx]">
                                        <view
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                formData.customer_type === item.value
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="formData.customer_type = item.value">
                                            <text
                                                class="font-bold"
                                                :class="
                                                    formData.customer_type === item.value
                                                        ? 'text-primary'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item.label }}
                                            </text>
                                        </view>
                                        <view v-if="item.value === 1" @click.stop="showTemp = true">
                                            <u-icon name="question-circle-fill" color="#BFDBFE" size="28" />
                                        </view>
                                    </view>
                                </view>
                            </u-radio-group>

                            <view v-if="formData.customer_type === 0" class="mt-[20rpx]">
                                <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">目标地区</text>
                                <view
                                    class="flex items-center justify-between bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0]"
                                    @click="showRegionFormPopup = true">
                                    <text
                                        class="font-semibold"
                                        :class="formData.region ? 'text-primary' : 'text-[#C0C4CC]'">
                                        {{ formData.region || "无限制" }}
                                    </text>
                                    <view class="flex items-center gap-[6rpx]">
                                        <text class="text-xs text-primary font-semibold">修改</text>
                                        <u-icon name="arrow-right" color="#0065fb" size="20" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        v-if="formData.customer_type === 0"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">获客线索词</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <view class="flex items-center gap-[12rpx]">
                                <view
                                    class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] flex items-center border border-solid border-[#E5E9F0]">
                                    <u-input
                                        class="w-full"
                                        v-model="industryInput"
                                        placeholder="请输入，如：服装设计、女装"
                                        maxlength="100"
                                        clearable
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;" />
                                </view>
                                <view
                                    class="w-[140rpx] h-[80rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.20)]"
                                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                    @click="handleAddIndustry">
                                    <text class="font-bold text-white">添加</text>
                                </view>
                            </view>

                            <view class="mt-[16rpx] bg-[#F7F9FC] rounded-[20rpx] border border-solid border-[#E5E9F0]">
                                <scroll-view
                                    v-if="formData.industry.length > 0"
                                    class="max-h-[300rpx]"
                                    ref="industryScrollViewRef"
                                    scroll-y
                                    :scroll-into-view="scrollToIndustryId"
                                    scroll-with-animation="true">
                                    <view class="flex flex-wrap gap-[12rpx] p-[20rpx]">
                                        <view
                                            v-for="(item, index) in formData.industry"
                                            :key="index"
                                            :id="'industry_' + index"
                                            class="flex items-center gap-[8rpx] bg-white rounded-[16rpx] px-[20rpx] py-[10rpx] border border-solid border-[#E5E9F0] shadow-[0_1rpx_4rpx_rgba(0,0,0,0.04)]"
                                            @click="handleEditClue(index)">
                                            <text class="text-xs text-[#0D1117]">{{ item }}</text>
                                            <view
                                                class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                                @click.stop="handleDeleteClue(index)">
                                                <u-icon name="close" color="#9CA3AF" size="14" />
                                            </view>
                                        </view>
                                    </view>
                                </scroll-view>
                                <view v-else class="py-[40rpx] text-center">
                                    <text class="text-[#C0C4CC]">输入或 AI 生成获客行业</text>
                                </view>
                                <view class="px-[20rpx] py-[16rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                    <view
                                        class="flex items-center gap-[6rpx] w-fit h-[60rpx] px-[24rpx] bg-[#EBF2FF] rounded-[14rpx] border border-solid border-[#BFDBFE]"
                                        @click="showClueGenPopup = true">
                                        <image
                                            src="@/ai_modules/device/static/icons/gen.svg"
                                            class="w-[24rpx] h-[24rpx]" />
                                        <text class="text-xs font-semibold text-primary">AI 生成</text>
                                    </view>
                                </view>
                            </view>
                            <view v-if="historyIndustry.length > 0" class="mt-[20rpx]">
                                <view class="flex items-center justify-between mb-[16rpx]">
                                    <text class="text-xs text-[#9CA3AF] font-semibold">历史记录</text>
                                    <view class="flex items-center gap-[4rpx]" @click="showHistoryIndustryPopup = true">
                                        <text class="text-xs text-primary font-semibold">更多</text>
                                        <u-icon name="arrow-right" color="#0065fb" size="18" />
                                    </view>
                                </view>
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <view
                                        v-for="(item, index) in historyIndustry.slice(0, 5)"
                                        :key="index"
                                        class="relative flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[10rpx] border border-solid border-[#E5E9F0]"
                                        @click="handleSelectHistoryIndustry(item.keyword)">
                                        <text class="text-xs text-[#4B5563]">{{ item.keyword }}</text>
                                        <view
                                            class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                            @click.stop="handleDeleteHistoryIndustry(index)">
                                            <u-icon name="close" color="#9CA3AF" size="14" />
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        v-if="formData.customer_type === 0"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">每条线索词浏览作品数量</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="(item, index) in INDUSTRY_NUM_LIST"
                                    :key="index"
                                    class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        formData.industryNum === item &&
                                        industryNumState.currentIndex !== INDUSTRY_NUM_LIST.length
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="industryNumState.handleSelect(item, index)">
                                    <text
                                        class="font-bold"
                                        :class="
                                            formData.industryNum === item &&
                                            industryNumState.currentIndex !== INDUSTRY_NUM_LIST.length
                                                ? 'text-primary'
                                                : 'text-[#9CA3AF]'
                                        ">
                                        {{ item }}条
                                    </text>
                                </view>
                                <view
                                    class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        industryNumState.currentIndex === INDUSTRY_NUM_LIST.length
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="industryNumState.openCustom">
                                    <text
                                        class="font-bold"
                                        :class="
                                            industryNumState.currentIndex === INDUSTRY_NUM_LIST.length
                                                ? 'text-primary'
                                                : 'text-[#9CA3AF]'
                                        ">
                                        {{
                                            industryNumState.currentIndex === INDUSTRY_NUM_LIST.length
                                                ? `${formData.industryNum}条`
                                                : industryNumState.savedCustomValue
                                                ? `${industryNumState.savedCustomValue}条`
                                                : "自定义"
                                        }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <scroll-view class="h-full" scroll-y v-show="currentStep === 2">
                <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                    <view
                        v-if="formData.customer_type === 0"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">评论词筛选</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <template v-if="formData.comment_filter_list.length > 0">
                                <view class="flex items-center justify-between mb-[16rpx]">
                                    <text class="text-xs text-[#9CA3AF]">包含以下关键词</text>
                                    <text class="text-xs text-primary font-semibold" @click="handleCommentFilterClear"
                                        >清空</text
                                    >
                                </view>
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <view
                                        v-for="(item, index) in displayedCommentFilterItems"
                                        :key="index"
                                        class="flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[10rpx] border border-solid border-[#E5E9F0]"
                                        @click="handleCommentFilterEdit(index)">
                                        <text class="text-xs text-[#0D1117] break-all">{{ item.value }}</text>
                                        <view
                                            class="flex-shrink-0 w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                            @click.stop="handleCommentFilterDelete(index)">
                                            <u-icon name="close" color="#9CA3AF" size="14" />
                                        </view>
                                    </view>
                                    <view
                                        class="flex items-center gap-[6rpx] h-[60rpx] px-[24rpx] rounded-[16rpx] border border-dashed border-[#BFDBFE] bg-[#F0F6FF]"
                                        @click="openCommentFilterEdit">
                                        <u-icon name="plus" color="#0065fb" size="18" />
                                        <text class="text-xs text-primary font-semibold">编辑</text>
                                    </view>
                                </view>
                                <view
                                    v-if="formData.comment_filter_list.length > commentFilterDefaultShowCount"
                                    class="flex items-center justify-center pt-[16rpx] gap-[4rpx]"
                                    @click="toggleCommentFilterExpand">
                                    <text class="text-xs text-[#9CA3AF]">
                                        {{
                                            isCommentFilterExpanded
                                                ? "收起"
                                                : `查看全部 ${formData.comment_filter_list.length} 条`
                                        }}
                                    </text>
                                    <u-icon
                                        :name="isCommentFilterExpanded ? 'arrow-up' : 'arrow-down'"
                                        color="#9CA3AF"
                                        size="20" />
                                </view>
                            </template>
                            <view v-else class="flex justify-center">
                                <view
                                    class="flex items-center gap-[8rpx] h-[80rpx] px-[40rpx] rounded-[20rpx] border border-dashed border-[#BFDBFE] bg-[#F0F6FF]"
                                    @click="openCommentFilterEdit">
                                    <u-icon name="plus" color="#0065fb" size="20" />
                                    <text class="text-primary font-semibold">添加评论词筛选</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        v-if="!isCollect"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center justify-between px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">触达方式和话术</text>
                            </view>
                            <text class="text-xs text-primary font-semibold" @click="handleCommentTypeClear">清空</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] gap-[6rpx] mb-[20rpx]">
                                <view
                                    v-for="(item, index) in ['发送私信', '发送评论']"
                                    :key="index"
                                    class="flex-1 h-[68rpx] rounded-[16rpx] flex items-center justify-center font-semibold transition-all duration-200"
                                    :class="
                                        commentIndex === index
                                            ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                            : 'text-[#9CA3AF]'
                                    "
                                    @click="
                                        commentIndex = index;
                                        reloadIndustryHistory();
                                    ">
                                    {{ item }}
                                </view>
                            </view>
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="(item, index) in displayedCommentContentItems"
                                    :key="index"
                                    class="flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[10rpx] border border-solid border-[#E5E9F0]"
                                    @click="handleEditCommentContent(index)">
                                    <text class="text-xs text-[#0D1117] break-all">{{ item }}</text>
                                    <view
                                        class="flex-shrink-0 w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                        @click.stop="handleCommentContentDelete(index)">
                                        <u-icon name="close" color="#9CA3AF" size="14" />
                                    </view>
                                </view>
                                <view
                                    class="flex items-center gap-[6rpx] h-[60rpx] px-[24rpx] rounded-[16rpx] border border-dashed border-[#BFDBFE] bg-[#F0F6FF]"
                                    @click="handleEditCommentContent(-1)">
                                    <u-icon name="plus" color="#0065fb" size="18" />
                                    <text class="text-xs text-primary font-semibold">添加</text>
                                </view>
                            </view>
                            <view
                                v-if="formData.comment_content_list.length > commentContentDefaultShowCount"
                                class="flex items-center justify-center pt-[16rpx] gap-[4rpx]"
                                @click="toggleCommentContentExpand">
                                <text class="text-xs text-[#9CA3AF]">
                                    {{
                                        isCommentContentExpanded
                                            ? "收起"
                                            : `查看全部 ${formData.comment_content_list.length} 条`
                                    }}
                                </text>
                                <u-icon
                                    :name="isCommentContentExpanded ? 'arrow-up' : 'arrow-down'"
                                    color="#9CA3AF"
                                    size="20" />
                            </view>
                        </view>
                    </view>

                    <view
                        v-if="isCollect"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">留痕方式</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <u-checkbox-group class="w-full">
                                <view class="flex flex-wrap gap-x-5 gap-y-3">
                                    <u-checkbox
                                        v-model="item.checked"
                                        v-for="(item, index) in getTouchTypeList"
                                        :key="index"
                                        :name="item.name"
                                        :size="28">
                                        <text class="">{{ item.label }}</text>
                                    </u-checkbox>
                                </view>
                            </u-checkbox-group>

                            <template v-if="isPinLunWork">
                                <view class="h-[1rpx] bg-[#F0F2F5] my-[24rpx]" />
                                <text class="font-semibold text-primary block mb-[20rpx]">评论作品方式</text>
                                <u-radio-group v-model="formData.comment_type" class="w-full">
                                    <u-radio :name="1" :size="28">
                                        <text class="">固定话术</text>
                                    </u-radio>
                                </u-radio-group>

                                <view v-if="formData.comment_type === 1" class="mt-[20rpx]">
                                    <template v-if="formData.fixed_comment_list.length > 0">
                                        <text class="text-xs text-[#9CA3AF] block mb-[16rpx]">固定话术内容</text>
                                        <view class="flex flex-wrap gap-[12rpx]">
                                            <view
                                                v-for="(item, index) in displayedFixedCommentItems"
                                                :key="index"
                                                class="flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[10rpx] border border-solid border-[#E5E9F0] max-w-full"
                                                @click="handleFixedCommentEdit(index)">
                                                <text class="text-xs text-[#0D1117] line-clamp-2 break-all">{{
                                                    item
                                                }}</text>
                                                <view
                                                    class="flex-shrink-0 w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                                    @click.stop="handleFixedCommentDelete(index)">
                                                    <u-icon name="close" color="#9CA3AF" size="14" />
                                                </view>
                                            </view>
                                            <view
                                                class="flex items-center gap-[6rpx] h-[60rpx] px-[24rpx] rounded-[16rpx] border border-dashed border-[#BFDBFE] bg-[#F0F6FF]"
                                                @click="handleFixedCommentEdit(-1)">
                                                <u-icon name="plus" color="#0065fb" size="18" />
                                                <text class="text-xs text-primary font-semibold">添加</text>
                                            </view>
                                        </view>
                                        <view
                                            v-if="formData.fixed_comment_list.length > fixedCommentDefaultShowCount"
                                            class="flex items-center justify-center pt-[16rpx] gap-[4rpx]"
                                            @click="toggleFixedCommentExpand">
                                            <text class="text-xs text-[#9CA3AF]">
                                                {{
                                                    isFixedCommentExpanded
                                                        ? "收起"
                                                        : `查看全部 ${formData.fixed_comment_list.length} 条`
                                                }}
                                            </text>
                                            <u-icon
                                                :name="isFixedCommentExpanded ? 'arrow-up' : 'arrow-down'"
                                                color="#9CA3AF"
                                                size="20" />
                                        </view>
                                    </template>
                                    <view v-else class="flex justify-center">
                                        <view
                                            class="flex items-center gap-[8rpx] h-[80rpx] px-[40rpx] rounded-[20rpx] border border-dashed border-[#BFDBFE] bg-[#F0F6FF]"
                                            @click="handleFixedCommentEdit(-1)">
                                            <u-icon name="plus" color="#0065fb" size="20" />
                                            <text class="text-primary font-semibold">添加固定话术</text>
                                        </view>
                                    </view>
                                </view>
                            </template>
                        </view>
                    </view>

                    <view
                        v-if="isCollect ? formData.customer_type === 0 : true"
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]"
                                >{{ isCollect ? "留痕用户" : "触达" }}数量上限</text
                            >
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="(item, index) in COMMENT_NUM_LIST"
                                    :key="index"
                                    class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        formData.commentNum === item &&
                                        commentNumState.currentIndex !== COMMENT_NUM_LIST.length
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="commentNumState.handleSelect(item, index)">
                                    <text
                                        class="font-bold"
                                        :class="
                                            formData.commentNum === item &&
                                            commentNumState.currentIndex !== COMMENT_NUM_LIST.length
                                                ? 'text-primary'
                                                : 'text-[#9CA3AF]'
                                        ">
                                        {{ item }}条
                                    </text>
                                </view>
                                <view
                                    class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        commentNumState.currentIndex === COMMENT_NUM_LIST.length
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="commentNumState.openCustom">
                                    <text
                                        class="font-bold"
                                        :class="
                                            commentNumState.currentIndex === COMMENT_NUM_LIST.length
                                                ? 'text-primary'
                                                : 'text-[#9CA3AF]'
                                        ">
                                        {{
                                            commentNumState.currentIndex === COMMENT_NUM_LIST.length
                                                ? `${formData.commentNum}条`
                                                : commentNumState.savedCustomValue
                                                ? `${commentNumState.savedCustomValue}条`
                                                : "自定义"
                                        }}
                                    </text>
                                </view>
                            </view>
                            <view v-if="!isComment" class="mt-[12rpx]">
                                <text class="text-[22rpx] text-[#9CA3AF]">建议：私信数量不要超过 8 个 / 天 / 账号</text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <scroll-view class="h-full" scroll-y v-show="currentStep === 3">
                <view class="px-4 pb-[120rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">高级设置</text>
                        </view>

                        <view
                            v-if="formData.customer_type === 0"
                            class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view>
                                <text class="font-semibold text-[#0D1117] block mb-[6rpx]">跳过内容作者</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">开启后，将不会对内容作者的评论触达</text>
                            </view>
                            <u-switch v-model="formData.skip_author" active-value="1" inactive-value="0" :size="36" />
                        </view>

                        <view
                            class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view>
                                <text class="font-semibold text-[#0D1117] block mb-[6rpx]">过滤已执行客户</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">开启后，将不会对客户池内的客户重复触达</text>
                            </view>
                            <u-switch
                                v-model="formData.filter_executed_customer"
                                active-value="1"
                                inactive-value="0"
                                :size="36" />
                        </view>

                        <view
                            v-if="!isCollect"
                            class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view>
                                <text class="font-semibold text-[#0D1117] block mb-[6rpx]">触达时顺带点赞</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">开启后，将会对触达内容进行点赞</text>
                            </view>
                            <u-switch v-model="formData.comment_like" active-value="1" inactive-value="0" :size="36" />
                        </view>

                        <view
                            v-if="!isCollect"
                            class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view>
                                <text class="font-semibold text-[#0D1117] block mb-[6rpx]">触达时顺带关注</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">开启后，将会对触达用户进行关注</text>
                            </view>
                            <u-switch
                                v-model="formData.comment_follow"
                                active-value="1"
                                inactive-value="0"
                                :size="36" />
                        </view>

                        <view
                            class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                            @click="handleChangeTime('content')">
                            <text class="font-semibold text-[#0D1117] flex-shrink-0">内容发布时间</text>
                            <view class="flex items-center gap-[6rpx]">
                                <text
                                    class="line-clamp-1 break-all"
                                    :class="
                                        formData.content_time > -1 ? 'text-primary font-semibold' : 'text-[#C0C4CC]'
                                    ">
                                    {{ getTimeLabel("content") }}
                                </text>
                                <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                            </view>
                        </view>

                        <view
                            class="flex items-center justify-between px-[28rpx] py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                            @click="handleChangeTime('comment')">
                            <text class="font-semibold text-[#0D1117] flex-shrink-0">评论发布时间</text>
                            <view class="flex items-center gap-[6rpx]">
                                <text
                                    class="line-clamp-1 break-all"
                                    :class="
                                        formData.comment_time > -1 ? 'text-primary font-semibold' : 'text-[#C0C4CC]'
                                    ">
                                    {{ getTimeLabel("comment") }}
                                </text>
                                <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                            </view>
                        </view>

                        <view
                            class="flex items-center justify-between px-[28rpx] py-[24rpx]"
                            @click="showChooseRegionPopup = true">
                            <text class="font-semibold text-[#0D1117] flex-shrink-0">客户地区</text>
                            <view class="flex items-center gap-[6rpx]">
                                <text class="text-primary font-semibold line-clamp-1 break-all">
                                    {{
                                        formData.comment_region.length > 0 ? formData.comment_region.join(";") : "不限"
                                    }}
                                </text>
                                <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <scroll-view v-if="currentStep === 4" scroll-y class="h-full">
                <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                    <base-setting
                        v-model="formData"
                        :show-device="false"
                        :show-accounts="true"
                        :current-frequency="currentFrequency"
                        :platform-types="[AppTypeEnum.XHS, AppTypeEnum.DOUYIN]"
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

        <view
            class="flex-shrink-0 bg-white border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]"
            :class="currentStep === 1 ? 'justify-end' : 'justify-between'">
            <view
                v-if="currentStep !== 1"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(currentStep, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <template v-if="currentStep != STEPS.length">
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

        <u-popup
            v-model="commonPopup.show"
            mode="center"
            width="90%"
            :border-radius="20"
            @close="commonPopup.show = false">
            <view class="p-[32rpx] bg-white rounded-[28rpx]">
                <text class="text-[30rpx] font-extrabold text-[#0D1117] text-center block mb-[28rpx]">{{
                    commonPopup.title
                }}</text>
                <view
                    class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0] mb-[28rpx]">
                    <u-input
                        v-model="commonPopup.inputValue"
                        placeholder="请输入"
                        type="digit"
                        placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                </view>
                <view class="flex items-center gap-[16rpx]">
                    <view
                        class="flex-1 h-[90rpx] flex items-center justify-center rounded-[20rpx] bg-[#F0F2F5]"
                        @click="commonPopup.show = false">
                        <text class="text-[28rpx] font-semibold text-[#4B5563]">取消</text>
                    </view>
                    <view
                        class="flex-1 h-[90rpx] flex items-center justify-center rounded-[20rpx] shadow-[0_6rpx_16rpx_rgba(0,101,251,0.25)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                        @click="handleCommonPopupConfirm">
                        <text class="text-[28rpx] font-bold text-white">确定</text>
                    </view>
                </view>
            </view>
        </u-popup>
    </view>

    <region-form v-model="showRegionFormPopup" :region="formData.region" @confirm="handleRegionConfirm" />
    <clue-gen-pop v-model="showClueGenPopup" @confirm="handleClueGenConfirm" />
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        :title="getKeywordsTitle"
        @confirm="handleKeywordsEditConfirm" />
    <comment-filter ref="commentFilterRef" v-model="showCommentFilterEdit" @confirm="handleCommentFilterConfirm" />
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        :show-close="false"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess" />
    <choose-region v-model="showChooseRegionPopup" @confirm="handleChooseRegionConfirm" />
    <choose-age ref="chooseAgeRef" v-model="showAgePopup" @confirm="handleAgeConfirm" />
    <choose-comment-time
        v-model="showCommentTimePopup"
        :value="commentTimeType === 'content' ? formData.content_time_index : formData.comment_time_index"
        :list="COMMENT_TIME_LIST"
        @confirm="handleCommentTimeConfirm" />
    <popup-bottom
        v-model="showHistoryIndustryPopup"
        title="获客行业记录"
        :is-disabled-touch="true"
        @close="showHistoryIndustryPopup = false">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="industryHistoryPagingRef"
                    v-model="historyIndustry"
                    :fixed="false"
                    @query="getIndustryHistory">
                    <view class="flex flex-wrap gap-[12rpx] p-4">
                        <view
                            v-for="(item, index) in historyIndustry"
                            :key="index"
                            class="relative flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[10rpx] border border-solid border-[#E5E9F0]"
                            @click="handleSelectHistoryIndustry(item.keyword)">
                            <text class="text-xs text-[#4B5563]">{{ item.keyword }}</text>
                            <view
                                class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                @click="handleDeleteHistoryIndustry(index)">
                                <u-icon name="close" color="#9CA3AF" size="14" />
                            </view>
                        </view>
                    </view>
                </z-paging>
            </view>
        </template>
    </popup-bottom>
    <popup-bottom v-model="showTemp" title="同城获客需知" custom-class="bg-[#F7F9FC]">
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="p-4 flex flex-col gap-[16rpx]">
                    <text class="text-[28rpx] text-[#4B5563] font-medium"
                        >为保证某音平台的同城功能顺畅运行，需将【所在城市】放置推荐的左侧</text
                    >
                    <view
                        v-for="(step, i) in [
                            {
                                num: 1,
                                text: '在AI手机中打开某音平台，长按上方频道',
                                img: ClosureStep1,
                            },
                            {
                                num: 2,
                                text: '将所在城市拖放到置顶（推荐的下方）',
                                img: ClosureStep2,
                            },
                        ]"
                        :key="i"
                        class="bg-white rounded-[28rpx] p-[32rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view class="flex items-center gap-[12rpx] mb-[24rpx]">
                            <view
                                class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                                <text class="font-bold text-white">{{ step.num }}</text>
                            </view>
                            <text class="text-[28rpx] font-semibold text-[#0D1117]">{{ step.text }}</text>
                        </view>
                        <image :src="step.img" mode="widthFix" class="w-full rounded-[16rpx]" />
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
    <task-conflict-dialog
        v-if="showTaskMsgPop"
        v-model="showTaskMsgPop"
        :messages="taskMsgPopContent"
        @close="showTaskMsgPop = false"
        @confirm="handleTaskMsgPopConfirm" />
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum, CreateTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import ClosureStep1 from "@/ai_modules/device/static/images/common/closure_step1.png";
import ClosureStep2 from "@/ai_modules/device/static/images/common/closure_step2.png";
import Steps from "@/ai_modules/device/components/steps/steps.vue";
import ClueGenPop from "@/ai_modules/device/components/clue-gen-pop/clue-gen-pop.vue";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import CommentFilter from "@/ai_modules/device/components/comment-filter/comment-filter.vue";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import ChooseRegion from "@/ai_modules/device/components/choose-region/choose-region.vue";
import ChooseAge from "@/ai_modules/device/components/choose-age/choose-age.vue";
import ChooseCommentTime from "@/ai_modules/device/components/choose-comment-time/choose-comment-time.vue";
import RegionForm from "@/ai_modules/device/components/region-form/region-form.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";

import {
    STEPS,
    INDUSTRY_NUM_LIST,
    COMMENT_NUM_LIST,
    COMMENT_TIME_LIST,
    TOUCH_TYPE_LIST_DEFAULT,
    createDefaultFormData,
} from "./hooks/types";
import type { CommentFilterItem } from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useIndustryStep } from "./hooks/useIndustry";
import { useCommentStep } from "./hooks/useComment";
import { useCreateTask } from "./hooks/useCreateTask";

// ── 基础状态 ──────────────────────────────────────────────────────
const { on } = useEventBusManager();
const createType = ref<CreateTypeEnum>(CreateTypeEnum.COMMENT_MARKETING);
const commentIndex = ref(0);
const currentFrequency = ref(0);

const formData = reactive(createDefaultFormData());

const touchTypeList = ref([...TOUCH_TYPE_LIST_DEFAULT.map((i) => ({ ...i }))]);

// ── 共享 computed ─────────────────────────────────────────────────
const isCollect = computed(() => createType.value === CreateTypeEnum.COLLECT_MARKETING);
const isComment = computed(() => commentIndex.value === 1);

const getTouchTypeList = computed(() =>
    formData.customer_type == 1 ? touchTypeList.value.filter((item: any) => item.name != 1) : touchTypeList.value,
);

const isPinLunWork = computed(() => getTouchTypeList.value.some((item: any) => item.checked && item.name == 4));

// ── 共享 Map（评论内容 & 筛选词按 tab/类型缓存） ──────────────────
const commentContentMap = reactive<Record<number, string[]>>({ 0: [], 1: [] });
const commentFilterMap = reactive<Record<string, CommentFilterItem[]>>({ closure: [], collect: [] });
const currentFilterMapKey = computed(() => (isCollect.value ? "collect" : "closure"));

// ── 弹窗状态（页面级） ────────────────────────────────────────────
const showTemp = ref(false);
const showRegionFormPopup = ref(false);

// ── 自定义数量弹窗 ────────────────────────────────────────────────
const commonPopup = reactive({
    show: false,
    title: "",
    inputValue: 1,
    callback: null as ((val: number) => void) | null,
});

function useNumSelection(list: number[], initialVal: number, title: string, updateFn: (v: number) => void) {
    const currentIndex = ref(list.indexOf(initialVal));
    const savedCustomValue = ref<number | null>(null);
    const handleSelect = (item: number, index: number) => {
        currentIndex.value = index;
        updateFn(item);
    };
    const openCustom = () => {
        commonPopup.title = title;
        commonPopup.inputValue = savedCustomValue.value || 1;
        commonPopup.callback = (val) => {
            updateFn(val);
            savedCustomValue.value = val;
            currentIndex.value = list.length;
        };
        commonPopup.show = true;
    };
    return reactive({ currentIndex, savedCustomValue, handleSelect, openCustom });
}

const industryNumState = useNumSelection(
    INDUSTRY_NUM_LIST,
    formData.industryNum,
    "输入每个行业看笔记的数量",
    (val) => (formData.industryNum = val),
);
const commentNumState = useNumSelection(
    COMMENT_NUM_LIST,
    formData.commentNum,
    "输入评论数量上限",
    (val) => (formData.commentNum = val),
);

const handleCommonPopupConfirm = () => {
    const val = Number(commonPopup.inputValue);
    if (val < 1) {
        uni.$u.toast("请输入大于等于1的数字");
        return;
    }
    commonPopup.callback?.(val);
    commonPopup.show = false;
};

// ── Hook：步骤导航 ────────────────────────────────────────────────
const { step: currentStep, canNext, handleStep } = useStep(formData, isCollect, isPinLunWork, getTouchTypeList);

// ── Hook：Step1 行业线索词 ────────────────────────────────────────
const {
    industryInput,
    scrollToIndustryId,
    showClueGenPopup,
    showHistoryIndustryPopup,
    historyIndustry,
    industryHistoryPagingRef,
    handleAddIndustry,
    handleDeleteClue,
    handleClueGenConfirm,
    getIndustryHistory,
    reloadIndustryHistory,
    handleSelectHistoryIndustry,
    handleDeleteHistoryIndustry,
    getClosureCommonHistory,
} = useIndustryStep(
    formData,
    isCollect,
    isComment,
    commentIndex,
    // commentFilterRef 由 useCommentStep 提供，先声明占位，后续通过 provide/inject 或传入
    ref(null) as any,
    commentContentMap,
    commentFilterMap,
    currentFilterMapKey,
);

// ── Hook：Step2/3 评论词 & 高级设置 ──────────────────────────────
const {
    keywordsEditRef,
    commentFilterRef,
    chooseAgeRef,
    showKeywordsEdit,
    showCommentFilterEdit,
    showAgePopup,
    showChooseRegionPopup,
    showCommentTimePopup,
    getKeywordsTitle,
    commentTimeType,
    displayedCommentFilterItems,
    displayedCommentContentItems,
    displayedFixedCommentItems,
    commentFilterDefaultShowCount,
    commentContentDefaultShowCount,
    fixedCommentDefaultShowCount,
    isCommentFilterExpanded,
    isCommentContentExpanded,
    isFixedCommentExpanded,
    openCommentFilterEdit,
    handleCommentFilterEdit,
    handleCommentFilterDelete,
    handleCommentFilterConfirm,
    handleCommentFilterClear,
    handleEditCommentContent,
    handleCommentContentDelete,
    handleCommentTypeClear,
    handleFixedCommentEdit,
    handleFixedCommentDelete,
    handleKeywordsEditConfirm,
    handleEditClue,
    getTimeLabel,
    handleChangeTime,
    handleCommentTimeConfirm,
    handleChooseRegionConfirm,
    handleEditCommentAge,
    handleAgeConfirm,
    handleEditCommentGender,
    handleEditCommentAccountFeature,
    toggleCommentFilterExpand,
    toggleCommentContentExpand,
    toggleFixedCommentExpand,
} = useCommentStep(formData, isCollect, commentIndex, commentContentMap, commentFilterMap, currentFilterMapKey);

// ── Hook：Step4 创建任务 ──────────────────────────────────────────
const {
    taskErrorMsg,
    showTaskMsgPop,
    taskMsgPopContent,
    showCreateTaskSuccessDialog,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask(formData, isCollect, isPinLunWork, getTouchTypeList, isComment);

// ── 地区确认 ──────────────────────────────────────────────────────
const handleRegionConfirm = (region: string) => {
    formData.region = region;
    showRegionFormPopup.value = false;
};

// ── commentIndex 切换时同步 Map ───────────────────────────────────
watch(commentIndex, (newIdx, oldIdx) => {
    commentContentMap[oldIdx] = [...formData.comment_content_list];
    formData.comment_content_list = [...commentContentMap[newIdx]];
});

// ── onLoad ────────────────────────────────────────────────────────
onLoad((options: any) => {
    createType.value = options.type as CreateTypeEnum;
    formData.name = `${isCollect.value ? "留痕" : "截流"}获客任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`;

    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (data.length === 0) {
                currentFrequency.value = 0;
                formData.custom_date = [];
                return;
            }
            formData.custom_date = data;
            currentFrequency.value = 5;
        }
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length === 0) {
                formData.accounts = [];
                return;
            }
            formData.accounts = data.map((item: any) => ({
                id: item.id,
                account: item.account,
                type: item.type,
            }));
        }
    });

    getIndustryHistory(1, 10);
    getClosureCommonHistory();
});
</script>
<style lang="scss" scoped>
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
}
</style>
