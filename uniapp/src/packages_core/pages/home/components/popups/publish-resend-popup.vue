<template>
    <popup-bottom
        v-model="show"
        height="82%"
        custom-class="bg-white"
        :z-index="5002"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="bg-white px-[32rpx] pt-[20rpx] pb-[16rpx]">
                <view class="w-[72rpx] h-[8rpx] mx-auto mb-[24rpx] bg-[#e5e7eb] rounded-full" />
                <view class="flex items-center justify-between">
                    <text class="text-base font-bold text-[#1f2937]">重新发送</text>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#f3f4f6] flex items-center justify-center"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#8B9199" :size="18"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view v-if="checking" class="grow flex flex-col items-center justify-center gap-[20rpx]">
                    <u-loading mode="circle" size="40" color="#999999"></u-loading>
                    <text class="text-xs text-[#9ca3af]">正在校验任务...</text>
                </view>

                <template v-else-if="checkData">
                    <view class="grow min-h-0">
                        <scroll-view scroll-y class="h-full">
                            <view class="px-[32rpx] pb-[24rpx] flex flex-col gap-[24rpx]">
                                <!-- 设备离线提示：后端下发前会拦截，这里提前告知并禁用发送 -->
                                <view
                                    v-if="deviceOffline"
                                    class="bg-error-light-9 rounded-[24rpx] px-[28rpx] py-[20rpx] flex items-start gap-[12rpx]">
                                    <u-icon
                                        name="wifi-off"
                                        color="#f56c6c"
                                        :size="28"
                                        class="flex-shrink-0 mt-[2rpx]"></u-icon>
                                    <text class="flex-1 text-xs text-error leading-[36rpx]">
                                        {{ checkData.device_offline_reason || DEVICE_OFFLINE_TIP }}
                                    </text>
                                </view>

                                <!-- 中断提示：仅当前设备有任务在执行时展示 -->
                                <view
                                    v-if="checkData.device_running"
                                    class="bg-warning-light-9 rounded-[24rpx] px-[28rpx] py-[20rpx] flex items-start gap-[12rpx]">
                                    <u-icon
                                        name="error-circle"
                                        color="#e6a23c"
                                        :size="28"
                                        class="flex-shrink-0 mt-[2rpx]"></u-icon>
                                    <text class="flex-1 text-xs text-warning leading-[36rpx]">
                                        {{ checkData.tip || DEFAULT_TIP }}
                                    </text>
                                </view>

                                <!-- 选择重发的视频 -->
                                <view>
                                    <text class="block text-xs font-bold text-[#6b7280] mb-[16rpx]">
                                        选择重发的视频
                                    </text>
                                    <view class="grid grid-cols-2 gap-[16rpx]">
                                        <view
                                            class="rounded-[24rpx] p-[24rpx] border-[3rpx] border-solid"
                                            :class="
                                                videoSource === VideoSourceEnum.GENERATED
                                                    ? 'bg-primary-light-9 border-primary'
                                                    : 'bg-[#f9fafb] border-[transparent]'
                                            "
                                            :style="checkData.has_generated_video ? '' : 'opacity:0.6'"
                                            @click="handlePickGenerated">
                                            <view class="flex items-center gap-[10rpx]">
                                                <u-icon name="play-circle" color="#0065fb" :size="30"></u-icon>
                                                <text class="text-[26rpx] font-bold text-[#1f2937]">
                                                    用已生成的视频
                                                </text>
                                            </view>
                                            <text
                                                v-if="checkData.has_generated_video"
                                                class="block mt-[12rpx] text-[20rpx] text-success">
                                                已有生成好的成片
                                            </text>
                                            <text v-else class="block mt-[12rpx] text-[20rpx] text-error">
                                                {{ checkData.generated_disabled_reason || '暂无生成好的视频' }}
                                            </text>
                                        </view>
                                        <view
                                            class="rounded-[24rpx] p-[24rpx] border-[3rpx] border-solid"
                                            :class="
                                                videoSource === VideoSourceEnum.UPLOAD
                                                    ? 'bg-primary-light-9 border-primary'
                                                    : 'bg-[#f9fafb] border-[transparent]'
                                            "
                                            @click="videoSource = VideoSourceEnum.UPLOAD">
                                            <view class="flex items-center gap-[10rpx]">
                                                <u-icon name="cut" color="#8b5cf6" :size="30"></u-icon>
                                                <text class="text-[26rpx] font-bold text-[#1f2937]">
                                                    换一个视频
                                                </text>
                                            </view>
                                            <text class="block mt-[12rpx] text-[20rpx] text-[#9ca3af]">
                                                手动上传并填写文案
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <!-- 已生成视频预览 -->
                                <view
                                    v-if="videoSource === VideoSourceEnum.GENERATED && generatedVideo"
                                    class="flex items-center gap-[20rpx] bg-[#f9fafb] rounded-[24rpx] p-[20rpx]">
                                    <view
                                        class="relative w-[120rpx] h-[160rpx] rounded-[16rpx] overflow-hidden bg-[#e5e7eb] flex-shrink-0"
                                        @click="showVideo = true">
                                        <image
                                            v-if="generatedVideo.pic"
                                            :src="generatedVideo.pic"
                                            mode="aspectFill"
                                            class="w-full h-full" />
                                        <view class="absolute inset-0 flex items-center justify-center">
                                            <view
                                                class="w-[52rpx] h-[52rpx] rounded-full bg-[rgba(0,0,0,0.45)] flex items-center justify-center">
                                                <u-icon
                                                    name="play-right-fill"
                                                    color="#ffffff"
                                                    :size="22"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                    <view class="flex-1 min-w-0">
                                        <text class="block text-sm font-bold text-[#1f2937] line-clamp-1">
                                            {{ checkData.material_title || '已生成成片' }}
                                        </text>
                                        <text class="block mt-[8rpx] text-xs text-[#9ca3af] line-clamp-2 break-all">
                                            {{ checkData.material_subtitle || '将沿用原任务的标题与文案发送' }}
                                        </text>
                                    </view>
                                </view>

                                <!-- 换视频：上传 + 文案 -->
                                <template v-if="videoSource === VideoSourceEnum.UPLOAD">
                                    <view>
                                        <text class="block text-xs font-bold text-[#6b7280] mb-[16rpx]">
                                            上传视频
                                        </text>
                                        <view
                                            v-if="uploadVideo"
                                            class="flex items-center gap-[16rpx] bg-[#f9fafb] rounded-[24rpx] px-[24rpx] py-[20rpx]">
                                            <view
                                                class="relative w-[96rpx] h-[128rpx] rounded-[12rpx] overflow-hidden bg-[#e5e7eb] flex-shrink-0">
                                                <image
                                                    v-if="uploadVideo.pic"
                                                    :src="uploadVideo.pic"
                                                    mode="aspectFill"
                                                    class="w-full h-full" />
                                            </view>
                                            <text class="flex-1 min-w-0 text-xs text-[#374151] line-clamp-2 break-all">
                                                {{ uploadVideo.name || '已选择视频' }}
                                            </text>
                                            <text
                                                class="flex-shrink-0 text-xs text-primary font-bold"
                                                @click="handleChooseVideo">
                                                重选
                                            </text>
                                        </view>
                                        <view
                                            v-else
                                            class="upload-dashed w-full py-[48rpx] rounded-[24rpx] flex flex-col items-center gap-[12rpx]"
                                            @click="handleChooseVideo">
                                            <u-icon name="arrow-upward" color="#9ca3af" :size="36"></u-icon>
                                            <text class="text-xs text-[#9ca3af]">点击上传视频（mp4/mov）</text>
                                        </view>
                                    </view>

                                    <view class="flex flex-col gap-[20rpx]">
                                        <view>
                                            <view class="flex items-center justify-between mb-[12rpx]">
                                                <text class="text-xs font-bold text-[#6b7280]">发布标题</text>
                                                <text class="text-[20rpx] text-[#9ca3af]">
                                                    {{ form.material_title.length }}/{{ TITLE_MAX }}
                                                </text>
                                            </view>
                                            <input
                                                v-model="form.material_title"
                                                :maxlength="TITLE_MAX"
                                                class="resend-input"
                                                :placeholder="`请输入发布标题（最多${TITLE_MAX}字）`"
                                                confirm-type="done" />
                                        </view>
                                        <view>
                                            <view class="flex items-center justify-between mb-[12rpx]">
                                                <text class="text-xs font-bold text-[#6b7280]">发布文案</text>
                                                <text class="text-[20rpx] text-[#9ca3af]">
                                                    {{ form.material_subtitle.length }}/{{ SUBTITLE_MAX }}
                                                </text>
                                            </view>
                                            <textarea
                                                v-model="form.material_subtitle"
                                                :maxlength="SUBTITLE_MAX"
                                                class="resend-textarea"
                                                placeholder="请输入发布文案（标题与文案至少填写一项）" />
                                        </view>
                                        <view>
                                            <text class="block text-xs font-bold text-[#6b7280] mb-[12rpx]">
                                                话题
                                            </text>
                                            <input
                                                v-model="form.material_tag"
                                                :maxlength="200"
                                                class="resend-input"
                                                placeholder="如：#敏感肌护肤 #换季修护"
                                                confirm-type="done" />
                                        </view>
                                        <view v-if="!isCircle">
                                            <text class="block text-xs font-bold text-[#6b7280] mb-[12rpx]">
                                                位置
                                            </text>
                                            <input
                                                v-model="form.poi"
                                                :maxlength="100"
                                                class="resend-input"
                                                placeholder="选填，如：XX美容院高新店"
                                                confirm-type="done" />
                                        </view>
                                    </view>
                                </template>
                            </view>
                        </scroll-view>
                    </view>

                    <view class="px-[32rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] bg-white">
                        <button
                            class="plain-btn w-full py-[24rpx] rounded-full text-[30rpx] font-bold"
                            :class="canSubmit ? 'bg-error text-white' : 'bg-[#e5e7eb] text-[#9ca3af]'"
                            :disabled="!canSubmit || submitting"
                            @click="handleSubmit">
                            {{ submitting ? '发送中...' : '确认重新发送' }}
                        </button>
                    </view>
                </template>
            </view>
        </template>
    </popup-bottom>

    <video-preview v-model="showVideo" title="视频预览" :video-url="generatedVideo?.video_url || ''" />
