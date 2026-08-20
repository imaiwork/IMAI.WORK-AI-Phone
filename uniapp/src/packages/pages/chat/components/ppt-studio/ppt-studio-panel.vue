<template>
    <view class="studio h-full flex flex-col min-h-0">
        <view class="flex-1 min-h-0 h-0 relative">
            <scroll-view class="h-full w-full" scroll-y :scroll-top="scrollTop" :scroll-with-animation="false">
                <view class="studio-content content-box px-[24rpx] pt-[16rpx] pb-[48rpx]">
                    <view v-if="!hasRenderableMessages" class="studio-empty">
                        <text class="studio-empty__title">PPT 生成</text>
                        <text class="studio-empty__desc"> 输入演讲主题开始生成，可开启智能追问获得更贴合的大纲 </text>
                    </view>

                    <view
                        v-for="(item, index) in contentList"
                        :key="`ppt-${item.id ?? 'n'}-${index}`"
                        class="mb-[24rpx]">
                        <view v-if="item.type === 1" class="flex justify-end">
                            <view class="user-bubble">
                                <text class="user-bubble__text">{{ item.message }}</text>
                            </view>
                        </view>

                        <view v-else-if="item.workbench?.kind === 'ppt-thinking'" class="ai-wrap">
                            <view class="opt-thinking">
                                <view class="thinking-dots">
                                    <view class="thinking-dot"></view>
                                    <view class="thinking-dot"></view>
                                    <view class="thinking-dot"></view>
                                </view>
                                <text class="opt-thinking__text">
                                    {{ item.workbench.text || "AI 正在思考…" }}
                                </text>
                            </view>
                        </view>

                        <view v-else-if="item.workbench?.kind === 'ppt-followup'" class="ai-wrap">
                            <ppt-followup-card
                                :description="item.workbench.description"
                                :ppt-type="item.workbench.pptType"
                                :fields="item.workbench.fields || []"
                                @confirm="(p) => emit('followup-confirm', index, p)"
                                @cancel="emit('followup-cancel', index)" />
                        </view>

                        <view v-else-if="item.error && item.workbench?.kind !== 'ppt'" class="ai-wrap">
                            <view class="err-bubble">
                                <text class="err-bubble__text">{{ item.error }}</text>
                            </view>
                        </view>

                        <view v-else-if="item.workbench?.kind === 'ppt'" class="ai-wrap">
                            <ppt-result-card
                                :topic="item.workbench.topic || ''"
                                :slides="item.workbench.slides || []"
                                :page-count="item.workbench.pageCount || 0"
                                :busy="!!item.loading || item.workbench.status === 'generating'"
                                :error="item.error || ''"
                                @regenerate="emit('regenerate', index)"
                                @regenerate-slide="(i) => emit('regenerate-slide', index, i)" />
                        </view>
                    </view>
                    <view class="h-[24rpx]"></view>
                </view>
            </scroll-view>
            <view v-show="keyboardOpen" class="absolute inset-0 z-30" @tap.stop="dismissKeyboard"></view>
        </view>

        <view class="studio-bottom" :class="{ 'studio-bottom--tabbar': tabbarVisible }">
            <view class="studio-gutter">
                <scroll-view scroll-x class="w-full" :class="{ 'toolbar-collapsed': !showToolbar }">
                    <view class="tb-row" :class="{ 'pointer-events-none': !showToolbar }">
                        <view
                            class="tb-pill tb-pill--active"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('exit'))">
                            <text>PPT生成</text>
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
                            @click="guardToolbarAction(() => emit('open-pages-sheet'))">
                            <u-icon name="file-text" :size="22" color="#6B7280"></u-icon>
                            <text>{{ pageRange }}</text>
                            <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                        </view>
                        <view
                            class="tb-pill"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => pickScene())">
                            <text class="max-w-[160rpx] truncate">{{ scene }}</text>
                            <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                        </view>
                        <view
                            class="tb-pill"
                            :class="{ 'tb-pill--active': followupOn }"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('update:followupOn', !followupOn))">
                            <view class="tb-switch" :class="{ 'tb-switch--on': followupOn }">
                                <view class="tb-knob"></view>
                            </view>
                            <text>智能追问</text>
                        </view>
                        <view
                            class="tb-pill"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('show-history'))">
                            <u-icon name="/static/images/icons/clock.svg" :size="24"></u-icon>
                            <text>历史</text>
                        </view>
                    </view>
                </scroll-view>

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
import { useStudioScrollBottom } from "../../composables/useStudioScrollBottom";
import { useStudioKeyboardSpacer, useStudioSendGuard } from "../../composables/useStudioKeyboardSpacer";
import { useStableTextareaAutoHeight } from "../../composables/useStableTextareaAutoHeight";
import PptFollowupCard from "./ppt-followup-card.vue";
import PptResultCard from "./ppt-result-card.vue";

