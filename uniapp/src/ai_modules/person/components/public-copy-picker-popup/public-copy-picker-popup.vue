<template>
    <popup-bottom
        v-model="show"
        mode="bottom"
        height="80%"
        border-radius="48"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        :z-index="5003"
        safe-area-inset-bottom
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="pc-handle"></view>
            <view class="pc-hd">
                <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                    <text class="text-[34rpx] font-semibold text-[#202937]">从公共热门文案添加</text>
                    <text class="text-[24rpx] leading-[36rpx] text-[#647184]">
                        「导入」把文案原样存进我的文案；「AI仿写」按当前人设重写后存入，按实际消耗算力扣费
                    </text>
                </view>
                <view class="pc-close" @click="emit('update:modelValue', false)">
                    <u-icon name="close" color="#5B6B80" size="24"></u-icon>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="px-[24rpx] pt-[8rpx] pb-[16rpx]">
                    <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                        <view class="flex items-center gap-[12rpx] pr-[24rpx]">
                            <view
                                v-for="item in typeFilters"
                                :key="item.key"
                                class="pc-chip"
                                :class="activeKey === item.key ? 'pc-chip--on' : ''"
                                @click="handleSwitchType(item.key)">
                                <text>{{ item.label }}</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view
                        scroll-y
                        class="h-full"
                        :lower-threshold="80"
                        @scrolltolower="loadMore">
                        <view class="px-[24rpx] pb-[24rpx]">
                            <view v-if="loadError" class="py-[80rpx] text-center text-[24rpx] text-[#86909C]">
                                公共文案暂时打不开，已加入的文案还在
                            </view>
                            <view
                                v-else-if="!items.length && !loading"
                                class="py-[80rpx] text-center text-[24rpx] text-[#86909C]">
                                这一类还没有上架文案
                            </view>
                            <view v-else class="flex flex-col gap-[16rpx]">
                                <view
                                    v-for="item in items"
                                    :key="item.id"
                                    class="pc-card"
                                    @click="toggleSelect(item)">
                                    <view class="flex items-start gap-[16rpx]">
                                        <view class="flex-1 min-w-0">
                                            <view class="flex items-center flex-wrap gap-[8rpx] mb-[8rpx]">
                                                <text class="text-[28rpx] font-semibold text-[#202937] break-all line-clamp-2">
                                                    {{ item.title || "未填写标题" }}
                                                </text>
                                                <text class="pc-badge" :class="Number(item.ui_type) === 2 ? 'pc-badge--frame' : 'pc-badge--ready'">
                                                    {{ Number(item.ui_type) === 2 ? "提示词框架" : "成品文案" }}
                                                </text>
                                                <text v-if="item.added && !item.has_update" class="pc-badge pc-badge--ok">
                                                    {{ item.edited ? "已同步 · 你改过" : "已同步" }}
                                                </text>
                                                <text v-if="item.has_update" class="pc-badge pc-badge--upd">有更新</text>
                                            </view>
                                            <text class="block text-[24rpx] text-[#647184] leading-[36rpx] line-clamp-3 break-all">
                                                {{ item.content || item.topic || typeLabel(item) }}
                                            </text>
                                        </view>
                                        <view class="pc-check" :class="isSelected(item.id) ? 'pc-check--on' : ''">
                                            <u-icon
                                                v-if="isSelected(item.id)"
                                                name="checkbox-mark"
                                                color="#FFFFFF"
                                                size="22"></u-icon>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view v-if="!loadError && (loading || items.length)" class="py-[24rpx] flex items-center justify-center">
                                <text class="text-[22rpx] text-[#C4C9D4]">
                                    {{ loading ? "加载中…" : finished ? "已加载全部" : "" }}
                                </text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="pc-foot">
                    <text class="text-[24rpx] text-[#647184]">已选 {{ selectedIds.length }} 条</text>
                    <view class="flex items-center gap-[16rpx]">
                        <view class="pc-add" :class="selectedIds.length ? '' : 'opacity-40'" @click="handleImport">
                            <text>导入</text>
                        </view>
                        <view
                            class="pc-ai"
                            :class="selectedIds.length ? '' : 'opacity-40'"
                            @click="handleRewrite">
                            <text>AI仿写</text>
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { addPublicCopies, getPublicCopyBrowse, rewritePublicCopies } from "@/api/publicCopy";

