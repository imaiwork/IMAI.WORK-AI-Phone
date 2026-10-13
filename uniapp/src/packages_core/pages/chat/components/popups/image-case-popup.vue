<template>
    <popup-bottom
        v-model="show"
        custom-class="bg-white"
        :is-disabled-touch="true"
        :clearable="false"
        :mask-close-able="true"
        height="78%">
        <template #header>
            <view class="px-[40rpx] pt-3 pb-[24rpx] border-b border-solid border-[#F3F4F6]">
                <view class="w-[66rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-3"></view>
                <view class="flex items-center justify-between">
                    <view>
                        <view class="text-[32rpx] font-bold text-[#1F2937]">优秀案例库</view>
                        <view class="text-xs text-[#9CA3AF] mt-[4rpx]">点选案例填充提示词与参考图</view>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                        hover-class="opacity-70"
                        :hover-stay-time="80"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#666666" :size="20"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <scroll-view
                    scroll-y
                    class="flex-1 h-0"
                    :lower-threshold="80"
                    @scrolltolower="loadMore">
                    <view class="px-[24rpx] py-[24rpx]">
                        <view v-if="!lists.length && !loading" class="empty">
                            <text class="empty__text">暂无案例内容</text>
                        </view>
                        <view class="grid">
                            <view
                                v-for="item in lists"
                                :key="item.id"
                                class="card"
                                hover-class="opacity-90"
                                :hover-stay-time="80"
                                @click="onChoose(item)">
                                <view class="card__media">
                                    <image
                                        v-if="item.pic"
                                        :src="item.pic"
                                        mode="aspectFill"
                                        class="card__img"
                                        lazy-load />
                                    <view v-else class="card__ph">
                                        <u-icon name="photo" :size="36" color="#CBD5E1"></u-icon>
                                    </view>
                                    <view class="card__use-bar">
                                        <text class="card__use">使用此案例</text>
                                    </view>
                                </view>
                                <view v-if="item.title" class="card__title-wrap">
                                    <text class="card__title">{{ item.title }}</text>
                                </view>
                            </view>
                        </view>
                        <view v-if="loading" class="foot-tip">加载中…</view>
                        <view v-else-if="finished && lists.length" class="foot-tip">没有更多了</view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getImagePromptList } from "@/api/draw";

export type ImageCaseItem = {
    id: number | string;
    title: string;
    pic: string;
};

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "choose", payload: { title: string; pic: string }): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const lists = ref<ImageCaseItem[]>([]);
const loading = ref(false);
const finished = ref(false);
const pageNo = ref(1);
const pageSize = 20;
const loadedOnce = ref(false);

const normalizeItem = (raw: any, index: number): ImageCaseItem => ({
    id: raw?.id ?? `${pageNo.value}-${index}`,
    title: String(raw?.title || raw?.prompt || raw?.name || "").trim(),
    pic: String(raw?.pic || raw?.image || raw?.url || raw?.cover || "").trim(),
});

const fetchList = async (reset = false) => {
    if (loading.value) return;
    if (!reset && finished.value) return;
    loading.value = true;
    try {
        if (reset) {
            pageNo.value = 1;
            finished.value = false;
            lists.value = [];
        }
        const res: any = await getImagePromptList({
            cid: 0,
            page_no: pageNo.value,
            page_size: pageSize,
        });
        const rows = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : [];
        const mapped = rows.map(normalizeItem);
        lists.value = reset ? mapped : lists.value.concat(mapped);
        finished.value = mapped.length < pageSize;
        if (!finished.value) pageNo.value += 1;
    } catch {
        if (reset) lists.value = [];
    } finally {
        loading.value = false;
    }
};

const loadMore = () => {
    if (!finished.value) fetchList(false);
};

const onChoose = (item: ImageCaseItem) => {
    emit("choose", {
        title: item.title,
        pic: item.pic,
    });
    emit("update:modelValue", false);
};

watch(
    () => props.modelValue,
    (v) => {
        if (!v) return;
        if (!loadedOnce.value) {
            loadedOnce.value = true;
            fetchList(true);
        }
    },
);
</script>

<style lang="scss" scoped>
.empty {
    @apply py-[120rpx] flex justify-center;
}
.empty__text {
    @apply text-[26rpx] text-[#9CA3AF];
}
.grid {
    @apply flex flex-wrap justify-between;
    /* 避免同行矮卡片被拉高，短标题下方出现大块空白 */
    align-items: flex-start;
}
.card {
    @apply w-[48%] mb-[20rpx] rounded-[20rpx] overflow-hidden bg-white border border-solid border-[#F1F5F9];
}
.card__media {
    @apply relative w-full overflow-hidden bg-[#F8FAFC];
    aspect-ratio: 3 / 4;
}
.card__img {
    @apply w-full h-full block;
}
.card__ph {
    @apply w-full h-full flex items-center justify-center;
}
.card__use-bar {
    @apply absolute left-0 right-0 bottom-0 py-[14rpx] flex items-center justify-center;
    background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.55));
}
.card__use {
    @apply px-[18rpx] py-[8rpx] rounded-full bg-white text-primary text-[22rpx] font-semibold;
}
.card__title-wrap {
    @apply px-[16rpx] py-[14rpx];
}
.card__title {
    @apply block text-[22rpx] text-[#64748B];
    /* 固定行高，避免 line-clamp 把字脚裁掉 */
    line-height: 34rpx;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    word-break: break-all;
}
.foot-tip {
    @apply text-center text-[22rpx] text-[#9CA3AF] py-[16rpx];
}
</style>
