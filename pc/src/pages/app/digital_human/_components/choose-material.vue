<template>
    <popup
        ref="popupRef"
        width="860px"
        top="5vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col h-[60vh]">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon :name="headerIcon" :size="18" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">{{ dialogTitle }}</span>

                    <div v-if="mode === 'all'" class="flex bg-slate-100 rounded-xl p-1 ml-2">
                        <button
                            v-for="(tab, i) in ['全部素材', '按分组']"
                            :key="i"
                            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="
                                currShowType === i
                                    ? 'bg-white text-gray-800 shadow-sm'
                                    : 'text-slate-400 hover:text-slate-600'
                            "
                            @click="handleShowType(i)">
                            {{ tab }}
                        </button>
                    </div>
                    <div v-if="isGroupListMode" class="text-xs text-slate-400">分组内素材不区分视频/图片</div>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        v-if="totalChooseCount > 0"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer transition-all"
                        :class="showChoosePanel ? 'bg-[#0065fb]/10' : 'bg-slate-100 hover:bg-slate-200'"
                        @click="toggleChoosePanel">
                        <Icon name="el-icon-Check" color="var(--color-primary)" :size="11" />
                        <span class="text-[12px] font-black text-primary">
                            已选 {{ totalChooseCount }}
                            <template v-if="limit"> / {{ limit }}</template>
                        </span>
                        <Icon
                            :name="showChoosePanel ? 'el-icon-ArrowUp' : 'el-icon-ArrowDown'"
                            :size="11"
                            color="var(--color-primary)" />
                    </div>
                    <div
                        v-if="multiple && limit && limit > 1"
                        class="flex items-center gap-1 px-3 py-1.5 rounded-full"
                        :class="
                            limit && totalChooseCount >= limit
                                ? 'bg-red-50 text-red-400'
                                : 'bg-slate-100 text-slate-400'
                        ">
                        <Icon
                            :name="totalChooseCount >= (limit ?? 0) ? 'el-icon-WarningFilled' : 'el-icon-InfoFilled'"
                            :size="13"
                            :color="totalChooseCount >= (limit ?? 0) ? '#f87171' : '#94a3b8'" />
                        <span class="text-[11px] font-bold">最多 {{ limit }} 个</span>
                    </div>

                    <div class="w-9 h-9 cursor-pointer" @click="close">
                        <close-btn />
                    </div>
                </div>
            </div>

            <transition name="slide-down">
                <div
                    v-if="showChoosePanel && totalChooseCount > 0"
                    class="mx-6 mt-3 bg-slate-50 rounded-2xl border border-slate-100 overflow-hidden shrink-0">
                    <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                        <span class="text-xs font-black text-slate-600">已选（{{ totalChooseCount }}）</span>
                        <button
                            class="text-xs font-black text-red-400 hover:text-red-500 transition-colors"
                            @click="clearAll">
                            清空
                        </button>
                    </div>
                    <div class="flex gap-2 px-4 py-3 overflow-x-auto">
                        <template v-if="!disableGroupSelect">
                            <div
                                v-for="group in chooseGroups"
                                :key="'g_' + group.id"
                                class="relative flex-shrink-0 w-16 h-20 rounded-xl overflow-hidden bg-white border border-slate-200 flex flex-col items-center justify-center gap-1 group">
                                <span class="text-2xl">📁</span>
                                <span class="text-[10px] text-slate-600 px-1 text-center line-clamp-2 leading-tight">{{
                                    group.name
                                }}</span>
                                <div class="absolute bottom-1 left-1 bg-indigo-500 rounded-full px-1.5 py-0.5">
                                    <span class="text-[9px] text-white font-bold">分组</span>
                                </div>
                                <button
                                    class="absolute top-1 right-1 w-5 h-5 rounded-full bg-[#000000]/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                    @click.stop="removeChooseGroup(group)">
                                    <Icon name="el-icon-Close" :size="10" color="#fff" />
                                </button>
                            </div>
                        </template>
                        <div
                            v-for="(item, index) in chooseLists"
                            :key="'m_' + item.id"
                            class="relative flex-shrink-0 w-16 h-20 rounded-xl overflow-hidden group">
                            <ElImage :src="item.pic || item.content" fit="cover" class="w-full h-full" />
                            <div
                                class="absolute bottom-1 left-1 bg-[#000000]/50 rounded-full px-1.5 py-0.5 text-[9px] text-white">
                                {{ isImage(item) ? "图片" : "视频" }}
                            </div>
                            <div
                                class="absolute bottom-1 right-1 w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                                <span class="text-[10px] text-white font-bold">{{ index + 1 }}</span>
                            </div>
                            <button
                                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-[#000000]/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                @click.stop="handleSelect(item)">
                                <Icon name="el-icon-Close" :size="10" color="#fff" />
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <div
                v-if="isGroupInnerMode"
                class="flex items-center gap-2 px-6 py-2.5 border-b border-slate-100 shrink-0 bg-slate-50 mt-3 mx-6 rounded-xl">
                <button
                    class="flex items-center gap-1.5 text-slate-400 hover:text-slate-600 transition-colors"
                    @click="backToGroupList">
                    <Icon name="el-icon-ArrowLeft" :size="13" />
                    <span class="text-xs font-bold">返回分组</span>
                </button>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-black text-slate-700">{{ currentGroupItem.name }}</span>
            </div>

            <div class="grow min-h-0 py-4">
                <div v-if="isLoading" class="px-6">
                    <div v-if="isGroupListMode" class="flex flex-col gap-3">
                        <div v-for="i in 5" :key="i" class="bg-slate-100 animate-pulse rounded-2xl h-16" />
                    </div>
                    <div v-else :class="['grid gap-3', gridClass]">
                        <div v-for="i in 8" :key="i" class="aspect-[3/4] rounded-2xl bg-slate-100 animate-pulse" />
                    </div>
                </div>

                <div
                    v-else-if="!isLoading && pager.lists.length === 0 && pager.isLoad"
                    class="flex flex-col items-center justify-center py-20 gap-4">
                    <div
                        class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100">
                        <Icon
                            :name="isGroupListMode ? 'el-icon-Folder' : headerIcon"
                            color="var(--slate-300)"
                            :size="32" />
                    </div>
                    <span class="text-sm font-black text-slate-300 uppercase tracking-wider">
                        {{ isGroupListMode ? "暂无分组" : "暂无可用素材" }}
                    </span>
                </div>

                <div class="h-full" v-else>
                    <ElScrollbar :distance="20" @end-reached="loadMore">
                        <div v-if="isGroupListMode" class="px-6 flex flex-col gap-3 pb-4">
                            <div
                                v-for="(item, index) in pager.lists"
                                :key="item.id ?? index"
                                class="bg-white rounded-2xl px-4 py-3 flex items-center border transition-all shadow-sm"
                                :class="[
                                    !disableGroupSelect && isChooseGroup(item)
                                        ? 'border-primary shadow-[#0065fb]/10'
                                        : 'border-slate-100 hover:border-slate-200',
                                    item.material_count === 0 ? 'opacity-50' : '',
                                ]">
                                <div
                                    class="flex-1 flex items-center gap-4 cursor-pointer"
                                    @click="item.material_count > 0 && handleGroupItemClick(item)">
                                    <div
                                        class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xl">📁</span>
                                        <div
                                            class="absolute -bottom-1.5 -right-1.5 min-w-5 h-5 px-1 rounded-full flex items-center justify-center text-[10px] text-white font-black"
                                            :class="item.material_count > 0 ? 'bg-primary' : 'bg-slate-300'">
                                            {{ item.material_count ?? 0 }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-gray-800 leading-tight">{{ item.name }}</p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            <template v-if="!disableGroupSelect && isChooseGroup(item)">
                                                <span class="text-primary font-bold">✓ 已选整个分组</span>
                                            </template>
                                            <template v-else-if="item.material_count === 0">暂无素材</template>
                                            <template v-else>共 {{ item.material_count }} 个素材</template>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 ml-3">
                                    <button
                                        v-if="!disableGroupSelect"
                                        class="px-3 py-1.5 rounded-lg text-xs font-black border transition-all"
                                        :class="
                                            item.material_count === 0
                                                ? 'bg-slate-50 border-slate-100 text-slate-300 cursor-not-allowed'
                                                : isChooseGroup(item)
                                                ? 'bg-primary border-primary text-white shadow-sm shadow-[#0065fb]/20'
                                                : 'bg-white border-slate-200 text-slate-500 hover:border-primary hover:text-primary'
                                        "
                                        @click.stop="item.material_count > 0 && toggleChooseGroup(item)">
                                        {{ isChooseGroup(item) ? "已选分组" : "选分组" }}
                                    </button>
                                    <button
                                        v-if="mode !== 'group'"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-all"
                                        :class="
                                            item.material_count === 0
                                                ? 'text-slate-200 cursor-not-allowed'
                                                : 'text-slate-400 hover:bg-slate-100 hover:text-slate-600'
                                        "
                                        @click.stop="item.material_count > 0 && enterGroup(item)">
                                        <Icon name="el-icon-ArrowRight" :size="15" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else :class="['grid gap-3 px-6 pb-4', gridClass]">
                            <div
                                v-for="(item, index) in pager.lists"
                                :key="item.id ?? index"
                                :class="[
                                    'relative rounded-2xl overflow-hidden aspect-[3/4] cursor-pointer group border-2 transition-all',
                                    isChoose(item)
                                        ? 'border-primary shadow-lg shadow-[#0065fb]/15 scale-[0.97]'
                                        : 'border-[transparent] hover:border-slate-200',
                                ]"
                                @click="handleSelect(item)">
                                <ElImage :src="item.pic || item.content" fit="cover" lazy class="w-full h-full" />

                                <div v-if="isVideo(item) && item.duration" class="absolute top-2 left-2 z-10">
                                    <div
                                        class="inline-flex items-center px-2 py-1 bg-[#000000]/40 backdrop-blur-sm rounded-md">
                                        <span class="text-[9px] text-white font-black tracking-wider leading-none">
                                            {{ formatDuration(item.duration) }}
                                        </span>
                                    </div>
                                </div>

                                <video
                                    v-if="isVideo(item)"
                                    :src="item.content"
                                    class="w-full h-full object-cover absolute inset-0 -z-[1]"
                                    :autoplay="false"
                                    :controls="false"
                                    preload="metadata" />

                                <div
                                    v-if="isChoose(item)"
                                    class="absolute inset-0 bg-[#0065fb]/20 flex items-start justify-end p-2">
                                    <div
                                        class="w-6 h-6 rounded-full bg-primary flex items-center justify-center shadow-md">
                                        <Icon name="el-icon-Check" color="#fff" :size="12" />
                                    </div>
                                </div>
                                <div
                                    v-if="isChoose(item)"
                                    class="absolute bottom-2 right-2 w-6 h-6 rounded-full bg-primary flex items-center justify-center shadow-md">
                                    <span class="text-[10px] text-white font-black">{{ getChooseIndex(item) }}</span>
                                </div>

                                <div
                                    v-else
                                    class="absolute top-2 right-2 w-6 h-6 rounded-full bg-[#000000]/20 border-2 border-white opacity-0 group-hover:opacity-100 transition-opacity" />

                                <div
                                    v-if="isVideo(item)"
                                    class="absolute inset-0 bg-[#000000]/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10">
                                    <div class="w-12 h-12" @click.stop="handlePreviewVideo(item)">
                                        <play-btn />
                                    </div>
                                </div>

                                <div class="absolute bottom-2 left-2">
                                    <div
                                        class="inline-flex items-center px-2 py-1 bg-[#000000]/40 backdrop-blur-sm rounded-md">
                                        <span
                                            class="text-[9px] text-white font-black uppercase tracking-wider leading-none">
                                            {{ isVideo(item) ? "视频" : "图片" }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <load-text :is-load="pager.isLoad" />
                    </ElScrollbar>
                </div>
            </div>

            <div
                class="px-8 py-4 border-t border-slate-100 flex items-center justify-between shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <div class="flex items-center gap-4">
                    <div
                        v-if="multiple && limit && limit > 1 && !isGroupListMode"
                        class="flex items-center gap-2 cursor-pointer group"
                        @click="toggleSelect">
                        <div
                            :class="[
                                'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all',
                                isCurrentPageAllSelected
                                    ? 'bg-primary border-primary'
                                    : 'border-slate-200 group-hover:border-[#0065fb]/50',
                            ]">
                            <Icon v-if="isCurrentPageAllSelected" name="el-icon-Check" color="#fff" :size="11" />
                        </div>
                        <span class="text-[13px] font-black text-slate-500 group-hover:text-slate-700 transition-colors"
                            >全选</span
                        >
                    </div>
                    <span class="text-[12px] font-black text-slate-400">
                        已选 <span class="text-primary">{{ totalChooseCount }}</span>
                        <template v-if="limit"> / {{ limit }}</template> 个素材
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="totalChooseCount > 0"
                        class="px-4 h-10 rounded-xl text-xs font-black text-slate-400 hover:text-red-400 hover:bg-red-50 transition-all"
                        @click="clearAll">
                        清空
                    </button>
                    <button
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95"
                        @click="close">
                        取消
                    </button>
                    <button
                        class="px-10 h-11 rounded-xl bg-primary text-white text-sm font-[1000] shadow-lg shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:scale-100"
                        :disabled="isConfirming"
                        @click="confirm">
                        <span v-if="isConfirming" class="flex items-center gap-2">
                            <span class="animate-spin">
                                <Icon name="el-icon-Loading" :size="14" />
                            </span>
                            处理中...
                        </span>
                        <span v-else>
                            确定选择
                            <span v-if="totalChooseCount > 0" class="opacity-70 text-xs ml-1"
                                >({{ totalChooseCount }})</span
                            >
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </popup>

    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />

    <teleport to="body">
        <transition name="fade">
            <div v-if="showProgressPop" class="fixed inset-0 z-[9999] flex items-end justify-center">
                <div class="absolute inset-0 bg-[#000000]/50" />
                <transition name="slide-up">
                    <div
                        v-if="isProgressVisible"
                        class="relative bg-white rounded-t-3xl w-full max-w-lg px-8 pt-7 pb-10">
                        <p class="text-base font-black text-gray-900 mb-1">正在处理素材</p>
                        <p class="text-xs text-slate-400 mb-6">{{ progressDesc }}</p>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mb-3">
                            <div
                                class="h-full bg-primary rounded-full transition-all duration-500"
                                :style="{ width: progressPercent + '%' }" />
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-400 mb-4">
                            <span>{{ progressStep }}</span>
                            <span class="font-black text-primary">{{ progressPercent }}%</span>
                        </div>
                        <div
                            v-if="hasLimit && previewDuration > 0"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#f0f5ff] mt-2">
                            <Icon name="el-icon-Clock" color="#0065fb" :size="16" />
                            <span class="text-xs text-primary">
                                已处理时长：<strong>{{ formatDuration(previewDuration) }}</strong> / 限制
                                {{ formatDuration((props.durationLimit ?? 0) * 60) }}
                            </span>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>

    <teleport to="body">
        <transition name="fade">
            <div v-if="showOverLimitPop" class="fixed inset-0 z-[9999] flex items-center justify-center">
                <div class="absolute inset-0 bg-[#000000]/50" @click="handleOverLimitReselect" />
                <transition name="zoom">
                    <div
                        v-if="isOverLimitVisible"
                        class="relative bg-white rounded-3xl mx-8 w-[420px] px-8 py-7 shadow-2xl">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                                <Icon name="el-icon-WarningFilled" color="#f59e0b" :size="22" />
                            </div>
                            <p class="text-base font-black text-gray-900">素材超出时长限制</p>
                        </div>
                        <p class="text-sm text-slate-500 leading-relaxed mb-2">
                            所选素材总时长超过限制（{{ formatDuration((props.durationLimit ?? 0) * 60) }}），
                            已自动为你保留前 <strong class="text-primary">{{ overLimitResult.kept }}</strong> 个素材
                            （共 <strong class="text-primary">{{ formatDuration(overLimitResult.keptDuration) }}</strong
                            >）， 丢弃了 <strong class="text-red-400">{{ overLimitResult.dropped }}</strong> 个。
                        </p>
                        <p class="text-xs text-slate-400 mb-6">
                            图片按 {{ props.imageDuration ?? 2 }}s/张，视频按实际时长计算
                        </p>
                        <div class="flex gap-3">
                            <button
                                class="flex-1 h-11 rounded-xl border border-slate-200 text-sm font-black text-slate-500 hover:bg-slate-50 transition-all"
                                @click="handleOverLimitReselect">
                                重新选择
                            </button>
                            <button
                                class="flex-1 h-11 rounded-xl bg-primary text-white text-sm font-black hover:bg-[#0056d6] transition-all shadow-lg shadow-[#0065fb]/20"
                                @click="handleOverLimitConfirm">
                                确认使用
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>

    <teleport to="body">
        <transition name="fade">
            <div v-if="showCountOverLimitPop" class="fixed inset-0 z-[9999] flex items-center justify-center">
                <div class="absolute inset-0 bg-[#000000]/50" @click="handleCountOverLimitReselect" />
                <transition name="zoom">
                    <div
                        v-if="isCountOverLimitVisible"
                        class="relative bg-white rounded-3xl mx-8 w-[440px] px-8 py-7 shadow-2xl">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                                <Icon name="el-icon-WarningFilled" color="#f59e0b" :size="22" />
                            </div>
                            <p class="text-base font-black text-gray-900">素材数量超出限制</p>
                        </div>
                        <p class="text-sm text-slate-500 leading-relaxed mb-2">
                            所选素材共 <strong class="text-gray-800">{{ countOverLimitResult.total }}</strong> 个，
                            超出当前限制（<strong class="text-primary">{{ props.limit }}</strong> 条），
                            系统将自动为你保留前 <strong class="text-primary">{{ props.limit }}</strong> 个素材， 丢弃
                            <strong class="text-red-400">{{ countOverLimitResult.dropped }}</strong> 个。
                        </p>
                        <p class="text-xs text-slate-400 mb-4">素材将按照分组内的排列顺序依次保留</p>
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 mb-6">
                            <Icon name="el-icon-List" color="#f59e0b" :size="16" />
                            <div class="flex items-center gap-1 text-xs">
                                <span class="text-slate-500">合并后素材：</span>
                                <strong class="text-gray-800">{{ countOverLimitResult.total }} 个</strong>
                                <span class="text-slate-300 mx-1">→</span>
                                <span class="text-slate-500">实际使用：</span>
                                <strong class="text-primary">{{ props.limit }} 个</strong>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button
                                class="flex-1 h-11 rounded-xl border border-slate-200 text-sm font-black text-slate-500 hover:bg-slate-50 transition-all"
                                @click="handleCountOverLimitReselect">
                                重新选择
                            </button>
                            <button
                                class="flex-1 h-11 rounded-xl bg-primary text-white text-sm font-black hover:bg-[#0056d6] transition-all shadow-lg shadow-[#0065fb]/20"
                                @click="handleCountOverLimitConfirm">
                                确认使用
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

