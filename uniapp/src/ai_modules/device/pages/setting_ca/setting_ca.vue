<template>
    <view class="h-screen flex flex-col" v-if="!loading">
        <view class="px-4 mt-4">
            <view class="bg-white rounded-[16rpx] px-[8rpx] w-fit">
                <view class="w-[360rpx] grid grid-cols-2 relative h-[84rpx]">
                    <view
                        v-for="(item, index) in modelTypes"
                        :key="index"
                        class="rounded-[12rpx] font-bold flex items-center justify-center z-10 transition-colors duration-500"
                        :class="{ 'text-white': modelIndex === index }"
                        @click="modelIndex = index">
                        {{ item.name }}
                    </view>
                    <view class="tab-slider" :style="{ transform: `translateX(${modelIndex * 100}%)` }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[40rpx]">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pb-[100rpx]">
                    <view class="px-[40rpx] py-[30rpx] rounded-[20rpx] bg-white">
                        <view class="font-bold text-[30rpx]"> 线索词任务结束后设置 </view>
                        <view class="mt-[36rpx]">
                            <u-radio-group v-model="formData.type" class="w-full">
                                <view class="flex justify-between w-full">
                                    <u-radio
                                        v-for="(item, index) in [
                                            { value: 1, label: '循环执行' },
                                            { value: 2, label: 'AI自动补充' },
                                            { value: 3, label: '不执行' },
                                        ]"
                                        :key="index"
                                        :name="item.value"
                                        :size="28">
                                        <text class="text-base">{{ item.label }}</text>
                                    </u-radio>
                                </view>
                            </u-radio-group>
                            <view class="mt-[36rpx]" v-if="formData.type === 2">
                                <view class="font-bold text-primary">AI补充的线索词方向：</view>
                                <view
                                    class="bg-[#F3F3F3] rounded-[16rpx] px-[26rpx] mt-[16rpx] h-[90rpx] flex items-center">
                                    <u-input
                                        v-model="formData.ai_direction"
                                        placeholder="请输入您的行业，如：家居用品"
                                        maxlength="100"
                                        clearable />
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="flex items-center justify-between">
                            <view class="font-bold text-[30rpx]"> 获客线索词组 </view>
                            <view class="flex items-center gap-x-1" @click="handleAddClue">
                                <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                <text class="text-primary font-bold">增加词组</text>
                            </view>
                        </view>
                        <view class="mt-[24rpx]">
                            <view class="flex flex-col gap-y-4" v-if="formData.clue_list.length > 0">
                                <view
                                    v-for="(item, index) in formData.clue_list"
                                    :key="index"
                                    class="rounded-[20rpx] bg-white px-[40rpx] py-[24rpx]"
                                    @click="handleEditClue(index)">
                                    <view class="flex items-center justify-between gap-x-4">
                                        <view class="flex items-center" @click.stop="handleEditClueName(index)">
                                            <text class="text-[30rpx] font-bold mr-2 break-all line-clamp-1">{{
                                                item.name
                                            }}</text>
                                            <image
                                                src="/static/images/icons/edit_pen.svg"
                                                class="w-[24rpx] h-[24rpx] flex-shrink-0"></image>
                                        </view>
                                        <view class="flex-shrink-0">
                                            <text class="mr-1">编辑({{ item.clue.length }})</text>
                                            <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                                        </view>
                                    </view>
                                    <view class="flex flex-wrap gap-x-[10rpx] gap-y-[12rpx] mt-[32rpx]">
                                        <view
                                            v-for="(clue, clueIndex) in item.clue"
                                            :key="clueIndex"
                                            class="px-[24rpx] py-[10rpx] border border-solid border-[#E5E5E5] rounded-[100rpx] text-xs">
                                            {{ clue }}
                                        </view>
                                    </view>
                                    <view
                                        class="mt-[36rpx] flex items-center gap-x-1 w-fit"
                                        @click="handleDeleteClue(index)">
                                        <image
                                            src="/static/images/icons/delete.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <text class="text-xs text-[#B2B2B2] flex-shrink-0">删除</text>
                                    </view>
                                </view>
                            </view>
                            <view v-else class="mt-10">
                                <view
                                    class="border border-solid rounded-[20rpx] w-fit px-4 h-[88rpx] flex items-center justify-center mx-auto"
                                    @click="handleAddClue">
                                    <u-icon name="plus" size="20"></u-icon>
                                    <text class="font-bold ml-1">添加线索词</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="font-bold text-[30rpx]"> 评论话术 </view>
                        <view class="mt-[36rpx] bg-white rounded-[20rpx] px-[40rpx] py-[24rpx]">
                            <u-radio-group v-model="formData.comment_type" class="w-full">
                                <view class="flex justify-between w-full">
                                    <u-radio
                                        v-for="(item, index) in [
                                            { value: 1, label: '固定话术' },
                                            { value: 2, label: 'AI回复' },
                                            { value: 3, label: 'AI根据固话优化' },
                                        ]"
                                        :key="index"
                                        :name="item.value"
                                        :size="28">
                                        <text class="text-base">{{ item.label }}</text>
                                    </u-radio>
                                </view>
                            </u-radio-group>
                            <view class="flex flex-wrap gap-2 mt-[36rpx]" v-if="formData.comment_type == 1">
                                <view
                                    v-for="(item, index) in formData.comment_list"
                                    :key="index"
                                    class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 py-[12rpx] flex items-center gap-x-2 break-all"
                                    @click="handleEditComment(index)">
                                    {{ item }}
                                    <view
                                        class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleCommentDelete(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="border border-solid border-[#0065FB] rounded-[12rpx] px-[28rpx] h-[60rpx] flex items-center justify-center"
                                    @click="handleEditComment(-1)">
                                    <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                    <text class="text-primary font-bold ml-1">添加</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]" v-if="modelIndex === 1">
                        <view class="font-bold text-[30rpx]"> 评论词筛选 </view>
                        <view
                            class="flex flex-wrap gap-2 mt-[36rpx] bg-white rounded-[20rpx] px-[40rpx] py-[24rpx]"
                            v-if="formData.comment_filter_list.length > 0">
                            <view
                                v-for="(item, index) in formData.comment_filter_list"
                                :key="index"
                                class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 py-[12rpx] flex items-center gap-x-2 break-all">
                                {{ item.value }}
                                <view
                                    class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                    @click.stop="handleCommentFilterDelete(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                            <view
                                class="border border-solid border-[#0065FB] rounded-[12rpx] px-[28rpx] h-[60rpx] flex items-center justify-center"
                                @click="openCommentFilterEdit">
                                <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                <text class="text-primary font-bold">编辑</text>
                            </view>
                        </view>
                        <view v-else class="mt-10">
                            <view
                                class="border border-solid rounded-[20rpx] w-fit px-4 h-[88rpx] flex items-center justify-center mx-auto"
                                @click="openCommentFilterEdit">
                                <u-icon name="plus" size="20"></u-icon>
                                <text class="font-bold ml-1">添加评论词筛选</text>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]" v-if="modelIndex === 1">
                        <view class="font-bold text-[30rpx]"> 附加动作设置 </view>
                        <view class="mt-[20rpx] rounded-[20rpx] bg-white px-[36rpx]">
                            <view class="flex items-center justify-between py-[28rpx]">
                                <text class="font-bold">评论附带点赞</text>
                                <u-switch
                                    v-model="formData.comment_like"
                                    active-value="1"
                                    inactive-value="0"
                                    :size="40" />
                            </view>

                            <template v-if="false">
                                <view class="flex items-center justify-between py-[28rpx]">
                                    <text class="font-bold">评论附带关注</text>
                                    <u-switch
                                        v-model="formData.comment_follow"
                                        active-value="1"
                                        inactive-value="0"
                                        :size="40" />
                                </view>
                                <view
                                    class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                                    @click="showCommentTimePopup = true">
                                    <text class="font-bold flex-shrink-0">评论时间</text>
                                    <view class="flex items-center gap-x-1">
                                        <text
                                            class="line-clamp-1 break-all"
                                            :class="
                                                formData.comment_time > -1 ? 'text-primary font-bold' : 'text-[#B2B2B2]'
                                            "
                                            >{{ getCommentTimeLabel || "请选择" }}</text
                                        >
                                        <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                                    @click="showChooseRegionPopup = true">
                                    <text class="font-bold flex-shrink-0">地区筛选</text>
                                    <view class="flex items-center gap-x-1">
                                        <text
                                            class="line-clamp-1 break-all"
                                            :class="
                                                formData.comment_region ? 'text-primary font-bold' : 'text-[#B2B2B2]'
                                            "
                                            >{{ formData.comment_region || "请选择" }}</text
                                        >
                                        <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                                    @click="handleEditCommentGender">
                                    <text class="font-bold flex-shrink-0">用户性别</text>
                                    <view class="flex items-center gap-x-1">
                                        <text
                                            class="font-bold line-clamp-1 break-all"
                                            :class="
                                                formData.comment_gender ? 'text-primary font-bold' : 'text-[#B2B2B2]'
                                            "
                                            >{{ formData.comment_gender || "请选择" }}</text
                                        >
                                        <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                                    @click="handleEditCommentAge">
                                    <text class="font-bold flex-shrink-0">用户年龄</text>
                                    <view class="flex items-center gap-x-1">
                                        <text class="line-clamp-1 break-all text-primary font-bold">{{
                                            formData.comment_age
                                        }}</text>
                                        <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between py-[28rpx] gap-2"
                                    @click="handleEditCommentAccountFeature">
                                    <text class="font-bold flex-shrink-0">账号特征</text>
                                    <view class="flex items-center gap-x-1">
                                        <text class="line-clamp-1 break-all text-primary font-bold">{{
                                            formData.comment_account_feature == "0" ? "全部" : "跳过认证号"
                                        }}</text>
                                        <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                            </template>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white flex-shrink-0 pb-5 pt-4 px-6">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center"
                @click="handleSaveConfig">
                确定保存
            </view>
        </view>
    </view>
    <keywords-edit
        ref="keywordsEditRef"
        :title="getKeywordsTitle"
        v-model="showKeywordsEdit"
        @confirm="handleKeywordsConfirm" />
    <comment-filter ref="commentFilterRef" v-model="showCommentFilterEdit" @confirm="handleCommentFilterConfirm" />
    <choose-region v-model="showChooseRegionPopup" @confirm="handleChooseRegionConfirm" />
    <choose-age ref="chooseAgeRef" v-model="showAgePopup" @confirm="handleAgeConfirm" />
    <choose-comment-time
        v-model="showCommentTimePopup"
        :value="formData.comment_time_index"
        :list="commentTimeList"
        @confirm="handleCommentTimeConfirm" />
