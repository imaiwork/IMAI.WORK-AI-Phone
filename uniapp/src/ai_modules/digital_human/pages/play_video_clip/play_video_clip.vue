<template>
    <view class="flex flex-col h-screen bg-[#F3F5FA]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }"
            title="影视引流拼接"
            title-bold
        />

        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-[32rpx] pb-[220rpx]">
                    <play-template-card
                        :template="tpl"
                        label="吸睛切片（平台提供）"
                        :subtitle="tplSubtitle"
                        @change="handleBack"
                    />

                    <view class="mt-[32rpx]"
                        ><play-section-title>上传你的视频</play-section-title></view
                    >
                    <text class="text-[19rpx] text-[#9CA3AF] block mt-[6rpx]"
                        >切片播完后接上它，整段播完成片就多长（两段都保留原声）</text
                    >
                    <view class="bg-white rounded-[24rpx] p-[24rpx] mt-[16rpx]">
                        <view
                            class="rounded-[20rpx] flex flex-col items-center justify-center py-[36rpx]"
                            :class="
                                video.uri || showUploadProgress
                                    ? 'bg-[#F5F9FF] border-[3rpx] border-solid border-[#2680F7]'
                                    : 'border-[3rpx] border-dashed border-[#C9D2E0] bg-[#FAFBFD]'
                            "
                            @click="handlePickVideo"
                        >
                            <!-- 上传中优先于已传态：否则重新上传时界面一直停在上一个视频上，全程无感 -->
                            <template v-if="showUploadProgress">
                                <text class="text-[24rpx] font-semibold text-[#2680F7]"
                                    >上传中 {{ uploadProgress }}%</text
                                >
                                <text class="text-[19rpx] text-[#9CA3AF] mt-[6rpx]"
                                    >完成前请勿离开页面</text
                                >
                            </template>
                            <template v-else-if="video.uri">
                                <!-- 勾用图标不用 ✓ 字符：部分真机字体缺这个字形，回退出来是一道斜杠；
                                     文件名不上 line-clamp（-webkit-box 在真机会把文本盒撑出卡片外） -->
                                <view
                                    class="flex items-center justify-center gap-[8rpx] max-w-full px-[20rpx]"
                                >
                                    <u-icon
                                        name="checkmark-circle-fill"
                                        color="#2680F7"
                                        size="30"
                                    />
                                    <text
                                        class="text-[24rpx] font-semibold text-[#2680F7] text-center break-all"
                                        >{{ displayName }}</text
                                    >
                                </view>
                                <text class="text-[19rpx] text-[#9CA3AF] mt-[6rpx]"
                                    >点击可重新上传</text
                                >
                            </template>
                            <template v-else>
                                <text class="text-[40rpx] text-[#C9D2E0]">+</text>
                                <text class="text-[21rpx] text-[#9CA3AF] mt-[6rpx]"
                                    >选择视频（≤{{ CLIP_VIDEO_MAX_MB }}MB）</text
                                >
                            </template>
                        </view>
                    </view>

                    <!-- 成片时长和算力都跟用户视频长度走，传完就把预估亮出来，别等到扣完才知道 -->
                    <text
                        v-if="video.uri && video.duration"
                        class="text-[19rpx] text-[#6B7280] block mt-[10rpx]"
                        >成片约 {{ estSec }} 秒{{
                            estTokens ? ` · 预计消耗约 ${estTokens} 算力` : ''
                        }}，按实际时长多退少补</text
                    >

                    <view class="mt-[32rpx]"
                        ><play-section-title>切片保留时长</play-section-title></view
                    >
                    <view class="flex gap-[16rpx] mt-[16rpx]">
                        <view
                            v-for="s in KEEP_OPTIONS"
                            :key="s"
                            class="flex-1 py-[18rpx] rounded-[18rpx] border-[3rpx] text-center"
                            :class="[
                                keepSec === s
                                    ? 'bg-[#EBF2FF] border-[#2680F7]'
                                    : 'bg-white border-[transparent]',
                                keepDisabled(s) ? 'opacity-40' : ''
                            ]"
                            @click="handlePickKeep(s)"
                        >
                            <text
                                class="text-[24rpx] font-bold"
                                :class="keepSec === s ? 'text-[#2680F7]' : 'text-[#4B5563]'"
                                >{{ s }}s</text
                            >
                        </view>
                    </view>

                    <view class="mt-[32rpx]"
                        ><play-section-title>拼接转场</play-section-title></view
                    >
                    <view class="flex gap-[16rpx] mt-[16rpx]">
                        <view
                            v-for="t in TRANSITIONS"
                            :key="t.key"
                            class="flex-1 py-[18rpx] rounded-[18rpx] border-[3rpx] text-center"
                            :class="
                                transition === t.key
                                    ? 'bg-[#EBF2FF] border-[#2680F7]'
                                    : 'bg-white border-[transparent]'
                            "
                            @click="transition = t.key"
                        >
                            <text
                                class="text-[22rpx] font-semibold"
                                :class="transition === t.key ? 'text-[#2680F7]' : 'text-[#8A93A3]'"
                                >{{ t.name }}</text
                            >
                            <text class="text-[17rpx] text-[#C0C6D0] block mt-[2rpx]">{{
                                t.desc
                            }}</text>
                        </view>
                    </view>

                    <view class="bg-white rounded-[24rpx] p-[24rpx] mt-[32rpx]">
                        <view class="flex items-center">
                            <view>
                                <text class="text-[24rpx] font-semibold text-[#1F2937] block"
                                    >拼接重音音效</text
                                >
                                <text class="text-[19rpx] text-[#9CA3AF] mt-[4rpx] block"
                                    >切换瞬间一记低频重击，强化卡点</text
                                >
                            </view>
                            <view class="ml-auto"
                                ><u-switch v-model="sfxOn" size="40" active-color="#2680F7"
                            /></view>
                        </view>
                        <view class="h-[2rpx] bg-[#F0F2F5] my-[20rpx]" />
                        <view class="flex items-center">
                            <view>
                                <text class="text-[24rpx] font-semibold text-[#1F2937] block"
                                    >钩子文案</text
                                >
                                <text class="text-[19rpx] text-[#9CA3AF] mt-[4rpx] block"
                                    >压在切片结尾 2.5~4.5 秒（按字数自动延长），把人勾进你的视频</text
                                >
                            </view>
                            <view class="ml-auto"
                                ><u-switch v-model="hookOn" size="40" active-color="#2680F7"
                            /></view>
                        </view>
                        <input
                            v-if="hookOn"
                            v-model="hookText"
                            class="w-full bg-[#F7F8FA] rounded-[16rpx] px-[20rpx] py-[18rpx] text-[24rpx] mt-[16rpx]"
                            :maxlength="MAX_HOOK_RUNES"
                            placeholder="例：看到最后我人傻了…"
                        />
                    </view>

                    <view class="bg-white rounded-[24rpx] p-[24rpx] mt-[32rpx]">
                        <view class="flex h-[52rpx] rounded-[12rpx] overflow-hidden">
                            <view class="flex items-center justify-center w-[38%] clip-bar">
                                <text class="text-[19rpx] text-white font-bold"
                                    >切片 {{ keepSec }}s</text
                                >
                            </view>
                            <view class="flex-1 flex items-center justify-center bg-[#EBF2FF]">
                                <text class="text-[19rpx] text-[#2680F7] font-bold"
                                    >你的视频（整段播完）</text
                                >
                            </view>
                        </view>
                        <text class="text-[19rpx] text-[#9CA3AF] block mt-[14rpx] leading-relaxed">
                            前 {{ keepSec }} 秒放切片{{
                                transitionName
                            }}后接你的视频完整播完（两段都保留原声）；你的视频多长成片就多长
                        </text>
                    </view>

                    <view
                        class="bg-[#FFFBEB] rounded-[24rpx] p-[24rpx] mt-[32rpx] flex gap-[14rpx]"
                    >
                        <image
                            :src="PLAY_ICONS.shieldAlert"
                            class="w-[30rpx] h-[30rpx] flex-shrink-0"
                        />
                        <text class="text-[20rpx] text-[#B45309] leading-relaxed flex-1">{{
                            LEGAL_TEXT[PlayKeyEnum.CLIP]
                        }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <play-bottom-bar
            text="合成视频"
            :loading="isSubmitting"
            @cost="showTokensCost = true"
            @submit="handleSubmitLock(false)"
        />
        <upload-progress
            v-if="showUploadProgress"
            v-model="showUploadProgress"
            :upload-list="uploadMaterialList"
        />
        <tokens-cost v-model="showTokensCost" :type="MontageTypeEnum.PLAY_SPLICE" />
        <play-gen-modal
            :state="gen.state"
            title="正在拼接合成…"
            @background="gen.background"
            @done="handleDone"
            @again="handleDone"
            @close="gen.close"
            @retry="handleSubmitLock(true)"
        />
    </view>
</template>

<script setup lang="ts">
import { onLoad } from '@dcloudio/uni-app'
import useUpload from '@/hooks/useUpload'
import { useLockFn } from '@/hooks/useLockFn'
import TokensCost from '../../components/tokens-cost/tokens-cost.vue'
import PlaySectionTitle from '../../components/play-video/play-section-title.vue'
import PlayTemplateCard from '../../components/play-video/play-template-card.vue'
import PlayBottomBar from '../../components/play-video/play-bottom-bar.vue'
import PlayGenModal from '../../components/play-video/play-gen-modal.vue'
import { MontageTypeEnum } from '../../enums'
import { TokensSceneEnum } from '@/enums/appEnums'
import { useUserStore } from '@/stores/user'
import {
    CLIP_VIDEO_ACCEPT,
    CLIP_VIDEO_DURATION,
    CLIP_VIDEO_MAX_MB,
    DEFAULT_HOOK_TEXT,
    KEEP_OPTIONS,
    KEEP_SEC_DEFAULT,
    LEGAL_TEXT,
    MAX_HOOK_RUNES,
    PLAY_ICONS,
    PlayKeyEnum,
    TRANSITIONS,
    TransitionEnum
} from '../../enums/play_video'
import { usePlayVideoGen } from '../../hooks/usePlayVideoGen'
import { buildTemplateParam, getCurrentTemplate, type PlayTemplate } from '../../utils/play_video'

/** 文件名过长时首尾各保留的字符数 */
const NAME_KEEP_CHARS = 9

const tpl = ref<PlayTemplate | null>(null)
const video = reactive({ uri: '', name: '', duration: 0 })
const userStore = useUserStore()
const keepSec = ref(KEEP_SEC_DEFAULT)
const transition = ref<TransitionEnum>(TransitionEnum.NONE)
const sfxOn = ref(true)
const hookOn = ref(true)
const hookText = ref(DEFAULT_HOOK_TEXT)
const showTokensCost = ref(false)
const gen = usePlayVideoGen(PlayKeyEnum.CLIP)

// 用户视频走通用上传 hook（素材通道 /upload/video），站长按 uri/url 直读
const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    count: 1,
    videoAccept: CLIP_VIDEO_ACCEPT,
    videoSize: CLIP_VIDEO_MAX_MB,
    videoDuration: CLIP_VIDEO_DURATION,
    onSuccess: (materials) => {
        const m = materials[0]
        video.uri = m.url
        video.name = m.name || '已上传视频'
        video.duration = Number(m.duration) || 0
        // 文件名是一串哈希，换成新视频后那行字看着几乎没变，必须给一句明确回执
        uni.$u.toast('视频上传成功')
    }
})
const uploadProgress = computed(() => uploadMaterialList.value[0]?.progress ?? 0)
// 上传回来的是一串 40 多位的哈希文件名，整串塞进这一行会被截断成只剩省略号，
// 按「首尾各留一截、中间省略」压到一行放得下，至少能看出换没换视频
const displayName = computed(() => {
    const name = video.name || '已上传视频'
    return name.length > NAME_KEEP_CHARS * 2
        ? `${name.slice(0, NAME_KEEP_CHARS)}…${name.slice(-NAME_KEEP_CHARS)}`
        : name
})
const handlePickVideo = async () => {
    if (showUploadProgress.value) return
    await uploadAndProcessFiles('video')
    // 通用 hook 里上传失败的提示是注释掉的：不自己兜一句，重新上传失败后界面
    // 还停在上一个视频上，用户会以为已经换成新的了
    if (uploadMaterialList.value.some((item) => item.status === 'fail')) {
        uni.$u.toast(video.uri ? '上传失败，仍在使用上一个视频' : '视频上传失败，请重试')
    }
}

