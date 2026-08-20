<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title="分镜混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="steps" :step="step" @step="handleStep" />
        </view>

        <view class="grow min-h-0 relative">
            <view v-show="step === 1" class="h-full flex flex-col">
                <view class="p-4 pt-2 space-y-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">镜头组素材</text>
                        </view>
                        <view
                            class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[24rpx] py-[10rpx]"
                            @click="handleEditMaterial()">
                            <u-icon name="plus" size="18" color="#0065fb" />
                            <text class="text-xs font-semibold text-primary">添加镜头</text>
                        </view>
                    </view>
                </view>

                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view v-if="formData.storyboardList.length > 0" class="px-4 flex flex-col gap-[16rpx] pb-4">
                            <view
                                v-for="(storyboard, index) in formData.storyboardList"
                                :key="index"
                                class="bg-white rounded-[24rpx] p-[24rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                @click="handleEditMaterial(index)">
                                <view class="flex items-center justify-between mb-[18rpx]">
                                    <view class="flex items-center gap-[10rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                            <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                        </view>
                                        <text class="text-[28rpx] font-bold text-[#0D1117] truncate flex-1">{{
                                            storyboard.groupName
                                        }}</text>
                                    </view>
                                    <view class="flex items-center gap-[6rpx]">
                                        <text class="text-primary font-bold">{{ storyboard.materialList.length }}</text>
                                        <text class="text-[#9CA3AF]">个</text>
                                        <u-icon name="arrow-right" color="#C0C4CC" size="20" />
                                    </view>
                                </view>
                                <view class="grid grid-cols-4 gap-[8rpx]">
                                    <view
                                        v-for="(value, valIndex) in storyboard.materialList"
                                        :key="valIndex"
                                        class="h-[156rpx] rounded-[12rpx] overflow-hidden">
                                        <image :src="value.pic" class="w-full h-full" mode="aspectFill" />
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between mt-[20rpx] pt-[16rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                    <view
                                        class="flex items-center gap-[8rpx]"
                                        @click.stop="handleDeleteStoryboard(index)">
                                        <view
                                            class="w-[36rpx] h-[36rpx] rounded-full bg-[#FFF1F2] flex items-center justify-center">
                                            <u-icon name="trash" color="#F56C6C" size="16" />
                                        </view>
                                        <text class="text-xs text-[#F56C6C]">删除</text>
                                    </view>
                                    <view class="flex items-center gap-[12rpx]" @click.stop>
                                        <text class="text-xs text-[#4B5563] font-medium">素材原声</text>
                                        <u-switch v-model="storyboard.is_use" :size="32" />
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view v-else class="flex flex-col items-center justify-center h-full px-8 py-[80rpx]">
                            <view class="relative mb-[48rpx]">
                                <view
                                    class="w-[280rpx] h-[280rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                    <view
                                        class="w-[200rpx] h-[200rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center">
                                        <view
                                            class="w-[120rpx] h-[120rpx] rounded-[32rpx] flex items-center justify-center shadow-[0_8rpx_24rpx_rgba(0,101,251,0.25)]"
                                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                            <u-icon name="photo" color="#fff" size="44" />
                                        </view>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-[8rpx] right-[-12rpx] w-[44rpx] h-[44rpx] rounded-full bg-[#F0FDF4] shadow-[0_4rpx_12rpx_rgba(0,0,0,0.06)] flex items-center justify-center">
                                    <u-icon name="grid" color="#16A34A" size="18" />
                                </view>

                                <view
                                    class="absolute bottom-[8rpx] left-[-12rpx] w-[56rpx] h-[56rpx] rounded-full bg-[#FEF9C3] shadow-[0_4rpx_12rpx_rgba(0,0,0,0.08)] flex items-center justify-center">
                                    <u-icon name="play-right-fill" color="#D97706" size="22" />
                                </view>
                            </view>

                            <text class="text-[34rpx] font-extrabold text-[#0D1117] mb-[16rpx]">还没有镜头组素材</text>
                            <text class="text-[#9CA3AF] text-center leading-relaxed mb-[64rpx]">
                                添加镜头组，AI 将按分镜顺序自动混剪生成视频
                            </text>

                            <view
                                class="flex items-center gap-[10rpx] h-[96rpx] px-[64rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click="handleEditMaterial()">
                                <u-icon name="plus" size="24" color="#fff" />
                                <text class="text-[30rpx] font-extrabold text-white">添加镜头</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view v-show="step === 2" class="h-full flex flex-col">
                <view class="p-4 pt-2 space-y-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">字幕文案</text>
                        </view>
                        <text class="text-xs text-[#9CA3AF]">
                            共
                            <text class="text-primary font-bold">
                                {{
                                    copywriterTypeIndex === 0
                                        ? formData.copywriterList.length
                                        : formData.subtitleList.length
                                }}
                            </text>
                            条
                        </text>
                    </view>
                    <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx]">
                        <view
                            v-for="(item, index) in ['按顺序文案', '镜头匹配文案']"
                            :key="index"
                            class="flex-1 h-[72rpx] rounded-[16rpx] flex items-center justify-center font-semibold transition-all duration-200"
                            :class="
                                copywriterTypeIndex === index
                                    ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                    : 'text-[#9CA3AF]'
                            "
                            @click="copywriterTypeIndex = index">
                            {{ item }}
                        </view>
                    </view>
                    <view v-if="copywriterTypeIndex === 0" class="flex gap-[12rpx] !my-4">
                        <view
                            class="flex-1 flex items-center justify-center gap-[10rpx] h-[96rpx] rounded-[24rpx] bg-white border border-solid border-[#E5E9F0] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]"
                            @click="handleShowCopywriter()">
                            <u-icon name="edit-pen" color="#4B5563" size="22" />
                            <text class="text-[28rpx] font-bold text-[#0D1117]">手动输入</text>
                        </view>
                        <view
                            class="flex-1 h-[96rpx] flex items-center justify-center gap-[10rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                            @click="showChooseAgent = true">
                            <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]" />
                            <text class="text-[28rpx] font-bold text-white">AI 智能生成</text>
                        </view>
                    </view>
                    <view
                        v-if="copywriterTypeIndex === 1"
                        class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                        <u-icon name="info-circle" color="#0065fb" size="20" />
                        <text class="text-xs text-primary font-medium">每个镜头组有多条字幕，则随机匹配1条</text>
                    </view>
                </view>

                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-[16rpx] pb-4">
                            <template v-if="copywriterTypeIndex === 0">
                                <view
                                    v-for="(item, index) in formData.copywriterList"
                                    :key="index"
                                    class="relative bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                    :class="
                                        !isSingleCopywriterValid(item) ? 'border border-solid border-[#F56C6C]' : ''
                                    "
                                    @click="handleSelectCopywriter(index)">
                                    <view class="absolute left-0 top-0 w-[6rpx] h-full bg-primary rounded-l-[24rpx]" />
                                    <view class="pl-[32rpx] pr-[24rpx] pt-[22rpx] pb-[18rpx]">
                                        <view class="flex items-center gap-[12rpx] mb-[18rpx]">
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                            </view>
                                            <text class="flex-1 text-[28rpx] font-bold text-[#0D1117] truncate"
                                                >文案 {{ index + 1 }}</text
                                            >
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                                @click.stop="handleDeleteCopywriter(index)">
                                                <u-icon name="close" color="#9CA3AF" size="14" />
                                            </view>
                                        </view>
                                        <text class="text-[#4B5563] leading-relaxed whitespace-pre-line">{{
                                            item
                                        }}</text>
                                        <view
                                            class="flex items-center justify-between mt-[14rpx] pt-[20rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                            <view
                                                class="flex items-center gap-[6rpx]"
                                                v-if="!isSingleCopywriterValid(item)">
                                                <u-icon name="info-circle-fill" color="#F56C6C" size="14" />
                                                <text class="text-[22rpx] text-[#F56C6C]">
                                                    {{
                                                        item.length > STORYBOARD_COPYWRITER_LIMIT
                                                            ? `超出${STORYBOARD_COPYWRITER_LIMIT}字限制`
                                                            : "不能少于3个字"
                                                    }}
                                                </text>
                                            </view>
                                            <view v-else />
                                            <text
                                                class="text-[22rpx]"
                                                :class="
                                                    item.length > STORYBOARD_COPYWRITER_LIMIT
                                                        ? 'text-[#F56C6C] font-bold'
                                                        : 'text-[#C0C4CC]'
                                                ">
                                                {{ item.length }} /
                                                {{ STORYBOARD_COPYWRITER_LIMIT }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </template>

                            <template v-if="copywriterTypeIndex === 1">
                                <view
                                    v-for="(item, index) in formData.subtitleList"
                                    :key="index"
                                    class="bg-white rounded-[24rpx] p-[24rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <view class="flex items-center gap-[10rpx]">
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                            </view>
                                            <text class="text-[28rpx] font-bold text-[#0D1117]">{{ item.title }}</text>
                                        </view>
                                        <view
                                            class="flex items-center gap-[6rpx] bg-primary px-[20rpx] py-[10rpx] rounded-full"
                                            @click.stop="handleAddSubtitleContent(index)">
                                            <u-icon name="plus" size="14" color="#ffffff" />
                                            <text class="text-white text-[22rpx] font-semibold">添加文案</text>
                                        </view>
                                    </view>
                                    <view v-if="item.contentList.length > 0" class="flex flex-col gap-[10rpx]">
                                        <view
                                            v-for="(content, contentIndex) in item.contentList"
                                            :key="contentIndex"
                                            class="relative rounded-[16rpx] px-[20rpx] py-[16rpx] pr-[60rpx]"
                                            :class="
                                                !isSingleSubtitleValid(content)
                                                    ? 'bg-[#FFF1F2] border border-solid border-[#F56C6C]'
                                                    : 'bg-[#F7F9FC]'
                                            "
                                            @click="handleSelectCopywriter(index, contentIndex)">
                                            <text class="text-[#4B5563] leading-relaxed">{{ content }}</text>
                                            <view
                                                class="absolute right-[16rpx] top-[16rpx] w-[36rpx] h-[36rpx] rounded-full bg-[#E5E7EB] flex items-center justify-center"
                                                @click.stop="handleDeleteCopywriter(index, contentIndex)">
                                                <u-icon name="close" color="#9CA3AF" size="14" />
                                            </view>
                                            <view class="flex items-center justify-between mt-[10rpx]">
                                                <view
                                                    class="flex items-center gap-[6rpx]"
                                                    v-if="!isSingleSubtitleValid(content)">
                                                    <u-icon name="info-circle-fill" color="#F56C6C" size="14" />
                                                    <text class="text-[22rpx] text-[#F56C6C]">
                                                        {{
                                                            content.length > STORYBOARD_SUBTITLE_LIMIT
                                                                ? `超出${STORYBOARD_SUBTITLE_LIMIT}字限制`
                                                                : "不能少于3个字"
                                                        }}
                                                    </text>
                                                </view>
                                                <view v-else />
                                                <text
                                                    class="text-[22rpx]"
                                                    :class="
                                                        content.length > STORYBOARD_SUBTITLE_LIMIT
                                                            ? 'text-[#F56C6C] font-bold'
                                                            : 'text-[#C0C4CC]'
                                                    ">
                                                    {{ content.length }} /
                                                    {{ STORYBOARD_SUBTITLE_LIMIT }}
                                                </text>
                                            </view>
                                        </view>
                                    </view>
                                    <view v-else class="flex items-center justify-center py-[24rpx]">
                                        <text class="text-xs text-[#9CA3AF]">
                                            点击右上角
                                            <text class="text-primary font-semibold">添加文案</text>
                                            按钮添加字幕内容
                                        </text>
                                    </view>
                                </view>
                            </template>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view v-show="step === 3" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="p-4 pt-2 space-y-3">
                        <view class="flex items-center h-[80rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[32rpx] font-extrabold text-[#0D1117]">生成设置</text>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">视频名称</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.name"
                                        maxlength="50"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="请输入视频名称" />
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">内容汇总</text>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(1)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">分镜素材</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.storyboardList.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(2)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">字幕文案</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{
                                        formData.copywriterList.length || formData.subtitleList.length
                                    }}</text>
                                    <text class="text-[#9CA3AF]">条</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/montage_storyboard_title/montage_storyboard_title?data=${JSON.stringify(
                                    formData.topTitleList,
                                )}`"
                                hover-class="none"
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">视频顶部标题</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.topTitleList.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </navigator>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/music_choose/music_choose?music=${JSON.stringify(
                                    formData.music,
                                )}&volume=${formData.extra.volume}&is_ai=0`"
                                hover-class="none"
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">背景音乐</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.music.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </navigator>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="openChooseTone()">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">选择音色</text>
                                <view class="flex items-center gap-[6rpx]">
                                    <view
                                        v-if="!formData.voiceValue.name"
                                        class="bg-[#EBF2FF] px-[14rpx] py-[6rpx] rounded-[8rpx]">
                                        <text class="text-[22rpx] text-[#9CA3AF] font-semibold">请选择音色</text>
                                    </view>
                                    <text v-else class="text-primary font-semibold">{{
                                        formData.voiceValue.name
                                    }}</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center justify-between shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view>
                                <text class="text-[28rpx] font-bold text-[#0D1117] block mb-[6rpx]">生成视频数量</text>
                                <text class="text-xs text-[#9CA3AF]">每组镜头生成视频的数量</text>
                            </view>
                            <view class="flex items-center gap-[16rpx]">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleVideoCount('minus')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                                </view>
                                <view
                                    class="w-[72rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                                    <u-input
                                        v-model="formData.extra.video_count"
                                        type="digit"
                                        placeholder=""
                                        :custom-style="{
                                            color: '#0065fb',
                                            fontWeight: '800',
                                            fontSize: '30rpx',
                                        }"
                                        input-align="center" />
                                </view>
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleVideoCount('add')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">使用设置</text>
                            </view>
                            <view class="flex items-center justify-between px-[28rpx] h-[104rpx]">
                                <text class="text-[28rpx] font-semibold text-[#0D1117]">背景音乐使用</text>
                                <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] w-[240rpx]">
                                    <view
                                        v-for="(item, index) in ORDER_MODE_TABS"
                                        :key="index"
                                        class="flex-1 h-[56rpx] rounded-[12rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                        :class="
                                            index === formData.extra.music
                                                ? 'bg-white text-primary shadow-[0_2rpx_6rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="formData.extra.music = index">
                                        {{ item }}
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="h-[20rpx]" />
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <view
                v-if="step === 1"
                class="w-[100rpx] h-[96rpx] rounded-[20rpx] flex flex-col items-center justify-center transition-all duration-300"
                :class="formData.storyboardList.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                <text
                    class="text-[32rpx] font-extrabold leading-none"
                    :class="formData.storyboardList.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                    {{ formData.storyboardList.length }}
                </text>
                <text
                    class="text-[20rpx] mt-[4rpx]"
                    :class="formData.storyboardList.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'"
                    >已添加</text
                >
            </view>
            <view
                v-else-if="step < steps.length"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(step, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <view v-else class="flex flex-col items-center gap-[6rpx] px-[16rpx]" @click="showTokensCost = true">
                <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]" />
                <text class="text-[20rpx] text-[#9CA3AF] font-medium">算力消耗</text>
            </view>

            <view
                v-if="step < steps.length"
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] transition-all duration-300"
                :class="canNext ? 'bg-primary shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : 'bg-[#E5E7EB]'"
                @click="handleStep(step, 'next')">
                <text class="text-[30rpx] font-bold" :class="canNext ? 'text-white' : 'text-[#9CA3AF]'">下一步</text>
            </view>
            <view
                v-else
                class="flex-1 h-[100rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleCreateVideo">
                <text class="text-[32rpx] font-extrabold text-white tracking-wide">
                    立即生成（{{ formData.extra.video_count }}个）
                </text>
            </view>
        </view>

        <choose-agent v-if="showChooseAgent" v-model="showChooseAgent" @select="handleSelectAgent" />
        <choose-tone
            ref="chooseToneRef"
            v-model="showChooseTone"
            :limit="1"
            :type="2"
            :model-version="DigitalHumanModelVersionEnum.MINIMAX_HD"
            :show-user-tone="copywriterTypeIndex === 0"
            :show-original-tone="false"
            @select="handleSelectTone" />
        <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="MontageTypeEnum.STORYBOARD_MIX" />
        <create-success-pop
            v-if="showCreateSuccess"
            v-model="showCreateSuccess"
            title="视频生成中"
            desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
            @to="toPublish"
            @seek="toRecord" />
        <recharge-popup ref="rechargePopupRef" />
    </view>
