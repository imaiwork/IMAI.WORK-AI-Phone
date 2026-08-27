<template>
    <view v-if="loading" class="h-screen flex flex-col bg-[#F4F6F9]">
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[20rpx]">
                    <view class="bg-white rounded-[32rpx] p-[28rpx] shadow-[0_4rpx_20rpx_rgba(0,0,0,0.03)]">
                        <view class="flex justify-between items-center mb-[24rpx]">
                            <view class="skeleton h-[40rpx] w-[200rpx] rounded-full" />
                            <view class="skeleton h-[56rpx] w-[160rpx] rounded-full" />
                        </view>
                        <view class="flex gap-[16rpx] mb-[24rpx]">
                            <view
                                v-for="i in 3"
                                :key="i"
                                class="skeleton flex-shrink-0 w-[164rpx] h-[224rpx] rounded-[24rpx]" />
                        </view>
                        <view class="space-y-[24rpx]">
                            <view v-for="i in 3" :key="i" class="flex justify-between items-center">
                                <view class="skeleton h-[32rpx] w-[140rpx] rounded-full" />
                                <view class="skeleton h-[48rpx] w-[180rpx] rounded-full" />
                            </view>
                        </view>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-[28rpx] shadow-[0_4rpx_20rpx_rgba(0,0,0,0.03)]">
                        <view class="flex gap-[16rpx] mb-[24rpx]">
                            <view class="skeleton flex-1 h-[80rpx] rounded-[20rpx]" />
                            <view class="skeleton flex-1 h-[80rpx] rounded-[20rpx]" />
                            <view class="skeleton flex-1 h-[80rpx] rounded-[20rpx]" />
                        </view>
                        <view class="skeleton h-[240rpx] w-full rounded-[24rpx]" />
                    </view>
                </view>
            </scroll-view>
        </view>
        <view
            class="flex-shrink-0 bg-white px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[24rpx] shadow-[0_-4rpx_20rpx_rgba(0,0,0,0.02)]">
            <view class="skeleton w-[80rpx] h-[80rpx] rounded-full" />
            <view class="flex-1 skeleton h-[100rpx] rounded-[28rpx]" />
        </view>
    </view>

    <view class="h-screen flex flex-col bg-[#F4F6F9]" v-else>
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[20rpx]">
                    <!-- 模型档位页签：文件夹式，与下方卡片粘连 -->
                    <view class="flex items-end gap-[10rpx] -mb-[20rpx] relative z-10">
                        <view
                            v-for="item in cloneModeTabs"
                            :key="item.value"
                            class="tier-tab flex items-center gap-[12rpx] flex-shrink-0"
                            :class="currCloneMode === item.value ? 'tier-tab--on' : ''"
                            @click="handleSelectCloneMode(item.value)">
                            <text>{{ item.name }}</text>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[32rpx] rounded-tl-none overflow-hidden shadow-[0_4rpx_24rpx_rgba(0,0,0,0.02)] border border-solid border-white">
                        <view class="flex items-center justify-between px-[28rpx] pt-[28rpx] pb-[20rpx]">
                            <view class="flex items-center gap-[12rpx]">
                                <view
                                    class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center shadow-inner"
                                    style="background: linear-gradient(to right, #ebf2ff, #d6e4ff)">
                                    <u-icon name="account-fill" color="#0065fb" size="18" />
                                </view>
                                <text class="text-[32rpx] font-extrabold text-[#111827]">出镜形象</text>
                            </view>
                            <view
                                class="flex items-center gap-[6rpx] rounded-full px-[24rpx] py-[12rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.2)] active:scale-95 transition-transform"
                                style="background: linear-gradient(to right, #0065fb, #3b82f6)"
                                @click="showChooseAnchor = true">
                                <u-icon name="plus" size="14" color="#ffffff" />
                                <text class="text-xs font-bold text-white">选择形象</text>
                            </view>
                        </view>

                        <view class="px-[28rpx] pb-[24rpx]">
                            <view v-if="anchorListLoading">
                                <scroll-view scroll-x>
                                    <view class="flex gap-[20rpx] p-[8rpx] pb-[12rpx]">
                                        <view
                                            v-for="i in 3"
                                            :key="i"
                                            class="skeleton flex-shrink-0 w-[164rpx] h-[224rpx] rounded-[24rpx]" />
                                    </view>
                                </scroll-view>
                            </view>

                            <view v-else-if="anchorLists.length > 0">
                                <scroll-view scroll-x>
                                    <view class="flex gap-[20rpx] p-[8rpx] pb-[12rpx]">
                                        <view
                                            v-for="(item, index) in anchorLists"
                                            :key="item.anchor_id || index"
                                            class="flex-shrink-0 w-[164rpx] h-[224rpx] rounded-[24rpx] relative overflow-hidden transition-all duration-300"
                                            :class="
                                                currAnchorIndex === index
                                                    ? 'shadow-[0_0_0_2rpx_rgba(0,101,251,0.35)] scale-105 z-10'
                                                    : 'shadow-sm'
                                            "
                                            @click="chooseAnchor(index)">
                                            <image :src="item.pic" class="w-full h-full" mode="aspectFill" />
                                            <view
                                                class="absolute bottom-[16rpx] right-[16rpx] w-[44rpx] h-[44rpx] rounded-full bg-[#ffffff]/20 flex items-center justify-center border border-solid border-[#ffffff]/40"
                                                @click.stop="previewVideo(item.result_url)">
                                                <u-icon
                                                    name="play-right-fill"
                                                    color="#ffffff"
                                                    size="20"
                                                    class="ml-0.5" />
                                            </view>

                                            <view
                                                v-if="currAnchorIndex === index"
                                                class="absolute inset-0 rounded-[24rpx] border-[4rpx] border-solid border-primary pointer-events-none" />

                                            <view
                                                class="absolute top-[12rpx] right-[12rpx] w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-all duration-300"
                                                :class="
                                                    currAnchorIndex === index
                                                        ? 'bg-primary'
                                                        : 'bg-[#000000]/30 border-[2rpx] border-solid border-[#ffffff]/50'
                                                ">
                                                <u-icon
                                                    v-if="currAnchorIndex === index"
                                                    name="checkmark"
                                                    color="#fff"
                                                    size="14"
                                                    font-weight="bold" />
                                            </view>

                                            <view
                                                v-if="getAnchorStatus(item.status, item.source_type) == 0"
                                                class="absolute inset-0 bg-[#ffffff]/60 flex flex-col items-center justify-center gap-[12rpx]">
                                                <u-icon name="clock" color="#fff" size="24" />
                                                <text class="text-[22rpx] text-white font-medium tracking-wide"
                                                    >克隆中</text
                                                >
                                            </view>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>

                            <view
                                v-else
                                class="flex flex-col items-center justify-center py-[50rpx] bg-[#F8FAFC] rounded-[24rpx] border border-dashed border-[#CBD5E1]">
                                <view
                                    class="w-[100rpx] h-[100rpx] rounded-full bg-white flex items-center justify-center mb-[16rpx]">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/avatar.png"
                                        class="w-[60rpx] h-[68rpx]" />
                                </view>
                                <text class="text-[28rpx] font-bold text-[#1E293B] mb-[8rpx]">定制专属数字人</text>
                                <text class="text-xs text-[#64748B] mb-[24rpx]">1:1 还原真人形象与声音</text>
                                <view
                                    class="px-[40rpx] py-[16rpx] rounded-full bg-[#1E293B] shadow-md active:scale-95 transition-transform"
                                    @click="openModel()">
                                    <text class="font-bold text-white">立即定制 ✨</text>
                                </view>
                            </view>
                        </view>
                        <view v-if="showDriveModel" class="px-[28rpx] pb-[12rpx]">
                            <view
                                class="flex items-center justify-between py-[24rpx] border-[0] border-t border-solid border-[#F1F5F9]"
                                @click="openChooseModel()">
                                <text class="text-[28rpx] font-bold text-[#334155]">驱动模型</text>
                                <view class="flex items-center gap-[12rpx]">
                                    <view
                                        class="px-[20rpx] py-[8rpx] rounded-full"
                                        :class="
                                            formData.model_version
                                                ? 'bg-[#F0F5FF] border border-solid border-[#D6E4FF]'
                                                : 'bg-[#F1F5F9]'
                                        ">
                                        <text
                                            class="text-xs font-bold"
                                            :class="formData.model_version ? 'text-primary' : 'text-[#94A3B8]'">
                                            {{ modelVersionMap[formData.model_version] || "未选择" }}
                                        </text>
                                    </view>
                                    <u-icon name="arrow-right" color="#CBD5E1" size="20" />
                                </view>
                            </view>
                        </view>
                        <view class="px-[28rpx] pb-[12rpx]">
                            <view
                                class="flex items-center justify-between py-[24rpx] border-[0] border-t border-solid border-[#F1F5F9]"
                                @click="openChooseTone()">
                                <text class="text-[28rpx] font-bold text-[#334155]">配音音色</text>
                                <view class="flex items-center gap-[12rpx]">
                                    <view
                                        class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full"
                                        :class="
                                            isOriginalToneSelected
                                                ? 'bg-[#F0FDF4] border border-solid border-[#BBF7D0]'
                                                : formData.voice_name
                                                ? 'bg-[#F5F3FF] border border-solid border-[#EDE9FE]'
                                                : 'bg-[#F1F5F9]'
                                        ">
                                        <u-icon v-if="isOriginalToneSelected" name="mic" color="#16A34A" size="16" />
                                        <text
                                            class="text-xs font-bold"
                                            :class="
                                                isOriginalToneSelected
                                                    ? 'text-[#16A34A]'
                                                    : formData.voice_name
                                                    ? 'text-[#8B5CF6]'
                                                    : 'text-[#94A3B8]'
                                            ">
                                            {{ isChanjingOriginalTone ? "视频原音" : formData.voice_name || "未选择" }}
                                        </text>
                                    </view>
                                    <u-icon name="arrow-right" color="#CBD5E1" size="20" />
                                </view>
                            </view>

                            <view
                                v-if="clipConfig.is_open"
                                class="flex items-center justify-between py-[24rpx] border-[0] border-t border-solid border-[#F1F5F9]">
                                <view class="flex items-center gap-[12rpx]">
                                    <text class="text-[28rpx] font-bold text-[#334155]">视频包装</text>
                                </view>
                                <u-switch
                                    v-model="formData.ai_clip_enabled"
                                    size="36"
                                    active-color="#0065fb"
                                    :active-value="1"
                                    :inactive-value="0" />
                            </view>

                            <view class="py-[24rpx] border-[0] border-t border-solid border-[#F1F5F9]">
                                <view class="flex items-center justify-between mb-[18rpx]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <view
                                            class="w-[44rpx] h-[44rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                            <u-icon name="volume" color="#0065fb" size="18" />
                                        </view>
                                        <view class="flex flex-col gap-[4rpx]">
                                            <text class="text-[28rpx] font-bold text-[#334155]">声音音量</text>
                                            <text class="text-[22rpx] text-[#94A3B8]">调节合成配音音量</text>
                                        </view>
                                    </view>
                                    <view class="flex items-center gap-[12rpx]">
                                        <text class="text-[24rpx] font-bold text-primary">
                                            {{ formatVolume(formData.extra.volume) }}
                                        </text>
                                    </view>
                                </view>
                                <view class="flex items-center gap-[16rpx]">
                                    <u-icon name="volume-off" color="#94A3B8" size="20" />
                                    <view class="flex-1">
                                        <slider
                                            :value="volumePercent"
                                            :min="10"
                                            :max="100"
                                            :step="10"
                                            active-color="#0065fb"
                                            background-color="#E5E7EB"
                                            block-size="18"
                                            @change="handleVolumeChange"
                                            @changing="handleVolumeChange" />
                                    </view>
                                    <u-icon name="volume" color="#0065fb" size="20" />
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[32rpx] overflow-hidden shadow-[0_4rpx_24rpx_rgba(0,0,0,0.02)] border border-solid border-white">
                        <view class="px-[28rpx] pt-[28rpx] pb-[20rpx] flex items-center gap-[12rpx]">
                            <view
                                class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center shadow-inner"
                                style="background: linear-gradient(to right, #f5f3ff, #ede9fe)">
                                <u-icon name="edit-pen-fill" color="#8B5CF6" size="18" />
                            </view>
                            <text class="text-[32rpx] font-extrabold text-[#111827]">口播内容</text>
                        </view>

                        <view class="flex items-center gap-[16rpx] px-[28rpx] pb-[20rpx]">
                            <view
                                class="flex-1 flex items-center justify-center gap-[8rpx] h-[76rpx] rounded-[20rpx] bg-[#FFF7ED] border border-solid border-[#FFEDD5] active:scale-95 transition-transform"
                                @click="randomCopywriter()">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/random.svg"
                                    class="w-[28rpx] h-[28rpx]" />
                                <text class="font-bold text-[#EA580C]">随机灵感</text>
                            </view>

                            <view
                                class="flex-1 flex items-center justify-center gap-[8rpx] h-[76rpx] rounded-[20rpx] border border-solid border-[#BFDBFE] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.1)] active:scale-95 transition-transform relative overflow-hidden"
                                style="background: linear-gradient(to right, #f0f5ff, #ebf2ff)"
                                @click="showChooseAgent = true">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/copywriter.svg"
                                    class="w-[28rpx] h-[28rpx]" />
                                <text class="font-bold text-primary">智能文案</text>
                            </view>

                            <view
                                class="flex-1 flex items-center justify-center gap-[8rpx] h-[76rpx] rounded-[20rpx] bg-[#F0FDF4] border border-solid border-[#DCFCE7] active:scale-95 transition-transform"
                                @click="showAudioType = true">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/sound.svg"
                                    class="w-[28rpx] h-[28rpx]" />
                                <text class="font-bold text-[#16A34A]">录音/音频</text>
                            </view>
                        </view>

                        <view class="px-[28rpx] pb-[28rpx]">
                            <navigator
                                v-if="formData.audio_type == CreateTypeEnum.TEXT"
                                :url="`/ai_modules/digital_human/pages/szr_copywriter/szr_copywriter?limit=${textLimit}&content=${formData.msg}`"
                                hover-class="none"
                                class="bg-[#F8FAFC] rounded-[24rpx] p-[24rpx] border border-solid border-[#F1F5F9] relative">
                                <view
                                    class="min-h-[280rpx] text-[28rpx] leading-relaxed"
                                    :class="formData.msg ? 'text-[#334155]' : 'text-[#94A3B8]'">
                                    {{ formData.msg || "点击输入您的口播文案，或使用上方 AI 工具生成..." }}
                                </view>

                                <view
                                    class="flex items-center justify-between mt-[20rpx] pt-[20rpx] border-[0] border-t border-dashed border-[#E2E8F0]">
                                    <view
                                        v-if="formData.msg?.length > textLimit"
                                        class="flex items-center gap-[8rpx] bg-[#FEF2F2] px-[16rpx] py-[6rpx] rounded-full">
                                        <u-icon name="info-circle-fill" color="#EF4444" size="14" />
                                        <text class="text-[22rpx] font-bold text-[#EF4444]"
                                            >超出 {{ textLimit }} 字，请删减</text
                                        >
                                    </view>
                                    <view v-else />
                                    <view
                                        class="bg-white px-[16rpx] py-[6rpx] rounded-full shadow-sm border border-solid border-[#F1F5F9]">
                                        <text
                                            class="text-[22rpx] font-bold"
                                            :class="
                                                formData.msg?.length > textLimit ? 'text-[#EF4444]' : 'text-[#94A3B8]'
                                            ">
                                            {{ formData.msg?.length || 0 }}
                                            <text class="text-[#CBD5E1] mx-1">/</text>
                                            {{ textLimit }}
                                        </text>
                                    </view>
                                </view>
                            </navigator>

                            <view
                                v-else
                                class="bg-[#F8FAFC] rounded-[24rpx] border border-solid border-[#F1F5F9] p-[24rpx] relative overflow-hidden">
                                <view
                                    v-if="!audioLoading"
                                    class="absolute top-[24rpx] right-[24rpx] w-[48rpx] h-[48rpx] rounded-full bg-white shadow-sm flex items-center justify-center z-10"
                                    @click="clearAudioData()">
                                    <u-icon name="close" size="16" color="#94A3B8" />
                                </view>

                                <template v-if="audioLoading">
                                    <view class="flex flex-col items-center justify-center py-[60rpx] gap-[16rpx]">
                                        <view class="relative w-[80rpx] h-[80rpx] flex items-center justify-center">
                                            <view
                                                class="absolute inset-0 border-[6rpx] border-solid border-[#EBF2FF] rounded-full" />
                                            <view
                                                class="absolute inset-0 border-[6rpx] border-solid border-primary rounded-full border-t-transparent rotate-anim" />
                                            <u-icon name="mic" color="#0065fb" size="24" />
                                        </view>
                                        <text class="text-[28rpx] font-bold text-[#1E293B]"
                                            >AI 正在提取音频文案...</text
                                        >
                                        <text class="text-[22rpx] text-[#64748B]">请勿熄屏或切换应用</text>
                                    </view>
                                </template>

                                <template v-else-if="audioError">
                                    <view class="flex flex-col items-center justify-center py-[48rpx] gap-[20rpx]">
                                        <view
                                            class="w-[96rpx] h-[96rpx] rounded-full bg-[#FEF2F2] flex items-center justify-center">
                                            <u-icon name="close-circle-fill" color="#EF4444" size="40" />
                                        </view>
                                        <view class="flex flex-col items-center gap-[10rpx]">
                                            <text class="text-[30rpx] font-bold text-[#1E293B]">音频转写失败</text>
                                            <text
                                                class="text-[24rpx] text-[#EF4444] text-center px-[40rpx] leading-relaxed">
                                                {{ audioErrorMsg }}
                                            </text>
                                        </view>
                                        <view class="flex gap-[24rpx] mt-[8rpx]">
                                            <view
                                                class="px-[48rpx] py-[18rpx] rounded-full border border-solid border-[#E2E8F0] bg-white active:scale-95 transition-transform"
                                                @click="clearAudioData()">
                                                <text class="text-[26rpx] font-bold text-[#64748B]">取消</text>
                                            </view>
                                            <view
                                                class="px-[48rpx] py-[18rpx] rounded-full bg-[#EF4444] shadow-[0_4rpx_12rpx_rgba(239,68,68,0.25)] active:scale-95 transition-transform"
                                                @click="retryAudioTranscribe()">
                                                <text class="text-[26rpx] font-bold text-white">重新转写</text>
                                            </view>
                                        </view>
                                    </view>
                                </template>

                                <template v-else>
                                    <view class="flex items-center gap-[20rpx] mb-[24rpx]">
                                        <view
                                            class="w-[88rpx] h-[88rpx] rounded-full flex items-center justify-center shadow-[0_8rpx_16rpx_rgba(0,101,251,0.2)] transition-all duration-300"
                                            :class="
                                                isPlaying
                                                    ? 'bg-gradient-to-br from-[#0065fb] to-[#3b82f6] scale-95'
                                                    : 'bg-white'
                                            "
                                            @click="handlePlayAudio">
                                            <u-icon
                                                :name="isPlaying ? 'pause' : 'play-right-fill'"
                                                :color="isPlaying ? '#fff' : '#0065fb'"
                                                size="32"
                                                :class="isPlaying ? '' : 'ml-1'" />
                                        </view>
                                        <view class="flex flex-col justify-center">
                                            <text class="text-[28rpx] font-bold text-[#1E293B] mb-[4rpx]"
                                                >已录制音频</text
                                            >
                                            <view class="flex items-center gap-[12rpx]">
                                                <view class="flex gap-[4rpx] items-center h-[24rpx]">
                                                    <view
                                                        v-for="i in 4"
                                                        :key="i"
                                                        class="w-[4rpx] bg-primary rounded-full"
                                                        :class="isPlaying ? 'wave-anim' : 'h-[8rpx]'"
                                                        :style="{ animationDelay: `${i * 0.15}s` }" />
                                                </view>
                                                <text class="text-xs font-bold text-primary">
                                                    {{ formatAudioTime(currDuration) }}
                                                    <text class="text-[#94A3B8] font-normal"
                                                        >/ {{ formatAudioTime(formData.audio_duration) }}</text
                                                    >
                                                </text>
                                            </view>
                                        </view>
                                    </view>
                                    <navigator
                                        :url="`/ai_modules/digital_human/pages/szr_copywriter/szr_copywriter?limit=${textLimit}&content=${formData.msg}`"
                                        hover-class="none"
                                        class="bg-white rounded-[16rpx] p-[20rpx] shadow-sm border border-solid border-[#F1F5F9]">
                                        <text class="text-[#475569] leading-relaxed">{{ formData.msg }}</text>
                                    </navigator>
                                </template>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-[#ffffff]/80 border-[0] border-t border-solid border-[#F1F5F9] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[24rpx]">
            <view class="flex flex-col items-center gap-[8rpx]" @click="openModelRule()">
                <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]" />
                <text class="text-[20rpx] font-bold text-[#94A3B8]">算力说明</text>
            </view>

            <view
                class="flex-1 h-[100rpx] rounded-[32rpx] flex items-center justify-center gap-[12rpx] relative overflow-hidden shadow-[0_12rpx_32rpx_rgba(0,101,251,0.35)] active:scale-[0.98] transition-all"
                style="background: linear-gradient(135deg, #0065fb 0%, #3b82f6 50%, #0ea5e9 100%)"
                @click="startCreate()">
                <text class="text-[32rpx] font-extrabold text-white tracking-wide">生成数字人视频</text>
            </view>
        </view>
    </view>

    <video-preview
        v-model="showVideoPreview"
        title="视频预览"
        :video-url="previewVideoUrl"
        @confirm="showVideoPreview = false" />
    <select-anchor v-model="showChooseAnchor" :clone-mode="currCloneMode" @select="handleChooseAnchor" />
    <choose-model
        v-model="showChooseModel"
        :model-version="[DigitalHumanModelVersionEnum.CHANJING, DigitalHumanModelVersionEnum.SHANJIAN]"
        @confirm="handleChooseModel" />
    <choose-tone
        v-if="showChooseTone"
        ref="chooseToneRef"
        v-model="showChooseTone"
        :show-original-tone="showOriginalTone"
        :model-version="toneListModelVersion"
        :limit="1"
        :show-free-tone="false"
        :original-selected="isOriginalToneSelected"
        @select="handleChooseTone"
        @original="handleChooseOriginalTone" />

    <choose-agent v-if="showChooseAgent" v-model="showChooseAgent" @select="handleSelectAgent" />
    <model-rule v-model="showModelRule" :model-version="formData.model_version" />
    <agreement v-model="showAgreement" @agree="agreeCreate" @close="showAgreement = false" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <recorder-control
        v-if="showRecorder"
        v-model="showRecorder"
        ref="recorderRef"
        @close="showRecorder = false"
        @success="recorderSuccess" />
    <choose-audio-type v-model="showAudioType" @recorder="openRecorder" @file="uploadAndProcessFiles('file')" />
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="数字人创作成功"
        desc="您可以立即去我的作品中查看"
        center
        to-text="取消"
        @to="goHome"
        @seek="toRecord" />
