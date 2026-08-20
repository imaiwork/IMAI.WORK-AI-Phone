<template>
    <Transition name="mention-pop">
        <div
            v-if="visible"
            ref="popRef"
            class="absolute z-[9999] w-[320px] bg-[#ffffff]/95 backdrop-blur-xl rounded-2xl border border-[#e2e8f0]/60 overflow-hidden shadow-[0_0_0_1px_rgba(0,0,0,0.03),0_20px_25px_-5px_rgba(0,0,0,0.1),0_8px_10px_-6px_rgba(0,0,0,0.1)]"
            style="bottom: calc(100% + 10px); left: 0px"
            @mousedown.prevent>
            <div
                class="relative px-4 pt-3 pb-2 flex items-center justify-between border-b border-[#f1f5f9]/80 bg-[#f8fafc]/50">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></div>
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">智能体助理</span>
                </div>
                <div
                    v-if="keyword"
                    class="flex items-center gap-1.5 max-w-[140px] px-2 py-0.5 bg-[#0065fb]/5 border border-[#0065fb]/10 rounded-md">
                    <span class="text-[10px] text-[#0065fb]/60 font-medium whitespace-nowrap">搜索:</span>
                    <span class="text-[11px] text-[#0065fb] font-bold truncate">{{ keyword }}</span>
                </div>
            </div>

            <ul
                ref="listRef"
                class="max-h-[280px] overflow-y-auto m-2 list-none space-y-0.5 mention-scrollbar scroll-smooth">
                <template v-if="filteredList.length">
                    <li
                        v-for="(item, index) in filteredList"
                        :key="item.id"
                        :ref="
                            (el) => {
                                if (activeIndex === index) activeItemRef = el;
                            }
                        "
                        class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer transition-colors duration-150"
                        :class="
                            activeIndex === index
                                ? 'bg-[#0065fb]/[0.06] shadow-[inset_0_0_0_1px_rgba(0,101,251,0.1)]'
                                : 'hover:bg-slate-50'
                        "
                        @mouseenter="activeIndex = index"
                        @mousedown.prevent="selectItem(item)">
                        <div
                            class="relative w-9 h-9 rounded-[11px] flex-shrink-0 flex items-center justify-center overflow-hidden"
                            :style="getAvatarStyle(index, item)">
                            <img v-if="item.image" :src="item.image" class="w-full h-full object-cover" />
                            <span v-else class="text-sm font-bold text-white uppercase">
                                {{ item.name?.charAt(0) }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span
                                    class="text-[13.5px] font-semibold truncate"
                                    :class="activeIndex === index ? 'text-slate-900' : 'text-slate-700'"
                                    v-html="highlightKeyword(item.name)">
                                </span>
                                <span
                                    v-if="shouldShowAgentAccessTag(item)"
                                    class="shrink-0 rounded-full border px-[6px] py-[1px] text-[10px] font-bold leading-none"
                                    :class="
                                        canUseAgent(item, userInfo)
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-600'
                                            : 'border-violet-200 bg-violet-50 text-violet-600'
                                    ">
                                    {{ getAgentAccessTagText(item, userInfo) }}
                                </span>
                            </div>
                            <div
                                v-if="item.desc"
                                class="text-[11px] truncate leading-relaxed mt-0.5"
                                :class="activeIndex === index ? 'text-[#0065fb]/70' : 'text-slate-400'">
                                {{ item.desc }}
                            </div>
                        </div>

                        <div v-if="activeIndex === index" class="flex-shrink-0">
                            <div
                                class="w-5 h-5 rounded-full bg-[#0065fb] flex items-center justify-center shadow-lg shadow-[#0065fb]/30">
                                <svg
                                    width="12"
                                    height="12"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="white"
                                    stroke-width="3.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                    </li>
                </template>

                <li v-else class="flex flex-col items-center justify-center py-12 px-6">
                    <div
                        class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mb-3 border border-dashed border-slate-200">
                        <svg
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-600">无匹配结果</p>
                    <p class="text-[11px] text-slate-400 mt-1">试着输入其他关键词</p>
                </li>
            </ul>

            <div
                class="flex items-center justify-center gap-3 px-4 py-[10px] bg-[#f8fafc]/80 border-t border-slate-100">
                <div class="flex items-center gap-1.5">
                    <div class="flex gap-0.5">
                        <kbd class="mention-kbd">↑</kbd>
                        <kbd class="mention-kbd">↓</kbd>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">切换</span>
                </div>
                <div class="w-[1px] h-3 bg-slate-200"></div>
                <div class="flex items-center gap-1.5">
                    <kbd class="mention-kbd px-1.5">Enter</kbd>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">选择</span>
                </div>
                <div class="w-[1px] h-3 bg-slate-200"></div>
                <div class="flex items-center gap-1.5">
                    <kbd class="mention-kbd px-1.5">Esc</kbd>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">关闭</span>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import { storeToRefs } from "pinia";
import { useUserStore } from "@/stores/user";
import { canUseAgent, getAgentAccessTagText, shouldShowAgentAccessTag } from "@/utils/agentPermission";

const { userInfo } = storeToRefs(useUserStore());

export interface MentionItem {
    id: string | number;
    name: string;
    image?: string;
    desc?: string;
    [key: string]: any;
}

interface Props {
    list: MentionItem[];
    keyword?: string;
    visible: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    keyword: "",
    visible: false,
});

