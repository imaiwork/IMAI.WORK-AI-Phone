<template>
    <view class="h-screen flex flex-col dh-bg">
        <u-navbar
            title="数字人口播混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-5 w-full">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="step-item-success-icon">
                        <image
                            src="@/ai_modules/digital_human/static/icons/success3.svg"
                            class="w-[18rpx] h-[18rpx]"></image>
                    </view>
                    <view class="step-item-icon" v-else>
                        <view class="step-item-icon-bg"></view>
                    </view>
                    <text class="step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="step-item-line"
                        :class="{ '!border-[#00B862]': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <view v-if="step === 1" class="flex flex-col h-full">
                <view class="flex items-center justify-between px-4">
                    <text class="font-bold">选择形象</text>
                    <view class="flex items-center gap-x-1" @click="handleCreateAnchor">
                        <image
                            src="@/ai_modules/digital_human/static/icons/add.svg"
                            class="w-[28rpx] h-[28rpx]"></image>
                        <text>新增形象</text>
                    </view>
                </view>
                <view class="grow min-h-0 mt-[38rpx]">
                    <scroll-view
                        scroll-y
                        class="h-full"
                        v-if="anchorLists.length > 0"
                        @scrolltolower="handleLoadAnchorMore">
                        <view class="grid grid-cols-3 gap-4 px-4 pb-4">
                            <view
                                v-for="(item, index) in anchorLists"
                                :key="index"
                                class="h-[276rpx] rounded-xl relative overflow-hidden"
                                @click="handleSelect(item)">
                                <image :src="item.pic" class="w-full h-full rounded-xl" mode="aspectFill"></image>
                                <view
                                    class="absolute top-0 left-0 w-full h-full bg-[#00000080]"
                                    v-if="formData.anchorLists.includes(item)">
                                    <view class="absolute top-2 right-2">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/success.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view class="h-full flex flex-col items-center justify-center" v-else>
                        <image
                            src="@/ai_modules/digital_human/static/images/common/avatar.png"
                            class="w-[120rpx] h-[136rpx] mx-auto"></image>
                        <view class="text-[26rpx] text-[#828282] mt-[32rpx] text-center">
                            您还没有数字人，快去定制一个吧~
                        </view>
                        <view
                            class="mt-[28rpx] mx-auto w-[202rpx] h-[68rpx] flex items-center justify-center rounded-[12rpx] text-white bg-black"
                            @click="handleCreateAnchor">
                            定制数字人
                        </view>
                    </view>
                </view>
            </view>
            <view
                v-if="step === 2"
                class="bg-white rounded-[16rpx] px-4 py-[28rpx] shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] mx-4">
                <text class="font-bold">身份信息</text>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人物名称</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.name"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人物名称"
                                maxlength="20"
                                @change="isHistoryPerson = false" />
                        </view>
                    </view>
                </view>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人物介绍</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.introduction"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人物介绍"
                                maxlength="50"
                                @change="isHistoryPerson = false" />
                        </view>
                    </view>
                </view>
                <view class="mt-[48rpx] flex justify-end">
                    <view
                        class="flex items-center gap-x-1 bg-[#F1F1F1] px-2 py-1 rounded-[8rpx]"
                        @click="handleShowHistory">
                        <image
                            src="@/ai_modules/digital_human/static/icons/user2.svg"
                            class="w-[24rpx] h-[24rpx]"></image>
                        <text class="text-xs">历史人设</text>
                    </view>
                </view>
            </view>
            <view v-if="step === 3" class="h-full flex flex-col">
                <view class="flex items-center gap-x-2 px-4">
                    <view
                        class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                        @click="handleShowCopywriter()">
                        <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-bold text-[32rpx]">手动输入</text>
                    </view>
                    <navigator
                        url="/ai_modules/digital_human/pages/montage_ai_copywriter/montage_ai_copywriter"
                        hover-class="none"
                        class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-bold text-[32rpx]">AI生成</text>
                    </navigator>
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-4 pb-4">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="copywriter-item"
                                @click="handleSelectCopywriter(index)">
                                <view class="text-[32rpx] font-bold mr-4">
                                    {{ item.title }}
                                </view>
                                <view class="mt-[28rpx]">
                                    {{ item.content }}
                                </view>
                                <view
                                    class="absolute right-2 top-2 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                    @click.stop="handleDeleteCopywriter(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view v-if="step === 4" class="h-full flex flex-col">
                <view class="mx-4">
                    <text class="font-bold">混剪素材（共{{ formData.materialList.length }}个）</text>
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-4 gap-[26rpx] p-4">
                            <view
                                class="bg-[#E6EBF1] rounded-[12rpx] flex flex-col items-center justify-center h-[154rpx]"
                                @click="showUploadTip = true">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加素材</text>
                            </view>
                            <view v-for="(item, index) in formData.materialList" :key="index" class="relative">
                                <view class="material-item overflow-hidden" @click="previewMaterial(item)">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[12rpx]"
                                        mode="aspectFill"></image>
                                    <view
                                        class="absolute bottom-0 h-[40rpx] w-full bg-[rgba(0,0,0,0.5)] flex items-center justify-center z-[88]">
                                        <image
                                            v-if="item.type === 'image'"
                                            src="@/ai_modules/digital_human/static/icons/pic.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <image
                                            v-else
                                            src="@/ai_modules/digital_human/static/icons/video.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                    </view>
                                </view>
                                <view
                                    class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                    @click="handleDeleteMaterial(item.id)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view v-if="step === 5" class="h-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4">
                            <view>
                                <view class="flex items-center justify-between">
                                    <text class="font-bold text-[32rpx]">数字人形象</text>
                                    <view class="flex items-center gap-x-2" @click="handleStep(1)">
                                        <view class="text-[#494949]">
                                            共<text class="text-[#00B862] font-bold text-[32rpx] mx-1">{{
                                                formData.anchorLists.length
                                            }}</text
                                            >个形象
                                        </view>
                                        <u-icon name="arrow-right" color="#C5CACA"></u-icon>
                                    </view>
                                </view>
                                <scroll-view scroll-x class="mt-[36rpx]">
                                    <view class="flex gap-x-[24rpx]">
                                        <view
                                            v-for="(item, index) in formData.anchorLists"
                                            :key="index"
                                            class="flex-shrink-0 w-[167rpx] h-[224rpx] rounded-[24rpx]">
                                            <image
                                                :src="item.pic"
                                                class="w-full h-full rounded-[24rpx]"
                                                mode="aspectFill"></image>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                            <view class="flex items-center justify-between gap-x-4 mt-[48rpx]">
                                <view class="font-bold text-[32rpx] flex-shrink-0">填写身份</view>
                                <view class="flex items-center" @click="handleStep(2)">
                                    <view class="text-[#00B862] line-clamp-1 min-w-[150rpx] text-end">{{
                                        formData.name
                                    }}</view>
                                    <view class="w-[1rpx] h-[24rpx] bg-[#C6CACC] mx-2"></view>
                                    <view class="line-clamp-1 text-[#00B862]">
                                        {{ formData.introduction }}
                                    </view>
                                    <u-icon name="arrow-right" color="#C5CACA"></u-icon>
                                </view>
                            </view>
                            <view class="flex items-center justify-between gap-x-4 mt-[48rpx]">
                                <view class="font-bold text-[32rpx] flex-shrink-0">口播文案</view>
                                <view class="flex items-center gap-x-2" @click="handleStep(3)">
                                    <view class="text-[#494949]">
                                        共<text class="text-[#00B862] font-bold text-[32rpx] mx-1">{{
                                            formData.copywriterList.length
                                        }}</text
                                        >条
                                    </view>
                                    <u-icon name="arrow-right" color="#C5CACA"></u-icon>
                                </view>
                            </view>
                            <view class="flex items-center justify-between gap-x-4 mt-[48rpx]">
                                <view class="font-bold text-[32rpx] flex-shrink-0">素材内容</view>
                                <view class="flex items-center gap-x-2" @click="handleStep(4)">
                                    <view class="text-[#494949]">
                                        共<text class="text-[#00B862] font-bold text-[32rpx] mx-1">{{
                                            formData.materialList.length
                                        }}</text
                                        >条
                                    </view>
                                    <u-icon name="arrow-right" color="#C5CACA"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="text-center text-[#B8B8B8] text-[24rpx] my-4 flex-shrink-0">
                    - 生成的视频数量由文案数量控制 -
                </view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view
                        v-if="step === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[formData.anchorLists.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-bold text-[32rpx]">{{ formData.anchorLists.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view v-else>
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                            @click="handleStep(step, 'prev')">
                            上一步
                        </view>
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view class="flex flex-col items-center gap-y-2" @click="showModelRule = true">
                        <image
                            src="@/ai_modules/digital_human/static/icons/star.svg"
                            class="w-[36rpx] h-[36rpx]"></image>
                        <text class="text-[#8C8C8C] text-[22rpx]">算力消耗</text>
                    </view>
                    <view
                        class="rounded-[16rpx] w-[456rpx] h-[100rpx] bg-black text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateVideo">
                        生成视频（{{ formData.copywriterList.length }}个）
                    </view>
                </template>
            </view>
        </view>
    </view>
    <popup-bottom
        v-model:show="showHistory"
        title="历史人设"
        show-close-btn
        :is-disabled-touch="true"
        custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="pagingHistoryRef"
                    v-model="historyLists"
                    :auto="false"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="handleQueryHistoryList">
                    <view class="flex flex-col gap-4 p-[32rpx]">
                        <view
                            class="bg-white rounded-[24rpx] p-[24rpx] relative"
                            v-for="(item, index) in historyLists"
                            :key="index"
                            @click="handleSelectHistory(item)">
                            <view class="font-bold text-[32rpx]"> {{ item.name }} </view>
                            <view class="mt-[12rpx]"> {{ item.introduced }} </view>
                            <view class="absolute right-[-12rpx] top-[-12rpx] z-[22]">
                                <view
                                    class="w-[32rpx] h-[32rpx] bg-[#0000004C] rounded-full flex items-center justify-center"
                                    @click="handleDeleteHistory(item.id)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </template>
    </popup-bottom>
    <u-popup v-model="showUploadTip" mode="center" border-radius="24" width="90%">
        <view class="bg-white rounded-[24rpx] p-[48rpx]">
            <view class="font-bold text-center">图片/视频上传须知</view>
            <view class="mt-[48rpx]">
                <view class="flex items-center gap-x-1">
                    <text class="text-[#FF3C26]">*</text>
                    <text>视频素材时长范围：{{ videoDuration[0] }}s-{{ videoDuration[1] }}s；{{ videoSize }}MB内</text>
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text
                        >图片素材支持{{ imageAccept.join("、") }}格式；{{ imageSize }}MB内，分辨率不超过{{
                            imageResolution[0]
                        }}*{{ imageResolution[1] }}</text
                    >
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text>所有素材只支持H.264编码</text>
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text>不符合条件的素材会被自动删除</text>
                </view>
            </view>
            <view class="flex gap-x-3 mt-[48rpx]">
                <view
                    class="flex-1 h-[100rpx] flex items-center justify-center shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] rounded-[16rpx]"
                    @click="showUploadTip = false"
                    >取消</view
                >
                <view
                    class="flex-1 h-[100rpx] flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,101,251,0.2)] bg-[#000000] rounded-[16rpx] text-white"
                    @click="handleUploadMaterial"
                    >去上传</view
                >
            </view>
        </view>
    </u-popup>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview
        ref="videoPreviewRef"
        v-model:show="showVideoPreview"
        title="视频预览"
        :poster="videoPreview.poster"
        :video-url="videoPreview.url" />
    <popup-bottom v-model:show="showModelRule" title="批量数字人口播混剪" height="70%">
        <template #content>
            <view class="h-full pt-4">
                <scroll-view class="h-full" scroll-y>
                    <view class="shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.05)] p-4 rounded-[32rpx] mx-4">
                        <view class="flex items-center gap-x-3">
                            <image
                                src="@/ai_modules/digital_human/static/images/common/icon.png"
                                class="w-[72rpx] h-[72rpx]"></image>
                            <view>
                                <view>专用数字人混剪（闪剪通道）</view>
                                <view class="text-[#0000004d]">轻量化呈现，快速生成，高效传播</view>
                            </view>
                        </view>
                        <view
                            class="rounded-[32rpx] mt-4 px-4"
                            style="
                                background: linear-gradient(180deg, rgba(0, 0, 0, 0.03) 0%, rgba(0, 0, 0, 0.03) 100%);
                            ">
                            <view
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">音色克隆</text>
                                <text>闪剪</text>
                            </view>
                            <view
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">形象合成</text>
                                <text>闪剪</text>
                            </view>
                            <view
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">视频克隆</text>
                                <text>闪剪</text>
                            </view>
                            <view
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">声音克隆费用</text>
                                <text
                                    >{{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).score
                                    }}{{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).unit }}</text
                                >
                            </view>

                            <view
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">形象克隆费用</text>
                                <text
                                    >{{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).score
                                    }}{{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).unit }}</text
                                >
                            </view>
                            <view
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">视频合成费用</text>
                                <text
                                    >{{ getTokenByScene(TokensSceneEnum.HUMAN_VIDEO_SHANJIAN).score
                                    }}{{ getTokenByScene(TokensSceneEnum.HUMAN_VIDEO_SHANJIAN).unit }}</text
                                >
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
    <u-popup
        v-model="showCreateSuccess"
        mode="center"
        border-radius="20"
        width="85%"
        :custom-style="{ backgroundColor: 'transparent' }"
        :mask-close-able="false">
        <view class="w-full bg-white rounded-[20rpx] py-[94rpx] px-[62rpx]">
            <view class="text-[40rpx] font-bold text-center">混剪视频创建成功</view>
            <view class="text-[30rpx] mt-[80rpx]"> 您可以立即去设置发布任务，也可以等待混剪视频成功后再发布 </view>
            <view
                @click="toPublish"
                class="mt-[98rpx] bg-black text-white text-[30rpx] font-bold rounded-[20rpx] h-[90rpx] flex items-center justify-center">
                立即去发布
            </view>
            <view
                class="mt-[30rpx] bg-[#F3F3F3] text-[30rpx] font-bold rounded-[20rpx] h-[90rpx] flex items-center justify-center"
                @click="toRecord">
                查看混剪记录
            </view>
        </view>
    </u-popup>
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import {
    getShanjianPersonList,
    getShanjianAnchorList,
    createShanjianTask,
    addShanjianPerson,
    deleteShanjianPerson,
} from "@/api/digital_human";
import { addMaterialLibrary, deleteMaterialLibrary } from "@/api/material";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { getVideoTranscodeResult, uploadFile, videoTranscode } from "@/api/app";
import { TokensSceneEnum } from "@/enums/appEnums";
import { chooseFile } from "@/components/file-upload/choose-file";
import { DigitalHumanModelVersionEnum, ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import usePolling from "@/hooks/usePolling";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const appStore = useAppStore();

const isOssTranscode = computed(() => appStore.config.is_oss_transcode);

const steps = ref([
    { step: 1, title: "选形象" },
    { step: 2, title: "填身份" },
    { step: 3, title: "选文案" },
    { step: 4, title: "传素材" },
    { step: 5, title: "交任务" },
]);

const step = ref(1);

const formData = reactive<any>({
    // 形象列表
    anchorLists: [],
    // 人物名称
    name: "",
    // 人物介绍
    introduction: "",
    copywriterList: [],
    // 素材列表
    materialList: [],
});

// 形象列表
const anchorLists = ref<any[]>([]);
const anchorLoading = ref(false);
const anchorFinished = ref(false);
const anchorQueryParams = reactive({
    page_no: 1,
    page_size: 20,
    status: 6,
});

// 历史人设列表
const historyLists = ref<any[]>([]);
const showHistory = ref(false);
// 是否是历史人设
const isHistoryPerson = ref(false);
const pagingHistoryRef = ref<any>(null);

// 编辑文案索引
const editCopywriterIndex = ref(-1);

// 上传素材提示显示
const showUploadTip = ref(false);
// 图片上传格式
const imageAccept = ["webp", "jpg", "png"];
// 图片上传大小
const imageSize = 20;
// 图片分辨率
const imageResolution = [2000, 2000];
// 视频上传大小
const videoSize = 200;
// 视频时长
const videoDuration = [1, 60];

// 上传素材列表
const uploadMaterialList = ref<any[]>([]);
// 上传素材进度
const showUploadProgress = ref(false);
const videoPreviewRef = shallowRef(null);
const showVideoPreview = ref(false);
// 视频预览数据
const videoPreview = reactive({
    poster: "",
    url: "",
});
// 显示算力消耗
const showModelRule = ref(false);
// 充值弹窗
const rechargePopupRef = shallowRef();
// 显示创建成功
const showCreateSuccess = ref(false);
// 创建结果
const createResult = ref<any>(null);

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.anchorLists.length > 0;
        case 2:
            return !!formData.name && !!formData.introduction;
        case 3:
            return formData.copywriterList.length > 0;
        case 4:
            return formData.materialList.length >= 3;
        case 5:
            return true; // 最后一步总是可进行的
        default:
            return false;
    }
};

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

