<template>
    <popup-bottom
        v-model="show"
        :title="popupTitle"
        height="72%"
        border-radius="44"
        custom-class="bg-white"
        :z-index="5002"
        :mask-close-able="true">
        <template #content>
            <view class="copy-pop">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="copy-field">
                            <view class="copy-label-row">
                                <text class="copy-label">标题</text>
                                <text class="copy-count">{{ localForm.title.length }}/{{ limits.title }}</text>
                            </view>
                            <view v-if="isNews" class="copy-textarea-wrap">
                                <textarea
                                    v-model="localForm.title"
                                    :maxlength="limits.title"
                                    class="copy-textarea"
                                    placeholder="请输入标题" />
                            </view>
                            <view v-else class="copy-input-wrap">
                                <input
                                    v-model="localForm.title"
                                    :maxlength="limits.title"
                                    class="copy-input"
                                    placeholder="请输入标题"
                                    confirm-type="done" />
                            </view>
                        </view>

                        <view v-if="!isNews" class="copy-field">
                            <view class="copy-label-row">
                                <text class="copy-label">内容</text>
                                <text class="copy-count">{{ localForm.content.length }}/{{ limits.content }}</text>
                            </view>
                            <view class="copy-textarea-wrap">
                                <textarea
                                    v-model="localForm.content"
                                    :maxlength="limits.content"
                                    class="copy-textarea"
                                    placeholder="请输入内容" />
                            </view>
                        </view>

                        <view v-if="isPublish" class="copy-field">
                            <view class="copy-label-row">
                                <text class="copy-label">话题</text>
                                <text class="copy-count">{{ localForm.topic.length }}/{{ limits.topic }}</text>
                            </view>
                            <view class="copy-input-wrap">
                                <input
                                    v-model="localForm.topic"
                                    :maxlength="limits.topic"
                                    class="copy-input"
                                    placeholder="如：#敏感肌护肤 #换季修护"
                                    confirm-type="done" />
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <button class="plain-btn copy-save" @click="handleConfirm">保存</button>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from "vue";
import { getCopyLimits, isNewsCopy, isPublishCopy, type CopyFormData } from "../hooks/useCopyLibrary";

const props = defineProps<{
    modelValue: boolean;
    form: CopyFormData | null;
    libraryType: number;
    driverType: number;
}>();

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "confirm", value: CopyFormData): void;
}>();

const localForm = reactive<CopyFormData>({ id: undefined, title: "", content: "", topic: "" });

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const isNews = computed(() => isNewsCopy(props.libraryType, props.driverType));
const isPublish = computed(() => isPublishCopy(props.libraryType));
const limits = computed(() => getCopyLimits(props.libraryType, props.driverType));

const popupTitle = computed(() => (localForm.id ? "编辑文案" : "新增文案"));

watch(
    () => props.modelValue,
    (visible) => {
        if (!visible) return;
        localForm.id = props.form?.id;
        localForm.title = props.form?.title ?? "";
        localForm.content = props.form?.content ?? "";
        localForm.topic = props.form?.topic ?? "";
    },
    { immediate: true },
);

const handleConfirm = (): void => {
    const title = localForm.title.trim();
    const content = localForm.content.trim();
    const topic = localForm.topic.trim();

    if (isNews.value) {
        if (!title) {
            uni.$u.toast("请输入标题");
            return;
        }
    } else if (!title && !content && !topic) {
        uni.$u.toast("请至少填写一项内容");
        return;
    }

    emit("confirm", { id: localForm.id, title, content: isNews.value ? "" : content, topic });
};
</script>

<style lang="scss" scoped>
.copy-pop {
    @apply h-full flex flex-col px-[36rpx] pt-[16rpx] pb-[calc(28rpx+env(safe-area-inset-bottom))];
}

.copy-pop-scroll {
    @apply grow min-h-0;
}

.copy-field {
    @apply mb-[24rpx];
}

.copy-label-row {
    @apply flex items-center justify-between mb-[14rpx];
}

.copy-label {
    @apply text-[24rpx] text-[#9ca3af] font-semibold;
}

.copy-count {
    @apply text-[22rpx] text-[#C4C9D4] tabular-nums;
}

.copy-input-wrap {
    @apply min-h-[96rpx] rounded-[24rpx] bg-[#F4F7FA] border-[3rpx] border-solid border-[#E5ECF7] px-[26rpx] flex items-center;
}

.copy-input {
    @apply flex-1 min-w-0 h-[88rpx] bg-[transparent] text-[#1d2129] text-[28rpx];
}

.copy-textarea-wrap {
    @apply rounded-[24rpx] bg-[#F4F7FA] border-[3rpx] border-solid border-[#E5ECF7] px-[26rpx] py-[20rpx];
}

.copy-textarea {
    @apply w-full h-[280rpx] bg-[transparent] text-[#1d2129] text-[28rpx] leading-[1.7];
}

.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.copy-save {
    @apply mt-[16rpx] h-[92rpx] rounded-[28rpx] bg-primary text-white text-[30rpx] font-bold flex items-center justify-center;
    box-shadow: 0 8rpx 28rpx rgba(47, 115, 246, 0.22);
}
</style>