<script setup lang="ts">
import { batchGetVideoInfoByUrl } from "@/api/app";
import { getMaterialLibraryList, getMaterialLibraryGroupList, batchUpdateMaterialDate } from "@/api/material";

enum ShowType {
    ALL = 0,
    GROUP = 1,
}

const props = withDefaults(
    defineProps<{
        type?: "video" | "image" | "all";
        multiple?: boolean;
        limit?: number;
        mode?: "all" | "list" | "group";
        durationLimit?: number;
        imageDuration?: number;
        disableGroupSelect?: boolean;
    }>(),
    {
        type: "all",
        multiple: true,
        mode: "all",
        imageDuration: 2,
        durationLimit: 0,
        disableGroupSelect: false,
    },
);

const emit = defineEmits<{
    (e: "select", value: any[]): void;
    (e: "close"): void;
}>();

const hasLimit = computed(() => !!props.durationLimit && props.durationLimit > 0);

// ── 弹窗 ref ─────────────────────────────────────────
const popupRef = shallowRef();
const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef();

// ── Tab 切换 ─────────────────────────────────────────
const currShowType = ref<ShowType>(ShowType.ALL);

const isGroupListMode = computed(() => {
    if (props.mode === "list") return false;
    if (props.mode === "group") return !currentGroupItem.id;
    return currShowType.value === ShowType.GROUP && !currentGroupItem.id;
});

