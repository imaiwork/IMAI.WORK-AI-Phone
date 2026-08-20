<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="一句话生成视频"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }" />

        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-[24rpx] pt-[20rpx] pb-[160rpx] flex flex-col gap-[16rpx]">
                    <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                        <view class="flex bg-[#F0F2F5] mx-[24rpx] mt-[24rpx] p-[6rpx] rounded-[16rpx] gap-[6rpx]">
                            <view
                                v-for="(tab, i) in tabs"
                                :key="i"
                                class="flex-1 h-[72rpx] flex items-center justify-center rounded-[12rpx] transition-all"
                                :class="activeTab === i ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]' : ''"
                                @click="activeTab = i">
                                <text
                                    class="font-semibold"
                                    :class="activeTab === i ? 'text-primary' : 'text-[#9CA3AF]'">
                                    {{ tab }}
                                </text>
                            </view>
                        </view>

                        <view
                            class="mx-[24rpx] mt-[20rpx] bg-[#F7F9FC] rounded-[20rpx] px-[20rpx] pt-[20rpx] pb-[16rpx] relative">
                            <u-input
                                ref="inputContentRef"
                                class="w-full"
                                v-model="formData.content"
                                type="textarea"
                                height="200"
                                placeholder-style="font-size:26rpx;line-height:40rpx;color:#C0C4CC;"
                                confirm-type="done"
                                :disable-default-padding="true"
                                :show-confirm-bar="false"
                                :adjust-position="false"
                                :auto-height="false"
                                :maxlength="MAX_DESC_LENGTH"
                                placeholder="描述你想要生成的视频画面... (支持纯文本、纯素材或混合输入)" />
                            <view class="flex items-center justify-between mt-[16rpx]">
                                <text class="text-[22rpx] text-[#C0C4CC]"
                                    >{{ formData.content.length }}/{{ MAX_DESC_LENGTH }}</text
                                >
                                <view class="flex items-center gap-x-[16rpx]">
                                    <view
                                        class="flex items-center gap-x-[10rpx] h-[64rpx] px-[24rpx] rounded-[16rpx] bg-[#F0F2F5]"
                                        @click="formData.content = ''">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/clear.svg"
                                            class="w-[24rpx] h-[24rpx]" />
                                        <text class="text-[#9CA3AF] text-xs">清除</text>
                                    </view>
                                    <view
                                        class="w-[60rpx] h-[60rpx] rounded-[14rpx] bg-white shadow-sm flex items-center justify-center border border-solid border-[#E5E9F0]"
                                        @click="showChooseAgent = true">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/star_primary.svg"
                                            class="w-[30rpx] h-[30rpx]" />
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view v-if="activeTab === 0" class="mx-[24rpx] mb-[24rpx] mt-4">
                            <view
                                class="flex items-start gap-[12rpx] bg-[#FFFBEB] rounded-[16rpx] px-[20rpx] py-[16rpx] mb-[16rpx] border border-solid border-[#FDE68A]">
                                <u-icon
                                    name="info-circle-fill"
                                    color="#F59E0B"
                                    size="28"
                                    class="flex-shrink-0 mt-[2rpx]" />
                                <text class="text-[22rpx] text-[#92400E] leading-relaxed flex-1">
                                    请勿上传含有<text class="font-extrabold text-[#D97706]">真实人脸</text
                                    >的素材，包括真人照片、真人视频等，以免影响生成效果或违反平台规定。
                                </text>
                            </view>

                            <view v-if="formData.materialList.length === 0" class="flex gap-[16rpx]">
                                <view
                                    class="flex-1 h-[144rpx] rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[8rpx]"
                                    @click="openPanel('material', 'image')">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/pic_primary.svg"
                                        class="w-[36rpx] h-[36rpx]" />
                                    <text class="text-primary text-xs font-semibold">添加参考图</text>
                                    <text class="text-[#9CA3AF] text-[20rpx]">最多9张</text>
                                </view>
                                <view
                                    class="flex-1 h-[144rpx] rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[8rpx]"
                                    @click="openPanel('material', 'video')">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/video_primary.svg"
                                        class="w-[36rpx] h-[36rpx]" />
                                    <text class="text-primary text-xs font-semibold">添加参考视频</text>
                                    <text class="text-[#9CA3AF] text-[20rpx]">最多3个</text>
                                </view>
                            </view>

                            <view v-else>
                                <view class="flex items-center justify-between mb-[16rpx]">
                                    <view class="flex items-center gap-[10rpx]">
                                        <view class="w-[8rpx] h-[8rpx] rounded-full bg-primary" />
                                        <text class="text-xs text-[#374151] font-semibold">已上传</text>
                                        <view
                                            class="h-[36rpx] px-[14rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                            <text class="text-[22rpx] text-primary font-bold"
                                                >{{ formData.materialList.length }} 个</text
                                            >
                                        </view>
                                    </view>
                                    <view
                                        class="flex items-center gap-[8rpx] h-[52rpx] px-[20rpx] rounded-[14rpx] bg-[#FFF1F2] border border-solid border-[#FECDD3]"
                                        @click="handleClearAllMaterials">
                                        <u-icon name="trash" color="#F43F5E" size="22" />
                                        <text class="text-[22rpx] text-[#F43F5E] font-semibold">清除全部</text>
                                    </view>
                                </view>

                                <view
                                    v-if="videoMaterials.length > 0"
                                    class="mb-[16rpx] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid"
                                    :class="
                                        totalVideoDuration > VIDEO_DURATION_LIMIT
                                            ? 'bg-[#FFF1F2] border-[#FECDD3]'
                                            : totalVideoDuration >= VIDEO_DURATION_WARN
                                            ? 'bg-[#FFFBEB] border-[#FDE68A]'
                                            : 'bg-[#F0F6FF] border-[#BFDBFE]'
                                    ">
                                    <view class="flex items-center justify-between mb-[12rpx]">
                                        <view class="flex items-center gap-[8rpx]">
                                            <u-icon
                                                name="play-right"
                                                :color="
                                                    totalVideoDuration > VIDEO_DURATION_LIMIT
                                                        ? '#F43F5E'
                                                        : totalVideoDuration >= VIDEO_DURATION_WARN
                                                        ? '#F59E0B'
                                                        : '#0065fb'
                                                "
                                                size="22" />
                                            <text
                                                class="text-xs font-semibold"
                                                :class="
                                                    totalVideoDuration > VIDEO_DURATION_LIMIT
                                                        ? 'text-[#F43F5E]'
                                                        : totalVideoDuration >= VIDEO_DURATION_WARN
                                                        ? 'text-[#F59E0B]'
                                                        : 'text-primary'
                                                ">
                                                视频总时长
                                            </text>
                                        </view>
                                        <view class="flex items-center gap-[6rpx]">
                                            <text
                                                class="font-extrabold"
                                                :class="
                                                    totalVideoDuration > VIDEO_DURATION_LIMIT
                                                        ? 'text-[#F43F5E]'
                                                        : totalVideoDuration >= VIDEO_DURATION_WARN
                                                        ? 'text-[#F59E0B]'
                                                        : 'text-primary'
                                                ">
                                                {{ totalVideoDuration.toFixed(1) }}s
                                            </text>
                                            <text class="text-[22rpx] text-[#9CA3AF]"
                                                >/ {{ VIDEO_DURATION_LIMIT }}s</text
                                            >
                                        </view>
                                    </view>
                                    <view class="h-[8rpx] bg-[#E5E9F0] rounded-full overflow-hidden">
                                        <view
                                            class="h-full rounded-full transition-all"
                                            :class="
                                                totalVideoDuration > VIDEO_DURATION_LIMIT
                                                    ? 'bg-[#F43F5E]'
                                                    : totalVideoDuration >= VIDEO_DURATION_WARN
                                                    ? 'bg-[#F59E0B]'
                                                    : 'bg-primary'
                                            "
                                            :style="`width:${Math.min(
                                                (totalVideoDuration / VIDEO_DURATION_LIMIT) * 100,
                                                100,
                                            )}%`" />
                                    </view>
                                    <view class="flex items-center justify-between mt-[10rpx]">
                                        <text
                                            class="text-[20rpx]"
                                            :class="
                                                totalVideoDuration > VIDEO_DURATION_LIMIT
                                                    ? 'text-[#F43F5E]'
                                                    : 'text-[#9CA3AF]'
                                            ">
                                            {{
                                                totalVideoDuration > VIDEO_DURATION_LIMIT
                                                    ? "⚠ 已超出限制，请删除部分视频"
                                                    : `单个视频 2~15s，总计不超过 ${VIDEO_DURATION_LIMIT}s`
                                            }}
                                        </text>
                                        <text
                                            class="text-[20rpx] font-semibold"
                                            :class="
                                                remainVideoDuration <= 0
                                                    ? 'text-[#F43F5E]'
                                                    : remainVideoDuration <= 3
                                                    ? 'text-[#F59E0B]'
                                                    : 'text-[#9CA3AF]'
                                            ">
                                            {{
                                                remainVideoDuration > 0
                                                    ? `还可上传 ${remainVideoDuration.toFixed(1)}s`
                                                    : "已达上限"
                                            }}
                                        </text>
                                    </view>
                                </view>

                                <scroll-view scroll-x class="whitespace-nowrap">
                                    <view class="flex gap-[16rpx] py-[4rpx]">
                                        <view
                                            v-for="(item, index) in formData.materialList"
                                            :key="index"
                                            class="relative w-[144rpx] h-[144rpx] flex-shrink-0 rounded-[16rpx] overflow-hidden border border-solid border-[#E5E9F0] shadow-sm"
                                            @click="previewMaterial(item)">
                                            <image :src="item.pic" class="w-full h-full" mode="aspectFill" />
                                            <view
                                                v-if="item.type === 'video' && item.duration"
                                                class="absolute top-[8rpx] left-[8rpx] bg-[#000000]/50 rounded-[6rpx] px-[8rpx] py-[3rpx] z-10">
                                                <text class="text-[18rpx] text-white font-medium"
                                                    >{{ Number(item.duration).toFixed(1) }}s</text
                                                >
                                            </view>
                                            <view
                                                class="absolute top-[8rpx] right-[8rpx] bg-[#000000]/50 rounded-full w-[36rpx] h-[36rpx] flex items-center justify-center z-10"
                                                @click.stop="handleDeleteMaterial(index)">
                                                <u-icon name="close" color="#ffffff" size="16" />
                                            </view>
                                            <view
                                                class="absolute bottom-[8rpx] left-0 right-0 flex justify-center z-10">
                                                <view
                                                    class="px-[16rpx] py-[4rpx] text-white text-[20rpx] rounded-full border border-solid border-[#ffffff]/30 bg-[#000000]/30"
                                                    @click.stop="handleReplaceMaterial(index)">
                                                    替换
                                                </view>
                                            </view>
                                        </view>
                                        <view
                                            class="w-[144rpx] h-[144rpx] flex-shrink-0 rounded-[16rpx] border-2 border-dashed border-[#E5E9F0] bg-[#F7F9FC] flex flex-col items-center justify-center gap-[8rpx]"
                                            @click="openPanel('material', 'image')">
                                            <u-icon name="plus" color="#C0C4CC" size="32" />
                                            <text class="text-[20rpx] text-[#C0C4CC]">继续添加</text>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                        </view>

                        <view v-if="activeTab === 1" class="mx-[24rpx] mb-[24rpx] mt-4">
                            <view
                                class="flex items-start gap-[12rpx] bg-[#FFFBEB] rounded-[16rpx] px-[20rpx] py-[16rpx] mb-[16rpx] border border-solid border-[#FDE68A]">
                                <u-icon
                                    name="info-circle-fill"
                                    color="#F59E0B"
                                    size="28"
                                    class="flex-shrink-0 mt-[2rpx]" />
                                <text class="text-[22rpx] text-[#92400E] leading-relaxed flex-1">
                                    请勿上传含有<text class="font-extrabold text-[#D97706]">真实人脸</text
                                    >的图片，以免影响生成效果或违反平台规定。
                                </text>
                            </view>

                            <view class="flex items-center gap-[16rpx]">
                                <view class="flex-1 relative h-[176rpx]">
                                    <view
                                        v-if="!firstFrame"
                                        class="w-full h-full rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[8rpx] relative overflow-hidden"
                                        @click="openPanel('firstFrame', 'image')">
                                        <view
                                            class="absolute top-0 left-0 bg-[#0065fb]/10 px-[16rpx] h-[40rpx] flex items-center rounded-br-[14rpx]">
                                            <text class="text-primary text-[20rpx] font-bold">首帧</text>
                                        </view>
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/pic_primary.svg"
                                            class="w-[36rpx] h-[36rpx]" />
                                        <text class="text-primary text-xs font-semibold mt-[4rpx]">上传起点</text>
                                        <text class="text-[#9CA3AF] text-[20rpx]">(可选)</text>
                                    </view>
                                    <view
                                        v-else
                                        class="w-full h-full rounded-[20rpx] overflow-hidden border border-solid border-[#E5E9F0] relative shadow-sm">
                                        <view
                                            class="absolute top-0 left-0 bg-primary px-[16rpx] h-[40rpx] flex items-center rounded-br-[14rpx] z-10 shadow-sm">
                                            <text class="text-white text-[20rpx] font-bold">首帧</text>
                                        </view>
                                        <image :src="firstFrame.pic" class="w-full h-full" mode="aspectFill" />
                                        <view
                                            class="absolute top-[8rpx] right-[8rpx] bg-[#000000]/50 rounded-full w-[40rpx] h-[40rpx] flex items-center justify-center z-10"
                                            @click="firstFrame = null">
                                            <u-icon name="trash" color="#ffffff" size="18" />
                                        </view>
                                    </view>
                                </view>
                                <view class="flex-shrink-0">
                                    <u-icon name="arrow-right" color="#C0C4CC" size="28" />
                                </view>
                                <view class="flex-1 relative h-[176rpx]">
                                    <view
                                        v-if="!lastFrame"
                                        class="w-full h-full rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[8rpx] relative overflow-hidden"
                                        @click="openPanel('lastFrame', 'image')">
                                        <view
                                            class="absolute top-0 right-0 bg-[#0065fb]/10 px-[16rpx] h-[40rpx] flex items-center rounded-bl-[14rpx]">
                                            <text class="text-primary text-[20rpx] font-bold">尾帧</text>
                                        </view>
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/pic_primary.svg"
                                            class="w-[36rpx] h-[36rpx]" />
                                        <text class="text-primary text-xs font-semibold mt-[4rpx]">上传终点</text>
                                        <text class="text-[#9CA3AF] text-[20rpx]">(可选)</text>
                                    </view>
                                    <view
                                        v-else
                                        class="w-full h-full rounded-[20rpx] overflow-hidden border border-solid border-[#E5E9F0] relative shadow-sm">
                                        <view
                                            class="absolute top-0 right-0 bg-primary px-[16rpx] h-[40rpx] flex items-center rounded-bl-[14rpx] z-10 shadow-sm">
                                            <text class="text-white text-[20rpx] font-bold">尾帧</text>
                                        </view>
                                        <image :src="lastFrame.pic" class="w-full h-full" mode="aspectFill" />
                                        <view
                                            class="absolute top-[8rpx] right-[8rpx] bg-[#000000]/50 rounded-full w-[40rpx] h-[40rpx] flex items-center justify-center z-10"
                                            @click="lastFrame = null">
                                            <u-icon name="trash" color="#ffffff" size="18" />
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                        <view
                            class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">参数设置</text>
                            </view>
                            <view
                                class="flex items-center gap-[8rpx] h-[52rpx] px-[20rpx] rounded-[14rpx] bg-[#F0F6FF]"
                                @click="showMaterialRule = true">
                                <u-icon name="info-circle" color="#0065fb" size="22" />
                                <text class="text-primary text-[22rpx] font-medium">查看素材规则</text>
                            </view>
                        </view>
                        <view class="px-[28rpx] py-[28rpx] flex flex-col gap-[36rpx]">
                            <view>
                                <text class="font-semibold text-[#374151] block mb-[20rpx]">画面比例</text>
                                <scroll-view scroll-x class="whitespace-nowrap">
                                    <view class="flex gap-[16rpx] py-[4rpx]">
                                        <view
                                            v-for="(item, index) in VIDEO_PROPORTIONS"
                                            :key="index"
                                            class="flex-shrink-0 flex flex-col items-center gap-[12rpx] px-[20rpx] py-[16rpx] rounded-[20rpx] border border-solid transition-all"
                                            :class="
                                                formData.aspect_ratio === item.value
                                                    ? 'border-primary bg-[#EBF2FF]'
                                                    : 'border-[#E5E9F0] bg-[#F7F9FC]'
                                            "
                                            @click="formData.aspect_ratio = item.value">
                                            <view class="flex items-center justify-center w-[56rpx] h-[56rpx]">
                                                <view
                                                    class="rounded-[4rpx] border-[3rpx] border-solid transition-all"
                                                    :class="
                                                        formData.aspect_ratio === item.value
                                                            ? 'border-primary'
                                                            : 'border-[#9CA3AF]'
                                                    "
                                                    :style="getRatioIconStyle(item.value)" />
                                            </view>
                                            <text
                                                class="text-[22rpx] font-semibold"
                                                :class="
                                                    formData.aspect_ratio === item.value
                                                        ? 'text-primary'
                                                        : 'text-[#6B7280]'
                                                ">
                                                {{ item.label }}
                                            </text>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                            <view>
                                <text class="font-semibold text-[#374151]">视频分辨率</text>
                                <view class="flex gap-[12rpx] mt-[16rpx]">
                                    <view
                                        v-for="item in VIDEO_RESOLUTIONS"
                                        :key="item.value"
                                        class="h-[72rpx] px-[40rpx] flex items-center justify-center rounded-[14rpx] transition-all"
                                        :class="
                                            formData.resolution === item.value
                                                ? 'bg-primary text-white shadow-[0_4rpx_12rpx_rgba(0,101,251,0.30)]'
                                                : 'bg-[#F0F2F5] text-[#4B5563]'
                                        "
                                        @click="formData.resolution = item.value">
                                        <text class="font-semibold">{{ item.label }}</text>
                                    </view>
                                </view>
                            </view>
                            <view>
                                <view class="flex items-center justify-between mb-[20rpx]">
                                    <text class="font-semibold text-[#374151]">生成时长</text>
                                    <view
                                        class="flex items-center gap-[6rpx] h-[48rpx] px-[16rpx] rounded-[12rpx] bg-[#F0F6FF]">
                                        <u-icon name="play-right" color="#0065fb" size="18" />
                                        <text class="text-primary text-xs font-bold">{{ sliderDuration }}s</text>
                                    </view>
                                </view>
                                <slider
                                    :value="sliderDuration"
                                    :min="DURATION_MIN"
                                    :max="DURATION_MAX"
                                    :step="1"
                                    activeColor="#0065fb"
                                    backgroundColor="#E5E9F0"
                                    block-color="#ffffff"
                                    block-size="22"
                                    @change="onSliderChange" />
                                <view class="flex items-center justify-between mt-[8rpx]">
                                    <text class="text-[22rpx] text-[#9CA3AF]">{{ DURATION_MIN }}秒</text>
                                    <text class="text-[22rpx] text-[#9CA3AF]">{{ DURATION_MAX }}秒</text>
                                </view>
                            </view>
                            <view class="flex items-center justify-between">
                                <text class="font-semibold text-[#374151]">生成视频数量</text>
                                <view class="flex items-center gap-[16rpx]">
                                    <view
                                        class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                        @click="handleVideoCount('minus')">
                                        <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                                    </view>
                                    <view
                                        class="max-w-[100rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                                        <u-input
                                            v-model="formData.video_count"
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
                        </view>
                    </view>

                    <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                        <view
                            class="flex items-center px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full mr-[10rpx]" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">视频名称</text>
                        </view>
                        <view class="px-[28rpx] py-[24rpx]">
                            <view
                                class="bg-[#F7F9FC] rounded-[16rpx] px-[24rpx] h-[96rpx] flex items-center border border-solid border-[#E5E9F0]">
                                <u-input
                                    class="w-full"
                                    v-model="formData.name"
                                    maxlength="50"
                                    placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                    placeholder="请输入视频名称"
                                    clearable />
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-t border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <view class="flex flex-col items-center gap-y-[6rpx] flex-shrink-0" @click="showTokensCost = true">
                <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]" />
                <text class="text-[#9CA3AF] text-[20rpx]">算力消耗</text>
            </view>
            <view
                class="flex-1 h-[96rpx] flex items-center justify-center rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleCreateVideo">
                <text class="text-white text-[28rpx] font-extrabold ml-[10rpx] relative z-10">
                    立即生成视频（{{ formData.video_count }}个）
                </text>
            </view>
        </view>
    </view>

    <!-- 弹窗部分完全不变 -->
    <popup-bottom v-model="showMaterialRule" title="素材上传规则">
        <template #content>
            <view class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-[40rpx] py-[32rpx] flex flex-col gap-[40rpx] pb-[60rpx]">
                        <view>
                            <view class="flex items-center gap-[12rpx] mb-[20rpx]">
                                <u-icon name="play-right" color="#0065fb" size="28" />
                                <text class="text-[28rpx] font-extrabold text-primary">视频要求</text>
                            </view>
                            <view class="flex flex-col gap-[16rpx]">
                                <view v-for="(rule, i) in VIDEO_RULES" :key="i" class="flex items-start gap-[12rpx]">
                                    <view
                                        class="w-[12rpx] h-[12rpx] rounded-full bg-[#0065fb]/30 mt-[10rpx] flex-shrink-0" />
                                    <text class="text-xs text-[#4B5563] leading-relaxed flex-1">
                                        <text class="font-semibold text-[#0D1117]">{{ rule.label }}：</text
                                        >{{ rule.value }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="flex items-center gap-[12rpx] mb-[20rpx]">
                                <u-icon name="photo" color="#10b981" size="28" />
                                <text class="text-[28rpx] font-extrabold text-[#10b981]">图片要求</text>
                            </view>
                            <view class="flex flex-col gap-[16rpx]">
                                <view v-for="(rule, i) in IMAGE_RULES" :key="i" class="flex items-start gap-[12rpx]">
                                    <view
                                        class="w-[12rpx] h-[12rpx] rounded-full bg-[#10b981]/30 mt-[10rpx] flex-shrink-0" />
                                    <view class="flex-1">
                                        <text class="text-xs text-[#4B5563] leading-relaxed">
                                            <text class="font-semibold text-[#0D1117]">{{ rule.label }}：</text
                                            >{{ rule.value }}
                                        </text>
                                        <view v-if="rule.sub" class="mt-[8rpx] pl-[16rpx] flex flex-col gap-[8rpx]">
                                            <text
                                                v-for="(s, si) in rule.sub"
                                                :key="si"
                                                class="text-[22rpx] text-[#9CA3AF] leading-relaxed block">
                                                - {{ s.text
                                                }}<text v-if="s.highlight" class="text-[#f97316] font-semibold">{{
                                                    s.highlight
                                                }}</text
                                                >{{ s.tail }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>

    <upload-category-panel
        v-model="showUploadCategoryPanel"
        :show-categories="[UploadAlbumTypeEnum.File, UploadCategoryEnum.Library, UploadCategoryEnum.Creation]"
        @select="handleSelectCategory" />
    <choose-material
        v-model="showChooseMaterial"
        :type="activeTab === 0 ? 'all' : 'image'"
        :limit="chooseMaterialLimit"
        @select="handleChooseMaterial" />
    <choose-history
        v-model="showChooseHistory"
        :type="activeTab === 0 ? 'all' : 'image'"
        :limit="chooseMaterialLimit"
        @select="handleSelectHistory" />
    <choose-agent
        v-if="showChooseAgent"
        v-model="showChooseAgent"
        is-sora
        :system-agent-ids="[7]"
        @select="handleSelectAgent" />
    <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="视频生成中"
        desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
        @to="toPublish"
        @seek="toRecord">
        <template #custom-btn>
            <view
                class="w-full text-[30rpx] font-medium rounded-[20rpx] h-[96rpx] flex items-center justify-center relative overflow-hidden border border-solid text-[#475569] border-[#e2e8f0]"
                style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)"
                @click="handleRelaunch">
                <text class="relative z-10">再创作一个</text>
            </view>
        </template>
    </create-success-pop>
    <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="MontageTypeEnum.SORA_VIDEO" />
    <recharge-popup ref="rechargePopupRef" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :pic="playData.pic" />
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { createSoraVideo } from "@/api/digital_human";
import { UploadAlbumTypeEnum, UploadCategoryEnum } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";
import useUpload from "@/hooks/useUpload";
import { MontageTypeEnum, ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { useMaterial } from "@/ai_modules/digital_human/hooks/useMaterial";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";

const MAX_DESC_LENGTH = 500;
const DURATION_MIN = 4;
const DURATION_MAX = 15;
const VIDEO_COUNT_MIN = 1;
const VIDEO_COUNT_MAX = 5;
const VIDEO_DURATION_LIMIT = 15;
const VIDEO_DURATION_WARN = 12;
const VIDEO_SINGLE_MIN = 2;
const VIDEO_SINGLE_MAX = 15;

const VIDEO_PROPORTIONS = [
    { label: "16:9", value: "16:9" },
    { label: "4:3", value: "4:3" },
    { label: "1:1", value: "1:1" },
    { label: "3:4", value: "3:4" },
    { label: "9:16", value: "9:16" },
    { label: "21:9", value: "21:9" },
] as const;

const VIDEO_RESOLUTIONS = [
    { label: "480p", value: "480p" },
    { label: "720p", value: "720p" },
] as const;

const VIDEO_RULES = [
    { label: "格式", value: "mp4、mov" },
    { label: "数量与时长", value: "单视频 2~15s，最多传 3 个，总时长不超过 15s" },
    { label: "大小", value: "单个视频不超过 50 MB" },
    { label: "尺寸", value: "宽高比 0.4 ~ 2.5，边长 300 ~ 6000px" },
    { label: "总像素", value: "409,600 ~ 927,408 之间 (如 480p, 720p)" },
    { label: "帧率", value: "24 ~ 60 FPS" },
];

const IMAGE_RULES = [
    { label: "格式", value: "jpeg, png, webp, bmp, tiff, gif" },
    { label: "大小", value: "单张 < 30 MB" },
    { label: "尺寸", value: "宽高比 0.4 ~ 2.5，边长 300 ~ 6000px" },
    {
        label: "数量限制",
        value: "",
        sub: [
            { text: "参考生成：1 ~ 9 张", highlight: "", tail: "" },
            { text: "首尾帧过渡：首帧与尾帧均为", highlight: "必填项", tail: "，必须同时上传。" },
        ],
    },
];

const { on } = useEventBusManager();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const tabs = ["参考生成", "首尾帧过渡"];
const activeTab = ref(0);
const sliderDuration = ref(7);
const showMaterialRule = ref(false);
const showTokensCost = ref(false);
const showCreateSuccess = ref(false);
const showVideoPreview = ref(false);
const showChooseAgent = ref(false);
const showChooseRole = ref(false);
const firstFrame = ref<any>(null);
const lastFrame = ref<any>(null);

const onSliderChange = (e: any) => {
    sliderDuration.value = e.detail.value;
};

const getRatioIconStyle = (ratio: string): string => {
    const [w, h] = ratio.split(":").map(Number);
    const maxSize = 40;
    const iconW = w >= h ? maxSize : Math.round((w / h) * maxSize);
    const iconH = w >= h ? Math.round((h / w) * maxSize) : maxSize;
    return `width:${iconW}rpx;height:${iconH}rpx;`;
};

const formData = reactive({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "一句话生成视频",
    content: "",
    materialList: [] as any[],
    aspect_ratio: "16:9",
    resolution: "480p",
    video_count: 1,
});

const { processAndAppend } = useMaterial(toRef(formData, "materialList"));

const videoMaterials = computed(() => formData.materialList.filter((i: any) => i.type === "video"));
const totalVideoDuration = computed(() =>
    videoMaterials.value.reduce((sum: number, i: any) => sum + (Number(i.duration) || 0), 0),
);
const remainVideoDuration = computed(() => Math.max(VIDEO_DURATION_LIMIT - totalVideoDuration.value, 0));

const handleVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.video_count <= VIDEO_COUNT_MIN) return uni.$u.toast(`视频数量最少为${VIDEO_COUNT_MIN}`);
        formData.video_count--;
    } else {
        if (formData.video_count >= VIDEO_COUNT_MAX) return uni.$u.toast(`视频数量最多为${VIDEO_COUNT_MAX}`);
        formData.video_count++;
    }
};

type UploadTarget = "material" | "firstFrame" | "lastFrame";
const showUploadCategoryPanel = ref(false);
const uploadTarget = ref<UploadTarget>("material");
const currentUploadType = ref<"image" | "video">("image");
const showChooseMaterial = ref(false);
const showChooseHistory = ref(false);

const openPanel = (target: UploadTarget, type: "image" | "video" = "image") => {
    uploadTarget.value = target;
    currentUploadType.value = type;
    showUploadCategoryPanel.value = true;
};

const chooseMaterialLimit = computed(() => {
    if (uploadTarget.value !== "material") return 1;
    const list = formData.materialList;
    return currentUploadType.value === "video"
        ? 3 - list.filter((i: any) => i.type === "video").length
        : 9 - list.filter((i: any) => i.type !== "video").length;
});

const appendMaterials = async (items: any[]) => {
    await processAndAppend({
        rawList: items,
        urlField: "url",
        maxDuration: 59,
        replaceIndex: replaceMaterialIndex.value,
    });
};

const setFrame = (target: UploadTarget, item: any) => {
    if (target === "firstFrame") firstFrame.value = item;
    if (target === "lastFrame") lastFrame.value = item;
};

const handleSelectCategory = (category: UploadAlbumTypeEnum | UploadCategoryEnum) => {
    if (category === UploadAlbumTypeEnum.File) {
        uploadTarget.value === "material"
            ? uploadAndProcessFiles(currentUploadType.value)
            : ((uploadingFrameTarget.value = uploadTarget.value as "firstFrame" | "lastFrame"),
              uploadFrameFile("image"));
    } else if (category === "library") {
        showChooseMaterial.value = true;
    } else {
        showChooseHistory.value = true;
    }
};

const handleChooseMaterial = (materials: any[]) => {
    if (uploadTarget.value !== "material") return setFrame(uploadTarget.value, materials[0]);
    appendMaterials(materials);
};

const handleSelectHistory = (history: any[]) => {
    if (uploadTarget.value !== "material") return setFrame(uploadTarget.value, history[0]);
    appendMaterials(history);
};

const replaceMaterialIndex = ref(-1);

const getVideoDuration = (filePath: string): Promise<number> =>
    new Promise((resolve) => {
        uni.getVideoInfo({
            src: filePath,
            success: (info) => resolve(info.duration ?? 0),
            fail: () => resolve(0),
        });
    });

const validateAndInjectDuration = async (materials: any[]): Promise<any[]> => {
    const result: any[] = [];
    for (const item of materials) {
        if (item.type !== "video" && currentUploadType.value !== "video") {
            result.push(item);
            continue;
        }
        const duration = item.duration ? Number(item.duration) : await getVideoDuration(item.url);
        if (duration < VIDEO_SINGLE_MIN || duration > VIDEO_SINGLE_MAX) {
            uni.$u.toast(`视频时长需在 ${VIDEO_SINGLE_MIN}~${VIDEO_SINGLE_MAX}s 之间`);
            continue;
        }
        const currentTotal = totalVideoDuration.value;
        if (currentTotal + duration > VIDEO_DURATION_LIMIT) {
            uni.$u.toast(
                `视频总时长不能超过 ${VIDEO_DURATION_LIMIT}s，当前剩余 ${(VIDEO_DURATION_LIMIT - currentTotal).toFixed(
                    1,
                )}s`,
            );
            continue;
        }
        result.push({ ...item, duration });
    }
    return result;
};

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: false,
    count: 9,
    sourceType: ["album", "camera"],
    imageAccept: ["jpg", "png", "jpeg", "webp", "gif"],
    imageSize: 30,
    videoAccept: ["mp4", "mov"],
    videoSize: 50,
    onSuccess: async (materials: any[]) => {
        const validated = currentUploadType.value === "video" ? await validateAndInjectDuration(materials) : materials;
        if (!validated.length) return;
        if (replaceMaterialIndex.value !== -1) {
            formData.materialList[replaceMaterialIndex.value] = validated[0];
            replaceMaterialIndex.value = -1;
            return;
        }
        appendMaterials(validated);
    },
});

const uploadingFrameTarget = ref<"firstFrame" | "lastFrame" | null>(null);

const { uploadAndProcessFiles: uploadFrameFile } = useUpload({
    isTranscode: false,
    count: 1,
    sourceType: ["album", "camera"],
    imageAccept: ["jpg", "png", "jpeg", "webp"],
    imageSize: 30,
    onSuccess: (materials: any[]) => {
        if (uploadingFrameTarget.value) setFrame(uploadingFrameTarget.value, materials[0]);
        uploadingFrameTarget.value = null;
    },
});

const handleClearAllMaterials = () => {
    uni.showModal({
        title: "确认清除",
        content: "确定要清除全部已上传素材吗？",
        confirmColor: "#F43F5E",
        success: ({ confirm }) => {
            if (confirm) formData.materialList = [];
        },
    });
};

const handleDeleteMaterial = (index: number) => {
    formData.materialList.splice(index, 1);
};

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
    openPanel("material", "image");
};
const previewMaterial = (item: any) => {
    uni.previewImage({ urls: [item.pic] });
};

const handleSelectAgent = ({ data }: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/sora_ai_copywriter/sora_ai_copywriter",
        params: { agentData: JSON.stringify(data), content: formData.content },
    });
};

const createResult = ref<any>(null);
const rechargePopupRef = shallowRef();
const playData = reactive<any>({ url: "", pic: "" });

const handleCreateVideo = async () => {
    if (userTokens.value <= 0) return rechargePopupRef.value?.open();
    if (!formData.name) return uni.$u.toast("请输入视频名称");
    const hasContent = formData.content.trim().length > 0;
    const hasMaterial = formData.materialList.length > 0;
    const hasFrame = firstFrame.value !== null && lastFrame.value !== null;
    if (activeTab.value === 0) {
        if (!hasContent && !hasMaterial) return uni.$u.toast("请输入提示词或上传素材");
    } else {
        if (!hasFrame) return uni.$u.toast("请上传首帧和尾帧");
    }
    if (formData.video_count <= 0) return uni.$u.toast("请输入视频数量");
    if (formData.video_count > VIDEO_COUNT_MAX) return uni.$u.toast(`视频数量最多为${VIDEO_COUNT_MAX}`);
    if (totalVideoDuration.value > VIDEO_DURATION_LIMIT)
        return uni.$u.toast(`视频总时长超出 ${VIDEO_DURATION_LIMIT}s 限制，请删除部分视频`);
    uni.showLoading({ title: "提交中...", mask: true });
    try {
        const params = {
            name: formData.name,
            content: formData.content,
            image_urls:
                activeTab.value === 0
                    ? formData.materialList
                          .filter((item: any) => item.type !== "video")
                          .map((item: any) => item.url)
                          .slice(0, 9)
                    : [firstFrame.value.url, lastFrame.value.url],
            video_urls: formData.materialList
                .filter((item: any) => item.type === "video")
                .map((item: any) => ({ url: item.url, duration: Number(item.duration) }))
                .slice(0, 3),
            aspect_ratio: formData.aspect_ratio,
            resolution: formData.resolution,
            duration: sliderDuration.value,
            number: formData.video_count,
            model: "seedance2.0",
            first_last_frame: activeTab.value === 0 ? 0 : 1,
        };

        const res = await createSoraVideo(params);
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
        WechatOA.notify();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const toPublish = () => {
    showCreateSuccess.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
        type: "reLaunch",
        params: {
            task_id: JSON.stringify([createResult.value.id]),
            scene: 1,
            type: MontageTypeEnum.SORA_VIDEO,
        },
    });
};

const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
        params: { source: "1", type: 6 },
    });
};
const handleRelaunch = () => {
    showCreateSuccess.value = false;
    formData.content = "";
    formData.materialList = [];
};

onLoad(() => {
    on("confirm", ({ type, data }: any) => {
        if (type === ListenerTypeEnum.SORA_AI_COPYWRITER) formData.content = data;
    });
});
</script>