</template>

<script setup lang="ts">
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import Steps from "@/ai_modules/digital_human/components/steps/steps.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import ChooseTone from "@/ai_modules/digital_human/components/choose-tone/choose-tone.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";

// ─── Hooks ──────────────────────────────────────────────────
import { useStoryboardSteps, STORYBOARD_COPYWRITER_LIMIT, STORYBOARD_SUBTITLE_LIMIT } from "./hooks/useSteps";
import { useStoryboardCopywriter } from "./hooks/useCopywriter";
import { useMaterialGroup } from "./hooks/useMaterialGroup";
import { useGenerateSetting, ORDER_MODE_TABS } from "./hooks/useGenerateSetting";

const { on } = useEventBusManager();

// ────────────────────────────────────────────────
// 表单数据
// ────────────────────────────────────────────────
const formData = reactive<{
    name: string;
    storyboardList: {
        is_use: boolean;
        groupName: string;
        materialList: any[];
    }[];
    copywriterList: any[];
    subtitleList: {
        title: string;
        contentList: any[];
    }[];
    topTitleList: any[];
    music: any[];
    clip: any[];
    extra: {
        volume: number;
        soundSwitch: boolean;
        human: number;
        music: number;
        clip: number;
        video_count: number;
    };
    voiceValue: any;
}>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "分镜混剪",
    storyboardList: [],
    copywriterList: [],
    subtitleList: [],
    topTitleList: [],
    music: [],
    clip: [],
    extra: {
        volume: 0.2,
        soundSwitch: false,
        human: 0,
        music: 0,
        clip: 0,
        video_count: 1,
    },
    voiceValue: {},
});

