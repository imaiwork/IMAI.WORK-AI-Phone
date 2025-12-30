<template>
    <view class="h-screen flex flex-col bg-white">
        <view class="flex items-center justify-between my-2 px-4">
            <vieW class="bg-[#F1F2F5] rounded-[16rpx] px-[8rpx]">
                <view class="grid grid-cols-2 h-[84rpx] relative" v-if="false">
                    <view
                        v-for="(item, index) in showTypes"
                        :key="index"
                        class="show-type-item"
                        :class="{ active: item.key == currShowType }"
                        @click="handleShowType(item.key)">
                        {{ item.name }}
                    </view>
                    <view class="tab-slider" :style="{ transform: `translateX(${showTypeIndex * 100}%)` }"></view>
                </view>
            </vieW>
            <view class="flex items-center gap-3" v-if="currShowType == ShowType.ALL">
                <view
                    v-for="(item, index) in materialTypes"
                    :key="index"
                    class="material-type-item"
                    :class="{ active: item.key == currMaterialType }"
                    @click="handleMaterialType(item.key)"
                    >{{ item.name }}</view
                >
            </view>
            <view
                class="bg-primary rounded-[10rpx] text-white px-[28rpx] py-[14rpx]"
                @click="handleUploadMaterial"
                v-if="currShowType == ShowType.ALL"
                >上传素材</view
            >
            <view
                class="bg-black rounded-[10rpx] text-white px-[28rpx] py-[14rpx]"
                v-if="currShowType == ShowType.GROUP"
                @click="handleAddGroup"
                >创作素材组</view
            >
        </view>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :default-page-size="40"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4">
                    <view class="grid grid-cols-4 gap-1" v-if="currShowType == ShowType.ALL">
                        <view
                            class="h-[170rpx] rounded-lg overflow-hidden relative card-gradient"
                            v-for="(item, index) in dataLists"
                            :key="index">
                            <view @click="handleItem(item)" class="w-full h-full">
                                <image
                                    :src="item.pic || item.content"
                                    class="h-full w-full"
                                    mode="aspectFill"
                                    lazy></image>
                            </view>
                            <view
                                v-if="isHandle"
                                class="absolute top-0 left-0 w-full h-full z-[44]"
                                :class="{ 'bg-[#0000004d]': isSelect(item.id) }"
                                @click="handleSelect(item.id)">
                                <view class="absolute top-2 right-2 z-[22] w-[32rpx] h-[32rpx]">
                                    <image
                                        v-if="isSelect(item.id)"
                                        src="/static/images/icons/success.svg"
                                        class="w-full h-full"
                                        lazy></image>
                                    <view class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]" v-else>
                                    </view>
                                </view>
                            </view>
                            <view
                                class="absolute left-1 bottom-1 text-[22rpx] text-white font-bold"
                                v-if="item.m_type == MaterialTypeEnum.VIDEO">
                                {{ formatAudioTime(item.duration) }}
                            </view>
                        </view>
                    </view>
                    <view class="flex flex-col gap-y-2" v-if="currShowType == ShowType.GROUP">
                        <view
                            v-for="(item, index) in dataLists"
                            :key="index"
                            class="bg-[#F1F2F5] rounded-[20rpx] p-1 flex items-center relative">
                            <view class="flex-shrink-0 w-[180rpx] h-[180rpx] rounded-[16rpx] overflow-hidden relative">
                                <image class="w-full h-full" :src="item.pic" mode="aspectFill"></image>
                            </view>
                            <view class="flex-1 ml-4 mr-8">
                                <view class="text-[30rpx] font-bold line-clamp-2 break-all"
                                    >素材组名称素材组名称素材组名称</view
                                >
                                <view class="text-[24rpx] text-[#0000004d] mt-1">
                                    共<text class="text-black mx-[4rpx]">666</text>个素材
                                </view>
                            </view>
                            <view class="absolute top-1 right-2">
                                <view class="text-primary text-xs" @click="handleManageGroup(index)">管理</view>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="bg-white pb-4 px-4 pt-2">
            <view class="flex items-center" :class="[isHandle ? 'justify-between' : 'justify-end']">
                <view v-if="isHandle" class="flex items-center gap-x-4">
                    <view
                        class="bg-primary text-white w-[144rpx] h-[70rpx] flex items-center justify-center rounded-[10rpx]"
                        @click="
                            isHandle = false;
                            handleList = [];
                        "
                        >取消</view
                    >
                    <view class="flex items-center gap-x-2" @click="handleSelectAll()">
                        <image
                            src="/static/images/icons/success.svg"
                            class="w-[32rpx] h-[32rpx]"
                            v-if="handleList.length > 0 && handleList.length == dataLists.length"></image>
                        <view
                            v-else
                            class="rounded-full w-[32rpx] h-[32rpx] shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]"></view>
                        全选
                    </view>
                    <view class="flex items-center gap-x-1" @click="showGroupPopup = true" v-if="false">
                        <view class="w-[28rpx] h-[28rpx] leading-[0]">
                            <image
                                v-if="handleList.length"
                                src="@/packages/static/icons/quit_black.svg"
                                class="w-[28rpx] h-[28rpx]"></image>
                            <image v-else src="@/packages/static/icons/quit.svg" class="w-[28rpx] h-[28rpx]"></image>
                        </view>
                        <text :class="[handleList.length ? 'text-black' : 'text-[#0000004d]']">添加到</text>
                    </view>
                </view>
                <view>
                    <view
                        v-if="isHandle"
                        class="text-white w-[170rpx] h-[70rpx] bg-[#FF2442] flex items-center justify-center rounded-[10rpx]"
                        @click="handleDelete(handleList)">
                        删除({{ handleList.length }})
                    </view>
                    <view
                        v-else
                        class="text-white w-[144rpx] h-[70rpx] bg-black flex items-center justify-center rounded-[10rpx]"
                        @click="isHandle = true">
                        管理
                    </view>
                </view>
            </view>
        </view>
    </view>
    <video-preview v-model="showVideoPreview" :video-url="playItem.url" :poster="playItem.pic"></video-preview>
    <!-- 添加分组弹框 -->
    <popup-bottom
        v-model="showGroupPopup"
        title="选择分组"
        clearable
        :is-disabled-touch="true"
        @close="closeAddGroupPopup">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0">
                    <z-paging v-model="groupDataLists" ref="pagingGroupRef" :fixed="false" @query="queryGroupList">
                        <view class="px-2 flex flex-col gap-y-4">
                            <view
                                v-for="(item, index) in groupDataLists"
                                :key="index"
                                class="bg-[#F1F2F5] rounded-[20rpx] p-1 flex items-center"
                                @click="handleGroup(item.id)">
                                <view class="flex-shrink-0 w-[180rpx] h-[180rpx] rounded-[16rpx] overflow-hidden">
                                    <image class="w-full h-full" :src="item.pic" mode="aspectFill"></image>
                                </view>
                                <view class="flex-1 ml-4">
                                    <view class="text-[30rpx] font-bold line-clamp-2 break-all">素材组名称</view>
                                    <view class="text-[24rpx] text-[#0000004d] mt-1">
                                        共<text class="text-black mx-[4rpx]">666</text>个素材
                                    </view>
                                </view>
                                <view class="w-[40rpx] h-[40rpx] flex-shrink-0 mr-5">
                                    <image
                                        src="/static/images/icons/success.svg"
                                        class="w-full h-full"
                                        v-if="item.id == chooseGroupId"></image>
                                    <view
                                        class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]"
                                        v-else></view>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
                <view class="flex items-center gap-x-[50rpx] p-3">
                    <view
                        class="w-[240rpx] h-[100rpx] flex items-center justify-center bg-[#F3F3F3] font-bold rounded-[20rpx]"
                        @click="closeAddGroupPopup"
                        >取消</view
                    >
                    <view
                        class="flex-1 h-[100rpx] flex items-center justify-center bg-black text-white font-bold rounded-[20rpx]"
                        @click="confirmGroup"
                        >确定</view
                    >
                </view>
            </view>
        </template>
    </popup-bottom>
    <u-popup v-model="showEditPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">编辑名称</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="newName"
                    placeholder="请输入分组名称"
                    maxlength="30"
                    clearable
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="
                        showEditPopup = false;
                        newName = '';
                    ">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="confirmUpdateGroupName"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <u-popup v-model="showAddGroupPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">创建素材组</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="newName"
                    placeholder="请输入素材组名称"
                    maxlength="30"
                    clearable
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="
                        showAddGroupPopup = false;
                        newName = '';
                    ">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="confirmAddGroupName"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
