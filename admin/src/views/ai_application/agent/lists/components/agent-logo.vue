<template>
    <div
        class="w-[70px] h-[70px] rounded-full p-[1px] relative"
        style="background: linear-gradient(rgba(255, 188, 80, 1), rgba(255, 165, 119, 1), rgba(255, 127, 182, 1))">
        <upload
            class="w-full h-full rounded-full bg-white"
            :limit="1"
            :show-file-list="false"
            show-progress
            @success="handleFileSuccess">
            <div class="h-full w-full rounded-full">
                <div class="w-5 h-5 rounded-full bg-white p-[1px] absolute bottom-0 right-1 z-[8888]">
                    <Icon name="el-icon-CirclePlusFilled" :size="18"></Icon>
                </div>
                <div class="flex flex-col justify-center items-center h-full w-full p-1" v-if="logo">
                    <img :src="logo" class="w-full h-full rounded-full" />
                </div>
                <div v-else class="w-full h-full bg-[#333333] rounded-full flex justify-center items-center">
                    <Icon name="el-icon-User" color="#ffffff" size="30"></Icon>
                </div>
            </div>
        </upload>
    </div>
</template>

<script setup lang="ts">
const logo = defineModel<string>("modelValue");

const handleFileSuccess = (result: any) => {
    const uri = result?.uri;
    if (uri) {
        logo.value = uri;
    }
};
</script>

<style scoped lang="scss">
:deep(.el-upload) {
    @apply w-full h-full;
}
</style>
