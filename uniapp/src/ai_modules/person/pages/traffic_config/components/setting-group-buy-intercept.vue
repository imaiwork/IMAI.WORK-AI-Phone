<template>
    <view>
        <config-card
            title="团购截流配置"
            desc="搜索或指定团购，监控评论区寻找潜在客户"
            icon-name="shopping-cart"
            icon-color="#0065FB"
            icon-bg="#E6F0FF">
            <view class="flex bg-[#F0F2F5] p-[6rpx] rounded-[20rpx]" v-if="false">
                <view
                    v-for="tab in GROUPON_TAB_LIST"
                    :key="tab.value"
                    class="flex-1 h-[72rpx] flex items-center justify-center rounded-[16rpx] transition-all duration-200"
                    :class="configData.grouponTab == tab.value ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]' : ''"
                    @click="configData.grouponTab = tab.value">
                    <text
                        class="font-bold"
                        :class="configData.grouponTab === tab.value ? 'text-[#0D1117]' : 'text-[#9CA3AF]'">
                        {{ tab.label }}
                    </text>
                </view>
            </view>

            <view
                class="mt-5 rounded-[28rpx] shadow-[0_8rpx_24rpx_rgba(0,101,251,0.18)]"
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
                    <view class="flex items-center gap-[6rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] h-[48rpx]">
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
                        <view v-if="idx < GROUPON_ACTION_LIST.length - 1" class="absolute right-[-60rpx] mb-[30rpx]">
                            <u-icon name="arrow-rightward" color="#ffffff" size="24" />
                        </view>
                    </view>
                </view>
            </view>

            <view class="mt-5" v-if="configData.grouponTab == GrouponTab.Search">
                <view class="flex items-center gap-[10rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">搜索与定位设置</text>
                </view>
                <view class="pt-[24rpx] flex flex-col gap-[20rpx]">
                    <view>
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]">输入团购类型</text>
                        <view
                            class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                            <u-input
                                v-model="configData.grouponTypeKeyword"
                                placeholder="如：双人套餐、火锅、美甲..."
                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                clearable />
                        </view>
                    </view>
                    <!-- <view class="h-[1rpx] bg-[#F0F2F5]" /> -->
                    <view v-if="false">
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]">团购距离范围</text>
                        <view class="flex flex-wrap gap-[12rpx]">
                            <view
                                v-for="item in DISTANCE_LIST"
                                :key="item.value"
                                class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                :class="
                                    !isCustomDistance && configData.grouponDistance == item.value
                                        ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                        : 'bg-[#F0F2F5]'
                                "
                                @click="handleSelectDistance(item.value)">
                                <text
                                    class="font-bold"
                                    :class="
                                        !isCustomDistance && configData.grouponDistance == item.value
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
                                <text class="font-bold" :class="isCustomDistance ? 'text-primary' : 'text-[#9CA3AF]'">
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
                            <text v-if="customDistanceError" class="text-[22rpx] text-red-500 mt-[8rpx] block">
                                {{ customDistanceError }}
                            </text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="mt-5">
                <view
                    class="flex items-center justify-between h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="flex items-center gap-[10rpx]">
                        <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                        <text class="text-[28rpx] font-extrabold text-[#0D1117]">任务目标与评论筛选</text>
                    </view>
                    <view class="flex items-center gap-[8rpx]">
                        <text class="text-xs text-[#9CA3AF]">执行</text>
                        <view
                            class="bg-[#EBF2FF] rounded-[12rpx] px-[12rpx] py-[6rpx] border border-solid border-[#BFDBFE] w-[100rpx]">
                            <u-input
                                v-model="configData.commentNumber"
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
                <view class="pt-[24rpx] flex flex-col gap-[20rpx]">
                    <view>
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]"
                            >评论必须包含以下关键词</text
                        >
                        <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                            <view
                                v-for="(kw, idx) in configData.grouponCommentKeywords"
                                :key="idx"
                                class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[20rpx] min-h-[56rpx] border border-solid border-[#BFDBFE]"
                                @click="emit('edit', idx)">
                                <text class="text-xs font-semibold text-primary">{{ kw }}</text>
                                <view
                                    class="shrink-0 w-[28rpx] h-[28rpx] rounded-full bg-[#0065fb]/10 flex items-center justify-center"
                                    @click.stop="handleRemoveKeyword(idx)">
                                    <u-icon name="close" size="14" color="#0065fb" />
                                </view>
                            </view>
                        </view>
                        <view class="flex gap-[12rpx]">
                            <view
                                class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                                <u-input
                                    v-model="grouponCommentKeyword"
                                    placeholder="如：怎么买、划算吗..."
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                    @confirm="handleAddKeyword" />
                            </view>
                            <view
                                class="px-[28rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click="handleAddKeyword">
                                <text class="font-semibold text-white">添加</text>
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
                                v-model="configData.grouponPublishDay"
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
                                >从第几个评论开始</text
                            >
                            <u-input
                                v-model="configData.grouponCommentNum"
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

            <view class="mt-5">
                <view class="flex items-center gap-[10rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">对评论用户执行动作</text>
                </view>
                <view class="pt-[24rpx] flex flex-col gap-[20rpx]">
                    <view class="flex gap-[12rpx]">
                        <view
                            v-for="action in GROUPON_FREE_ACTION_LIST"
                            :key="action.value"
                            class="flex-1 relative flex flex-col items-center justify-center py-[24rpx] rounded-[20rpx] border-2 border-solid transition-all duration-200"
                            :class="
                                configData.grouponActions.includes(action.value)
                                    ? 'border-primary bg-[#EBF2FF]'
                                    : 'border-[#F0F2F5] bg-[#F7F9FC]'
                            "
                            @click="toggleFreeAction(action.value)">
                            <view
                                class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                                :class="
                                    configData.grouponActions.includes(action.value)
                                        ? 'bg-[#0065fb]/10'
                                        : 'bg-[#F0F2F5]'
                                ">
                                <u-icon
                                    :name="action.icon"
                                    :color="configData.grouponActions.includes(action.value) ? '#0065fb' : '#9CA3AF'"
                                    size="32" />
                            </view>
                            <text
                                class="text-[22rpx] font-semibold"
                                :class="
                                    configData.grouponActions.includes(action.value) ? 'text-primary' : 'text-[#9CA3AF]'
                                ">
                                {{ action.label }}
                            </text>
                            <view
                                v-if="configData.grouponActions.includes(action.value)"
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
                                    v-for="(action, idx) in GROUPON_MUTEX_ACTION_LIST"
                                    :key="action.value"
                                    class="flex-1 flex flex-col items-center justify-center py-[24rpx] transition-all duration-200 relative"
                                    :class="[
                                        configData.grouponActions.includes(action.value)
                                            ? 'bg-[#EBF2FF]'
                                            : 'bg-[#F7F9FC]',
                                        idx === 0 ? 'border-[0] border-r border-solid border-[#E5E9F0]' : '',
                                    ]"
                                    @click="toggleMutexAction(action.value)">
                                    <view
                                        class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                                        :class="
                                            configData.grouponActions.includes(action.value)
                                                ? 'bg-[#0065fb]/10'
                                                : 'bg-[#F0F2F5]'
                                        ">
                                        <u-icon
                                            :name="action.icon"
                                            :color="
                                                configData.grouponActions.includes(action.value) ? '#0065fb' : '#9CA3AF'
                                            "
                                            size="32" />
                                    </view>
                                    <text
                                        class="text-[22rpx] font-semibold"
                                        :class="
                                            configData.grouponActions.includes(action.value)
                                                ? 'text-primary'
                                                : 'text-[#9CA3AF]'
                                        ">
                                        {{ action.label }}
                                    </text>
                                    <view
                                        v-if="configData.grouponActions.includes(action.value)"
                                        class="absolute top-[8rpx] right-[8rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center">
                                        <u-icon name="checkmark" color="#fff" size="14" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view class="rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]" v-if="false">
                        <text class="text-xs text-[#0D1117] font-semibold block mb-[14rpx]">点赞方式</text>
                        <view class="flex bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#F0F2F5]">
                            <view
                                v-for="item in LIKE_TYPE_LIST"
                                :key="item.value"
                                class="flex-1 h-[68rpx] flex items-center justify-center rounded-[10rpx] transition-all duration-200"
                                :class="configData.grouponLikeType == item.value ? 'bg-white shadow-sm' : ''"
                                @click="configData.grouponLikeType = item.value">
                                <text
                                    class="font-semibold"
                                    :class="
                                        configData.grouponLikeType == item.value ? 'text-[#0D1117]' : 'text-[#9CA3AF]'
                                    ">
                                    {{ item.label }}
                                </text>
                            </view>
                        </view>
                    </view>
                    <view class="flex gap-[16rpx]">
                        <view
                            class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                            <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                                >观看视频（秒）</text
                            >
                            <u-input
                                v-model="configData.grouponWatchSeconds"
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
                                v-model="configData.grouponReachInterval"
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

            <view class="mt-5">
                <view class="flex items-center gap-[10rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">目标画像与地域</text>
                </view>
                <view class="pt-[24rpx] flex flex-col gap-[20rpx]">
                    <view>
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]">性别要求</text>
                        <view class="flex bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0]">
                            <view
                                v-for="item in GENDER_LIST"
                                :key="item.value"
                                class="flex-1 h-[68rpx] flex items-center justify-center rounded-[10rpx] transition-all duration-200"
                                :class="
                                    configData.grouponGenderFilter == item.value
                                        ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                        : ''
                                "
                                @click="configData.grouponGenderFilter = item.value">
                                <text
                                    class="font-semibold"
                                    :class="
                                        configData.grouponGenderFilter == item.value
                                            ? 'text-[#0D1117]'
                                            : 'text-[#9CA3AF]'
                                    ">
                                    {{ item.label }}
                                </text>
                            </view>
                        </view>
                    </view>
                    <view class="h-[1rpx] bg-[#F0F2F5]" />
                    <view class="flex gap-[16rpx]">
                        <view class="flex-1">
                            <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]">筛选 IP</text>
                            <view
                                class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                                <u-input
                                    v-model="configData.grouponFilterIp"
                                    placeholder="如：浙江"
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                            </view>
                        </view>
                        <view class="flex-1">
                            <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]">筛选地区</text>
                            <view
                                class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                                <u-input
                                    v-model="configData.grouponFilterRegion"
                                    placeholder="如：杭州"
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                            </view>
                        </view>
                    </view>
                    <view class="h-[1rpx] bg-[#F0F2F5]" />
                    <view>
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]"
                            >对方昵称不包含（防误触）</text
                        >
                        <view class="bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                            <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                                <view
                                    v-for="(name, idx) in configData.grouponNicknameFilters"
                                    :key="idx"
                                    class="flex items-center gap-[8rpx] bg-white rounded-full px-[16rpx] h-[52rpx] border border-solid border-[#E5E9F0]"
                                    @click="emit('edit-nickname-filter', idx)">
                                    <text class="text-xs text-[#4B5563]">{{ name }}</text>
                                    <view
                                        class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                        @click.stop="handleRemoveNicknameFilter(idx)">
                                        <u-icon name="close" size="14" color="#9CA3AF" />
                                    </view>
                                </view>
                                <view
                                    v-if="configData.grouponNicknameFilters.length == 0"
                                    class="w-full flex justify-center py-[8rpx]">
                                    <text class="text-xs text-[#C0C4CC]">暂无排除词</text>
                                </view>
                            </view>
                            <view class="flex gap-[12rpx]">
                                <view
                                    class="flex-1 bg-white rounded-[14rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                                    <u-input
                                        v-model="grouponNicknameFilter"
                                        placeholder="如：同行、客服..."
                                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                        @confirm="handleAddNicknameFilter" />
                                </view>
                                <view
                                    class="px-[28rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                    @click="handleAddNicknameFilter">
                                    <text class="font-semibold text-white">添加</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </config-card>
    </view>
