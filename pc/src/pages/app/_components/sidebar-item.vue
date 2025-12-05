<template>
    <div
        class="sidebar-item"
        :class="[
            {
                active: isActive,
                'is-black': isBlack,
                'is-disabled': item.disabled,
            },
            isActive && getThemeClass.shadow,
            getThemeClass.hoverBgColor,
            getThemeClass.hoverShadow,
        ]"
        :style="{
            backgroundColor: isActive ? getTheme.sidebarBgColor : '',
        }"
        @click="handleSidebar(item.type)">
        <span
            class="flex items-center justify-center rounded p-1"
            :class="[isActive ? 'bg-primary' : getThemeClass.iconBgColor]">
            <Icon :name="`local-icon-${item.icon}`" :size="14" :color="getIconColor" />
        </span>
        <span class="text-sm" :style="{ color: item.disabled ? 'rgba(255,255,255,0.2)' : getTheme.textColor }">
            {{ item.name }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { AppKeyEnum } from "@/enums/appEnums";

interface SidebarItem {
    type: number;
    icon: string;
    name: string;
    disabled?: boolean;
}

interface Theme {
    bgColor: string;
    iconColor: string;
    textColor: string;
    sidebarBgColor: string;
}

interface ThemeClasses {
    shadow: string;
    hoverBgColor: string;
    hoverShadow: string;
    iconBgColor: string;
}

const props = withDefaults(
    defineProps<{
        item: SidebarItem;
        sidebarIndex: number;
    }>(),
    {
        item: () => ({
            type: 0,
            icon: "",
            name: "",
            disabled: false,
        }),
        sidebarIndex: 0,
    }
);

const emit = defineEmits<{
    (e: "update:sidebarIndex", index: number): void;
}>();

const route = useRoute();

const isActive = computed(() => props.sidebarIndex === props.item.type);

const isBlack = computed(() => {
    return [
        AppKeyEnum.DIGITAL_HUMAN,
        AppKeyEnum.DRAWING,
        AppKeyEnum.REDBOOK,
        AppKeyEnum.SPH,
        AppKeyEnum.MATRIX,
    ].includes(route.meta.key as AppKeyEnum);
});

const getIconColor = computed(() => {
    if (props.item.disabled) return "rgba(255,255,255,0.2)";
    return isActive.value ? "#ffffff" : getTheme.value.iconColor;
});

const getTheme = computed<Theme>(() => {
    const isDarkTheme = isBlack.value;

    return isDarkTheme
        ? {
              bgColor: "var(--app-bg-color-2)",
              sidebarBgColor: "var(--app-bg-color-1)",
              iconColor: "#ffffff",
              textColor: "#ffffff",
          }
        : {
              bgColor: "#ffffff",
              sidebarBgColor: "#F6F6F6",
              iconColor: "var(--color-black)",
              textColor: "var(--color-black)",
          };
});

const getThemeClass = computed<ThemeClasses>(() => {
    const isDarkTheme = isBlack.value;

    return isDarkTheme
        ? {
              shadow: "shadow-[0_0_0_1px_rgba(42,42,42,1)]",
              hoverBgColor: "hover:bg-app-bg-1",
              hoverShadow: "hover:shadow-[0_0_0_1px_rgba(42,42,42,1)]",
              iconBgColor: "bg-[#ffffff0d]",
          }
        : {
              shadow: "shadow-[0_0_0_1px_rgba(239,239,239,1)]",
              hoverBgColor: "hover:bg-[#F6F6F6]",
              hoverShadow: "hover:shadow-[0_0_0_1px_rgba(239,239,239,1)]",
              iconBgColor: "bg-[#0000000d]",
          };
});

const handleSidebar = (type: number) => {
    if (isActive.value || props.item.disabled) return;
    emit("update:sidebarIndex", type);
};
</script>

<style scoped lang="scss">
.sidebar-item {
    @apply flex items-center gap-2.5 h-11 rounded-md px-3 cursor-pointer transition-all duration-200;

    &.is-disabled {
        @apply cursor-not-allowed;

        &:hover {
            background-color: transparent;
            box-shadow: none;
        }
    }

    &:focus {
        @apply outline-none ring-2 ring-primary ring-offset-2;
    }
}
</style>
