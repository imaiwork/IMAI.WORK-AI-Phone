<template>
    <view class="h-screen flex flex-col sora-page">
        <u-navbar
            title-bold
            title="一句话生成视频"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>

        <view class="grow min-h-0 mt-[20rpx]">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pb-[100rpx]">
                    <view class="p-[30rpx] bg-white rounded-[20rpx]">
                        <view>
                            <view class="font-medium text-[30rpx]">提示词</view>
                            <u-input
                                ref="inputContentRef"
                                class="w-full mt-[18rpx]"
                                v-model="formData.content"
                                type="textarea"
                                height="300"
                                placeholder-style="font-size: 26rpx;line-height: 40rpx;"
                                confirm-type="done"
                                :disable-default-padding="true"
                                :show-confirm-bar="false"
                                :adjust-position="false"
                                :auto-height="false"
                                :maxlength="maxDescLength"
                                placeholder="描个25岁的青年女性，穿着正装，手上拿着商品正在讲解介绍...若选择角色，需在文案中使用角色名 ，如：小爱穿着正装，手上拿着商品正在讲解介绍..." />
                        </view>
                        <view class="flex items-center justify-between">
                            <view
                                class="px-[18rpx] py-[12rpx] bg-black rounded-[10rpx] flex items-center gap-x-[10rpx]"
                                @click="showChooseAgent = true">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/star_white.svg"
                                    class="w-[24rpx] h-[24rpx]"></image>
                                <text class="text-white font-medium">优化提示词</text>
                            </view>
                            <view class="flex items-center gap-x-[46rpx]">
                                <view class="flex items-center gap-x-[10rpx]" @click="formData.content = ''">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/clear.svg"
                                        class="w-[24rpx] h-[24rpx]"></image>
                                    <text class="text-[#999999] text-xs">清除</text>
                                </view>
                                <view class="text-[#B2B2B2] text-xs">
                                    {{ formData.content.length }}/{{ maxDescLength }}
                                </view>
                            </view>
                        </view>
                        <view class="mt-[30rpx]">
                            <view class="font-medium">选择角色(选填)</view>
                            <view class="grid grid-cols-4 mt-[22rpx] gap-x-[16rpx]">
                                <view
                                    class="bg-[#F3F4FB] rounded-[12rpx] flex flex-col items-center justify-center h-[200rpx]"
                                    @click="showChooseRole = true">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/add.svg"
                                        class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-xs font-medium mt-2">选择</text>
                                </view>
                                <view
                                    v-for="(item, index) in soraRoleList"
                                    :key="index"
                                    class="h-[200rpx] rounded-[12rpx] relative">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[12rpx]"
                                        mode="aspectFill"></image>
                                    <view
                                        class="absolute top-0 left-0 w-full h-full flex items-center justify-center"
                                        @click="previewVideo(item)">
                                        <image src="/static/images/icons/play.svg" class="w-[40rpx] h-[40rpx]"></image>
                                    </view>
                                    <view
                                        class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                        @click="handleDeleteRole(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[20rpx] p-[30rpx] bg-white rounded-[20rpx]">
                        <view>
                            <view class="font-medium text-[30rpx]"
                                >参考图<text class="text-[#0000004d] text-xs">（选填，图片不可出现人物）</text></view
                            >
                            <view class="grid grid-cols-3 gap-[20rpx] mt-[20rpx]">
                                <view v-for="(item, index) in formData.materialList" :key="index" class="relative">
                                    <view
                                        class="h-[200rpx] rounded-[12rpx] relative overflow-hidden"
                                        @click="previewMaterial(item)">
                                        <image
                                            :src="item.pic"
                                            class="w-full h-full rounded-[12rpx]"
                                            mode="aspectFill"></image>
                                    </view>
                                    <view
                                        class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                        @click="handleDeleteMaterial(item.id)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                    <view class="absolute bottom-2 w-full z-[33] flex justify-center">
                                        <view class="dh-version-name" @click.stop="handleReplaceMaterial(index)">
                                            替换
                                        </view>
                                    </view>
                                </view>
                                <view
                                    v-if="formData.materialList.length < 1"
                                    class="bg-[#F3F4FB] rounded-[12rpx] flex flex-col items-center justify-center h-[200rpx]"
                                    @click="uploadAndProcessFiles('image')">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/add.svg"
                                        class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-xs font-medium mt-2">上传</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[20rpx] p-[30rpx] bg-white rounded-[20rpx]">
                        <view>
                            <view class="font-medium text-[30rpx]">视频比例</view>
                            <view class="mt-[20rpx] flex flex-wrap gap-2">
                                <view
                                    class="common-type-item"
                                    v-for="(item, index) in videoProportions"
                                    :key="index"
                                    :class="{ active: formData.aspect_ratio == item.value }"
                                    @click="formData.aspect_ratio = item.value">
                                    {{ item.label }}
                                </view>
                            </view>
                        </view>
                        <view class="mt-[50rpx]">
                            <view class="text-[30rpx] font-medium">视频时长</view>
                            <view class="mt-[20rpx] flex flex-wrap gap-2">
                                <view
                                    class="common-type-item"
                                    v-for="(item, index) in videoDurations"
                                    :key="index"
                                    :class="{ active: currVideoDurationIndex == index }"
                                    @click="currVideoDurationIndex = index">
                                    {{ item.label }}
                                </view>
                            </view>
                            <view class="text-[#999999] text-[22rpx] mt-3"
                                >提示：sora生成视频为官方模型，效果更佳稳定</view
                            >
                        </view>
                        <view class="flex items-center justify-between gap-x-2 mt-[50rpx]">
                            <view class="text-[30rpx] font-medium">生成视频数量</view>
                            <view class="flex items-center gap-x-2">
                                <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('minus')">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/minus_circle.svg"
                                        class="w-[36rpx] h-[36rpx]"></image>
                                </view>
                                <view
                                    class="w-[90rpx] h-[52rpx] px-1 flex items-center justify-center bg-[#F6F6F6] rounded-[10rpx]">
                                    <u-input
                                        v-model="formData.video_count"
                                        type="digit"
                                        placeholder=""
                                        :min="1"
                                        :max="99"
                                        :custom-style="{ fontWeight: 'bold' }"
                                        input-align="center" />
                                </view>
                                <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('add')">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/add_circle.svg"
                                        class="w-[36rpx] h-[36rpx]"></image>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[20rpx] p-[30rpx] bg-white rounded-[20rpx]">
                        <view class="text-[30rpx] font-medium">视频名称</view>
                        <view class="mt-[20rpx] bg-[#F3F4FB] rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                            <u-input
                                class="w-full"
                                v-model="formData.name"
                                maxlength="50"
                                placeholder-style="font-size:26rpx;"
                                placeholder="请输入"
                                clearable />
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <view class="flex flex-col items-center gap-y-2" @click="toRoleLibrary">
                    <image
                        src="@/ai_modules/digital_human/static/images/common/role.png"
                        class="w-[36rpx] h-[36rpx]"></image>
                    <text class="text-[#8C8C8C] text-[22rpx]">角色库</text>
                </view>
                <view class="flex flex-col items-center gap-y-2" @click="showTokensCost = true">
                    <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]"></image>
                    <text class="text-[#8C8C8C] text-[22rpx]">算力消耗</text>
                </view>
                <view
                    class="rounded-[16rpx] w-[456rpx] h-[100rpx] bg-black text-white font-medium flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                    @click="handleCreateVideo">
                    立即生成（{{ formData.video_count }}个）
                </view>
            </view>
        </view>
    </view>

    <choose-role v-model="showChooseRole" @confirm="handleConfirmRole" />
    <choose-agent
        v-if="showChooseAgent"
        v-model="showChooseAgent"
        is-sora
        :system-agent-ids="[7]"
        @select="handleSelectAgent" />
    <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="视频生成中"
        desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
        @to="toPublish"
        @seek="toRecord">
        <template #custom-btn>
            <view
                class="btn-secondary w-full text-[30rpx] font-medium rounded-[20rpx] h-[96rpx] flex items-center justify-center relative overflow-hidden border border-solid text-[#475569] border-[#e2e8f0]"
                style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)"
                @click="handleRelaunch">
                <view class="btn-ripple"></view>
                <text class="relative z-10">再创作一个</text>
            </view>
        </template>
    </create-success-pop>
    <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="5" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :pic="playData.pic" />
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { createSoraVideo } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import useUpload from "@/hooks/useUpload";
import { MontageTypeEnum, ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import ChooseRole from "@/ai_modules/digital_human/components/choose-role/choose-role.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";

const { on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const formData = reactive<{
    name: string;
    content: string;
    materialList: any[];
    videoStyle: number;
    videoResolution: number;
    aspect_ratio: string;
    videoDuration: number;
    videoSwitchFrequency: number;
    video_count: number;
    ai_type: 0 | 1;
}>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "一句话生成视频",
    content: "",
    materialList: [],
    videoStyle: 1,
    videoResolution: 1,
    aspect_ratio: "9:16",
    videoDuration: 10,
    videoSwitchFrequency: 1,
    video_count: 1,
    ai_type: 1,
});

