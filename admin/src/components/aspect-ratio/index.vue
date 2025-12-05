<template>
    <div style="position: relative" :style="ratioStr">
        <div style="position: absolute; inset: 0px; user-select: none">
            <img
                v-if="type === 'image'"
                :src="src"
                :alt="alt || src"
                style="object-fit: cover; width: 100%; height: 100%" />
            <video
                v-else
                :src="src"
                controls
                style="object-fit: contain; width: 100%; height: 100%" />
        </div>
    </div>
</template>

<script lang="ts" setup>
const props = defineProps({
    ratio: {
        type: Array as unknown as () => [number, number],
        default: () => [1, 1],
    },
    alt: {
        type: String,
        default: "",
    },
    type: {
        type: String as () => "image" | "video",
        default: "image",
    },
    src: {
        type: String,
        default: "",
    },
});

const ratioStr = computed(() => {
    const [width, paddingBottom] = props.ratio;
    return `width: 100%;padding-bottom: ${(paddingBottom / width) * 100}%;`;
});
</script>

<style lang="scss" scoped></style>