const getTokenByScene = (key: string) => userStore.getTokenByScene(key);

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    // 点击“上一步”
    if (type === "prev") {
        step.value--;
        return;
    }

    // 点击“下一步”
    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: string } = {
                1: "请至少选择一个形象",
                2: "请填写人物名称和介绍",
                3: "请至少添加一条文案",
                4: "请上传至少3个素材",
            };
            uni.$u.toast(messages[step.value] || "请完成当前步骤");
        }
        return;
    }

    // 直接点击步骤条进行跳转
    if (targetStep === step.value) return;

    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const handleSelect = (val: any) => {
    if (formData.anchorLists.includes(val)) {
        formData.anchorLists = formData.anchorLists.filter((item: any) => item !== val);
    } else {
        formData.anchorLists.push(val);
    }
};

const handleCreateAnchor = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
        params: {
            source: DigitalHumanModelVersionEnum.SHANJIAN,
        },
    });
};

const handleShowHistory = async () => {
    showHistory.value = true;
    await nextTick();
    pagingHistoryRef.value?.reload();
};

const handleQueryHistoryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getShanjianPersonList({
            page_no,
            page_size,
        });
        pagingHistoryRef.value?.complete(lists);
    } catch (error) {
        console.error("查询历史记录失败:", error);
    }
};

