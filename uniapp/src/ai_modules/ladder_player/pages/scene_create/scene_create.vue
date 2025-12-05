<template>
    <view class="h-screen bg-[#F9FAFD] flex flex-col">
        <view class="fixed top-0 left-0 right-0 z-50">
            <u-navbar
                :is-fixed="false"
                :border-bottom="false"
                :background="{
                    background: 'transparent',
                }">
            </u-navbar>
        </view>
        <view class="relative">
            <image src="@/ai_modules/ladder_player/static/images/common/scene_bg.png" class="w-full h-[440rpx]"></image>
        </view>
        <view class="grow min-h-0 mt-4">
            <scroll-view scroll-y class="h-full">
                <view class="mx-4 flex flex-col gap-4 pb-10">
                    <view>
                        <view class="font-bold text-xl">对练者音色</view>
                        <view class="mt-4">
                            <view class="grid grid-cols-2 gap-[24rpx]">
                                <view
                                    v-for="(item, index) in voiceList"
                                    :key="index"
                                    class="bg-[#E8F0FF] rounded-full h-[92rpx] px-1 flex items-center"
                                    :class="{
                                        'bg-primary text-white': formData.coach_voice === item.code,
                                    }"
                                    @click="formData.coach_voice = item.code">
                                    <view class="w-[76rpx] h-[76rpx] rounded-full bg-white p-1">
                                        <image :src="item.logo" class="w-full h-full rounded-full"></image>
                                    </view>
                                    <view class="font-bold flex-1 px-2 whitespace-nowrap">
                                        {{ item.name }}
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl">
                            <text class="text-[#FF4D4F]">*</text>
                            练习场景
                        </view>
                        <view class="mt-4">
                            <view class="px-4 py-1 bg-white rounded-lg">
                                <u-input
                                    v-model="formData.name"
                                    placeholder="点击此输入您想练习的场景；如：茶叶销售"
                                    placeholder-style="color: #524B6B;"></u-input>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl"> 挂载知识库 </view>
                        <view class="mt-4">
                            <view
                                class="px-4 py-1 bg-white rounded-lg flex items-center justify-between"
                                @click="openKnb()">
                                <u-input
                                    v-model="activeKnb.name"
                                    placeholder="点击配置挂载知识库"
                                    placeholder-style="color: #524B6B;"
                                    disabled></u-input>
                                <u-icon name="arrow-right" color="#524B6B" :size="24"></u-icon>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl">
                            <text class="text-[#FF4D4F]">*</text>
                            场景简介
                        </view>
                        <view class="mt-4">
                            <view class="px-4 py-1 bg-white rounded-lg">
                                <u-input
                                    v-model="formData.description"
                                    placeholder="简单概括您的场景，如：售后服务人员，正在沟通处理客户提出的退款问题"
                                    type="textarea"
                                    placeholder-style="color: #524B6B;"
                                    height="160"></u-input>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl">
                            <text class="text-[#FF4D4F]">*</text>
                            陪练者名称
                        </view>
                        <view class="mt-4">
                            <view class="px-4 py-1 bg-white rounded-lg">
                                <u-input
                                    v-model="formData.coach_name"
                                    placeholder="点击此输入您设定的陪练者名称；如：王总"
                                    placeholder-style="color: #524B6B;"></u-input>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl">陪练者情感</view>
                        <view class="mt-4">
                            <view class="flex flex-wrap gap-[24rpx]">
                                <view
                                    v-for="(item, index) in emotionList"
                                    :key="index"
                                    class="bg-[#F5F5F5] rounded-lg px-[64rpx] py-2"
                                    :class="{
                                        'bg-[#f1f6ff] text-primary': formData.coach_emotion == item.value,
                                    }"
                                    @click="formData.coach_emotion = item.value">
                                    {{ item.name }}
                                </view>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl">陪练者情感投入度</view>
                        <view class="mt-4">
                            <view class="flex flex-wrap gap-[24rpx]">
                                <view
                                    v-for="(item, index) in intensityList"
                                    :key="index"
                                    class="bg-[#F5F5F5] rounded-lg px-[64rpx] py-2"
                                    :class="{
                                        'bg-[#f1f6ff] text-primary': formData.coach_intensity == item.value,
                                    }"
                                    @click="formData.coach_intensity = item.value">
                                    {{ item.name }}
                                </view>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl">
                            <text class="text-[#FF4D4F]">*</text>
                            陪练画像描述
                        </view>
                        <view class="mt-4">
                            <view class="px-4 py-1 bg-white rounded-lg">
                                <u-input
                                    v-model="formData.coach_persona"
                                    placeholder="点击此输入您需要创建的陪练对象背景设定；如：对茶叶研究很丰富的上市公司老板，对圈子和品质要求较高，脾气不太好。"
                                    type="textarea"
                                    placeholder-style="color: #524B6B;"
                                    height="160"></u-input>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-xl">
                            <text class="text-[#FF4D4F]">*</text>
                            我的角色
                        </view>
                        <view class="mt-4">
                            <view class="px-4 py-1 bg-white rounded-lg">
                                <u-input
                                    v-model="formData.practitioner_persona"
                                    placeholder="点击此输入您当前扮演的角色；如：茶叶首席销售"
                                    placeholder-style="color: #524B6B;"></u-input>
                            </view>
                        </view>
                    </view>
                </view>
                <view :style="{ height: dynamicHeight + 'px' }"></view>
            </scroll-view>
        </view>
        <view class="mx-4 mb-4">
            <view class="flex items-center justify-center gap-2">
                <u-button
                    type="primary"
                    shape="circle"
                    :custom-style="{
                        width: '556rpx',
                        height: '104rpx',
                    }"
                    @click="handleCreateScene">
                    <view class="flex items-center gap-2">
                        <template v-if="formData.id">确认保存</template>
                        <template v-else>
                            <image
                                class="w-[48rpx] h-[48rpx]"
                                src="@/ai_modules/ladder_player/static/icons/tips.svg"></image>
                            <text class="text-xl font-bold">马上创建并开始进行练习</text>
                        </template>
                    </view>
                </u-button>
                <view
                    v-if="detail && detail.user_id != 0"
                    class="w-[96rpx] h-[96rpx] flex items-center justify-center rounded-full bg-[#EB2F2F]"
                    @click="showPopup = true">
                    <u-icon name="trash-fill" color="#fff" :size="48"></u-icon>
                </view>
            </view>
        </view>
    </view>
    <u-popup v-model="showPopup" mode="bottom" border-radius="40" height="25%">
        <view class="h-full flex flex-col">
            <view class="flex items-center justify-center gap-2 h-[112rpx]">
                <u-icon name="info-circle" color="#FE975F" :size="32"></u-icon>
                <text class="text-[#2C2C36] text-xl font-bold">确定删除场景吗？</text>
            </view>
            <view>
                <u-line />
            </view>
            <view class="mt-4 px-4 grow text-[#4C4B6A]">
                确认删除后，无法找回该场景，若重新创建则需另外扣除算力。
            </view>
            <view class="flex justify-between gap-4 px-[56rpx] pb-[56rpx]">
                <view
                    class="flex-1 flex items-center justify-center h-[88rpx] rounded-full shadow-[0_0_0_2rpx_rgba(232,234,242,1)]"
                    @click="showPopup = false">
                    再考虑下
                </view>
                <view
                    class="flex-1 flex items-center justify-center h-[88rpx] text-white rounded-full bg-[#EB2F2F]"
                    @click="handleDeleteScene">
                    确认删除
                </view>
            </view>
        </view>
    </u-popup>
    <knb-select ref="knbSelectRef" :show-vector="false" @confirm="getSelectKnb"></knb-select>
