<template>
    <view class="h-screen dh-bg flex flex-col">
        <u-navbar
            title="AI文案生成"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: 'transparent' }"
            :custom-back="back"></u-navbar>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4">
                    <template v-if="!isGenerating">
                        <view class="flex items-center gap-1 font-bold">
                            <text class="text-[#FF3C26]">*</text>
                            <text>您想生成的主题大纲</text>
                        </view>
                        <view class="mt-4 p-4 bg-white rounded-[16rpx]">
                            <textarea
                                v-model="contentVal"
                                focus
                                type="textarea"
                                height="364"
                                placeholder="点击此输入您想生成的主题，如：北京旅游"
                                placeholder-style="color: #7C7E80; "
                                :maxlength="contentMaxLength" />
                            <view class="text-[#B2B2B2] text-[26rpx] text-end">
                                {{ contentVal.length }} / {{ contentMaxLength }}
                            </view>
                        </view>
                        <view class="flex items-center gap-1 font-bold mt-[48rpx]">
                            <text class="text-[#FF3C26]">*</text>
                            <text> 口播文案的字数</text>
                        </view>
                        <view class="flex items-center gap-[36rpx] mt-[28rpx]">
                            <view
                                v-for="item in getPromptList"
                                :key="item.id"
                                class="prompt-length-item"
                                :class="{ active: currentPrompt?.id === item.id }"
                                @click="currentPrompt = item">
                                {{ item.name }}
                            </view>
                        </view>
                        <view class="flex items-center gap-1 font-bold mt-[48rpx]">
                            <text class="text-[#FF3C26]">*</text>
                            <text> 生成的口播文案数量</text>
                        </view>
                        <view class="flex items-center gap-[36rpx] mt-[28rpx]">
                            <view
                                v-for="(item, index) in promptNumList"
                                :key="item"
                                class="prompt-num-item"
                                :class="{ active: currentPromptNum === item }"
                                @click="currentPromptNum = item">
                                {{ item }}条
                            </view>
                        </view>
                    </template>
                    <view class="flex flex-col gap-4" v-else>
                        <view v-for="(item, index) in chatContentList" :key="index" class="copywriter-item">
                            <!-- 骨架屏 -->
                            <view v-if="item.status === 'pending'">
                                <view class="flex items-center gap-1">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/star2.svg"
                                        class="w-[24rpx] h-[24rpx]"></image>

                                    <text class="font-bold">文案{{ index + 1 }}生成中</text>
                                </view>
                                <view class="mt-4">
                                    <view class="flex flex-col gap-3">
                                        <view class="w-full h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                        <view class="w-[70%] h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                        <view class="w-[50%] h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                    </view>
                                    <view class="flex flex-col gap-3 mt-6">
                                        <view class="w-full h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                        <view class="w-[70%] h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                        <view class="w-[50%] h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                        <view class="w-[70%] h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                    </view>
                                </view>
                            </view>
                            <template v-else>
                                <view class="text-[28rpx] font-bold mr-4">
                                    <u-input
                                        v-model="item.title"
                                        placeholder-style="color: #7C7E80; "
                                        maxlength="30"></u-input>
                                </view>
                                <view class="mt-[28rpx]">
                                    <u-input
                                        v-model="item.content"
                                        type="textarea"
                                        placeholder-style="color: #7C7E80; "
                                        maxlength="500"
                                        :auto-height="false"></u-input>
                                    <view class="mt-2 text-[#B2B2B2] text-end"> {{ item.content.length }} / 500 </view>
                                </view>
                                <view
                                    class="absolute right-2 top-2 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                    @click="handleDeleteCopywriter(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </template>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5 pt-2">
            <view class="flex items-center justify-between px-4 gap-[48rpx]">
                <view
                    v-if="!isGenerating"
                    class="flex-1 flex items-center justify-center text-white rounded-[8rpx] h-[100rpx]"
                    :class="[contentVal.length > 0 ? 'bg-black' : 'bg-[#787878CC]']"
                    @click="contentPost(contentVal)">
                    生成文案（消耗{{ getToken }}算力）
                </view>
                <template v-else>
                    <view
                        class="w-[260rpx] h-[80rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787] flex items-center justify-center"
                        @click="contentPost(contentVal)">
                        重新生成
                    </view>
                    <view
                        class="flex-1 flex items-center justify-center text-white rounded-[8rpx] h-[80rpx]"
                        :class="[isGenerated ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="useContent">
                        使用文案
                    </view>
                </template>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { generateShanjianPrompt } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const contentVal = ref<string>("");
const contentMaxLength = 500;
const textLimit = ref<number>(150);
const chatContentList = ref<any[]>([]);

const promptList = [
    { id: 1, name: "长", length: 500 },
    { id: 2, name: "中", length: 300 },
    { id: 3, name: "短", length: 150 },
];

const getPromptList = computed(() => {
    return promptList;
});

// 口播数量
const promptNumList = [1, 3, 5, 10, 20];
const currentPromptNum = ref<number>(1);

const currentPrompt = ref<any>(getPromptList.value[0]);

// 是否正在生成
const isGenerating = ref<boolean>(false);

// 是否生成好
const isGenerated = computed(() => {
    return chatContentList.value.every((item) => item.status === "success");
});

// 获取消耗的算力
const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.SHANJIAN_COPYWRITING_CREATE)?.score;
    return parseFloat(token) * currentPromptNum.value;
});