// 预估成片 = 切片保留 + 用户视频整段；单价取算力配置里 play_splice 的秒价（没配就只显示时长）
const estSec = computed(() => Math.ceil(keepSec.value + video.duration))
const estTokens = computed(() => {
    const score = Number(userStore.getTokenByScene(TokensSceneEnum.PLAY_SPLICE)?.score) || 0
    return score > 0 ? Math.ceil(estSec.value * score * 100) / 100 : 0
})

const tplSubtitle = computed(() => {
    const parts = [tpl.value?.category || '影视剧']
    if (tpl.value?.duration_sec) parts.push(`素材共 ${tpl.value.duration_sec}s`)
    return parts.join(' · ')
})
const transitionName = computed(() => {
    const t = TRANSITIONS.find((x) => x.key === transition.value)
    return t && t.key !== TransitionEnum.NONE ? `「${t.name}」` : '硬切'
})
// 档位超过切片素材时长则禁用（原型行为）
const keepDisabled = (s: number) => !!tpl.value?.duration_sec && s > (tpl.value.duration_sec || 0)
const handlePickKeep = (s: number) => {
    if (!keepDisabled(s)) keepSec.value = s
}

const handleBack = () => uni.navigateBack()

const handleSubmit = async (retry: boolean) => {
    if (!tpl.value) return uni.$u.toast('模板丢失，请返回重选')
    if (showUploadProgress.value) return uni.$u.toast('视频还在上传中，稍等一下')
    if (!video.uri) return uni.$u.toast('请先上传你的视频')
    const hook = hookOn.value ? hookText.value.trim() : ''
    if (hookOn.value && !hook) return uni.$u.toast('钩子文案开着但还没填，写一句或关掉开关')
    await gen
        .start(
            {
                consent: 1,
                template: buildTemplateParam(tpl.value),
                product_video_url: video.uri,
                product_video_sec: video.duration,
                clip_keep_sec: keepSec.value,
                transition: transition.value,
                splice_sfx: sfxOn.value ? 1 : 0,
                hook_text: hook
            },
            retry
        )
        .catch(() => undefined)
}
const { lockFn: handleSubmitLock, isLock: isSubmitting } = useLockFn(handleSubmit)

// 拼接是确定性合成，同样的素材再提只会出一模一样的片：「再生成一条」不原参数重提，
// 与「完成」一样回外层爆款玩法页重选模板/视频（换脸、跳舞出片有随机性，仍走原参数重提）
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
    if (keepDisabled(keepSec.value)) {
        const ok = KEEP_OPTIONS.filter((s) => !keepDisabled(s))
        if (ok.length) keepSec.value = ok[ok.length - 1]
    }
})
</script>

<style lang="scss" scoped>
.clip-bar {
    background: linear-gradient(90deg, #2b3a5c, #3d4a63);
}
</style>
