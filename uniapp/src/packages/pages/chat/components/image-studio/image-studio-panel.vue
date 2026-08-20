<template>
    <view class="studio h-full flex flex-col min-h-0">
        <!-- 消息流：自绘，不走 chat-scroll-view / chat-record-item -->
        <view class="flex-1 min-h-0 h-0 relative">
            <scroll-view class="h-full w-full" scroll-y :scroll-top="scrollTop" :scroll-with-animation="false">
                <view class="studio-content content-box px-[24rpx] pt-[16rpx] pb-[48rpx]">
                    <view v-if="!contentList.length" class="studio-empty">
                        <text class="studio-empty__title">图像创作</text>
                        <text class="studio-empty__desc"> 输入提示词开始生成，可开启提示词优化获得更好效果 </text>
                    </view>

                    <view
                        v-for="(item, index) in contentList"
                        :key="`img-${item.id ?? 'n'}-${index}`"
                        class="mb-[24rpx]">
                        <view v-if="item.type === 1" class="flex justify-end">
                            <view class="user-bubble">
                                <view v-if="item.refImages?.length" class="user-bubble__refs">
                                    <image
                                        v-for="(url, refIndex) in item.refImages"
                                        :key="refIndex"
                                        :src="url"
                                        class="user-bubble__ref-img"
                                        mode="aspectFill"
                                        @click.stop="previewRefImages(item.refImages, refIndex)" />
                                </view>
                                <text class="user-bubble__text">{{ item.message }}</text>
                            </view>
                        </view>

                        <!-- 优化中：对齐 HTML thinking 三点 -->
                        <view v-else-if="item.workbench?.kind === 'prompt-optimizing'" class="ai-wrap">
                            <view class="opt-thinking">
                                <view class="thinking-dots">
                                    <view class="thinking-dot"></view>
                                    <view class="thinking-dot"></view>
                                    <view class="thinking-dot"></view>
                                </view>
                                <text class="opt-thinking__text">正在优化提示词…</text>
                            </view>
                        </view>

                        <view v-else-if="item.workbench?.kind === 'prompt-optimize'" class="ai-wrap">
                            <image-optimize-card
                                :text="item.workbench.text"
                                :regenerating="!!item.workbench.regenerating"
                                :busy="sendDisabled"
                                @update:text="(v) => emit('optimize-update', index, v)"
                                @regen="emit('optimize-regen', index)"
                                @confirm="(t) => emit('optimize-confirm', index, t)"
                                @cancel="emit('optimize-cancel', index)" />
                        </view>

                        <view v-else-if="item.type === 2" class="ai-wrap">
                            <image-result-card
                                :status="resolveStatus(item)"
                                :urls="item.workbench?.urls || []"
                                :title="item.workbench?.title || ''"
                                :error="item.error || ''"
                                :count="item.workbench?.count || imageCount"
                                :model-name="item.workbench?.modelName || currentModelName"
                                :ratio="item.workbench?.ratio || imageRatio"
                                :size-label="item.workbench?.sizeLabel || sizeLabel"
                                :aspect-ratio="item.workbench?.aspectRatio || aspectRatio"
                                @regenerate="emit('regenerate', index)"
                                @save="onSave" />
                        </view>
                    </view>
                    <view class="h-[24rpx]"></view>
                </view>
            </scroll-view>
            <!-- 键盘弹起时盖透明层，点空白可靠收起（scroll-view 空区域 tap 不可靠） -->
            <view v-show="keyboardOpen" class="absolute inset-0 z-30" @tap.stop="dismissKeyboard"></view>
        </view>

        <!-- 底栏：工具栏/输入同层同宽，左右 gutter 统一 24rpx（对齐 HTML px-3） -->
        <view class="studio-bottom" :class="{ 'studio-bottom--tabbar': tabbarVisible }">
            <view class="studio-gutter">
                <scroll-view scroll-x class="w-full" :class="{ 'toolbar-collapsed': !showToolbar }">
                    <view class="tb-row" :class="{ 'pointer-events-none': !showToolbar }">
                        <view
                            class="tb-pill tb-pill--active"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('exit'))">
                            <u-icon name="photo" :size="24" color="#2563EB"></u-icon>
                            <text>图像生成</text>
                            <u-icon name="close" :size="20" color="#2563EB"></u-icon>
                        </view>
                        <view
                            class="tb-pill"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('open-model-sheet'))">
                            <view class="tb-avatar">
                                <text class="text-white text-[18rpx] font-bold">{{ modelInitial }}</text>
                            </view>
                            <text class="max-w-[160rpx] truncate">{{ currentModelName }}</text>
                            <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                        </view>
                        <view
                            class="tb-pill"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('open-size-sheet'))">
                            <u-icon name="grid" :size="22" color="#6B7280"></u-icon>
                            <text>{{ sizeLabel }}</text>
                            <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                        </view>
                        <view
                            class="tb-pill"
                            :class="{ 'tb-pill--active': imageOptimize }"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('update:imageOptimize', !imageOptimize))">
                            <view class="tb-switch" :class="{ 'tb-switch--on': imageOptimize }">
                                <view class="tb-knob"></view>
                            </view>
                            <text>提示词优化</text>
                        </view>
                        <view class="tb-pill" hover-class="opacity-70" :hover-stay-time="80" @click="guardToolbarAction(() => emit('open-case'))">
                            <u-icon name="star" :size="22" color="#F59E0B"></u-icon>
                            <text>优秀案例</text>
                        </view>
                        <view
                            class="tb-pill"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('show-history'))">
                            <u-icon name="/static/images/icons/clock.svg" :size="24"></u-icon>
                            <text>历史</text>
                        </view>
                        <view
                            class="tb-pill tb-pill--dashed"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => pickRefImage())">
                            <u-icon name="plus" :size="22" color="#9CA3AF"></u-icon>
                            <text>参考图上传</text>
                        </view>
                    </view>
                </scroll-view>

                <view class="ref-strip" :class="{ 'is-hidden': !refImages.length }">
                    <view v-for="(url, idx) in refImages" :key="idx" class="ref-thumb">
                        <image :src="url" mode="aspectFill" class="ref-thumb__img" />
                        <view class="ref-thumb__rm" @click.stop="emit('remove-ref', idx)">
                            <u-icon name="close" :size="18" color="#FFFFFF"></u-icon>
                        </view>
                    </view>
                    <view
                        class="ref-thumb ref-thumb--add"
                        :class="{ 'is-hidden': refImages.length >= refMax }"
                        hover-class="opacity-70"
                        :hover-stay-time="80"
                        @click="pickRefImage">
                        <u-icon name="plus" :size="28" color="#9CA3AF"></u-icon>
                    </view>
                </view>

                <!-- 输入区对齐 chat-scroll-view：外层灰底，竖向 padding 放内层 -->
                <view class="studio-input" @tap.stop>
                    <view class="flex items-center pl-2 py-[12rpx]">
                        <textarea
                            class="studio-ta flex-1 min-h-[40rpx] max-h-[140rpx] overflow-y-auto px-2 text-[28rpx] leading-[40rpx]"
                            v-model="localInput"
                            :disabled="sendDisabled"
                            :maxlength="-1"
                            :auto-height="taAutoHeight"
                            :style="taTextareaStyle"
                            :disable-default-padding="true"
                            :show-confirm-bar="false"
                            :adjust-position="false"
                            :placeholder="placeholder"
                            placeholder-style="color: rgba(0, 0, 0, 0.2); font-size: 26rpx; line-height: 40rpx;"
                            @focus="onTextareaFocus"
                            @blur="onTextareaBlur" />
                        <view class="flex-shrink-0 flex items-center gap-2.5 mr-2">
                            <view
                                class="send-btn"
                                :class="canSend ? 'send-btn--on' : 'send-btn--off'"
                                hover-class="opacity-80"
                                :hover-stay-time="80"
                                @click="onSend">
                                <u-icon
                                    :name="
                                        canSend
                                            ? '/static/images/icons/arrow_up_primary.svg'
                                            : '/static/images/icons/arrow_up.svg'
                                    "
                                    :size="36"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="studio-disclaimer">
                    <view class="studio-disclaimer__pill">
                        <u-icon name="/static/images/icons/tips.svg" :size="32"></u-icon>
                        <view class="studio-disclaimer__text">免责声明：内容由AI大模型生成，请仔细甄别。</view>
                    </view>
                </view>
            </view>
        </view>
        <!-- 键盘顶起占位：对齐对话页 chat-scroll-view -->
        <view class="flex-shrink-0" :style="{ height: spacerHeight + 'rpx' }"></view>
    </view>