const props = withDefaults(
    defineProps<{
        contentList?: any[];
        sendDisabled?: boolean;
        placeholder?: string;
        pageRange?: string;
        scene?: string;
        followupOn?: boolean;
        currentModelName?: string;
        /** 页面已展示底栏时不再叠加 safe-area，避免底间隙过大 */
        tabbarVisible?: boolean;
    }>(),
    {
        contentList: () => [],
        sendDisabled: false,
        placeholder: "输入演讲主题，例如：2026年Q1业务总结",
        pageRange: "15-25页",
        scene: "通用",
        followupOn: false,
        currentModelName: "image-2",
        tabbarVisible: false,
    },
);

const emit = defineEmits<{
    (e: "exit"): void;
    (e: "content-post", text: string): void;
    (e: "show-history"): void;
    (e: "open-model-sheet"): void;
    (e: "open-pages-sheet"): void;
    (e: "open-scene-sheet"): void;
    (e: "update:pageRange", v: string): void;
    (e: "update:scene", v: string): void;
    (e: "update:followupOn", v: boolean): void;
    (
        e: "followup-confirm",
        index: number,
        payload: {
            answers: Record<string, any>;
            summary: Record<string, string>;
            pageCount?: number;
        },
    ): void;
    (e: "followup-cancel", index: number): void;
    (e: "regenerate", index: number): void;
    (e: "regenerate-slide", index: number, slideIndex: number): void;
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
const { guardSend } = useStudioSendGuard();

const modelInitial = computed(() => {
    const n = String(props.currentModelName || "模").trim();
    return n.slice(0, 1) || "模";
});

const canSend = computed(() => !props.sendDisabled && !!localInput.value.trim());

/** 是否存在可渲染的消息：过滤掉异常残留的空消息，避免整块消息区空白无提示 */
const hasRenderableMessages = computed(() =>
    (props.contentList || []).some((item: any) => {
        if (item?.type === 1) return true;
        if (item?.error) return true;
        const kind = item?.workbench?.kind;
        return kind === "ppt" || kind === "ppt-thinking" || kind === "ppt-followup";
    }),
);

const onSend = () => {
    guardSend(() => {
        if (!canSend.value) return;
        const text = localInput.value.trim();
        localInput.value = "";
        emit("content-post", text);
        scrollToBottom();
    });
};

const pickScene = () => {
    emit("open-scene-sheet");
};

watch(
    () => props.contentList.length,
    (len) => {
        if (len === 0) {
            // 清空消息时回到顶部，避免空态被之前的滚动位置挤出可视区
            scrollTop.value = 0;
            return;
        }
        scrollToBottom();
    },
);

watch(
    () => {
        const list = props.contentList;
        const last = list[list.length - 1];
        return [
            last?.loading,
            last?.workbench?.kind,
            last?.workbench?.status,
            last?.workbench?.slides?.length,
            last?.workbench?.slides?.filter((s: any) => s?.url).length,
            last?.error,
        ];
    },
    () => scrollToBottom(),
);

defineExpose({
    scrollToBottom,
    setUserInput: (v = "") => {
        localInput.value = v;
    },
});
</script>

<style lang="scss" scoped>
@import "../../styles/studio-common.scss";

.studio-empty {
    @apply pt-[120rpx];
}

</style>
