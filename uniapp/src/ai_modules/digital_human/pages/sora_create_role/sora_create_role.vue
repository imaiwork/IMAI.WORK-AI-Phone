<template>
    <view class="h-screen flex flex-col page-bg">
        <u-navbar
            title-bold
            title="创建角色"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>

        <view class="grow min-h-0 mt-[20rpx]">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pb-[100rpx]">
                    <view v-if="false">
                        <view class="text-[30rpx] font-bold"><text class="text-[#FF0000]">*</text>参考角色图片</view>
                        <view class="bg-white rounded-[20rpx] px-[8rpx] mt-[20rpx]">
                            <view class="grid grid-cols-2 gap-x-1 h-[100rpx] relative">
                                <view
                                    v-for="(item, index) in themeTypes"
                                    :key="index"
                                    class="theme-type-item"
                                    :class="{ active: index == themeTypeIndex }"
                                    @click="themeTypeIndex = index">
                                    {{ item.label }}
                                </view>
                                <view
                                    class="tab-slider"
                                    :style="{ transform: `translateX(${themeTypeIndex * 100}%)` }"></view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="text-[30rpx] font-bold"><text class="text-[#FF0000]">*</text>角色名称</view>
                        <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 flex items-center h-[100rpx]">
                            <u-input
                                class="w-full"
                                v-model="formData.name"
                                placeholder="请输入角色名称"
                                placeholder-style="color: rgba(0,0,0,0.3); font-size: 26rpx;"
                                clearable
                                maxlength="100" />
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="flex items-center justify-between">
                            <view class="text-[30rpx] font-bold"
                                ><text class="text-[#FF0000]">*</text>参考角色视频</view
                            >
                            <view
                                v-if="formData.anchor_url"
                                class="text-primary"
                                @click="uploadAndProcessFiles('video')"
                                >重新上传</view
                            >
                        </view>
                        <view class="mt-[20rpx] bg-white rounded-[16rpx]">
                            <view v-if="!formData.anchor_url" class="p-[30rpx]" @click="uploadAndProcessFiles('video')">
                                <view class="flex flex-col items-center justify-center py-4">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/image_add.png"
                                        class="w-[60rpx] h-[60rpx]"></image>
                                    <text class="text-[28rpx] font-bold mt-[12rpx]">上传视频</text>
                                </view>
                                <view class="mt-4 px-[60rpx] text-[22rpx] flex flex-col gap-y-2 text-primary">
                                    <view>· 视频不可出现真人或人物肖像</view>
                                    <view>· 视频必须是有声视频，声音不可包含歌曲(带歌词演唱的歌词等) </view>
                                    <view>· 支持 {{ accept.join("、") }}，最大{{ maxSize }}MB </view>
                                </view>
                            </view>
                            <view class="h-[400rpx] relative" v-else>
                                <video :src="formData.anchor_url" class="w-full h-full rounded-[16rpx]"></video>
                            </view>
                        </view>
                        <!-- <view class="bg-white rounded-[20rpx] p-[30rpx]" v-if="themeTypeIndex === 1">
                            <view>
                                <view class="font-bold text-[30rpx]">提示词</view>
                                <textarea
                                    class="w-full mt-[18rpx]"
                                    v-model="formData.name"
                                    type="textarea"
                                    height="300"
                                    placeholder-style="font-size: 26rpx;"
                                    confirm-type="done"
                                    :disable-default-padding="true"
                                    :show-confirm-bar="false"
                                    :adjust-position="false"
                                    :auto-height="false"
                                    :maxlength="maxDescLength"
                                    placeholder="描述您的角色创意，如：一个篮球运动员手拿饮料的广告片" />
                            </view>
                            <view class="flex items-center justify-end">
                                <view class="flex items-center gap-x-[46rpx]">
                                    <view class="flex items-center gap-x-[10rpx]" @click="formData.name = ''">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/clear.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <text class="text-[#999999] text-xs">清除</text>
                                    </view>
                                    <view class="text-[#B2B2B2] text-xs">
                                        {{ formData.name.length }}/{{ maxDescLength }}
                                    </view>
                                </view>
                            </view>
                        </view> -->
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="flex items-center justify-between">
                            <view class="text-[30rpx] font-bold"><text class="text-[#FF0000]">*</text>抽取范围</view>
                        </view>
                        <view class="mt-[20rpx] bg-white rounded-[20rpx] p-[30rpx]">
                            <view class="text-[22rpx] text-[#00000080] px-10 py-2" v-if="!formData.anchor_url">
                                <view>· 请先上传角色视频后选择抽取范围 </view>
                                <view class="mt-2">· 范围差值需在1-3秒之间</view>
                            </view>
                            <view v-else class="px-4">
                                <RangeSlider
                                    :start="formData.start"
                                    :end="formData.end"
                                    :max-diff="3"
                                    :total="videoDuration"
                                    @change="handleRangeChange" />
                                <!-- <view class="grid grid-cols-2 gap-x-3">
                                    <view
                                        class="bg-[#F3F4FB] rounded-[16rpx] px-[30rpx] py-[6rpx] flex items-center justify-between">
                                        <view class="text-[#00000080] font-bold flex-shrink-0">开始</view>
                                        <u-input
                                            v-model="startTime"
                                            type="digit"
                                            input-align="right"
                                            placeholder=""
                                            :custom-style="{ textAlign: 'right', fontWeight: 'bold' }"
                                            @input="handleStartTimeInput" />
                                    </view>
                                    <view
                                        class="bg-[#F3F4FB] rounded-[16rpx] px-[30rpx] py-[4rpx] flex items-center justify-between">
                                        <view class="text-[#00000080] font-bold flex-shrink-0">结束</view>
                                        <u-input
                                            v-model="endTime"
                                            type="digit"
                                            input-align="right"
                                            placeholder=""
                                            :custom-style="{ textAlign: 'right', fontWeight: 'bold' }"
                                            @input="handleEndTimeInput" />
                                    </view>
                                </view> -->
                            </view>
                        </view>
                        <!-- <view
                            class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center justify-between"
                            @click="showRoleStylePopup = true">
                            <view class="font-bold">{{
                                roleStyles.find((item) => item.value === formData.roleStyle)?.label
                            }}</view>
                            <view class="text-[#00000080] flex items-center gap-x-1">
                                选择 <u-icon name="arrow-right" size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view> -->
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5 flex justify-center px-4 pt-4">
            <view
                class="rounded-[16rpx] w-full h-[100rpx] bg-black text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                @click="handleCreateRole">
                提交创建 <text class="text-[#ffffff80] text-xs">(预计消耗{{ getToken }}算力)</text>
            </view>
        </view>
    </view>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <!-- <choose-material v-model="showChooseMaterial" type="image" :limit="1" @select="handleChooseMaterial" /> -->
    <create-success-pop
        v-model="showCreateSuccess"
        title="角色生成中"
        seek-text=""
        to-text="查看角色库"
        desc="角色正在生成，请耐心等待，可在角色库中查看"
        @to="toRoleLibrary" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>

    <!-- <popup-bottom v-model="showRoleStylePopup" title="选择角色风格" height="40%">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 flex flex-col items-center justify-center">
                    <picker-view
                        :value="formData.roleStyleIndex"
                        indicator-style="height: 100rpx;"
                        indicator-class="font-bold"
                        @change="changeRoleStyle">
                        <picker-view-column>
                            <view
                                v-for="(item, index) in roleStyles"
                                :key="index"
                                class="h-[100rpx] flex items-center justify-center"
                                >{{ item.label }}</view
                            >
                        </picker-view-column>
                    </picker-view>
                </view>
                <view class="flex justify-around p-4 border-[0] border-t-[1rpx] border-solid border-[rgba(0,0,0,0.03)]">
                    <view
                        class="w-[180rpx] h-[76rpx] flex items-center justify-center rounded-[10rpx] bg-[#F3F3F3] text-[30rpx] text-[#00000080] font-bold"
                        @click="cancelRoleStyle"
                        >取消</view
                    >
                    <view
                        class="w-[180rpx] h-[76rpx] flex items-center justify-center rounded-[10rpx] bg-primary text-[30rpx] text-white font-bold"
                        @click="confirmRoleStyle"
                        >确定</view
                    >
                </view>
            </view>
        </template>
    </popup-bottom> -->