const isGroupInnerMode = computed(() => {
    if (props.mode === "group") return !!currentGroupItem.id;
    return currShowType.value === ShowType.GROUP && !!currentGroupItem.id;
});

// ── 标题 / 图标 ───────────────────────────────────────
const dialogTitle = computed(() => {
    if (props.mode === "group") return "选择分组";
    if (props.type === "video") return "选择视频素材";
    if (props.type === "image") return "选择图片素材";
    return "选择素材";
});

const headerIcon = computed(() => {
    if (props.type === "video") return "el-icon-VideoCamera";
    return "el-icon-Picture";
});

const gridClass = computed(() => {
    return "grid-cols-4";
});

// ── 数据 ─────────────────────────────────────────────
const chooseLists = ref<any[]>([]);
const chooseGroups = ref<any[]>([]);
const showChoosePanel = ref(false);
const isLoading = ref(false);
const isConfirming = ref(false);
const currentGroupItem = reactive<any>({ id: "", name: "" });

const totalChooseCount = computed(() => chooseGroups.value.length + chooseLists.value.length);

const commonParams = reactive({ page_no: 1, page_size: 20 });

const { getLists, pager } = usePaging({
    fetchFun: (params: any) =>
        isGroupListMode.value
            ? getMaterialLibraryGroupList({ ...commonParams, ...params })
            : getMaterialLibraryList({ ...commonParams, ...params }),
    params: commonParams,
    isScroll: true,
});

