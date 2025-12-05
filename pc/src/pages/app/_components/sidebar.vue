<template>
    <div class="w-[228px] rounded-[20px] py-5 flex-shrink-0" :style="getTheme">
        <ElScrollbar>
            <div class="flex flex-col gap-2.5 px-4 mt-1">
                <div v-for="(item, index) in sidebar" :key="index">
                    <div class="text-white rounded-md px-3 py-3 mb-1 font-bold" v-if="item.title">{{ item.title }}</div>
                    <template v-if="item.children && item.children.length > 0">
                        <div class="flex flex-col gap-2.5">
                            <sidebar-item
                                v-for="(child, index) in item.children"
                                :key="index"
                                :item="child"
                                :sidebar-index="sidebarIndex"
                                @update:sidebar-index="emit('update:sidebarIndex', $event)" />
                        </div>
                    </template>
                    <template v-else>
                        <sidebar-item
                            :item="item"
                            :sidebar-index="sidebarIndex"
                            @update:sidebar-index="emit('update:sidebarIndex', $event)" />
                    </template>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import SidebarItem from "./sidebar-item.vue";
import { ThemeEnum } from "@/enums/appEnums";

const props = withDefaults(defineProps<{ sidebar: any[]; sidebarIndex: number; theme?: ThemeEnum }>(), {
    sidebar: () => [],
    sidebarIndex: 0,
    theme: ThemeEnum.LIGHT,
});

const emit = defineEmits<{
    (event: "update:sidebarIndex", index: number): void;
}>();

// 检查sidebar 是否存在children
const hasChildren = computed(() => {
    return props.sidebar.some((item) => item.children);
});

const getTheme = computed(() => {
    if (props.theme === ThemeEnum.LIGHT) {
        return {
            backgroundColor: "#fff",
        };
    } else {
        return {
            backgroundColor: "var(--app-bg-color-2)",
        };
    }
});
</script>

<style scoped></style>
