<template>
    <div class="w-[60px] h-[60px]">
        <div
            v-if="isVideo"
            class="relative w-full h-full overflow-hidden cursor-pointer"
            role="button"
            aria-label="播放视频素材"
            @click="emit('play', src)">
            <video :src="src" class="w-full h-full object-cover" muted preload="metadata" />
            <div class="absolute inset-0 flex items-center justify-center bg-black/35">
                <Icon name="el-icon-VideoPlay" :size="22" color="#fff" />
            </div>
        </div>
        <image-contain
            v-else
            :src="src"
            width="60"
            height="60"
            fit="cover"
            :preview-src-list="[src]"
            preview-teleported />
    </div>
</template>

<script lang="ts" setup>
const props = defineProps<{
    src: string
    type?: string
}>()

const emit = defineEmits<{
    play: [url: string]
}>()

const isVideo = computed(() => props.type === 'video')
</script>
