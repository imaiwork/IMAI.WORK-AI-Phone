<template>
    <popup-bottom v-model="show" title="素材选择" :is-disabled-touch="true" height="80%" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="flex items-center justify-between px-4 pt-2 pb-1">
                    <view v-if="props.mode === 'all'" class="flex bg-[#F1F5F9] rounded-[12rpx] p-[4rpx]">
                        <view
                            v-for="(tab, index) in ['全部', '分组']"
                            :key="index"
                            class="px-[24rpx] py-[8rpx] rounded-[8rpx] text-xs font-medium transition-all"
                            :class="currShowType === index ? 'bg-white text-[#374151] shadow-sm' : 'text-[#6B7280]'"
                            @click="handleShowType(index)">
                            {{ tab }}
                        </view>
                    </view>
                    <view v-else class="text-[28rpx] font-medium text-[#374151]">
                        {{ props.mode === "group" ? "选择分组" : "选择素材" }}
                    </view>

                    <view class="flex items-center gap-[10rpx]" @click="toggleChoosePanel">
                        <view class="flex items-center gap-1 px-3 py-1 rounded-full bg-[#F1F5F9] active:bg-[#E5E7EB]">
                            <text class="text-xs">
                                已选：<text
                                    class="font-semibold"
                                    :class="
                                        props.limit && totalChooseCount >= props.limit
                                            ? 'text-[#EF4444]'
                                            : 'text-primary'
                                    "
                                    >{{ totalChooseCount }}</text
                                >
                            </text>
                            <u-icon
                                v-if="totalChooseCount > 0"
                                :name="showChoosePanel ? 'arrow-up' : 'arrow-down'"
                                size="12"
                                color="#6B7280" />
                        </view>
                        <view
                            v-if="props.multiple && props.limit && props.limit > 1"
                            class="flex items-center gap-[4rpx] px-[12rpx] h-[36rpx] rounded-full"
                            :class="props.limit && totalChooseCount >= props.limit ? 'bg-[#FEF2F2]' : 'bg-[#F0F2F5]'">
                            <u-icon
                                :name="
                                    props.limit && totalChooseCount >= props.limit ? 'info-circle-fill' : 'info-circle'
                                "
                                :color="props.limit && totalChooseCount >= props.limit ? '#EF4444' : '#9CA3AF'"
                                size="18" />
                            <text
                                class="text-[20rpx] font-medium"
                                :class="
                                    props.limit && totalChooseCount >= props.limit ? 'text-[#EF4444]' : 'text-[#9CA3AF]'
                                ">
                                最多 {{ props.limit }} 个
                            </text>
                        </view>
                    </view>
                </view>

                <view
                    v-if="showChoosePanel && totalChooseCount > 0"
                    class="mx-4 mb-2 bg-white rounded-[16rpx] border border-[#F1F5F9] shadow-sm overflow-hidden">
                    <view class="flex items-center justify-between px-3 py-2 border-b border-[#F1F5F9]">
                        <text class="text-xs font-medium text-[#374151]">已选（{{ totalChooseCount }}）</text>
                        <text class="text-[22rpx] text-[#EF4444]" @click="clearAll">清空</text>
                    </view>
                    <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                        <view class="flex gap-2 px-3 py-2" style="width: max-content">
                            <template v-if="!props.disableGroupSelect">
                                <view
                                    v-for="group in chooseGroups"
                                    :key="'g_' + group.id"
                                    class="relative flex-shrink-0 w-[120rpx] h-[160rpx] rounded-[12rpx] overflow-hidden bg-[#F1F5F9] flex flex-col items-center justify-center gap-1">
                                    <text class="text-[40rpx]">📁</text>
                                    <text class="text-[20rpx] text-[#374151] px-1 text-center line-clamp-2">{{
                                        group.name
                                    }}</text>
                                    <view
                                        class="absolute bottom-1 left-1 bg-[#6366F1] rounded-full px-[8rpx] py-[2rpx]">
                                        <text class="text-[18rpx] text-white">分组</text>
                                    </view>
                                    <view
                                        class="absolute top-1 right-1 w-[36rpx] h-[36rpx] rounded-full bg-[#00000080] flex items-center justify-center"
                                        @click.stop="removeChooseGroup(group)">
                                        <u-icon name="close" size="12" color="#fff" />
                                    </view>
                                </view>
                            </template>
                            <view
                                v-for="(item, index) in chooseLists"
                                :key="'m_' + item.id"
                                class="relative flex-shrink-0 w-[120rpx] h-[160rpx] rounded-[12rpx] overflow-hidden bg-[#EBF2FF]">
                                <image
                                    v-if="!isAudio(item) && item.pic"
                                    :src="item.pic"
                                    class="w-full h-full"
                                    mode="aspectFill" />
                                <view v-else class="w-full h-full flex items-center justify-center">
                                    <u-icon name="volume-up" size="36" color="#2F73F6" />
                                </view>
                                <view
                                    class="absolute bottom-1 left-1 flex items-center gap-[4rpx] bg-[#00000066] rounded-full px-[8rpx] py-[2rpx]">
                                    <u-icon
                                        :name="isAudio(item) ? 'volume-up' : isImage(item) ? 'photo' : 'play-right-fill'"
                                        size="16"
                                        color="#fff" />
                                </view>
                                <view
                                    class="absolute top-1 right-1 w-[36rpx] h-[36rpx] rounded-full bg-[#00000080] flex items-center justify-center"
                                    @click.stop="handleSelect(item)">
                                    <u-icon name="close" size="12" color="#fff" />
                                </view>
                                <view
                                    class="absolute bottom-1 right-1 w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center">
                                    <text class="text-[18rpx] text-white font-medium">{{ index + 1 }}</text>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view
                    v-if="isGroupInnerMode"
                    class="flex items-center gap-2 px-4 py-2 border-b border-[#F1F5F9]"
                    @click="backToGroupList">
                    <u-icon name="arrow-left" size="16" color="#6B7280"></u-icon>
                    <text class="text-xs text-[#6B7280]">返回分组</text>
                    <text class="text-xs text-[#374151] font-medium">/ {{ currentGroupItem.name }}</text>
                </view>

                <view class="grow min-h-0 mt-[10rpx] relative">
                    <view v-if="isLoading" class="absolute inset-0 z-10 bg-[#F9FAFB] px-4 pt-1">
                        <view v-if="isGroupListMode" class="flex flex-col gap-y-3">
                            <view
                                v-for="i in 6"
                                :key="i"
                                class="bg-white rounded-[16rpx] p-3 flex items-center gap-3 border border-[#F1F5F9]">
                                <view class="w-[100rpx] h-[100rpx] rounded-[12rpx] skeleton" />
                                <view class="flex-1 flex flex-col gap-2">
                                    <view class="h-[28rpx] w-3/4 rounded-full skeleton" />
                                    <view class="h-[22rpx] w-1/2 rounded-full skeleton" />
                                </view>
                            </view>
                        </view>
                        <view v-else-if="isAudioOnly" class="flex flex-col gap-3">
                            <view v-for="i in 6" :key="i" class="bg-white rounded-[16rpx] p-3 flex items-center gap-3">
                                <view class="w-[80rpx] h-[80rpx] rounded-full skeleton" />
                                <view class="flex-1 flex flex-col gap-2">
                                    <view class="h-[28rpx] w-3/4 rounded-full skeleton" />
                                    <view class="h-[22rpx] w-1/3 rounded-full skeleton" />
                                </view>
                            </view>
                        </view>
                        <view v-else class="grid gap-2" :class="props.type === 'video' ? 'grid-cols-2' : 'grid-cols-3'">
                            <view v-for="i in 9" :key="i" class="rounded-xl aspect-[3/4] skeleton" />
                        </view>
                    </view>

                    <z-paging
                        class="h-full"
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :auto="false"
                        :default-page-size="20"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view v-if="isGroupListMode" class="px-4 flex flex-col gap-y-3">
                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="bg-white rounded-[16rpx] p-3 flex items-center shadow-sm border transition-all"
                                :class="[
                                    !props.disableGroupSelect && isChooseGroup(item)
                                        ? 'border-primary'
                                        : 'border-[#F1F5F9]',
                                    item.material_count === 0 ? 'opacity-50' : '',
                                ]">
                                <view
                                    class="flex-1 flex items-center gap-3"
                                    @click="item.material_count > 0 && handleGroupItemClick(item)">
                                    <view
                                        class="flex-shrink-0 relative flex items-center justify-center w-[100rpx] h-[100rpx] rounded-[12rpx]"
                                        style="background-image: linear-gradient(to bottom right, #f1f5f9, #e5e7eb)">
                                        <view class="text-[32rpx] opacity-60">📁</view>
                                        <view
                                            class="absolute -bottom-[8rpx] -right-[8rpx] min-w-[36rpx] h-[36rpx] px-[8rpx] rounded-full flex items-center justify-center"
                                            :class="item.material_count > 0 ? 'bg-primary' : 'bg-[#D1D5DB]'">
                                            <text class="text-[18rpx] text-white font-medium">{{
                                                item.material_count ?? 0
                                            }}</text>
                                        </view>
                                    </view>
                                    <view class="flex-1">
                                        <text class="text-[28rpx] font-medium text-[#1F2937] line-clamp-1">{{
                                            item.name
                                        }}</text>
                                        <view
                                            v-if="!props.disableGroupSelect && isChooseGroup(item)"
                                            class="mt-1 flex items-center gap-1">
                                            <view class="w-[12rpx] h-[12rpx] rounded-full bg-primary" />
                                            <text class="text-[20rpx] text-primary">已选整个分组</text>
                                        </view>
                                        <view
                                            v-else-if="item.material_count === 0"
                                            class="mt-1 flex items-center gap-1">
                                            <text class="text-[20rpx] text-[#9CA3AF]">暂无素材</text>
                                        </view>
                                        <view v-else class="mt-1 flex items-center gap-1">
                                            <text class="text-[20rpx] text-[#9CA3AF]"
                                                >共 {{ item.material_count }} 个素材</text
                                            >
                                        </view>
                                    </view>
                                </view>

                                <view class="flex items-center gap-2 ml-2">
                                    <view
                                        v-if="!props.disableGroupSelect"
                                        class="px-[16rpx] py-[8rpx] rounded-full text-[22rpx] border transition-all"
                                        :class="
                                            item.material_count === 0
                                                ? 'bg-[#F3F4F6] border-[#E5E7EB] text-[#D1D5DB]'
                                                : isChooseGroup(item)
                                                ? 'bg-primary border-primary text-white'
                                                : 'bg-white border-[#E5E7EB] text-[#6B7280]'
                                        "
                                        @click.stop="item.material_count > 0 && toggleChooseGroup(item)">
                                        {{ isChooseGroup(item) ? "已选分组" : "选分组" }}
                                    </view>

                                    <u-icon
                                        name="arrow-right"
                                        size="16"
                                        :color="item.material_count === 0 ? '#D1D5DB' : '#9CA3AF'"
                                        @click.stop="item.material_count > 0 && enterGroup(item)" />
                                </view>
                            </view>
                        </view>

                        <view v-else-if="isAudioOnly" class="flex flex-col gap-[16rpx] px-4">
                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="bg-white rounded-[16rpx] px-[24rpx] py-[20rpx] flex items-center gap-[20rpx] border border-solid transition-all"
                                :class="isChoose(item) ? 'border-primary' : 'border-[#F1F5F9]'"
                                @click="handleSelect(item)">
                                <view class="flex-1 min-w-0">
                                    <text class="text-[28rpx] font-medium text-[#1F2937] line-clamp-1">
                                        {{ item.name || "未命名音频" }}
                                    </text>
                                    <text class="text-[22rpx] text-[#9CA3AF] mt-[6rpx] block">
                                        {{ formatDuration(item.duration) || "音频" }}
                                    </text>
                                </view>
                                <view
                                    class="px-[20rpx] py-[12rpx] rounded-full flex items-center gap-[6rpx] shrink-0"
                                    :style="
                                        isAudioPlaying(item)
                                            ? 'background:#FFF0F0;border:2rpx solid #FFD6D6'
                                            : 'background:#EBF3FF;border:2rpx solid #BAD4FF'
                                    "
                                    @click.stop="handleToggleAudioPlay(item)">
                                    <u-icon
                                        :name="isAudioPlaying(item) ? 'pause-circle' : 'play-circle'"
                                        :color="isAudioPlaying(item) ? '#FF4D4F' : '#0065FB'"
                                        size="26"></u-icon>
                                    <text
                                        class="text-xs font-semibold"
                                        :class="isAudioPlaying(item) ? 'text-[#FF4D4F]' : 'text-primary'">
                                        {{ isAudioPlaying(item) ? "暂停" : "试听" }}
                                    </text>
                                </view>
                                <view
                                    class="w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center shrink-0"
                                    :class="isChoose(item) ? 'bg-primary' : 'bg-[#E5E7EB]'">
                                    <text v-if="isChoose(item)" class="text-[20rpx] text-white font-medium">
                                        {{ getChooseIndex(item) }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view
                            v-else
                            class="grid gap-2 px-4"
                            :class="props.type === 'video' ? 'grid-cols-2' : 'grid-cols-3'">
                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="rounded-xl relative overflow-hidden aspect-[3/4]"
                                @click="handleSelect(item)">
                                <image
                                    v-if="item.pic"
                                    :src="item.pic"
                                    lazy-load
                                    class="absolute inset-0 w-full h-full rounded-xl"
                                    mode="aspectFill" />
                                <view
                                    class="absolute bottom-2 left-2 flex items-center gap-[6rpx] bg-[#00000066] rounded-full px-[10rpx] py-[4rpx]">
                                    <u-icon
                                        :name="isImage(item) ? 'photo' : 'play-right-fill'"
                                        size="16"
                                        color="#fff" />
                                    <text class="text-[20rpx] text-white font-mono">
                                        {{ isImage(item) ? "图片" : formatDuration(item.duration) }}
                                    </text>
                                </view>
                                <view class="absolute top-0 left-0 w-full h-full bg-[#00000066]" v-if="isChoose(item)">
                                    <view class="absolute top-2 right-2">
                                        <image src="/static/images/icons/success.svg" class="w-[28rpx] h-[28rpx]" />
                                    </view>
                                    <view
                                        class="absolute bottom-2 right-2 w-[36rpx] h-[36rpx] rounded-full bg-primary flex items-center justify-center">
                                        <text class="text-[20rpx] text-white font-medium">{{
                                            getChooseIndex(item)
                                        }}</text>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white opacity-80"
                                    v-else />
                            </view>
                        </view>

                        <template #empty>
                            <view class="flex flex-col items-center justify-center py-[80rpx]">
                                <text class="text-[60rpx] mb-3">📂</text>
                                <text class="text-[#6B7280]">{{ isGroupListMode ? "暂无分组" : "暂无素材" }}</text>
                            </view>
                        </template>
                    </z-paging>
                </view>

                <view class="flex items-center justify-between gap-2 mt-[20rpx] mb-4 px-4">
                    <view
                        v-if="multiple && props.limit && props.limit > 1 && !isGroupListMode"
                        class="flex flex-col gap-[6rpx] flex-shrink-0">
                        <view class="flex items-center gap-x-2" @click="toggleSelect">
                            <view class="w-[32rpx] h-[32rpx]">
                                <image
                                    v-if="isCurrentPageAllSelected"
                                    src="/static/images/icons/success.svg"
                                    class="w-full h-full" />
                                <view class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]" v-else />
                            </view>
                            <view>全选</view>
                        </view>
                    </view>
                    <view
                        class="flex-1 text-white font-medium text-[30rpx] rounded-[20rpx] h-[90rpx] flex items-center justify-center transition-all"
                        :class="[
                            !props.multiple ? 'w-full' : '',
                            isConfirming ? 'bg-primary opacity-70' : 'bg-primary',
                        ]"
                        @click="confirm">
                        <view v-if="isConfirming" class="flex items-center gap-2">
                            <text>处理中...</text>
                        </view>
                        <view v-else class="flex items-center gap-1">
                            <text>确定选择</text>
                            <text v-if="totalChooseCount > 0" class="text-xs opacity-80">({{ totalChooseCount }})</text>
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <view v-if="showProgressPop" class="fixed inset-0 z-[9999] flex flex-col justify-end">
        <view
            class="absolute inset-0 bg-[#000000]/50 transition-opacity duration-300"
            :class="isProgressVisible ? 'opacity-100' : 'opacity-0'" />
        <view
            class="relative bg-white rounded-t-[32rpx] px-6 pt-6 pb-10 transition-transform duration-300"
            :class="isProgressVisible ? 'translate-y-0' : 'translate-y-full'">
            <view class="text-[30rpx] font-bold text-[#1F2937] mb-1">正在处理素材</view>
            <view class="text-xs text-[#6B7280] mb-6">{{ progressDesc }}</view>
            <view class="w-full h-[12rpx] bg-[#F1F5F9] rounded-full overflow-hidden mb-3">
                <view
                    class="h-full bg-primary rounded-full transition-all duration-500"
                    :style="{ width: progressPercent + '%' }" />
            </view>
            <view class="flex items-center justify-between text-[22rpx] text-[#9CA3AF] mb-6">
                <text>{{ progressStep }}</text>
                <text>{{ progressPercent }}%</text>
            </view>
            <view
                v-if="hasLimit && previewDuration > 0"
                class="flex items-center gap-2 px-3 py-2 rounded-[16rpx] bg-[#F0F5FF] mb-4">
                <u-icon name="clock" color="#0065fb" size="22" />
                <text class="text-[22rpx] text-primary">
                    已处理时长：
                    <text class="font-bold">{{ formatDuration(previewDuration) }}</text>
                    / 限制 {{ formatDuration((props.durationLimit ?? 0) * 60) }}
                </text>
            </view>
            <view class="h-[20rpx]" />
        </view>
    </view>

    <view v-if="showOverLimitPop" class="fixed inset-0 z-[9999] flex items-center justify-center">
        <view
            class="absolute inset-0 bg-[#000000]/50 transition-opacity duration-300"
            :class="isOverLimitVisible ? 'opacity-100' : 'opacity-0'" />
        <view
            class="relative bg-white rounded-[32rpx] mx-8 px-6 py-6 w-[560rpx] transition-all duration-300"
            :class="isOverLimitVisible ? 'opacity-100 scale-100' : 'opacity-0 scale-90'">
            <view class="flex items-center gap-2 mb-3">
                <u-icon name="info-circle-fill" color="#F59E0B" size="36" />
                <text class="text-[30rpx] font-bold text-[#1F2937]">素材超出时长限制</text>
            </view>
            <view class="text-xs text-[#6B7280] leading-relaxed mb-2">
                所选素材总时长超过限制（{{ formatDuration((props.durationLimit ?? 0) * 60) }}）， 已自动为你保留前
                <text class="text-primary font-bold">{{ overLimitResult.kept }}</text> 个素材 （共
                <text class="text-primary font-bold">{{ formatDuration(overLimitResult.keptDuration) }}</text
                >）， 丢弃了 <text class="text-[#EF4444] font-bold">{{ overLimitResult.dropped }}</text> 个。
            </view>
            <view class="text-[22rpx] text-[#9CA3AF] mb-6">
                图片按 {{ props.imageDuration ?? 2 }}s/张，视频按实际时长计算
            </view>
            <view class="flex gap-3">
                <view
                    class="flex-1 h-[80rpx] rounded-[16rpx] border border-[#E5E7EB] flex items-center justify-center text-[#6B7280] active:bg-[#F9FAFB]"
                    @click="handleOverLimitReselect">
                    重新选择
                </view>
                <view
                    class="flex-1 h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center text-white font-medium active:opacity-80"
                    @click="handleOverLimitConfirm">
                    确认使用
                </view>
            </view>
        </view>
    </view>

    <!-- ── 数量超限弹窗 ── -->
    <view v-if="showCountOverLimitPop" class="fixed inset-0 z-[9999] flex items-center justify-center">
        <view
            class="absolute inset-0 bg-[#000000]/50 transition-opacity duration-300"
            :class="isCountOverLimitVisible ? 'opacity-100' : 'opacity-0'" />
        <view
            class="relative bg-white rounded-[32rpx] mx-8 px-6 py-6 w-[80%] transition-all duration-300"
            :class="isCountOverLimitVisible ? 'opacity-100 scale-100' : 'opacity-0 scale-90'">
            <view class="flex items-center gap-2 mb-3">
                <u-icon name="info-circle-fill" color="#F59E0B" size="36" />
                <text class="text-[30rpx] font-bold text-[#1F2937]">素材数量超出限制</text>
            </view>
            <view class="text-xs text-[#6B7280] leading-relaxed mb-2">
                所选素材共
                <text class="text-[#1F2937] font-bold">{{ countOverLimitResult.total }}</text>
                个，超出当前限制（
                <text class="text-primary font-bold">{{ props.limit }}</text>
                条），系统将自动为你保留前
                <text class="text-primary font-bold">{{ props.limit }}</text>
                个素材，丢弃
                <text class="text-[#EF4444] font-bold">{{ countOverLimitResult.dropped }}</text>
                个。
            </view>
            <view class="text-[22rpx] text-[#9CA3AF] mb-5">素材将按照分组内的排列顺序依次保留</view>
            <view class="flex items-center gap-3 px-3 py-[14rpx] rounded-[16rpx] bg-[#FFFBEB] mb-6">
                <u-icon name="list" color="#F59E0B" size="22" />
                <view class="flex items-center gap-1 text-[22rpx]">
                    <text class="text-[#6B7280]">合并后素材：</text>
                    <text class="text-[#1F2937] font-bold">{{ countOverLimitResult.total }} 个</text>
                    <text class="text-[#D1D5DB] mx-1">→</text>
                    <text class="text-[#6B7280]">实际使用：</text>
                    <text class="text-primary font-bold">{{ props.limit }} 个</text>
                </view>
            </view>
            <view class="flex gap-3">
                <view
                    class="flex-1 h-[80rpx] rounded-[16rpx] border border-[#E5E7EB] flex items-center justify-center text-[#6B7280] active:bg-[#F9FAFB]"
                    @click="handleCountOverLimitReselect">
                    重新选择
                </view>
                <view
                    class="flex-1 h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center text-white font-medium active:opacity-80"
                    @click="handleCountOverLimitConfirm">
                    确认使用
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { batchGetVideoInfoByUrl } from "@/api/app";
import { getMaterialLibraryList, getMaterialLibraryGroupList, batchUpdateMaterialDate } from "@/api/material";
import { useAudio } from "@/hooks/useAudio";