const handleSelectHistory = (item: any) => {
    formData.name = item.name;
    formData.introduction = item.introduced;
    isHistoryPerson.value = true;
    showHistory.value = false;
};

const handleDeleteHistory = async (id: number) => {
    uni.showLoading({
        title: "删除中...",
        mask: true,
    });
    try {
        await deleteShanjianPerson({ id });
        uni.hideLoading();
        uni.showToast({
            title: "删除成功",
            icon: "none",
            duration: 3000,
        });
        historyLists.value = historyLists.value.filter((item) => item.id !== id);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "删除失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleSelectCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    const selectedCopywriter = formData.copywriterList[index];
    handleShowCopywriter(selectedCopywriter);
};

const handleShowCopywriter = (data?: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_copywriter/montage_copywriter",
        params: {
            data: data ? JSON.stringify(data) : "",
        },
    });
};

const handleDeleteCopywriter = (index: number) => {
    formData.copywriterList.splice(index, 1);
};

/**
 * 上传进度回调函数
 * @param progress - 进度值 (0-100)
 * @param options - 上传选项，包含 filePath
 */
const progressCallback = (progress: number, options: { tempFilePath: string }) => {
    const targetIndex = uploadMaterialList.value.findIndex(
        (material) => material.tempFilePath === options.tempFilePath
    );
    if (targetIndex !== -1) {
        const newList = [...uploadMaterialList.value];
        newList[targetIndex] = {
            ...newList[targetIndex],
            progress: progress,
        };
        uploadMaterialList.value = newList;
    }
};