</template>

<script setup lang="ts">
import { createAutoTaskClosureConfig, getAutoTaskClosureConfigDetail } from "@/api/device";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import useMaterialStore from "@/ai_modules/device/stores/material";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import CommentFilter from "@/ai_modules/device/components/comment-filter/comment-filter.vue";
import ChooseRegion from "@/ai_modules/device/components/choose-region/choose-region.vue";
import ChooseAge from "@/ai_modules/device/components/choose-age/choose-age.vue";
import ChooseCommentTime from "@/ai_modules/device/components/choose-comment-time/choose-comment-time.vue";

const materialStore = useMaterialStore();

const { on } = useEventBusManager();

const loading = ref(true);
const deviceCode = ref<string>("");
const accountType = ref<AppTypeEnum>();

// 模式类型
const modelTypes = [
    { name: "简易模式", value: 1 },
    { name: "精确模式", value: 2 },
];
const modelIndex = ref<number>(0);

const formData = reactive<{
    type: 1 | 2 | 3;
    ai_direction: string;
    clue_list: any[];
    comment_list: string[];
    comment_filter_list: { value: string; checked: boolean; id: number }[];
    comment_like: string;
    comment_follow: string;
    comment_time: number;
    comment_time_index: number[];
    comment_region: string;
    comment_gender: string;
    comment_age: string;
    comment_account_feature: string;
    comment_type: 1 | 2 | 3;
}>({
    type: 1,
    ai_direction: "",
    clue_list: [],
    comment_list: [],
    comment_filter_list: [],
    comment_like: "1",
    comment_follow: "1",
    comment_time: 0,
    comment_time_index: [0],
    comment_region: "不限",
    comment_gender: "不限",
    comment_age: "不限",
    comment_account_feature: "0",
    comment_type: 1,
});

