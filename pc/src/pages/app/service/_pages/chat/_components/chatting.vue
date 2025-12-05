<template>
    <div class="h-full">
        <template v-if="messageList.length > 0">
            <DynamicScroller
                class="h-full py-4 dynamic-scroller"
                ref="scrollbarRef"
                :items="messageList"
                :min-item-size="100"
                @scroll="scroll">
                <template #default="{ item, index, active }">
                    <DynamicScrollerItem :item="item" :active="active" :size-dependencies="[item.message, item.file]">
                        <div class="px-4 py-2">
                            <div v-if="item.type == 2">
                                <ChatMsgItem
                                    :type="2"
                                    :avatar="item.avatar"
                                    :wechat-id="item.wechatId"
                                    :is-room="item.is_room">
                                    <template #his>
                                        <ChatContent :type="2" :message="item.Content" />
                                    </template>
                                </ChatMsgItem>
                            </div>
                            <div v-if="item.type == 1">
                                <ChatMsgItem :type="1" :avatar="item.avatar">
                                    <template #my>
                                        <ChatContent :type="1" :message="item.Content" />
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
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import ChatMsgItem from "./chat-msg-item.vue";
import ChatContent from "./chat-content.vue";
import useMessage from "../../../_hooks/useMessage";

const { messageList, getMessageList } = useMessage();

//滚动条ref
const scrollbarRef = ref<any>(null);

//滚动到底部
const scrollToBottom = async () => {
    await nextTick();
    scrollbarRef.value?.scrollToBottom();
};

const scroll = () => {};

getMessageList().then(async () => {
    await nextTick();
    scrollToBottom();
});
</script>

<style scoped></style>