// 视频转码
const handleVideoTranscode = async (url: string) => {
    return new Promise(async (resolve: any, reject: any) => {
        try {
            const data = await videoTranscode({
                video_url: url,
            });
            const { start, end } = usePolling(async () => {
                try {
                    const result = await getVideoTranscodeResult({
                        jobid: data.jobid,
                    });
                    if (result.state == "TranscodeSuccess") {
                        end();
                        resolve(true);
                    } else if (result.state == "TranscodeFail" || result.state == "TranscodeCancelled") {
                        end();
                        resolve(false);
                    }
                } catch (error: any) {
                    end();
                    resolve(false);
                }
            }, {});
            await start();
        } catch (error: any) {
            resolve(false);
        }
    });
};

/**
 * 统一处理文件选择和上传的函数
 * @param fileType - 文件类型 'image' 或 'video'
 */
const uploadAndProcessFiles = async (fileType: "image" | "video") => {
    uploadMaterialList.value = [];
    try {
        const isImage = fileType === "image";
        const { tempFiles } = await chooseFile({
            type: fileType,
            count: 9,
            sourceType: ["album"],
        });

        // 先过滤图片
        const fileList = [];
        for (const file of tempFiles) {
            if (isImage) {
                /**
                 * 判断条件
                 * 1. 图片大小不能超过20M
                 * 2. 图片分辨率不能超过2000*2000
                 */
                try {
                    // 1. 获取图片宽高
                    const { width, height } = await uni.getImageInfo({
                        src: file.tempFilePath,
                    });
                    if (!imageAccept.includes(file.name.split(".").pop())) {
                        uni.$u.toast(`图片格式必须是${imageAccept.join("、")}`);
                        continue;
                    }
                    if (width > imageResolution[0] || height > imageResolution[1]) {
                        uni.$u.toast(`图片分辨率不能超过${imageResolution[0]}*${imageResolution[1]}`);
                        continue;
                    }
                    if (file.size > imageSize * 1024 * 1024) {
                        uni.$u.toast(`图片大小不能超过${imageSize}M`);
                        continue;
                    }
                    fileList.push(file);
                } catch (error) {
                    continue;
                }
            } else {
                const durationOk = file.duration >= videoDuration[0] && file.duration <= videoDuration[1];
                if (!durationOk) {
                    uni.$u.toast(`视频时长不能超过${videoDuration[1]}秒`);
                    continue;
                }
                if (file.size > videoSize * 1024 * 1024) {
                    uni.$u.toast(`视频大小不能超过${videoSize}M`);
                    continue;
                }
                fileList.push(file);
            }
        }
        if (fileList.length === 0) {
            uni.$u.toast(`所选${isImage ? "图片" : "视频"}素材均不符合条件，重新上传`);
            return;
        }

        uploadMaterialList.value = fileList.map((file: any) => ({ ...file, progress: 0 }));
        showUploadProgress.value = true;

        const uploadedFilesData = [];
        for (const item of uploadMaterialList.value) {
            const coverRes: any = isImage ? null : await uploadFile("image", { filePath: item.thumbTempFilePath });
            const fileRes: any = await uploadFile(fileType, { filePath: item.tempFilePath }, (progress) =>
                progressCallback(progress, item)
            );
            uploadedFilesData.push({ item, coverRes: isImage ? fileRes : coverRes, fileRes });
        }

        async function handleAddMaterial(result: any) {
            try {
                const { item, coverRes, fileRes } = result;
                const addRes = await addMaterialLibrary({
                    name: item.name,
                    size: item.size,
                    type: 0,
                    sort: 0,
                    m_type: 2,
                    content: fileRes.uri,
                    duration: item.duration || 0,
                });

                formData.materialList.push({
                    url: fileRes.uri,
                    type: fileType,
                    pic: coverRes.uri,
                    id: addRes.id,
                });
            } catch (error) {
                console.error("添加素材失败:", error);
            }
        }

        if (fileType === "video") {
            for (const [index, data] of uploadedFilesData.entries()) {
                const { fileRes } = data;
                if (isOssTranscode.value) {
                    uni.showLoading({
                        title: `转码中(${index + 1}/${uploadedFilesData.length})...`,
                        mask: true,
                    });
                    try {
                        await handleVideoTranscode(fileRes.uri);
                        uni.hideLoading();
                    } catch (error) {
                        uni.hideLoading();
                        showUploadProgress.value = false;
                    }
                }
                await handleAddMaterial(data);
            }
            uni.hideLoading();
        } else {
            for (const data of uploadedFilesData) {
                await handleAddMaterial(data);
            }
        }

        if (uploadMaterialList.value.every((item) => item.progress === 100)) {
            showUploadProgress.value = false;
            uni.$u.toast(`上传成功`);
        }
    } catch (error) {
        uni.$u.toast(error);
        uploadMaterialList.value = [];
        showUploadProgress.value = false;
    }
};

