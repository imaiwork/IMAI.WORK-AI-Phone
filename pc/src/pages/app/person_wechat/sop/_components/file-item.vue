<template>
    <div
        class="text-xs p-2 text-[#8A8C99] hover:underline cursor-pointer bg-primary-light-9 rounded-md"
        @click="emit('edit')">
        <template v-if="file.text"> 【文本】 </template>
        <template v-if="file.image"> 【图片】 </template>
        <template v-if="file.video"> 【视频】 </template>
        <template v-if="file.link"> 【链接】 </template>
        <template v-if="file.mini_program"> 【小程序】 </template>
        <template v-if="file.file"> 【文件】 </template>
        点击查看详情
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    file: any;
}>();

const emit = defineEmits<{
    (e: "edit"): void;
}>();
</script>

<style scoped></style>
