<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                    >
                        <view
                            class="flex items-center px-[28rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                        >
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[30rpx] font-bold text-[#0D1117]"
                                    >请说说您的想法</text
                                >
                                <text class="text-[22rpx] text-[#EF4444] font-bold">*</text>
                            </view>
                        </view>
                        <view class="px-[28rpx] pt-[20rpx] pb-[8rpx]">
                            <textarea
                                class="w-full text-[28rpx] text-[#0D1117] leading-relaxed"
                                v-model="contentVal"
                                :style="{ height: '320rpx' }"
                                placeholder="例如：一个女生在海边喝咖啡，杯子就是我上传的那只"
                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                :maxlength="MAX_TEXT_LENGTH"
                            />
                        </view>
                        <view class="flex justify-end px-[28rpx] pb-[20rpx]">
                            <text
                                class="text-[22rpx]"
                                :class="
                                    contentVal.length >= MAX_TEXT_LENGTH
                                        ? 'text-[#EF4444] font-bold'
                                        : 'text-[#C0C4CC]'
                                "
                            >
                                {{ contentVal.length }} / {{ MAX_TEXT_LENGTH }}
                            </text>
                        </view>

                        <view class="px-[28rpx] pb-[24rpx]">
                            <view
                                class="flex items-start gap-[12rpx] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#D6E4FF] bg-[#F0F6FF]"
                            >
                                <u-icon
                                    name="info-circle"
                                    color="#0065fb"
                                    size="26"
                                    class="flex-shrink-0 mt-[2rpx]"
                                />
                                <view class="flex-1">
                                    <text class="text-[24rpx] text-[#1D4ED8] font-bold">{{
                                        infoText
                                    }}</text>
                                    <text
                                        class="block text-[22rpx] text-[#4B5563] leading-relaxed mt-[4rpx]"
                                    >
                                        镜头数、口播字数已按所选时长算好，AI 会严格照这个规格写{{
                                            materialsText
                                        }}。
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="px-[28rpx] pb-[28rpx]">
                            <view class="flex items-center gap-[8rpx] mb-[16rpx]">
                                <text class="font-bold text-[#0D1117] text-[26rpx]">模式</text>
                            </view>
                            <view class="flex items-center gap-[16rpx]">
                                <view
                                    v-for="item in MODE_OPTIONS"
                                    :key="item.value"
                                    class="flex-1 h-[96rpx] flex flex-col items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        mode === item.value
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="mode = item.value"
                                >
                                    <text
                                        class="font-bold text-[26rpx]"
                                        :class="
                                            mode === item.value ? 'text-primary' : 'text-[#9CA3AF]'
                                        "
                                    >
                                        {{ item.name }}
                                    </text>
                                    <text
                                        class="text-[20rpx] mt-[2rpx]"
                                        :class="
                                            mode === item.value
                                                ? 'text-[#0065fb]/60'
                                                : 'text-[#C0C4CC]'
                                        "
                                    >
                                        {{ item.desc }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        v-for="(item, index) in chatContentList"
                        :key="item.id ?? index"
                        class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                    >
                        <view v-if="item.status === 'pending'" class="flex">
                            <view
                                class="w-[6rpx] flex-shrink-0 bg-[#E5E9F0] rounded-l-[24rpx] skeleton-bar"
                            />
                            <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[24rpx]">
                                <view class="flex items-center gap-[10rpx] mb-[20rpx]">
                                    <view class="skeleton w-[40rpx] h-[40rpx] rounded-full" />
                                    <view class="skeleton h-[24rpx] w-[120rpx] rounded-full" />
                                </view>
                                <view class="flex flex-col gap-[12rpx]">
                                    <view class="skeleton h-[26rpx] w-full rounded-full" />
                                    <view class="skeleton h-[26rpx] w-[88%] rounded-full" />
                                    <view class="skeleton h-[26rpx] w-[72%] rounded-full" />
                                    <view class="skeleton h-[26rpx] w-[80%] rounded-full" />
                                    <view class="skeleton h-[26rpx] w-[60%] rounded-full" />
                                </view>
                            </view>
                        </view>

                        <view v-else class="flex">
                            <view class="w-[6rpx] flex-shrink-0 bg-primary rounded-l-[24rpx]" />
                            <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[18rpx]">
                                <view class="flex items-center justify-between mb-[16rpx]">
                                    <view class="flex items-center gap-[10rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0"
                                        >
                                            <text class="text-[22rpx] font-bold text-primary">
                                                {{ chatContentList.length - index }}
                                            </text>
                                        </view>
                                        <text class="text-xs text-[#9CA3AF]">AI 生成提示词</text>
                                    </view>
                                    <view
                                        class="w-[44rpx] h-[44rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                        @click="handleDeleteItem(index)"
                                    >
                                        <u-icon name="close" color="#9CA3AF" size="16" />
                                    </view>
                                </view>

                                <view
                                    class="bg-[#F8FAFC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#F0F2F5]"
                                >
                                    <view v-if="item.title" class="mb-[12rpx] flex items-center gap-[8rpx]">
                                        <text
                                            class="text-[20rpx] font-bold text-[#64748B] bg-[#EEF2F7] px-[10rpx] py-[4rpx] rounded-[6rpx]"
                                            >记录标题</text
                                        >
                                        <text class="flex-1 text-[22rpx] text-[#64748B] truncate">{{
                                            item.title
                                        }}</text>
                                    </view>
                                    <u-input
                                        v-model="item.content"
                                        type="textarea"
                                        placeholder="请输入提示词"
                                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                        :maxlength="MAX_TEXT_LENGTH"
                                        :auto-height="false"
                                    />
                                    <view class="mt-[12rpx] flex justify-between items-center">
                                        <text class="text-[22rpx] text-[#C0C4CC]">
                                            {{ item.content.length }} / {{ MAX_TEXT_LENGTH }}
                                        </text>
                                        <view
                                            class="flex items-center gap-[6rpx] px-[20rpx] py-[8rpx] rounded-full use-content-button"
                                            @click="useContent(item)"
                                        >
                                            <u-icon name="checkmark" color="#fff" size="14" />
                                            <text class="text-[22rpx] font-bold text-white"
                                                >使用提示词</text
                                            >
                                        </view>
                                    </view>
                                </view>
                                <text
                                    class="block text-[20rpx] text-[#C0C4CC] leading-relaxed mt-[10rpx]"
                                >
                                    @图片N / @视频N
                                    是素材引用，改成别的写法会导致参考图不生效，建议只改描述内容。
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]"
        >
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden transition-all duration-200"
                :class="canGenerate ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                :style="
                    canGenerate
                        ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                        : 'background: #C0C4CC'
                "
                @click="contentPost"
            >
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">生成提示词</text>
                <view
                    class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx]"
                >
                    <text class="text-[22rpx] text-white font-medium"
                        >消耗 {{ getToken }} 算力</text
                    >
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { createSoraPrompt } from '@/api/digital_human'
import { useUserStore } from '@/stores/user'
import { TokensSceneEnum } from '@/enums/appEnums'
import { ListenerTypeEnum } from '@/enums/digitalHuman'
import { useEventBusManager } from '@/hooks/useEventBusManager'