const handleUploadMaterial = () => {
    showUploadTip.value = false;
    uni.showActionSheet({
        itemList: ["选择图片素材", "选择视频素材"],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("image");
            else if (res.tapIndex === 1) {
                uploadAndProcessFiles("video");
            }
        },
    });
};

const previewMaterial = (item: any) => {
    const { type, pic, url } = item;
    if (type === "image") {
        uni.previewImage({
            urls: [pic],
        });
    } else {
        videoPreview.poster = pic;
        videoPreview.url = url;
        showVideoPreview.value = true;
    }
};

const handleDeleteMaterial = (id: number) => {
    formData.materialList = formData.materialList.filter((item: any) => item.id !== id);
    deleteMaterialLibrary({ id });
};

// 生成视频
const handleCreateVideo = async () => {
    // 判断是否有算力
    if (userTokens.value < 0) {
        rechargePopupRef.value?.open();
        return;
    }
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        const res = await createShanjianTask({
            anchor: formData.anchorLists.map((item: any) => ({
                anchor_id: item.anchor_id,
                pic: item.pic,
                anchor_url: item.anchor_url,
                name: item.name,
            })),
            character_design: [
                {
                    name: formData.name,
                    introduced: formData.introduction,
                },
            ],
            voice: formData.anchorLists.map((item: any) => ({
                voice_id: item.voice_id,
                voice_url: item.voice_url,
                voice_name: item.voice_name,
            })),
            copywriting: formData.copywriterList,
            material: formData.materialList.map((item: any) => ({ fileUrl: item.url, type: item.type })),
        });
        if (!isHistoryPerson.value) {
            addShanjianPerson({
                name: formData.name,
                introduced: formData.introduction,
            });
        }
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "创建失败",
            icon: "none",
            duration: 3000,
        });
    }
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
        },
    });
};