enum ShowType {
    ALL = 0,
    GROUP = 1,
}

/** 全局素材库 m_type：1 图片 / 2 视频 / 6 音频 */
enum MaterialLibTypeEnum {
    IMAGE = 1,
    VIDEO = 2,
    AUDIO = 6,
}

type MaterialContentType = "video" | "image" | "audio" | "all";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        limit?: number;
        type: MaterialContentType;
        multiple?: boolean;
        mode?: "all" | "list" | "group";
        durationLimit?: number;
        imageDuration?: number;
        disableGroupSelect?: boolean;
    }>(),
    {
        multiple: true,
        type: "all",
        mode: "all",
        imageDuration: 2,
        durationLimit: 0,
        disableGroupSelect: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "select", value: any[]): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const hasLimit = computed(() => !!props.durationLimit && props.durationLimit > 0);
const currShowType = ref<ShowType>(ShowType.ALL);

// ── 视图模式计算 ─────────────────────────────────────────────
/**
 * isGroupListMode：当前是否展示「分组列表」
 *   - mode=list  → 永远不是分组列表，直接展示素材
 *   - mode=group → 没有进入某个分组时展示分组列表
 *   - mode=all   → 切到「分组」tab 且没有进入某个分组时展示分组列表
 */
const isGroupListMode = computed(() => {
    if (props.mode === "list") return false;
    if (props.mode === "group") return !currentGroupItem.id;
    return currShowType.value === ShowType.GROUP && !currentGroupItem.id;
});