// ── 分页加载 ──────────────────────────────────────────
const loadMore = (e: string) => {
    if (e === "bottom" && pager.isLoad && !pager.loading) {
        commonParams.page_no++;
        getLists();
    }
};

const triggerReload = async () => {
    isLoading.value = true;
    pager.lists = [];

    // 先组装好所有参数，再统一赋值
    const params: any = { page_no: 1, page_size: commonParams.page_size };

    if (!isGroupListMode.value) {
        params.m_type = props.type === "all" ? "" : props.type === "video" ? 2 : 1;
        params.group_id = currentGroupItem.id || "";
    } else {
        params.group_id = undefined;
        params.m_type = undefined;
    }

    Object.assign(commonParams, params);

    await getLists();

    isLoading.value = false;
};

// ── 工具函数 ──────────────────────────────────────────
const isVideo = (item: any) => item.m_type == 2;
const isImage = (item: any) => item.m_type == 1;
const isChoose = (item: any) => chooseLists.value.some((i) => i.id === item.id);
const isChooseGroup = (group: any) => chooseGroups.value.some((g) => g.id === group.id);
const getChooseIndex = (item: any) => chooseLists.value.findIndex((i) => i.id === item.id) + 1;

const formatDuration = (seconds: number): string => {
    if (!seconds || seconds <= 0) return "";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    if (m === 0) return `${s}s`;
    return `${m}分${s > 0 ? s + "秒" : ""}`;
};