</template>

<script setup lang="ts">
import { lpSceneAdd, lpSceneEdit, lpSceneDetail, lpSceneDelete } from "@/api/ladder_player";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import { useAppStore } from "@/stores/app";
import KnbSelect from "@/components/knb-select/knb-select.vue";
import { KnbTypeEnum } from "@/enums/appEnums";
const appStore = useAppStore();

const { dynamicHeight } = useKeyboardHeight();

const formData = reactive<Record<string, any>>({
    id: "",
    name: "",
    description: "",
    coach_name: "",
    coach_persona: "",
    coach_voice: "",
    practitioner_persona: "",
    coach_emotion: "",
    coach_intensity: "",
    index_id: undefined,
    kb_id: undefined,
    kb_type: "",
});

const detail = ref<any>(null);

// 陪练者情感
const emotionList = computed(() => {
    const data = appStore.getLadderConfig?.emotions || [];
    if (data.length) {
        if (!formData.id) {
            formData.coach_emotion = data[0].value;
        }
        return data;
    }
    return [];
});

// 陪练者情感投入程度
const intensityList = computed(() => {
    const data = appStore.getLadderConfig?.intensity || [];
    if (data.length) {
        if (!formData.id) {
            formData.coach_intensity = data[0].value;
        }
        return data;
    }
    return [];
});