/**
 * isGroupInnerMode：当前是否处于「分组内部」（面包屑导航可见）
 *   - mode=group → 进入了某个分组（currentGroupItem.id 有值）
 *   - mode=all   → 切到「分组」tab 且进入了某个分组
 *   - mode=list  → 不存在分组内部概念，永远 false
 */
const isGroupInnerMode = computed(() => {
    if (props.mode === "list") return false;
    if (props.mode === "group") return !!currentGroupItem.id;
    return currShowType.value === ShowType.GROUP && !!currentGroupItem.id;
});

// ── 状态 ─────────────────────────────────────────────────────
const dataLists = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseLists = ref<any[]>([]);
const chooseGroups = ref<any[]>([]);
const showChoosePanel = ref(false);
const isLoading = ref(false);
const isConfirming = ref(false);
const currentGroupItem = reactive<any>({ id: "", name: "" });

const totalChooseCount = computed(() => chooseGroups.value.length + chooseLists.value.length);
const isImage = (item: any) => item.m_type === MaterialLibTypeEnum.IMAGE;
const isAudio = (item: any) => item.m_type === MaterialLibTypeEnum.AUDIO;
const isAudioOnly = computed(() => props.type === "audio");

const resolveContentType = (item: any): "image" | "video" | "audio" => {
    if (item?.m_type === MaterialLibTypeEnum.AUDIO) return "audio";
    if (item?.m_type === MaterialLibTypeEnum.IMAGE) return "image";
    return "video";
};