// ── 全选 ─────────────────────────────────────────────
const isCurrentPageAllSelected = computed(() => {
    if (pager.lists.length === 0) return false;
    const unselected = pager.lists.filter((item: any) => !isChoose(item));
    if (unselected.length === 0) return true;
    const remaining = (props.limit ?? Infinity) - chooseLists.value.length;
    return remaining <= 0;
});

// limit=0 视为"禁止选择"
const effectiveLimit = computed(() => {
    if (props.limit === 0) return 0;
    return props.limit; // undefined 表示不限
});

const isSelectionDisabled = computed(() => effectiveLimit.value === 0);

const toggleSelect = () => {
    if (isCurrentPageAllSelected.value) {
        const currentIds = new Set(pager.lists.map((i: any) => i.id));
        chooseLists.value = chooseLists.value.filter((i) => !currentIds.has(i.id));
        if (totalChooseCount.value === 0) showChoosePanel.value = false;
    } else {
        for (const item of pager.lists) {
            if (effectiveLimit.value != null && chooseLists.value.length >= effectiveLimit.value) break;
            if (!isChoose(item)) chooseLists.value.push(item);
        }
    }
};

// ── 选择操作 ──────────────────────────────────────────
const handleSelect = (item: any) => {
    if (!item) return;
    if (isSelectionDisabled.value) {
        feedback.msgWarning("当前不允许选择素材");
        return;
    }

    if (isChoose(item)) {
        chooseLists.value = chooseLists.value.filter((i) => i.id !== item.id);
        if (totalChooseCount.value === 0) showChoosePanel.value = false;
        return;
    }
    const isSingle = !props.multiple || props.limit === 1;
    if (isSingle) {
        chooseLists.value = [item];
        return;
    }
    if (effectiveLimit.value != null && chooseLists.value.length >= effectiveLimit.value) {
        feedback.msgWarning(`最多选择 ${effectiveLimit.value} 个素材`);
        return;
    }
    chooseLists.value.push(item);
};