</template>

<script setup lang="ts">
import { createSoraRole } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import useUpload from "@/hooks/useUpload";
import { TokensSceneEnum } from "@/enums/appEnums";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import RangeSlider from "@/ai_modules/digital_human/components/range-slider/range-slider.vue";

enum ThemeType {
    LOCAL = "local",
    AI = "ai",
}

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// 获取消耗的算力
const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.SORA_ROLE)?.score;
    return parseFloat(token);
});

const themeTypes = [
    {
        label: "本地上传图片",
        value: ThemeType.LOCAL,
    },
    { label: "AI生成图片", value: ThemeType.AI },
];
const themeTypeIndex = ref(0);

const formData = reactive({
    name: "",
    pic: "",
    anchor_url: "",
    start: 0,
    end: 2,
});

const startTime = ref(0);
const endTime = ref(2);
const videoDuration = ref(10);

const accept = ["mp4", "mov"];
const maxSize = 200;
const maxDescLength = 500;

const showChooseMaterial = ref(false);
const rechargePopupRef = shallowRef();

const roleStyles = [
    {
        label: "真人风格",
        value: 1,
    },
    {
        label: "宫崎骏风格",
        value: 2,
    },
    {
        label: "日韩漫画",
        value: 3,
    },
    {
        label: "新海诚风格",
        value: 4,
    },
    {
        label: "二次元国风",
        value: 5,
    },
    {
        label: "二次元仙侠插画",
        value: 6,
    },
    {
        label: "二次元奇幻插画",
        value: 7,
    },
    {
        label: "二次元国风",
        value: 8,
    },
    {
        label: "二次元恐怖插画",
        value: 9,
    },
    {
        label: "赛博朋克风格",
        value: 10,
    },
    {
        label: "二次元赛璐璐",
        value: 11,
    },
    {
        label: "二次元校园恋爱",
        value: 12,
    },
    {
        label: "二次元都市异能",
        value: 13,
    },
    {
        label: "萌系动漫插画",
        value: 14,
    },
    {
        label: "Q版",
        value: 15,
    },
];
const roleStyleIndex = ref([0]);
const showRoleStylePopup = ref(false);
const showCreateSuccess = ref(false);
const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    count: 1,
    videoAccept: accept,
    videoSize: maxSize,
    videoDuration: [2, 600],
    onSuccess: (materials: any[]) => {
        formData.anchor_url = materials[0].url;
        formData.pic = materials[0].pic;
        videoDuration.value = Math.floor(materials[0].duration);
    },
});