const keywordsType = ref<string>("");
const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>();
const showKeywordsEdit = ref<boolean>(false);
const editClueIndex = ref<number>(-1);
const editCommentIndex = ref<number>(-1);
const showCommentFilterEdit = ref<boolean>(false);
const showCommentTimePopup = ref(false);
const commentTimeIndex = ref<number[]>([0]);
const commentFilterRef = ref<InstanceType<typeof CommentFilter>>();
const showChooseRegionPopup = ref(false);
const showAgePopup = ref(false);
const chooseAgeRef = ref<InstanceType<typeof ChooseAge>>();
const commentTimeList = [
    { value: 0, label: "不限" },
    { value: 1, label: "24小时内" },
    { value: 2, label: "2天内" },
    { value: 3, label: "3天内" },
    { value: 4, label: "4天内" },
    { value: 5, label: "5天内" },
    { value: 6, label: "6天内" },
    { value: 7, label: "7天内" },
];

const getKeywordsTitle = computed(() => {
    switch (keywordsType.value) {
        case "clue":
            return "线索词组";
        case "comment":
            return "评论话术";
        default:
            return "";
    }
});

const getCommentTimeLabel = computed(() => {
    return formData.comment_time > -1 ? commentTimeList[formData.comment_time_index[0]].label : "请选择";
});

