<template>
    <popup-bottom
        v-model="show"
        :height="isPublish ? '72%' : '92%'"
        border-radius="48"
        custom-class="bg-white"
        :z-index="5002"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="copy-handle"></view>
            <view class="copy-header">
                <view class="flex-1 min-w-0">
                    <text class="text-[34rpx] font-semibold text-[#202937]">{{ popupTitle }}</text>
                    <text class="block mt-[8rpx] text-[24rpx] text-[#647184] leading-[36rpx]">
                        {{ copyTypeLabel }} · {{ fieldHint }}
                    </text>
                </view>
                <view class="copy-close active:opacity-70" @click="emit('update:modelValue', false)">
                    <u-icon name="close" color="#5B6B80" size="24"></u-icon>
                </view>
            </view>
        </template>
        <template #content>
            <view class="copy-pop">
                <view class="copy-scroll-wrap">
                    <scroll-view scroll-y class="h-full">
                        <view class="copy-field">
                            <view class="copy-label-row">
                                <text class="copy-label">{{ isNews ? "标题内容" : "标题" }}</text>
                                <text class="copy-count">{{ localForm.title.length }}/{{ limits.title }}</text>
                            </view>
                            <view v-if="isNews" class="copy-textarea-wrap">
                                <textarea
                                    v-model="localForm.title"
                                    :maxlength="limits.title"
                                    class="copy-textarea"
                                    placeholder="请输入标题"
                                    placeholder-style="color: #647184" />
                            </view>
                            <view v-else class="copy-input-wrap">
                                <input
                                    v-model="localForm.title"
                                    :maxlength="limits.title"
                                    class="copy-input"
                                    placeholder="请输入标题"
                                    placeholder-style="color: #647184"
                                    confirm-type="done" />
                            </view>
                        </view>

                        <view v-if="!isNews" class="copy-field">
                            <view class="copy-label-row">
                                <text class="copy-label">正文</text>
                                <text class="copy-count">{{ localForm.content.length }}/{{ limits.content }}</text>
                            </view>
                            <view class="copy-textarea-wrap">
                                <textarea
                                    v-model="localForm.content"
                                    :maxlength="limits.content"
                                    class="copy-textarea"
                                    placeholder="请输入内容"
                                    placeholder-style="color: #647184" />
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
                                    placeholder="输入话题，多个话题用空格分隔"
                                    placeholder-style="color: #647184"
                                    confirm-type="done" />
                            </view>
                        </view>

                        <template v-if="!isPublish">
                            <view class="copy-field">
                                <view class="copy-label-row">
                                    <text class="copy-label">发布标题</text>
                                    <text class="copy-count">{{ localForm.publish_title.length }}/{{ pairLimits.title }}</text>
                                </view>
                                <view class="copy-input-wrap">
                                    <input
                                        v-model="localForm.publish_title"
                                        :maxlength="pairLimits.title"
                                        class="copy-input"
                                        placeholder="可以不填"
                                        placeholder-style="color: #647184"
                                        confirm-type="done" />
                                </view>
                            </view>
                            <view class="copy-field">
                                <view class="copy-label-row">
                                    <text class="copy-label">发布正文</text>
                                    <text class="copy-count">{{ localForm.publish_content.length }}/{{ pairLimits.content }}</text>
                                </view>
                                <view class="copy-textarea-wrap">
                                    <textarea
                                        v-model="localForm.publish_content"
                                        :maxlength="pairLimits.content"
                                        class="copy-textarea"
                                        placeholder="可以不填"
                                        placeholder-style="color: #647184" />
                                </view>
                            </view>
                            <view class="copy-field">
                                <view class="copy-label-row">
                                    <text class="copy-label">话题</text>
                                    <text class="copy-count">{{ localForm.publish_topic.length }}/{{ pairLimits.topic }}</text>
                                </view>
                                <view class="copy-input-wrap">
                                    <input
                                        v-model="localForm.publish_topic"
                                        :maxlength="pairLimits.topic"
                                        class="copy-input"
                                        placeholder="可以不填，多个话题用空格分隔"
                                        placeholder-style="color: #647184"
                                        confirm-type="done" />
                                </view>
                            </view>
                        </template>
                    </scroll-view>
                </view>
                <view class="copy-footer">
                    <button class="plain-btn copy-save" @click="handleConfirm">保存文案</button>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from "vue";