</template>

<script setup lang="ts">
import {
    getMaterialLibraryList,
    deleteMaterialLibrary,
    addMaterialLibrary,
    addMaterialLibraryGroup,
    updateMaterialLibraryGroup,
} from "@/api/material";
import { AppTypeEnum } from "@/enums/appEnums";
import useUpload from "@/hooks/useUpload";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import { formatAudioTime } from "@/utils/util";

enum ShowType {
    ALL,
    GROUP,
}

enum MaterialTypeEnum {
    ALL = "",
    VIDEO = 2,
    IMAGE = 1,
    MUSIC = 6,
}

// 展示类型type
const showTypes = [
    { name: "全部展示", key: ShowType.ALL },
    { name: "分组展示", key: ShowType.GROUP },
];
const currShowType = ref<ShowType>(ShowType.ALL);
const showTypeIndex = computed(() => {
    return showTypes.findIndex((item) => item.key == currShowType.value);
});

// 素材类型
const materialTypes = [
    { name: "全部", key: "" },
    { name: "图片", key: MaterialTypeEnum.IMAGE },
    { name: "视频", key: MaterialTypeEnum.VIDEO },
];
const currMaterialType = ref<MaterialTypeEnum>(MaterialTypeEnum.ALL);

const dataLists = ref<any[]>([]);
const pagingRef = shallowRef();