const toggleChooseGroup = (group: any) => {
    if (props.disableGroupSelect) return;
    if (isChooseGroup(group)) {
        removeChooseGroup(group);
    } else {
        chooseGroups.value.push(group);
    }
};

const removeChooseGroup = (group: any) => {
    chooseGroups.value = chooseGroups.value.filter((g) => g.id !== group.id);
    if (totalChooseCount.value === 0) showChoosePanel.value = false;
};

const clearAll = () => {
    chooseLists.value = [];
    chooseGroups.value = [];
    showChoosePanel.value = false;
};

const toggleChoosePanel = () => {
    if (totalChooseCount.value === 0) return;
    showChoosePanel.value = !showChoosePanel.value;
};

// ── 分组导航 ──────────────────────────────────────────
const handleGroupItemClick = (item: any) => {
    if (props.mode === "group") {
        if (!props.disableGroupSelect) toggleChooseGroup(item);
    } else {
        enterGroup(item);
    }
};

const enterGroup = (item: any) => {
    currentGroupItem.id = item.id;
    currentGroupItem.name = item.name;
    triggerReload();
};

const backToGroupList = () => {
    currentGroupItem.id = "";
    currentGroupItem.name = "";
    triggerReload();
};

const handleShowType = (type: ShowType) => {
    if (currShowType.value === type) return;
    currShowType.value = type;
    currentGroupItem.id = "";
    currentGroupItem.name = "";
    triggerReload();
};

// ── 视频预览 ──────────────────────────────────────────
const handlePreviewVideo = async (item: any) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(item.content);
};

// ── 进度弹窗 ──────────────────────────────────────────
const ANIM_DURATION = 300;
const showProgressPop = ref(false);
const isProgressVisible = ref(false);
const progressDesc = ref("");
const progressStep = ref("");
const progressPercent = ref(0);
const previewDuration = ref(0);

const setProgress = (desc: string, step: string, percent: number) => {
    progressDesc.value = desc;
    progressStep.value = step;
    progressPercent.value = percent;
};

const openProgressPop = async () => {
    showProgressPop.value = true;
    await nextTick();
    isProgressVisible.value = true;
};

const closeProgressPop = () => {
    isProgressVisible.value = false;
    setTimeout(() => (showProgressPop.value = false), ANIM_DURATION);
};

