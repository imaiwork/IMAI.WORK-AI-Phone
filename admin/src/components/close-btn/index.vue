<template>
    <div class="w-full h-full rounded-full flex items-center justify-center cursor-pointer" :class="themeClass">
        <Icon name="el-icon-Close" color="#8B9199" :size="iconSize"></Icon>
    </div>
</template>

<script setup lang="ts">
import { ThemeEnum } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        theme?: ThemeEnum;
        iconSize?: number;
    }>(),
    {
        iconSize: 16,
        theme: ThemeEnum.DARK,
    }
);

const themeClass = computed(() => {
    return props.theme === ThemeEnum.DARK
        ? "bg-[rgba(255,255,255,0.1)] hover:bg-[rgba(255,255,255,0.2)]"
        : "bg-[rgba(223,223,223,0.3)] hover:bg-[rgba(223,223,223,0.6)]";
});
</script>

<style scoped></style>
