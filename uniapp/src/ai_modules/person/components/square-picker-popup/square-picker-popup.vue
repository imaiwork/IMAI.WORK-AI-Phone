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
            <view class="sq-handle"></view>
            <view class="sq-hd">
                <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                    <text class="text-[34rpx] font-semibold text-[#202937]">从素材库广场添加</text>
                    <text class="text-[24rpx] leading-[36rpx] text-[#647184]">引用到当前人设，不占上传空间</text>
                </view>
                <view class="sq-close" @click="emit('update:modelValue', false)">
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
                                class="sq-chip"
                                :class="activeGroupId === 0 ? 'sq-chip--on' : ''"
                                @click="handleSwitchGroup(0)">
                                <text>全部</text>
                            </view>
                            <view
                                v-for="group in groups"
                                :key="group.id"
                                class="sq-chip"
                                :class="activeGroupId === group.id ? 'sq-chip--on' : ''"
                                @click="handleSwitchGroup(group.id)">
                                <text>{{ group.name }}</text>
                            </view>
                        </view>
                    </scroll-view>
                    <view class="flex items-center gap-[16rpx] mt-[16rpx]">
                        <view
                            v-for="item in kindFilters"
                            :key="item.value"
                            class="sq-kind"
                            :class="activeKind === item.value ? 'sq-kind--on' : ''"
                            @click="handleSwitchKind(item.value)">
                            <text>{{ item.label }}</text>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view
                        scroll-y
                        class="h-full"
                        :lower-threshold="80"
                        @scrolltolower="loadMore">
                        <view class="px-[24rpx] pb-[24rpx]">
                            <view v-if="loadError" class="py-[80rpx] text-center text-[24rpx] text-[#86909C]">
                                广场暂时打不开，已加入的素材还在
                            </view>
                            <view
                                v-else-if="!assets.length && !loading"
                                class="py-[80rpx] text-center text-[24rpx] text-[#86909C]">
                                这一组还没有上架素材
                            </view>
                            <view v-else class="sq-grid">
                                <view
                                    v-for="item in assets"
                                    :key="item.id"
                                    class="sq-card"
                                    :class="isJoined(item.id) ? 'opacity-50' : ''"
                                    @click="toggleSelect(item)">
                                    <view class="sq-cover">
                                        <image
                                            :src="item.cover_url || (item.kind === SquareKindEnum.IMAGE ? item.url : '')"
                                            class="absolute inset-0 w-full h-full"
                                            mode="aspectFill"></image>
                                        <text class="sq-type">{{ item.kind === SquareKindEnum.VIDEO ? "视频" : "图片" }}</text>
                                        <view v-if="isJoined(item.id)" class="sq-joined">
                                            <text>已加入</text>
                                        </view>
                                        <view v-else class="sq-check" :class="isSelected(item.id) ? 'sq-check--on' : ''">
                                            <u-icon
                                                v-if="isSelected(item.id)"
                                                name="checkbox-mark"
                                                color="#FFFFFF"
                                                size="22"></u-icon>
                                        </view>
                                    </view>
                                    <text class="sq-name">{{ item.name }}</text>
                                </view>
                            </view>
                            <view v-if="!loadError && (loading || assets.length)" class="py-[24rpx] flex items-center justify-center">
                                <text class="text-[22rpx] text-[#C4C9D4]">
                                    {{ loading ? "加载中…" : finished ? "已加载全部" : "" }}
                                </text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="sq-foot">
                    <text class="text-[24rpx] text-[#647184]">已选 {{ selectedIds.length }} 个</text>
                    <view
                        class="sq-add"
                        :class="selectedIds.length ? '' : 'opacity-40'"
                        @click="handleConfirm">
                        <text>加入我的素材</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { addSquareRefs, getSquareBrowse } from "@/api/square";
import { SquareKindEnum } from "@/ai_modules/person/enums/square";

