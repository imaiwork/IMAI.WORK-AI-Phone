<template>
    <view class="flex flex-col h-screen bg-[#F3F5FA]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }"
            title="跳舞换脸"
            title-bold
        />

        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-[32rpx] pb-[220rpx]">
                    <play-template-card
                        :template="tpl"
                        :subtitle="tplSubtitle"
                        @change="handleBack"
                    >
                        <template #cover>
                            <!-- 示意图：脸框随上传状态变化（原型交互） -->
                            <view
                                class="w-[200rpx] h-[280rpx] rounded-[20rpx] overflow-hidden relative flex-shrink-0 pv-cover"
                            >
                                <image
                                    v-if="tpl?.cover_url"
                                    :src="tpl.cover_url"
                                    class="absolute inset-0 w-full h-full opacity-60"
                                    mode="aspectFill"
                                />
                                <view
                                    class="absolute inset-0 flex flex-col items-center justify-center"
                                >
                                    <view
                                        class="w-[88rpx] h-[88rpx] rounded-full flex items-center justify-center overflow-hidden"
                                        :class="
                                            face.state.previewUrl
                                                ? 'border-[4rpx] border-solid border-[#3E9BFF]'
                                                : 'border-[3rpx] border-dashed border-[rgba(255,255,255,0.6)]'
                                        "
                                    >
                                        <image
                                            v-if="face.state.previewUrl"
                                            :src="face.state.previewUrl"
                                            class="w-full h-full"
                                            mode="aspectFill"
                                        />
                                        <text
                                            v-else
                                            class="text-[18rpx] text-[rgba(255,255,255,0.8)]"
                                            >原脸</text
                                        >
                                    </view>
                                    <text
                                        class="text-[18rpx] text-[rgba(255,255,255,0.85)] mt-[10rpx]"
                                        >{{ face.state.previewUrl ? '你的脸' : '待替换' }}</text
                                    >
                                    <text
                                        class="text-[16rpx] text-[rgba(255,255,255,0.55)] mt-[20rpx]"
                                        >{{ bgLabel }}</text
                                    >
                                </view>
                            </view>
                        </template>
                    </play-template-card>

                    <view class="mt-[32rpx]"
                        ><play-section-title>上传你的正脸照</play-section-title></view
                    >
                    <view class="bg-white rounded-[24rpx] p-[24rpx] mt-[16rpx]">
                        <play-image-upload
                            :preview-url="face.state.previewUrl"
                            :uploading="face.state.uploading"
                            :progress="face.state.progress"
                            :hint="`正脸、五官清晰、无遮挡；JPG/PNG，≤${FACE_IMAGE_MAX_MB}MB`"
                            @pick="face.pick"
                        />
                        <view class="mt-[20rpx]"><play-consent-check v-model="consent" /></view>
                    </view>

                    <template v-if="tpl?.allow_bg_replace">
                        <view class="mt-[32rpx]"
                            ><play-section-title>视频背景</play-section-title></view
                        >
                        <view class="flex gap-[16rpx] mt-[16rpx]">
                            <view
                                v-for="m in BG_MODES"
                                :key="m.key"
                                class="flex-1 py-[18rpx] rounded-[18rpx] border-[3rpx] text-center"
                                :class="
                                    bgMode === m.key
                                        ? 'bg-[#EBF2FF] border-[#2680F7]'
                                        : 'bg-white border-[transparent]'
                                "
                                @click="bgMode = m.key"
                            >
                                <text
                                    class="text-[22rpx] font-semibold"
                                    :class="bgMode === m.key ? 'text-[#2680F7]' : 'text-[#8A93A3]'"
                                    >{{ m.name }}</text
                                >
                            </view>
                        </view>
                        <view
                            v-if="bgMode === BgModeEnum.UPLOAD"
                            class="bg-white rounded-[24rpx] p-[24rpx] mt-[16rpx]"
                        >
                            <play-image-upload
                                :preview-url="bg.state.previewUrl"
                                :uploading="bg.state.uploading"
                                :progress="bg.state.progress"
                                hint="上传你的门店/场景照片，人物会跳进这个背景里"
                                placeholder="上传背景图"
                                @pick="bg.pick"
                            />
                        </view>
                    </template>

                    <view class="flex items-center mt-[32rpx]">
                        <play-section-title>片尾口播</play-section-title>
                        <view class="ml-auto"
                            ><u-switch v-model="speechOn" size="40" active-color="#2680F7"
                        /></view>
                    </view>
                    <text class="text-[19rpx] text-[#9CA3AF] block mt-[6rpx] leading-relaxed">
                        开启后会对口型配音引导关注/进店。<text class="text-[#F59E0B]"
                            >注意：成片时长会跟着这句话走</text
                        >，话短片子就短，想保留完整舞蹈就关掉它
                    </text>
                    <view v-if="speechOn" class="bg-white rounded-[24rpx] p-[24rpx] mt-[16rpx]">
                        <textarea
                            v-model="speechText"
                            class="w-full bg-[#F7F8FA] rounded-[16rpx] p-[20rpx] text-[24rpx] text-[#1D2129] min-h-[88rpx]"
                            :maxlength="MAX_SPEECH_RUNES"
                            auto-height
                            placeholder="例：关注我，明天教你下一支"
                        />
                        <text class="text-[18rpx] text-[#C0C6D0] block text-right mt-[6rpx]"
                            >至少 {{ MIN_SPEECH_RUNES }} 字 · {{ speechText.length }}/{{
                                MAX_SPEECH_RUNES
                            }}</text
                        >
                        <view class="mt-[16rpx]"><play-voice-picker v-model="voice" /></view>
                        <text class="text-[19rpx] text-[#F59E0B] block mt-[16rpx]"
                            >提示：开口播后成片时长以口播音频为准</text
                        >
                    </view>

                    <view
                        class="bg-[#FFFBEB] rounded-[24rpx] p-[24rpx] mt-[32rpx] flex gap-[14rpx]"
                    >
                        <image
                            :src="PLAY_ICONS.shieldAlert"
                            class="w-[30rpx] h-[30rpx] flex-shrink-0"
                        />
                        <text class="text-[20rpx] text-[#B45309] leading-relaxed flex-1">{{
                            LEGAL_TEXT[PlayKeyEnum.DANCE]
                        }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <play-bottom-bar
            text="生成同款"
            :loading="isSubmitting"
            @cost="showTokensCost = true"
            @submit="handleSubmitLock(false)"
        />
        <tokens-cost v-model="showTokensCost" :type="MontageTypeEnum.PLAY_DANCE" />
        <play-gen-modal
            :state="gen.state"
            title="正在换脸生成…"
            @background="gen.background"
            @done="handleDone"
            @again="handleSubmitLock(true)"
            @close="gen.close"
            @retry="handleSubmitLock(true)"
        />
    </view>
</template>

<script setup lang="ts">
import { onLoad } from '@dcloudio/uni-app'
import { useLockFn } from '@/hooks/useLockFn'
import TokensCost from '@/components/tokens-cost/tokens-cost.vue'
import PlaySectionTitle from '../../components/play-video/play-section-title.vue'
import PlayTemplateCard from '../../components/play-video/play-template-card.vue'
import PlayImageUpload from '../../components/play-video/play-image-upload.vue'
import PlayConsentCheck from '../../components/play-video/play-consent-check.vue'
import PlayVoicePicker from '../../components/play-video/play-voice-picker.vue'
import PlayBottomBar from '../../components/play-video/play-bottom-bar.vue'
import PlayGenModal from '../../components/play-video/play-gen-modal.vue'
import { MontageTypeEnum } from '@/enums/digitalHuman'
import {
    BG_MODES,
    BgModeEnum,
    FACE_IMAGE_MAX_MB,
    LEGAL_TEXT,
    MAX_SPEECH_RUNES,
    MIN_SPEECH_RUNES,
    PLAY_ICONS,
    PlayKeyEnum,
    VOICES
} from '../../enums/play_video'
import { usePlayFaceUpload } from '../../hooks/usePlayFaceUpload'
import { usePlayVideoGen } from '../../hooks/usePlayVideoGen'
import { buildTemplateParam, getCurrentTemplate, type PlayTemplate } from '../../utils/play_video'

const tpl = ref<PlayTemplate | null>(null)
const consent = ref(false)
const bgMode = ref<BgModeEnum>(BgModeEnum.KEEP)
const speechOn = ref(false)
const speechText = ref('')
const voice = ref(VOICES[0])
const showTokensCost = ref(false)
const face = usePlayFaceUpload()
const bg = usePlayFaceUpload()
const gen = usePlayVideoGen(PlayKeyEnum.DANCE)

const tplSubtitle = computed(() => {
    const parts = [tpl.value?.category || '热舞']
    if (tpl.value?.duration_sec) parts.push(`${tpl.value.duration_sec}s`)
    parts.push('自带 BGM')
    return parts.join(' · ')
})

const bgLabel = computed(() =>
    !tpl.value?.allow_bg_replace || bgMode.value === BgModeEnum.KEEP ? '原视频背景' : '自定义背景'
)

const handleBack = () => uni.navigateBack()

const handleSubmit = async (retry: boolean) => {
    if (!tpl.value) return uni.$u.toast('模板丢失，请返回重选')
    if (face.state.uploading || bg.state.uploading) return uni.$u.toast('图片还在上传中，稍等一下')
    if (face.state.fileId <= 0) return uni.$u.toast('请先上传你的正脸照')
    if (!consent.value) return uni.$u.toast('请先勾选「本人授权」')
    const mode = tpl.value.allow_bg_replace ? bgMode.value : BgModeEnum.KEEP
    if (mode === BgModeEnum.UPLOAD && bg.state.fileId <= 0) return uni.$u.toast('请上传背景图')
    const speech = speechOn.value ? speechText.value.trim() : ''
    if (speechOn.value && !speech) return uni.$u.toast('口播开着但还没填，写一句或关掉开关')
    if (speech && speech.length < MIN_SPEECH_RUNES)
        return uni.$u.toast(`口播至少 ${MIN_SPEECH_RUNES} 个字，太短无法对口型`)

    const params: Record<string, any> = {
        consent: 1,
        template: buildTemplateParam(tpl.value, { face_count: 1 }),
        face_file_id: face.state.fileId,
        bg_mode: mode,
        speech_text: speech
    }
    if (mode === BgModeEnum.UPLOAD) params.bg_file_id = bg.state.fileId
    if (speech) params.voice = voice.value
    await gen.start(params, retry).catch(() => undefined)
}
const { lockFn: handleSubmitLock, isLock: isSubmitting } = useLockFn(handleSubmit)

const handleDone = () => {
    gen.close()
    uni.navigateBack()
}

onLoad(() => {
    tpl.value = getCurrentTemplate()
    if (!tpl.value) {
        uni.$u.toast('请先选择模板')
        setTimeout(() => uni.navigateBack(), 600)
    }
})
</script>

<style lang="scss" scoped>
.pv-cover {
    background: linear-gradient(160deg, #38294a, #160e22);
}
</style>