// 视频比例
const videoProportions = [
    { label: "横屏16:9", value: "16:9" },
    { label: "竖屏9:16", value: "9:16" },
];

// 视频时长
const videoDurations = [
    { label: "4s(普通)", value: 4, model: "sora-2" },
    { label: "8s(普通)", value: 8, model: "sora-2" },
    { label: "12s(普通)", value: 12, model: "sora-2" },
    { label: "15s(PRO)", value: 15, model: "sora-2-pro" },
    { label: "25s(PRO)", value: 25, model: "sora-2-pro" },
];

const currVideoDurationIndex = ref<number>(0);

const maxDescLength = 5000;
const replaceMaterialIndex = ref(-1);
const showChooseAgent = ref(false);

const showChooseRole = ref(false);
const soraRoleList = ref<any[]>([]);
const showVideoPreview = ref(false);
const playData = reactive<any>({
    url: "",
    pic: "",
});

const showCreateSuccess = ref(false);
const showTokensCost = ref(false);
const createResult = ref<any>(null);
const rechargePopupRef = shallowRef();

const handleSelectAgent = (res: any) => {
    const { data } = res;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/sora_ai_copywriter/sora_ai_copywriter",
        params: {
            agentData: JSON.stringify(data),
            content: formData.content,
        },
    });
};