const groupDataLists = ref<any[]>([]);
const pagingGroupRef = shallowRef();

const showEditPopup = ref(false);
const editIndex = ref<number>(-1);
const newName = ref<string>("");

const showGroupPopup = ref(false);
const chooseGroupId = ref<number>();

const showAddGroupPopup = ref(false);

const isHandle = ref(false);
const handleList = ref<number[]>([]);
const showVideoPreview = ref(false);
const playItem = reactive<any>({
    url: "",
    pic: "",
});

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getMaterialLibraryList({ page_no, page_size, m_type: currMaterialType.value });
        pagingRef.value?.complete(lists.filter((item: any) => item.m_type != MaterialTypeEnum.MUSIC));
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const queryGroupList = async (page_no: number, page_size: number) => {
    try {
        // const { lists } = await getMaterialLibraryList({ page_no, page_size, m_type: currMaterialType.value });
        pagingGroupRef.value?.complete([]);
    } catch (error) {
        pagingGroupRef.value?.complete([]);
    }
};

const handleShowType = (type: ShowType) => {
    if (currShowType.value == type) return;
    currShowType.value = type;
};

const handleMaterialType = (type: any) => {
    if (currMaterialType.value == type) return;
    currMaterialType.value = type;
    pagingRef.value?.reload();
};

const handleItem = (item: any) => {
    uni.showActionSheet({
        itemList: ["预览", "下载", "删除"],
        success: (res) => {
            if (res.tapIndex == 0) {
                if (item.m_type == MaterialTypeEnum.VIDEO) {
                    handlePlay(item);
                } else {
                    uni.previewImage({
                        urls: [item.content],
                    });
                }
            } else if (res.tapIndex == 1) {
                if (item.m_type == MaterialTypeEnum.VIDEO) {
                    saveVideoToPhotosAlbum(item.content);
                } else {
                    saveImageToPhotosAlbum(item.content);
                }
            } else {
                handleDelete(item.id);
            }
        },
    });
};

const isSelect = (id: number) => {
    return handleList.value.includes(id);
};

const handleSelect = (id: number) => {
    if (isSelect(id)) {
        handleList.value = handleList.value.filter((item) => item != id);
    } else {
        handleList.value.push(id);
    }
};

const handleSelectAll = () => {
    if (handleList.value.length == dataLists.value.length) {
        handleList.value = [];
    } else {
        handleList.value = dataLists.value.map((item) => item.id);
    }
};

const handleDelete = (id: number | number[]) => {
    uni.showModal({
        title: "提示",
        content: "确定要删除吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中...",
                });
                try {
                    await deleteMaterialLibrary({
                        id: Array.isArray(id) ? id : [id],
                    });
                    pagingRef.value?.reload();
                    handleList.value = [];
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                    });
                } catch (error) {
                    uni.hideLoading();
                }
            }
        },
    });
};