const queryMType = computed(() => {
    if (props.type === "all") return "";
    if (props.type === "video") return MaterialLibTypeEnum.VIDEO;
    if (props.type === "audio") return MaterialLibTypeEnum.AUDIO;
    return MaterialLibTypeEnum.IMAGE;
});

const emptySelectTip = computed(() => {
    if (props.type === "all") return "视频或图片";
    if (props.type === "video") return "视频";
    if (props.type === "audio") return "音频";
    return "图片";
});

const { isPlaying, play, pause, destroy } = useAudio();
const currAudioId = ref<string | number | null>(null);

const getAudioUrl = (item: any): string => item?.content || item?.url || "";

const isAudioPlaying = (item: any): boolean =>
    !!isPlaying.value && currAudioId.value !== null && String(currAudioId.value) === String(item?.id);

const handleToggleAudioPlay = (item: any): void => {
    const url = getAudioUrl(item);
    if (!url) {
        uni.$u.toast("音频地址无效");
        return;
    }
    if (isAudioPlaying(item)) {
        pause();
        return;
    }
    pause();
    currAudioId.value = item.id;
    play(url);
};

const stopAudioPreview = (): void => {
    pause();
    currAudioId.value = null;
};

const isCurrentPageAllSelected = computed(() => {
    if (dataLists.value.length === 0) return false;
    const unselected = dataLists.value.filter((item) => !isChoose(item));
    if (unselected.length === 0) return true;
    const remaining = (props.limit ?? Infinity) - chooseLists.value.length;
    if (remaining <= 0) return true;
    return false;
});

