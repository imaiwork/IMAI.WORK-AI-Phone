<template>
    <view class="page">
        <view class="page-body">
            <scroll-view scroll-y class="h-full">
                <view class="page-scroll">
                    <template v-if="!isGenerating">
                        <view class="tip-card">
                            <u-icon name="info-circle" color="#2F73F6" size="28" class="shrink-0 mt-[2rpx]"></u-icon>
                            <text class="tip-text">
                                输入分享主题，AI 将生成可用于平台发布的标题、正文与话题。
                            </text>
                        </view>

                        <view class="panel">
                            <view class="panel-hd">
                                <view class="panel-bar"></view>
                                <text class="panel-title">分享主题</text>
                                <text class="panel-required">*</text>
                            </view>
                            <view class="panel-body">
                                <textarea
                                    v-model="contentVal"
                                    class="theme-input"
                                    :style="{ height: '300rpx' }"
                                    placeholder="例如：敏感肌换季护肤技巧、红魔游戏平板开箱体验"
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                    :maxlength="contentMaxLength"
                                    focus />
                                <view class="count-row">
                                    <text
                                        class="count-text"
                                        :class="contentVal.length >= contentMaxLength ? 'danger' : ''">
                                        {{ contentVal.length }} / {{ contentMaxLength }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="panel panel-pad">
                            <view class="flex items-center gap-[8rpx] mb-[20rpx]">
                                <text class="panel-title">生成数量</text>
                                <text class="panel-required">*</text>
                            </view>
                            <view class="flex items-center gap-[12rpx] flex-wrap">
                                <view
                                    v-for="item in promptNumList"
                                    :key="item"
                                    class="num-chip"
                                    :class="{ active: currentPromptNum === item }"
                                    @click="currentPromptNum = item">
                                    <text :class="currentPromptNum === item ? 'text-primary' : 'text-[#9CA3AF]'">
                                        {{ item }}条
                                    </text>
                                </view>
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <view
                            v-for="(item, index) in chatContentList"
                            :key="index"
                            class="result-card">
                            <view v-if="item.status === 'pending'" class="result-inner">
                                <view class="skeleton-side"></view>
                                <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[24rpx]">
                                    <view class="flex items-center gap-[10rpx] mb-[20rpx]">
                                        <view class="skeleton w-[40rpx] h-[40rpx] rounded-full"></view>
                                        <view class="skeleton h-[24rpx] w-[140rpx] rounded-full"></view>
                                    </view>
                                    <view class="flex flex-col gap-[12rpx]">
                                        <view class="skeleton h-[26rpx] w-full rounded-full"></view>
                                        <view class="skeleton h-[26rpx] w-[88%] rounded-full"></view>
                                        <view class="skeleton h-[26rpx] w-[72%] rounded-full"></view>
                                        <view class="skeleton h-[26rpx] w-[60%] rounded-full"></view>
                                    </view>
                                </view>
                            </view>

                            <view v-else class="result-inner">
                                <view class="result-side"></view>
                                <view class="flex-1 px-[24rpx] pt-[22rpx] pb-[18rpx]">
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <view class="flex items-center gap-[10rpx]">
                                            <view class="result-index">
                                                <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                            </view>
                                            <text class="text-xs text-[#9CA3AF]">发布文案</text>
                                        </view>
                                        <view class="result-close" @click="handleDeleteCopywriter(index)">
                                            <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                                        </view>
                                    </view>

                                    <view class="field-box mb-[16rpx]">
                                        <text class="field-label">标题</text>
                                        <u-input
                                            v-model="item.title"
                                            placeholder="请输入标题"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                            :maxlength="50" />
                                    </view>

                                    <view class="field-box">
                                        <text class="field-label">正文</text>
                                        <u-input
                                            v-model="item.content"
                                            type="textarea"
                                            placeholder="请输入正文内容"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                            maxlength="1000"
                                            :auto-height="false" />
                                        <view class="count-row mt-[8rpx]">
                                            <text class="count-text">{{ item.content.length }} / 1000</text>
                                        </view>
                                    </view>

                                    <view v-if="item.topic?.length" class="mt-[16rpx] flex flex-wrap gap-[10rpx]">
                                        <view
                                            v-for="(topic, topicIndex) in item.topic"
                                            :key="topicIndex"
                                            class="topic-chip"
                                            @click="handleEditTopic(index, topicIndex)">
                                            <text class="text-[22rpx] font-semibold text-primary">{{ topic }}</text>
                                            <view
                                                class="topic-del"
                                                @click.stop="handleDeleteTopic(index, topicIndex)">
                                                <u-icon name="close" size="12" color="#ffffff"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>
            </scroll-view>
        </view>

        <view class="page-foot">
            <view v-if="!isGenerating">
                <view
                    class="primary-btn"
                    :class="canGenerate ? 'ready' : 'disabled'"
                    @click="handleGenerate">
                    <text class="primary-btn-text">生成文案</text>
                    <view class="token-pill">
                        <text class="token-text">消耗 {{ getToken }} 算力</text>
                    </view>
                </view>
            </view>
            <view v-else class="flex items-center gap-[16rpx]">
                <view class="secondary-btn" @click="reloadContent">
                    <u-icon name="reload" color="#4B5563" size="24"></u-icon>
                    <text class="text-[28rpx] font-bold text-[#4B5563]">重新生成</text>
                </view>
                <view
                    class="primary-btn flex-1"
                    :class="isGenerated ? 'ready' : 'disabled'"
                    @click="useContent">
                    <u-icon name="checkmark" color="#fff" size="20"></u-icon>
                    <text class="primary-btn-text">入库使用</text>
                </view>
            </view>
        </view>

        <u-popup v-model="showEditTopicPopup" mode="center" width="90%" :border-radius="20">
            <view class="topic-pop">
                <view class="topic-pop-hd">
                    <text class="text-[30rpx] font-extrabold text-[#0D1117]">编辑话题</text>
                    <view class="topic-pop-close" @click="showEditTopicPopup = false">
                        <u-icon name="close" size="18" color="#6B7280"></u-icon>
                    </view>
                </view>
                <view class="px-[32rpx] pt-[32rpx] pb-[40rpx]">
                    <view class="field-box">
                        <u-input
                            v-model="newTopic"
                            placeholder="请输入话题，如 #敏感肌护肤"
                            maxlength="40"
                            placeholder-style="color:#C0C4CC;font-size:28rpx;" />
                    </view>
                    <text class="count-text mt-[12rpx] block text-right">{{ newTopic.length }}/40</text>
                    <view class="flex items-center gap-[16rpx] mt-[32rpx]">
                        <view class="pop-btn cancel" @click="showEditTopicPopup = false">
                            <text class="text-[28rpx] font-semibold text-[#6B7280]">取消</text>
                        </view>
                        <view class="pop-btn confirm" @click="handleEditTopicConfirm">
                            <text class="text-[28rpx] font-extrabold text-white">确定</text>
                        </view>
                    </view>
                </view>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { generateMatrixPrompt } from "@/api/device";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const PUBLISH_AI_COPYWRITER_EVENT = "publish-ai-copywriter";

type GenItem = {
    title: string;
    content: string;
    topic: string[];
    status: "pending" | "success";
};

const { emit } = useEventBusManager();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const contentVal = ref("");
const contentMaxLength = 500;
const promptNumList = [1, 3, 5, 10, 20, 30];
const currentPromptNum = ref(1);
const isGenerating = ref(false);
const chatContentList = ref<GenItem[]>([]);

const showEditTopicPopup = ref(false);
const newTopic = ref("");
const editTopicIndex = ref<number[]>([]);

const isGenerated = computed(() => chatContentList.value.every((item) => item.status === "success"));
const canGenerate = computed(() => contentVal.value.trim().length > 0);

const getToken = computed(() => {
    const score = userStore.getTokenByScene(TokensSceneEnum.MATRIX_COPYWRITER)?.score;
    return parseFloat(String(score || 0)) * currentPromptNum.value;
});

const normalizeTopicList = (topic: unknown): string[] => {
    if (Array.isArray(topic)) {
        return topic.map((item) => String(item ?? "").trim()).filter(Boolean);
    }
    const text = String(topic ?? "").trim();
    if (!text) return [];
    return text.split(/[\s,，]+/).filter(Boolean);
};

const handleGenerate = async () => {
    const keywords = contentVal.value.trim();
    if (!keywords) {
        uni.$u.toast("请输入分享主题");
        return;
    }
    if (isGenerating.value && !isGenerated.value) {
        uni.$u.toast("正在生成中，请稍候");
        return;
    }
    if (userTokens.value <= getToken.value) {
        uni.$u.toast("算力不足，请充值！");
        return;
    }

    try {
        isGenerating.value = true;
        chatContentList.value = Array.from({ length: currentPromptNum.value }, () => ({
            title: "",
            content: "",
            topic: [],
            status: "pending" as const,
        }));
        const results = await generateMatrixPrompt({
            keywords,
            number: currentPromptNum.value,
        });
        const list = Array.isArray(results) ? results : [];
        chatContentList.value = list.map((item: any) => ({
            title: String(item?.title ?? "").trim(),
            content: String(item?.content ?? "").trim(),
            topic: normalizeTopicList(item?.topic),
            status: "success" as const,
        }));
        if (!chatContentList.value.length) {
            isGenerating.value = false;
            uni.showToast({ title: "未生成到文案，请重试", icon: "none", duration: 2000 });
            return;
        }
        userStore.getUser();
    } catch (err: any) {
        isGenerating.value = false;
        chatContentList.value = [];
        uni.showToast({ title: err || "生成失败，请重试", icon: "none", duration: 3000 });
    }
};

const reloadContent = () => {
    if (isGenerating.value && !isGenerated.value) {
        uni.$u.toast("正在生成中，请稍后再试");
        return;
    }
    chatContentList.value = [];
    isGenerating.value = false;
    handleGenerate();
};

const handleDeleteCopywriter = (index: number) => {
    chatContentList.value.splice(index, 1);
    if (!chatContentList.value.length) isGenerating.value = false;
};

const handleEditTopic = (index: number, topicIndex: number) => {
    editTopicIndex.value = [index, topicIndex];
    newTopic.value = chatContentList.value[index].topic[topicIndex] || "";
    showEditTopicPopup.value = true;
};

const handleDeleteTopic = (index: number, topicIndex: number) => {
    chatContentList.value[index].topic.splice(topicIndex, 1);
};

const handleEditTopicConfirm = () => {
    const [index, topicIndex] = editTopicIndex.value;
    if (index == null || topicIndex == null) return;
    const value = newTopic.value.trim();
    if (!value) {
        uni.$u.toast("请输入话题");
        return;
    }
    chatContentList.value[index].topic[topicIndex] = value.startsWith("#") ? value : `#${value}`;
    showEditTopicPopup.value = false;
};

const useContent = () => {
    if (!isGenerated.value) {
        uni.$u.toast("文案生成中…");
        return;
    }
    const data = chatContentList.value
        .filter((item) => item.title || item.content)
        .map((item) => ({
            title: item.title,
            content: item.content,
            topic: item.topic,
        }));
    if (!data.length) {
        uni.$u.toast("请至少保留一条有效文案");
        return;
    }
    emit("confirm", {
        type: PUBLISH_AI_COPYWRITER_EVENT,
        data,
    });
    chatContentList.value = [];
    uni.navigateBack();
};
</script>

<style scoped lang="scss">
.page {
    @apply h-screen flex flex-col;
    background: #f4f7fa;
}

.page-body {
    @apply grow min-h-0;
}

.page-scroll {
    @apply px-[32rpx] pt-[24rpx] pb-[32rpx] flex flex-col gap-[16rpx];
}

.tip-card {
    @apply flex items-start gap-[14rpx] px-[26rpx] py-[22rpx] rounded-[24rpx] border border-solid border-[#BAD4FF];
    background: linear-gradient(135deg, #ebf3ff, #f0f7ff);
}

.tip-text {
    @apply text-[22rpx] text-[#5C7ECC] leading-relaxed;
}

.panel {
    @apply bg-white rounded-[28rpx] overflow-hidden;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}

.panel-pad {
    @apply px-[28rpx] py-[24rpx];
}

.panel-hd {
    @apply flex items-center gap-[10rpx] px-[28rpx] h-[96rpx] border-0 border-b border-solid border-[#F0F2F5];
}

.panel-bar {
    @apply w-[6rpx] h-[32rpx] bg-primary rounded-full;
}

.panel-title {
    @apply text-[30rpx] font-bold text-[#0D1117];
}

.panel-required {
    @apply text-[22rpx] text-[#EF4444] font-bold;
}

.panel-body {
    @apply px-[28rpx] pt-[20rpx] pb-[20rpx];
}

.theme-input {
    @apply w-full text-[28rpx] text-[#0D1117] leading-relaxed;
}

.count-row {
    @apply flex justify-end;
}

.count-text {
    @apply text-[22rpx] text-[#C0C4CC];

    &.danger {
        @apply text-[#EF4444] font-bold;
    }
}

.num-chip {
    @apply w-[100rpx] h-[76rpx] flex items-center justify-center rounded-[20rpx] bg-[#F0F2F5];

    &.active {
        @apply bg-[#EBF2FF];
        box-shadow: inset 0 0 0 3rpx #bfdbfe;
    }

    text {
        @apply font-bold;
    }
}

.result-card {
    @apply bg-white rounded-[24rpx] overflow-hidden;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}

.result-inner {
    @apply flex;
}

.skeleton-side {
    @apply w-[6rpx] shrink-0 rounded-l-[24rpx];
    background: linear-gradient(180deg, #f0f2f5 25%, #e8eaed 50%, #f0f2f5 75%);
    background-size: 100% 400%;
    animation: skeleton-shimmer-v 1.4s ease infinite;
}

.result-side {
    @apply w-[6rpx] shrink-0 bg-primary rounded-l-[24rpx];
}

.result-index {
    @apply w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center shrink-0;
}

.result-close {
    @apply w-[44rpx] h-[44rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center;
}

.field-box {
    @apply bg-[#F8FAFC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#F0F2F5];
}

.field-label {
    @apply block text-[20rpx] font-semibold text-[#9CA3AF] mb-[8rpx];
}

.topic-chip {
    @apply relative flex items-center gap-[6rpx] bg-[#EBF2FF] rounded-full px-[20rpx] h-[56rpx] border border-solid border-[#0065fb]/20;
}

.topic-del {
    @apply absolute -top-[8rpx] -right-[8rpx] w-[32rpx] h-[32rpx] rounded-full bg-[#374151]/50 flex items-center justify-center;
}

.page-foot {
    @apply shrink-0 bg-white border-0 border-t border-solid border-[#F0F2F5] px-[32rpx] pt-[20rpx] pb-[50rpx];
    box-shadow: 0 -2rpx 12rpx rgba(0, 0, 0, 0.04);
}

.primary-btn {
    @apply h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx];

    &.ready {
        background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%);
        box-shadow: 0 10rpx 30rpx rgba(28, 111, 235, 0.28);
    }

    &.disabled {
        @apply opacity-60;
        background: #c0c4cc;
    }
}

.primary-btn-text {
    @apply text-[30rpx] font-extrabold text-white tracking-wide;
}

.token-pill {
    @apply flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx];
}

.token-text {
    @apply text-[22rpx] text-white font-medium;
}

.secondary-btn {
    @apply w-[220rpx] h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-[#F7F9FC];
}

.topic-pop {
    @apply bg-white rounded-[28rpx] overflow-hidden;
}

.topic-pop-hd {
    @apply flex items-center justify-center h-[96rpx] border-0 border-b border-solid border-[#F0F2F5] relative;
}

.topic-pop-close {
    @apply absolute right-[24rpx] w-[48rpx] h-[48rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center;
}

.pop-btn {
    @apply flex-1 h-[88rpx] flex items-center justify-center rounded-[20rpx];

    &.cancel {
        @apply bg-[#F3F4F6];
    }

    &.confirm {
        background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%);
        box-shadow: 0 6rpx 16rpx rgba(0, 101, 251, 0.28);
    }
}

.skeleton {
    background: linear-gradient(90deg, #f0f2f5 25%, #e8eaed 50%, #f0f2f5 75%);
    background-size: 400% 100%;
    animation: skeleton-shimmer 1.4s ease infinite;
}

@keyframes skeleton-shimmer {
    0% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes skeleton-shimmer-v {
    0% {
        background-position: 50% 100%;
    }
    100% {
        background-position: 50% 0%;
    }
}
</style>
