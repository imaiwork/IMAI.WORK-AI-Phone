<template>
    <div class="chat-message flex gap-x-4">
        <!-- My message -->
        <div v-if="type == 1" class="w-full flex justify-end">
            <slot name="my"></slot>
        </div>
        <!-- Avatar -->
        <div class="flex-shrink-0 leading-[0] w-[32px] h-[32px]">
            <ElImage v-if="avatar" :src="avatar" lazy class="w-full h-full object-cover rounded-full" />
            <ElAvatar v-else :icon="UserFilled" :size="32" class="w-full h-full" />
        </div>
        <!-- His message -->
        <div v-if="type == 2" class="w-full flex flex-col">
            <slot name="his"></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UserFilled } from "@element-plus/icons-vue";
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
});
</script>

<style scoped></style>
