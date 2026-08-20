<template>
    <view class="h-screen flex flex-col bg-[#F5F7FA]">
        <view class="relative overflow-hidden" style="background: linear-gradient(135deg, #0050cc 0%, #0065fb 100%)">
            <view
                class="absolute -right-12 -top-12 w-[280rpx] h-[280rpx] rounded-full bg-[#ffffff]/5 pointer-events-none" />
            <view
                class="absolute -left-8 bottom-0 w-[180rpx] h-[180rpx] rounded-full bg-[#ffffff]/5 pointer-events-none" />

            <u-navbar
                title="使用教程"
                :border-bottom="false"
                :background="{ background: 'transparent' }"
                title-color="#ffffff"
                back-icon-color="#ffffff" />

            <view class="px-4 pb-4">
                <view class="flex items-center gap-2 bg-[#ffffff]/15 rounded-[20rpx] px-4 h-[76rpx] mb-4">
                    <u-icon name="search" color="rgba(255,255,255,0.7)" size="30" />
                    <input
                        v-model="searchKeyword"
                        class="flex-1 text-white"
                        placeholder="搜索教程名称"
                        placeholder-style="color:rgba(255,255,255,0.45)"
                        @confirm="handleSearch" />
                    <view
                        v-if="searchKeyword"
                        class="w-[40rpx] h-[40rpx] flex items-center justify-center rounded-full bg-[#ffffff]/20"
                        @click="clearSearch">
                        <u-icon name="close" color="#fff" size="20" />
                    </view>
                </view>
            </view>
        </view>

        <view class="flex-1 min-h-0">
            <view v-if="categoryLoading" class="flex flex-col items-center justify-center h-full gap-3">
                <u-loading mode="circle" color="#0065fb" />
                <text class="text-[#C0C4CC] text-xs">加载中...</text>
            </view>

            <view v-else class="flex h-full">
                <scroll-view scroll-y class="w-[152rpx] flex-shrink-0 bg-white">
                    <view class="flex flex-col py-2">
                        <view
                            v-for="(cat, ci) in categoryList"
                            :key="ci"
                            class="relative flex flex-col items-center justify-center py-[28rpx] gap-[10rpx]"
                            @click="handleCategoryChange(ci)">
                            <view
                                v-if="currentCategory === ci"
                                class="absolute left-0 top-1/2 w-[6rpx] h-[48rpx] rounded-r-full bg-[#0065fb]"
                                style="transform: translateY(-50%)" />
                            <view
                                class="w-[64rpx] h-[64rpx] rounded-[18rpx] flex items-center justify-center transition-all"
                                :style="
                                    currentCategory === ci
                                        ? 'background: linear-gradient(135deg, #0050cc, #0065fb)'
                                        : 'background: #f0f6ff'
                                ">
                                <text
                                    class="font-bold"
                                    :class="currentCategory === ci ? 'text-white' : 'text-[#99c2fe]'">
                                    {{ cat.name?.charAt(0) ?? "全" }}
                                </text>
                            </view>
                            <text
                                class="text-[22rpx] text-center px-[8rpx] leading-tight break-all line-clamp-1"
                                :class="currentCategory === ci ? 'text-[#0058e0] font-bold' : 'text-[#9CA3AF]'">
                                {{ cat.name }}
                            </text>
                        </view>
                    </view>
                </scroll-view>

                <view class="grow min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="tutorialList"
                        :fixed="false"
                        :auto="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="flex flex-col gap-4 p-2">
                            <view
                                v-for="(item, vi) in tutorialList"
                                :key="vi"
                                class="rounded-[24rpx] overflow-hidden"
                                style="box-shadow: 0 8rpx 32rpx rgba(0, 101, 251, 0.08)">
                                <view
                                    class="relative h-[180rpx]"
                                    style="background: linear-gradient(135deg, #0065fb 0%, #3b82f6 100%)"
                                    @click="handlePlay(item.main_type, item.main_url)">
                                    <view class="absolute inset-0 px-5 pt-5">
                                        <view class="flex items-center gap-2 mb-2">
                                            <view
                                                class="px-[12rpx] py-[4rpx] rounded-full bg-[#ffffff]/20 flex items-center gap-1">
                                                <text class="text-[18rpx] text-[#ffffff]/80 tracking-wide"
                                                    >视频/图片教程</text
                                                >
                                            </view>
                                        </view>
                                        <view class="text-[30rpx] font-extrabold text-white leading-snug pr-[80rpx]">{{
                                            item.title
                                        }}</view>
                                    </view>
                                    <view
                                        class="absolute right-5 bottom-5 w-[72rpx] h-[72rpx] rounded-full bg-[#ffffff]/25 flex items-center justify-center">
                                        <u-icon name="play-right-fill" color="#fff" size="34" />
                                    </view>
                                </view>
                                <view class="bg-white" v-if="item.sub_items.length > 0">
                                    <view
                                        v-for="(sub, si) in item.sub_items"
                                        :key="si"
                                        class="flex justify-between px-4 py-[24rpx]"
                                        :class="
                                            si < item.sub_items.length - 1
                                                ? 'border-[0] border-b border-solid border-[#F1F5F9]'
                                                : ''
                                        "
                                        @click="handlePlay(sub.type, sub.url)">
                                        <view class="flex items-center gap-3">
                                            <view
                                                class="w-[36rpx] h-[36rpx] rounded-[10rpx] bg-[#f0f6ff] flex items-center justify-center">
                                                <u-icon name="play-right-fill" color="#99c2fe" size="16" />
                                            </view>
                                            <text class="text-[#374151] font-medium">{{ sub.title }}</text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <view class="flex flex-col items-center py-24 gap-3">
                                <view
                                    class="w-[120rpx] h-[120rpx] rounded-full bg-[#f0f6ff] flex items-center justify-center">
                                    <u-icon name="search" color="#99c2fe" size="56" />
                                </view>
                                <text class="text-[#C0C4CC] text-xs">暂无相关教程</text>
                            </view>
                        </template>
                    </z-paging>
                </view>
            </view>
        </view>
    </view>

    <video-preview-v2
        v-if="showVideoPreview"
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        @update:show="showVideoPreview = false" />
</template>
<script setup lang="ts">
import { getTutorialList, getTutorialCategoryList as getTutorialCategoryListApi } from "@/api/app";

