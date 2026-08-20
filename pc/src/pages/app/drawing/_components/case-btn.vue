<template>
    <div class="fixed right-6 bottom-32 z-[8888]">
        <ElTooltip content="优秀案例" placement="top">
            <div
                class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-primary cursor-pointer hover:scale-110 transition-transform active:scale-95 border border-[#F1F5F9]"
                @click="emit('onClick')">
                <Icon name="el-icon-Opportunity" :size="24"></Icon>
            </div>
        </ElTooltip>
    </div>
</template>

<script setup lang="ts">
const emit = defineEmits<{
    (e: "onClick"): void;
}>();
</script>

<style scoped></style>