const handleConfirmRole = (role: any) => {
    soraRoleList.value.push(role);
    formData.content += role.name;
};

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    count: 1,
    sourceType: ["album", "camera"],
    onSuccess: (materials: any[]) => {
        if (replaceMaterialIndex.value !== -1) {
            formData.materialList[replaceMaterialIndex.value] = materials[0];
        } else {
            formData.materialList = formData.materialList.concat(materials);
        }
        replaceMaterialIndex.value = -1;
    },
});

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
    uploadAndProcessFiles("image");
};

const handleDeleteRole = (index: number) => {
    soraRoleList.value.splice(index, 1);
};

const previewVideo = (item: any) => {
    const { anchor_url, pic } = item;
    playData.url = anchor_url;
    playData.pic = pic;
    showVideoPreview.value = true;
};

const previewMaterial = (item: any) => {
    const { pic } = item;
    uni.previewImage({
        urls: [pic],
    });
};

const handleDeleteMaterial = (id: number) => {
    formData.materialList = formData.materialList.filter((item: any) => item.id !== id);
};

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.video_count <= 1) {
            uni.$u.toast("视频数量最少为1");
            return;
        }
        formData.video_count--;
    } else {
        if (formData.video_count >= 99) {
            uni.$u.toast("视频数量最多为99");
            return;
        }
        formData.video_count++;
    }
};

const handleCreateVideo = async () => {
    // 判断是否有算力
    if (userTokens.value <= 0) {
        rechargePopupRef.value?.open();
        return;
    }
    if (!formData.name) {
        uni.$u.toast("请输入视频名称");
        return;
    }
    if (!formData.content) {
        uni.$u.toast("请输入提示词");
        return;
    }
    if (formData.video_count <= 0) {
        uni.$u.toast("请输入视频数量");
        return;
    }
    if (formData.video_count > 99) {
        uni.$u.toast("视频数量最多为99");
        return;
    }
    uni.showLoading({
        title: "提交中...",
        mask: true,
    });
    try {
        const res = await createSoraVideo({
            name: formData.name,
            theme: "",
            content: formData.content,
            gender: "",
            image_urls: formData.materialList.map((item: any) => item.pic),
            style: "",
            frequency: "",
            aspect_ratio: formData.aspect_ratio,
            duration: videoDurations[currVideoDurationIndex.value].value,
            number: formData.video_count,
            model: videoDurations[currVideoDurationIndex.value].model,
            anchor_ids: soraRoleList.value.map((item: any) => item.anchor_id),
        });
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
        WechatOA.notify();
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
    });
};

// 去发布
const toPublish = () => {
    showCreateSuccess.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
        type: "reLaunch",
        params: {
            task_id: JSON.stringify([createResult.value.id]),
            scene: 1,
            type: MontageTypeEnum.SORA_VIDEO,
        },
    });
};

const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
        params: {
            source: "1",
            type: 6,
        },
    });
};

const handleRelaunch = () => {
    showCreateSuccess.value = false;
    formData.content = "";
    formData.materialList = [];
    soraRoleList.value = [];
};

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.SORA_AI_COPYWRITER) {
            formData.content = data;
            console.log(formData.content, "formData.content");
        }
    });
});
</script>

<style scoped lang="scss">
.sora-page {
    position: relative;
    background: #eff2f4;
    &::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 400rpx;
        background: linear-gradient(90deg, rgba(171, 226, 240, 1) 0%, rgba(202, 220, 250, 1) 100%);
        mask-image: linear-gradient(180deg, black 0rpx, transparent 400rpx);
        z-index: 0;
    }
}

.common-type-item {
    @apply px-[28rpx] h-[72rpx] flex items-center justify-center rounded-[10rpx] bg-[#F3F4FB];
    &.active {
        @apply bg-black text-white font-medium;
    }
}
</style>