const handleRangeChange = (e: any) => {
    formData.start = e.start;
    formData.end = e.end;
};

// const handleChooseMaterial = (materials: any[]) => {
//     const { pic, size } = materials[0];
//     if (parseFloat(size) > maxSize * 1024 * 1024) {
//         uni.$u.toast(`视频大小不能超过${maxSize}MB`);
//         return;
//     }
//     uni.getImageInfo({
//         src: materials[0].pic,
//         success: (res) => {
//             const { type = "" } = res;
//             if (accept.includes(type)) {
//                 formData.roleVideo = pic;
//             } else {
//                 uni.$u.toast(`不支持的视频格式`);
//                 return;
//             }
//         },
//     });
// };

const handleUploadRoleImage = () => {
    uni.showActionSheet({
        itemList: ['从"微信聊天"中选择', '从"素材库"中选择', '从"手机相册"中选择'],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("file");
            else if (res.tapIndex === 1) showChooseMaterial.value = true;
            else if (res.tapIndex === 2) uploadAndProcessFiles("image");
        },
    });
};

const handleStartTimeInput = (e: any) => {
    console.log(e);
};

const handleEndTimeInput = (e: any) => {};

// const handleStartTimeChange = (e: any) => {
//     formData.startTime = e[0];
//     formData.endTime = e[1];
// };

const changeRoleStyle = (e: any) => {
    const { value } = e.detail;
    roleStyleIndex.value = value;
};

// const confirmRoleStyle = () => {
//     formData.roleStyle = roleStyles[roleStyleIndex.value[0]].value;
//     formData.roleStyleIndex = roleStyleIndex.value;
//     showRoleStylePopup.value = false;
// };

// const cancelRoleStyle = () => {
//     roleStyleIndex.value = formData.roleStyleIndex;
//     showRoleStylePopup.value = false;
// };

const handleCreateRole = async () => {
    if (userTokens.value <= getToken.value) {
        rechargePopupRef.value?.open();
        return;
    }

    if (!formData.name) {
        uni.$u.toast("请输入角色名称");
        return;
    }
    if (!formData.anchor_url) {
        uni.$u.toast("请上传角色视频");
        return;
    }

    if (endTime.value - startTime.value >= 3) {
        uni.$u.toast("范围差值需在1-3秒之间");
        return;
    }

    uni.showLoading({
        title: "提交中...",
        mask: true,
    });
    try {
        const res = await createSoraRole({
            name: formData.name,
            pic: formData.pic,
            anchor_url: formData.anchor_url,
            start: startTime.value,
            end: endTime.value,
        });
        uni.hideLoading();
        showCreateSuccess.value = true;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const toRoleLibrary = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/sora_role_library/sora_role_library",
        type: "reLaunch",
    });
};
</script>

<style scoped lang="scss">
picker-view {
    width: 100%;
    height: 100%;
}
.theme-type-item {
    @apply flex items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500;
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
</style>
