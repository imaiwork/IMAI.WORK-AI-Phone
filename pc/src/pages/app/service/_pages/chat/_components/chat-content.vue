<template>
    <div
        class="w-full flex items-center gap-x-4 max-w-[50%] relative break-all"
        :class="{
            'flex-row-reverse ': type == 1,
        }">
        <div
            class="px-4 py-2 rounded-md"
            :class="{
                'flex-row-reverse bg-[#A9EA7A]': type == 1,
                'bg-[#2880FC] text-white': type == 2,
            }"
            v-html="formatMessage(message)"></div>
    </div>
</template>

<script setup lang="ts">
import useHandle from "@/pages/app/person_wechat/_hooks/useHandle";

const props = withDefaults(
    defineProps<{
        message: string;
        type: number;
    }>(),
    {
        message: "",
        type: null,
    }
);

const emit = defineEmits(["previewVideo", "downloadFile", "voiceToText"]);

const { emojis } = useHandle();

const { play, setUrl, pause, pauseAll, isPlaying } = useAudio();

const formatMessage = (message: string) => {
    const emojiRegex = /\[(.*?)\]/g;
    return message.replace(emojiRegex, (match) => {
        const emoji = emojis.find((emoji) => emoji.name === match);
        if (emoji) {
            return `<img src="${emoji.src}" class="w-[24px] h-[24px] inline-block" />`;
        }
        return match;
    });
};
</script>

<style scoped lang="scss">
.loader {
    border: 2px solid hsla(226, 90%, 55%, 0.1);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