const TYPE_FILTERS = [
    { key: "all", label: "全部", library_type: 0, driver_type: -1 },
    { key: "news", label: "新闻体", library_type: 1, driver_type: 1 },
    { key: "oral", label: "口播文案", library_type: 1, driver_type: 2 },
    { key: "mixcut", label: "混剪口播", library_type: 1, driver_type: 3 },
    { key: "publish", label: "发布文案", library_type: 2, driver_type: 0 },
] as const;

type TypeFilterKey = (typeof TYPE_FILTERS)[number]["key"];

const resolveTypeKey = (libraryType?: number, driverType?: number): TypeFilterKey => {
    const matched = TYPE_FILTERS.find(
        (item) =>
            item.key !== "all" &&
            item.library_type === Number(libraryType) &&
            item.driver_type === Number(driverType),
    );
    return matched?.key ?? "all";
};

const props = defineProps<{
    modelValue: boolean;
    personaId: number | string;
    libraryType?: number;
    driverType?: number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "added"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const PAGE_SIZE = 20;
/** 与服务端 PublicCopywritingRewriteService::MAX_BATCH 对齐：一次串行仿写太多会撞请求超时 */
const MAX_REWRITE = 5;

const typeFilters = TYPE_FILTERS;
const items = ref<any[]>([]);
const loadError = ref(false);
const loading = ref(false);
const finished = ref(false);
const pageNo = ref(1);
const activeKey = ref<TypeFilterKey>(resolveTypeKey(props.libraryType, props.driverType));
const selectedIds = ref<number[]>([]);
let requestSeq = 0;

const currentFilter = computed(() => TYPE_FILTERS.find((item) => item.key === activeKey.value) || TYPE_FILTERS[0]);

const typeLabel = (item: any) => {
    const matched = TYPE_FILTERS.find(
        (filter) => filter.library_type === Number(item.library_type) && filter.driver_type === Number(item.driver_type),
    );
    return matched?.label || "公共文案";
};

/** 已同步且平台没有新版本 —— 只影响「导入」，不影响勾选（勾选后仍可 AI 仿写） */
const isSynced = (item: any) => Boolean(item.added) && !item.has_update;
const isFrame = (item: any) => Number(item.ui_type) === 2;
const isSelected = (id: number) => selectedIds.value.includes(id);

const toggleSelect = (item: any) => {
    if (isSelected(item.id)) {
        selectedIds.value = selectedIds.value.filter((id) => id !== item.id);
        return;
    }
    if (selectedIds.value.length >= MAX_REWRITE) {
        uni.showToast({ title: `一次最多选 ${MAX_REWRITE} 条`, icon: "none" });
        return;
    }
    selectedIds.value = selectedIds.value.concat(item.id);
};

const loadPage = async (reset: boolean) => {
    if (!reset && (loading.value || finished.value)) return;
    const reqId = ++requestSeq;
    const nextPage = reset ? 1 : pageNo.value + 1;
    if (reset) {
        finished.value = false;
        items.value = [];
    }
    loading.value = true;
    loadError.value = false;
    try {
        const filter = currentFilter.value;
        const res: any = await getPublicCopyBrowse({
            persona_id: props.personaId,
            page_no: nextPage,
            page_size: PAGE_SIZE,
            ...(filter.key === "all"
                ? {}
                : { library_type: filter.library_type, driver_type: filter.driver_type }),
        });
        if (reqId !== requestSeq) return;
        const rows = res?.lists || res?.items || [];
        items.value = reset ? rows : items.value.concat(rows);
        pageNo.value = nextPage;
        const count = Number(res?.count || 0);
        finished.value = items.value.length >= count || rows.length < PAGE_SIZE;
    } catch {
        if (reqId !== requestSeq) return;
        loadError.value = reset;
        if (reset) items.value = [];
    } finally {
        if (reqId === requestSeq) loading.value = false;
    }
};

const loadMore = () => {
    loadPage(false);
};

const handleSwitchType = (key: TypeFilterKey) => {
    if (activeKey.value === key) return;
    activeKey.value = key;
    // 换分类后旧的选中项已经不在列表里，继续留着只会占「最多 5 条」的名额
    selectedIds.value = [];
    loadPage(true);
};

const handleImport = async () => {
    const ids = selectedIds.value.filter((id) => {
        const item = items.value.find((row) => row.id === id);
        return item ? !isSynced(item) : false;
    });
    if (!ids.length) {
        uni.showToast({ title: "选中的都已同步，无需重复导入", icon: "none" });
        return;
    }
    uni.showLoading({ title: "导入中...", mask: true });
    try {
        const res: any = await addPublicCopies({
            persona_id: props.personaId,
            ids,
        });
        uni.showToast({ title: res?.message || "已加入", icon: "none", duration: 2500 });
        emit("added");
        emit("update:modelValue", false);
    } catch (error: any) {
        uni.showToast({ title: error || "导入失败", icon: "none", duration: 2500 });
    } finally {
        uni.hideLoading();
    }
};

const handleRewrite = () => {
    if (!selectedIds.value.length) return;
    const picked = items.value.filter((item) => selectedIds.value.includes(item.id));
    // 提示词框架是「给 AI 看的骨架」，本身没成品正文可仿写，先拦在本地避免白扣算力
    if (picked.some((item) => isFrame(item))) {
        uni.showToast({ title: "「提示词框架」类请用导入", icon: "none", duration: 2500 });
        return;
    }
    if (picked.length > MAX_REWRITE) {
        uni.showToast({ title: `一次最多仿写 ${MAX_REWRITE} 条`, icon: "none" });
        return;
    }

    uni.showModal({
        title: "AI仿写",
        content: `按当前人设重写这 ${picked.length} 条文案并存入我的文案，按实际消耗算力扣费，是否继续？`,
        confirmText: "开始仿写",
        success: async (res) => {
            if (!res.confirm) return;
            uni.showLoading({ title: "AI 仿写中...", mask: true });
            try {
                const result: any = await rewritePublicCopies({
                    persona_id: props.personaId,
                    ids: picked.map((item) => item.id),
                });
                uni.showToast({ title: result?.message || "已仿写", icon: "none", duration: 2500 });
                emit("added");
                emit("update:modelValue", false);
            } catch (error: any) {
                uni.showToast({ title: error || "仿写失败", icon: "none", duration: 2500 });
            } finally {
                uni.hideLoading();
            }
        },
    });
};

watch(
    () => props.modelValue,
    (open) => {
        if (!open) {
            selectedIds.value = [];
            return;
        }
        activeKey.value = resolveTypeKey(props.libraryType, props.driverType);
        loadPage(true);
    },
);
</script>

<style lang="scss" scoped>
.pc-handle {
    @apply mx-auto mt-[12rpx] mb-[8rpx] w-[72rpx] h-[8rpx] rounded-full bg-[#E5E7EB];
}
.pc-hd {
    @apply flex items-start gap-[16rpx] px-[32rpx] pb-[16rpx];
}
.pc-close {
    @apply w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center;
}
.pc-chip {
    @apply shrink-0 px-[22rpx] py-[10rpx] rounded-full bg-[#F3F4F6] text-[24rpx] text-[#526B88];
}
.pc-chip--on {
    @apply bg-[#E8F1FF] text-[#075ECF];
}
.pc-card {
    @apply rounded-[20rpx] bg-[#F7F8FA] px-[24rpx] py-[22rpx];
}
.pc-badge {
    @apply text-[20rpx] px-[10rpx] py-[4rpx] rounded-[8rpx] text-white shrink-0;
}
.pc-badge--ready {
    background: #2f73f6;
}
.pc-badge--frame {
    background: #7b61ff;
}
.pc-badge--ok {
    background: #00b578;
}
.pc-badge--upd {
    background: #f86e21;
}
.pc-check {
    @apply shrink-0 w-[36rpx] h-[36rpx] rounded-full border-[4rpx] border-solid border-[#C8C9CC] bg-white;
}
.pc-check--on {
    @apply bg-primary border-primary;
}
.pc-foot {
    @apply flex items-center justify-between px-[24rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))];
}
.pc-add {
    @apply min-h-[72rpx] px-[32rpx] rounded-full bg-primary text-white text-[26rpx] font-semibold flex items-center;
}
/* AI仿写：次级样式，和「导入」区分开，避免误点扣费 */
.pc-ai {
    @apply min-h-[72rpx] px-[28rpx] rounded-full text-[26rpx] font-semibold flex items-center;
    color: #075ecf;
    background: #e8f1ff;
    border: 2rpx solid #b9d5fb;
}
</style>