const props = defineProps<{
    modelValue: boolean;
    personaId: number | string;
    joinedIds: number[];
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "added"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const kindFilters = [
    { label: "全部", value: 0 },
    { label: "视频", value: SquareKindEnum.VIDEO },
    { label: "图片", value: SquareKindEnum.IMAGE },
];

const PAGE_SIZE = 20;

const groups = ref<any[]>([]);
const assets = ref<any[]>([]);
const loadError = ref(false);
const loading = ref(false);
const finished = ref(false);
const pageNo = ref(1);
const activeGroupId = ref(0);
const activeKind = ref(0);
const selectedIds = ref<number[]>([]);
let requestSeq = 0;

const isJoined = (id: number) => props.joinedIds.includes(id);
const isSelected = (id: number) => selectedIds.value.includes(id);

const toggleSelect = (item: any) => {
    if (isJoined(item.id)) return;
    selectedIds.value = isSelected(item.id)
        ? selectedIds.value.filter((id) => id !== item.id)
        : selectedIds.value.concat(item.id);
};

const loadPage = async (reset: boolean) => {
    if (!reset && (loading.value || finished.value)) return;
    const reqId = ++requestSeq;
    const nextPage = reset ? 1 : pageNo.value + 1;
    if (reset) {
        finished.value = false;
        assets.value = [];
    }
    loading.value = true;
    loadError.value = false;
    try {
        const res: any = await getSquareBrowse({
            page_no: nextPage,
            page_size: PAGE_SIZE,
            ...(activeGroupId.value ? { group_id: activeGroupId.value } : {}),
            ...(activeKind.value ? { kind: activeKind.value } : {}),
        });
        if (reqId !== requestSeq) return;
        groups.value = res?.groups || [];
        const rows = res?.lists || res?.assets || [];
        assets.value = reset ? rows : assets.value.concat(rows);
        pageNo.value = nextPage;
        const count = Number(res?.count || 0);
        finished.value = assets.value.length >= count || rows.length < PAGE_SIZE;
    } catch {
        if (reqId !== requestSeq) return;
        loadError.value = reset;
        if (reset) {
            groups.value = [];
            assets.value = [];
        }
    } finally {
        if (reqId === requestSeq) loading.value = false;
    }
};

const loadMore = () => {
    loadPage(false);
};

const handleSwitchGroup = (id: number) => {
    if (activeGroupId.value === id) return;
    activeGroupId.value = id;
    loadPage(true);
};

const handleSwitchKind = (kind: number) => {
    if (activeKind.value === kind) return;
    activeKind.value = kind;
    loadPage(true);
};

const handleConfirm = async () => {
    if (!selectedIds.value.length) return;
    uni.showLoading({ title: "加入中...", mask: true });
    try {
        const res: any = await addSquareRefs({
            persona_id: props.personaId,
            ids: selectedIds.value,
        });
        uni.showToast({ title: res?.message || "已加入", icon: "none", duration: 2500 });
        emit("added");
        emit("update:modelValue", false);
    } catch (error: any) {
        uni.showToast({ title: error || "加入失败", icon: "none", duration: 2500 });
    } finally {
        uni.hideLoading();
    }
};

watch(
    () => props.modelValue,
    (open) => {
        if (!open) {
            selectedIds.value = [];
            return;
        }
        activeGroupId.value = 0;
        activeKind.value = 0;
        loadPage(true);
    },
);
</script>

<style lang="scss" scoped>
.sq-handle {
    @apply mx-auto mt-[12rpx] mb-[8rpx] w-[72rpx] h-[8rpx] rounded-full bg-[#E5E7EB];
}
.sq-hd {
    @apply flex items-start gap-[16rpx] px-[32rpx] pb-[16rpx];
}
.sq-close {
    @apply w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center;
}
.sq-chip {
    @apply shrink-0 px-[22rpx] py-[10rpx] rounded-full bg-[#F3F4F6] text-[24rpx] text-[#526B88];
}
.sq-chip--on {
    @apply bg-[#E8F1FF] text-[#075ECF];
}
.sq-kind {
    @apply px-[18rpx] py-[8rpx] text-[24rpx] text-[#647184];
}
.sq-kind--on {
    @apply text-[#075ECF] font-semibold;
}
.sq-grid {
    @apply grid grid-cols-2 gap-[16rpx];
}
.sq-card {
    @apply rounded-[20rpx] overflow-hidden bg-white;
}
.sq-cover {
    @apply relative aspect-[3/4] bg-[#EEF3FB];
}
.sq-type {
    @apply absolute top-[12rpx] left-[12rpx] text-[18rpx] font-bold text-white px-[10rpx] py-[4rpx] rounded-[8rpx];
    background: rgba(0, 0, 0, 0.55);
}
.sq-joined {
    @apply absolute inset-0 flex items-center justify-center bg-[rgba(0,0,0,0.3)];
    text {
        @apply text-white text-[22rpx];
    }
}
.sq-check {
    @apply absolute top-[12rpx] right-[12rpx] w-[36rpx] h-[36rpx] rounded-full border-[4rpx] border-solid border-white bg-[rgba(0,0,0,0.2)];
}
.sq-check--on {
    @apply bg-primary border-primary;
}
.sq-name {
    @apply block px-[8rpx] py-[10rpx] text-[22rpx] text-[#26364E] line-clamp-1;
}
.sq-foot {
    @apply flex items-center justify-between px-[24rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))];
}
.sq-add {
    @apply min-h-[72rpx] px-[32rpx] rounded-full bg-primary text-white text-[26rpx] font-semibold flex items-center;
}
</style>