</template>

<script setup lang="ts">
import VideoPreview from '@/components/video-preview/video-preview.vue'
import useUpload from '@/hooks/useUpload'
import { checkPublishResend, publishResend } from '@/api/person'
import type { PublishItem, ResendCheckData, ResendGeneratedVideo } from './publish-types'

/** 与后端 PublishResendLogic::VIDEO_SOURCE_* 一致 */
enum VideoSourceEnum {
    GENERATED = 'generated',
    UPLOAD = 'upload'
}

// 标题20字/文案1000字，取各发布平台限制的最小值，保证全平台可发
const TITLE_MAX = 20
const SUBTITLE_MAX = 1000

const DEFAULT_TIP = '当前有任务正在执行，发送将会中断任务，直至下一任务才会重新恢复'
const DEVICE_OFFLINE_TIP = '设备当前不在线，请先启动设备后再重新发送'

const props = defineProps<{
    modelValue: boolean
    item: PublishItem | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'success'): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})

const checking = ref(false)
const submitting = ref(false)
const checkData = ref<ResendCheckData | null>(null)
const videoSource = ref<VideoSourceEnum | ''>('')
const uploadVideo = ref<{ url: string; pic: string; name: string } | null>(null)
const showVideo = ref(false)
const form = reactive({
    material_title: '',
    material_subtitle: '',
    material_tag: '',
    poi: ''
})