const handleKeywordsConfirm = (data: any) => {
    switch (keywordsType.value) {
        case "clue":
            formData.clue_list[editClueIndex.value].name = data;
            break;
        case "comment":
            if (editCommentIndex.value >= 0) {
                formData.comment_list[editCommentIndex.value] = data;
            } else {
                formData.comment_list.push(data);
            }
            break;
        default:
            break;
    }
    showKeywordsEdit.value = false;
    editClueIndex.value = -1;
    editCommentIndex.value = -1;
};

const handleEditClueName = (index: number) => {
    editClueIndex.value = index ?? -1;
    keywordsType.value = "clue";
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.clue_list[index].name);
};

const handleAddClue = () => {
    editClueIndex.value = -1;
    uni.$u.route({
        url: `/ai_modules/device/pages/clue_edit/clue_edit`,
        params: {
            type: "add",
        },
    });
};

const handleEditClue = (index: number) => {
    editClueIndex.value = index ?? -1;
    if (index > -1) {
        materialStore.setList("clueList", formData.clue_list[index].clue);
    }
    uni.$u.route({
        url: `/ai_modules/device/pages/clue_edit/clue_edit`,
        params: {
            index: index,
        },
    });
};

const handleDeleteClue = (index: number) => {
    formData.clue_list.splice(index, 1);
};

const handleEditComment = (index: number) => {
    editCommentIndex.value = index ?? -1;
    keywordsType.value = "comment";
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.comment_list[index]);
};

const handleCommentDelete = (index: number) => {
    formData.comment_list.splice(index, 1);
};

const handleCommentTimeConfirm = (res: any) => {
    formData.comment_time_index = res;
    formData.comment_time = commentTimeList[formData.comment_time_index[0]].value;
};

const openCommentFilterEdit = () => {
    keywordsEditRef.value?.setFormData(formData.comment_filter_list);
    showCommentFilterEdit.value = true;
};

const handleCommentFilterDelete = (index: number) => {
    formData.comment_filter_list.splice(index, 1);
};

const handleCommentFilterConfirm = (data: any[]) => {
    formData.comment_filter_list = data;
};

const handleEditCommentAge = () => {
    showAgePopup.value = true;
    chooseAgeRef.value?.setFormData(formData.comment_age);
};

const handleAgeConfirm = (data: string) => {
    formData.comment_age = data;
    showAgePopup.value = false;
};

const handleChooseRegionConfirm = (data: any) => {
    if (data.isAll || data.regionList.length === 0) {
        formData.comment_region = "不限";
    } else {
        formData.comment_region = data.regionList.join(";");
    }
    showChooseRegionPopup.value = false;
};