type Mode = 'commerce' | 'creative'

const MODE_OPTIONS: { value: Mode; name: string; desc: string }[] = [
    { value: 'commerce', name: '带货', desc: '讲一个卖点' },
    { value: 'creative', name: '创作', desc: '重氛围表达' }
]

/** 想法与产出共用一个字数上限；产出侧后端会在 900 字处按句末标点截断 */
const MAX_TEXT_LENGTH = 1000

const { emit } = useEventBusManager()

const userStore = useUserStore()
const { userTokens } = toRefs(userStore)

/** 页面入参：时长/比例/素材数量决定了提示词的硬参数，缺省值保证单独打开页面也能跑 */
const duration = ref<number>(7)
const aspectRatio = ref<string>('16:9')
const imageCount = ref<number>(0)
const videoCount = ref<number>(0)
const firstLastFrame = ref<number>(0)

const mode = ref<Mode>('commerce')
const contentVal = ref<string>('')
const chatContentList = ref<any[]>([])
const isGenerating = ref<boolean>(false)

/** 后端返回的 meta，用来把「真实算好的」镜数/口播上限显示出来 */
const meta = ref<{ shots?: number; max_dialogue_chars?: number; duration?: number } | null>(null)

const showInfo = computed(() => {
    const d = meta.value?.duration ?? duration.value
    const shots = meta.value?.shots ?? Math.min(4, Math.max(1, Math.round(d / 4)))
    const chars = meta.value?.max_dialogue_chars ?? Math.floor(d * 4.5)
    return { d, shots, chars }
})

