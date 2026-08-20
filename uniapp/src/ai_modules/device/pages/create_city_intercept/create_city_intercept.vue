<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="同城视频评论截流"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />
        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="step" @step="handleStep" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <view v-show="step === 1" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-[24rpx] pb-[120rpx] flex flex-col gap-[16rpx]">
                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">互动与触达动作</text>
                                </view>
                            </view>
                            <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[20rpx]">
                                <view class="flex gap-[12rpx]">
                                    <view
                                        v-for="action in FREE_ACTION_LIST"
                                        :key="action.key"
                                        class="flex-1 relative flex flex-col items-center justify-center py-[24rpx] rounded-[20rpx] border-2 border-solid transition-all duration-200"
                                        :class="
                                            formData.marker_method.includes(action.key)
                                                ? 'border-primary bg-[#EBF2FF]'
                                                : 'border-[#F0F2F5] bg-[#F7F9FC]'
                                        "
                                        @click="toggleFreeAction(action.key)">
                                        <view
                                            class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                                            :class="
                                                formData.marker_method.includes(action.key)
                                                    ? 'bg-[#0065fb]/10'
                                                    : 'bg-[#F0F2F5]'
                                            ">
                                            <u-icon
                                                :name="action.icon"
                                                :color="
                                                    formData.marker_method.includes(action.key) ? '#0065fb' : '#9CA3AF'
                                                "
                                                size="32" />
                                        </view>
                                        <text
                                            class="text-[22rpx] font-semibold"
                                            :class="
                                                formData.marker_method.includes(action.key)
                                                    ? 'text-primary'
                                                    : 'text-[#9CA3AF]'
                                            ">
                                            {{ action.label }}
                                        </text>
                                        <view
                                            v-if="formData.marker_method.includes(action.key)"
                                            class="absolute top-[8rpx] right-[8rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center">
                                            <u-icon name="checkmark" color="#fff" size="14" />
                                        </view>
                                    </view>

                                    <view
                                        class="flex-1 relative rounded-[20rpx] border-2 border-solid transition-all duration-200 overflow-hidden"
                                        :class="hasMutexSelected ? 'border-primary' : 'border-[#F0F2F5]'">
                                        <view
                                            class="absolute top-0 left-0 right-0 flex justify-center"
                                            style="z-index: 1; margin-top: -1rpx">
                                            <view
                                                class="px-[16rpx] h-[36rpx] flex items-center rounded-b-[12rpx] transition-all duration-200"
                                                :class="hasMutexSelected ? 'bg-primary' : 'bg-[#E5E9F0]'">
                                                <text
                                                    class="text-[18rpx] font-semibold"
                                                    :class="hasMutexSelected ? 'text-white' : 'text-[#9CA3AF]'">
                                                    二选一
                                                </text>
                                            </view>
                                        </view>
                                        <view class="flex h-full pt-[36rpx]">
                                            <view
                                                v-for="(action, idx) in MUTEX_ACTION_LIST"
                                                :key="action.key"
                                                class="flex-1 flex flex-col items-center justify-center py-[24rpx] transition-all duration-200 relative"
                                                :class="[
                                                    formData.marker_method.includes(action.key)
                                                        ? 'bg-[#EBF2FF]'
                                                        : 'bg-[#F7F9FC]',
                                                    idx === 0
                                                        ? 'border-[0] border-r border-solid border-[#E5E9F0]'
                                                        : '',
                                                ]"
                                                @click="toggleMutexAction(action.key)">
                                                <view
                                                    class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                                                    :class="
                                                        formData.marker_method.includes(action.key)
                                                            ? 'bg-[#0065fb]/10'
                                                            : 'bg-[#F0F2F5]'
                                                    ">
                                                    <u-icon
                                                        :name="action.icon"
                                                        :color="
                                                            formData.marker_method.includes(action.key)
                                                                ? '#0065fb'
                                                                : '#9CA3AF'
                                                        "
                                                        size="32" />
                                                </view>
                                                <text
                                                    class="text-[22rpx] font-semibold"
                                                    :class="
                                                        formData.marker_method.includes(action.key)
                                                            ? 'text-primary'
                                                            : 'text-[#9CA3AF]'
                                                    ">
                                                    {{ action.label }}
                                                </text>
                                                <view
                                                    v-if="formData.marker_method.includes(action.key)"
                                                    class="absolute top-[8rpx] right-[8rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center">
                                                    <u-icon name="checkmark" color="#fff" size="14" />
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    class="bg-[#EEF2FF] rounded-[20rpx] p-[20rpx] border border-solid border-[#C7D2FE]">
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <view class="flex items-center gap-[8rpx]">
                                            <u-icon name="link" color="#6366F1" size="28" />
                                            <text class="text-xs text-[#0D1117] font-semibold">话术关联 IP</text>
                                        </view>
                                        <view
                                            class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] rounded-[14rpx] bg-[#E0E7FF] border border-solid border-[#C7D2FE]"
                                            @click="handleSelectPerson">
                                            <u-icon name="plus" color="#6366F1" size="20" />
                                            <text class="text-[22rpx] font-semibold text-[#6366F1]">选择IP人设</text>
                                        </view>
                                    </view>
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-if="formData.persona_id"
                                            class="flex items-center gap-[8rpx] bg-white rounded-[14rpx] px-[16rpx] py-[10rpx] border border-solid border-[#C7D2FE] shadow-sm">
                                            <text class="text-[22rpx] text-[#6366F1]">{{
                                                personValue.persona_name
                                            }}</text>
                                            <view
                                                class="w-[28rpx] h-[28rpx] rounded-full bg-[#E0E7FF] flex items-center justify-center"
                                                @click="clearIpPerson">
                                                <u-icon name="close" size="14" color="#818CF8" />
                                            </view>
                                        </view>
                                        <text v-else class="text-[22rpx] text-[#9CA3AF]">暂未选择IP人设</text>
                                    </view>
                                </view>

                                <view class="flex gap-[16rpx]">
                                    <view
                                        class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                            >观看视频（秒）</text
                                        >
                                        <input
                                            v-model="formData.watch_time"
                                            type="digit"
                                            class="font-bold text-[#0D1117] h-[40rpx]" />
                                    </view>
                                    <view
                                        class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                            >触达间隔（秒）</text
                                        >
                                        <input
                                            v-model="formData.interval_time"
                                            type="digit"
                                            class="font-bold text-[#0D1117] h-[40rpx]" />
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">评论者画像</text>
                                </view>
                                <view
                                    class="bg-[#EBF2FF] rounded-[12rpx] px-[16rpx] h-[48rpx] flex items-center border border-solid border-[#BFDBFE]">
                                    <text class="text-xs font-bold text-primary">
                                        {{ formData.radius == 0 ? "全城" : formData.radius + "公里内" }}
                                    </text>
                                </view>
                            </view>
                            <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[20rpx]">
                                <view>
                                    <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]"
                                        >附近距离范围</text
                                    >
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-for="item in DISTANCE_LIST"
                                            :key="item.value"
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                !isCustomDistance && formData.radius == item.value
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handleSelectDistance(item.value)">
                                            <text
                                                class="font-bold"
                                                :class="
                                                    !isCustomDistance && formData.radius == item.value
                                                        ? 'text-primary'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item.label }}
                                            </text>
                                        </view>
                                        <view
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] border border-solid transition-all"
                                            :class="
                                                isCustomDistance
                                                    ? 'bg-[#EBF2FF] border-primary'
                                                    : 'bg-[#F0F2F5] border-[transparent]'
                                            "
                                            @click="handleSelectCustomDistance">
                                            <text
                                                class="font-bold"
                                                :class="isCustomDistance ? 'text-primary' : 'text-[#9CA3AF]'">
                                                自定义
                                            </text>
                                        </view>
                                    </view>
                                    <view v-if="isCustomDistance" class="mt-[16rpx]">
                                        <view
                                            class="flex items-center gap-[12rpx] bg-[#F5F5F5] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0]">
                                            <input
                                                v-model="customDistanceInput"
                                                type="digit"
                                                placeholder="请输入距离数值"
                                                placeholder-style="color:#BBBBBB;font-size:26rpx;"
                                                class="flex-1 text-[28rpx] font-bold text-[#212121]"
                                                @blur="handleCustomDistanceBlur" />
                                            <text class="text-[#888888] flex-shrink-0">公里</text>
                                        </view>
                                        <text
                                            v-if="customDistanceError"
                                            class="text-[22rpx] text-error mt-[8rpx] block">
                                            {{ customDistanceError }}
                                        </text>
                                    </view>
                                </view>

                                <view class="h-[1rpx] bg-[#F0F2F5]" />

                                <view class="flex gap-[32rpx]">
                                    <view class="flex-1 flex flex-col">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]"
                                            >性别要求</text
                                        >
                                        <view
                                            class="flex flex-1 bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0]">
                                            <view
                                                v-for="item in GENDER_LIST"
                                                :key="item.value"
                                                class="flex-1 py-[12rpx] flex items-center justify-center rounded-[10rpx] transition-all duration-200"
                                                :class="
                                                    formData.gender === item.value
                                                        ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                                        : ''
                                                "
                                                @click="formData.gender = item.value">
                                                <text
                                                    class="font-semibold"
                                                    :class="
                                                        formData.gender === item.value
                                                            ? 'text-[#0D1117]'
                                                            : 'text-[#9CA3AF]'
                                                    ">
                                                    {{ item.label }}
                                                </text>
                                            </view>
                                        </view>
                                    </view>
                                    <view class="flex-1 flex flex-col">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]"
                                            >年龄范围</text
                                        >
                                        <view
                                            class="flex items-center bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0] gap-[6rpx]">
                                            <view
                                                class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                                <input
                                                    v-model="formData.age_min"
                                                    type="digit"
                                                    class="font-bold text-center text-[#0D1117]" />
                                            </view>
                                            <text class="text-[#9CA3AF] text-xs">-</text>
                                            <view
                                                class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                                <input
                                                    v-model="formData.age_max"
                                                    type="digit"
                                                    class="font-bold text-center text-[#0D1117]" />
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">作品与账号过滤</text>
                            </view>
                            <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[20rpx]">
                                <view class="flex gap-[32rpx]">
                                    <view
                                        class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <view class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                            >视频作品满足（赞）</view
                                        >
                                        <view class="flex items-center gap-[8rpx]">
                                            <input
                                                v-model="formData.like_num"
                                                class="font-bold text-[32rpx] text-center text-[#0D1117] h-[40rpx]"
                                                type="digit" />
                                            <text class="text-[22rpx] text-[#9CA3AF] whitespace-nowrap">以上</text>
                                        </view>
                                    </view>
                                    <view
                                        class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                            >视频评论数不大于</text
                                        >
                                        <view class="flex items-center gap-[8rpx]">
                                            <input
                                                v-model="formData.comment_num"
                                                type="digit"
                                                class="font-bold text-[32rpx] text-center text-[#0D1117] h-[40rpx]" />
                                            <text class="text-[22rpx] text-[#9CA3AF] whitespace-nowrap">条</text>
                                        </view>
                                    </view>
                                </view>
                                <view class="flex gap-[32rpx]">
                                    <view>
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]"
                                            >目标评论粉丝数量</text
                                        >
                                        <view
                                            class="flex items-center bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0] gap-[6rpx]">
                                            <view
                                                class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                                <input
                                                    v-model="formData.comment_fans_min_num"
                                                    type="digit"
                                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                                            </view>
                                            <text class="text-[#9CA3AF] text-xs">-</text>
                                            <view
                                                class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                                <input
                                                    v-model="formData.comment_fans_max_num"
                                                    type="digit"
                                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                                            </view>
                                        </view>
                                    </view>

                                    <view>
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]"
                                            >目标评论关注数量</text
                                        >
                                        <view
                                            class="flex items-center bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0] gap-[6rpx]">
                                            <view
                                                class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                                <input
                                                    v-model="formData.comment_follow_min_num"
                                                    type="digit"
                                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                                            </view>
                                            <text class="text-[#9CA3AF] text-xs">-</text>
                                            <view
                                                class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                                <input
                                                    v-model="formData.comment_follow_max_num"
                                                    type="digit"
                                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <view>
                                    <view class="flex items-center justify-between mb-[14rpx]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF]">
                                            <text class="text-error">*</text>
                                            评论必须包含以下关键词(必填项)
                                        </text>
                                        <view
                                            v-if="formData.include_filter.length > 0"
                                            class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#FEF2F2] border border-solid border-[#FECACA]"
                                            @click="handleClearAllIncludeFilter">
                                            <u-icon name="trash" size="22" color="#EF4444" />
                                            <text class="text-xs font-semibold text-[#EF4444]">一键删除</text>
                                        </view>
                                    </view>
                                    <view
                                        class="bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <view v-if="formData.include_filter.length > 0" class="mb-[12rpx]">
                                            <text class="text-xs text-[#9CA3AF]"
                                                >共 {{ formData.include_filter.length }} 个关键词</text
                                            >
                                        </view>
                                        <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                                            <view
                                                v-for="(name, idx) in visibleIncludeFilter"
                                                :key="idx"
                                                class="flex items-center gap-[8rpx] bg-white rounded-full px-[16rpx] h-[52rpx] border border-solid border-[#E5E9F0]"
                                                @click="openKeywordsEdit(idx, KeyEditTarget.Keywords)">
                                                <text class="text-xs text-[#4B5563]">{{ name }}</text>
                                                <view
                                                    class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                                    @click.stop="formData.include_filter.splice(idx, 1)">
                                                    <u-icon name="close" size="14" color="#9CA3AF" />
                                                </view>
                                            </view>
                                            <view
                                                v-if="formData.include_filter.length === 0"
                                                class="w-full flex justify-center py-[8rpx]">
                                                <text class="text-xs text-[#C0C4CC]">暂无包含词</text>
                                            </view>
                                        </view>
                                        <view
                                            v-if="includeFilterOverflow"
                                            class="flex items-center justify-center gap-[8rpx] mb-[16rpx] py-[10rpx] rounded-[16rpx] bg-[#F0F6FF] border border-solid border-[#BFDBFE]"
                                            @click="toggleIncludeFilter">
                                            <text class="text-xs text-primary font-semibold">
                                                {{
                                                    includeFilterExpanded
                                                        ? "收起"
                                                        : `展开全部 ${formData.include_filter.length} 个`
                                                }}
                                            </text>
                                            <u-icon
                                                :name="includeFilterExpanded ? 'arrow-up' : 'arrow-down'"
                                                color="#0065fb"
                                                size="22" />
                                        </view>
                                        <view class="flex gap-[12rpx]">
                                            <view
                                                class="flex-1 bg-white rounded-[14rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                                                <u-input
                                                    v-model="includeNameInput"
                                                    placeholder="如：怎么买、划算吗..."
                                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                                    @confirm="handleAddIncludeName" />
                                            </view>
                                            <view
                                                class="px-[28rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                                @click="handleAddIncludeName">
                                                <text class="font-semibold text-white">添加</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view class="h-[1rpx] bg-[#F0F2F5]" />

                                <view>
                                    <view class="flex items-center justify-between mb-[14rpx]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF]"
                                            >对方昵称不包含（防误触）</text
                                        >
                                        <view
                                            v-if="formData.nickname_filter.length > 0"
                                            class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#FEF2F2] border border-solid border-[#FECACA]"
                                            @click="handleClearAllNicknameFilter">
                                            <u-icon name="trash" size="22" color="#EF4444" />
                                            <text class="text-xs font-semibold text-[#EF4444]">一键删除</text>
                                        </view>
                                    </view>
                                    <view
                                        class="bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <view v-if="formData.nickname_filter.length > 0" class="mb-[12rpx]">
                                            <text class="text-xs text-[#9CA3AF]"
                                                >共 {{ formData.nickname_filter.length }} 个排除词</text
                                            >
                                        </view>
                                        <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                                            <view
                                                v-for="(name, idx) in visibleNicknameFilter"
                                                :key="idx"
                                                class="flex items-center gap-[8rpx] bg-white rounded-full px-[16rpx] h-[52rpx] border border-solid border-[#E5E9F0]"
                                                @click="openKeywordsEdit(idx, KeyEditTarget.NicknameFilter)">
                                                <text class="text-xs text-[#4B5563]">{{ name }}</text>
                                                <view
                                                    class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                                    @click.stop="formData.nickname_filter.splice(idx, 1)">
                                                    <u-icon name="close" size="14" color="#9CA3AF" />
                                                </view>
                                            </view>
                                            <view
                                                v-if="formData.nickname_filter.length === 0"
                                                class="w-full flex justify-center py-[8rpx]">
                                                <text class="text-xs text-[#C0C4CC]">暂无排除词</text>
                                            </view>
                                        </view>
                                        <view
                                            v-if="nicknameFilterOverflow"
                                            class="flex items-center justify-center gap-[8rpx] mb-[16rpx] py-[10rpx] rounded-[16rpx] bg-[#F0F6FF] border border-solid border-[#BFDBFE]"
                                            @click="toggleNicknameFilter">
                                            <text class="text-xs text-primary font-semibold">
                                                {{
                                                    nicknameFilterExpanded
                                                        ? "收起"
                                                        : `展开全部 ${formData.nickname_filter.length} 个`
                                                }}
                                            </text>
                                            <u-icon
                                                :name="nicknameFilterExpanded ? 'arrow-up' : 'arrow-down'"
                                                color="#0065fb"
                                                size="22" />
                                        </view>
                                        <view class="flex gap-[12rpx]">
                                            <view
                                                class="flex-1 bg-white rounded-[14rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                                                <u-input
                                                    v-model="excludeNameInput"
                                                    placeholder="如：店长、主播..."
                                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                                    @confirm="handleAddExcludeName" />
                                            </view>
                                            <view
                                                class="px-[28rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                                @click="handleAddExcludeName">
                                                <text class="font-semibold text-white">添加</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <scroll-view v-show="step === 2" scroll-y class="h-full">
                <view class="px-[24rpx] pb-[120rpx]">
                    <base-setting
                        v-model="formData"
                        :show-device="false"
                        :show-accounts="true"
                        :multiple="0"
                        :current-frequency="currentFrequency"
                        :platform-types="[AppTypeEnum.DOUYIN]"
                        @change-frequency="currentFrequency = $event" />
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]"
            :class="step === 1 ? 'justify-end' : 'justify-between'">
            <view
                v-if="step !== 1"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center justify-center border border-solid border-[#E5E9F0] bg-[#F7F9FC]"
                @click="handleStep(step, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <template v-if="step !== STEPS.length">
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center transition-all duration-300"
                    :class="canNext ? 'shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
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
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateTask">
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide">创建任务</text>
                </view>
            </template>
        </view>
    </view>

    <keywords-edit
        v-model="keywordsEditShow"
        :title="keywordsEditTitle"
        ref="keywordsEditRef"
        @confirm="handleKeywordsConfirm" />
    <choose-person
        ref="choosePersonRef"
        v-model="showChoosePersonPopup"
        :limit="1"
        @select="handleChoosePersonConfirm" />
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
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum, GENDER_LIST } from "@/ai_modules/device/enums";
import Steps from "@/ai_modules/device/components/steps/steps.vue";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";