</template>

<script setup lang="ts">
import {
    ConfigData,
    GrouponTab,
    GrouponAction,
    GROUPON_TAB_LIST,
    GROUPON_ACTION_LIST,
    GENDER_LIST,
    LIKE_TYPE_LIST,
    GROUPON_FAVORITE_LIST,
    GROUPON_SEARCH_LIST,
    DISTANCE_LIST,
} from "./type";
import ConfigCard from "./config-card.vue";

const props = defineProps<{
    configData: ConfigData;
}>();

const emit = defineEmits<{
    (e: "add"): void;
    (e: "edit", value: any): void;
    (e: "remove", value: any): void;
    (e: "edit-nickname-filter", value: any): void;
}>();

const currentPath = computed(() =>
    props.configData.grouponTab == GrouponTab.Favorite ? GROUPON_FAVORITE_LIST : GROUPON_SEARCH_LIST,
);

const GROUPON_FREE_ACTION_LIST = GROUPON_ACTION_LIST.filter(
    (a) => ![GrouponAction.Comment, GrouponAction.Dm].includes(a.value),
);
const GROUPON_MUTEX_ACTION_LIST = GROUPON_ACTION_LIST.filter((a) =>
    [GrouponAction.Comment, GrouponAction.Dm].includes(a.value),
);

