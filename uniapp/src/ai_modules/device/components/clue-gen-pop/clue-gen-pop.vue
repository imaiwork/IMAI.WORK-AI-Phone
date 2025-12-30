<template>
    <popup-bottom v-model="show" title="AI生成线索词" custom-class="bg-[#f3f3f3]" @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="text-[30rpx] font-bold mx-4 mt-2"> 您想获取的线索方向 </view>
                <scroll-view class="grow min-h-0 mt-[20rpx]" scroll-y>
                    <view v-if="!isGenerating" class="p-4 bg-white rounded-[16rpx] mx-4">
                        <textarea
                            class="w-full"
                            v-model="contentVal"
                            focus
                            height="364"
                            placeholder="请输入您想获取客户的行业，如：家居用品"
                            placeholder-style="color: #7C7E80; "
                            :maxlength="contentMaxLength" />
                        <view class="text-[#B2B2B2] text-[26rpx] text-end">
                            {{ contentVal.length }} / {{ contentMaxLength }}
                        </view>
                    </view>
                    <view v-else class="px-4 flex flex-wrap gap-4 pb-[100rpx]">
                        <view
                            v-for="(item, index) in chatContentList"
                            :key="index"
                            class="relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] px-4 py-2 font-bold">
                            <view v-if="item.status === 'pending'">
                                <view class="flex items-center gap-1">
                                    <image
                                        src="@/ai_modules/sph/static/icons/star2.svg"
                                        class="w-[24rpx] h-[24rpx]"></image>

                                    <text class="font-bold">线索词{{ index + 1 }}生成中</text>
                                </view>
                                <view class="mt-4">
                                    <view class="w-full h-[28rpx] bg-[#F7F8FC] rounded-[8rpx]"></view>
                                </view>
                            </view>
                            <view class="flex items-center gap-x-2" v-else>
                                <view>
                                    {{ item.content }}
                                </view>
                                <view
                                    class="rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                    @click="handleDelete(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
                <view class="flex items-center gap-2 p-4">
                    <view
                        v-if="!isGenerating"
                        class="flex-1 bg-black text-white rounded-[20rpx] h-[90rpx] flex items-center justify-center text-[30rpx] font-bold"
                        @click="generateClue(contentVal)">
                        立即生成（消耗{{ getToken }}算力）
                    </view>
                    <template v-else>
                        <view
                            v-if="isGenerated"
                            class="w-[240rpx] h-[90rpx] flex items-center justify-center rounded-[20rpx] bg-white text-[30rpx] font-bold"
                            @click="handleReload">
                            重新生成
                        </view>
                        <view
                            class="flex-1 bg-black text-white rounded-[20rpx] h-[90rpx] flex items-center justify-center text-[30rpx] font-bold"
                            @click="handleConfirm">
                            确定使用
                        </view>
                    </template>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { getAiKeywords } from "@/api/sph";
import { TokensSceneEnum } from "@/enums/appEnums";
import requestCancel from "@/utils/request/cancel";
const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", value: any[]): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const currentPromptNum = 30;
const chatContentList = ref<any[]>([]);
const contentVal = ref<string>("");
const contentMaxLength = 500;
const isGenerating = ref<boolean>(false);
const isGenerated = computed(() => {
    return chatContentList.value.every((item) => item.status === "success");
});

const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.SPH_AI_CLUE)?.score;
    return parseFloat(token) * currentPromptNum;
});

const generateClue = async (content: string) => {
    if (!contentVal.value) {
        uni.$u.toast("请输入您想获取的线索方向");
        return;
    }
    if (isGenerating.value) return;
    if (userTokens.value < getToken.value) {
        uni.$u.toast("算力不足，请充值！");
        return;
    }
    try {
        isGenerating.value = true;

        chatContentList.value = Array.from({ length: currentPromptNum }, () => ({
            title: "",
            content: "",
            status: "pending",
        }));
        // 这里要根据生成数量来请求接口, 要并发请求
        const results = await getAiKeywords({
            keyword: contentVal.value,
            targetCount: currentPromptNum,
            channelVersion: 2,
        });
        chatContentList.value = results
            .filter((item: any) => item.indexOf("=") == -1)
            .map((item: any) => ({
                content: item.trim(),
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
const handleDelete = (index: number) => {
    chatContentList.value.splice(index, 1);
};

const handleReload = () => {
    if (isGenerating.value) {
        if (isGenerated.value) {
            isGenerating.value = false;
        } else {
            uni.$u.toast("正在生成中，请稍后再试");
            return;
        }
    }
    isGenerating.value = false;
    chatContentList.value = [];
    generateClue(contentVal.value);
};

const handleConfirm = () => {
    emit(
        "confirm",
        chatContentList.value.map((item) => item.content)
    );
    close();
};

const close = () => {
    chatContentList.value = [];
    contentVal.value = "";
    isGenerating.value = false;
    requestCancel.remove("/sv.tools/getSearchTerms");
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
