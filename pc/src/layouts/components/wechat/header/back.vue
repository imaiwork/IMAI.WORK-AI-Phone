<template>
    <div class="flex items-center gap-2">
        <div @click="handleBack" class="cursor-pointer flex items-center p-2">
            <Icon name="el-icon-ArrowLeftBold" :size="16" color="#ffffff"></Icon>
            <div class="text-white">返回</div>
        </div>
    </div>
</template>

<script setup lang="ts">
const router = useRouter();

const handleBack = () => {
    router.push("/staff");
};
</script>

<style scoped></style>