// 互斥组中是否有选中项，用于控制外边框颜色
const hasMutexSelected = computed(() =>
    GROUPON_MUTEX_ACTION_LIST.some((a) => props.configData.grouponActions.includes(a.value)),
);

const grouponCommentKeyword = ref<string>("");
const grouponNicknameFilter = ref<string>("");

const customDistanceInput = ref<number | null>();
const customDistanceError = ref<string>("");
const isCustomDistance = ref(false);

const handleRemoveKeyword = (idx: number) => {
    props.configData.grouponCommentKeywords.splice(idx, 1);
};

const handleSelectDistance = (value: number): void => {
    props.configData.grouponDistance = value;
    isCustomDistance.value = false;
    customDistanceError.value = "";
};

const handleSelectCustomDistance = (): void => {
    customDistanceError.value = "";
    isCustomDistance.value = true;
};

const handleCustomDistanceBlur = (): void => {
    const raw = customDistanceInput.value?.toString().trim();
    if (raw === "") {
        customDistanceError.value = "";
        props.configData.grouponDistance = 0;
        return;
    }
    const num = Number(raw);
    if (isNaN(num) || !Number.isInteger(num) || num <= 0) {
        customDistanceError.value = "请输入大于 0 的整数（单位：公里）";
        return;
    }
    customDistanceError.value = "";
    props.configData.grouponDistance = num;
};