// ─── Refs ─────────────────────────────────────────────────────────
const rechargePopupRef = shallowRef();

// 文案类型切换（按顺序 / 镜头匹配），需传入两个 composable
const copywriterTypeIndex = ref(0);

// ────────────────────────────────────────────────
// 独立步骤逻辑
// ────────────────────────────────────────────────
const { steps, step, canNext, handleStep, isSingleCopywriterValid, isSingleSubtitleValid } = useStoryboardSteps(
    formData,
    copywriterTypeIndex,
);

// ────────────────────────────────────────────────
// 独立文案逻辑
// ────────────────────────────────────────────────
const {
    showChooseAgent,
    handleShowCopywriter,
    handleSelectCopywriter,
    handleDeleteCopywriter,
    handleAddSubtitleContent,
    handleSelectAgent,
    onCopywriterConfirm,
} = useStoryboardCopywriter(formData, copywriterTypeIndex);

// ────────────────────────────────────────────────
// 素材
// ────────────────────────────────────────────────
const { editMaterialIndex, syncSubtitleList, handleEditMaterial, handleDeleteStoryboard } = useMaterialGroup({
    formData,
});

// ────────────────────────────────────────────────
// 生成视频
// ────────────────────────────────────────────────

const {
    chooseToneRef,
    showChooseTone,
    showTokensCost,
    showCreateSuccess,
    openChooseTone,
    handleSelectTone,
    handleVideoCount,
    handleCreateVideo,
    toPublish,
    toRecord,
} = useGenerateSetting({
    formData,
    copywriterTypeIndex,
    rechargePopupRef,
});