</template>

<script setup lang="ts">
import Cache from "@/utils/cache";
import WechatOA from "@/utils/wechat";
import { createShanjianTask, getPublicAnchorList } from "@/api/digital_human";
import { getClipConfig } from "@/api/app";
import { lpSceneSpeechToText } from "@/api/ladder_player";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import {
    ModeTypeEnum,
    CreateTypeEnum,
    ListenerTypeEnum,
    CloneModeEnum,
    ClipStyleEnum,
    SpeechEngineTypeEnum,
    cloneModeToIsPro,
    SPEECH_TEXT_LIMIT,
} from "@/ai_modules/digital_human/enums";
import useUpload from "@/hooks/useUpload";
import { useAudio } from "@/hooks/useAudio";
import usePolling from "@/hooks/usePolling";
import { formatAudioTime } from "@/utils/util";
import { createVideoCopywriter } from "@/ai_modules/digital_human/config/copywriter";
import SelectAnchor from "@/ai_modules/digital_human/components/choose-anchor/choose-anchor.vue";
import ChooseModel from "@/ai_modules/digital_human/components/choose-model/choose-model.vue";
import ChooseTone from "@/ai_modules/digital_human/components/choose-tone/choose-tone.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import ModelRule from "@/ai_modules/digital_human/components/model-rule/model-rule.vue";
import Agreement from "@/ai_modules/digital_human/components/agreement/agreement.vue";
import RecorderControl from "@/ai_modules/digital_human/components/recorder-control/recorder-control.vue";
import ChooseAudioType from "@/ai_modules/digital_human/components/choose-audio-type/choose-audio-type.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";