import {
    COPY_PUBLISH_CONTENT_LIMIT,
    COPY_TITLE_LIMIT,
    COPY_TOPIC_LIMIT,
    copyDriveTypes,
    getCopyLimits,
    isNewsCopy,
    isPublishCopy,
    type CopyFormData,
} from "../hooks/useCopyLibrary";

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

const localForm = reactive<CopyFormData>({
    id: undefined,
    title: "",
    content: "",
    topic: "",
    publish_title: "",
    publish_content: "",
    publish_topic: "",
});

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const isNews = computed(() => isNewsCopy(props.libraryType, props.driverType));
const isPublish = computed(() => isPublishCopy(props.libraryType));
const limits = computed(() => getCopyLimits(props.libraryType, props.driverType));
const copyTypeLabel = computed(() =>
    isPublish.value
        ? "发布文案"
        : copyDriveTypes.find((item) => item.key === props.driverType)?.label || "视频驱动文案",
);

const popupTitle = computed(() => (localForm.id ? "编辑文案" : "新增文案"));
const pairLimits = { title: COPY_TITLE_LIMIT, content: COPY_PUBLISH_CONTENT_LIMIT, topic: COPY_TOPIC_LIMIT };
const fieldHint = computed(() => {
    if (isPublish.value) return "至少填写一项内容";
    return isNews.value ? "标题内容必填，发布文案可以不填" : "至少填写一项内容，发布文案可以不填";
});

watch(
    () => props.modelValue,
    (visible) => {
        if (!visible) return;
        localForm.id = props.form?.id;
        localForm.title = props.form?.title ?? "";
        localForm.content = props.form?.content ?? "";
        localForm.topic = props.form?.topic ?? "";
        localForm.publish_title = props.form?.publish_title ?? "";
        localForm.publish_content = props.form?.publish_content ?? "";
        localForm.publish_topic = props.form?.publish_topic ?? "";
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

    emit("confirm", {
        id: localForm.id,
        title,
        content: isNews.value ? "" : content,
        topic: isPublish.value ? topic : "",
        publish_title: isPublish.value ? "" : localForm.publish_title.trim(),
        publish_content: isPublish.value ? "" : localForm.publish_content.trim(),
        publish_topic: isPublish.value ? "" : localForm.publish_topic.trim(),
    });
};
</script>

<style lang="scss" scoped>
.copy-handle {
    @apply w-[64rpx] h-[8rpx] rounded-[8rpx] bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.copy-header {
    @apply flex items-center gap-[24rpx] px-[36rpx] pt-[18rpx] pb-[26rpx]
        border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.copy-close {
    @apply relative w-[56rpx] h-[56rpx] rounded-full bg-[#F1F4F8] flex items-center justify-center shrink-0;

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.copy-pop {
    @apply h-full flex flex-col;
}

.copy-scroll-wrap {
    @apply grow min-h-0 px-[36rpx] pt-[36rpx];
}

.copy-footer {
    @apply px-[36rpx] pt-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]
        border-[0] border-t-[2rpx] border-solid border-[#E7ECF2];
}

.copy-field {
    @apply mb-[36rpx];
}

.copy-label-row {
    @apply flex items-center justify-between mb-[14rpx];
}

.copy-label {
    @apply text-[26rpx] text-[#475971] font-semibold;
}

.copy-count {
    @apply text-[24rpx] text-[#647184] tabular-nums;
}

.copy-input-wrap {
    @apply min-h-[92rpx] rounded-[22rpx] bg-[#F9FBFD] border-[2rpx] border-solid border-[#DBE3EE] px-[24rpx] flex items-center;
}

.copy-input {
    @apply flex-1 min-w-0 h-[88rpx] bg-[transparent] text-[#26364E] text-[30rpx];
}

.copy-textarea-wrap {
    @apply rounded-[22rpx] bg-[#F9FBFD] border-[2rpx] border-solid border-[#DBE3EE] px-[24rpx] py-[22rpx];
}

.copy-textarea {
    @apply w-full h-[240rpx] bg-[transparent] text-[#26364E] text-[30rpx] leading-[1.6];
}

.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.copy-save {
    @apply h-[94rpx] rounded-[24rpx] bg-primary text-white text-[28rpx] font-semibold flex items-center justify-center active:opacity-80;
}
</style>
