<template>
    <view class="h-screen flex flex-col dh-bg">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            title="我的创作库"
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
                        <view class="flex justify-between items-center">
                            <view class="">
                                <view class="font-bold"> 视频名称 </view>
                                <view class="flex items-center gap-x-2">
                                    <view class="text-[#0000007F] mt-[12rpx]">
                                        {{ item.name }}
                                    </view>
                                    <view class="p-1" @click="handleEdit(index)">
                                        <image src="/static/images/icons/edit_pen.svg" class="w-4 h-4"></image>
                                    </view>
                                </view>
                            </view>
                            <view
                                class="w-[40rpx] h-[40rpx] flex items-center justify-center bg-[#EAEFF2] rounded-[8rpx]"
                                @click="handleDelete(item.id)">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/delete.svg"
                                    class="w-[28rpx] h-[28rpx]"></image>
                            </view>
                        </view>
                        <view class="h-[324rpx] w-[250rpx] mt-4 rounded-md overflow-hidden relative">
                            <image
                                :src="item.pic"
                                class="h-full w-full absolute top-0 left-0"
                                mode="aspectFill"></image>
                            <view v-if="item.automatic_clip == 1" class="absolute left-2 top-2 text-xs text-white"
                                >AI剪辑</view
                            >
                            <template v-if="item.status == 1">
                                <view
                                    class="w-full h-full flex items-center justify-center gap-1 text-center px-2 text-white relative z-[22]">
                                    <view
                                        class="rounded-full bg-[#ffffff33] w-[68rpx] h-[68rpx]"
                                        @click="handlePlay(item)">
                                        <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                    </view>
                                </view>
                                <view
                                    v-if="item.automatic_clip == 1"
                                    class="absolute bottom-[80rpx] left-0 w-full z-[51] text-[#ffffff80] text-[22rpx] text-center">
                                    <template v-if="item.clip_status == 1 || item.clip_status == 2">
                                        AI智能剪辑中...
                                    </template>
                                    <template v-if="item.clip_status == 3">AI智能剪辑完成</template>
                                    <template v-if="item.clip_status == 4">AI智能剪辑失败</template>
                                </view>
                            </template>
                            <template v-else>
                                <view
                                    class="bg-[#0000005E] w-full h-full flex flex-col items-center justify-center gap-2 relative">
                                    <template class="" v-if="item.status == 2">
                                        <view class="w-6 h-6 flex items-center justify-center rounded-full bg-error">
                                            <image
                                                src="@/ai_modules/digital_human/static/icons/video2.svg"
                                                class="w-[28rpx] h-[28rpx]"></image>
                                        </view>
                                        <view class="text-center text-[#ffffff80] text-[22rpx]">
                                            {{ item.remark || "生成失败" }}
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
                                {{ item.create_time }}
                            </view>
                        </view>
                        <view class="mt-[24rpx] flex gap-x-2" v-if="item.status == 1">
                            <view
                                class="px-[24rpx] py-[14rpx] rounded-[12rpx] text-xs border border-solid border-[#EFEFEF] bg-[#FAFAFAFF]"
                                @click="handlePlay(item, 1)"
                                >查看原视频</view
                            >
                            <view
                                class="px-[24rpx] py-[14rpx] rounded-[12rpx] text-xs border border-solid border-[#EFEFEF] bg-[#FAFAFAFF]"
                                @click="saveVideoToPhotosAlbum(item.result_url)"
                                >下载原视频</view
                            >
                            <view
                                v-if="item.automatic_clip == 1"
                                class="px-[24rpx] py-[14rpx] rounded-[12rpx] text-xs border border-solid border-[#EFEFEF] bg-[#FAFAFAFF]"
                                @click="saveVideoToPhotosAlbum(item.clip_result_url)"
                                >下载智剪视频</view
                            >
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
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
import { digitalHumanLists, deleteDigitalHuman, updateDigitalHuman } from "@/api/digital_human";
import { saveVideoToPhotosAlbum } from "@/utils/file";
import VideoPreviewV2 from "@/ai_modules/digital_human/components/video-preview-v2/video-preview-v2.vue";

const dataLists = ref<any[]>([]);
const dataCount = ref(0);
const searchValue = ref("");

const handleSearch = (value: string) => {
    pagingRef.value?.reload();
};

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists, count } = await digitalHumanLists({
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

const editIndex = ref<number>(-1);
const newName = ref<string>("");
const shoeEditPopup = ref(false);

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
        await updateDigitalHuman({
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

const videoUrl = ref<string>("");
const showVideoPreview = ref(false);
const playType = ref(1);
const playItem = reactive<any>({
    url: "",
    pic: "",
});
const handlePlay = (item: any, type?: number) => {
    const { result_url, clip_result_url, automatic_clip } = item;
    playItem.url = type == 1 ? result_url : automatic_clip == 1 ? clip_result_url : result_url;
    playItem.pic = item.pic;

    showVideoPreview.value = true;
    if (type) {
        playType.value = type;
    }
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
                    await deleteDigitalHuman({
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
</script>

<style scoped lang="scss">
.index-page {
}
.radio-wrap {
    @apply w-[32rpx] h-[32rpx] rounded-full border border-solid border-[#c8c9cc];
}
.radio-wrap-active {
    @apply bg-primary border-primary;
}
</style>