const DIGITAL_HUMAN_SPEECH_SHANJIAN_TYPE = 5;

// 定义锚点数据接口
interface AnchorItem {
    name: string;
    id: string;
    model_version: DigitalHumanModelVersionEnum & 0;
    anchor_id: string;
    anchor_ids: {
        chanjing_anchor_id: string;
        weiju_anchor_id: string;
        shanjian_anchor_id: string;
    };
    result_url: string;
    pic: string;
    width: number;
    height: number;
    status: number;
    source_type: string;
    extra_info: {
        width: number;
        height: number;
        shanjian_voice_id?: string;
    };
}

const { on } = useEventBusManager();

const userStore = useUserStore();
const appStore = useAppStore();

const loading = ref(true);

// 常量定义
const DH_CREATE_AGREEMENT_KEY = "create_agreement";

// 表单数据初始化
const formData = reactive<any>({
    name: "",
    pic: "",
    width: 0,
    height: 0,
    anchor_id: "",
    anchor_name: "",
    gender: "male",
    model_version: "" as unknown as DigitalHumanModelVersionEnum,
    audio_type: CreateTypeEnum.TEXT,
    audio_url: "",
    voice_id: "",
    voice_type: 1,
    voice_name: "",
    msg: "",
    video_url: "",
    ai_clip_enabled: 1,
    automatic_clip: 0,
    clip_type: ClipStyleEnum.AI_RECOMMEND,
    music_url: "",
    music_name: "",
    music_type: 1,
    extra: {
        video_count: 1,
        volume: 0.3,
    },
});