const generatedVideo = computed<ResendGeneratedVideo | null>(
    () => checkData.value?.generated_video || null
)
const isCircle = computed(() => checkData.value?.publish_kind === 'circle')
// 兼容旧后端未返回 device_online 的情况，仅显式 false 视为离线
const deviceOffline = computed(() => checkData.value?.device_online === false)

const canSubmit = computed(() => {
    if (!checkData.value) return false
    if (deviceOffline.value) return false
    if (videoSource.value === VideoSourceEnum.GENERATED) {
        return checkData.value.has_generated_video
    }
    if (videoSource.value === VideoSourceEnum.UPLOAD) {
        const hasCopy = !!(form.material_title.trim() || form.material_subtitle.trim())
        return !!uploadVideo.value?.url && hasCopy
    }
    return false
})

const resetState = () => {
    checkData.value = null
    videoSource.value = ''
    uploadVideo.value = null
    showVideo.value = false
    form.material_title = ''
    form.material_subtitle = ''
    form.material_tag = ''
    form.poi = ''
}

let checkToken = 0
const queryCheck = async () => {
    const item = props.item
    if (!item) return
    const token = ++checkToken
    checking.value = true
    try {
        const data: ResendCheckData = await checkPublishResend({
            task_id: item.task_id,
            detail_id: item.detail_id
        })
        if (token !== checkToken) return
        checkData.value = data
        // 换视频时的文案默认带出原任务文案，减少重复输入；超限部分截断
        form.material_title = (data.material_title || '').slice(0, TITLE_MAX)
        form.material_subtitle = (data.material_subtitle || '').slice(0, SUBTITLE_MAX)
        form.material_tag = data.material_tag || ''
        form.poi = data.poi || ''
        videoSource.value = data.has_generated_video ? VideoSourceEnum.GENERATED : VideoSourceEnum.UPLOAD
    } catch (error: any) {
        if (token !== checkToken) return
        uni.$u.toast(error?.message || error || '当前任务不可重新发送')
        emit('update:modelValue', false)
    } finally {
        if (token === checkToken) checking.value = false
    }
}

