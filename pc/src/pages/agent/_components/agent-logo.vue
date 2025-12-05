<template>
    <!-- Logo 容器，带有渐变色边框 -->
    <div
        class="w-[70px] h-[70px] rounded-full p-[1px] relative"
        style="background: linear-gradient(rgba(255, 188, 80, 1), rgba(255, 165, 119, 1), rgba(255, 127, 182, 1))">
        <!-- 上传组件 -->
        <upload
            class="w-full h-full rounded-full bg-white"
            :limit="1"
            :show-file-list="false"
            show-progress
            @success="handleFileSuccess">
            <div class="h-full w-full rounded-full relative">
                <!-- 上传按钮图标 -->
                <div class="w-5 h-5 rounded-full bg-white p-[1px] absolute bottom-0 right-1 z-10">
                    <Icon name="el-icon-CirclePlusFilled" :size="18"></Icon>
                </div>

                <!-- Logo 图片显示 -->
                <div v-if="logo" class="flex flex-col justify-center items-center h-full w-full p-1">
                    <img :src="logo" class="w-full h-full rounded-full" alt="Agent Logo" />
                </div>
                <!-- 默认占位符 -->
                <div v-else class="w-full h-full bg-[#333333] rounded-full flex justify-center items-center">
                    <Icon name="local-icon-user" color="#ffffff" size="30"></Icon>
                </div>
            </div>
        </upload>
    </div>
</template>

<script setup lang="ts">
// 使用 defineModel 来支持 v-model
const logo = defineModel<string>("modelValue");

/**
 * @description 文件上传成功后的回调函数
 * @param {any} result - 上传结果
 */
const handleFileSuccess = (result: any) => {
    const uri = result?.data?.uri;
    if (uri) {
        logo.value = uri;
    }
};
</script>

<style scoped lang="scss">
:deep(.upload) {
    .upload-wrap {
        @apply w-full h-full;
        .el-upload {
            @apply w-full h-full;
        }
    }
}
</style>