const toRecord = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_works/montage_works",
        type: "reLaunch",
    });
};

// 获取形象列表
const getAnchorList = async () => {
    anchorLoading.value = true;
    try {
        const { lists, count } = await getShanjianAnchorList(anchorQueryParams);
        anchorFinished.value = !(lists.length < (anchorQueryParams.page_size || count));
        anchorLists.value = anchorLists.value.concat(lists);
    } finally {
        anchorLoading.value = false;
    }
};

const handleLoadAnchorMore = () => {
    if (!anchorFinished.value || anchorLoading.value) return;
    anchorQueryParams.page_no++;
    getAnchorList();
};

onLoad(() => {
    getAnchorList();
    uni.$on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CREATE_ANCHOR) {
            if (!data) return;
            anchorLists.value = anchorLists.value.concat(data);
        }
        if (type === ListenerTypeEnum.MONTAGE_COPYWRITER || type === ListenerTypeEnum.MONTAGE_AI_COPYWRITER) {
            if (data.length == 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
            } else {
                formData.copywriterList = formData.copywriterList.concat(data);
            }
        }
    });
});

onUnload(() => {
    uni.$off("confirm");
});
</script>

<style scoped lang="scss">
.step-item {
    @apply flex flex-col items-center justify-center relative;
    &.active {
        .step-item-icon {
            @apply border-[#00B862];
        }
        .step-item-icon-bg {
            @apply rounded-full w-full h-full bg-[#00B862];
        }
        .step-item-line {
            @apply border-[#00B862] border-solid;
        }
        .step-item-title {
            @apply text-black;
        }
    }
    &-icon {
        @apply rounded-full w-[32rpx] h-[32rpx] border border-solid border-[#D1D6D4] p-[6rpx] flex items-center justify-center;
    }

    &-line {
        @apply absolute right-[-32rpx] top-[15rpx] w-[64rpx] border-[0] border-b border-dashed border-[#D1D6D4];
    }
    &-title {
        @apply mt-[20rpx] text-[#D1D6D4];
    }
    &-success-icon {
        @apply rounded-full w-[32rpx] h-[32rpx] bg-[#73D9B1] flex items-center justify-center;
    }
}

.copywriter-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
}

.material-item {
    @apply h-[154rpx] rounded-[12rpx] relative;
}
</style>
