<template>
    <view class="h-screen flex flex-col">
        <view class="p-4">
            <view class="flex items-center gap-1 font-medium">
                <text class="text-[#FF3C26]">*</text>
                <text>请说说您的想法</text>
            </view>
            <view class="mt-4 p-4 bg-white rounded-[16rpx]">
                <textarea
                    class="w-full"
                    v-model="contentVal"
                    type="textarea"
                    height="160"
                    placeholder="请输入或粘贴您的文案 ..."
                    placeholder-style="color: #7C7E80;font-size: 26rpx; "
                    :maxlength="contentMaxLength" />
                <view class="text-[#B2B2B2] text-[26rpx] text-end">
                    {{ contentVal.length }} / {{ contentMaxLength }}
                </view>
            </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 flex flex-col gap-2 pb-[100rpx] content-box">
                    <view
                        v-for="item in chatContentList"
                        :key="item.id"
                        class="border border-solid border-[#ededed] rounded-lg p-4 bg-white">
                        <template v-if="item.status === 'pending'">
                            <view class="flex items-center gap-1">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/star2.svg"
                                    class="w-[24rpx] h-[24rpx]"></image>

                                <text class="font-medium">文案生成中</text>
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
                        </template>
                        <template v-else>
                            <view class="text-[#323232] leading-[1.5]">{{ item.content }}</view>
                            <view class="justify-end flex mt-2">
                                <u-button type="primary" size="mini" @click="useContent(item)">使用文案</u-button>
                            </view>
                        </template>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5 pt-4 px-4">
            <view
                class="flex-1 flex items-center justify-center text-white rounded-[8rpx] h-[100rpx]"
                :class="[contentVal.length > 0 && !isGenerating ? 'bg-black' : 'bg-[#787878CC]']"
                @click="contentPost()">
                生成文案<template v-if="isSystem">（消耗{{ getToken }}算力）</template>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import useAgent from "@/ai_modules/digital_human/hooks/useAgent";

const { emit } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const agentData = ref<{
    type: string;
    genType: number;
    agentType: number;
    agentId: number;
}>({
    type: "",
    genType: 0,
    agentType: 0,
    agentId: -1,
});

const contentVal = ref<string>("");
const contentMaxLength = 500;
const chatContentList = ref<any[]>([]);

// 是否正在生成
const isGenerating = ref<boolean>(false);

const isSystem = computed(() => agentData.value.agentType == 1);

// 获取消耗的算力
const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score;
    return parseFloat(token);
});

const { result, getDetail, handleGenerate, systemChat } = useAgent({
    onfinish: () => {
        chatContentList.value.forEach((item) => {
            if (item.status === "pending") {
                item.status = "success";
                item.content = result.value;
            }
        });
        isGenerating.value = false;
        userStore.getUser();
        uni.hideLoading();
    },
    onerror: (error) => {
        const pendingIndex = chatContentList.value.findIndex((item) => item.status === "pending");
        if (pendingIndex !== -1) {
            chatContentList.value.splice(pendingIndex, 1);
        }
        isGenerating.value = false;
        uni.hideLoading();
        uni.showToast({
            title: error || "生成失败，请重试",
            icon: "none",
            duration: 3000,
        });
    },
});

const contentPost = async () => {
    if (!contentVal.value.trim()) {
        uni.$u.toast("请输入文案");
        return;
    }
    if (isGenerating.value) {
        uni.$u.toast("文案在生成中...");
        return;
    }
    if (userTokens.value < (isSystem.value ? 0 : getToken.value)) {
        uni.$u.toast("算力不足，请充值！");
        return;
    }
    const chatContent = reactive({
        content: "",
        status: "pending",
    });
    isGenerating.value = true;
    chatContentList.value.unshift(chatContent);
    if (isSystem.value) {
        try {
            const { content } = await systemChat({
                sn: agentData.value.agentId,
                keywords: contentVal.value,
                number: 1,
                length: 200,
            });
            if (content && content.length > 0) {
                chatContent.content = content[0];
                chatContent.status = "success";
                isGenerating.value = false;
                userStore.getUser();
            }
        } catch (err: any) {
            isGenerating.value = false;
            chatContentList.value = [];
            uni.showToast({
                title: err || "生成失败，请重试",
                icon: "none",
                duration: 3000,
            });
        }
    } else {
        await handleGenerate(contentVal.value, agentData.value.agentType);
    }
};

const useContent = (item: any) => {
    const { status, content } = item;
    if (status === "pending") {
        uni.$u.toast("文案在生成中...");
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.SORA_AI_COPYWRITER,
        data: content,
    });
    uni.navigateBack();
};

onLoad(async (options: any) => {
    if (options.agentData) {
        agentData.value = JSON.parse(options.agentData);
        if (!isSystem.value) {
            await getDetail(agentData.value.agentId, agentData.value.agentType);
        }
    }
    if (options.content) {
        contentVal.value = options.content;
        contentPost();
    }
});
</script>

<style scoped lang="scss">
.send-btn {
    @apply w-[50rpx] h-[50rpx] rounded-full flex items-center justify-center;
}
</style>