const getChooseIndex = (item: any) => chooseLists.value.findIndex((i) => i.id === item.id) + 1;

const formatDuration = (seconds: number): string => {
    if (!seconds || seconds <= 0) return "";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    if (m === 0) return `${s}s`;
    return `${m}分${s > 0 ? s + "秒" : ""}`;
};

// ── 进度弹窗 ─────────────────────────────────────────────────
const showProgressPop = ref(false);
const isProgressVisible = ref(false);
const progressDesc = ref("");
const progressStep = ref("");
const progressPercent = ref(0);
const previewDuration = ref(0);
const ANIM_DURATION = 300;

const setProgress = (desc: string, step: string, percent: number) => {
    progressDesc.value = desc;
    progressStep.value = step;
    progressPercent.value = percent;
};

const openProgressPop = async () => {
    showProgressPop.value = true;
    await nextTick();
    isProgressVisible.value = true;
};

const closeProgressPop = () => {
    isProgressVisible.value = false;
    setTimeout(() => (showProgressPop.value = false), ANIM_DURATION);
};

// ── 时长超限弹窗 ─────────────────────────────────────────────
const showOverLimitPop = ref(false);
const isOverLimitVisible = ref(false);
const overLimitResult = reactive({ kept: 0, keptDuration: 0, dropped: 0 });
let pendingResult: any[] = [];

