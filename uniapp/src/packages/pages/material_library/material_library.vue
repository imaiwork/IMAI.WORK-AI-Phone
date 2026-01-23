<template>
    <view class="h-screen flex flex-col">
        <view
            class="flex items-center justify-between py-3 px-4 bg-white sticky top-0 z-[100] border-b border-[#F1F5F9]">
            <view
                class="flex items-center bg-[#F1F2F5] rounded-full h-[84rpx] relative"
                v-if="currShowType == ShowType.ALL">
                <view
                    v-for="(item, index) in materialTypes"
                    :key="index"
                    class="relative px-[32rpx] py-[12rpx] rounded-full text-[26rpx] transition-all duration-300 z-10"
                    :class="{ 'text-white': materialTypeIndex === index }"
                    @click="handleMaterialType(item.key, index)">
                    {{ item.name }}
                </view>
                <view class="type-tab-slider" :style="{ transform: `translateX(${materialTypeIndex * 100}%)` }"></view>
            </view>

            <view v-if="currShowType == ShowType.GROUP" class="flex items-center gap-2">
                <view class="w-1.5 h-4 bg-black rounded-full"></view>
                <text class="text-[32rpx] font-black text-[#1E293B]">素材分组</text>
            </view>

            <view>
                <view
                    v-if="currShowType == ShowType.ALL"
                    class="flex items-center gap-1 bg-primary text-white px-[32rpx] py-[16rpx] rounded-full shadow-lg shadow-[#0065fb]/20 active:scale-95 transition-all"
                    @click="handleUploadMaterial">
                    <u-icon name="plus" color="#fff" size="24"></u-icon>
                    <text class="text-[26rpx] font-bold">上传素材</text>
                </view>

                <view
                    v-if="currShowType == ShowType.GROUP"
                    class="flex items-center gap-1 bg-[#1E293B] text-white px-[32rpx] py-[16rpx] rounded-full shadow-lg shadow-gray-200 active:scale-95 transition-all"
                    @click="handleAddGroup">
                    <u-icon name="folder-add" color="#fff" size="24"></u-icon>
                    <text class="text-[26rpx] font-bold">创建素材组</text>
                </view>
            </view>
        </view>

        <view class="grow min-h-0 pb-[150rpx]">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4">
                    <view class="grid grid-cols-2 gap-2" v-if="currShowType == ShowType.ALL">
                        <view
                            v-for="(item, index) in dataLists"
                            :key="item.id"
                            class="bg-white rounded-[24rpx] overflow-hidden border border-[#F1F5F9] shadow-sm relative"
                            :class="{ 'ring-2 ring-primary': isSelect(item.id) }"
                            @click="isHandle ? handleSelect(item.id) : null">
                            <view
                                class="aspect-square relative bg-[#F8FAFC] overflow-hidden"
                                @click="!isHandle && handleItem(item)">
                                <image
                                    v-if="item.m_type != MaterialTypeEnum.MUSIC"
                                    :src="item.pic || item.content"
                                    class="w-full h-full"
                                    mode="aspectFill"
                                    lazy-load></image>
                                <image
                                    src="@/packages/static/images/common/audio_bg.png"
                                    class="w-full h-full"
                                    mode="aspectFill"
                                    v-else></image>

                                <view
                                    v-if="
                                        !isHandle &&
                                        (item.m_type == MaterialTypeEnum.VIDEO || item.m_type == MaterialTypeEnum.MUSIC)
                                    "
                                    class="absolute inset-0 flex items-center justify-center bg-black/10">
                                    <view
                                        class="w-10 h-10 bg-white/90 rounded-full flex items-center justify-center shadow-lg">
                                        <u-icon name="play-fill" color="#0065fb" size="20" class="ml-1"></u-icon>
                                    </view>
                                </view>

                                <view v-if="isHandle" class="absolute top-2 right-2 z-20">
                                    <view
                                        class="w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center border-2 border-white shadow-sm"
                                        :style="{
                                            background: isSelect(item.id) ? '#0065fb' : 'rgba(255,255,255,0.8)',
                                        }">
                                        <u-icon
                                            v-if="isSelect(item.id)"
                                            name="checkbox-mark"
                                            color="#fff"
                                            size="18"></u-icon>
                                    </view>
                                </view>
                            </view>

                            <view class="p-3">
                                <view class="flex items-center justify-between mb-2">
                                    <text class="text-[26rpx] font-bold text-[#1E293B] truncate flex-1 mr-2">
                                        {{ item.name || "未命名素材" }}
                                    </text>
                                    <view
                                        class="text-[18rpx] px-1.5 py-0.5 rounded bg-[#F1F5F9] text-[#64748B] font-bold uppercase">
                                        {{ getTypeName(item.m_type) }}
                                    </view>
                                </view>

                                <view class="flex items-center justify-between">
                                    <text class="text-[20rpx] text-[#94A3B8]">
                                        {{ item.create_time ? item.create_time.split(" ")[0] : "" }}
                                    </text>
                                    <view
                                        class="flex items-center gap-1 bg-[#F8FAFC] px-1.5 py-0.5 rounded border border-[#F1F5F9]">
                                        <u-icon name="file-text" size="18" color="#94A3B8"></u-icon>
                                        <text class="text-[20rpx] text-[#64748B] font-bold">
                                            {{ formatFileSize(item.size) }}
                                        </text>
                                    </view>
                                </view>
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
        <view
            class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-lg border-t border-[#F1F5F9] px-4 pb-4 pt-2 z-[100]">
            <view class="flex items-center h-[110rpx]" :class="[isHandle ? 'justify-between' : 'justify-end']">
                <view v-if="isHandle" class="flex items-center gap-x-6">
                    <view class="text-[28rpx] text-[#64748B] font-medium active:opacity-60" @click="exitHandleMode">
                        取消
                    </view>

                    <view class="flex items-center gap-x-2" @click="handleSelectAll()">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-full border-2 transition-all flex items-center justify-center"
                            :class="[isAllSelected ? 'bg-primary border-primary' : 'border-[#CBD5E0] bg-white']">
                            <u-icon v-if="isAllSelected" name="checkbox-mark" color="#fff" size="20"></u-icon>
                        </view>
                        <text
                            class="text-[28rpx] font-bold"
                            :class="[isAllSelected ? 'text-primary' : 'text-[#1E293B]']"
                            >全选</text
                        >
                    </view>
                </view>

                <view class="flex items-center gap-x-3">
                    <template v-if="isHandle">
                        <view
                            v-if="false"
                            class="h-[76rpx] px-5 bg-[#F1F5F9] text-[#1E293B] rounded-full flex items-center justify-center text-[26rpx] font-bold active:scale-95 transition-all"
                            @click="showGroupPopup = true">
                            <u-icon name="folder-open" size="28" class="mr-1"></u-icon>
                            移动至
                        </view>

                        <view
                            class="min-w-[180rpx] h-[76rpx] px-4 rounded-full flex items-center justify-center text-white text-[26rpx] font-bold transition-all active:scale-95"
                            :class="[
                                handleList.length > 0
                                    ? 'bg-[#FF2442] shadow-lg shadow-[#FF2442]/20'
                                    : 'bg-[#FF2442]/40',
                            ]"
                            @click="handleDelete(handleList)">
                            <u-icon name="trash" color="#fff" size="28" class="mr-1"></u-icon>
                            删除 <text v-if="handleList.length > 0" class="ml-1">({{ handleList.length }})</text>
                        </view>
                    </template>

                    <view
                        v-else
                        class="h-[76rpx] px-6 bg-[#1E293B] text-white flex items-center justify-center rounded-full text-[26rpx] font-bold shadow-lg shadow-gray-300 active:scale-95 transition-all"
                        @click="isHandle = true">
                        <u-icon name="setting" color="#fff" size="28" class="mr-1"></u-icon>
                        批量管理
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
    { name: "音频", key: MaterialTypeEnum.MUSIC },
];

