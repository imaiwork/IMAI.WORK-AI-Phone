<template>
    <view class="h-screen flex flex-col" v-if="!loading">
        <view class="mx-4 mt-4 bg-white px-[40rpx] py-[30rpx] rounded-[20rpx]">
            <view class="text-[30rpx] font-bold"> 线索词任务结束后设置 </view>
            <view class="mt-[32rpx]">
                <u-radio-group v-model="formData.type" class="w-full">
                    <view class="flex justify-between w-full">
                        <u-radio
                            v-for="(item, index) in [
                                { value: 1, label: '循环执行' },
                                { value: 2, label: 'AI自动补充' },
                                { value: 3, label: '不执行' },
                            ]"
                            :name="item.value"
                            :size="28">
                            <text class="text-base">{{ item.label }}</text>
                        </u-radio>
                    </view>
                </u-radio-group>
            </view>
            <view class="mt-[36rpx]" v-if="formData.type === 2">
                <view class="font-bold text-primary">AI补充的线索词方向：</view>
                <view class="bg-[#F3F3F3] rounded-[16rpx] px-[26rpx] mt-[16rpx] h-[90rpx] flex items-center">
                    <u-input
                        v-model="formData.ai_direction"
                        placeholder="请输入您的行业，如：家居用品"
                        maxlength="100"
                        clearable />
                </view>
            </view>
        </view>
        <view class="grow min-h-0 flex flex-col mt-[50rpx]">
            <view class="flex items-center justify-between px-4">
                <view class="text-[30rpx] font-bold">线索词组</view>
                <view @click="handleEditClue(-1)">
                    <u-icon name="plus" size="20" color="#0065FB"></u-icon
                    ><text class="text-primary font-bold ml-1">增加词组</text>
                </view>
            </view>
            <view class="mt-4 grow min-h-0">
                <scroll-view class="h-full" scroll-y v-if="formData.clue_list.length > 0">
                    <view class="p-4 flex flex-col gap-4">
                        <view
                            v-for="(item, index) in formData.clue_list"
                            :key="index"
                            class="bg-white px-[40rpx] py-[24rpx] rounded-[20rpx]"
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
                                @click.stop="handleDeleteClue(index)">
                                <image src="/static/images/icons/delete.svg" class="w-[24rpx] h-[24rpx]"></image>
                                <text class="text-xs text-[#B2B2B2] flex-shrink-0">删除</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
                <view v-else class="mt-10">
                    <view
                        class="border border-solid rounded-[20rpx] w-[220rpx] h-[88rpx] flex items-center justify-center mx-auto"
                        @click="handleAddClue">
                        <u-icon name="plus" size="20"></u-icon>
                        <text class="font-bold ml-1">添加线索词</text>
                    </view>
                </view>
            </view>
        </view>
        <view class="bg-white flex-shrink-0 pb-5 pt-4 px-6">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center"
                @click="handleSaveConfig">
                确定保存
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { createAutoTaskClueConfig, getAutoTaskClueConfigDetail } from "@/api/device";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import useMaterialStore from "@/ai_modules/device/stores/material";

const materialStore = useMaterialStore();

const { on } = useEventBusManager();

const loading = ref(true);
const deviceCode = ref<string>("");
const formData = reactive<{
    type: 1 | 2 | 3;
    ai_direction: string;

    clue_list: { clue: string[]; name: string }[];
}>({
    type: 1,
    ai_direction: "",
    clue_list: [],
});

const editClueIndex = ref<number>(-1);

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

const handleEditClueName = (index: number) => {};

const handleDeleteClue = (index: number, clueIndex?: number) => {
    if (!clueIndex) {
        uni.showModal({
            title: "提示",
            content: "确定要删除这个线索词组吗？",
            success: (res) => {
                if (res.confirm) {
                    formData.clue_list.splice(index, 1);
                }
            },
        });
    }
    // 要判断删除最后一个的时候需要弹窗提醒，提示用户是否删除
    if (formData.clue_list.length === 1) {
        uni.showModal({
            title: "提示",
            content: "删除最后一个线索词组会同时删除线索词组",
            success: (res) => {
                if (res.confirm) {
                    formData.clue_list.splice(index, 1);
                }
            },
        });
    } else {
        clueIndex && formData.clue_list[index].clue.splice(clueIndex, 1);
    }
};

const handleSaveConfig = async () => {
    if (formData.type === 2 && formData.ai_direction === "") {
        uni.$u.toast("请输入AI补充的线索词方向");
        return;
    }
    if (formData.clue_list.length === 0) {
        uni.$u.toast("请添加线索词组");
        return;
    }
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await createAutoTaskClueConfig({
            device_code: deviceCode.value,
            exec_type: formData.type,
            clue_theme: formData.ai_direction,
            keywords: formData.clue_list.map((item) => ({ title: item.name, keywords: item.clue })),
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
        const res = await getAutoTaskClueConfigDetail({ device_code: deviceCode.value });
        formData.type = res.exec_type;
        formData.ai_direction = res.clue_theme;
        formData.clue_list = res.keywords.map((item: any) => ({
            name: item.title,
            clue: item.keywords,
        }));
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

onLoad((options) => {
    deviceCode.value = options?.device_code as string;
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

<style scoped></style>