const openOverLimitPop = async () => {
    showOverLimitPop.value = true;
    await nextTick();
    isOverLimitVisible.value = true;
};

const closeOverLimitPop = () => {
    isOverLimitVisible.value = false;
    setTimeout(() => (showOverLimitPop.value = false), ANIM_DURATION);
};

// ── 数量超限弹窗 ─────────────────────────────────────────────
const showCountOverLimitPop = ref(false);
const isCountOverLimitVisible = ref(false);
const countOverLimitResult = reactive({ total: 0, dropped: 0 });
let countOverLimitResolve: ((confirmed: boolean) => void) | null = null;

const openCountOverLimitPop = (total: number, dropped: number): Promise<boolean> => {
    countOverLimitResult.total = total;
    countOverLimitResult.dropped = dropped;
    showCountOverLimitPop.value = true;
    nextTick(() => (isCountOverLimitVisible.value = true));
    return new Promise((resolve) => {
        countOverLimitResolve = resolve;
    });
};

const closeCountOverLimitPop = () => {
    isCountOverLimitVisible.value = false;
    setTimeout(() => (showCountOverLimitPop.value = false), ANIM_DURATION);
};

const handleCountOverLimitReselect = () => {
    closeCountOverLimitPop();
    countOverLimitResolve?.(false);
    countOverLimitResolve = null;
};

const handleCountOverLimitConfirm = () => {
    closeCountOverLimitPop();
    countOverLimitResolve?.(true);
    countOverLimitResolve = null;
};

// ── 已选面板 ─────────────────────────────────────────────────
const toggleChoosePanel = () => {
    if (totalChooseCount.value === 0) return;
    showChoosePanel.value = !showChoosePanel.value;
};

const clearAll = () => {
    chooseLists.value = [];
    chooseGroups.value = [];
    showChoosePanel.value = false;
};

const isChooseGroup = (group: any) => chooseGroups.value.some((g) => g.id === group.id);

const toggleChooseGroup = (group: any) => {
    if (props.disableGroupSelect) return;
    if (isChooseGroup(group)) {
        removeChooseGroup(group);
        return;
    }
    chooseGroups.value.push(group);
};

const removeChooseGroup = (group: any) => {
    chooseGroups.value = chooseGroups.value.filter((g) => g.id !== group.id);
    if (totalChooseCount.value === 0) showChoosePanel.value = false;
};

