<template>
    <popup width="700px" title="详情" ref="popRef" confirm-button-text="" cancel-button-text="" @close="close">
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
                        <span class="text-xs text-[#374151] flex-1 break-all">{{ detailData.id || "—" }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">任务标题</span>
                        <span class="text-xs text-[#374151] flex-1 break-all">{{ detailData.title || "—" }}</span>
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
                    <div class="flex items-center px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">素材类型</span>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                            :style="
                                detailData.is_material === 1
                                    ? 'background:#ecfeff; color:#0891b2; border-color:#a5f3fc;'
                                    : 'background:#eff6ff; color:#0065fb; border-color:#bfdbfe;'
                            ">
                            {{ detailData.is_material === 1 ? "素材视频" : "数字人视频" }}
                        </span>
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
                            detailData.original_text || "—"
                        }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">仿写文案</span>
                        <span class="text-xs text-[#374151] flex-1 leading-7 whitespace-pre-wrap break-all">{{
                            detailData.rewritten_text || "—"
                        }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3" :class="publishTopics.length ? 'border-b border-[#f9fafb]' : ''">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">发布文案</span>
                        <span class="text-xs text-[#374151] flex-1 leading-7 whitespace-pre-wrap break-all">{{
                            detailData.publish_text || "—"
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

            <!-- 分析结果 -->
            <div class="rounded-xl border border-[#f3f4f6] overflow-hidden">
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
                        <span class="text-xs text-[#374151] flex-1">{{ detailData.compliance_status || "—" }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">人设风格</span>
                        <span class="text-xs text-[#374151] flex-1">{{ detailData.persona_tone || "—" }}</span>
                    </div>
                </div>
            </div>

            <!-- 媒体资源 -->
            <div class="rounded-xl border border-[#f3f4f6] overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#f9fafb] border-b border-[#f3f4f6]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0065fb] flex-shrink-0"></span>
                    <span class="text-xs font-semibold text-[#6b7280] tracking-wide">媒体资源</span>
                </div>
                <div class="flex flex-col">
                    <div v-if="detailData.is_material !== 1" class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">数字人形象</span>
                        <span class="text-xs text-[#374151] flex-1">{{
                            detailData.avatar_name ? `数字人${detailData.avatar_name}` : "—"
                        }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">合成音色</span>
                        <span class="text-xs text-[#374151] flex-1">{{ detailData.voice_name || "—" }}</span>
                    </div>
                    <div class="flex px-4 py-3 gap-3 border-b border-[#f9fafb]">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0 pt-0.5">视频时长</span>
                        <span class="text-xs text-[#374151] flex-1">{{
                            detailData.duration ? `${detailData.duration} 秒` : "—"
                        }}</span>
                    </div>
                    <div class="flex items-center px-4 py-3 gap-3">
                        <span class="text-xs text-[#9ca3af] w-20 flex-shrink-0">视频链接</span>
                        <a
                            v-if="detailData.video_url"
                            :href="detailData.video_url"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-lg bg-[#0065fb] text-white text-xs font-semibold no-underline cursor-pointer">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <circle cx="6" cy="6" r="6" fill="rgba(255,255,255,0.2)" />
                                <path d="M4.5 3.5l4 2.5-4 2.5V3.5z" fill="#fff" />
                            </svg>
                            查看视频
                        </a>
                        <span v-else class="text-xs text-[#9ca3af]">—</span>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import popup from "@/components/popup/index.vue";

const emit = defineEmits(["close", "success"]);
const popRef = shallowRef();
const detailData = ref<any>({});

const statusMap: Record<number, { label: string; bg: string; color: string; border: string; dot: string }> = {
    1: { label: "待处理", bg: "#fefce8", color: "#ca8a04", border: "#fef08a", dot: "#facc15" },
    2: { label: "视频生成中", bg: "#eff6ff", color: "#0065fb", border: "#bfdbfe", dot: "#0065fb" },
    3: { label: "确认发布", bg: "#fff7ed", color: "#ea580c", border: "#fed7aa", dot: "#fb923c" },
    4: { label: "已完成", bg: "#f0fdf4", color: "#16a34a", border: "#bbf7d0", dot: "#22c55e" },
};

const statusLabel = computed(() => statusMap[detailData.value?.status]?.label ?? "—");
const statusStyle = computed(() => {
    const s = statusMap[detailData.value?.status];
    if (!s) return "background:#f9fafb; color:#9ca3af; border-color:#f3f4f6;";
    return `background:${s.bg}; color:${s.color}; border-color:${s.border};`;
});
const statusDotColor = computed(() => statusMap[detailData.value?.status]?.dot ?? "#d1d5db");

const publishTopics = computed(() => {
    try {
        return detailData.value?.publish_topic ? JSON.parse(detailData.value.publish_topic) : [];
    } catch {
        return [];
    }
});

const analysisTags = computed(() => detailData.value?.analysis_tags || []);

const tagPillColors = [
    { bg: "#eff6ff", color: "#0065fb", border: "#bfdbfe" },
    { bg: "#fff7ed", color: "#f59e0b", border: "#fed7aa" },
    { bg: "#f0fdf4", color: "#10b981", border: "#d1fae5" },
    { bg: "#fdf4ff", color: "#a855f7", border: "#f3e8ff" },
    { bg: "#ecfeff", color: "#06b6d4", border: "#a5f3fc" },
];
const tagPillStyle = (index: number) => {
    const c = tagPillColors[index % tagPillColors.length];
    return `background:${c.bg}; color:${c.color}; border-color:${c.border};`;
};

const open = (detail: any) => {
    detailData.value = detail;
    popRef.value?.open();
};
const close = () => emit("close");

defineExpose({ open, close });
</script>
