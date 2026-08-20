<template>
    <view
        v-if="step.status === 'done'"
        class="rounded-[18rpx] px-[22rpx] py-[16rpx] flex flex-col gap-[12rpx] mb-[20rpx] bg-white border border-solid border-l-[5rpx] border-l-primary border-[#e5e7eb]"
        style="box-shadow: 0 2px 12px rgba(0, 101, 251, 0.07)">
        <view class="flex items-start gap-[14rpx]">
            <view v-if="!step.doneType || step.doneType === 'text'" class="flex flex-wrap items-center gap-[8rpx]">
                <text class="text-[23rpx] text-[#6b7280] leading-[1.6]">
                    {{ step.doneText || step.desc }}
                </text>
                <view v-if="step.meta" class="flex flex-wrap gap-[8rpx]">
                    <view v-for="(m, mi) in step.meta" :key="mi" class="px-[12rpx] py-[3rpx] rounded-full bg-[#f3f4f6]">
                        <text class="text-[18rpx] text-[#9ca3af]">{{ m }}</text>
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
                <text class="text-xs text-[#374151] leading-[1.75]">
                    {{ step.doneText }}
                </text>
                <view v-if="step.hashtags && step.hashtags.length" class="flex flex-wrap gap-[8rpx] mt-[12rpx]">
                    <text v-for="(h, hi) in step.hashtags" :key="hi" class="text-[21rpx] font-medium text-primary">
                        {{ h }}
                    </text>
                </view>
            </view>

            <view v-else-if="step.doneType === 'kv'">
                <text class="text-[23rpx] text-[#6b7280] leading-[1.6]">
                    {{ step.doneText }}
                </text>
            </view>
        </view>

        <view
            v-if="step.id === 10 && step.videoUrl"
            class="flex items-center justify-center gap-[10rpx] h-[72rpx] rounded-[14rpx]"
            style="background: linear-gradient(135deg, #0065fb, #3d8bfc); box-shadow: 0 4px 12px rgba(0, 101, 251, 0.3)"
            @click="emit('watch-video', step)">
            <u-icon name="play-right" color="#fff" size="28"></u-icon>
            <text class="text-xs font-semibold text-white">查看视频</text>
        </view>
    </view>

    <view v-else-if="step.status === 'running'" class="mb-[20rpx]">
        <view
            class="rounded-[18rpx] px-[22rpx] py-[18rpx] border border-solid border-[#bfdbfe] border-l-[5rpx] border-l-primary"
            style="
                background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                box-shadow: 0 2px 16px rgba(0, 101, 251, 0.1);
            ">
            <view class="flex items-center gap-[14rpx]">
                <view class="flex gap-[5rpx] items-end flex-shrink-0">
                    <view class="w-[6rpx] h-[6rpx] rounded-full bg-primary"></view>
                    <view class="w-[6rpx] h-[6rpx] rounded-full bg-primary"></view>
                    <view class="w-[6rpx] h-[6rpx] rounded-full bg-primary"></view>
                </view>
                <text class="text-[23rpx] font-medium text-[#0050c8]">{{ step.desc }}</text>
            </view>
        </view>
    </view>

    <view v-else-if="step.status === 'confirm'" class="mb-[20rpx]">
        <view
            class="rounded-[20rpx] overflow-hidden border border-solid border-[#bfdbfe] bg-white"
            style="box-shadow: 0 4px 20px rgba(0, 101, 251, 0.08)">
            <view
                class="flex items-center justify-between gap-[10rpx] px-[24rpx] pt-[18rpx] pb-[12rpx]"
                style="border-bottom: 1rpx solid #f3f4f6">
                <view class="flex items-center gap-[10rpx]">
                    <view class="w-[8rpx] h-[8rpx] rounded-full bg-primary"></view>
                    <text class="text-[21rpx] text-primary">可直接编辑内容后确认</text>
                </view>
                <view
                    v-if="step.confirmContent && step.confirmContent.length > 0"
                    class="flex items-center gap-[4rpx] px-[14rpx] py-[5rpx] rounded-full bg-[#f3f4f6]"
                    hover-class="clear-btn-active"
                    :hover-stay-time="100"
                    @click="onClearContent">
                    <u-icon name="trash" color="#6b7280" size="20"></u-icon>
                    <text class="text-[20rpx] text-[#6b7280]">清空</text>
                </view>
            </view>

            <view class="px-[24rpx] pt-[16rpx] pb-[12rpx]">
                <textarea
                    v-model="step.confirmContent"
                    maxlength="1000"
                    :show-confirm-bar="false"
                    :hold-keyboard="true"
                    :adjust-position="false"
                    :cursor-spacing="120"
                    placeholder="请输入内容..."
                    placeholder-style="color:#d1d5db;font-size:24rpx"
                    style="width: 100%; font-size: 24rpx; color: #374151; line-height: 1.75; min-height: 120rpx"
                    @focus="onFocus"
                    @blur="onBlur" />
                <view class="flex justify-end mt-[8rpx]">
                    <text class="text-[19rpx] text-[#d1d5db]"> {{ (step.confirmContent || "").length }}/1000 字 </text>
                </view>
            </view>

            <view
                v-if="step.hashtags !== undefined"
                class="px-[24rpx] pb-[16rpx] border-[0] border-t-[1rpx] border-t-solid border-t-[#f3f4f6]">
                <view class="flex items-center justify-between pt-[14rpx] pb-[10rpx]">
                    <view class="flex items-center gap-[8rpx]">
                        <view class="w-[6rpx] h-[6rpx] rounded-full bg-primary"></view>
                        <text class="text-[21rpx] font-medium text-primary">话题标签</text>
                    </view>
                    <text
                        class="text-[19rpx]"
                        :class="step.hashtags.length >= MAX_HASHTAG_COUNT ? 'text-[#ef4444]' : 'text-[#9ca3af]'">
                        {{ step.hashtags.length }} / {{ MAX_HASHTAG_COUNT }} 个
                    </text>
                </view>

                <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                    <view
                        v-for="(h, hi) in step.hashtags"
                        :key="hi"
                        class="flex items-center gap-[6rpx] px-[16rpx] py-[7rpx] rounded-full bg-[#eff6ff] border border-solid border-[#bfdbfe]">
                        <view v-if="editingIndex === hi" class="flex items-center gap-[6rpx]">
                            <input
                                :value="editingValue"
                                :focus="editingIndex === hi"
                                :maxlength="MAX_HASHTAG_LENGTH"
                                :hold-keyboard="true"
                                :adjust-position="false"
                                :cursor-spacing="120"
                                placeholder="标签名"
                                placeholder-style="color:#d1d5db"
                                style="font-size: 22rpx; color: #0065fb; min-width: 80rpx; max-width: 200rpx"
                                @input="onEditInput"
                                @blur="onEditConfirm(step, hi)"
                                @confirm="onEditConfirm(step, hi)" />
                        </view>
                        <view v-else class="flex items-center gap-[6rpx]" @click="onEditStart(hi, h)">
                            <text class="text-[22rpx] font-medium" style="color: #0065fb">{{ h }}</text>
                        </view>
                        <view
                            class="w-[28rpx] h-[28rpx] rounded-full flex items-center justify-center ml-[2rpx] bg-[#bfdbfe]"
                            @click.stop="onRemoveTag(step, hi)">
                            <text class="text-[20rpx] font-bold text-primary">×</text>
                        </view>
                    </view>

                    <view
                        v-if="!isAdding && step.hashtags.length < MAX_HASHTAG_COUNT"
                        class="flex items-center gap-[6rpx] px-[16rpx] py-[7rpx] rounded-full bg-[#f9fafb] border border-dashed border-[#d1d5db]"
                        @click="onAddStart">
                        <text class="text-[26rpx] text-[#9ca3af]">+</text>
                        <text class="text-[21rpx] text-[#9ca3af]">添加标签</text>
                    </view>

                    <view
                        v-else-if="isAdding"
                        class="flex items-center gap-[6rpx] px-[16rpx] py-[7rpx] rounded-full bg-[#eff6ff] border border-solid border-primary">
                        <text class="text-[22rpx] text-primary">#</text>
                        <input
                            :value="addingValue"
                            :focus="isAdding"
                            :maxlength="MAX_HASHTAG_LENGTH"
                            :hold-keyboard="true"
                            :adjust-position="false"
                            :cursor-spacing="120"
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
                    class="flex-1 h-[80rpx] flex items-center justify-center gap-[8rpx] rounded-[16rpx] bg-[#f3f4f6] border border-solid border-[#e5e7eb]"
                    @click="emit('reject', step)">
                    <u-icon name="reload" color="#6b7280" size="24"></u-icon>
                    <text class="text-xs font-medium text-[#6b7280]">
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
                    <text class="text-xs font-bold text-white">
                        {{ step.confirmLabel || "确认" }}
                    </text>
                </view>
            </view>
        </view>
    </view>

    <view v-else-if="step.status === 'failed'" class="mb-[20rpx]">
        <view
            class="rounded-[18rpx] px-[22rpx] py-[18rpx] border border-solid border-[#FECACA] border-l-[5rpx] border-l-[#EF4444] bg-[#FEF2F2]"
            style="box-shadow: 0 2px 12px rgba(239, 68, 68, 0.08)">
            <text class="text-[23rpx] text-[#EF4444] leading-relaxed break-words">
                {{ failReason || step.desc || "任务失败" }}
            </text>
        </view>
    </view>

    <view v-else-if="step.status === 'pending'" class="mb-[20rpx]">
        <text class="text-[22rpx] text-[#d1d5db]">等待执行...</text>
    </view>
</template>

<script setup lang="ts">
type StepStatus = "done" | "running" | "confirm" | "pending" | "failed";
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

const props = withDefaults(
    defineProps<{
        step: Step;
        failReason?: string;
    }>(),
    {
        failReason: "",
    },
);

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

const onFocus = () => {
    emit("focus");
};

const onBlur = () => {
    emit("blur");
    uni.hideKeyboard?.();
};

const onClearContent = () => {
    if (!props.step.confirmContent) return;
    uni.showModal({
        title: "提示",
        content: "确定要清空当前文案吗？",
        confirmColor: "#0065fb",
        cancelColor: "#6b7280",
        success: (res) => {
            if (res.confirm) {
                props.step.confirmContent = "";
            }
        },
    });
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
.clear-btn-active {
    background: #e5e7eb !important;
}
</style>
