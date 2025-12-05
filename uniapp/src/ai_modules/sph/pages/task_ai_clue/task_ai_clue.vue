<template>
    <view class="h-screen flex flex-col">
        <u-navbar
            title="线索词AI生成"
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
                            <text>您想获取的线索方向</text>
                        </view>
                        <view class="mt-4 p-4 bg-white rounded-[16rpx]">
                            <textarea
                                v-model="contentVal"
                                focus
                                height="364"
                                placeholder="请输入的您的行业，如：家庭用品"
                                placeholder-style="color: #7C7E80; "
                                :maxlength="contentMaxLength" />
                            <view class="text-[#B2B2B2] text-[26rpx] text-end">
                                {{ contentVal.length }} / {{ contentMaxLength }}
                            </view>
                        </view>
                        <view class="flex items-center gap-1 font-bold mt-[48rpx]">
                            <text class="text-[#FF3C26]">*</text>
                            <text> 线索词数量</text>
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
                    <view class="flex flex-wrap gap-4" v-else>
                        <view v-for="(item, index) in chatContentList" :key="index" class="copywriter-item">
                            <!-- 骨架屏 -->
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
                                <view @click="handleEdit(index)">
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
                    生成线索词（消耗{{ getToken }}算力）
                </view>
                <template v-else>
                    <view
                        class="w-[260rpx] h-[80rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787] flex items-center justify-center"
                        @click="reloadContent">
                        重新生成
                    </view>
                    <view
                        class="flex-1 flex items-center justify-center text-white rounded-[8rpx] h-[80rpx]"
                        :class="[isGenerated ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="useContent">
                        使用线索词
                    </view>
                </template>
            </view>
        </view>
    </view>
    <clue-edit ref="clueEditRef" v-model="showEdit" @confirm="handleConfirm" @close="showEdit = false" />
</template>

<script setup lang="ts">
import { getAiKeywords } from "@/api/sph";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/sph/enums";
import ClueEdit from "@/ai_modules/sph/components/clue-edit/clue-edit.vue";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const genType = ref<any>();

const contentVal = ref<string>("");
const contentMaxLength = 500;
const textLimit = ref<number>(150);
const chatContentList = ref<any[]>([]);

// 口播数量
const promptNumList = [10, 20, 30, 40, 50];
const currentPromptNum = ref<number>(promptNumList[0]);

const editIndex = ref<number>(-1);
const showEdit = ref<boolean>(false);
const clueEditRef = ref<any>(null);

// 是否正在生成
const isGenerating = ref<boolean>(false);

// 是否生成好
const isGenerated = computed(() => {
    return chatContentList.value.every((item) => item.status === "success");
});

// 获取消耗的算力
const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.SPH_AI_CLUE)?.score;
    return parseFloat(token) * currentPromptNum.value;
});

const contentPost = async (userInput: string) => {
    if (!userInput.trim()) {
        uni.$u.toast("请输入文案");
        return;
    }
    if (isGenerating.value) return;

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
        const results = await getAiKeywords({
            keyword: contentVal.value,
            targetCount: currentPromptNum.value,
            channelVersion: genType.value,
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

const reloadContent = () => {
    if (isGenerating.value) {
        if (isGenerated.value) {
            isGenerating.value = false;
        } else {
            uni.$u.toast("正在生成中，请稍后再试");
            return;
        }
    }
    chatContentList.value = [];
    isGenerating.value = false;
    contentPost(contentVal.value);
};

const handleEdit = async (index: number) => {
    editIndex.value = index;
    showEdit.value = true;
    await nextTick();
    clueEditRef.value.setFormData(chatContentList.value[index].content);
};

const handleConfirm = (val: string) => {
    chatContentList.value[editIndex.value].content = val;
    showEdit.value = false;
};

const handleDelete = (index: number) => {
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
        type: ListenerTypeEnum.TASK_AI_CLUE,
        data: chatContentList.value.map((item: any) => item.content),
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
    genType.value = options.type;
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
        @apply font-bold text-primary shadow-[0_0_0_1rpx_var(--color-primary)];
    }
}

.copywriter-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] px-4 py-2;
}

.topic-item {
    @apply bg-[rgba(0,101,251,0.03)] rounded-[10rpx] text-[#000000b3] px-[28rpx] py-[16rpx] font-bold relative;
}

.send-btn {
    @apply w-[50rpx] h-[50rpx] rounded-full flex items-center justify-center;
}
</style>
