<template>
    <view class="h-screen flex flex-col bg-white">
        <u-navbar
            title="发布记录"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="px-4 mt-[26rpx] flex items-center gap-x-2">
            <view class="flex-1">
                <u-search
                    v-model="searchValue"
                    height="72"
                    search-icon-color="#ABB1B3"
                    placeholder-color="#ABB1B3"
                    :show-action="false"
                    @clear="handleSearch"
                    @search="handleSearch"></u-search>
            </view>
            <view class="w-[68rpx] flex items-center justify-center" @click="isDelete = !isDelete">
                <u-icon name="setting" color="#A8AAAC" size="48" v-if="!isDelete"></u-icon>
                <text class="text-primary" v-else>取消</text>
            </view>
        </view>
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-4 p-4">
                    <view
                        v-for="(item, index) in dataLists"
                        :key="index"
                        class="bg-[#F8F8FA] rounded-[16rpx] flex items-center gap-x-4 px-4 py-[28rpx] relative"
                        @click="handleRecord(item)">
                        <view class="flex-1">
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1 line-clamp-1 break-all">{{ item.name }}</view>
                                <view
                                    class="px-[20rpx] py-[10rpx] rounded-[8rpx] text-[20rpx] font-bold"
                                    :class="[
                                        item.status != 3
                                            ? 'text-[#C93F8D] bg-[#F3E5EE]'
                                            : 'text-[#479797] bg-[#E6EEF0]',
                                    ]"
                                    >{{ item.status != 3 ? "执行中" : "已完成" }}</view
                                >
                            </view>
                            <view class="text-xs text-[#00000080] mt-[12rpx]"> {{ item.create_time }} </view>
                            <view class="mt-4">
                                <view class="flex gap-x-2">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/timer.svg"
                                        class="w-[32rpx] h-[32rpx]"></image>
                                    <view class="grid grid-cols-3 gap-2 mt-[1rpx]" v-if="item.times.length > 0">
                                        <view
                                            v-for="(timer, index) in item.times"
                                            :key="index"
                                            class="text-xs text-[#00000066]">
                                            {{ timer }}
                                        </view>
                                    </view>
                                    <view v-else class="text-[#00000066] text-xs">-</view>
                                </view>
                                <view class="flex items-center gap-x-2 mt-[24rpx]">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/calendar.svg"
                                        class="w-[32rpx] h-[32rpx]"></image>
                                    <view class="text-xs text-[#00000066]"> {{ item.publish_cycle }}天 </view>
                                </view>
                            </view>
                        </view>
                        <view class="basis-1/4">
                            <view class="circle-container w-[198rpx] h-[198rpx]" v-if="item.status !== 3">
                                <circle-progress
                                    :percent="
                                        isNaN((item.published_count / item.count) * 100)
                                            ? 0
                                            : parseFloat(((item.published_count / item.count) * 100).toFixed(2))
                                    "
                                    bg-color="#E5ECFA"
                                    borderWidth="15rpx"
                                    width="198rpx"
                                    progressColor="#467EF9"
                                    notProgressColor="#E5ECFA">
                                    <template #text>
                                        <view class="text-[#3E7FFF]"
                                            >剩余{{ item.count - item.published_count }}条</view
                                        >
                                    </template>
                                </circle-progress>
                            </view>
                            <view
                                v-else
                                class="rounded-full bg-[#467EF9] w-[198rpx] h-[198rpx] flex items-center justify-center text-white relative">
                                发布完毕
                            </view>
                        </view>
                        <view class="absolute right-[-20rpx] top-[-20rpx] z-[22]" v-if="isDelete">
                            <view
                                class="w-[40rpx] h-[40rpx] bg-[#0000004C] rounded-full flex items-center justify-center"
                                @click.stop="handleDelete(item)">
                                <u-icon name="close" color="#ffffff" size="20"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPublishRecord, deletePublishRecord } from "@/api/digital_human";
const searchValue = ref("");
const dataLists = ref<any[]>([]);

const pagingRef = shallowRef();

const isDelete = ref(false);

const handleSearch = (value: string) => {
    pagingRef.value?.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getPublishRecord({
            page_no,
            page_size,
            name: searchValue.value,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleRecord = (item: any) => {
    uni.navigateTo({
        url: `/ai_modules/digital_human/pages/montage_publish_video/montage_publish_video?id=${item.id}`,
    });
};

const handleDelete = (item: any) => {
    const { id } = item;
    uni.showModal({
        title: "提示",
        content: "删除后任务将终止，切后续将不再执行，注意，该操作不可逆！",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading();
                try {
                    await deletePublishRecord({ id });
                    pagingRef.value?.reload();
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                } catch (error) {
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除失败",
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};
</script>
<style scoped lang="scss"></style>
