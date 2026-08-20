<template>
    <view class="min-h-screen bg-[#F2F4FA] flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F2F4FA' }"
            :title="navTitle"
            title-bold
            back-icon-color="#1D2129" />

        <scroll-view scroll-y class="flex-1 min-h-0">
            <!-- 封面 -->
            <view class="flex flex-col items-center pt-[36rpx] pb-[44rpx]">
                <view class="cover-avatar" @click="handleChooseCover">
                    <image v-if="coverPreview" :src="coverPreview" class="cover-img" mode="aspectFill" />
                    <view v-else class="cover-placeholder">
                        <u-icon name="camera-fill" color="#B6C2D9" :size="44" />
                    </view>
                    <view class="cover-edit">
                        <u-icon name="plus" color="#ffffff" :size="20" />
                    </view>
                </view>
                <text class="text-[22rpx] text-[#94A3B8] mt-[20rpx]">为您的 AI 提供精准的私有数据支撑</text>
            </view>

            <!-- 名称 + 描述 -->
            <view class="card">
                <view class="field">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="field-label"><text class="text-[#EF4444]">*</text>知识库名称</text>
                        <text class="text-[24rpx] text-[#94A3B8]">{{ form.name.length }} / 20</text>
                    </view>
                    <input v-model="form.name" class="field-input" maxlength="20" placeholder="请输入知识库名称" />
                </view>
                <view class="field no-border">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="field-label">知识库描述</text>
                        <text class="text-[24rpx] text-[#94A3B8]">{{ form.intro.length }} / 200</text>
                    </view>
                    <textarea
                        v-model="form.intro"
                        class="field-textarea"
                        maxlength="200"
                        placeholder="请输入知识库描述" />
                </view>
            </view>
        </scroll-view>

        <view class="bottom-cta">
            <view class="cta-btn" :class="{ 'cta-btn--disabled': !canSubmit }" @click="lockFn">
                <text class="text-white font-bold text-[32rpx]">{{ ctaText }}</text>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { vectorKnowledgeBaseAdd, vectorKnowledgeBaseEdit, vectorKnowledgeBaseDetail } from "@/api/knowledge_base";
import { useAppStore } from "@/stores/app";
import { useLockFn } from "@/hooks/useLockFn";
import useUpload from "@/hooks/useUpload";

// 向量知识库默认模型配置（与 PC 创建保持一致）
const DEFAULT_KB_MODEL = {
    documents_model_id: 2,
    documents_model_sub_id: 2,
    embedding_model_id: 3,
    embedding_model_sub_id: 3,
};

const appStore = useAppStore();
// 用户未选封面时，使用网站配置的 shop_logo（服务端 URL，小程序无需上传）
const defaultCover = computed(() => appStore.getWebsiteConfig.shop_logo || "");

const form = reactive({
    name: "",
    intro: "",
    image: "",
});

const editId = ref<string | number>("");
const navTitle = computed(() => (editId.value ? "编辑知识库" : "新建知识库"));
const ctaText = computed(() => (editId.value ? "保存" : "立即开启知识库"));
const canSubmit = computed(() => !!form.name.trim());
const coverPreview = computed(() => form.image || defaultCover.value);

const { uploadAndProcessFiles } = useUpload({
    count: 1,
    imageResolution: [99999, 99999],
    onSuccess: (materials) => {
        if (materials[0]) form.image = materials[0].url;
    },
});

onLoad((options: any) => {
    if (options?.id) {
        editId.value = options.id;
        loadDetail(options.id);
    }
});

const loadDetail = async (id: string | number) => {
    uni.showLoading({ title: "加载中...", mask: true });
    try {
        const data: any = await vectorKnowledgeBaseDetail({ id });
        const power = Number(data.power);
        const canManage =
            Number(data.owned) === 1 || Number(data.is_owner) === 1 || (power > 0 && power < 3);
        if (!canManage) {
            uni.$u.toast("仅创建者可编辑该知识库");
            setTimeout(() => uni.navigateBack(), 500);
            return;
        }
        form.name = data.name || "";
        form.intro = data.intro || data.description || "";
        form.image = data.image || "";
    } catch (error) {
        uni.$u.toast("获取知识库详情失败");
    } finally {
        uni.hideLoading();
    }
};

const handleChooseCover = () => {
    uploadAndProcessFiles("image");
};

// 跳转知识库详情（文档管理），用 redirectTo 替换当前页，返回直接回列表
const goKnowledgeDetail = (id: string | number, name: string) => {
    if (!id) {
        uni.navigateBack();
        return;
    }
    uni.redirectTo({
        url: `/packages/pages/knowledge_detail/knowledge_detail?id=${id}&name=${encodeURIComponent(name || "")}`,
    });
};

const handleSubmit = async () => {
    if (!canSubmit.value) {
        uni.$u.toast("请输入知识库名称");
        return;
    }
    const isEdit = !!editId.value;
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        // 用户未选封面时回退到网站 shop_logo（服务端 URL，小程序无需上传）
        const image = form.image || defaultCover.value;
        let newId: string | number = editId.value;
        if (isEdit) {
            await vectorKnowledgeBaseEdit({
                id: editId.value,
                name: form.name,
                intro: form.intro,
                image,
            });
        } else {
            const data: any = await vectorKnowledgeBaseAdd({
                name: form.name,
                intro: form.intro,
                image,
                ...DEFAULT_KB_MODEL,
            });
            newId = data?.id;
        }
        uni.hideLoading();
        uni.$emit("knowledgeUpdated");

        if (isEdit) {
            // 编辑完成后提醒是否前往完善详细内容
            uni.showModal({
                title: "保存成功",
                content: "是否前往更新知识库的详细内容（文档管理等）？",
                confirmText: "前往",
                cancelText: "暂不",
                success: (res) => {
                    if (res.confirm) {
                        goKnowledgeDetail(newId, form.name);
                    } else {
                        uni.navigateBack();
                    }
                },
            });
        } else {
            // 新建成功直接进入详情页完善文档
            uni.showToast({ title: "创建成功", icon: "none", duration: 3000 });
            setTimeout(() => goKnowledgeDetail(newId, form.name), 600);
        }
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
    }
};

const { lockFn } = useLockFn(handleSubmit);
</script>

<style lang="scss" scoped>
.cover-avatar {
    @apply w-[176rpx] h-[176rpx] rounded-full bg-white flex items-center justify-center relative;
    box-shadow: 0 8rpx 32rpx rgba(126, 82, 224, 0.18);
}
.cover-img {
    @apply w-full h-full rounded-full;
}
.cover-placeholder {
    @apply w-full h-full rounded-full flex items-center justify-center bg-[#F2F4FA];
}
.cover-edit {
    @apply absolute right-[6rpx] bottom-[6rpx] w-[48rpx] h-[48rpx] rounded-full bg-[#2F73F6] flex items-center justify-center;
    box-shadow: 0 4rpx 16rpx rgba(47, 115, 246, 0.42);
}

.card {
    @apply bg-white rounded-[28rpx] mx-[24rpx] overflow-hidden;
}
.field {
    @apply px-[32rpx] py-[28rpx] border-[0] border-b border-solid border-[#F0F2F7];
}
.no-border {
    @apply border-b-0;
}
.field-label {
    @apply text-[30rpx] font-bold text-[#1D2129];
}
.field-input {
    @apply w-full text-[30rpx] text-[#1D2129];
}
.field-textarea {
    @apply w-full text-[28rpx] text-[#1D2129] leading-relaxed min-h-[140rpx];
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
