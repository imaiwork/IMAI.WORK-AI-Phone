<template>
    <div class="chat-message flex gap-x-4">
        <!-- My message -->
        <div v-if="type == 1" class="w-full flex justify-end">
            <slot name="my"></slot>
        </div>
        <!-- Avatar -->
        <div class="flex-shrink-0 leading-[0] w-[32px] h-[32px]" :class="[isRoom ? 'mt-4' : 'mt-[2px]']">
            <ElImage v-if="avatar" :src="avatar" lazy class="w-full h-full object-cover rounded" />
            <ElAvatar v-else :icon="UserFilled" :size="32" shape="square" class="w-full h-full" />
        </div>
        <!-- His message -->
        <div v-if="type == 2" class="w-full flex flex-col">
            <div class="mb-1" v-if="isRoom">
                {{ wechatId }}
            </div>
            <slot name="his"></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UserFilled } from "@element-plus/icons-vue";
import { ContentTypeEnum } from "../../_enums";
import useHandle from "../../_hooks/useHandle";
import ChatContent from "./chat-content.vue";

const props = defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    avatar: {
        type: String,
        default: "",
    },
    message: {
        type: String,
        default: "",
    },
    type: {
        type: Number,
        default: null,
    },
    messageType: {
        type: [String, Number],
        default: "",
    },
    file: {
        type: Object,
        default: () => ({}),
    },
    duration: {
        type: Number,
        default: 0,
    },
    isRoom: {
        type: Boolean,
        default: false,
    },
    wechatId: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["previewVideo"]);

const { currentWechat } = useHandle();

const getWechatName = (wechatId: string) => {
    const { showNameList, members } = currentWechat.value;
    if (showNameList) {
        const data = showNameList.find((item: any) => item.UserName === wechatId);
        if (data) {
            return data.ShowName || data.UserName;
        }
    }
    return "";
};
</script>

<style scoped></style>
