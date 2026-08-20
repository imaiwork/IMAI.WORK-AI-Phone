<template>
    <view>
        <!-- 资源类型主导航（始终显示）-->
        <view class="mb-[22rpx]">
            <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                <view class="flex gap-[16rpx] whitespace-nowrap">
                    <view
                        v-for="tab in state.materialSubTabs"
                        :key="tab.key"
                        class="px-[26rpx] py-[14rpx] rounded-full shrink-0 border border-solid"
                        :class="
                            state.activeMaterialTab === tab.key
                                ? 'bg-primary border-primary'
                                : 'bg-[#F4F6FA] border-[#E8ECF0]'
                        "
                        @click="actions.switchMaterialTab(tab.key)">
                        <text
                            class="text-xs font-semibold"
                            :class="state.activeMaterialTab === tab.key ? 'text-white' : 'text-[#888888]'">
                            {{ tab.label }}
                        </text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 素材内容 -->
        <template v-if="state.activeMaterialTab === MaterialTabEnum.COMPOSE">
            <!-- 次级：剪辑原片库 / 成品直发库（本地视图切换，切换会按模式重新拉列表）-->
            <view class="inline-flex bg-[#EEF1F6] p-[6rpx] rounded-[18rpx] mb-[16rpx]">
                <view
                    v-for="option in state.materialPublishModes"
                    :key="option.value"
                    class="px-[26rpx] h-[56rpx] rounded-[14rpx] flex items-center justify-center"
                    :class="
                        state.publishMode === option.value ? 'bg-white shadow-[0_4rpx_16rpx_rgba(14,35,72,0.08)]' : ''
                    "
                    @click="actions.switchPublishMode(option.value)">
                    <text
                        class="text-[24rpx] font-semibold line-clamp-1"
                        :class="state.publishMode === option.value ? 'text-[#1D2129]' : 'text-[#9CA3AF]'">
                        {{ option.title }}
                    </text>
                </view>
            </view>

            <!-- 模式说明横幅 -->
            <view
                class="flex items-start gap-[14rpx] mb-[18rpx] px-[26rpx] py-[20rpx] rounded-[24rpx] border border-solid border-[#BAD4FF]"
                style="background: linear-gradient(135deg, #ebf3ff, #f0f7ff)">
                <u-icon name="info-circle" color="#2F73F6" size="28" class="shrink-0 mt-[2rpx]"></u-icon>
                <text class="text-[22rpx] text-[#5C7ECC] leading-relaxed">{{ bannerText }}</text>
            </view>

            <view class="flex items-center gap-[14rpx] mb-[18rpx] overflow-hidden h-[60rpx]">
                <view
                    v-for="(filter, index) in state.materialFilters"
                    :key="filter.name"
                    class="px-[24rpx] py-[10rpx] rounded-full border border-solid shrink-0"
                    :class="
                        state.activeMaterialFilter === index
                            ? 'bg-primary border-primary'
                            : 'bg-[#F4F6FA] border-[#E8ECF0]'
                    "
                    @click="actions.materialFilter(index)">
                    <text
                        class="text-xs font-semibold"
                        :class="state.activeMaterialFilter === index ? 'text-white' : 'text-[#888888]'">
                        {{ filter.name }}
                    </text>
                </view>
                <view
                    v-if="state.publishMode === 1"
                    class="ml-auto w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center shrink-0 border border-solid"
                    :class="state.batchDeleteMode ? 'bg-[#EBF2FF] border-[#BAD4FF]' : 'bg-[#F4F6FA] border-[#E8ECF0]'"
                    @click="actions.toggleBatchDelete()">
                    <u-icon name="trash" :color="state.batchDeleteMode ? '#0065FB' : '#9CA3AF'" size="24"></u-icon>
                </view>
                <view
                    class="flex items-center gap-[6rpx] text-[22rpx] font-semibold text-white bg-primary rounded-full px-[22rpx] py-[10rpx] shrink-0"
                    :class="state.publishMode === 2 ? 'ml-auto' : ''"
                    @click="actions.upload()">
                    <u-icon name="plus" color="#FFFFFF" size="20"></u-icon>
                    <text>上传</text>
                </view>
            </view>

            <view
                v-if="state.publishMode === 1 && state.hasOverusedMaterial"
                class="bg-[#FFF7ED] rounded-[28rpx] px-[26rpx] py-[22rpx] flex gap-[16rpx] mb-[18rpx] border border-solid border-[#FED7AA]">
                <u-icon name="warning" color="#F97316" size="28" class="shrink-0 mt-[2rpx]"></u-icon>
                <text class="text-xs text-[#EA580C] leading-relaxed">
                    有素材使用已达3次，建议及时替换，避免影响内容流量权重。
                </text>
            </view>

            <view
                v-if="state.publishMode === 1 && !state.hasSlicingMaterial && state.failedMaterialCount > 0"
                class="bg-[#FEF2F2] rounded-[28rpx] px-[26rpx] py-[22rpx] flex items-center gap-[16rpx] mb-[18rpx] border border-solid border-[#FCA5A5]">
                <u-icon name="close-circle" color="#EF4444" size="28" class="shrink-0"></u-icon>
                <text class="flex-1 min-w-0 text-xs text-[#DC2626] leading-relaxed">
                    有 {{ state.failedMaterialCount }} 条视频分割失败，无法用于合成，建议删除后重新上传。
                </text>
                <view
                    class="shrink-0 px-[22rpx] py-[12rpx] rounded-full bg-[#EF4444] active:opacity-80"
                    @click="actions.deleteFailedSlices()">
                    <text class="text-[22rpx] font-semibold text-white">一键删除</text>
                </view>
            </view>

            <view v-if="state.publishMode === 2" class="flex items-center mb-[18rpx]">
                <text class="text-xs font-semibold text-[#6B7280]">当前素材</text>
                <text class="text-[22rpx] text-[#9CA3AF] ml-[8rpx]"> · 每条素材只能使用一次 </text>
            </view>

            <!-- 切割仅针对视频：有失败且无切割中时不展示；删除后无失败时靠 statistics 为空收口 -->
            <slice-progress
                v-if="
                    state.publishMode === 1 &&
                    state.materialFilters[state.activeMaterialFilter]?.value !== 2 &&
                    state.hasSlicingTask &&
                    (state.hasSlicingMaterial || state.failedMaterialCount === 0)
                "
                :statistics="state.sliceStatistics" />

            <view v-if="state.materialList.length" class="grid grid-cols-2 gap-[20rpx]">
                <view
                    v-for="item in state.materialList"
                    :key="item.id"
                    class="relative bg-white rounded-[28rpx] overflow-hidden border border-solid detail-card-shadow"
                    :class="state.isMaterialSelected(item.id) ? 'border-primary' : 'border-[#F3F4F6]'"
                    @click="state.batchDeleteMode && actions.toggleMaterialSelected(item.id)">
                    <view
                        v-if="state.batchDeleteMode"
                        class="absolute top-[14rpx] right-[14rpx] z-10 w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="
                            state.isMaterialSelected(item.id)
                                ? 'bg-primary border-primary'
                                : 'bg-white border-[#D1D5DB]'
                        "
                        @click.stop="actions.toggleMaterialSelected(item.id)">
                        <u-icon
                            v-if="state.isMaterialSelected(item.id)"
                            name="checkbox-mark"
                            color="#FFFFFF"
                            size="24"></u-icon>
                    </view>
                    <view
                        class="relative h-[220rpx] bg-[#F6F6F6]"
                        @click.stop="
                            state.batchDeleteMode
                                ? actions.toggleMaterialSelected(item.id)
                                : actions.previewMaterial(item)
                        ">
                        <view
                            v-if="state.isMaterialSlicing(item)"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-[12rpx] bg-[#EEF3FB]">
                            <view class="mat-slice-spinner"></view>
                            <text class="text-[20rpx] font-semibold text-primary">切割中…</text>
                        </view>
                        <view
                            v-else-if="state.isMaterialSliceFailed(item)"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-[10rpx] bg-[#FEF2F2]">
                            <image
                                v-if="item.thumbnail_url"
                                :src="item.thumbnail_url"
                                class="absolute inset-0 w-full h-full opacity-30"
                                mode="aspectFill"></image>
                            <view class="relative z-[1] flex flex-col items-center gap-[10rpx]">
                                <u-icon name="close-circle" color="#EF4444" size="40"></u-icon>
                                <text class="text-[20rpx] font-semibold text-[#EF4444]">
                                    {{ item.slice_status_text || "分割失败" }}
                                </text>
                            </view>
                        </view>
                        <template v-else>
                            <image :src="item.thumbnail_url" class="w-full h-full" mode="aspectFill"></image>
                        </template>
                        <view class="absolute top-[14rpx] left-[14rpx] flex flex-col gap-[8rpx]">
                            <text
                                class="text-[18rpx] font-bold text-white px-[10rpx] py-[4rpx] rounded-[8rpx] w-fit"
                                style="background: rgba(0, 0, 0, 0.55)">
                                {{ item.material_type === 1 ? "视频" : item.material_type === 2 ? "图片" : "素材" }}
                            </text>
                            <text
                                v-if="item.grab_type === 1"
                                class="text-[18rpx] font-bold text-white px-[10rpx] py-[4rpx] rounded-[8rpx]"
                                style="background: rgba(124, 58, 237, 0.82)">
                                AI抓取
                            </text>
                        </view>
                        <view
                            v-if="
                                state.publishMode === 1 &&
                                item.use_num >= 3 &&
                                !state.batchDeleteMode &&
                                !state.isMaterialSliceFailed(item)
                            "
                            class="absolute top-[14rpx] right-[14rpx] w-[36rpx] h-[36rpx] rounded-full bg-[#FBBF24] flex items-center justify-center">
                            <u-icon name="warning" color="#FFFFFF" size="20"></u-icon>
                        </view>
                        <view
                            v-if="
                                item.material_type === 1 &&
                                !state.isMaterialSlicing(item) &&
                                !state.isMaterialSliceFailed(item)
                            "
                            class="absolute inset-0 flex items-center justify-center"
                            @click.stop="
                                state.batchDeleteMode
                                    ? actions.toggleMaterialSelected(item.id)
                                    : actions.previewMaterial(item)
                            ">
                            <view
                                class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center"
                                style="background: rgba(255, 255, 255, 0.3)">
                                <u-icon name="play-right-fill" color="#FFFFFF" size="28"></u-icon>
                            </view>
                        </view>
                        <text
                            v-if="item.duration && !state.isMaterialSliceFailed(item)"
                            class="absolute left-[14rpx] bottom-[14rpx] text-[20rpx] text-white font-mono">
                            {{ formatAudioTime(item.duration) }}
                        </text>
                    </view>
                    <view class="px-[20rpx] py-[18rpx]">
                        <template v-if="state.publishMode === 1">
                            <view class="flex items-center justify-between mb-[10rpx]">
                                <text class="text-xs font-semibold text-[#1F2937] line-clamp-1 pr-[10rpx]">
                                    {{ item.material_name }}
                                </text>
                                <u-icon
                                    v-if="!state.batchDeleteMode && !state.isMaterialSlicing(item)"
                                    name="more-dot-fill"
                                    color="#9CA3AF"
                                    size="28"
                                    @click="actions.moreMaterial(item)"></u-icon>
                            </view>
                            <view class="flex items-center justify-between">
                                <text
                                    v-if="state.isMaterialSlicing(item)"
                                    class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-[8rpx] text-primary bg-[#EBF2FF]">
                                    切割中
                                </text>
                                <text
                                    v-else-if="state.isMaterialSliceFailed(item)"
                                    class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-[8rpx] text-[#EF4444] bg-[#FEF2F2]">
                                    {{ item.slice_status_text || "分割失败" }}
                                </text>
                                <text
                                    v-else
                                    class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-[8rpx]"
                                    :class="
                                        item.use_num >= 3
                                            ? 'text-[#D97706] bg-[#FFF7ED]'
                                            : item.use_num > 0
                                            ? 'text-[#16A34A] bg-[#F0FDF4]'
                                            : 'text-[#9CA3AF] bg-[#F9FAFB]'
                                    ">
                                    {{ item.use_num > 0 ? `已用${item.use_num}次` : "未使用" }}
                                </text>
                                <text class="text-[18rpx] text-[#C4C9D4]">
                                    {{ state.formatMaterialTime(item.create_time) }}
                                </text>
                            </view>
                        </template>
                        <view v-else>
                            <text class="text-xs font-semibold text-[#1F2937] line-clamp-1 block">
                                {{ item.material_name }}
                            </text>
                            <view class="flex items-center justify-between mt-[12rpx]">
                                <text class="text-[18rpx] text-[#C4C9D4]">
                                    {{ state.formatMaterialTime(item.create_time) }}
                                </text>
                                <view
                                    v-if="!state.batchDeleteMode"
                                    class="flex items-center gap-[4rpx] text-[20rpx] text-[#9CA3AF]"
                                    @click.stop="actions.removeMaterial(item)">
                                    <u-icon name="trash" color="#9CA3AF" size="18"></u-icon>
                                    <text>移除</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <empty
                v-else-if="!state.materialLoading && state.materialList.length === 0"
                :text="state.publishMode === 1 ? '暂无素材' : '暂无成品素材'" />
            <view class="py-[24rpx] flex items-center justify-center gap-[12rpx]">
                <u-loading v-if="state.materialLoading" mode="circle" size="28" color="#999999"></u-loading>
                <text v-if="state.materialLoading" class="text-xs text-[#9CA3AF]">加载中...</text>
                <text
                    v-else-if="state.materialFinished && state.materialList.length"
                    class="text-[22rpx] text-[#C4C9D4]">
                    已加载全部
                </text>
            </view>

            <!-- 批量删除模式下为固定操作栏预留高度，避免遮挡列表底部 -->
            <view v-if="state.batchDeleteMode" class="h-[150rpx]"></view>

            <view v-if="state.batchDeleteMode" class="mat-batch-bar">
                <view class="flex items-center gap-[10rpx] shrink-0" @click="actions.toggleSelectAll()">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="
                            state.isAllMaterialSelected ? 'bg-primary border-primary' : 'bg-white border-[#D1D5DB]'
                        ">
                        <u-icon
                            v-if="state.isAllMaterialSelected"
                            name="checkbox-mark"
                            color="#FFFFFF"
                            size="24"></u-icon>
                    </view>
                    <text class="text-xs font-semibold text-[#374151]">全选</text>
                </view>
                <text class="text-xs text-[#9CA3AF]"> 已选 {{ state.selectedMaterialCount }} 项 </text>
                <view class="flex items-center gap-[14rpx] ml-auto shrink-0">
                    <view
                        class="text-xs text-[#9CA3AF] bg-[#F4F6FA] border border-solid border-[#E8ECF0] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.cancelBatchDelete()">
                        取消
                    </view>
                    <view
                        class="text-xs text-white bg-[#EF4444] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.confirmBatchDelete()">
                        删除所选
                    </view>
                </view>
            </view>
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.AVATAR">
            <digital-tip />
            <view class="flex items-center px-[4rpx] pt-[18rpx] mb-[18rpx]">
                <text class="text-sm font-bold text-[#1F2937]">数字人形象</text>
                <text class="ml-[10rpx] text-xs text-[#9CA3AF]">({{ state.avatars.length }})</text>
            </view>
            <view class="grid grid-cols-2 gap-[20rpx]">
                <view
                    v-for="item in state.avatars"
                    :key="item.id"
                    class="bg-white rounded-[28rpx] overflow-hidden border border-solid border-[#F3F4F6] detail-card-shadow">
                    <view class="relative h-[320rpx] bg-[#F4F5F7]">
                        <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
                        <view
                            class="absolute top-[14rpx] right-[14rpx] w-[44rpx] h-[44rpx] rounded-full flex items-center justify-center"
                            style="background: rgba(0, 0, 0, 0.5)"
                            @click.stop="actions.removeAvatar(item)">
                            <u-icon name="close" color="#FFFFFF" size="20"></u-icon>
                        </view>
                        <view
                            class="absolute left-0 right-0 bottom-0 px-[18rpx] py-[14rpx] flex items-center gap-[8rpx]"
                            style="background: linear-gradient(to top, rgba(0, 0, 0, 0.65), transparent)">
                            <u-icon name="volume-up" color="#FFFFFF" size="18"></u-icon>
                            <text class="text-[20rpx] text-white line-clamp-1">
                                {{
                                    item.is_original_voice === 1
                                        ? item.bind_desc || "形象原音"
                                        : item.voice_name || "未绑定音色"
                                }}
                            </text>
                        </view>
                    </view>
                    <view class="px-[22rpx] pt-[18rpx] pb-[22rpx]">
                        <view class="flex items-center justify-between mb-[16rpx]">
                            <text class="text-xs font-bold text-[#1F2937] line-clamp-1">
                                {{ item.name }}
                            </text>
                            <text
                                class="text-[18rpx] text-[#9CA3AF] bg-[#F9FAFB] rounded-[8rpx] px-[10rpx] py-[4rpx] shrink-0">
                                已用 {{ item.use_count || 0 }} 次
                            </text>
                        </view>
                        <view
                            class="h-[58rpx] rounded-[16rpx] px-[16rpx] flex items-center justify-between"
                            style="background: #ebf3ff; border: 2rpx solid #bad4ff"
                            @click="actions.openVoiceForAvatar(item)">
                            <view class="flex items-center gap-[8rpx] min-w-0">
                                <u-icon name="link" color="#0065FB" size="18"></u-icon>
                                <text class="text-[20rpx] font-semibold text-primary line-clamp-1">
                                    已绑：{{
                                        item.is_original_voice === 1
                                            ? item.bind_desc || "形象原音"
                                            : item.voice_name || "未绑定"
                                    }}
                                </text>
                            </view>
                            <u-icon name="arrow-down" color="#0065FB" size="18"></u-icon>
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[28rpx] border-[4rpx] border-dashed border-[#E5E7EB] min-h-[420rpx] flex flex-col items-center justify-center gap-[18rpx] active:bg-[#F9FAFB]"
                    @click="actions.addAvatar()">
                    <view class="w-[80rpx] h-[80rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                        <u-icon name="plus" color="#60A5FA" size="36"></u-icon>
                    </view>
                    <text class="text-xs text-[#9CA3AF]">添加形象</text>
                </view>
            </view>
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.VOICE">
            <digital-tip />
            <view class="flex items-center px-[4rpx] pt-[18rpx] mb-[18rpx]">
                <text class="text-sm font-bold text-[#1F2937]">数字人音色</text>
                <text class="ml-[10rpx] text-xs text-[#9CA3AF]">({{ state.voices.length }})</text>
            </view>
            <view class="flex flex-col gap-[18rpx]">
                <view
                    v-for="item in state.voices"
                    :key="item.voice_id"
                    class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] detail-card-shadow">
                    <view class="flex items-center gap-[22rpx]">
                        <view
                            class="w-[96rpx] h-[96rpx] rounded-[24rpx] bg-[#EBF3FF] flex items-center justify-center shrink-0">
                            <u-icon name="volume-up" color="#0065FB" size="42"></u-icon>
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="text-sm font-bold text-[#1F2937] line-clamp-1">
                                {{ item.name }}
                            </text>
                            <text class="text-[20rpx] text-[#9CA3AF] mt-[6rpx] block">
                                {{ state.formatMaterialTime(item.create_time) || "暂无添加时间" }}
                                添加
                            </text>
                        </view>
                        <view
                            class="px-[22rpx] py-[14rpx] rounded-full flex items-center gap-[8rpx]"
                            :style="
                                state.isCurrentPlaying(item.voice_id)
                                    ? 'background:#FFF0F0;border:2rpx solid #FFD6D6'
                                    : 'background:#EBF3FF;border:2rpx solid #BAD4FF'
                            "
                            @click.stop="actions.playVoice(item)">
                            <u-icon
                                :name="state.isCurrentPlaying(item.voice_id) ? 'pause-circle' : 'play-circle'"
                                :color="state.isCurrentPlaying(item.voice_id) ? '#FF4D4F' : '#0065FB'"
                                size="26"></u-icon>
                            <text
                                class="text-xs font-semibold"
                                :class="state.isCurrentPlaying(item.voice_id) ? 'text-[#FF4D4F]' : 'text-primary'">
                                {{ state.isCurrentPlaying(item.voice_id) ? "暂停" : "试听" }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="mt-[20rpx] pt-[18rpx] border-0 border-t border-solid border-[#F3F4F6] flex items-center justify-between">
                        <text class="text-[20rpx] text-[#9CA3AF]"> 已使用 {{ item.use_count || 0 }} 次 </text>
                        <view
                            class="flex items-center gap-[6rpx] text-[22rpx] text-[#9CA3AF]"
                            @click="actions.removeVoice(item)">
                            <u-icon name="trash" color="#9CA3AF" size="20"></u-icon>
                            <text>移除</text>
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[28rpx] border-[4rpx] border-dashed border-[#E5E7EB] py-[40rpx] flex items-center justify-center gap-[16rpx] active:bg-[#F9FAFB]"
                    @click="actions.addVoice()">
                    <view class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                        <u-icon name="plus" color="#0065FB" size="26"></u-icon>
                    </view>
                    <text class="text-xs text-[#9CA3AF] font-medium">添加新音色</text>
                </view>
            </view>
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.MUSIC">
            <view
                class="flex items-start gap-[14rpx] mb-[18rpx] px-[26rpx] py-[20rpx] rounded-[24rpx] border border-solid border-[#BAD4FF]"
                style="background: linear-gradient(135deg, #ebf3ff, #f0f7ff)">
                <u-icon name="volume-up" color="#2F73F6" size="28" class="shrink-0 mt-[2rpx]"></u-icon>
                <text class="text-[22rpx] text-[#5C7ECC] leading-relaxed">
                    上传自定义背景音乐，AI 剪辑视频时可从人设音乐库中随机取用配乐。
                    <text class="font-bold">仅支持 MP3 / WAV 格式</text>。
                </text>
            </view>

            <view class="flex items-center gap-[16rpx] mb-[18rpx]">
                <text class="text-xs font-semibold text-[#6B7280] flex-1">
                    我的音乐
                    <text class="font-normal text-[#9CA3AF]"> · {{ state.musicList.length }} 首</text>
                </text>
                <view
                    v-if="state.musicList.length"
                    class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center shrink-0 border border-solid"
                    :class="state.musicBatchMode ? 'bg-[#EBF2FF] border-[#BAD4FF]' : 'bg-[#F4F6FA] border-[#E8ECF0]'"
                    @click="actions.toggleMusicBatch()">
                    <u-icon name="trash" :color="state.musicBatchMode ? '#0065FB' : '#9CA3AF'" size="24"></u-icon>
                </view>
                <view
                    class="flex items-center gap-[6rpx] text-[22rpx] font-semibold text-white bg-primary rounded-full px-[22rpx] py-[10rpx] shrink-0 active:opacity-85"
                    @click="actions.uploadMusic()">
                    <u-icon name="plus" color="#FFFFFF" size="20"></u-icon>
                    <text>上传音乐</text>
                </view>
            </view>

            <view v-if="state.musicList.length" class="flex flex-col gap-[18rpx]">
                <view
                    v-for="item in state.musicList"
                    :key="item.id"
                    class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] detail-card-shadow flex items-center gap-[22rpx] border border-solid"
                    :class="state.isMusicSelected(item.id) ? 'border-primary' : 'border-[transparent]'"
                    @click="state.musicBatchMode && actions.toggleMusicSelected(item.id)">
                    <view
                        v-if="state.musicBatchMode"
                        class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center shrink-0"
                        :class="
                            state.isMusicSelected(item.id) ? 'bg-primary border-primary' : 'bg-white border-[#D1D5DB]'
                        "
                        @click.stop="actions.toggleMusicSelected(item.id)">
                        <u-icon
                            v-if="state.isMusicSelected(item.id)"
                            name="checkbox-mark"
                            color="#FFFFFF"
                            size="24"></u-icon>
                    </view>
                    <view
                        v-else
                        class="w-[80rpx] h-[80rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center shrink-0 active:opacity-80"
                        @click.stop="actions.playMusic(item)">
                        <u-icon
                            :name="state.isCurrentMusicPlaying(item.id) ? 'pause-circle' : 'play-circle'"
                            color="#2F73F6"
                            size="32"></u-icon>
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="text-[26rpx] font-semibold text-[#1F2937] line-clamp-1">
                            {{ item.material_name || "未命名音乐" }}
                        </text>
                    </view>
                    <view
                        v-if="!state.musicBatchMode"
                        class="flex items-center gap-[6rpx] text-[22rpx] text-[#9CA3AF] shrink-0 active:text-[#EF4444]"
                        @click.stop="actions.removeMusic(item)">
                        <u-icon name="trash" color="#9CA3AF" size="20"></u-icon>
                        <text>移出</text>
                    </view>
                </view>
            </view>

            <view
                v-else-if="!state.musicLoading"
                class="bg-white rounded-[28rpx] border-[4rpx] border-dashed border-[#E5E7EB] py-[64rpx] flex flex-col items-center justify-center gap-[12rpx] active:bg-[#F9FAFB]"
                @click="actions.uploadMusic()">
                <view class="w-[72rpx] h-[72rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                    <u-icon name="volume-up" color="#0065FB" size="34"></u-icon>
                </view>
                <text class="text-xs font-semibold text-[#6B7280]">暂无音乐</text>
                <text class="text-[22rpx] text-[#9CA3AF]">点击上传背景音乐</text>
            </view>

            <view v-if="state.musicLoading" class="py-[40rpx] flex justify-center">
                <text class="text-xs text-[#9CA3AF]">加载中...</text>
            </view>

            <view v-if="state.musicBatchMode" class="h-[150rpx]"></view>
            <view v-if="state.musicBatchMode" class="mat-batch-bar">
                <view class="flex items-center gap-[10rpx] shrink-0" @click="actions.toggleSelectAllMusic()">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="state.isAllMusicSelected ? 'bg-primary border-primary' : 'bg-white border-[#D1D5DB]'">
                        <u-icon v-if="state.isAllMusicSelected" name="checkbox-mark" color="#FFFFFF" size="24"></u-icon>
                    </view>
                    <text class="text-xs font-semibold text-[#374151]">全选</text>
                </view>
                <text class="text-xs text-[#9CA3AF]"> 已选 {{ state.selectedMusicCount }} 项 </text>
                <view class="flex items-center gap-[14rpx] ml-auto shrink-0">
                    <view
                        class="text-xs text-[#9CA3AF] bg-[#F4F6FA] border border-solid border-[#E8ECF0] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.cancelMusicBatch()">
                        取消
                    </view>
                    <view
                        class="text-xs text-white bg-[#EF4444] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.confirmMusicBatchDelete()">
                        移出所选
                    </view>
                </view>
            </view>
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.COPY">
            <!-- 说明条：默认收起，减少首屏 chrome -->
            <view
                class="flex items-start gap-[14rpx] mb-[22rpx] px-[26rpx] py-[18rpx] rounded-[24rpx] border border-solid border-[#BAD4FF] active:opacity-80"
                style="background: linear-gradient(135deg, #ebf3ff, #f0f7ff)"
                @click="copyTipExpanded = !copyTipExpanded">
                <u-icon name="info-circle" color="#2F73F6" size="30" class="shrink-0 mt-[2rpx]"></u-icon>
                <view class="flex-1 min-w-0">
                    <text v-if="!copyTipExpanded" class="text-[22rpx] text-[#5C7ECC] font-semibold leading-relaxed">
                        点击了解文案类型区别
                    </text>
                    <text v-else class="text-[22rpx] text-[#5C7ECC] leading-relaxed">
                        “视频驱动文案”用于 AI 生成视频时驱动画面与口播；“发布文案”用于发布到平台的标题、正文与话题。
                    </text>
                </view>
                <u-icon
                    :name="copyTipExpanded ? 'arrow-up' : 'arrow-down'"
                    color="#7AABFF"
                    size="22"
                    class="shrink-0 mt-[6rpx]"></u-icon>
            </view>

            <!-- 唯一强分段：视频驱动文案 / 发布文案 -->
            <view class="flex bg-[#EEF1F6] p-[8rpx] rounded-[22rpx] mb-[22rpx]">
                <view
                    v-for="tab in state.copyTabs"
                    :key="tab.key"
                    class="flex-1 h-[64rpx] rounded-[16rpx] flex items-center justify-center"
                    :class="state.copyTab === tab.key ? 'bg-white shadow-[0_8rpx_24rpx_rgba(14,35,72,0.08)]' : ''"
                    @click="actions.switchCopyTab(tab.key)">
                    <text
                        class="text-xs font-bold"
                        :class="state.copyTab === tab.key ? 'text-[#1F2937]' : 'text-[#9CA3AF]'">
                        {{ tab.label }}
                    </text>
                </view>
            </view>

            <!-- 操作行：视频驱动含「类型▾」；发布文案无类型 -->
            <view class="flex items-center gap-[12rpx] mb-[18rpx]">
                <view
                    v-if="state.copyTab === CopyLibraryTypeEnum.DRIVE"
                    class="flex items-center gap-[4rpx] max-w-[240rpx] text-[22rpx] font-semibold text-[#374151] bg-[#F4F6FA] border border-solid border-[#E8ECF0] rounded-full px-[20rpx] py-[10rpx] shrink-0 active:opacity-70"
                    @click="handleCopyDriveTypePick()">
                    <text class="line-clamp-1 break-all">{{ currentCopyDriveTypeLabel }}</text>
                    <u-icon name="arrow-down" color="#9CA3AF" size="18"></u-icon>
                </view>
                <view
                    v-if="!state.copyBatchMode"
                    class="flex items-center gap-[6rpx] text-[22rpx] font-semibold text-primary bg-[#EBF2FF] border border-solid border-[#BAD4FF] rounded-full px-[24rpx] py-[10rpx] shrink-0"
                    @click="actions.addCopy()">
                    <u-icon name="plus" color="#2F73F6" size="20"></u-icon>
                    <text>新增</text>
                </view>
                <view
                    v-if="!state.copyBatchMode"
                    class="flex items-center gap-[6rpx] text-[22rpx] font-semibold text-white bg-primary rounded-full px-[24rpx] py-[10rpx] shrink-0"
                    @click="actions.aiGenerate()">
                    <u-icon name="star" color="#FFFFFF" size="20"></u-icon>
                    <text>AI 生成</text>
                </view>
                <view
                    class="ml-auto w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center shrink-0 border border-solid"
                    :class="state.copyBatchMode ? 'bg-[#EBF2FF] border-[#BAD4FF]' : 'bg-[#F4F6FA] border-[#E8ECF0]'"
                    @click="handleCopyManage()">
                    <u-icon
                        name="more-dot-fill"
                        :color="state.copyBatchMode ? '#0065FB' : '#9CA3AF'"
                        size="26"></u-icon>
                </view>
            </view>

            <!-- 文案列表 -->
            <view v-if="state.copyList.length" class="flex flex-col gap-[18rpx]">
                <view
                    v-for="item in state.copyList"
                    :key="item.id"
                    class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] detail-card-shadow border border-solid"
                    :class="
                        state.copyBatchMode && state.isCopySelected(item.id) ? 'border-primary' : 'border-[transparent]'
                    "
                    @click="state.copyBatchMode ? actions.toggleCopySelected(item.id) : actions.editCopy(item)">
                    <view class="flex items-start gap-[16rpx]">
                        <text
                            v-if="isCopyNews"
                            class="shrink-0 mt-[4rpx] text-[20rpx] font-bold px-[12rpx] py-[4rpx] rounded-[8rpx] bg-[#FEF3C7] text-[#B45309]">
                            标题
                        </text>
                        <text class="flex-1 text-sm font-bold text-[#1F2937] leading-snug">
                            {{ item.title || "（无标题）" }}
                        </text>
                        <view
                            v-if="state.copyBatchMode"
                            class="shrink-0 mt-[2rpx] w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                            :class="
                                state.isCopySelected(item.id)
                                    ? 'bg-primary border-primary'
                                    : 'bg-white border-[#D1D5DB]'
                            "
                            @click.stop="actions.toggleCopySelected(item.id)">
                            <u-icon
                                v-if="state.isCopySelected(item.id)"
                                name="checkbox-mark"
                                color="#FFFFFF"
                                size="22"></u-icon>
                        </view>
                        <view v-else class="shrink-0 mt-[2rpx]" @click.stop="actions.removeCopy(item)">
                            <u-icon name="trash" color="#C4C9D4" size="30"></u-icon>
                        </view>
                    </view>
                    <text
                        v-if="!isCopyNews && item.content"
                        class="block mt-[12rpx] text-[24rpx] text-[#6B7280] leading-relaxed line-clamp-3">
                        {{ item.content }}
                    </text>
                    <view
                        v-if="isCopyPublish && topicTags(item.topic).length"
                        class="flex flex-wrap gap-[10rpx] mt-[16rpx]">
                        <text
                            v-for="(tag, i) in topicTags(item.topic)"
                            :key="i"
                            class="text-[20rpx] px-[16rpx] py-[4rpx] rounded-full bg-[#EBF3FF] text-primary">
                            {{ tag }}
                        </text>
                    </view>
                </view>
            </view>
            <empty
                v-else-if="!state.copyLoading"
                :text="isCopyPublish ? '暂无发布文案，点击上方新增' : '暂无文案，点击上方新增'" />

            <view v-if="state.copyLoading" class="py-[24rpx] flex items-center justify-center gap-[12rpx]">
                <u-loading mode="circle" size="28" color="#999999"></u-loading>
                <text class="text-xs text-[#9CA3AF]">加载中...</text>
            </view>

            <!-- 批量删除固定操作栏 -->
            <view v-if="state.copyBatchMode" class="h-[150rpx]"></view>
            <view v-if="state.copyBatchMode" class="mat-batch-bar">
                <view class="flex items-center gap-[10rpx] shrink-0" @click="actions.toggleSelectAllCopy()">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="state.isAllCopySelected ? 'bg-primary border-primary' : 'bg-white border-[#D1D5DB]'">
                        <u-icon v-if="state.isAllCopySelected" name="checkbox-mark" color="#FFFFFF" size="24"></u-icon>
                    </view>
                    <text class="text-xs font-semibold text-[#374151]">全选</text>
                </view>
                <text class="text-xs text-[#9CA3AF]"> 已选 {{ state.selectedCopyCount }} 项 </text>
                <view class="flex items-center gap-[14rpx] ml-auto shrink-0">
                    <view
                        class="text-xs text-[#9CA3AF] bg-[#F4F6FA] border border-solid border-[#E8ECF0] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.cancelCopyBatch()">
                        取消
                    </view>
                    <view
                        class="text-xs text-white bg-[#EF4444] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.confirmCopyBatchDelete()">
                        删除所选
                    </view>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatAudioTime } from "@/utils/util";
import DigitalTip from "./digital-tip.vue";
import SliceProgress from "./slice-progress.vue";
import {
    MaterialTabEnum,
    publishModeBannerText,
    type PublishMode,
    type SliceStatistics,
} from "../hooks/useMaterialsTab";
import {
    CopyDriverTypeEnum,
    CopyLibraryTypeEnum,
    isNewsCopy,
    isPublishCopy,
    type CopyItem,
} from "../hooks/useCopyLibrary";

const props = defineProps<{
    state: {
        activeMaterialTab: MaterialTabEnum;
        batchDeleteMode: boolean;
        publishMode: PublishMode;
        materialPublishModes: readonly { value: PublishMode; title: string; desc: string }[];
        materialSubTabs: readonly { key: MaterialTabEnum; label: string }[];
        materialFilters: readonly { name: string; value: string | number }[];
        activeMaterialFilter: number;
        materialList: any[];
        materialLoading: boolean;
        materialFinished: boolean;
        hasOverusedMaterial: boolean;
        failedMaterialCount: number;
        hasSlicingMaterial: boolean;
        hasSlicingTask: boolean;
        sliceStatistics: SliceStatistics | null;
        avatars: any[];
        voices: any[];
        selectedMaterialCount: number;
        isAllMaterialSelected: boolean;
        isMaterialSelected: (id: string) => boolean;
        isMaterialSlicing: (item: any) => boolean;
        isMaterialSliceFailed: (item: any) => boolean;
        isCurrentPlaying: (voiceId: string) => boolean;
        isCurrentMusicPlaying: (musicId: string) => boolean;
        formatMaterialTime: (time: string) => string;
        musicList: any[];
        musicLoading: boolean;
        musicFinished: boolean;
        musicBatchMode: boolean;
        selectedMusicCount: number;
        isMusicSelected: (id: string) => boolean;
        isAllMusicSelected: boolean;
        copyTab: CopyLibraryTypeEnum;
        copyDriveType: CopyDriverTypeEnum;
        copyList: CopyItem[];
        copyLoading: boolean;
        copyTabs: readonly { key: CopyLibraryTypeEnum; label: string }[];
        copyDriveTypes: readonly { key: CopyDriverTypeEnum; label: string }[];
        copyBatchMode: boolean;
        selectedCopyCount: number;
        isCopySelected: (id: number) => boolean;
        isAllCopySelected: boolean;
    };
    actions: {
        switchMaterialTab: (tab: MaterialTabEnum) => void;
        switchPublishMode: (mode: PublishMode) => void;
        upload: () => void;
        uploadMusic: () => void;
        toggleMusicBatch: () => void;
        toggleMusicSelected: (id: string) => void;
        toggleSelectAllMusic: () => void;
        cancelMusicBatch: () => void;
        confirmMusicBatchDelete: () => void;
        materialFilter: (index: number) => void;
        toggleBatchDelete: () => void;
        toggleMaterialSelected: (id: string) => void;
        toggleSelectAll: () => void;
        cancelBatchDelete: () => void;
        confirmBatchDelete: () => void;
        deleteFailedSlices: () => void;
        previewMaterial: (item: any) => void;
        playMaterial: (item: any) => void;
        playMusic: (item: any) => void;
        moreMaterial: (item: any) => void;
        removeMaterial: (item: any) => Promise<void>;
        removeMusic: (item: any) => Promise<void>;
        removeAvatar: (item: any) => void;
        openVoiceForAvatar: (item: any) => void;
        addAvatar: () => void;
        playVoice: (item: any) => void;
        removeVoice: (item: any) => void;
        addVoice: () => void;
        switchCopyTab: (type: CopyLibraryTypeEnum) => void;
        switchCopyDriveType: (type: CopyDriverTypeEnum) => void;
        addCopy: () => void;
        editCopy: (item: CopyItem) => void;
        removeCopy: (item: CopyItem) => void;
        importCopy: () => void;
        aiGenerate: () => void;
        toggleCopyBatch: () => void;
        toggleCopySelected: (id: number) => void;
        toggleSelectAllCopy: () => void;
        cancelCopyBatch: () => void;
        confirmCopyBatchDelete: () => void;
    };
}>();

const bannerText = computed(() => publishModeBannerText[props.state.publishMode]);

const musicExtLabel = (url = ""): string => {
    const ext = url.split("?")[0]?.split(".").pop()?.toUpperCase() || "";
    return ["MP3", "WAV"].includes(ext) ? ext : "";
};

// 文案库：新闻体仅标题；发布文案含话题标签
const isCopyNews = computed(() => isNewsCopy(props.state.copyTab, props.state.copyDriveType));
const isCopyPublish = computed(() => isPublishCopy(props.state.copyTab));
const topicTags = (topic: string): string[] => (topic ? topic.split(/\s+/).filter(Boolean) : []);

/** 文案说明默认收起，避免与多层筛选抢首屏 */
const copyTipExpanded = ref(false);

const currentCopyDriveTypeLabel = computed(
    () => props.state.copyDriveTypes.find((item) => item.key === props.state.copyDriveType)?.label ?? "类型",
);

const handleCopyDriveTypePick = (): void => {
    const types = props.state.copyDriveTypes;
    uni.showActionSheet({
        itemList: types.map((item) => item.label),
        success: ({ tapIndex }) => {
            const picked = types[tapIndex];
            if (picked) props.actions.switchCopyDriveType(picked.key);
        },
    });
};

// 文案库次要操作收敛到「⋯管理」
const handleCopyManage = (): void => {
    uni.showActionSheet({
        itemList: ["批量导入", props.state.copyBatchMode ? "退出批量删除" : "批量删除"],
        success: ({ tapIndex }) => {
            if (tapIndex === 0) props.actions.importCopy();
            else if (tapIndex === 1) props.actions.toggleCopyBatch();
        },
    });
};
</script>

<style scoped lang="scss">
.detail-card-shadow {
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}

// 批量删除固定底部操作栏：贴底、处理安全区、横向铺满
.mat-batch-bar {
    @apply fixed left-0 right-0 bottom-0 z-50 flex items-center gap-[20rpx] px-[32rpx] pt-[22rpx];
    padding-bottom: calc(22rpx + env(safe-area-inset-bottom));
    background: #ffffff;
    box-shadow: 0 -8rpx 24rpx rgba(0, 0, 0, 0.06);
}

.mat-slice-spinner {
    width: 40rpx;
    height: 40rpx;
    border: 5rpx solid #c9dbfb;
    border-top-color: var(--color-primary, #0065fb);
    border-radius: 50%;
    animation: matSliceSpin 0.8s linear infinite;
}

@keyframes matSliceSpin {
    to {
        transform: rotate(360deg);
    }
}
</style>