// ── 分组交互 ─────────────────────────────────────────────────
/**
 * 点击分组行左侧区域的处理逻辑：
 *
 * mode=group：
 *   - 左侧点击 → 进入分组查看素材（与 PC 端一致，右侧箭头也可进入）
 *   - 右侧「选分组」按钮 → toggleChooseGroup
 *
 * mode=all / mode=list：
 *   - 左侧点击 → 进入分组
 */
const handleGroupItemClick = (item: any) => {
    enterGroup(item);
};

const enterGroup = (item: any) => {
    currentGroupItem.id = item.id;
    currentGroupItem.name = item.name;
    triggerReload();
};

const backToGroupList = () => {
    currentGroupItem.id = "";
    currentGroupItem.name = "";
    triggerReload();
};

const handleShowType = (type: ShowType) => {
    if (currShowType.value === type) return;
    currShowType.value = type;
    currentGroupItem.id = "";
    currentGroupItem.name = "";
    triggerReload();
};

// ── 分页 ─────────────────────────────────────────────────────
const triggerReload = () => {
    isLoading.value = true;
    pagingRef.value?.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        if (isGroupListMode.value) {
            const { lists } = await getMaterialLibraryGroupList({ page_no, page_size });
            pagingRef.value?.complete(lists);
        } else {
            const { lists } = await getMaterialLibraryList({
                page_no,
                page_size,
                m_type: queryMType.value,
                // currentGroupItem.id 有值时（分组内部），传入 group_id 过滤
                group_id: currentGroupItem.id || "",
            });
            pagingRef.value?.complete(lists);
        }
    } catch (error) {
        pagingRef.value?.complete([]);
    } finally {
        isLoading.value = false;
    }
};

// ── 素材选择 ─────────────────────────────────────────────────
const isChoose = (data: any) => chooseLists.value.some((item) => item.id === data.id);

const handleSelect = (data: any) => {
    if (!data) return;
    if (isChoose(data)) {
        chooseLists.value = chooseLists.value.filter((item) => item.id !== data.id);
        if (totalChooseCount.value === 0) showChoosePanel.value = false;
        return;
    }
    const isSingleMode = !props.multiple || props.limit === 1;
    if (isSingleMode) {
        chooseLists.value = [data];
    } else {
        if (props.limit && chooseLists.value.length >= props.limit) {
            uni.$u.toast(`最多选择${props.limit}个素材`);
            return;
        }
        chooseLists.value.push(data);
    }
};

// ── 全选 / 取消全选 ──────────────────────────────────────────
const toggleSelect = () => {
    if (isCurrentPageAllSelected.value) {
        const currentIds = new Set(dataLists.value.map((i) => i.id));
        chooseLists.value = chooseLists.value.filter((i) => !currentIds.has(i.id));
        if (totalChooseCount.value === 0) showChoosePanel.value = false;
    } else {
        for (const item of dataLists.value) {
            if (props.limit && chooseLists.value.length >= props.limit) break;
            if (!isChoose(item)) chooseLists.value.push(item);
        }
    }
};

// ── 获取缺失时长 ─────────────────────────────────────────────
const fetchMissingDurations = async (
    items: any[],
    onProgress: (done: number, total: number) => void,
): Promise<any[]> => {
    const missing = items.filter((item) => !isImage(item) && (!item.duration || item.duration <= 0));
    if (missing.length === 0) return items;

    const urlToItem = new Map<string, any>(missing.map((item) => [item.content, item]));
    const batchSize = 20;
    const batches: any[][] = [];
    for (let i = 0; i < missing.length; i += batchSize) {
        batches.push(missing.slice(i, i + batchSize));
    }

    let done = 0;
    for (const batch of batches) {
        try {
            const { results } = await batchGetVideoInfoByUrl({
                video_urls: batch.map((item) => item.content),
            });
            results.forEach((r: any) => {
                const item = urlToItem.get(r.url);
                if (item && r.data?.duration > 0) {
                    item.duration = r.data.duration;
                }
            });
            batchUpdateMaterialDate(
                results.map((r: any) => ({
                    id: urlToItem.get(r.url)?.id,
                    duration: r.data?.duration,
                })),
            );
        } finally {
            done += batch.length;
            onProgress(done, missing.length);
        }
    }
    return items;
};

const getItemDuration = (item: any): number => {
    if (isImage(item)) return props.imageDuration ?? 2;
    return item.duration || 0;
};

const applyDurationLimit = (
    items: any[],
): { result: any[]; kept: number; keptDuration: number; dropped: number; exceeded: boolean } => {
    if (!hasLimit.value) {
        return { result: items, kept: items.length, keptDuration: 0, dropped: 0, exceeded: false };
    }
    const maxSeconds = props.durationLimit! * 60;
    let accumulated = 0;
    const kept: any[] = [];
    for (const item of items) {
        const d = getItemDuration(item);
        if (accumulated + d <= maxSeconds) {
            accumulated += d;
            kept.push(item);
        } else {
            break;
        }
    }
    const dropped = items.length - kept.length;
    return {
        result: kept,
        kept: kept.length,
        keptDuration: accumulated,
        dropped,
        exceeded: dropped > 0,
    };
};

