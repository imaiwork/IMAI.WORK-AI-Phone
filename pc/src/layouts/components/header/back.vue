<template>
    <div class="flex items-center gap-2 px-4">
        <ElTooltip content="返回上一页">
            <div
                class="cursor-pointer flex items-center p-2.5 rounded-full"
                :style="{ backgroundColor: getTheme.iconBgColor }"
                @click="handleBack">
                <Icon name="el-icon-Back" :size="20" :color="getTheme.iconColor"></Icon>
            </div>
        </ElTooltip>
        <div v-if="title" class="font-bold" :style="{ color: getTheme.titleColor }">
            {{ title }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { AppKeyEnum } from "@/enums/appEnums";

const router = useRouter();
const route = useRoute();
const title = ref<any>(route.meta.title);

interface Theme {
    iconColor: string;
    iconBgColor: string;
    titleColor: string;
}

const getTheme = computed<Theme>(() => {
    const key = route.meta.key;
    switch (key) {
        case AppKeyEnum.DIGITAL_HUMAN:
        case AppKeyEnum.DRAWING:
        case AppKeyEnum.REDBOOK:
        case AppKeyEnum.SPH:
        case AppKeyEnum.MATRIX:
            return {
                iconColor: "#ffffff",
                iconBgColor: "rgba(255,255,255,0.1)",
                titleColor: "#ffffff",
            };
        default:
            return {
                iconColor: "#000000",
                iconBgColor: "#0000000d",
                titleColor: "#000000",
            };
    }
});

const handleBack = () => {
    router.back();
};

watch(
    () => route.meta.title,
    (newVal) => {
        if (newVal) {
            title.value = newVal;
        }
    },
    {
        deep: true,
    }
);
</script>

<style scoped></style>