const handleAddKeyword = () => {
    const kw = grouponCommentKeyword.value.trim();
    if (!kw) return;
    if (!props.configData.grouponCommentKeywords.includes(kw)) {
        props.configData.grouponCommentKeywords.push(kw);
    } else {
        uni.$u.toast("该关键词已存在");
    }
    grouponCommentKeyword.value = "";
};

const handleAddNicknameFilter = () => {
    const name = grouponNicknameFilter.value.trim();
    if (!name) return;
    if (!props.configData.grouponNicknameFilters.includes(name)) {
        props.configData.grouponNicknameFilters.push(name);
    }
    grouponNicknameFilter.value = "";
};

const handleRemoveNicknameFilter = (idx: number) => {
    props.configData.grouponNicknameFilters.splice(idx, 1);
};

const toggleFreeAction = (key: number) => {
    const idx = props.configData.grouponActions.indexOf(key);
    if (idx === -1) props.configData.grouponActions.push(key);
    else props.configData.grouponActions.splice(idx, 1);
};

const toggleMutexAction = (key: number) => {
    const idx = props.configData.grouponActions.indexOf(key);
    if (idx !== -1) {
        props.configData.grouponActions.splice(idx, 1);
    } else {
        GROUPON_MUTEX_ACTION_LIST.forEach(({ value }) => {
            const i = props.configData.grouponActions.indexOf(value);
            if (i !== -1) props.configData.grouponActions.splice(i, 1);
        });
        props.configData.grouponActions.push(key);
    }
};
</script>

<style scoped></style>