// 状态变量：标准版→is_pro=1，优质版→is_pro=2（创建仍用 clone_mode 2/3）
const cloneModeTabs = [
    {
        value: CloneModeEnum.FAST,
        name: "标准版",
        sub: `${SPEECH_TEXT_LIMIT.DEFAULT}字`,
        max: SPEECH_TEXT_LIMIT.DEFAULT,
    },
    {
        value: CloneModeEnum.PRO,
        name: "优质版",
        sub: `${SPEECH_TEXT_LIMIT.DEFAULT}字`,
        max: SPEECH_TEXT_LIMIT.DEFAULT,
    },
] as const;
const currCloneMode = ref<CloneModeEnum>(CloneModeEnum.FAST);
const anchorListLoading = ref(false);
const anchorLists = ref<AnchorItem[]>([]);
const showChooseModel = ref(false);
const currAnchorIndex = ref(-1);
const showChooseAnchor = ref(false);
const previewVideoUrl = ref<string>("");
const showVideoPreview = ref(false);
const showChooseTone = ref(false);
const currCopywriterIndex = ref(-1);
const showModelRule = ref(false);
const showChooseAgent = ref(false);
const showAgreement = ref(false);
const showAudioType = ref(false);
const audioLoading = ref(false);
const audioError = ref(false);
const audioErrorMsg = ref("");
const showRecorder = ref(false);
const showCreateSuccess = ref(false);
const recorderRef = shallowRef<InstanceType<typeof RecorderControl>>();
const chooseToneRef = shallowRef<InstanceType<typeof ChooseTone>>();

