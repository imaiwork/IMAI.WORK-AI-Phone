<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="团购评论截流"
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
                        <view class="flex bg-[#F0F2F5] p-[6rpx] rounded-[20rpx]" v-if="false">
                            <view
                                v-for="tab in TASK_TYPE_LIST"
                                :key="tab.value"
                                class="flex-1 h-[72rpx] flex items-center justify-center rounded-[16rpx] transition-all duration-200"
                                :class="
                                    formData.group_buy_type === tab.value
                                        ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                        : ''
                                "
                                @click="formData.group_buy_type = tab.value">
                                <text
                                    class="font-bold"
                                    :class="
                                        formData.group_buy_type === tab.value ? 'text-[#0D1117]' : 'text-[#9CA3AF]'
                                    ">
                                    {{ tab.label }}
                                </text>
                            </view>
                        </view>

                        <view
                            class="rounded-[28rpx] overflow-hidden shadow-[0_8rpx_24rpx_rgba(0,101,251,0.18)]"
                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                            <view
                                class="flex items-center justify-between px-[28rpx] pt-[24rpx] pb-[20rpx] border-[0] border-b border-solid border-[rgba(255,255,255,0.15)]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[28rpx] bg-[#ffffff]/60 rounded-full" />
                                    <text class="text-xs text-white font-semibold">机器自动执行路径</text>
                                    <view class="bg-[#ffffff]/20 rounded-full px-[14rpx] h-[36rpx] flex items-center">
                                        <text class="text-[20rpx] text-[#ffffff]/80">高意向客户</text>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center gap-[6rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] h-[48rpx]">
                                    <u-icon name="play-right-fill" color="#fff" size="18" />
                                    <text class="text-[22rpx] text-white">运行逻辑</text>
                                </view>
                            </view>
                            <view class="flex items-center justify-between px-[28rpx] py-[24rpx]">
                                <view v-for="(node, idx) in currentPath" :key="idx" class="flex items-center relative">
                                    <view class="flex flex-col items-center gap-[10rpx]">
                                        <view
                                            class="w-[80rpx] h-[80rpx] rounded-[20rpx] bg-[#ffffff]/15 flex items-center justify-center shadow-[inset_0_0_0_1rpx_rgba(255,255,255,0.25)]">
                                            <u-icon :name="node.icon" color="#fff" size="34" />
                                        </view>
                                        <text class="text-[20rpx] text-[#ffffff]/80 font-medium">{{ node.label }}</text>
                                    </view>
                                    <view
                                        v-if="idx < currentPath.length - 1"
                                        class="absolute right-[-60rpx] mb-[30rpx]">
                                        <u-icon name="arrow-rightward" color="#ffffff" size="24" />
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="formData.group_buy_type === TaskType.SEARCH"
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">搜索与定位设置</text>
                            </view>
                            <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[20rpx]">
                                <view>
                                    <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]"
                                        >输入团购类型</text
                                    >
                                    <view
                                        class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                                        <u-input
                                            v-model="formData.group_type"
                                            placeholder="如：双人套餐、火锅、美甲..."
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                            clearable />
                                    </view>
                                </view>
                                <!-- <view class="h-[1rpx] bg-[#F0F2F5]" /> -->
                                <view v-if="false">
                                    <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]"
                                        >团购距离范围</text
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
                                            class="text-[22rpx] text-red-500 mt-[8rpx] block">
                                            {{ customDistanceError }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">任务目标与评论筛选</text>
                                </view>
                                <view class="flex items-center gap-[8rpx]">
                                    <text class="text-xs text-[#9CA3AF]">执行</text>
                                    <view
                                        class="bg-[#EBF2FF] rounded-[12rpx] px-[12rpx] py-[6rpx] border border-solid border-[#BFDBFE] w-[100rpx]">
                                        <u-input
                                            v-model="formData.send_num"
                                            type="digit"
                                            height="40"
                                            :custom-style="{
                                                textAlign: 'center',
                                                fontWeight: 'bold',
                                                color: '#0065fb',
                                                fontSize: '30rpx',
                                            }"
                                            placeholder-style="color:#C0C4CC;" />
                                    </view>
                                    <text class="text-xs text-[#9CA3AF]">人</text>
                                </view>
                            </view>
                            <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[20rpx]">
                                <view>
                                    <view class="flex items-center justify-between mb-[14rpx]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF]">
                                            评论必须包含以下关键词
                                        </text>
                                        <view
                                            v-if="formData.filter.length > 0"
                                            class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#FEF2F2] border border-solid border-[#FECACA]"
                                            @click="handleClearAllIncludeFilter">
                                            <u-icon name="trash" size="22" color="#EF4444" />
                                            <text class="text-xs font-semibold text-[#EF4444]">一键删除</text>
                                        </view>
                                    </view>
                                    <view
                                        class="bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <view v-if="formData.filter.length > 0" class="mb-[12rpx]">
                                            <text class="text-xs text-[#9CA3AF]">
                                                共 {{ formData.filter.length }} 个关键词
                                            </text>
                                        </view>
                                        <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                                            <view
                                                v-for="(kw, idx) in visibleIncludeFilter"
                                                :key="idx"
                                                class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[20rpx] min-h-[56rpx] border border-solid border-[#BFDBFE]"
                                                @click="openKeywordsEdit(idx, KeyEditTarget.Keywords)">
                                                <text class="text-xs font-semibold text-primary">{{ kw }}</text>
                                                <view
                                                    class="shrink-0 w-[28rpx] h-[28rpx] rounded-full bg-[#0065fb]/10 flex items-center justify-center"
                                                    @click.stop="removeIncludeFilter(idx)">
                                                    <u-icon name="close" size="14" color="#0065fb" />
                                                </view>
                                            </view>
                                            <view
                                                v-if="formData.filter.length === 0"
                                                class="w-full flex justify-center py-[8rpx]">
                                                <text class="text-xs text-[#C0C4CC]">暂无关键词</text>
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
                                                        : `展开全部 ${formData.filter.length} 个`
                                                }}
                                            </text>
                                            <u-icon
                                                :name="includeFilterExpanded ? 'arrow-up' : 'arrow-down'"
                                                color="#0065fb"
                                                size="22" />
                                        </view>
                                        <view class="flex gap-[12rpx]">
                                            <view
                                                class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
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

                                <view class="flex gap-[16rpx]">
                                    <view
                                        v-if="false"
                                        class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                            >发布少于（天）</text
                                        >
                                        <u-input
                                            v-model="formData.content_publish_day"
                                            type="digit"
                                            height="40"
                                            :custom-style="{
                                                fontWeight: 'bold',
                                                fontSize: '36rpx',
                                                color: '#0D1117',
                                            }"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                                    </view>
                                    <view
                                        class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                            >从第几个评论开始</text
                                        >
                                        <u-input
                                            v-model="formData.comment_offset"
                                            type="digit"
                                            height="40"
                                            :custom-style="{
                                                fontWeight: 'bold',
                                                fontSize: '36rpx',
                                                color: '#0D1117',
                                            }"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">对评论用户执行动作</text>
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
                                    class="rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]"
                                    v-if="false">
                                    <text class="text-xs text-[#0D1117] font-semibold block mb-[14rpx]">点赞方式</text>
                                    <view
                                        class="flex bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#F0F2F5]">
                                        <view
                                            v-for="item in LIKE_TYPE_LIST"
                                            :key="item.value"
                                            class="flex-1 h-[68rpx] flex items-center justify-center rounded-[10rpx] transition-all duration-200"
                                            :class="formData.like_type == item.value ? 'bg-white shadow-sm' : ''"
                                            @click="formData.like_type = item.value">
                                            <text
                                                class="font-semibold"
                                                :class="
                                                    formData.like_type == LikeType.Avatar
                                                        ? 'text-[#0D1117]'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item.label }}
                                            </text>
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
                                        <u-input
                                            v-model="formData.watch_time"
                                            type="digit"
                                            height="40"
                                            :custom-style="{
                                                fontWeight: 'bold',
                                                fontSize: '36rpx',
                                                color: '#0D1117',
                                            }"
                                            placeholder-style="color:#C0C4CC;" />
                                    </view>
                                    <view
                                        class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                            >触达间隔（秒）</text
                                        >
                                        <u-input
                                            v-model="formData.interval_time"
                                            type="digit"
                                            height="40"
                                            :custom-style="{
                                                fontWeight: 'bold',
                                                fontSize: '36rpx',
                                                color: '#0D1117',
                                            }"
                                            placeholder-style="color:#C0C4CC;" />
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">目标画像与地域</text>
                            </view>
                            <view class="px-[28rpx] py-[24rpx] flex flex-col gap-[20rpx]">
                                <view>
                                    <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]"
                                        >性别要求</text
                                    >
                                    <view
                                        class="flex bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0]">
                                        <view
                                            v-for="item in GENDER_LIST"
                                            :key="item.value"
                                            class="flex-1 h-[68rpx] flex items-center justify-center rounded-[10rpx] transition-all duration-200"
                                            :class="
                                                formData.gender === item.value
                                                    ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                                    : ''
                                            "
                                            @click="formData.gender = item.value">
                                            <text
                                                class="font-semibold"
                                                :class="
                                                    formData.gender === item.value ? 'text-[#0D1117]' : 'text-[#9CA3AF]'
                                                ">
                                                {{ item.label }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                                <view class="h-[1rpx] bg-[#F0F2F5]" />
                                <view class="flex gap-[16rpx]">
                                    <view class="flex-1">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]"
                                            >筛选 IP</text
                                        >
                                        <view
                                            class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                                            <u-input
                                                v-model="formData.city"
                                                placeholder="如：浙江"
                                                placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                                        </view>
                                    </view>
                                    <view class="flex-1">
                                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]"
                                            >筛选地区</text
                                        >
                                        <view
                                            class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                                            <u-input
                                                v-model="formData.region"
                                                placeholder="如：杭州"
                                                placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                                        </view>
                                    </view>
                                </view>
                                <view class="h-[1rpx] bg-[#F0F2F5]" />

                                <!-- ② 对方昵称不包含（防误触） -->
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
                                            <text class="text-xs text-[#9CA3AF]">
                                                共 {{ formData.nickname_filter.length }} 个排除词
                                            </text>
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
                                                    @click.stop="removeNicknameFilter(idx)">
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
                                                    placeholder="如：同行、客服..."
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
    TaskType,
    LikeType,
    KeyEditTarget,
    STEPS,
    TASK_TYPE_LIST,
    DISTANCE_LIST,
    ACTION_LIST,
    LIKE_TYPE_LIST,
    FAVORITE_PATH,
    SEARCH_PATH,
    FREE_ACTION_LIST,
    MUTEX_ACTION_LIST,
    createDefaultFormData,
} from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useSourceStep } from "./hooks/useSourceStep";
import { useCreateTask } from "./hooks/useCreateTask";

