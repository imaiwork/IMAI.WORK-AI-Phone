<template>
    <view class="h-full flex flex-col">
        <view class="p-4">
            <view class="text-[#C7C8C9] text-[26rpx]">
                不知道该写什么，试试<text class="text-primary ml-2" @click="useCopywriter">一键填入数据</text>
            </view>
            <view class="rounded-[48rpx] bg-white px-[32rpx] mt-[28rpx]">
                <view class="h-[100rpx] flex items-center text-[#333333]"> 分享主题 </view>
                <u-line color="#e5e5e5"></u-line>
                <view class="py-2">
                    <u-input
                        v-model="contentVal"
                        type="textarea"
                        focus
                        height="160"
                        placeholder="请输入或粘贴您的文案 ..."
                        placeholder-style="color: #00000033; font-size: 26rpx;"
                        :maxlength="contentMaxLength"></u-input>
                </view>
                <view class="text-[#B2B2B2] text-[26rpx] text-end py-[34rpx]">
                    {{ contentVal.length }} / {{ contentMaxLength }}
                </view>
            </view>
            <view class="mt-[44rpx]">
                <view class="text-[26rpx] text-[#323232]">字数</view>
                <view class="flex items-center gap-2 mt-[26rpx]">
                    <view
                        v-for="item in getPromptList"
                        :key="item.id"
                        class="w-[148rpx] h-[76rpx] flex items-center justify-center rounded-full text-[26rpx]"
                        :class="[
                            currentPrompt?.id === item.id
                                ? 'bg-primary-light-9 text-primary'
                                : 'bg-[#F3F3F3] text-[#323232]',
                        ]"
                        @click="currentPrompt = item">
                        {{ item.name }}
                    </view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y :scroll-top="scrollTop">
                <view class="px-4 flex flex-col gap-2 content-box">
                    <view
                        v-for="item in chatContentList"
                        :key="item.id"
                        class="border border-solid border-[#ededed] rounded-lg p-4">
                        <view class="text-[26rpx] text-[#323232]">{{ item }}</view>
                        <div class="justify-end flex mt-2">
                            <u-button type="primary" size="mini" @click="useContent(item)">使用文案</u-button>
                        </div>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="px-4 z-[55] pb-10 mt-2">
            <u-button
                type="primary"
                shape="circle"
                :custom-style="{
                    height: '90rpx',
                    boxShadow: '0px 6px 12px 0px rgba(0, 101, 251, 0.20)',
                    fontSize: '26rpx',
                }"
                @click="contentPost(contentVal)">
                生成文案（消耗{{ getToken }}算力）
            </u-button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getRect } from "@/utils/util";
import { generatePrompt } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { aiTemplateCopywriter } from "../../config/copywriter";
import { ListenerTypeEnum } from "../../enums";
import { TokensSceneEnum } from "@/enums/appEnums";
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const useCopywriter = () => {
    contentVal.value = aiTemplateCopywriter[0 % aiTemplateCopywriter.length];
};

const contentVal = ref<string>("");
const contentMaxLength = 500;
const textLimit = ref<number>(150);
const chatContentList = ref<any[]>([]);

const promptList = [
    { id: 1, name: "短", length: 150 },
    { id: 2, name: "中", length: 300 },
    { id: 3, name: "长", length: 1000 },
];
const getPromptList = computed(() => {
    return promptList;
});

// 获取消耗的算力
const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.HUMAN_COPYWRITING_CREATE)?.score;
    return parseFloat(token);
});

const currentPrompt = ref<any>(getPromptList.value[0]);

const scrollTop = ref<number>(0);

const contentPost = async (userInput: string) => {
    if (!userInput.trim()) {
        uni.$u.toast("请输入文案");
        return;
    }
    if (userTokens.value < getToken.value) {
        uni.$u.toast("算力不足，请充值！");
        return;
    }
    uni.showLoading({
        title: "生成中...",
        mask: true,
    });
    try {
        const { content } = await generatePrompt({
            keywords: userInput,
            number: currentPrompt.value.length,
        });
        chatContentList.value.push(content);
        uni.hideLoading();
    } catch (err: any) {
        uni.hideLoading();
        uni.showToast({
            title: err || "生成失败，请重试",
            icon: "none",
            duration: 3000,
        });
    } finally {
        setTimeout(() => {
            scrollToBottom();
        }, 500);
    }
};

const { proxy }: any = getCurrentInstance();
const scrollToBottom = async () => {
    await nextTick();
    getRect(".content-box", false, proxy).then((res: any) => {
        scrollTop.value = res.height;
    });
};

const useContent = (content: string) => {
    uni.$emit("confirm", {
        type: ListenerTypeEnum.AI_COPYWRITER,
        data: { content },
    });
    chatContentList.value = [];
    uni.navigateBack();
};

onLoad((options: any) => {
    textLimit.value = options.limit;
});
</script>

<style scoped lang="scss">
.send-btn {
    @apply w-[50rpx] h-[50rpx] rounded-full flex items-center justify-center;
}
</style>