const currMaterialType = ref<MaterialTypeEnum>(MaterialTypeEnum.ALL);
const materialTypeIndex = ref<number>(0);
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

// 计算是否全选
const isAllSelected = computed(() => {
    return handleList.value.length > 0 && handleList.value.length === dataLists.value.length;
});

// 补充退出管理逻辑
const exitHandleMode = () => {
    isHandle.value = false;
    handleList.value = [];
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getMaterialLibraryList({ page_no, page_size, m_type: currMaterialType.value });
        pagingRef.value?.complete(lists);
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
// 获取展示类型名称
const getTypeName = (type: number | string) => {
    switch (Number(type)) {
        case MaterialTypeEnum.VIDEO:
            return "VIDEO";
        case MaterialTypeEnum.IMAGE:
            return "IMAGE";
        case MaterialTypeEnum.MUSIC:
            return "AUDIO";
        default:
            return "FILE";
    }
};

// 格式化文件大小
const formatFileSize = (size: number) => {
    if (!size) return "0B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(size) / Math.log(k));
    return (size / Math.pow(k, i)).toFixed(1) + sizes[i];
};

const handleShowType = (type: ShowType) => {
    if (currShowType.value == type) return;
    currShowType.value = type;
};

const handleMaterialType = (type: any, index: number) => {
    if (currMaterialType.value == type) return;
    currMaterialType.value = type;
    materialTypeIndex.value = index;
    pagingRef.value?.reload();
};

const handleItem = (item: any) => {
    const itemLists = ["预览", "下载", "删除"];
    const isMusic = item.m_type == MaterialTypeEnum.MUSIC;
    if (isMusic) {
        itemLists.splice(0, 2);
    }
    uni.showActionSheet({
        itemList: itemLists,
        success: (res) => {
            if (res.tapIndex == 0) {
                if (isMusic) {
                    handleDelete(item.id);
                    return;
                }
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
    if (!id || (Array.isArray(id) && id.length == 0)) return;
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
    fileAccept: ["mp4", "mp3", "m4a", "jpg", "png", "jpeg", "webp"],
    isTranscode: true,
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

.type-tab-slider {
    @apply h-[calc(100%-10rpx)] w-[25%] rounded-[100rpx] bg-primary absolute shadow-[0_4rpx_12rpx_rgba(0,101,251,0.3)] top-[4rpx] left-0 transition-all duration-500;
}

.material-type-item {
    @apply px-[20rpx] py-[11rpx] rounded-[10rpx] shadow-[0_0_0_1rpx_rgba(0,0,0,0.1)];
    &.active {
        @apply bg-black text-white;
    }
}
</style>
