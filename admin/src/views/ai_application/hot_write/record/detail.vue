<template>
    <popup width="760px" title="详情" ref="popRef" confirm-button-text="" cancel-button-text="" @close="close">
        <div class="max-h-[65vh] overflow-y-auto pr-1 space-y-4 pb-2">
            <!-- 基本信息 -->
            <div class="rounded-xl border border-[#f3f4f6] overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#f9fafb] border-b border-[#f3f4f6]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0065fb] flex-shrink-0"></span>
                    <span class="text-xs font-semibold text-[#6b7280] tracking-wide">基本信息</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">任务 ID</span>
                        <span class="text-xs text-[#374151] flex-1 break-all">{{ detailData.id || '—' }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">任务标题</span>
                        <span class="text-xs text-[#374151] flex-1 break-all">{{ detailData.title || '—' }}</span>
                    </div>
                    <div class="flex items-center px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">类型</span>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                            :style="
                                isImageText
                                    ? 'background:#fff7ed; color:#ea580c; border-color:#fed7aa;'
                                    : 'background:#eff6ff; color:#0065fb; border-color:#bfdbfe;'
                            ">
                            {{ isImageText ? '图文' : '视频' }}
                        </span>
                    </div>
                    <div class="flex items-center px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">平台</span>
                        <span class="text-xs text-[#374151]">{{ platformLabel }}</span>
                    </div>
                    <div class="flex items-center px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">任务状态</span>
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                            :style="statusStyle">
                            <span
                                class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                :style="{ background: statusDotColor }"></span>
                            {{ statusLabel }}
                        </span>
                    </div>
                    <div v-if="!isImageText" class="flex items-center px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">素材类型</span>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                            :style="
                                detailData.is_material === 1
                                    ? 'background:#ecfeff; color:#0891b2; border-color:#a5f3fc;'
                                    : 'background:#eff6ff; color:#0065fb; border-color:#bfdbfe;'
                            ">
                            {{ detailData.is_material === 1 ? '素材视频' : '数字人视频' }}
                        </span>
                    </div>
                    <div v-else class="flex items-center px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">改写状态</span>
                        <span class="text-xs text-[#374151]">{{ imageRewriteLabel }}</span>
                    </div>
                </div>
            </div>

            <!-- 文案内容 -->
            <div class="rounded-xl border border-[#f3f4f6] overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#f9fafb] border-b border-[#f3f4f6]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0065fb] flex-shrink-0"></span>
                    <span class="text-xs font-semibold text-[#6b7280] tracking-wide">文案内容</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">原始文案</span>
                        <span class="text-xs text-[#374151] flex-1 leading-7 whitespace-pre-wrap break-all">{{
                            detailData.original_text || '—'
                        }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">仿写标题</span>
                        <span class="text-xs text-[#374151] flex-1 leading-7 whitespace-pre-wrap break-all">{{
                            rewriteTitle || '—'
                        }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">仿写文案</span>
                        <span class="text-xs text-[#374151] flex-1 leading-7 whitespace-pre-wrap break-all">{{
                            detailData.rewritten_text || '—'
                        }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3" :class="publishTopics.length ? 'border-b border-[#f9fafb]' : ''">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">发布文案</span>
                        <span class="text-xs text-[#374151] flex-1 leading-7 whitespace-pre-wrap break-all">{{
                            detailData.publish_text || '—'
                        }}</span>
                    </div>
                    <div v-if="publishTopics.length" class="flex px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">话题标签</span>
                        <div class="flex flex-wrap gap-1.5 flex-1">
                            <span
                                v-for="(tag, i) in publishTopics"
                                :key="i"
                                class="inline-flex items-center px-2 py-0.5 rounded-full bg-[#eff6ff] border border-[#bfdbfe] text-xs text-[#0065fb] font-medium">
                                #{{ tag }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 图文资源 -->
            <div v-if="isImageText" class="rounded-xl border border-[#f3f4f6] overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#f9fafb] border-b border-[#f3f4f6]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0065fb] flex-shrink-0"></span>
                    <span class="text-xs font-semibold text-[#6b7280] tracking-wide">图文资源</span>
                </div>
                <div class="flex flex-col">
                    <div class="px-4 py-3 border-b border-[#f9fafb]">
                        <div class="text-xs text-[#9ca3af] mb-2">原图（{{ originalImages.length }}）</div>
                        <div v-if="originalImages.length" class="grid grid-cols-4 gap-2">
                            <el-image
                                v-for="(url, index) in originalImages"
                                :key="`o-${index}`"
                                :src="url"
                                :preview-src-list="originalImages"
                                :initial-index="index"
                                fit="cover"
                                class="w-full h-[96px] rounded-md bg-[#f3f4f6]" />
                        </div>
                        <span v-else class="text-xs text-[#9ca3af]">—</span>
                    </div>
                    <div class="px-4 py-3 border-b border-[#f9fafb]">
                        <div class="text-xs text-[#9ca3af] mb-2">已选图（{{ selectedImages.length }}）</div>
                        <div v-if="selectedImages.length" class="grid grid-cols-4 gap-2">
                            <el-image
                                v-for="(url, index) in selectedImages"
                                :key="`s-${index}`"
                                :src="url"
                                :preview-src-list="selectedImages"
                                :initial-index="index"
                                fit="cover"
                                class="w-full h-[96px] rounded-md bg-[#f3f4f6]" />
                        </div>
                        <span v-else class="text-xs text-[#9ca3af]">—</span>
                    </div>
                    <div class="px-4 py-3">
                        <div class="text-xs text-[#9ca3af] mb-2">改写图（{{ rewrittenImages.length }}）</div>
                        <div v-if="rewrittenImages.length" class="grid grid-cols-4 gap-2">
                            <el-image
                                v-for="(url, index) in rewrittenImages"
                                :key="`r-${index}`"
                                :src="url"
                                :preview-src-list="rewrittenImages"
                                :initial-index="index"
                                fit="cover"
                                class="w-full h-[96px] rounded-md bg-[#f3f4f6]" />
                        </div>
                        <span v-else class="text-xs text-[#9ca3af]">—</span>
                    </div>
                </div>
            </div>

            <!-- 视频媒体资源 -->
            <div v-else class="rounded-xl border border-[#f3f4f6] overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#f9fafb] border-b border-[#f3f4f6]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0065fb] flex-shrink-0"></span>
                    <span class="text-xs font-semibold text-[#6b7280] tracking-wide">媒体资源</span>
                </div>
                <div class="flex flex-col">
                    <div v-if="detailData.is_material !== 1" class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">数字人形象</span>
                        <span class="text-xs text-[#374151] flex-1">{{
                            detailData.avatar_name ? `数字人${detailData.avatar_name}` : '—'
                        }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">合成音色</span>
                        <span class="text-xs text-[#374151] flex-1">{{ detailData.voice_name || '—' }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">视频时长</span>
                        <span class="text-xs text-[#374151] flex-1">{{
                            detailData.duration ? `${detailData.duration} 秒` : '—'
                        }}</span>
                    </div>
                    <div class="flex items-center px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">视频链接</span>
                        <a
                            v-if="detailData.video_url"
                            :href="detailData.video_url"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-lg bg-[#0065fb] text-white text-xs font-semibold no-underline cursor-pointer">
                            查看视频
                        </a>
                        <span v-else class="text-xs text-[#9ca3af]">—</span>
                    </div>
                </div>
            </div>

            <!-- 分析结果（视频侧更有用；图文有则展示） -->
            <div
                v-if="analysisTags.length || detailData.compliance_status || detailData.persona_tone"
                class="rounded-xl border border-[#f3f4f6] overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#f9fafb] border-b border-[#f3f4f6]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0065fb] flex-shrink-0"></span>
                    <span class="text-xs font-semibold text-[#6b7280] tracking-wide">分析结果</span>
                </div>
                <div class="flex flex-col">
                    <div v-if="analysisTags.length" class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">爆款标签</span>
                        <div class="flex flex-wrap gap-1.5 flex-1">
                            <span
                                v-for="(tag, i) in analysisTags"
                                :key="i"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                :style="tagPillStyle(i)">
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">合规状态</span>
                        <span class="text-xs text-[#374151] flex-1">{{ detailData.compliance_status || '—' }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">人设风格</span>
                        <span class="text-xs text-[#374151] flex-1">{{ detailData.persona_tone || '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import popup from '@/components/popup/index.vue'
import { HOT_WRITE_PLATFORM_LABEL, isImageTextRecord } from './enums'

const emit = defineEmits(['close', 'success'])
const popRef = shallowRef()
const detailData = ref<any>({})

const isImageText = computed(() => isImageTextRecord(detailData.value))
const platformLabel = computed(
    () =>
        detailData.value?.platform_type_text ||
        HOT_WRITE_PLATFORM_LABEL[Number(detailData.value?.platform_type)] ||
        '—'
)
const rewriteTitle = computed(
    () => String(detailData.value?.title || detailData.value?.publish_title || '').trim()
)

const statusMap: Record<number, { label: string; bg: string; color: string; border: string; dot: string }> = {
    0: { label: '解析中', bg: '#eff6ff', color: '#0065fb', border: '#bfdbfe', dot: '#0065fb' },
    1: { label: '待确认', bg: '#fefce8', color: '#ca8a04', border: '#fef08a', dot: '#facc15' },
    2: { label: '生成中', bg: '#eff6ff', color: '#0065fb', border: '#bfdbfe', dot: '#0065fb' },
    3: { label: '成功', bg: '#f0fdf4', color: '#16a34a', border: '#bbf7d0', dot: '#22c55e' },
    4: { label: '失败', bg: '#fef2f2', color: '#dc2626', border: '#fecaca', dot: '#ef4444' }
}

const imageRewriteMap: Record<number, string> = {
    0: '无需改写',
    1: '待提交',
    2: '处理中',
    3: '改写成功',
    4: '改写失败',
    5: '待选图'
}

const statusLabel = computed(() => statusMap[detailData.value?.status]?.label ?? '—')
const statusStyle = computed(() => {
    const s = statusMap[detailData.value?.status]
    if (!s) return 'background:#f9fafb; color:#9ca3af; border-color:#f3f4f6;'
    return `background:${s.bg}; color:${s.color}; border-color:${s.border};`
})
const statusDotColor = computed(() => statusMap[detailData.value?.status]?.dot ?? '#d1d5db')
const imageRewriteLabel = computed(
    () => imageRewriteMap[Number(detailData.value?.image_rewrite_status ?? 0)] || '—'
)

const publishTopics = computed(() => {
    const raw = detailData.value?.publish_topic
    if (Array.isArray(raw)) return raw
    if (typeof raw === 'string' && raw) {
        try {
            const parsed = JSON.parse(raw)
            return Array.isArray(parsed) ? parsed : []
        } catch {
            return []
        }
    }
    return []
})

const analysisTags = computed(() => {
    const raw = detailData.value?.analysis_tags
    return Array.isArray(raw) ? raw : []
})

const originalImages = computed(() =>
    Array.isArray(detailData.value?.original_images) ? detailData.value.original_images.filter(Boolean) : []
)
const selectedImages = computed(() =>
    Array.isArray(detailData.value?.selected_images) ? detailData.value.selected_images.filter(Boolean) : []
)
const rewrittenImages = computed(() =>
    Array.isArray(detailData.value?.rewritten_images) ? detailData.value.rewritten_images.filter(Boolean) : []
)

const tagPillColors = [
    { bg: '#eff6ff', color: '#0065fb', border: '#bfdbfe' },
    { bg: '#fff7ed', color: '#f59e0b', border: '#fed7aa' },
    { bg: '#f0fdf4', color: '#10b981', border: '#d1fae5' },
    { bg: '#fdf4ff', color: '#a855f7', border: '#f3e8ff' },
    { bg: '#ecfeff', color: '#06b6d4', border: '#a5f3fc' }
]
const tagPillStyle = (index: number) => {
    const c = tagPillColors[index % tagPillColors.length]
    return `background:${c.bg}; color:${c.color}; border-color:${c.border};`
}

const open = (detail: any) => {
    detailData.value = detail
    popRef.value?.open()
}
const close = () => emit('close')

defineExpose({ open, close })
</script>
