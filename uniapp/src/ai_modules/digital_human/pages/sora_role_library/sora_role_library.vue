<template>
    <view class="h-screen flex flex-col page-bg">
        <view class="grow min-h-0 mt-4">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4">
                    <view class="grid grid-cols-2 gap-2">
                        <view class="" v-for="(item, index) in dataLists" :key="index">
                            <view class="h-[440rpx] rounded-[20rpx] relative overflow-hidden">
                                <image :src="item.pic" class="w-full h-full rounded-[20rpx]" mode="aspectFill"></image>
                                <view
                                    v-if="item.status == 1"
                                    class="absolute top-0 left-0 w-full h-full flex items-center justify-center"
                                    @click="previewVideo(item)">
                                    <image src="/static/images/icons/play.svg" class="w-[60rpx] h-[60rpx]"></image>
                                </view>
                                <view
                                    v-else-if="item.status == 2"
                                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center">
                                    <view class="bg-[#E32340] text-xs text-white rounded-[10rpx] px-2 py-1"
                                        >训练失败</view
                                    >
                                    <view class="text-xs text-white px-4 text-center mt-2" v-if="item.remark">
                                        {{ item.remark }}
                                    </view>
                                </view>
                                <view
                                    v-else
                                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center bg-[#00000080]">
                                    <view class="rotation"></view>
                                    <view class="text-white">训练中...</view>
                                </view>
                                <view
                                    class="absolute z-[8889] top-0 left-0 w-full h-full bg-[#00000080] rounded-md"
                                    v-if="isDelete"
                                    @click="clickItem(index)">
                                    <view class="absolute right-2 top-2 z-[88]">
                                        <view
                                            class="radio-wrap"
                                            :class="{
                                                'radio-wrap-active': isChoose(index),
                                            }">
                                            <view
                                                class="h-full w-full flex items-center justify-center"
                                                v-if="isChoose(index)">
                                                <u-icon name="checkmark" color="#fff" :size="20"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="flex items-center justify-between">
                                <view class="break-all line-clamp-1">{{ item.name }}</view>
                                <view class="p-2" @click="handleMore(index)">
                                    <u-icon name="more-dot-fill" color="#7F7F7F"></u-icon>
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
        <view class="px-4 pb-4 pt-1 flex items-center justify-between" v-if="dataLists.length > 0">
            <view class="flex items-center gap-x-2">
                <view
                    class="w-[144rpx] h-[68rpx] flex items-center justify-center text-white bg-primary rounded-md"
                    @click="handleManage">
                    {{ isDelete ? "取消" : "管理" }}
                </view>
                <view
                    v-if="isDelete"
                    class="w-[144rpx] h-[68rpx] flex items-center justify-center text-primary border border-solid border-primary rounded-md"
                    @click="handleSelectAll">
                    全选
                </view>
            </view>
            <view v-if="isDelete">
                <view
                    class="w-[174rpx] h-[68rpx] flex items-center justify-center text-white bg-[#FF2442] rounded-md"
                    @click="handleDelete()">
                    删除 ({{ chooseList.length }})
                </view>
            </view>
        </view>
    </view>
    <video-preview
        v-model="showVideoPreview"
        title="视频预览"
        :video-url="playData.url"
        :pic="playData.pic"
        @confirm="showVideoPreview = false" />
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        title="修改角色名称"
        @confirm="handleConfirmKeywords" />
</template>

<script setup lang="ts">
import { getSoraRoleList, deleteSoraRole, editSoraRole } from "@/api/digital_human";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import KeywordsEdit from "@/ai_modules/digital_human/components/keywords-edit/keywords-edit.vue";
import { saveVideoToPhotosAlbum } from "@/utils/file";

const dataLists = ref<any[]>([]);
const chooseList = ref<number[]>([]);

const playData = reactive<any>({
    url: "",
    pic: "",
});
const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getSoraRoleList({
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const showKeywordsEdit = ref(false);
const keywordsEditRef = ref();
const editIndex = ref(0);

const showVideoPreview = ref(false);
const previewVideo = (item: any) => {
    const { anchor_url, pic } = item;
    playData.url = anchor_url;
    playData.pic = pic;
    showVideoPreview.value = true;
};

const handleMore = (index: number) => {
    editIndex.value = index;
    const { anchor_url, name, id } = dataLists.value[editIndex.value];
    uni.showActionSheet({
        itemList: ["修改名称", "下载视频", "删除"],
        success: (res) => {
            if (res.tapIndex === 0) {
                showKeywordsEdit.value = true;
                keywordsEditRef.value.setFormData(name);
            } else if (res.tapIndex === 1) {
                saveVideoToPhotosAlbum(anchor_url);
            } else if (res.tapIndex === 2) {
                handleDelete(id);
            }
        },
    });
};

const handleConfirmKeywords = async (name: string) => {
    uni.showLoading({
        title: "修改中...",
        mask: true,
    });
    try {
        await editSoraRole({
            id: dataLists.value[editIndex.value].id,
            name,
        });
        uni.hideLoading();
        dataLists.value[editIndex.value].name = name;
        showKeywordsEdit.value = false;
        uni.showToast({ title: "修改成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "修改失败", icon: "none", duration: 3000 });
    }
};

const isChoose = (index: number) => {
    return chooseList.value.includes(index);
};

const clickItem = (index: number) => {
    if (isChoose(index)) {
        chooseList.value = chooseList.value.filter((item, i) => i !== index);
    } else {
        chooseList.value.push(index);
    }
};

const isDelete = ref(false);

const handleManage = () => {
    isDelete.value = !isDelete.value;
    chooseList.value = [];
};

const handleSelectAll = () => {
    if (chooseList.value.length === dataLists.value.length) {
        chooseList.value = [];
    } else {
        chooseList.value = dataLists.value.map((item, index) => index);
    }
};

const handleDelete = async (id?: number | number[]) => {
    const confirmed = await showModal("提示", "确定要删除吗？");
    if (!confirmed) return;

    uni.showLoading({
        title: "删除中...",
        mask: true,
    });

    try {
        const deleteIds = id ? [id] : chooseList.value.map((item) => dataLists.value[item].id);
        await deleteSoraRole({
            id: deleteIds,
        });
        dataLists.value = dataLists.value.filter((item) => !deleteIds.includes(item.id));
        chooseList.value = [];
        uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
        isDelete.value = false;
        chooseList.value = [];
    }
};

async function showModal(title: string, content: string) {
    return new Promise((resolve) =>
        uni.showModal({
            title,
            content,
            success: resolve,
        })
    ).then((res: any) => res.confirm);
}
</script>

<style scoped lang="scss">
.radio-wrap {
    @apply w-[32rpx] h-[32rpx] rounded-full border border-solid border-[#c8c9cc];
}
.radio-wrap-active {
    @apply bg-primary border-primary;
}
</style>