const modelChannel = computed(() => appStore.getAiModelConfig.humanModels || []);

const isPublicAnchor = computed(() => {
    if (anchorLists.value.length === 0) return false;
    const { model_version } = anchorLists.value[currAnchorIndex.value];
    return model_version === 0;
});

// 优质版固定闪剪，不展示驱动模型选择
const showDriveModel = computed(() => currCloneMode.value !== CloneModeEnum.PRO);

const modelVersionMap = computed(() => {
    return modelChannel.value.reduce((acc: Record<string, any>, item: any) => {
        acc[item.model_version] = item.name;
        return acc;
    }, {});
});

const clipConfig = reactive({
    is_open: false,
});

// 文案字数：蝉镜无包装 4000，蝉镜有包装 / 标准版 / 优质版 1500
const textLimit = computed(() => {
    if (formData.model_version == DigitalHumanModelVersionEnum.CHANJING) {
        return clipConfig.is_open && formData.ai_clip_enabled == 1
            ? SPEECH_TEXT_LIMIT.DEFAULT
            : SPEECH_TEXT_LIMIT.CHANJING_NO_PACK;
    }
    return cloneModeTabs.find((item) => item.value === currCloneMode.value)?.max ?? SPEECH_TEXT_LIMIT.DEFAULT;
});

const trimMsgByLimit = () => {
    if (formData.msg?.length > textLimit.value) {
        formData.msg = formData.msg.slice(0, textLimit.value);
    }
};

watch([() => clipConfig.is_open, () => formData.ai_clip_enabled], () => {
    trimMsgByLimit();
});

const isAudioCreate = computed(() => formData.audio_type == CreateTypeEnum.AUDIO);

const hasCreateContent = computed(() => {
    if (isAudioCreate.value) {
        return !!formData.audio_url && !audioLoading.value && !audioError.value;
    }
    return formData.msg?.length > 0;
});

const isChanjingOriginalTone = computed(() => {
    return (
        formData.model_version == DigitalHumanModelVersionEnum.CHANJING &&
        (formData.voice_id == -1 || formData.voice_id == "-1")
    );
});

// 蝉镜：视频原音；闪剪：形象原声
const showOriginalTone = computed(() => {
    return (
        formData.model_version == DigitalHumanModelVersionEnum.CHANJING ||
        formData.model_version == DigitalHumanModelVersionEnum.SHANJIAN
    );
});

// 驱动模型音色 + MiniMax（蝉镜/闪剪均支持）
const toneListModelVersion = computed(() => {
    if (!formData.model_version) return "";
    return [
        formData.model_version,
        DigitalHumanModelVersionEnum.MINIMAX_HD,
        DigitalHumanModelVersionEnum.MINIMAX_TURBO,
    ].join(",");
});