const infoText = computed(
    () => `${showInfo.value.d} 秒 · ${showInfo.value.shots} 镜 · 口播 ≤ ${showInfo.value.chars} 字`
)

const materialsText = computed(() => {
    const parts: string[] = []
    if (firstLastFrame.value === 1) {
        parts.push('首尾帧')
    } else if (imageCount.value > 0) {
        parts.push(`参考图 ${imageCount.value} 张`)
    }
    if (videoCount.value > 0) {
        parts.push(`参考视频 ${videoCount.value} 段`)
    }
    return parts.length > 0 ? `（素材：${parts.join('、')}）` : ''
})

const canGenerate = computed(() => contentVal.value.length > 0 && !isGenerating.value)

const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score
    return parseFloat(token)
})

const contentPost = async () => {
    if (!contentVal.value.trim()) {
        uni.$u.toast('请输入您的想法')
        return
    }
    if (isGenerating.value) {
        uni.$u.toast('提示词在生成中...')
        return
    }
    if (userTokens.value < getToken.value) {
        powerInsufficientTip()
        return
    }
    const chatContent = reactive<any>({ content: '', status: 'pending' })
    isGenerating.value = true
    chatContentList.value.unshift(chatContent)

    try {
        const res: any = await createSoraPrompt({
            content: contentVal.value,
            duration: duration.value,
            aspect_ratio: aspectRatio.value,
            image_count: imageCount.value,
            video_count: videoCount.value,
            first_last_frame: firstLastFrame.value,
            mode: mode.value,
            language: 'zh',
            number: 1
        })
        meta.value = res?.meta ?? null
        chatContent.content = res?.message ?? ''
        chatContent.title = res?.titles?.[0] || res?.meta?.title || ''
        chatContent.status = 'success'
        isGenerating.value = false
        userStore.getUser()
    } catch (err: any) {
        isGenerating.value = false
        chatContentList.value.shift()
        uni.$u.toast(err || '生成失败，请重试')
    }
}

/** 删除单条结果 */
const handleDeleteItem = (index: number) => {
    chatContentList.value.splice(index, 1)
}

const useContent = (item: any) => {
    if (item.status === 'pending') {
        uni.$u.toast('提示词在生成中...')
        return
    }
    emit('confirm', {
        type: ListenerTypeEnum.SORA_AI_COPYWRITER,
        data: item.content,
        // 后端从提示词里剥出来的记录标题，sora_create 用它替换「时间戳 + 一句话生成视频」
        title: item.title || ''
    })
    uni.navigateBack()
}

onLoad((options: any) => {
    if (options?.content) contentVal.value = String(options.content)
    if (options?.duration) duration.value = Number(options.duration) || 7
    if (options?.aspect_ratio) aspectRatio.value = String(options.aspect_ratio)
    if (options?.image_count) imageCount.value = Number(options.image_count) || 0
    if (options?.video_count) videoCount.value = Number(options.video_count) || 0
    if (options?.first_last_frame) firstLastFrame.value = Number(options.first_last_frame) || 0
    if (options?.mode === 'creative' || options?.mode === 'commerce') mode.value = options.mode

    // 从 sora_create 点「AI 帮写」进来且已经写了想法：直接开跑，少点一次
    if (contentVal.value.trim().length > 0) {
        contentPost()
    }
})
</script>

<style scoped lang="scss">
.use-content-button {
    background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%);
}

.skeleton {
    background: linear-gradient(90deg, #f0f2f5 25%, #e8eaed 50%, #f0f2f5 75%);
    background-size: 400% 100%;
    animation: skeleton-shimmer 1.4s ease infinite;
}

.skeleton-bar {
    background: linear-gradient(180deg, #f0f2f5 25%, #e8eaed 50%, #f0f2f5 75%);
    background-size: 100% 400%;
    animation: skeleton-shimmer-v 1.4s ease infinite;
}

@keyframes skeleton-shimmer {
    0% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes skeleton-shimmer-v {
    0% {
        background-position: 50% 100%;
    }
    100% {
        background-position: 50% 0%;
    }
}
</style>
