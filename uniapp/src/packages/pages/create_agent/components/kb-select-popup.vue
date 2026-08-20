<template>
    <popup-bottom v-model="show" title="选择知识库" height="70%" custom-class="bg-[#F4F6FB]">
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="px-[32rpx] pt-[8rpx] pb-[16rpx] flex-shrink-0">
                    <view class="search-box">
                        <u-icon name="search" color="#C0C8D8" :size="28" />
                        <input
                            v-model="keyword"
                            class="flex-1 text-[28rpx] text-[#1D2129] ml-[10rpx]"
                            placeholder="搜索知识库名称"
                            confirm-type="search" />
                        <view
                            v-if="keyword"
                            class="w-[40rpx] h-[40rpx] flex items-center justify-center"
                            @click="handleClearKeyword">
                            <u-icon name="close-circle-fill" color="#C0C8D8" :size="28" />
                        </view>
                    </view>
                </view>

                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full w-full">
                        <view class="px-[32rpx] pb-[24rpx] flex flex-col gap-y-[16rpx]">
                            <view
                                v-for="item in filteredList"
                                :key="item.id"
                                class="kb-item"
                                :class="{ 'kb-item--on': isSelected(item.id) }"
                                @click="handleToggle(item)">
                                <view class="kb-avatar">
                                    <image
                                        v-if="item.image"
                                        :src="item.image"
                                        class="w-full h-full rounded-[16rpx]"
                                        mode="aspectFill" />
                                    <u-icon v-else name="file-text" color="#16A34A" :size="32" />
                                </view>
                                <view class="flex-1 min-w-0">
                                    <text class="kb-name">{{ item.name }}</text>
                                    <text class="kb-desc">
                                        {{ item.intro || item.description || "暂无描述" }}
                                    </text>
                                </view>
                                <view class="kb-check" :class="{ 'kb-check--on': isSelected(item.id) }">
                                    <u-icon v-if="isSelected(item.id)" name="checkmark" color="#ffffff" :size="22" />
                                </view>
                            </view>

                            <view v-if="!loading && !filteredList.length" class="py-[80rpx] flex flex-col items-center">
                                <text class="text-[28rpx] text-[#94A3B8]">暂无知识库</text>
                            </view>
                            <view v-if="loading" class="py-[80rpx] flex flex-col items-center">
                                <text class="text-[28rpx] text-[#94A3B8]">加载中...</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="footer">
                    <text class="text-[26rpx] text-[#94A3B8]">
                        已选
                        <text class="text-[#2563EB] font-bold">{{ draftList.length }}</text>
                        个
                    </text>
                    <view class="confirm-btn" @click="handleConfirm">
                        <text class="text-white text-[28rpx] font-bold">确定</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { vectorKnowledgeBaseLists } from "@/api/knowledge_base";

const props = defineProps<{
    modelValue: boolean;
    selected: any[];
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "confirm", list: any[]): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const keyword = ref("");
const loading = ref(false);
const kbList = ref<any[]>([]);
const draftList = ref<any[]>([]);

const filteredList = computed(() => {
    const key = keyword.value.trim().toLowerCase();
    if (!key) return kbList.value;
    return kbList.value.filter((item) => String(item.name || "").toLowerCase().includes(key));
});

const isSelected = (id: string | number) => draftList.value.some((item) => String(item.id) === String(id));

const loadLists = async () => {
    loading.value = true;
    try {
        const { lists } = await vectorKnowledgeBaseLists({
            page_no: 1,
            page_size: 25000,
        });
        kbList.value = lists || [];
    } catch (error) {
        kbList.value = [];
    } finally {
        loading.value = false;
    }
};

const handleToggle = (item: any) => {
    const idx = draftList.value.findIndex((i) => String(i.id) === String(item.id));
    if (idx > -1) {
        draftList.value.splice(idx, 1);
    } else {
        draftList.value.push(item);
    }
};

const handleClearKeyword = () => {
    keyword.value = "";
};

const handleConfirm = () => {
    emit("confirm", [...draftList.value]);
    show.value = false;
};

watch(
    () => props.modelValue,
    (v) => {
        if (!v) return;
        draftList.value = [...(props.selected || [])];
        keyword.value = "";
        loadLists();
    },
);
</script>

<style lang="scss" scoped>
.search-box {
    @apply flex items-center bg-white rounded-[16rpx] px-[20rpx] h-[72rpx];
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.kb-item {
    @apply flex items-center gap-x-[20rpx] bg-white rounded-[24rpx] p-[24rpx] border border-solid border-[transparent] active:opacity-90;
    &--on {
        @apply border-primary bg-[#F8FAFE];
    }
}
.kb-avatar {
    @apply w-[80rpx] h-[80rpx] rounded-[16rpx] bg-[#F0FDF4] flex items-center justify-center flex-shrink-0 overflow-hidden;
}
.kb-name {
    @apply block text-[28rpx] font-bold text-[#1D2129] truncate;
}
.kb-desc {
    @apply block text-[22rpx] text-[#94A3B8] mt-[6rpx] line-clamp-1;
}
.kb-check {
    @apply w-[40rpx] h-[40rpx] rounded-full border-[3rpx] border-solid border-[#E5EAF3] flex items-center justify-center flex-shrink-0;
    &--on {
        @apply border-primary bg-primary;
    }
}
.footer {
    @apply flex-shrink-0 flex items-center justify-between gap-x-[24rpx] bg-white px-[32rpx] pt-[20rpx];
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #eaeef5;
}
.confirm-btn {
    @apply h-[80rpx] px-[56rpx] rounded-[20rpx] flex items-center justify-center active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
}
</style>