const { on } = useEventBusManager();

// ─── 表单数据 ────────────────────────────────────────────────
const formData = reactive(createDefaultFormData());

// ─── 当前路径图（根据 sourceMode 切换）────────────────────────
const currentPath = computed(() => (formData.group_buy_type === TaskType.FAVORITE ? FAVORITE_PATH : SEARCH_PATH));

// ─── Step 导航 ───────────────────────────────────────────────
const { step, canNext, handleStep } = useStep(formData);

// ─── Step1 线索来源 ──────────────────────────────────────────
const {
    includeNameInput,
    visibleIncludeFilter,
    excludeNameInput,
    handleAddIncludeName,
    handleAddExcludeName,
    removeIncludeFilter,
    includeFilterExpanded,
    includeFilterOverflow,
    toggleIncludeFilter,
    handleClearAllIncludeFilter,
    nicknameFilterExpanded,
    visibleNicknameFilter,
    nicknameFilterOverflow,
    toggleNicknameFilter,
    handleClearAllNicknameFilter,
    removeNicknameFilter,
    keywordsEditShow,
    keywordsEditTitle,
    openKeywordsEdit,
    handleKeywordsConfirm,
    toggleFreeAction,
    toggleMutexAction,
    hasMutexSelected,
    choosePersonRef,
    showChoosePersonPopup,
    personValue,
    handleSelectPerson,
    handleChoosePersonConfirm,
    clearIpPerson,
    keywordsEditRef,
    isCustomDistance,
    customDistanceInput,
    customDistanceError,
    handleSelectDistance,
    handleSelectCustomDistance,
    handleCustomDistanceBlur,
} = useSourceStep(formData);

// ─── 创建任务 ────────────────────────────────────────────────
const {
    currentFrequency,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask(formData);

// ─── 事件总线 ────────────────────────────────────────────────
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
