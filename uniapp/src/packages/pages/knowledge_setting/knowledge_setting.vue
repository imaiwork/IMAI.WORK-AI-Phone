<template>
    <view class="min-h-screen bg-[#F2F4FA] flex flex-col pb-[350rpx]">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F2F4FA' }"
            title="知识库设置"
            title-bold
            back-icon-color="#1D2129" />

        <scroll-view scroll-y class="flex-1 min-h-0">
            <!-- 基础信息 -->
            <view class="sec-bar">基础信息</view>
            <view class="card">
                <view class="field">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="field-label"><text class="text-[#EF4444]">*</text>知识库名称</text>
                        <text class="field-counter">{{ form.name.length }} / 20</text>
                    </view>
                    <input v-model="form.name" class="field-input" maxlength="20" placeholder="请输入知识库名称" />
                </view>
                <view class="field">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="field-label">知识库描述</text>
                        <text class="field-counter">{{ form.intro.length }} / 200</text>
                    </view>
                    <textarea
                        v-model="form.intro"
                        class="field-textarea"
                        maxlength="200"
                        placeholder="请输入知识库描述" />
                </view>
                <view class="row" @click="handleChooseCover">
                    <view class="row-icon bg-[#FCE7F3]">
                        <u-icon name="photo" color="#DB2777" :size="28" />
                    </view>
                    <text class="row-label">知识库封面</text>
                    <view class="flex-1 flex justify-end">
                        <image
                            v-if="form.image"
                            :src="form.image"
                            class="w-[48rpx] h-[48rpx] rounded-[10rpx]"
                            mode="aspectFill" />
                        <text v-else class="text-[26rpx] text-[#C0C8D8]">未设置</text>
                    </view>
                    <u-icon name="arrow-right" color="#C0C8D8" :size="26" />
                </view>
            </view>

            <!-- 访问控制与模式 -->
            <view class="sec-bar">访问控制与模式</view>
            <view class="card">
                <view class="row">
                    <view class="row-icon bg-[#F0FDF4]">
                        <u-icon name="lock" color="#16A34A" :size="28" />
                    </view>
                    <text class="row-label">可见权限</text>
                    <text class="row-value">{{ PREVIEW.visibility }}</text>
                    <u-icon name="arrow-right" color="#C0C8D8" :size="26" />
                </view>
                <view class="row">
                    <view class="row-icon bg-[#FFF7ED]">
                        <u-icon name="star" color="#F59E0B" :size="28" />
                    </view>
                    <text class="row-label">索引模式</text>
                    <text class="row-value">{{ PREVIEW.indexMode }}</text>
                    <u-icon name="arrow-right" color="#C0C8D8" :size="26" />
                </view>
            </view>

            <!-- 检索参数（预览） -->
            <view class="sec-bar">
                检索参数<text class="text-[22rpx] font-medium text-[#94A3B8] ml-[8rpx]">（预览）</text>
            </view>
            <view class="mx-[32rpx] mb-[28rpx] p-[28rpx] bg-[#F7F9FC] rounded-[28rpx]">
                <!-- 语义向量检索 -->
                <view class="retr-card">
                    <view class="flex items-start gap-x-[20rpx] mb-[20rpx]">
                        <view class="retr-icon">
                            <u-icon name="search" color="#2563EB" :size="32" />
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="retr-title">语义向量检索</text>
                            <text class="retr-desc"> 通过 Embedding 模型生成向量，检索余弦相似度最高的内容分段。 </text>
                        </view>
                    </view>
                    <view class="flex gap-x-[20rpx]">
                        <view class="retr-mini">
                            <text class="mini-label">Top K</text>
                            <text class="mini-val">{{ PREVIEW.topK }}</text>
                        </view>
                        <view class="retr-mini">
                            <text class="mini-label">相似度阈值</text>
                            <text class="mini-val">{{ PREVIEW.similarity }}</text>
                        </view>
                    </view>
                </view>
                <!-- 全文关键字检索（禁用） -->
                <view class="retr-card retr-card--disabled mt-[20rpx]">
                    <view class="flex items-center gap-x-[20rpx]">
                        <view class="retr-icon !bg-[#F1F5F9]">
                            <u-icon name="search" color="#94A3B8" :size="32" />
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="retr-title !text-[#94A3B8]">
                                全文关键字检索
                                <text class="text-[22rpx] font-medium text-[#94A3B8]">(已禁用)</text>
                            </text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="h-[40rpx]"></view>
        </scroll-view>

        <view class="bottom-cta">
            <view class="cta-btn" :class="{ 'cta-btn--disabled': !canSubmit }" @click="lockFn">
                <text class="text-white font-bold text-[32rpx]">保存设置</text>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { vectorKnowledgeBaseDetail, vectorKnowledgeBaseEdit } from "@/api/knowledge_base";
import { useLockFn } from "@/hooks/useLockFn";
import useUpload from "@/hooks/useUpload";

