<template>
    <view
        v-if="step.status === 'done'"
        class="rounded-[18rpx] px-[22rpx] py-[16rpx] flex flex-col gap-[12rpx] mb-[20rpx]"
        style="
            background: #fff;
            border: 1.5rpx solid #e5e7eb;
            border-left: 5rpx solid #0065fb;
            box-shadow: 0 2px 12px rgba(0, 101, 251, 0.07);
        ">
        <view class="flex items-start gap-[14rpx]">
            <view v-if="!step.doneType || step.doneType === 'text'" class="flex flex-wrap items-center gap-[8rpx]">
                <text class="text-[23rpx]" style="color: #6b7280; line-height: 1.6">
                    {{ step.doneText || step.desc }}
                </text>
                <view v-if="step.meta" class="flex flex-wrap gap-[8rpx]">
                    <view
                        v-for="(m, mi) in step.meta"
                        :key="mi"
                        class="px-[12rpx] py-[3rpx] rounded-full"
                        style="background: #f3f4f6">
                        <text class="text-[18rpx]" style="color: #9ca3af">{{ m }}</text>
                    </view>
                </view>
            </view>

            <view v-else-if="step.doneType === 'tags'" class="flex flex-wrap gap-[12rpx]">
                <view
                    v-for="(t, ti) in step.doneTags"
                    :key="ti"
                    class="px-[20rpx] py-[8rpx] rounded-full"
                    :style="tagPillStyle(ti)">
                    <text class="text-[22rpx] font-semibold">{{ t }}</text>
                </view>
            </view>

            <view v-else-if="step.doneType === 'content'" class="flex-1">
                <text class="text-[24rpx]" style="color: #374151; line-height: 1.75">
                    {{ step.doneText }}
                </text>
                <view v-if="step.hashtags && step.hashtags.length" class="flex flex-wrap gap-[8rpx] mt-[12rpx]">
                    <text
                        v-for="(h, hi) in step.hashtags"
                        :key="hi"
                        class="text-[21rpx] font-medium"
                        style="color: #0065fb">
                        #{{ h }}
                    </text>
                </view>
            </view>

            <view v-else-if="step.doneType === 'kv'">
                <text class="text-[23rpx]" style="color: #6b7280; line-height: 1.6">
                    {{ step.doneText }}
                </text>
            </view>
        </view>

        <!-- 查看视频按钮：改为蓝色主题 -->
        <view
            v-if="step.id === 10 && step.videoUrl"
            class="flex items-center justify-center gap-[10rpx] h-[72rpx] rounded-[14rpx]"
            style="background: linear-gradient(135deg, #0065fb, #3d8bfc); box-shadow: 0 4px 12px rgba(0, 101, 251, 0.3)"
            @click="emit('watch-video', step)">
            <u-icon name="play-right" color="#fff" size="28"></u-icon>
            <text class="text-[24rpx] font-semibold text-white">查看视频</text>
        </view>
    </view>

    <view v-else-if="step.status === 'running'" class="mb-[20rpx]">
        <view
            class="rounded-[18rpx] px-[22rpx] py-[18rpx]"
            style="
                background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                border: 1.5rpx solid #bfdbfe;
                border-left: 5rpx solid #0065fb;
                box-shadow: 0 2px 16px rgba(0, 101, 251, 0.1);
            ">
            <view class="flex items-center gap-[14rpx]">
                <view class="flex gap-[5rpx] items-end flex-shrink-0">
                    <view
                        class="w-[6rpx] h-[6rpx] rounded-full typing-dot"
                        style="background: #0065fb; animation-delay: 0s"></view>
                    <view
                        class="w-[6rpx] h-[6rpx] rounded-full typing-dot"
                        style="background: #0065fb; animation-delay: 0.18s"></view>
                    <view
                        class="w-[6rpx] h-[6rpx] rounded-full typing-dot"
                        style="background: #0065fb; animation-delay: 0.36s"></view>
                </view>
                <text class="text-[23rpx] font-medium" style="color: #0050c8">{{ step.desc }}</text>
            </view>
        </view>
    </view>

    <view v-else-if="step.status === 'confirm'" class="mb-[20rpx]">
        <view
            class="rounded-[20rpx] overflow-hidden"
            style="background: #fff; border: 1.5rpx solid #bfdbfe; box-shadow: 0 4px 20px rgba(0, 101, 251, 0.08)">
            <view
                class="flex items-center gap-[10rpx] px-[24rpx] pt-[18rpx] pb-[12rpx]"
                style="border-bottom: 1rpx solid #f3f4f6">
                <view class="w-[8rpx] h-[8rpx] rounded-full" style="background: #0065fb"></view>
                <text class="text-[21rpx]" style="color: #0065fb">可直接编辑内容后确认</text>
            </view>

            <view class="px-[24rpx] pt-[16rpx] pb-[12rpx]">
                <textarea
                    v-model="step.confirmContent"
                    maxlength="1000"
                    :auto-height="true"
                    :show-confirm-bar="false"
                    placeholder="请输入内容..."
                    placeholder-style="color:#d1d5db;font-size:24rpx"
                    style="width: 100%; font-size: 24rpx; color: #374151; line-height: 1.75; min-height: 120rpx"
                    @focus="emit('focus')"
                    @blur="emit('blur')" />
                <view class="flex justify-end mt-[8rpx]">
                    <text class="text-[19rpx]" style="color: #d1d5db">
                        {{ (step.confirmContent || "").length }}/1000 字
                    </text>
                </view>
            </view>

            <view
                v-if="step.hashtags !== undefined"
                class="px-[24rpx] pb-[16rpx]"
                style="border-top: 1rpx solid #f3f4f6">
                <view class="flex items-center justify-between pt-[14rpx] pb-[10rpx]">
                    <view class="flex items-center gap-[8rpx]">
                        <view class="w-[6rpx] h-[6rpx] rounded-full" style="background: #0065fb"></view>
                        <text class="text-[21rpx] font-medium" style="color: #0065fb">话题标签</text>
                    </view>
                    <text
                        class="text-[19rpx]"
                        :style="step.hashtags.length >= MAX_HASHTAG_COUNT ? 'color:#ef4444' : 'color:#9ca3af'">
                        {{ step.hashtags.length }} / {{ MAX_HASHTAG_COUNT }} 个
                    </text>
                </view>

                <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                    <view
                        v-for="(h, hi) in step.hashtags"
                        :key="hi"
                        class="flex items-center gap-[6rpx] px-[16rpx] py-[7rpx] rounded-full"
                        style="background: #eff6ff; border: 1.5rpx solid #bfdbfe">
                        <view v-if="editingIndex === hi" class="flex items-center gap-[6rpx]">
                            <input
                                :value="editingValue"
                                :focus="editingIndex === hi"
                                :maxlength="MAX_HASHTAG_LENGTH"
                                placeholder="标签名"
                                placeholder-style="color:#d1d5db"
                                style="font-size: 22rpx; color: #0065fb; min-width: 80rpx; max-width: 200rpx"
                                @input="onEditInput"
                                @blur="onEditConfirm(step, hi)"
                                @confirm="onEditConfirm(step, hi)" />
                        </view>
                        <view v-else class="flex items-center gap-[6rpx]" @click="onEditStart(hi, h)">
                            <text class="text-[22rpx] font-medium" style="color: #0065fb">#{{ h }}</text>
                        </view>
                        <view
                            class="w-[28rpx] h-[28rpx] rounded-full flex items-center justify-center ml-[2rpx]"
                            style="background: #bfdbfe"
                            @click.stop="onRemoveTag(step, hi)">
                            <text style="color: #0065fb; font-size: 20rpx; line-height: 1; font-weight: bold">×</text>
                        </view>
                    </view>

                    <view
                        v-if="!isAdding && step.hashtags.length < MAX_HASHTAG_COUNT"
                        class="flex items-center gap-[6rpx] px-[16rpx] py-[7rpx] rounded-full"
                        style="background: #f9fafb; border: 1.5rpx dashed #d1d5db"
                        @click="onAddStart">
                        <text style="color: #9ca3af; font-size: 26rpx; line-height: 1">+</text>
                        <text class="text-[21rpx]" style="color: #9ca3af">添加标签</text>
                    </view>

                    <view
                        v-else-if="isAdding"
                        class="flex items-center gap-[6rpx] px-[16rpx] py-[7rpx] rounded-full"
                        style="background: #eff6ff; border: 1.5rpx solid #0065fb">
                        <text style="color: #0065fb; font-size: 22rpx">#</text>
                        <input
                            :value="addingValue"
                            :focus="isAdding"
                            :maxlength="MAX_HASHTAG_LENGTH"
                            placeholder="输入标签"
                            placeholder-style="color:#d1d5db;font-size:22rpx"
                            style="font-size: 22rpx; color: #0065fb; min-width: 80rpx; max-width: 200rpx"
                            @input="onAddInput"
                            @blur="onAddConfirm(step)"
                            @confirm="onAddConfirm(step)" />
                    </view>
                </view>

                <text class="text-[19rpx]" style="color: #d1d5db">
                    点击标签可编辑，点击 × 可删除，最多 {{ MAX_HASHTAG_COUNT }} 个标签
                </text>
            </view>

            <view class="flex px-[20rpx] pb-[20rpx] pt-[16rpx] gap-[16rpx]">
                <view
                    v-if="step.rejectLabel"
                    class="flex-1 h-[80rpx] flex items-center justify-center gap-[8rpx] rounded-[16rpx]"
                    style="background: #f3f4f6; border: 1.5rpx solid #e5e7eb"
                    @click="emit('reject', step)">
                    <u-icon name="reload" color="#6b7280" size="24"></u-icon>
                    <text class="text-[24rpx] font-medium" style="color: #6b7280">
                        {{ step.rejectLabel }}
                    </text>
                </view>
                <view
                    class="flex-1 h-[80rpx] flex items-center justify-center rounded-[16rpx]"
                    style="
                        background: linear-gradient(135deg, #0065fb, #3d8bfc);
                        box-shadow: 0 4px 16px rgba(0, 101, 251, 0.3);
                    "
                    @click="emit('confirm', step)">
                    <text class="text-[24rpx] font-bold text-white">
                        {{ step.confirmLabel || "确认" }}
                    </text>
                </view>
            </view>
        </view>
    </view>

    <view v-else-if="step.status === 'pending'" class="mb-[20rpx]">
        <text class="text-[22rpx]" style="color: #d1d5db">等待执行...</text>
    </view>
</template>

<script setup lang="ts">
type StepStatus = "done" | "running" | "confirm" | "pending";
type DoneType = "text" | "tags" | "content" | "kv";

interface Step {
    id: number;
    title: string;
    desc: string;
    status: StepStatus;
    tag?: string;
    doneType?: DoneType;
    doneText?: string;
    meta?: string[];
    doneTags?: string[];
    hashtags?: string[];
    needConfirm?: boolean;
    confirmContent?: string;
    confirmLabel?: string;
    rejectLabel?: string;
    videoUrl?: string;
}

defineProps<{ step: Step }>();

const emit = defineEmits<{
    (e: "confirm", step: Step): void;
    (e: "reject", step: Step): void;
    (e: "watch-video", step: Step): void;
    (e: "focus"): void;
    (e: "blur"): void;
}>();

const MAX_HASHTAG_COUNT = 10;
const MAX_HASHTAG_LENGTH = 50;

const editingIndex = ref<number | null>(null);
const editingValue = ref("");

const onEditStart = (index: number, value: string) => {
    editingIndex.value = index;
    editingValue.value = value.startsWith("#") ? value.slice(1) : value;
};

const onEditInput = (e: any) => {
    editingValue.value = (e.detail.value as string).slice(0, MAX_HASHTAG_LENGTH);
};

const onEditConfirm = (step: Step, index: number) => {
    const val = editingValue.value.trim().replace(/^#+/, "").slice(0, MAX_HASHTAG_LENGTH);
    if (val && step.hashtags) {
        step.hashtags[index] = val;
    }
    editingIndex.value = null;
    editingValue.value = "";
};

const onRemoveTag = (step: Step, index: number) => {
    if (step.hashtags) {
        step.hashtags.splice(index, 1);
    }
};

const isAdding = ref(false);
const addingValue = ref("");

const onAddStart = () => {
    isAdding.value = true;
    addingValue.value = "";
};

const onAddInput = (e: any) => {
    addingValue.value = (e.detail.value as string).slice(0, MAX_HASHTAG_LENGTH);
};

const onAddConfirm = (step: Step) => {
    const val = addingValue.value.trim().replace(/^#+/, "").slice(0, MAX_HASHTAG_LENGTH);
    if (val && step.hashtags && step.hashtags.length < MAX_HASHTAG_COUNT) {
        step.hashtags.push(val);
    }
    isAdding.value = false;
    addingValue.value = "";
};

const tagPillColors = [
    { bg: "#eff6ff", color: "#0065fb", border: "#bfdbfe" },
    { bg: "#FFF7ED", color: "#F59E0B", border: "#FED7AA" },
    { bg: "#F0FDF4", color: "#10B981", border: "#D1FAE5" },
    { bg: "#FDF4FF", color: "#A855F7", border: "#F3E8FF" },
    { bg: "#ecfeff", color: "#06B6D4", border: "#a5f3fc" },
];

const tagPillStyle = (index: number) => {
    const c = tagPillColors[index % tagPillColors.length];
    return `background:${c.bg};color:${c.color};border:1.5rpx solid ${c.border}`;
};
</script>

<style scoped>
.typing-dot {
    animation: typingBounce 0.9s ease-in-out infinite;
}
@keyframes typingBounce {
    0%,
    80%,
    100% {
        transform: translateY(0);
        opacity: 0.3;
    }
    40% {
        transform: translateY(-5rpx);
        opacity: 1;
    }
}
</style>