const emit = defineEmits(["select", "close"]);

const listRef = ref<HTMLUListElement>();
const activeItemRef = ref<any>(null);
const activeIndex = ref(0);

const avatarGradients = [
    "linear-gradient(135deg, #0065FB 0%, #38bdf8 100%)",
    "linear-gradient(135deg, #6366f1 0%, #a855f7 100%)",
    "linear-gradient(135deg, #10b981 0%, #38bdf8 100%)",
    "linear-gradient(135deg, #f59e0b 0%, #f97316 100%)",
    "linear-gradient(135deg, #0284c7 0%, #0065FB 100%)",
];

const getAvatarStyle = (index: number, item: MentionItem) => {
    if (item.image) return {};
    return { background: avatarGradients[index % avatarGradients.length] };
};

const highlightKeyword = (name: string) => {
    if (!props.keyword) return name;
    const kw = props.keyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    return name.replace(
        new RegExp(`(${kw})`, "gi"),
        `<span style="color:#0065FB;font-weight:800;text-decoration:underline;text-underline-offset:2px;">$1</span>`
    );
};

const filteredList = computed(() => {
    if (!props.keyword) return props.list;
    const kw = props.keyword.toLowerCase();
    return props.list.filter((item) => item.name.toLowerCase().includes(kw) || item.desc?.toLowerCase().includes(kw));
});

// 核心逻辑：确保上下键选择时滚动条跟随
watch(activeIndex, () => {
    nextTick(() => {
        if (activeItemRef.value) {
            activeItemRef.value.scrollIntoView({
                behavior: "smooth",
                block: "nearest",
            });
        }
    });
});

watch(
    () => props.keyword,
    () => {
        activeIndex.value = 0;
        nextTick(() => {
            if (listRef.value) listRef.value.scrollTop = 0;
        });
    }
);

const moveUp = () => {
    if (!filteredList.value.length) return;
    activeIndex.value = (activeIndex.value - 1 + filteredList.value.length) % filteredList.value.length;
};

const moveDown = () => {
    if (!filteredList.value.length) return;
    activeIndex.value = (activeIndex.value + 1) % filteredList.value.length;
};

const confirm = () => {
    const item = filteredList.value[activeIndex.value];
    if (item) selectItem(item);
};

const selectItem = (item: MentionItem) => emit("select", item);

defineExpose({ moveUp, moveDown, confirm, activeIndex, filteredList });
</script>

<style scoped>
.mention-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.mention-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.mention-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 20px;
    border: 1px solid transparent;
    background-clip: padding-box;
}
.mention-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

.mention-kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-bottom: 2px solid #e5e7eb;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 800;
    color: #475569;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.02);
}

.mention-pop-enter-active {
    transition: all 0.2s cubic-bezier(0.34, 1.25, 0.64, 1);
}
.mention-pop-leave-active {
    transition: all 0.15s ease-in;
}
.mention-pop-enter-from,
.mention-pop-leave-to {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
}
</style>
