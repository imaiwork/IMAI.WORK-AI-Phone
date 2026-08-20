<template>
    <view class="studio h-full flex flex-col min-h-0">
        <!-- 消息流：自绘，不走 chat-scroll-view -->
        <view class="flex-1 min-h-0 h-0 relative">
            <scroll-view
                class="h-full w-full"
                scroll-y
                :scroll-top="scrollTop"
                :scroll-with-animation="false">
                <view class="studio-content content-box px-[24rpx] pt-[16rpx] pb-[48rpx]">
                    <view v-if="!contentList.length" class="studio-empty">
                        <text class="studio-empty__title">地图获客</text>
                        <text class="studio-empty__desc">
                            告诉我你想找什么商家，例如：帮我找北京东城区的咖啡店
                        </text>
                    </view>

                    <view
                        v-for="(item, index) in contentList"
                        :key="`map-${item.id ?? 'n'}-${index}`"
                        class="mb-[24rpx]">
                        <view v-if="item.type === 1" class="flex justify-end">
                            <view class="user-bubble">
                                <text class="user-bubble__text">{{ item.message }}</text>
                            </view>
                        </view>

                        <!-- 检索中：对齐 HTML thinking -->
                        <view
                            v-else-if="
                                !item.error &&
                                (item.workbench?.kind === 'map-searching' ||
                                    (item.loading && item.workbench?.kind !== 'map'))
                            "
                            class="ai-wrap">
                            <view class="opt-thinking">
                                <view class="thinking-dots">
                                    <view class="thinking-dot"></view>
                                    <view class="thinking-dot"></view>
                                    <view class="thinking-dot"></view>
                                </view>
                                <text class="opt-thinking__text">正在检索商家…</text>
                            </view>
                        </view>

                        <view v-else-if="item.error && !item.workbench?.cards?.length" class="ai-wrap">
                            <view class="err-bubble">
                                <text class="err-bubble__text">{{ item.error }}</text>
                            </view>
                        </view>

                        <view v-else-if="item.workbench?.kind === 'map'" class="ai-wrap">
                            <map-result-card
                                :cards="item.workbench.cards || []"
                                :query="item.workbench.query || ''"
                                :page-label="item.workbench.pageLabel || ''"
                                :error="item.error || ''" />
                        </view>
                    </view>
                    <view class="h-[24rpx]"></view>
                </view>
            </scroll-view>
            <view v-show="keyboardOpen" class="absolute inset-0 z-30" @tap.stop="dismissKeyboard"></view>
        </view>

        <!-- 底栏：退出 / 历史 / 继续获取 / 导出 + 输入 -->
        <view class="studio-bottom" :class="{ 'studio-bottom--tabbar': tabbarVisible }">
            <view class="studio-gutter">
                <scroll-view scroll-x class="w-full" :class="{ 'toolbar-collapsed': !showToolbar }">
                    <view class="tb-row" :class="{ 'pointer-events-none': !showToolbar }">
                        <view
                            class="tb-pill tb-pill--active"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('exit'))">
                            <u-icon name="map" :size="24" color="#2563EB"></u-icon>
                            <text>地图获客</text>
                            <u-icon name="close" :size="20" color="#2563EB"></u-icon>
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
                            class="tb-pill"
                            :class="{ 'is-hidden': !canLoadMore }"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('map-more'))">
                            <text>继续获取</text>
                        </view>
                        <view
                            class="tb-pill"
                            :class="{ 'is-hidden': !canExport }"
                            hover-class="opacity-70"
                            :hover-stay-time="80"
                            @click="guardToolbarAction(() => emit('map-export'))">
                            <text>导出 Excel</text>
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
import MapResultCard from "./map-result-card.vue";

const props = withDefaults(
    defineProps<{
        contentList?: any[];
        sendDisabled?: boolean;
        placeholder?: string;
        canLoadMore?: boolean;
        canExport?: boolean;
        /** 页面已展示底栏时不再叠加 safe-area，避免底间隙过大 */
        tabbarVisible?: boolean;
    }>(),
    {
        contentList: () => [],
        sendDisabled: false,
        placeholder: "告诉我你想找什么商家，例如：帮我找北京东城区的咖啡店",
        canLoadMore: false,
        canExport: false,
        tabbarVisible: false,
    },
);

const emit = defineEmits<{
    (e: "exit"): void;
    (e: "content-post", text: string): void;
    (e: "show-history"): void;
    (e: "map-more"): void;
    (e: "map-export"): void;
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

const canSend = computed(() => !props.sendDisabled && !!localInput.value.trim());

const onSend = () => {
    guardSend(() => {
        if (!canSend.value) return;
        const text = localInput.value.trim();
        localInput.value = "";
        emit("content-post", text);
        scrollToBottom();
    });
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
            last?.workbench?.cards?.length,
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
</style>