// ── 常量 ──────────────────────────────────────────────────────────
const ALL_CATEGORY = { id: null, name: "全部" };
const MEDIA_TYPE_VIDEO = 1;

// ── 分页 ──────────────────────────────────────────────────────────
const pagingRef = ref<any>(null);
const tutorialList = ref<any[]>([]);

// ── 搜索 ──────────────────────────────────────────────────────────
const searchKeyword = ref("");

const clearSearch = () => {
    searchKeyword.value = "";
    pagingRef.value?.reload();
};

const handleSearch = () => {
    pagingRef.value?.reload();
};

// ── 分类 ──────────────────────────────────────────────────────────
const categoryLoading = ref(false);
const currentCategory = ref(0);
const categoryList = ref<any[]>([ALL_CATEGORY]);

const fetchCategoryList = async () => {
    categoryLoading.value = true;
    try {
        const { lists } = await getTutorialCategoryListApi({
            page_no: 1,
            page_size: 1000,
            status: 1,
        });
        categoryList.value = [ALL_CATEGORY, ...lists];
    } catch {
        categoryList.value = [ALL_CATEGORY];
    } finally {
        categoryLoading.value = false;
        currentCategory.value = 0;
        await nextTick();
        pagingRef.value?.reload();
    }
};

const handleCategoryChange = (index: number) => {
    if (currentCategory.value === index) return;
    currentCategory.value = index;
    pagingRef.value?.reload();
};

// ── 列表请求（z-paging 回调）────────────────────────────────────
const queryList = async (page_no: number, page_size: number) => {
    try {
        const currentCat = categoryList.value[currentCategory.value];
        const params: Record<string, any> = { page_no, page_size };

        if (currentCat?.id != null) {
            params.tutorial_category_id = currentCat.id;
        }
        if (searchKeyword.value) {
            params.title = searchKeyword.value;
        }

        const { lists } = await getTutorialList(params);
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

// ── 媒体播放（主/子项合并为一个函数）───────────────────────────
const showVideoPreview = ref(false);
const playItem = ref<{ url: string }>({ url: "" });

const handlePlay = (type: number, url: string) => {
    if (type == MEDIA_TYPE_VIDEO) {
        playItem.value = { url };
        showVideoPreview.value = true;
    } else {
        uni.previewImage({ urls: [url] });
    }
};

// ── 初始化 ────────────────────────────────────────────────────────
onMounted(() => {
    fetchCategoryList();
});
</script>

<style scoped lang="scss">
.bg-mask {
    background: linear-gradient(to top, rgba(15, 23, 42, 0.78) 0%, rgba(15, 23, 42, 0.08) 60%);
}
</style>