const handlePickGenerated = () => {
    if (!checkData.value?.has_generated_video) {
        uni.$u.toast(checkData.value?.generated_disabled_reason || '暂无生成好的视频')
        return
    }
    videoSource.value = VideoSourceEnum.GENERATED
}

const { uploadAndProcessFiles } = useUpload({
    count: 1,
    onSuccess: (materials) => {
        const material = materials[0]
        if (!material?.url) return
        uploadVideo.value = { url: material.url, pic: material.pic || '', name: material.name || '' }
    }
})
const handleChooseVideo = () => uploadAndProcessFiles('video')

const handleSubmit = async () => {
    const item = props.item
    if (!item || !canSubmit.value || submitting.value) return
    submitting.value = true
    try {
        const params: Record<string, any> = {
            task_id: item.task_id,
            detail_id: item.detail_id,
            video_source: videoSource.value
        }
        if (videoSource.value === VideoSourceEnum.GENERATED) {
            params.video_task_id = generatedVideo.value?.video_task_id || 0
        } else {
            // 后端校验换视频时必须携带完整文案字段（标题/文案/话题/位置）
            params.video_url = uploadVideo.value?.url || ''
            params.pic = uploadVideo.value?.pic || ''
            params.material_title = form.material_title.trim()
            params.material_subtitle = form.material_subtitle.trim()
            params.material_tag = form.material_tag.trim()
            params.poi = form.poi.trim()
        }
        await publishResend(params)
        uni.$u.toast('已重新发送，任务执行中')
        emit('success')
        emit('update:modelValue', false)
    } catch (error: any) {
        uni.$u.toast(error?.message || error || '重新发送失败')
    } finally {
        submitting.value = false
    }
}

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) {
            resetState()
            queryCheck()
        }
    }
)
</script>

<style lang="scss" scoped>
.upload-dashed {
    border: 3rpx dashed #cbd5e1;
    background: #f9fafb;
}

.resend-input {
    @apply w-full bg-[#f9fafb] rounded-[20rpx] px-[24rpx] py-[20rpx] text-sm text-[#1f2937];
}

.resend-textarea {
    @apply w-full bg-[#f9fafb] rounded-[20rpx] px-[24rpx] py-[20rpx] text-sm text-[#1f2937];
    height: 160rpx;
}

.plain-btn {
    border: none;
    line-height: 1.4;

    &::after {
        border: none;
    }
}
</style>