const handleManageGroup = (index: number) => {
    uni.showActionSheet({
        itemList: ["修改名称", "删除素材组"],
        success: (res) => {
            if (res.tapIndex == 0) {
                editIndex.value = index;
                showEditPopup.value = true;
            } else {
                uni.showModal({
                    title: "提示",
                    content: "删除后无法找回，是否确认删除？",
                    success: async (res) => {
                        if (res.confirm) {
                            uni.showLoading({
                                title: "删除中...",
                            });
                            try {
                                await deleteMaterialLibrary({
                                    id: dataLists.value[index].id,
                                });

                                uni.hideLoading();
                                uni.showToast({
                                    title: "删除成功",
                                    icon: "none",
                                });
                                pagingRef.value?.reload();
                            } catch (error: any) {
                                uni.hideLoading();
                                uni.showToast({
                                    title: error,
                                    icon: "none",
                                    duration: 3000,
                                });
                            }
                        }
                    },
                });
            }
        },
    });
};

const confirmUpdateGroupName = async () => {
    uni.showLoading({
        title: "修改中...",
        mask: true,
    });
    try {
        await updateMaterialLibraryGroup({
            id: dataLists.value[editIndex.value].id,
            name: newName.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "修改成功",
            icon: "none",
        });
        showEditPopup.value = false;
        pagingRef.value?.reload();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const handleAddGroup = () => {
    showAddGroupPopup.value = true;
};

const confirmAddGroupName = async () => {
    if (!newName.value) {
        uni.$u.toast("请输入分组名称");
        return;
    }
    uni.showLoading({
        title: "添加中...",
        mask: true,
    });
    try {
        await addMaterialLibraryGroup({
            name: newName.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "添加成功",
            icon: "none",
        });
        showAddGroupPopup.value = false;
        pagingGroupRef.value?.reload();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const handleGroup = (id: number) => {
    if (chooseGroupId.value == id) {
        chooseGroupId.value = -1;
    } else {
        chooseGroupId.value = id;
    }
};

const closeAddGroupPopup = () => {
    showAddGroupPopup.value = false;
    chooseGroupId.value = -1;
};

const confirmGroup = async () => {
    if (chooseGroupId.value == -1) {
        uni.$u.toast("至少选择一个分组");
        return;
    }
};

const handlePlay = (item: any) => {
    playItem.pic = item.pic;
    playItem.url = item.content;
    showVideoPreview.value = true;
};

const { uploadAndProcessFiles, showUploadProgress, uploadMaterialList } = useUpload({
    fileAccept: ["mp4", "m4a", "jpg", "png", "jpeg", "webp"],
    onSuccess: async (materials) => {
        const promises = [];
        for (const item of materials) {
            promises.push(
                addMaterialLibrary({
                    name: item.name.split(".")[0],
                    content: item.url,
                    size: item.size,
                    pic: item.pic,
                    duration: item.duration,
                    sort: 0,
                    type: AppTypeEnum.XHS,
                    m_type: item.type == "image" ? MaterialTypeEnum.IMAGE : MaterialTypeEnum.VIDEO,
                })
            );
        }
        uni.showLoading({
            title: "添加中...",
            mask: true,
        });
        try {
            await Promise.all(promises);
            pagingRef.value?.reload();
            uni.hideLoading();
            uni.showToast({
                title: "添加成功",
                icon: "none",
                duration: 3000,
            });
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({
                title: error,
                icon: "none",
                duration: 3000,
            });
        }
    },
});

const handleUploadMaterial = () => {
    uni.showActionSheet({
        itemList: ["上传图片", "上传视频"],
        success: (res) => {
            if (res.tapIndex == 0) {
                uploadAndProcessFiles("image");
            } else {
                uploadAndProcessFiles("video");
            }
        },
    });
};
</script>

<style scoped lang="scss">
.show-type-item {
    @apply w-[156rpx] flex items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500;
    &.active {
        @apply text-black font-bold relative;
    }
}

.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-[#F9FAFB] absolute top-[4rpx] left-0 transition-all duration-500;
    &::after {
        content: "";
        @apply absolute bottom-0 w-[20%] h-[4rpx] bg-black;
        // 让线居中
        left: 0;
        right: 0;
        margin: auto;
    }
}

.material-type-item {
    @apply px-[34rpx] py-[11rpx] rounded-[10rpx] shadow-[0_0_0_1rpx_rgba(0,0,0,0.1)];
    &.active {
        @apply bg-black text-white;
    }
}
</style>