const contentPost = async (userInput: string) => {
    if (!userInput.trim()) {
        uni.$u.toast("请输入文案");
        return;
    }
    if (!isGenerated.value) return;
    if (userTokens.value < getToken.value) {
        uni.$u.toast("算力不足，请充值！");
        return;
    }

    try {
        isGenerating.value = true;

        chatContentList.value = Array.from({ length: currentPromptNum.value }, () => ({
            title: "",
            content: "",
            status: "pending",
        }));
        // 这里要根据生成数量来请求接口, 要并发请求
        const promises = [];
        for (let i = 0; i < currentPromptNum.value; i++) {
            promises.push(
                generateShanjianPrompt({
                    keywords: userInput,
                    number: currentPrompt.value.length,
                })
            );
        }
        const results = await Promise.all(promises);
        chatContentList.value = results.map((item) => ({
            title: item.title,
            content: item.content,
            status: "success",
        }));
    } catch (err: any) {
        isGenerating.value = false;
        uni.showToast({
            title: err || "生成失败，请重试",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleDeleteCopywriter = (index: number) => {
    chatContentList.value.splice(index, 1);
    if (chatContentList.value.length === 0) {
        isGenerating.value = false;
    }
};

const useContent = () => {
    if (!isGenerated.value) {
        uni.$u.toast("文案在生成中...");
        return;
    }
    uni.$emit("confirm", {
        type: ListenerTypeEnum.MONTAGE_AI_COPYWRITER,
        data: chatContentList.value
            .filter((item) => item.title)
            .map((item) => ({ title: item.title, content: item.content })),
    });
    chatContentList.value = [];
    uni.navigateBack();
};

const back = () => {
    if (chatContentList.value.length > 0) {
        chatContentList.value = [];
        isGenerating.value = false;
    } else {
        uni.navigateBack();
    }
};

onLoad((options: any) => {
    textLimit.value = options.limit;
});
</script>

<style scoped lang="scss">
@mixin content-box {
    @apply absolute top-[-4rpx] left-[-4rpx] w-[100%] h-[100%]  p-[4rpx] rounded-[16rpx] content-[''];
    background: conic-gradient(#47d59f, #37cced);
    -webkit-mask: linear-gradient(#47d59f 0 100%) content-box, linear-gradient(#37cced 0 100%);
    -webkit-mask-composite: xor;
}

.prompt-length-item,
.prompt-num-item {
    @apply w-[84rpx] h-[72rpx] flex items-center justify-center  bg-white text-[26rpx]  relative rounded-[16rpx];
    &.active {
        @apply font-bold text-black;

        &::before {
            @include content-box;
        }
    }
}

.copywriter-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
    &::before {
        @include content-box;
    }
}

.send-btn {
    @apply w-[50rpx] h-[50rpx] rounded-full flex items-center justify-center;
}
</style>
