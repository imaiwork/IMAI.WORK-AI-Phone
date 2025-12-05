<template>
    <div class="h-full">
        <template v-if="historyMsgList.length > 0">
            <DynamicScroller
                class="h-full pb-4 dynamic-scroller"
                ref="scrollbarRef"
                :items="historyMsgList"
                :min-item-size="100"
                @scroll="scroll">
                <template #default="{ item, index, active }">
                    <DynamicScrollerItem :item="item" :active="active" :size-dependencies="[item.message, item.file]">
                        <div
                            class="px-4"
                            :class="{
                                'min-h-[100px]': [
                                    ContentTypeEnum.File,
                                    ContentTypeEnum.Picture,
                                    ContentTypeEnum.Video,
                                ].includes(item.contentType),
                            }">
                            <div class="text-center py-4" v-if="[1, 2].includes(item.type)">
                                <span class="text-xs text-center text-white bg-[#dadada] rounded px-[10px] py-[5px]">
                                    {{ dayjs(item.createTime).format("YYYY-MM-DD HH:mm:ss") }}
                                </span>
                            </div>
                            <div v-if="item.type == 2">
                                <ChatMsgItem
                                    :type="2"
                                    :avatar="item.avatar"
                                    :wechat-id="item.wechatId"
                                    :is-room="item.is_room">
                                    <template #his>
                                        <ChatContent
                                            :type="2"
                                            :message="item.message"
                                            :show-stt="item.showStt"
                                            :stt-message="item.sttMessage"
                                            :stt-loading="item.sttLoading"
                                            :message-type="item.contentType"
                                            :file="item.file"
                                            @previewVideo="previewVideo(item)"
                                            @downloadFile="downloadFile(item)"
                                            @voiceToText="voiceToText(item)" />
                                    </template>
                                </ChatMsgItem>
                            </div>
                            <div v-if="item.type == 3" class="flex justify-center py-4">
                                <div class="text-xs text-gray-500" v-html="item.message"></div>
                            </div>
                            <div v-if="item.type == 1">
                                <ChatMsgItem :type="1" :avatar="item.avatar">
                                    <template #my>
                                        <ChatContent
                                            :type="1"
                                            :loading="item.loading"
                                            :is-active="item.is_active"
                                            :message="item.message"
                                            :stt-message="item.sttMessage"
                                            :show-stt="item.showStt"
                                            :stt-loading="item.sttLoading"
                                            :message-type="item.contentType"
                                            :file="item.file"
                                            @previewVideo="previewVideo(item)"
                                            @downloadFile="downloadFile(item)"
                                            @voiceToText="voiceToText(item)" />
                                    </template>
                                </ChatMsgItem>
                            </div>
                        </div>
                    </DynamicScrollerItem>
                </template>
            </DynamicScroller>
        </template>
        <div v-else class="flex h-full items-center justify-center">
            <ElEmpty description="暂无消息" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElScrollbar, dayjs } from "element-plus";
import { useElementSize } from "@vueuse/core";
import ChatMsgItem from "./chat-msg-item.vue";
import ChatContent from "./chat-content.vue";
import useHandle from "../../_hooks/useHandle";
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import { ContentTypeEnum } from "../../_enums";

const props = withDefaults(
    defineProps<{
        sendDisabled?: boolean;
    }>(),
    {
        sendDisabled: false,
    }
);

const emit = defineEmits(["top"]);

// 使用微信消息处理
const { previewVideo, historyMsgList, disabledScroll, downloadFile, voiceToText } = useHandle();

const previousScrollTop = ref(0);

//滚动条ref
const scrollbarRef = ref<any>(null);
//对话框ref
const innerRef = ref<HTMLDivElement>(null);
//滚动到底部
const scrollToBottom = async () => {
    if (disabledScroll.value) return;
    await nextTick();
    scrollbarRef.value?.scrollToBottom();
    // 这里是解决虚拟列表滚动到底部时，高度计算不准确的问题，例如渲染了图片、视频等之类的
    setTimeout(() => {
        scrollbarRef.value?.scrollToBottom();
    }, 200);
};

// 滚动到具体子项
const scrollToItem = (index: any) => {
    scrollbarRef.value?.scrollToItem(index);
};

//对话框滚动
const scroll = (value) => {
    const currentScrollTop = value.target.scrollTop;
    if (currentScrollTop < previousScrollTop.value) {
        disabledScroll.value = true;
    } else if (currentScrollTop > previousScrollTop.value) {
        disabledScroll.value = false;
    }
    previousScrollTop.value = currentScrollTop;
    refresh(value.target);
};

//滚动至顶部加载
const refresh = ({ scrollTop }) => {
    if (scrollTop == 0) {
        emit("top");
    }
};

const initScroll = () => {
    disabledScroll.value = false;
    previousScrollTop.value = 0;
};

const { height } = useElementSize(innerRef);
watch(height, (value) => {
    if (props.sendDisabled) {
        scrollToBottom();
    }
});

defineExpose({ scrollToBottom, scrollToItem, initScroll });
</script>
<style lang="scss" scoped>
a {
    @apply text-primary;
}
</style>