const canCreate = computed(() => {
    // 音频驱动时直接使用上传音频，不强制选择音色；蝉镜原音 voice_id=-1 也算已选
    const hasVoice = isAudioCreate.value || isChanjingOriginalTone.value || !!formData.voice_id;
    return currAnchorIndex.value !== -1 && !!formData.model_version && hasVoice && hasCreateContent.value;
});

const volumePercent = computed(() => {
    return Math.round(Number(formData.extra.volume || 0) * 100);
});

const getClipConfigData = async () => {
    const { code } = await getClipConfig();
    clipConfig.is_open = code == 10000;
};

const formatVolume = (volume: number) => {
    return Math.round(volume * 100) + "%";
};

const normalizeVolume = (volume: number) => {
    return Number(Number(volume || 0).toFixed(1));
};

const handleVolumeChange = (e: any) => {
    const { value } = e.detail;
    formData.extra.volume = normalizeVolume(value / 100);
};

const getAnchorStatus = (status: number, source_type: string) => {
    const anchorStatusMapping: Record<string, any> = {
        human_anchor: {
            1: 1,
            2: 2,
            default: 0,
        },
        public_anchor: {
            1: 0,
            2: 1,
            3: 2,
            default: 0,
        },
    };
    return anchorStatusMapping?.[source_type]?.[status] ?? anchorStatusMapping?.[source_type]?.["default"];
};

// 是否使用形象原声（默认原声，手动选音色后关闭，切换形象时原声跟随更新）
const isOriginalVoice = ref(true);

const handleSelectCloneMode = async (mode: CloneModeEnum) => {
    if (currCloneMode.value === mode || anchorListLoading.value) return;
    currCloneMode.value = mode;
    anchorListLoading.value = true;
    anchorLists.value = [];
    currAnchorIndex.value = -1;
    // 优质版固定闪剪驱动模型
    if (mode === CloneModeEnum.PRO) {
        formData.model_version = DigitalHumanModelVersionEnum.SHANJIAN;
    }
    if (isOriginalVoice.value) {
        formData.voice_id = "";
        formData.voice_name = "";
    }
    trimMsgByLimit();

    await getModelLists();
    start();
};

// 形象相关方法
const chooseAnchor = (index: number) => {
    const { status, source_type, model_version } = anchorLists.value[index];
    const anchorStatus = getAnchorStatus(status, source_type);
    if (anchorStatus != 1) {
        uni.$u.toast("该形象正在克隆中，请稍后再试");
        return;
    }

    const prevModel = formData.model_version;
    if (currCloneMode.value === CloneModeEnum.PRO) {
        // 优质版固定闪剪
        formData.model_version = DigitalHumanModelVersionEnum.SHANJIAN;
    } else if (model_version === 0) {
        // 公共形象可切换驱动模型，未选时默认闪剪
        if (
            formData.model_version !== DigitalHumanModelVersionEnum.CHANJING &&
            formData.model_version !== DigitalHumanModelVersionEnum.SHANJIAN
        ) {
            formData.model_version = DigitalHumanModelVersionEnum.SHANJIAN;
        }
    } else {
        formData.model_version = model_version;
    }
    currAnchorIndex.value = index;

    // 模型变化或默认原声时，按当前模型重置音色
    if (prevModel !== formData.model_version || isOriginalVoice.value) {
        resetToneByModel();
    }
    if (prevModel !== formData.model_version) {
        trimMsgByLimit();
    }
};

const handleChooseAnchor = (data: AnchorItem) => {
    const exists = anchorLists.value.findIndex((item) => item.id === data.id);
    if (exists === -1) {
        anchorLists.value = [data, ...anchorLists.value];
        const index = anchorLists.value.findIndex((item: any) => getAnchorStatus(item.status, item.source_type) == 1);
        if (index === -1) {
            chooseAnchor(index);
        } else {
            chooseAnchor(0);
        }
    } else {
        chooseAnchor(exists);
    }
    showChooseAnchor.value = false;
};

// 模型相关方法
const openModel = () => {
    uni.$u.route({
        url: `/ai_modules/digital_human/pages/anchor_create/anchor_create?source=${DigitalHumanModelVersionEnum.SHANJIAN}&type=${ModeTypeEnum.ANCHOR}`,
    });
};

const openChooseModel = () => {
    if (!showDriveModel.value) return;
    if (currAnchorIndex.value === -1) {
        uni.$u.toast("请先选择形象");
        showChooseAnchor.value = true;
        return;
    }
    if (isPublicAnchor.value) {
        showChooseModel.value = true;
    } else {
        uni.$u.toast("该形象无法更改模型哦~");
    }
};

const resetToneByModel = () => {
    isOriginalVoice.value = true;
    chooseToneRef.value?.setChooseLists([]);
    if (formData.model_version == DigitalHumanModelVersionEnum.CHANJING) {
        formData.voice_id = "-1";
        formData.voice_name = "";
        formData.voice_type = 1;
        return;
    }
    if (formData.model_version == DigitalHumanModelVersionEnum.SHANJIAN) {
        applyOriginalTone(anchorLists.value[currAnchorIndex.value]);
        return;
    }
    formData.voice_id = "";
    formData.voice_name = "";
    formData.voice_type = 1;
};

const handleChooseModel = (modelVersion: string | number) => {
    if (formData.model_version == modelVersion) return;
    formData.model_version = modelVersion;
    resetToneByModel();
    trimMsgByLimit();
};

// 视频预览相关方法
const previewVideo = (url: string) => {
    if (!url) return;
    showVideoPreview.value = true;
    previewVideoUrl.value = url;
};

// 音色相关方法

const isOriginalToneSelected = computed(() => {
    if (isChanjingOriginalTone.value) return true;
    return isOriginalVoice.value && !!formData.voice_id;
});

const openChooseTone = () => {
    if (!formData.model_version) {
        uni.$u.toast("请先选择驱动模型");
        openChooseModel();
        return;
    }
    showChooseTone.value = true;
};

const applyOriginalTone = (anchor?: AnchorItem) => {
    const voiceId = anchor?.extra_info?.shanjian_voice_id;
    if (voiceId) {
        formData.voice_id = voiceId;
        formData.voice_name = "形象原声";
    } else {
        formData.voice_id = "";
        formData.voice_name = "";
    }
    formData.voice_type = 1;
    return !!voiceId;
};