const handleEditCommentGender = () => {
    const genderList = ["不限", "男", "女"];
    uni.showActionSheet({
        itemList: genderList,
        success: (res: any) => {
            formData.comment_gender = genderList[res.tapIndex];
        },
    });
};

const handleEditCommentAccountFeature = () => {
    uni.showActionSheet({
        itemList: ["全部", "跳过认证号"],
        success: (res: any) => {
            if (res.tapIndex === 0) {
                formData.comment_account_feature = "0";
            } else {
                formData.comment_account_feature = "1";
            }
        },
    });
};

const handleSaveConfig = async () => {
    if (formData.type === 2 && !formData.ai_direction) {
        uni.$u.toast("请输入AI补充的线索词方向");
        return;
    }
    if (formData.clue_list.length === 0) {
        uni.$u.toast("请添加线索词组");
        return;
    }
    if (formData.comment_type === 1 && formData.comment_list.length === 0) {
        uni.$u.toast("请添加评论话术");
        return;
    }
    if (modelIndex.value === 1 && formData.comment_filter_list.length === 0) {
        uni.$u.toast("请添加评论筛选");
        return;
    }
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await createAutoTaskClosureConfig({
            device_code: deviceCode.value,
            mode: modelTypes[modelIndex.value].value,
            exec_type: formData.type,
            ai_direction: formData.type == 2 ? formData.ai_direction : "",
            keywords: formData.clue_list.map((item) => ({ title: item.name, keywords: item.clue })),
            comment_screening: formData.comment_filter_list.map((item) => item.value),
            touch_speech_type: formData.comment_type,
            touch_speech: formData.comment_list,
            actions: {
                msg_comment_likes: formData.comment_like,
                msg_follow: formData.comment_follow,
                comment_time: formData.comment_time,
                areas: formData.comment_region,
                gender: formData.comment_gender,
                age: formData.comment_age,
                account_feature: formData.comment_account_feature,
            },
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        uni.navigateBack();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中...",
        mask: true,
    });
    try {
        const res = await getAutoTaskClosureConfigDetail({
            device_code: deviceCode.value,
        });
        formData.type = res.exec_type;
        formData.ai_direction = res.ai_direction;
        formData.clue_list = res.keywords.map((item: any) => ({ name: item.title, clue: item.keywords }));
        formData.comment_list = res.touch_speech;
        formData.comment_filter_list = res.comment_screening.map((item: any, index: number) => ({
            value: item,
            checked: true,
            id: index,
        }));
        formData.comment_like = res.actions?.msg_comment_likes;
        formData.comment_follow = res.actions?.msg_follow;
        formData.comment_time = res.actions?.comment_time;
        formData.comment_region = res.actions?.areas;
        formData.comment_gender = res.actions?.gender;
        formData.comment_age = res.actions?.age;
        formData.comment_account_feature = res.actions?.account_feature;
        formData.comment_type = res.touch_speech_type;
        modelIndex.value = modelTypes.findIndex((item) => item.value == res.mode) || 0;
        formData.comment_time_index = [
            commentTimeList.findIndex((item) => item.value == res.actions?.comment_time) || 0,
        ];
        commentTimeIndex.value = formData.comment_time_index;
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

onLoad((options) => {
    deviceCode.value = options?.device_code as string;
    accountType.value = options?.account_type as AppTypeEnum;
    getDetail();
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CLUE_LIST) {
            if (editClueIndex.value >= 0) {
                formData.clue_list[editClueIndex.value] = {
                    ...formData.clue_list[editClueIndex.value],
                    ...data,
                };
            } else {
                formData.clue_list.push({
                    name: `线索词组${
                        formData.clue_list.length < 10
                            ? `0${formData.clue_list.length + 1}`
                            : formData.clue_list.length + 1
                    }`,
                    clue: data,
                });
            }
        }
    });
});
</script>

<style scoped lang="scss">
picker-view {
    height: 100%;
    width: 100%;
}
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-primary absolute top-[4rpx] left-0 transition-all duration-500;
}
</style>