const voiceList = computed(() => {
    const data = appStore.getLadderConfig?.voice || [];
    if (data.length) {
        if (!formData.id) {
            formData.coach_voice = data[0].code;
        }
        return data;
    }
    return [];
});

const knbSelectRef = shallowRef<InstanceType<typeof KnbSelect>>();
const activeKnb = ref<any>({
    name: "",
    index_id: "",
});
const openKnb = async () => {
    await nextTick();
    knbSelectRef.value?.open(activeKnb.value);
};

const getSelectKnb = (val: any) => {
    const { type, data } = val;
    activeKnb.value = data;
    if (type == KnbTypeEnum.RAG) {
        formData.index_id = data.index_id;
        formData.kb_id = undefined;
    } else {
        formData.kb_id = data.id;
        formData.index_id = undefined;
    }
    formData.kb_type = type == KnbTypeEnum.RAG ? 1 : 2;
};

const showPopup = ref(false);

const handleCreateScene = async () => {
    if (!formData.name) {
        uni.$u.toast("请输入场景名称");
        return;
    } else if (!formData.description) {
        uni.$u.toast("请输入场景简介");
        return;
    } else if (!formData.coach_name) {
        uni.$u.toast("请输入陪练者名称");
        return;
    } else if (!formData.coach_persona) {
        uni.$u.toast("请输入陪练画像描述");
        return;
    } else if (!formData.practitioner_persona) {
        uni.$u.toast("请输入我的角色");
        return;
    }
    uni.showLoading({
        title: "创建中",
        mask: true,
    });
    const title = formData.id ? "编辑" : "创建";
    try {
        const result = formData.id ? await lpSceneEdit(formData) : await lpSceneAdd(formData);
        uni.hideLoading();
        uni.showToast({
            title: `${title}成功`,
            icon: "none",
            duration: 3000,
        });
        if (!formData.id) {
            uni.$u.route({
                url: "/ai_modules/ladder_player/pages/chat/chat",
                params: {
                    id: result.id,
                },
                type: "redirect",
            });
            return;
        }
        setTimeout(() => {
            uni.navigateBack();
        }, 2000);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || `${title}失败`,
            icon: "none",
            duration: 3000,
        });
    }
};

const handleDeleteScene = () => {
    uni.showModal({
        title: "提示",
        content: "确定要删除场景吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中",
                    mask: true,
                });
                try {
                    await lpSceneDelete({ id: formData.id });
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    setTimeout(() => {
                        uni.navigateBack({
                            delta: 2,
                        });
                    }, 2000);
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

const getDetail = async () => {
    const data = await lpSceneDetail({ id: formData.id });
    detail.value = data;
    activeKnb.value.index_id = data.knowledge?.index_id;
    activeKnb.value.name = data.knowledge?.name;
    setFormData(data);
};

const setFormData = async (row: any) => {
    for (const key in formData) {
        if (row[key] != null && row[key] != undefined) {
            //@ts-ignore
            formData[key] = row[key];
        }
    }
};

onLoad((options: any) => {
    if (options.id) {
        formData.id = options.id;
        getDetail();
    }
});
</script>

<style scoped></style>