const handleChooseTone = (data: any) => {
    const { voice_id, name, builtin } = data || {};
    if (!voice_id) {
        formData.voice_id = "";
        formData.voice_name = "";
        formData.voice_type = 1;
    } else {
        if (builtin === 0) {
            formData.voice_type = 0;
        } else {
            formData.voice_type = 1;
        }
        formData.voice_id = voice_id;
        formData.voice_name = name;
        isOriginalVoice.value = false;
    }
    showChooseTone.value = false;
};

const handleChooseOriginalTone = () => {
    const anchor = anchorLists.value[currAnchorIndex.value];
    if (!anchor) {
        uni.$u.toast("请先选择形象");
        return;
    }
    // 蝉镜：视频原音
    if (formData.model_version == DigitalHumanModelVersionEnum.CHANJING) {
        isOriginalVoice.value = true;
        formData.voice_id = "-1";
        formData.voice_name = "";
        formData.voice_type = 1;
        chooseToneRef.value?.setChooseLists([]);
        return;
    }
    // 闪剪：形象原声
    if (!anchor.extra_info?.shanjian_voice_id) {
        uni.$u.toast("当前形象暂无原声，请选择其他音色");
        return;
    }
    isOriginalVoice.value = true;
    applyOriginalTone(anchor);
    chooseToneRef.value?.setChooseLists([]);
};

const handleSelectAgent = (res: any) => {
    const { data } = res;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/szr_ai_copywriter/szr_ai_copywriter",
        params: {
            limit: textLimit.value,
            agentData: JSON.stringify(data),
        },
    });
};

// 文案相关方法
const randomCopywriter = () => {
    if (!createVideoCopywriter.length) return;
    currCopywriterIndex.value = (currCopywriterIndex.value + 1) % createVideoCopywriter.length;
    formData.msg = createVideoCopywriter[currCopywriterIndex.value];
    formData.audio_type = CreateTypeEnum.TEXT;
};

const retryAudioTranscribe = async () => {
    if (!formData.audio_url) return;
    audioError.value = false;
    audioErrorMsg.value = "";
    audioLoading.value = true;
    formData.audio_type = CreateTypeEnum.AUDIO;
    try {
        const { message, audio_duration } = await lpSceneSpeechToText({
            audio: formData.audio_url,
        });
        formData.msg = message;
        formData.audio_duration = audio_duration;
    } catch (error: any) {
        audioError.value = true;
        audioErrorMsg.value = typeof error === "string" ? error : "转写失败，请检查网络后重试";
        formData.audio_type = CreateTypeEnum.AUDIO;
    } finally {
        audioLoading.value = false;
    }
};

const { uploadAndProcessFiles, showUploadProgress, uploadMaterialList } = useUpload({
    count: 1,
    fileAccept: ["mp3", "wav", "m4a", "MP3", "WAV", "M4A"],
    fileSize: 100,
    onSuccess: async (res: any) => {
        const { url } = res[0];
        formData.audio_type = CreateTypeEnum.AUDIO;
        showAudioType.value = false;
        audioLoading.value = true;
        audioError.value = false;
        audioErrorMsg.value = "";
        try {
            const { message, audio_duration } = await lpSceneSpeechToText({
                audio: url,
            });
            formData.msg = message;
            formData.audio_url = url;
            formData.audio_duration = audio_duration;
        } catch (error: any) {
            formData.audio_url = url;
            audioError.value = true;
            audioErrorMsg.value = typeof error === "string" ? error : "转写失败，请检查网络后重试";
        } finally {
            audioLoading.value = false;
        }
    },
});

const { play, pause, currentTime: currDuration, isPlaying, destroy } = useAudio();

const handlePlayAudio = () => {
    if (isPlaying.value) {
        pause();
    } else {
        play(formData.audio_url);
    }
};

const openRecorder = async () => {
    showAudioType.value = false;
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    showRecorder.value = true;
};

const recorderSuccess = (res: any) => {
    const { link, duration, message } = res;
    formData.msg = message;
    formData.audio_url = link;
    formData.audio_type = CreateTypeEnum.AUDIO;
    formData.audio_duration = duration / 1000;
    showRecorder.value = false;
};

const clearAudioData = () => {
    uni.showModal({
        title: "提示",
        content: "删除该音频后，将无法找回，确认删除？",
        success: (res: any) => {
            if (res.confirm) {
                formData.msg = "";
                formData.audio_url = "";
                formData.audio_type = CreateTypeEnum.TEXT;
                audioError.value = false;
                audioErrorMsg.value = "";
                destroy();
            }
        },
    });
};

// 算力规则相关方法
const openModelRule = () => {
    showModelRule.value = true;
};

// 协议相关方法
const agreeCreate = () => {
    Cache.set(DH_CREATE_AGREEMENT_KEY, "1");
    confirmCreate();
};

// 创建视频相关方法
const startCreate = () => {
    if (!canCreate.value) {
        if (currAnchorIndex.value === -1) {
            uni.$u.toast("请先选择形象");
            showChooseAnchor.value = true;
        } else if (!formData.model_version) {
            uni.$u.toast("请先选择驱动模型");
            openChooseModel();
        } else if (!isAudioCreate.value && !formData.voice_id && !isChanjingOriginalTone.value) {
            uni.$u.toast("请先选择音色");
            openChooseTone();
        } else if (isAudioCreate.value && audioLoading.value) {
            uni.$u.toast("音频正在转写，请稍后");
        } else if (isAudioCreate.value && audioError.value) {
            uni.$u.toast("音频转写失败，请重新处理");
        } else if (isAudioCreate.value && !formData.audio_url) {
            uni.$u.toast("请先上传音频");
        } else if (!formData.msg) {
            uni.$u.toast("请先输入视频文案");
        }
        return;
    }
    confirmCreate();
};