// ── 时长超限弹窗 ──────────────────────────────────────
const showOverLimitPop = ref(false);
const isOverLimitVisible = ref(false);
const overLimitResult = reactive({ kept: 0, keptDuration: 0, dropped: 0 });
let pendingResult: any[] = [];

const openOverLimitPop = async () => {
    showOverLimitPop.value = true;
    await nextTick();
    isOverLimitVisible.value = true;
};

const closeOverLimitPop = () => {
    isOverLimitVisible.value = false;
    setTimeout(() => (showOverLimitPop.value = false), ANIM_DURATION);
};

const handleOverLimitConfirm = () => {
    closeOverLimitPop();
    emitResult(pendingResult);
};

const handleOverLimitReselect = () => {
    closeOverLimitPop();
    pendingResult = [];
};

// ── 数量超限弹窗 ──────────────────────────────────────
const showCountOverLimitPop = ref(false);
const isCountOverLimitVisible = ref(false);
const countOverLimitResult = reactive({ total: 0, dropped: 0 });
let countOverLimitResolve: ((confirmed: boolean) => void) | null = null;

const openCountOverLimitPop = (total: number, dropped: number): Promise<boolean> => {
    countOverLimitResult.total = total;
    countOverLimitResult.dropped = dropped;
    showCountOverLimitPop.value = true;
    nextTick(() => (isCountOverLimitVisible.value = true));
    return new Promise((resolve) => {
        countOverLimitResolve = resolve;
    });
};

const closeCountOverLimitPop = () => {
    isCountOverLimitVisible.value = false;
    setTimeout(() => (showCountOverLimitPop.value = false), ANIM_DURATION);
};

const handleCountOverLimitReselect = () => {
    closeCountOverLimitPop();
    countOverLimitResolve?.(false);
    countOverLimitResolve = null;
};

const handleCountOverLimitConfirm = () => {
    closeCountOverLimitPop();
    countOverLimitResolve?.(true);
    countOverLimitResolve = null;
};

// ── 时长工具 ──────────────────────────────────────────
const getItemDuration = (item: any): number => {
    if (isImage(item)) return props.imageDuration ?? 2;
    return item.duration || 0;
};

const fetchMissingDurations = async (
    items: any[],
    onProgress: (done: number, total: number) => void,
): Promise<any[]> => {
    const missing = items.filter((item) => !isImage(item) && (!item.duration || item.duration <= 0));
    if (missing.length === 0) return items;

    const urlToItem = new Map<string, any>(missing.map((item) => [item.content, item]));
    const batchSize = 20;
    const batches: any[][] = [];
    for (let i = 0; i < missing.length; i += batchSize) {
        batches.push(missing.slice(i, i + batchSize));
    }

    let done = 0;
    for (const batch of batches) {
        try {
            const { results } = await batchGetVideoInfoByUrl({
                video_urls: batch.map((item) => item.content),
            });
            results.forEach((r: any) => {
                const item = urlToItem.get(r.url);
                if (item && r.data?.duration > 0) item.duration = r.data.duration;
            });
            batchUpdateMaterialDate(
                results.map((r: any) => ({ id: urlToItem.get(r.url)?.id, duration: r.data?.duration })),
            );
        } finally {
            done += batch.length;
            onProgress(done, missing.length);
        }
    }
    return items;
};

const applyDurationLimit = (
    items: any[],
): { result: any[]; kept: number; keptDuration: number; dropped: number; exceeded: boolean } => {
    if (!hasLimit.value) {
        return { result: items, kept: items.length, keptDuration: 0, dropped: 0, exceeded: false };
    }
    const maxSeconds = props.durationLimit! * 60;
    let accumulated = 0;
    const kept: any[] = [];
    for (const item of items) {
        const d = getItemDuration(item);
        if (accumulated + d <= maxSeconds) {
            accumulated += d;
            kept.push(item);
        } else {
            break;
        }
    }
    const dropped = items.length - kept.length;
    return { result: kept, kept: kept.length, keptDuration: accumulated, dropped, exceeded: dropped > 0 };
};