// ── 确认提交 ─────────────────────────────────────────────────
const confirm = async () => {
    if (totalChooseCount.value === 0) {
        uni.$u.toast(`至少选择一个${emptySelectTip.value}`);
        return;
    }
    if (isConfirming.value) return;

    isConfirming.value = true;
    previewDuration.value = 0;
    await openProgressPop();

    try {
        setProgress("正在从分组中获取素材列表...", "步骤 1/3：拉取分组", 5);

        const groupItems: any[] = [];
        for (let gi = 0; gi < chooseGroups.value.length; gi++) {
            const group = chooseGroups.value[gi];
            setProgress(
                `正在获取分组「${group.name}」的素材...`,
                `步骤 1/3：分组 ${gi + 1}/${chooseGroups.value.length}`,
                Math.round(5 + (gi / chooseGroups.value.length) * 30),
            );
            try {
                const { lists } = await getMaterialLibraryList({
                    page_no: 1,
                    page_size: 999,
                    group_id: group.id,
                    m_type: queryMType.value,
                });
                groupItems.push(
                    ...lists.map((item: any) => ({
                        ...item,
                        url: item.content,
                        type: resolveContentType(item),
                        pic: isImage(item) ? item.pic || item.content : item.pic,
                        _fromGroup: group.id,
                    })),
                );
            } catch (e) {
                console.error(`分组 ${group.name} 拉取失败`, e);
            }
        }

        setProgress("正在合并素材...", "步骤 2/3：合并去重", 40);

        const singleItems = chooseLists.value.map((item: any) => ({
            ...item,
            url: item.content,
            type: resolveContentType(item),
            pic: isImage(item) ? item.pic || item.content : item.pic,
        }));

        const idSet = new Set(singleItems.map((i: any) => i.id));
        const mergedGroupItems: any[] = [];
        for (const item of groupItems) {
            if (!idSet.has(item.id)) {
                idSet.add(item.id);
                mergedGroupItems.push(item);
            }
        }
        let allItems = [...singleItems, ...mergedGroupItems];

        if (props.limit && allItems.length > props.limit) {
            setProgress("素材数量超出限制，等待确认...", "步骤 3/3：数量校验", 45);
            closeProgressPop();

            const confirmed = await openCountOverLimitPop(allItems.length, allItems.length - props.limit);
            if (!confirmed) {
                isConfirming.value = false;
                return;
            }

            allItems = allItems.slice(0, props.limit);
            await openProgressPop();
        }

        if (hasLimit.value) {
            const videosWithoutDuration = allItems.filter(
                (item) => !isImage(item) && !isAudio(item) && (!item.duration || item.duration <= 0),
            );

            if (videosWithoutDuration.length > 0) {
                setProgress(`正在获取 ${videosWithoutDuration.length} 个视频的时长信息...`, "步骤 3/3：获取时长", 50);
                allItems = await fetchMissingDurations(allItems, (done, total) => {
                    const p = Math.round(50 + (done / total) * 35);
                    previewDuration.value = allItems
                        .slice(0, done)
                        .reduce((acc, item) => acc + getItemDuration(item), 0);
                    setProgress(`正在获取视频时长 (${done}/${total})...`, "步骤 3/3：获取时长", p);
                });
            } else {
                setProgress("正在校验时长限制...", "步骤 3/3：校验时长", 85);
            }

            setProgress("正在校验时长限制...", "完成", 95);
            const { result, kept, keptDuration, dropped, exceeded } = applyDurationLimit(allItems);

            closeProgressPop();
            isConfirming.value = false;

            if (exceeded) {
                pendingResult = result;
                overLimitResult.kept = kept;
                overLimitResult.keptDuration = keptDuration;
                overLimitResult.dropped = dropped;
                await openOverLimitPop();
            } else {
                emitResult(result);
            }
        } else {
            setProgress("完成", "完成", 100);
            closeProgressPop();
            isConfirming.value = false;
            emitResult(allItems);
        }
    } catch (e) {
        closeProgressPop();
        isConfirming.value = false;
        uni.$u.toast(e || "处理失败，请重试");
    }
};

const emitResult = (result: any[]) => {
    stopAudioPreview();
    show.value = false;
    emit(
        "select",
        result.map((item) => ({
            id: item.id,
            name: item.name,
            pic: item.pic,
            url: item.content || item.url,
            size: item.size,
            duration: item.duration,
            type: resolveContentType(item),
        })),
    );
    chooseLists.value = [];
    chooseGroups.value = [];
    showChoosePanel.value = false;
    pendingResult = [];
};

const handleOverLimitConfirm = () => {
    closeOverLimitPop();
    emitResult(pendingResult);
};

const handleOverLimitReselect = () => {
    closeOverLimitPop();
    pendingResult = [];
};

// ── 初始化 ───────────────────────────────────────────────────
const initShowType = () => {
    currShowType.value = props.mode === "group" ? ShowType.GROUP : ShowType.ALL;
    currentGroupItem.id = "";
    currentGroupItem.name = "";
};

watch(
    () => props.modelValue,
    async (newVal) => {
        if (newVal) {
            initShowType();
            showChoosePanel.value = false;
            stopAudioPreview();
            await nextTick();
            triggerReload();
        } else {
            stopAudioPreview();
            destroy();
        }
    },
    { immediate: true, deep: true },
);
</script>