import {
    STEPS,
    FREE_ACTION_LIST,
    MUTEX_ACTION_LIST,
    DISTANCE_LIST,
    createDefaultFormData,
    KeyEditTarget,
} from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useSourceStep } from "./hooks/useSourceStep";
import { useCreateTask } from "./hooks/useCreateTask";

const { on } = useEventBusManager();

const formData = reactive(createDefaultFormData());

const { step, canNext, handleStep } = useStep(formData);

const {
    toggleFreeAction,
    toggleMutexAction,
    hasMutexSelected,
    includeNameInput,
    excludeNameInput,
    handleAddIncludeName,
    handleAddExcludeName,
    keywordsEditShow,
    keywordsEditTitle,
    keywordsEditRef,
    openKeywordsEdit,
    handleKeywordsConfirm,
    includeFilterExpanded,
    visibleIncludeFilter,
    includeFilterOverflow,
    toggleIncludeFilter,
    handleClearAllIncludeFilter,
    handleClearAllNicknameFilter,
    nicknameFilterExpanded,
    visibleNicknameFilter,
    nicknameFilterOverflow,
    toggleNicknameFilter,
    clearIpPerson,
    choosePersonRef,
    showChoosePersonPopup,
    personValue,
    handleSelectPerson,
    handleChoosePersonConfirm,
    isCustomDistance,
    customDistanceInput,
    customDistanceError,
    handleSelectDistance,
    handleSelectCustomDistance,
    handleCustomDistanceBlur,
} = useSourceStep(formData);

const {
    currentFrequency,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask(formData);

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;

        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (!data?.length) {
                currentFrequency.value = 0;
                formData.custom_date = [];
            } else {
                formData.custom_date = data;
                currentFrequency.value = 5;
            }
            return;
        }

        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            formData.accounts = data?.length
                ? data.map((item: any) => ({ id: item.id, account: item.account, type: item.type }))
                : [];
        }
    });
});
</script>

<style scoped></style>