const confirmCreate = async () => {
    const closeAgreement = Cache.get(DH_CREATE_AGREEMENT_KEY);
    if (!closeAgreement) {
        showAgreement.value = true;
        return;
    }
    if (!isAudioCreate.value && formData.msg?.length > textLimit.value) {
        uni.$u.toast("文案超出限制，请重新编辑文案");
        return;
    }

    showAgreement.value = false;
    try {
        uni.showLoading({
            title: "正在生成",
            mask: true,
        });

        const engineType =
            formData.model_version == DigitalHumanModelVersionEnum.CHANJING
                ? SpeechEngineTypeEnum.CHANJING
                : SpeechEngineTypeEnum.SHANJIAN;
        await createShanjianSpeechTask(engineType);

        userStore.getUser();
        uni.hideLoading();
        showCreateSuccess.value = true;
        WechatOA.notify();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "生成失败",
            icon: "none",
            duration: 3000,
        });
    }
};

/** 蝉镜视频原音传空 voice，由服务端从形象视频克隆原音 */
const createShanjianSpeechTask = async (engineType: SpeechEngineTypeEnum = SpeechEngineTypeEnum.SHANJIAN) => {
    const {
        model_version,
        anchor_id,
        width,
        height,
        pic,
        anchor_ids: { shanjian_anchor_id, chanjing_anchor_id } = {} as any,
    } = anchorLists.value[currAnchorIndex.value];

    let anchorId = anchor_id;
    if (model_version === 0) {
        anchorId =
            engineType === SpeechEngineTypeEnum.CHANJING ? chanjing_anchor_id : shanjian_anchor_id;
    }
    const contentParams = isAudioCreate.value
        ? {
              audio: [
                  {
                      url: formData.audio_url,
                  },
              ],
          }
        : {
              copywriting: [
                  {
                      content: formData.msg,
                  },
              ],
          };
    const voiceId =
        formData.voice_id == "-1" || formData.voice_id == -1 ? "" : formData.voice_id;
    const extra: Record<string, any> = {
        video_count: formData.extra.video_count,
        volume: formData.extra.volume,
    };
    // 蝉镜引擎要求宽高
    if (engineType === SpeechEngineTypeEnum.CHANJING) {
        extra.width = width || formData.width;
        extra.height = height || formData.height;
    }
    await createShanjianTask({
        name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "数字人口播",
        shanjian_type: DIGITAL_HUMAN_SPEECH_SHANJIAN_TYPE,
        engine_type: engineType,
        ai_clip_enabled: clipConfig.is_open ? formData.ai_clip_enabled : 0,
        pic: pic || formData.pic,
        ...contentParams,
        anchor: [
            {
                anchor_id: anchorId,
            },
        ],
        voice: voiceId
            ? [
                  {
                      voice_id: voiceId,
                  },
              ]
            : [],
        extra,
    });
};

const getModelLists = async () => {
    const requestMode = currCloneMode.value;
    const isSwitchLoading = anchorListLoading.value;
    try {
        const { lists } = await getPublicAnchorList({
            page_size: 10,
            page_no: 1,
            status: 1,
            filter: 2,
            is_pro: cloneModeToIsPro(requestMode),
        });

        // 切换档位过程中丢弃旧请求结果，避免列表抖动/错乱
        if (requestMode !== currCloneMode.value) {
            return;
        }

        if (lists && lists.length) {
            const merged = isSwitchLoading ? [] : [...anchorLists.value];

            for (const newItem of lists) {
                const existIndex = merged.findIndex((item) => item.id === newItem.id);
                if (existIndex !== -1) {
                    merged[existIndex] = { ...merged[existIndex], ...newItem };
                } else {
                    merged.push(newItem);
                }
            }

            anchorLists.value = merged;

            if (currAnchorIndex.value === -1) {
                const index = merged.findIndex((item) => getAnchorStatus(item.status, item.source_type) == 1);
                if (index !== -1) {
                    chooseAnchor(index);
                } else {
                    chooseAnchor(0);
                }
            } else {
                const currentItem = anchorLists.value[currAnchorIndex.value];
                // 默认原声但此前未取到原声 id 时，数据更新后重新应用
                if (currentItem && isOriginalVoice.value && !formData.voice_id) {
                    applyOriginalTone(currentItem);
                }
            }

            const generatingAnchor = anchorLists.value.find(
                (item) => getAnchorStatus(item.status, item.source_type) == 0,
            );
            if (!generatingAnchor) {
                end();
            }
        } else if (isSwitchLoading) {
            anchorLists.value = [];
        }
    } finally {
        loading.value = false;
        if (requestMode === currCloneMode.value) {
            anchorListLoading.value = false;
        }
    }
};

const { start, end } = usePolling(getModelLists, {
    time: 3000,
});

const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
        params: {
            source: "1",
            type: 1,
        },
    });
};

const goHome = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/index/index",
        type: "redirect",
    });
};

onShow(() => {
    getClipConfigData();
    getModelLists();
    start();
});

onLoad(async (options: any) => {
    on("confirm", (result: any) => {
        const { type, data } = result;
        if (type === ListenerTypeEnum.CREATE_ANCHOR) {
            getModelLists();
        }
        if (type === ListenerTypeEnum.SZR_COPYWRITER) {
            formData.msg = data;
        }
        if (type === ListenerTypeEnum.AI_COPYWRITER) {
            formData.msg = data;
            if (formData.msg?.length > textLimit.value) {
                formData.msg = formData.msg.slice(0, textLimit.value);
            }
            formData.audio_type = CreateTypeEnum.TEXT;
        }
        if (type === ListenerTypeEnum.CHOOSE_STYLES) {
            // formData.styles = data;
        }
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            formData.music_url = data.url;
            formData.music_name = data.name;
        }
    });
});

onUnload(() => {
    destroy();
    end();
});
</script>

<style scoped lang="scss">
.tier-tab {
    @apply px-[28rpx] py-[18rpx] rounded-t-[26rpx] text-[27rpx] font-bold text-[#8A94A6] bg-[#E4E9F1];
}

.tier-tab--on {
    @apply bg-white text-[#2680F7] pt-[22rpx];
}

.skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 400% 100%;
    animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
@keyframes skeleton-shimmer {
    0% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

.rotate-anim {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.wave-anim {
    animation: wave 1s ease-in-out infinite alternate;
}
@keyframes wave {
    0% {
        height: 8rpx;
    }
    100% {
        height: 24rpx;
    }
}
</style>