// 向量知识库默认模型配置（与 PC 编辑保持一致）
const DEFAULT_KB_MODEL = {
    documents_model_id: 2,
    documents_model_sub_id: 2,
    embedding_model_id: 3,
    embedding_model_sub_id: 3,
};
// 检索参数为预览展示（与 PC 设置页一致，暂不支持移动端修改）
const PREVIEW = {
    visibility: "私人",
    indexMode: "高质量",
    topK: 2,
    similarity: 2,
};

const kbId = ref<string | number>("");
const form = reactive({
    name: "",
    intro: "",
    image: "",
});

const canSubmit = computed(() => !!form.name.trim());

const { uploadAndProcessFiles } = useUpload({
    count: 1,
    imageResolution: [99999, 99999],
    onSuccess: (materials) => {
        if (materials[0]) form.image = materials[0].url;
    },
});

onLoad((options: any) => {
    kbId.value = options?.id || "";
    if (kbId.value) loadDetail();
});

const loadDetail = async () => {
    const data: any = await vectorKnowledgeBaseDetail({ id: kbId.value });
    form.name = data.name || "";
    form.intro = data.intro || data.description || "";
    form.image = data.image || "";
    const power = Number(data.power);
    const canManage =
        Number(data.owned) === 1 || Number(data.is_owner) === 1 || (power > 0 && power < 3);
    if (!canManage) {
        uni.$u.toast("仅创建者可修改设置");
        setTimeout(() => uni.navigateBack(), 500);
    }
};

const handleChooseCover = () => uploadAndProcessFiles("image");

const handleLocked = () => uni.$u.toast("该项暂不支持在移动端修改");

const handleSubmit = async () => {
    if (!canSubmit.value) {
        uni.$u.toast("请输入知识库名称");
        return;
    }
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await vectorKnowledgeBaseEdit({
            id: kbId.value,
            name: form.name,
            intro: form.intro,
            image: form.image,
            ...DEFAULT_KB_MODEL,
        });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none" });
        uni.$emit("knowledgeUpdated");
        setTimeout(() => uni.navigateBack(), 800);
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(typeof error === "string" ? error : "保存失败");
    }
};

const { lockFn } = useLockFn(handleSubmit);
</script>

<style lang="scss" scoped>
.sec-bar {
    @apply flex items-center text-[28rpx] font-bold text-[#1D2129] px-[32rpx] pt-[32rpx] pb-[16rpx];
    &::before {
        content: "";
        @apply w-[6rpx] h-[28rpx] bg-[#2563EB] rounded-full mr-[12rpx] flex-shrink-0;
    }
}
.card {
    @apply bg-white rounded-[28rpx] mx-[32rpx] overflow-hidden;
}
.field {
    @apply px-[36rpx] py-[28rpx] border-0 border-b border-solid border-[#F0F2F7];
}
.field-label {
    @apply text-[30rpx] font-bold text-[#1D2129];
}
.field-counter {
    @apply text-[24rpx] text-[#94A3B8];
}
.field-input {
    @apply w-full text-[30rpx] text-[#1D2129];
}
.field-textarea {
    @apply w-full text-[28rpx] text-[#1D2129] leading-relaxed min-h-[128rpx];
}
.row {
    @apply flex items-center gap-x-[20rpx] px-[36rpx] py-[26rpx] border-0 border-b border-solid border-[#F0F2F7] active:bg-[#F8FAFD];
    &:last-child {
        @apply border-b-0;
    }
}
.row-icon {
    @apply w-[56rpx] h-[56rpx] rounded-[14rpx] flex items-center justify-center flex-shrink-0;
}
.row-label {
    @apply text-[30rpx] font-medium text-[#1D2129] flex-shrink-0;
}
.row-value {
    @apply flex-1 text-right text-[28rpx] text-[#94A3B8] truncate;
}
.retr-card {
    @apply bg-white rounded-[20rpx] p-[28rpx] border border-solid border-[#EAEEF5];
}
.retr-card--disabled {
    @apply opacity-55;
}
.retr-icon {
    @apply w-[68rpx] h-[68rpx] rounded-[18rpx] bg-[#EBF2FF] flex items-center justify-center flex-shrink-0;
}
.retr-title {
    @apply block text-[28rpx] font-bold text-[#1D2129];
}
.retr-desc {
    @apply block text-[22rpx] text-[#94A3B8] leading-relaxed mt-[6rpx];
}
.retr-mini {
    @apply flex-1 bg-[#F7F9FC] rounded-[18rpx] px-[24rpx] py-[20rpx] border border-solid border-[#EAEEF5];
}
.mini-label {
    @apply block text-[22rpx] text-[#94A3B8];
}
.mini-val {
    @apply block text-[36rpx] font-extrabold text-[#2563EB] mt-[4rpx];
}
.bottom-cta {
    @apply fixed left-0 right-0 bottom-0 bg-white px-[32rpx] pt-[20rpx] z-20;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #eaeef5;
}
.cta-btn {
    @apply w-full h-[96rpx] rounded-[28rpx] flex items-center justify-center active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 12rpx 40rpx rgba(47, 115, 246, 0.28);
    &--disabled {
        background: #e5eaf3;
        box-shadow: none;
        @apply pointer-events-none;
        text {
            @apply text-[#94A3B8];
        }
    }
}
</style>
