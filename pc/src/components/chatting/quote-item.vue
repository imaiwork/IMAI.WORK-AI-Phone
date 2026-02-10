<template>
    <div class="flex items-center gap-1">
        <div>
            <Icon name="local-icon-corner_down_right" color="#8c8c8c" :size="16" />
        </div>
        <div class="px-2 py-1 hover:bg-[#F8F8F9] rounded-md cursor-pointer">
            <template v-if="shouldShowPopover">
                <ElPopover
                    trigger="hover"
                    placement="top"
                    popper-class="!min-w-[400px]"
                    :content="quote"
                    :show-arrow="false">
                    <template #reference>
                        <div class="line-clamp-1 break-all text-[#8c8c8c]">
                            {{ quote }}
                        </div>
                    </template>
                </ElPopover>
            </template>
            <template v-else>
                <span class="line-clamp-1 break-all text-[#8c8c8c]">
                    {{ quote }}
                </span>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    quote: string;
}>();

const shouldShowPopover = computed(() => {
    return props.quote?.length > 20;
});
</script>

<style scoped></style>
