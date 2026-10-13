<template>
    <view class="flex flex-col h-screen bg-[#F3F5FA]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }"
            title="影视换脸改词"
            title-bold
        />

        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-[32rpx] pb-[220rpx]">
                    <play-template-card
                        :template="tpl"
                        :subtitle="tplSubtitle"
                        @change="handleBack"
                    />

                    <view class="mt-[32rpx]">
                        <play-section-title>{{
                            twoFaces ? '上传两位人物的正脸照' : '上传你的正脸照'
                        }}</play-section-title>
                    </view>
                    <view class="bg-white rounded-[24rpx] p-[24rpx] mt-[16rpx]">
                        <!-- 双人模板要两张：人物A 主角脸、人物B 配角脸 -->
                        <text
                            v-if="twoFaces"
                            class="text-[22rpx] font-semibold text-[#1D2129] block mb-[12rpx]"
                            >人物A（主角）</text
                        >
                        <play-image-upload
                            :preview-url="face.state.previewUrl"
                            :uploading="face.state.uploading"
                            :progress="face.state.progress"
                            :hint="`正脸、五官清晰、无遮挡；JPG/PNG，≤${FACE_IMAGE_MAX_MB}MB`"
                            @pick="face.pick"
                        />
                        <template v-if="twoFaces">
                            <text
                                class="text-[22rpx] font-semibold text-[#1D2129] block mt-[24rpx] mb-[12rpx]"
                                >人物B（配角）</text
                            >
                            <play-image-upload
                                :preview-url="face2.state.previewUrl"
                                :uploading="face2.state.uploading"
                                :progress="face2.state.progress"
                                :hint="`正脸、五官清晰、无遮挡；JPG/PNG，≤${FACE_IMAGE_MAX_MB}MB`"
                                @pick="face2.pick"
                            />
                        </template>
                        <view class="mt-[20rpx]"><play-consent-check v-model="consent" /></view>
                    </view>

                    <view class="flex items-center mt-[32rpx]">
                        <play-section-title>逐句改写台词</play-section-title>
                        <text
                            class="text-[19rpx] ml-[12rpx]"
                            :class="
                                script.speechOver.value
                                    ? 'text-[#EF4444] font-bold'
                                    : 'text-[#9CA3AF]'
                            "
                        >
                            预计口播 {{ script.speechSeconds.value }}s / {{ MAX_SPEECH_SECONDS }}s
                        </text>
                        <view
                            class="ml-auto flex items-center gap-[6rpx] px-[20rpx] py-[10rpx] rounded-full active:opacity-80 btn-blue"
                            @click="showPersona = true"
                        >
                            <image :src="PLAY_ICONS.sparkles" class="w-[22rpx] h-[22rpx]" />
                            <text class="text-[20rpx] font-bold text-white">一键替换台词</text>
                        </view>
                    </view>
                    <text class="text-[19rpx] text-[#9CA3AF] block mt-[6rpx]">{{
                        script.scriptHint.value
                    }}</text>
                    <view class="bg-white rounded-[24rpx] p-[24rpx] mt-[16rpx]">
                        <view v-if="script.lines.value.length === 0" class="py-[24rpx] text-center">
                            <text class="text-[22rpx] text-[#9CA3AF]"
                                >该模板没带台本，先写第一句 👇</text
                            >
                        </view>
                        <view v-for="(line, i) in script.lines.value" :key="i" class="mb-[18rpx]">
                            <view
                                class="flex items-start gap-[12rpx]"
                                :class="script.isRight(line, i) ? 'flex-row-reverse' : ''"
                            >
                                <view
                                    class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                    :class="
                                        script.isRight(line, i) ? 'bg-[#2680F7]' : 'bg-[#3A4558]'
                                    "
                                >
                                    <text class="text-[18rpx] font-bold text-white">{{
                                        (line.role || roleOf(i)).slice(0, 1)
                                    }}</text>
                                </view>
                                <view
                                    class="max-w-[78%]"
                                    :class="
                                        script.isRight(line, i) ? 'items-end flex flex-col' : ''
                                    "
                                >
                                    <view
                                        class="flex items-center gap-[8rpx]"
                                        :class="script.isRight(line, i) ? 'flex-row-reverse' : ''"
                                    >
                                        <text class="text-[18rpx] text-[#C0C6D0]"
                                            >{{ line.role || roleOf(i) }} · 第 {{ i + 1 }} 句</text
                                        >
                                        <view
                                            v-if="script.lines.value.length > 1"
                                            class="px-[8rpx] active:opacity-60"
                                            @click="script.removeLine(i)"
                                        >
                                            <text class="text-[18rpx] text-[#C0C6D0]">删除</text>
                                        </view>
                                    </view>
                                    <textarea
                                        v-model="line.text"
                                        class="rounded-[18rpx] p-[18rpx] text-[24rpx] text-[#1D2129] mt-[6rpx] min-h-[64rpx] w-[480rpx]"
                                        :class="[
                                            script.isRight(line, i)
                                                ? 'bg-[#EBF2FF]'
                                                : 'bg-[#F7F8FA]',
                                            script.isChanged(line, i) ? 'bub-changed' : ''
                                        ]"
                                        :maxlength="MAX_LINE_RUNES"
                                        auto-height
                                        placeholder="改成你的词…"
                                    />
                                    <text
                                        v-if="script.isChanged(line, i)"
                                        class="text-[17rpx] text-[#2680F7] mt-[4rpx]"
                                        >已改写</text
                                    >
                                </view>
                            </view>
                        </view>
                        <view class="flex gap-[16rpx] mt-[4rpx]">
                            <view
                                v-if="script.lines.value.length < MAX_LINES"
                                class="flex-1 py-[14rpx] rounded-[16rpx] border-[3rpx] border-dashed border-[#C9D2E0] text-center active:opacity-70"
                                @click="script.addLine"
                            >
                                <text class="text-[22rpx] text-[#4B5563]">+ 加一句</text>
                            </view>
                            <view
                                v-if="script.originalLines.value.length"
                                class="flex-1 py-[14rpx] rounded-[16rpx] bg-[#F4F6FA] text-center active:opacity-70"
                                @click="script.resetScript(tpl)"
                            >
                                <text class="text-[22rpx] text-[#4B5563]">恢复原片台词</text>
                            </view>
                        </view>
                    </view>

                    <view class="mt-[32rpx]"
                        ><play-section-title>配音音色</play-section-title></view
                    >
                    <view class="mt-[16rpx]"><play-voice-picker v-model="voice" /></view>

                    <view
                        class="bg-[#FFFBEB] rounded-[24rpx] p-[24rpx] mt-[32rpx] flex gap-[14rpx]"
                    >
                        <image
                            :src="PLAY_ICONS.shieldAlert"
                            class="w-[30rpx] h-[30rpx] flex-shrink-0"
                        />
                        <text class="text-[20rpx] text-[#B45309] leading-relaxed flex-1">{{
                            LEGAL_TEXT[PlayKeyEnum.FACE]
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
        <tokens-cost v-model="showTokensCost" :type="MontageTypeEnum.PLAY_FACE" />
        <play-persona-popup
            v-model="showPersona"
            :applied="script.appliedPersona.value"
            :rewriting="script.rewriting.value"
            @apply="handleApplyPersona"
        />
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
import PlayPersonaPopup from '../../components/play-video/popups/play-persona-popup.vue'
import { MontageTypeEnum } from '@/enums/digitalHuman'
import {
    FACE_IMAGE_MAX_MB,
    LEGAL_TEXT,
    MAX_LINES,
    MAX_LINE_RUNES,
    MAX_SPEECH_SECONDS,
    PLAY_ICONS,
    PlayKeyEnum,
    VOICES
} from '../../enums/play_video'
import { usePlayFaceUpload } from '../../hooks/usePlayFaceUpload'
import { usePlayLines } from '../../hooks/usePlayLines'
import { usePlayVideoGen } from '../../hooks/usePlayVideoGen'
import {
    buildTemplateParam,
    getCurrentTemplate,
    roleOf,
    type PlayPersona,
    type PlayTemplate
} from '../../utils/play_video'

const tpl = ref<PlayTemplate | null>(null)
const voice = ref(VOICES[0])
const consent = ref(false)
const showTokensCost = ref(false)
const showPersona = ref(false)
const face = usePlayFaceUpload()
const face2 = usePlayFaceUpload()
// 双人模板（face_count>=2）要两张脸
const twoFaces = computed(() => (tpl.value?.face_count || 1) >= 2)
const script = usePlayLines()
const gen = usePlayVideoGen(PlayKeyEnum.FACE)

const tplSubtitle = computed(() => {
    const parts = [(tpl.value?.face_count || 1) >= 2 ? '双人对白' : '单人片段']
    if (tpl.value?.duration_sec) parts.push(`${tpl.value.duration_sec}s`)
    if (script.originalLines.value.length) parts.push(`${script.originalLines.value.length} 句台词`)
    return parts.join(' · ')
})

const handleBack = () => uni.navigateBack()

const handleApplyPersona = async (p: PlayPersona) => {
    const ok = await script.applyPersona(p, tpl.value?.name || '')
    if (ok) showPersona.value = false
}

const handleSubmit = async (retry: boolean) => {
    if (!tpl.value) return uni.$u.toast('模板丢失，请返回重选')
    if (face.state.uploading || face2.state.uploading)
        return uni.$u.toast('照片还在上传中，稍等一下')
    if (face.state.fileId <= 0)
        return uni.$u.toast(twoFaces.value ? '请先上传人物A的正脸照' : '请先上传你的正脸照')
    if (twoFaces.value && face2.state.fileId <= 0) return uni.$u.toast('请先上传人物B的正脸照')
    if (!consent.value) return uni.$u.toast('请先勾选「本人授权」')
    const lines = script.validate()
    if (!lines) return
    await gen
        .start(
            {
                consent: 1,
                template: buildTemplateParam(tpl.value, { allow_bg_replace: false }),
                face_file_id: face.state.fileId,
                ...(twoFaces.value ? { face_file_id2: face2.state.fileId } : {}),
                lines,
                voice: voice.value
            },
            retry
        )
        .catch(() => undefined) // 提交失败已在 hook 内 toast
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
        return
    }
    script.fillFromTemplate(tpl.value)
})
</script>

<style lang="scss" scoped>
.bub-changed {
    box-shadow: inset 0 0 0 3rpx #93c5fd;
}
.btn-blue {
    background: linear-gradient(90deg, #2680f7, #3e9bff);
}
</style>