</template>

<script setup lang="ts">
import useUpload from "@/hooks/useUpload";
import { saveImageToPhotosAlbum } from "@/utils/file";
import { IMAGE_REF_MAX } from "../../enums/workbench";
import { useStudioScrollBottom } from "../../composables/useStudioScrollBottom";
import { useStudioKeyboardSpacer, useStudioSendGuard } from "../../composables/useStudioKeyboardSpacer";
import { useStableTextareaAutoHeight } from "../../composables/useStableTextareaAutoHeight";
import ImageOptimizeCard from "../image-optimize-card.vue";
import ImageResultCard from "./image-result-card.vue";

const props = withDefaults(
    defineProps<{
        contentList?: any[];
        sendDisabled?: boolean;
        placeholder?: string;
        imageOptimize?: boolean;
        currentModelName?: string;
        sizeLabel?: string;
        imageRatio?: string;
        imageCount?: number;
        aspectRatio?: number;
        refImages?: string[];
        refMax?: number;
        /** 页面已展示底栏时不再叠加 safe-area，避免底间隙过大 */
        tabbarVisible?: boolean;
    }>(),
    {
        contentList: () => [],
        sendDisabled: false,
        placeholder: "输入图片生成的提示词，例如：浩瀚的银河中一艘宇宙飞船驶过",
        imageOptimize: false,
        currentModelName: "选择模型",
        sizeLabel: "9:16 · 高清2K",
        imageRatio: "9:16",
        imageCount: 1,
        aspectRatio: 9 / 16,
        refImages: () => [],
        refMax: IMAGE_REF_MAX,
        tabbarVisible: false,
    },
);

