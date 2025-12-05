<template>
    <div
        class="h-[var(--nav-height)] pr-[18px]"
        :class="{
            'min-w-[375px] fixed top-0 left-0 right-0 z-[888]': isFixed,
            'ml-[var(--aside-width)]': isFixed && !hideSidebar,
            'shadow-lg flex items-center justify-between': !isFixed,
        }"
        :style="{ backgroundColor: getTheme.bgColor }">
        <Back v-if="isBack" />
        <div class="h-full flex justify-end">
            <User />
        </div>
    </div>
</template>

<script setup lang="ts">
import User from "./user.vue";
import Back from "./back.vue";
import { useAppStore } from "@/stores/app";
import { AppKeyEnum } from "@/enums/appEnums";

const route = useRoute();

const hideSidebar = computed(() => useAppStore().hideSidebar);

const props = defineProps({
    isBack: {
        type: Boolean,
        default: false,
    },
    isFixed: {
        type: Boolean,
        default: true,
    },
});

interface Theme {
    bgColor: string;
}

const getTheme = computed<Theme>(() => {
    const key = route.meta.key;
    switch (key) {
        case "home":
            return {
                bgColor: "#ffffff",
            };
        case AppKeyEnum.DIGITAL_HUMAN:
        case AppKeyEnum.DRAWING:
        case AppKeyEnum.REDBOOK:
        case AppKeyEnum.MATRIX:
        case AppKeyEnum.SPH:
            return {
                bgColor: "var(--app-bg-color-1)",
            };
        default:
            return {
                bgColor: "",
            };
    }
});
</script>

<style scoped></style>
