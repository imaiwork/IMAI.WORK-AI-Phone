<template>
    <div
        class="sidebar-item group"
        :class="[
            {
                active: isActive,
                'is-disabled': item.disabled,
            },
        ]"
        @click="handleSidebar(item.type)">
        <div
            class="absolute left-0 w-1 h-5 bg-primary rounded-r-full transition-all duration-300 scale-y-0"
            :class="{ 'scale-y-100': isActive }"></div>

        <!-- <div v-if="item.icon" class="flex-shrink-0">
            <Icon :name="item.icon" :size="18" />
        </div> -->

        <span class="text-[14px] font-medium transition-colors">
            {{ item.name }}
        </span>

        <div v-if="isActive" class="ml-auto">
            <div class="w-1.5 h-1.5 rounded-full bg-[#0065fb]/40"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
// Logic remains the same as your original code
interface SidebarItem {
    type: number;
    icon: string;
    name: string;
    disabled?: boolean;
}
const props = defineProps<{ item: SidebarItem; sidebarIndex: number }>();
const emit = defineEmits<{ (e: "update:sidebarIndex", index: number): void }>();

const isActive = computed(() => props.sidebarIndex === props.item.type);
const handleSidebar = (type: number) => {
    if (isActive.value || props.item.disabled) return;
    emit("update:sidebarIndex", type);
};
</script>

<style scoped lang="scss">
.sidebar-item {
    @apply relative flex items-center gap-3 h-[46px] rounded-xl pr-2 pl-5 cursor-pointer transition-all duration-300;
    @apply text-[#64748B];

    &:not(.active):not(.is-disabled):hover {
        @apply bg-[#F1F5F9] text-primary;
    }

    &.active {
        @apply bg-[#0065FB]/5 text-primary;
    }

    &.is-disabled {
        @apply cursor-not-allowed opacity-40;
    }
}
</style>