const emit = defineEmits<{
    (e: "exit"): void;
    (e: "content-post", text: string): void;
    (e: "show-history"): void;
    (e: "open-case"): void;
    (e: "open-model-sheet"): void;
    (e: "open-size-sheet"): void;
    (e: "update:imageOptimize", v: boolean): void;
    (e: "add-ref", url: string): void;
    (e: "remove-ref", index: number): void;
    (e: "optimize-update", index: number, text: string): void;
    (e: "optimize-regen", index: number): void;
    (e: "optimize-confirm", index: number, text: string): void;
    (e: "optimize-cancel", index: number): void;
    (e: "regenerate", index: number): void;
}>();

const localInput = ref("");
const {
    autoHeight: taAutoHeight,
    textareaStyle: taTextareaStyle,
    onTextareaFocus,
    onTextareaBlur,
} = useStableTextareaAutoHeight(localInput);
const { scrollTop, scrollToBottom } = useStudioScrollBottom();
const { keyboardOpen, spacerHeight, showToolbar, guardToolbarAction, dismissKeyboard, hideKeyboard } =
    useStudioKeyboardSpacer(() => props.tabbarVisible);
const { guardSend, markSent, isUploadMisfire } = useStudioSendGuard();

const modelInitial = computed(() => {
    const n = String(props.currentModelName || "模").trim();
    return n.slice(0, 1) || "模";
});

const canSend = computed(() => !props.sendDisabled && !!localInput.value.trim());

const resolveStatus = (item: any): "generating" | "done" | "error" => {
    if (item.error) return "error";
    if (item.loading || item.workbench?.status === "generating") return "generating";
    return "done";
};

const onSend = () => {
    guardSend(() => {
        if (!canSend.value) return;
        markSent();
        const text = localInput.value.trim();
        localInput.value = "";
        emit("content-post", text);
        scrollToBottom();
    });
};

const { uploadAndProcessFiles } = useUpload({
    count: 1,
    sourceType: ["album", "camera"],
    imageResolution: [4096, 4096],
    onSuccess: (materials) => {
        const url = String(materials?.[0]?.url || "").trim();
        if (url) emit("add-ref", url);
    },
});

const pickRefImage = async () => {
    if (isUploadMisfire()) return;
    if (props.refImages.length >= props.refMax) {
        uni.$u.toast(`最多添加 ${props.refMax} 张参考图`);
        return;
    }
    try {
        await uploadAndProcessFiles("image");
    } catch {
        /* useUpload 内部已 toast */
    }
};

const onSave = (url: string) => {
    saveImageToPhotosAlbum(url);
};

const previewRefImages = (urls: string[], index: number) => {
    uni.previewImage({ urls, current: urls[index] });
};

watch(
    () => props.contentList.length,
    () => scrollToBottom(),
);

watch(
    () => {
        const list = props.contentList;
        const last = list[list.length - 1];
        return [
            last?.loading,
            last?.workbench?.kind,
            last?.workbench?.status,
            last?.workbench?.urls?.length,
            last?.workbench?.text,
        ];
    },
    () => scrollToBottom(),
);

defineExpose({
    scrollToBottom,
    pickRefImage,
    setUserInput: (v = "") => {
        localInput.value = v;
    },
});
</script>

<style lang="scss" scoped>
@import "../../styles/studio-common.scss";
</style>
