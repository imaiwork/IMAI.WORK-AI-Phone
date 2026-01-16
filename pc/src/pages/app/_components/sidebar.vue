<template>
    <div class="w-[200px] h-full flex-shrink-0 bg-white py-6 rounded-[20px]">
        <ElScrollbar>
            <div class="flex flex-col gap-3 px-4">
                <div v-for="(group, index) in sidebar" :key="index">
                    <div
                        class="mb-3 text-[11px] font-black text-[#94A3B8] px-4 uppercase tracking-widest"
                        v-if="group.title">
                        {{ group.title }}
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <template v-if="group.children && group.children.length > 0">
                            <sidebar-item
                                v-for="(child, cIndex) in group.children"
                                :key="cIndex"
                                :item="child"
                                :sidebar-index="sidebarIndex"
                                @update:sidebar-index="emit('update:sidebarIndex', $event)" />
                        </template>
                        <template v-else>
                            <sidebar-item
                                :item="group"
                                :sidebar-index="sidebarIndex"
                                @update:sidebar-index="emit('update:sidebarIndex', $event)" />
                        </template>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import SidebarItem from "./sidebar-item.vue";

const props = withDefaults(defineProps<{ sidebar: any[]; sidebarIndex: number }>(), {
    sidebar: () => [],
    sidebarIndex: 0,
});

const emit = defineEmits<{
    (event: "update:sidebarIndex", index: number): void;
}>();
</script>
