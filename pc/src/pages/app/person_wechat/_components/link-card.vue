<template>
    <div class="p-2 bg-white h-full rounded-lg">
        <div class="font-bold line-clamp-2 text-[15px]">
            {{ title }}
        </div>
        <div class="mt-2 flex gap-3 w-full">
            <div class="flex-1 w-0 overflow-hidden">
                <span class="line-clamp-2 text-xs text-[#545454]">
                    {{ desc }}
                </span>
            </div>
            <div class="flex-shrink-0">
                <img :src="img" class="w-12 h-12 object-cover rounded-md" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    desc: string;
    img: string;
}>();
</script>

<style scoped></style>
