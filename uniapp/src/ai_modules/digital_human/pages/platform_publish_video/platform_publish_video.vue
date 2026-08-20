<template>
    <view class="min-h-screen bg-[#f8fafc]" v-if="!loading">
        <view class="bg-white px-6 py-6 shadow-sm">
            <view class="flex items-center justify-between mb-4">
                <view class="flex items-center gap-3">
                    <text class="text-xl font-medium text-[#1a1a1a] line-clamp-1">{{ detailData.name }}</text>
                    <view class="p-2 bg-[#f9f9f9] rounded-xl" @click="handleEdit">
                        <image src="/static/images/icons/edit_pen.svg" class="w-4 h-4" />
                    </view>
                </view>
            </view>

            <view class="bg-[#f9f9f9]/50 rounded-2xl p-4 border border-[#ececec]">
                <view class="flex items-center gap-3">
                    <view class="relative">
                        <image src="/static/images/common/douyin_s.png" class="w-10 h-10 rounded-full shadow-sm" />
                        <view
                            class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-[#22c55e] rounded-full border-2 border-white"></view>
                    </view>
                    <view class="flex-1">
                        <text class="text-[#b4b4b4] text-xs block">投放平台：DOU音</text>
                        <view class="flex items-baseline gap-1">
                            <text class="text-primary font-medium text-xl">{{ videoList.length || 6 }}</text>
                            <text class="text-[#9b9b9b] text-xs font-medium">个视频已就绪</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view class="px-4 py-4 pb-[350rpx]">
            <view class="flex items-center justify-between mb-4">
                <text class="text-lg font-semibold text-[#333]">视频素材</text>
                <view class="flex items-center gap-2">
                    <view class="w-2 h-2 bg-[#22c55e] rounded-full"></view>
                    <text class="text-sm text-[#9b9b9b]">全部就绪</text>
                </view>
            </view>

            <view class="grid grid-cols-2 gap-4">
                <view
                    v-for="(item, index) in videoList"
                    :key="index"
                    class="bg-white rounded-[24rpx] shadow-sm border border-[#000000]/5 overflow-hidden active:scale-[0.98] transition-all duration-200"
                    @click="handleAction(item, index)">
                    <view class="aspect-[3/4] relative">
                        <view class="w-full h-full leading-[0]">
                            <image :src="item.pic" class="w-full h-full" mode="aspectFill" lazy-load />
                        </view>

                        <view class="absolute inset-0 flex items-center justify-center">
                            <view class="w-8 h-8" @click.stop="previewMedia(item, index)">
                                <image src="/static/images/icons/play.svg" class="w-full h-full" />
                            </view>
                        </view>

                        <view class="absolute bottom-2 left-3 right-3">
                            <view
                                class="w-full bg-[#0065fb] text-center py-2 text-white rounded-2xl font-medium text-xs shadow-[0_8px_16px_-4px_rgba(0,101,251,0.3)] active:scale-[0.98] transition-all"
                                @click.stop="handlePublish(item)">
                                扫码发布到DOU音
                            </view>
                        </view>
                    </view>

                    <view class="p-3">
                        <text class="text-[#333] font-medium text-sm">{{ item.create_time }}</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
    <name-edit ref="nameEditRef" v-model="showNameEdit" title="项目名称" :maxlength="100" @confirm="confirmNameEdit" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
    <popup-bottom v-model="showCodePop" title="DOU音扫码发布视频">
        <template #content>
            <scroll-view class="h-full" scroll-y>
                <view class="px-6">
                    <view class="py-4 flex flex-col items-center">
                        <view class="relative mb-6">
                            <view
                                class="w-64 h-64 bg-white rounded-2xl shadow-lg border-4 border-[#f1f5f9] flex items-center justify-center overflow-hidden">
                                <image :src="publishData.app_url" class="w-full h-full" mode="aspectFit" />
                            </view>
                        </view>

                        <view class="flex items-center gap-2 mb-4">
                            <view class="w-2 h-2 bg-[#22c55e] rounded-full animate-pulse"></view>
                            <text class="text-sm text-[#22c55e] font-medium">二维码已生成，等待扫描</text>
                        </view>

                        <view class="bg-[#f8fafc] rounded-2xl p-4 w-full mb-6">
                            <text class="text-sm text-[#64748b] font-medium block mb-2">📱 扫码步骤：</text>
                            <view class="space-y-1">
                                <text class="text-xs text-[#64748b] block">1. 打开DOU音APP</text>
                                <text class="text-xs text-[#64748b] block">2. 点击右上角扫一扫</text>
                                <text class="text-xs text-[#64748b] block">3. 扫描上方二维码</text>
                                <text class="text-xs text-[#64748b] block">4. 确认发布视频内容</text>
                            </view>
                        </view>
                    </view>

                    <view class="pb-4">
                        <button
                            class="flex items-center justify-center gap-2 py-2 rounded-2xl bg-[#0065fb] active:scale-[0.98] transition-all"
                            @click="downloadQRCode">
                            <text class="text-white font-medium">保存到相册</text>
                        </button>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import {
    getManualPublishTaskList,
    getManualPublishTaskDetail,
    editManualPublish,
    deleteManualPublishTask,
    publishManualPublishTask,
} from "@/api/device";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import NameEdit from "@/ai_modules/digital_human/components/keywords-edit/keywords-edit.vue";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const detailId = ref<string>("");
const detailData = ref<any>({});

const videoList = ref<any[]>([]);
const loading = ref(true);

const showNameEdit = ref(false);
const nameEditRef = shallowRef<InstanceType<typeof NameEdit>>();

const showVideoPreview = ref(false);
const playData = ref<any>({});
const publishData = ref<any>({});

const showCodePop = ref(false);

const rechargePopupRef = shallowRef();

const getToken = computed(() => {
    return userStore.getTokenByScene(TokensSceneEnum.PUBLISH_DOUYIN)?.score || 0;
});

const getVideoList = async () => {
    const { lists } = await getManualPublishTaskList({
        page_no: 1,
        page_size: 100,
        manual_setting_id: detailId.value,
    });
    videoList.value = lists || [];
};

const handlePublish = async (item: any) => {
    if (userTokens.value < getToken.value) {
        rechargePopupRef.value?.open();
        return;
    }
    showCodePop.value = true;
    publishData.value = item;
    const res = await publishManualPublishTask({ id: item.id });
    publishData.value.app_url = res.app_url;
};

const previewMedia = (item: any, index: number) => {
    const { media_type } = item;
    if (media_type == 1) {
        showVideoPreview.value = true;
        playData.value = {
            url: item.media_url,
            pic: item.pic,
        };
    } else {
        uni.previewImage({
            urls: [item.pic],
        });
    }
};

const handleAction = (item: any, index: number) => {
    uni.showActionSheet({
        itemList: ["下载", "删除"],
        success: (res) => {
            switch (res.tapIndex) {
                case 0:
                    const { media_type } = item;
                    if (media_type == 1) {
                        saveVideoToPhotosAlbum(item.media_url);
                    } else {
                        saveImageToPhotosAlbum(item.media_url);
                    }
                    break;
                case 1:
                    uni.showModal({
                        title: "确认删除",
                        content: "确定要删除这个视频吗？",
                        success: async (modalRes) => {
                            if (modalRes.confirm) {
                                try {
                                    await deleteManualPublishTask({ id: item.id });
                                    uni.showToast({
                                        title: "删除成功",
                                        icon: "none",
                                        duration: 3000,
                                    });
                                    videoList.value = videoList.value.filter((v) => v.id !== item.id);
                                } catch (error: any) {
                                    uni.hideLoading();
                                    uni.showToast({ title: error, icon: "none", duration: 3000 });
                                }
                            }
                        },
                    });
                    break;
            }
        },
    });
};

const handleEdit = async () => {
    showNameEdit.value = true;
    await nextTick();
    nameEditRef.value?.setFormData(detailData.value.name);
};

const confirmNameEdit = async (name: string) => {
    uni.showLoading({
        title: "编辑中...",
        mask: true,
    });
    try {
        await editManualPublish({ id: detailId.value, name });
        detailData.value.name = name;
        uni.showToast({ title: "编辑成功", icon: "none", duration: 3000 });
        showNameEdit.value = false;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const downloadQRCode = () => {
    saveImageToPhotosAlbum(publishData.value.app_url);
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中...",
    });
    try {
        const data = await getManualPublishTaskDetail({ id: detailId.value });
        detailData.value = data;
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

onLoad((options: any) => {
    if (options.id) {
        detailId.value = options.id;
        getDetail();
        getVideoList();
    }
});
</script>