// ── 确认提交 ──────────────────────────────────────────
const confirm = async () => {
    if (totalChooseCount.value === 0) {
        feedback.msgWarning(
            `至少选择一个${props.type === "all" ? "视频或图片" : props.type === "video" ? "视频" : "图片"}`,
        );
        return;
    }
    if (isConfirming.value) return;

    isConfirming.value = true;
    previewDuration.value = 0;
    await openProgressPop();

    try {
        setProgress("正在从分组中获取素材列表...", "步骤 1/3：拉取分组", 5);

        // 1. 拉取分组素材
        const groupItems: any[] = [];
        for (let gi = 0; gi < chooseGroups.value.length; gi++) {
            const group = chooseGroups.value[gi];
            setProgress(
                `正在获取分组「${group.name}」的素材...`,
                `步骤 1/3：分组 ${gi + 1}/${chooseGroups.value.length}`,
                Math.round(5 + (gi / chooseGroups.value.length) * 30),
            );
            try {
                const { lists } = await getMaterialLibraryList({
                    page_no: 1,
                    page_size: 999,
                    group_id: group.id,
                });
                groupItems.push(
                    ...lists.map((item: any) => ({
                        ...item,
                        url: item.content,
                        type: item.m_type === 1 ? "image" : "video",
                        pic: isImage(item) ? item.pic || item.content : item.pic,
                        _fromGroup: group.id,
                    })),
                );
            } catch (e) {
                console.error(`分组 ${group.name} 拉取失败`, e);
            }
        }

        // 2. 合并去重
        setProgress("正在合并素材...", "步骤 2/3：合并去重", 40);
        const singleItems = chooseLists.value.map((item: any) => ({
            ...item,
            url: item.content,
            type: item.m_type === 1 ? "image" : "video",
            pic: isImage(item) ? item.pic || item.content : item.pic,
        }));
        const idSet = new Set(singleItems.map((i: any) => i.id));
        const mergedGroupItems: any[] = [];
        for (const item of groupItems) {
            if (!idSet.has(item.id)) {
                idSet.add(item.id);
                mergedGroupItems.push(item);
            }
        }
        let allItems = [...singleItems, ...mergedGroupItems];

        // 3. 数量超限检查
        if (props.limit && allItems.length > props.limit) {
            setProgress("素材数量超出限制，等待确认...", "步骤 3/3：数量校验", 45);
            closeProgressPop();
            const confirmed = await openCountOverLimitPop(allItems.length, allItems.length - props.limit);
            if (!confirmed) {
                isConfirming.value = false;
                return;
            }
            allItems = allItems.slice(0, props.limit);
            await openProgressPop();
        }

        // 4. 时长限制
        if (hasLimit.value) {
            const videosWithoutDuration = allItems.filter(
                (item) => !isImage(item) && (!item.duration || item.duration <= 0),
            );
            if (videosWithoutDuration.length > 0) {
                setProgress(`正在获取 ${videosWithoutDuration.length} 个视频的时长信息...`, "步骤 3/3：获取时长", 50);
                allItems = await fetchMissingDurations(allItems, (done, total) => {
                    const p = Math.round(50 + (done / total) * 35);
                    previewDuration.value = allItems
                        .slice(0, done)
                        .reduce((acc, item) => acc + getItemDuration(item), 0);
                    setProgress(`正在获取视频时长 (${done}/${total})...`, "步骤 3/3：获取时长", p);
                });
            } else {
                setProgress("正在校验时长限制...", "步骤 3/3：校验时长", 85);
            }

            setProgress("正在校验时长限制...", "完成", 95);
            const { result, kept, keptDuration, dropped, exceeded } = applyDurationLimit(allItems);
            closeProgressPop();
            isConfirming.value = false;

            if (exceeded) {
                pendingResult = result;
                overLimitResult.kept = kept;
                overLimitResult.keptDuration = keptDuration;
                overLimitResult.dropped = dropped;
                await openOverLimitPop();
            } else {
                emitResult(result);
            }
        } else {
            setProgress("完成", "完成", 100);
            closeProgressPop();
            isConfirming.value = false;
            emitResult(allItems);
        }
    } catch (e) {
        closeProgressPop();
        isConfirming.value = false;
        feedback.msgError(e || "处理失败，请重试");
    }
};

const emitResult = (result: any[]) => {
    emit(
        "select",
        result.map((item) => ({
            id: item.id,
            name: item.name,
            pic: item.pic,
            url: item.content,
            size: item.size,
            duration: item.duration,
            type: item.m_type === 1 ? "image" : "video",
        })),
    );
    close();
    clearAll();
    pendingResult = [];
};

// ── 打开 / 关闭 ───────────────────────────────────────
const initShowType = () => {
    currShowType.value = props.mode === "group" ? ShowType.GROUP : ShowType.ALL;
    currentGroupItem.id = "";
    currentGroupItem.name = "";
};

const open = () => {
    initShowType();
    clearAll();
    popupRef.value?.open();
    triggerReload();
};

const close = () => {
    emit("close");
};

defineExpose({ open, close });
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.zoom-enter-active,
.zoom-leave-active {
    transition: all 0.25s ease;
}
.zoom-enter-from,
.zoom-leave-to {
    opacity: 0;
    transform: scale(0.92);
}
</style>