// ────────────────────────────────────────────────
// 事件总线监听
// ────────────────────────────────────────────────
onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;

        if (type === ListenerTypeEnum.AI_COPYWRITER || type === ListenerTypeEnum.SZR_COPYWRITER) {
            onCopywriterConfirm(type, data);
            return;
        }

        // 素材组回调
        if (type === ListenerTypeEnum.MONTAGE_MATERIAL_GROUP) {
            if (editMaterialIndex.value !== -1) {
                if (data.length === 0) {
                    formData.storyboardList.splice(editMaterialIndex.value, 1);
                } else {
                    formData.storyboardList[editMaterialIndex.value].materialList = data;
                }
                editMaterialIndex.value = -1;
            } else {
                if (data.length > 0) {
                    formData.storyboardList.push({
                        is_use: true,
                        groupName: `镜头组${formData.storyboardList.length + 1}`,
                        materialList: data,
                    });
                }
            }
            syncSubtitleList();
        }

        // 顶部标题回调
        if (type === ListenerTypeEnum.MONTAGE_TOP_TITLE) {
            formData.topTitleList = data;
        }

        // 背景音乐回调
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            formData.music = data.music;
            formData.extra.volume = data.volume;
        }
    });
});
</script>

<style scoped lang="scss">
.type-item {
    @apply flex flex-col items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500 text-xs;
    &.active {
        @apply text-primary font-medium relative;
    }
}
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[6rpx] left-0 transition-all duration-500;
}
.copywriter-item {
    @apply whitespace-pre-line relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
    &--error {
        @apply border border-solid border-error bg-[#f56c6c]/50;
    }
}
.subtitle-content--error {
    @apply bg-[#f56c6c]/50 border border-solid border-error;
}
</style>
