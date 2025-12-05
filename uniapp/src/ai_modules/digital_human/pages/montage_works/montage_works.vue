<template>
    <view class="h-screen flex flex-col dh-bg">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            title="混剪记录"
            title-bold>
        </u-navbar>
        <view class="px-4 mt-[26rpx]">
            <u-search
                v-model="searchValue"
                bg-color="#FFFFFF"
                height="72"
                search-icon-color="#ABB1B3"
                placeholder-color="#ABB1B3"
                :show-action="false"
                @clear="handleSearch"
                @search="handleSearch"></u-search>
        </view>
        <view class="grow min-h-0 mt-[48rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4 flex flex-col gap-4">
                    <view
                        class="relative border-[0] border-b-[1rpx] border-solid border-[#E0E0E0] pb-4"
                        v-for="(item, index) in dataLists"
                        :key="index">
                        <view class="absolute w-full h-full z-[22]" v-if="isChoose" @click="handleChoose(item)"></view>
                        <view class="flex justify-between items-center">
                            <view>
                                <view class="flex items-center gap-x-2 mr-4">
                                    <view class="line-clamp-1 break-all font-bold text-[30rpx]">
                                        {{ item.name }}
                                    </view>
                                    <view class="p-1" @click="handleEdit(index)">
                                        <image
                                            src="/static/images/icons/edit_pen.svg"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                    </view>
                                </view>
                                <view class="text-[#0000007F] mt-[12rpx]">
                                    {{ item.create_time }}
                                </view>
                            </view>
                            <view
                                v-if="!isChoose"
                                class="w-[40rpx] h-[40rpx] flex items-center justify-center bg-[#EAEFF2] rounded-[8rpx]"
                                @click="handleDelete(item.id)">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/delete.svg"
                                    class="w-[28rpx] h-[28rpx]"></image>
                            </view>
                            <view v-else>
                                <image
                                    v-if="isChooseItem(item)"
                                    src="@/ai_modules/digital_human/static/icons/radio_s.svg"
                                    class="w-[32rpx] h-[32rpx]"></image>
                                <image
                                    v-else
                                    src="@/ai_modules/digital_human/static/icons/radio.svg"
                                    class="w-[32rpx] h-[32rpx]"></image>
                            </view>
                        </view>
                        <view class="flex flex-wrap gap-3 mt-4">
                            <view
                                class="h-[324rpx] w-[250rpx] rounded-md overflow-hidden relative"
                                v-for="(res, index) in item.task"
                                :key="index">
                                <image
                                    :src="res.pic"
                                    class="h-full w-full absolute top-0 left-0"
                                    mode="aspectFill"></image>
                                <view class="absolute left-0 top-2 text-xs text-white w-full px-2">
                                    <view class="line-clamp-1 break-all"> {{ res.name }} </view>
                                </view>
                                <template v-if="res.status == 3">
                                    <view
                                        class="w-full h-full flex items-center justify-center gap-1 text-center px-2 text-white">
                                        <view
                                            class="rounded-full bg-[#ffffff33] w-[48rpx] h-[48rpx]"
                                            style="backdrop-filter: blur(5px)"
                                            @click="handlePlay(res)">
                                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                        </view>
                                    </view>
                                </template>
                                <template v-else>
                                    <view
                                        class="bg-[#0000005E] w-full h-full flex flex-col items-center justify-center gap-2 relative">
                                        <template v-if="res.status == 2">
                                            <view
                                                class="w-6 h-6 flex items-center justify-center rounded-full bg-error">
                                                <image
                                                    src="@/ai_modules/digital_human/static/icons/video2.svg"
                                                    class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <view class="text-center text-[#ffffff80] text-[22rpx] px-2">
                                                {{ res.remark || "生成失败" }}
                                            </view>
                                            <view class="text-[#ffffff80] text-center text-[22rpx]">
                                                （请检查训练的视频文件）
                                            </view>
                                        </template>
                                        <template v-else>
                                            <text class="rotation"></text>
                                            <text class="text-xs text-[#ffffff80]">正在生成中</text>
                                            <text class="text-[20rpx] text-[#ffffff80]">几分钟即可生成视频</text>
                                        </template>
                                    </view>
                                </template>
                                <view class="absolute bottom-1 left-1 text-white w-[50%] text-[20rpx]">
                                    {{ res.create_time }}
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="flex-shrink-0 pb-5 pt-2" :class="{ 'shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] bg-white': isChoose }">
            <view class="flex items-center justify-between px-4 gap-[48rpx]">
                <view
                    v-if="!isChoose"
                    class="flex-1 flex items-center justify-center text-white rounded-[8rpx] h-[100rpx] bg-black"
                    @click="createPublishTask">
                    创建发布任务
                </view>
                <template v-else>
                    <view
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[selectedLists.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-bold text-[32rpx]">{{ selectedLists.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view class="flex items-center gap-x-4">
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md shadow-[0_0_0_1rpx_#F1F2F5] bg-white"
                            @click="
                                isChoose = false;
                                selectedLists = [];
                            ">
                            取消
                        </view>
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md text-white"
                            :class="[selectedLists.length > 0 ? 'bg-black' : 'bg-[#787878CC]']"
                            @click="toPublishTask">
                            下一步
                        </view>
                    </view>
                </template>
            </view>
        </view>
    </view>
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview-v2>
    <u-popup v-model="shoeEditPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">编辑名称</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="newName"
                    placeholder="请输入名称"
                    maxlength="30"
                    clearable
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="shoeEditPopup = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="handleEditConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { getShanjianTaskRecord, deleteShanjianTaskRecord, updateShanjianTaskName } from "@/api/digital_human";
import VideoPreviewV2 from "@/ai_modules/digital_human/components/video-preview-v2/video-preview-v2.vue";

const dataLists = ref<any[]>([]);
const dataCount = ref(0);
const searchValue = ref("");

// 是否是选择类型
const isChoose = ref(false);

const selectedLists = ref<any[]>([]);

const shoeEditPopup = ref(false);
const editIndex = ref(-1);
const newName = ref("");

const handleSearch = (value: string) => {
    pagingRef.value?.reload();
};

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists, count } = await getShanjianTaskRecord({
            page_no,
            page_size,
            name: searchValue.value,
        });
        dataCount.value = count;
        lists.forEach((item: any) => {
            item.is_delete = false;
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const showVideoPreview = ref(false);
const playItem = reactive<any>({
    url: "",
    pic: "",
});
const handlePlay = (item: any) => {
    const { video_result_url, pic } = item;
    playItem.url = video_result_url;
    playItem.pic = pic;
    showVideoPreview.value = true;
};

const handleDelete = async (id?: number) => {
    uni.showModal({
        title: "您真的要删除吗？",
        content: "删除后将无法找回，且该操作不可逆！",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中...",
                    mask: true,
                });
                try {
                    await deleteShanjianTaskRecord({
                        id,
                    });
                    pagingRef.value?.reload();
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    return;
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error || "删除失败",
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};

const isChooseItem = (val: any) => {
    return selectedLists.value.some((item) => item.id == val.id);
};

const createPublishTask = () => {
    isChoose.value = true;
};

const handleChoose = (item: any) => {
    const { task } = item;
    // 判断task的任务是不是全部失败，
    const isError = task.every((val: any) => val.status != 3);
    if (isError) {
        uni.$u.toast("只有生成成功的视频才能去发布");
        return;
    }
    if (isChoose.value) {
        if (isChooseItem(item)) {
            selectedLists.value = selectedLists.value.filter((item) => item.id != item.id);
        } else {
            selectedLists.value.push(item);
        }
    }
};

const toPublishTask = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
        params: {
            task_id: JSON.stringify(selectedLists.value.map((item) => item.id)),
            scene: 2,
        },
    });
    isChoose.value = false;
    selectedLists.value = [];
};

const handleEdit = (index: number) => {
    editIndex.value = index;
    newName.value = dataLists.value[index].name;
    shoeEditPopup.value = true;
};

const handleEditConfirm = async () => {
    if (!newName.value) {
        uni.$u.toast("请输入名称");
        return;
    }
    shoeEditPopup.value = false;
    uni.showLoading({
        title: "修改中...",
        mask: true,
    });
    try {
        await updateShanjianTaskName({
            id: dataLists.value[editIndex.value].id,
            name: newName.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "修改成功",
            icon: "none",
            duration: 3000,
        });
        dataLists.value[editIndex.value].name = newName.value;
    } catch (error: any) {
        uni.showToast({
            title: error || "修改失败",
            icon: "none",
            duration: 3000,
        });
    }
};
</script>

<style scoped lang="scss">
.radio-wrap {
    @apply w-[32rpx] h-[32rpx] rounded-full border border-solid border-[#c8c9cc];
}
.radio-wrap-active {
    @apply bg-primary border-primary;
}
</style>
