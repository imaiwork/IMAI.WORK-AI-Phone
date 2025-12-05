<template>
    <div class="w-full flex gap-2">
        <ElSelect v-model="currResolution" popper-class="dark-select-popper" class="!w-[120px]" :show-arrow="false">
            <ElOption
                v-for="(item, index) in getResolutionOptions"
                :key="index"
                :label="item.label"
                :value="item.value"></ElOption>
        </ElSelect>
        <div class="flex-1 flex items-center">
            <div class="h-11 flex-1 flex items-center gap-x-2 bg-app-bg-3 border border-app-border-2 px-3 rounded-lg">
                <span class="text-white">宽</span>
                <span class="text-[#ffffff80]">{{ getResolutionSize.width }}</span>
            </div>
            <div>x</div>
            <div class="h-11 flex-1 flex items-center gap-x-2 bg-app-bg-3 border border-app-border-2 px-3 rounded-lg">
                <span class="text-white">高</span>
                <span class="text-[#ffffff80]">{{ getResolutionSize.height }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { resolutionOptions, videoResolutionOptions, ModelEnum, seedreamResolutionOptions } from "../_enums";

const props = withDefaults(
    defineProps<{
        type?: "image" | "video";
        model?: ModelEnum;
    }>(),
    {
        type: "image",
    }
);

const emit = defineEmits<{
    (event: "update:resolution", value: { width: number | string; height: number | string; label: string }): void;
}>();

const getResolutionOptions = computed(() => {
    if (props.type === "image") {
        if (props.model == ModelEnum.SEEDREAM) {
            return seedreamResolutionOptions;
        }
        return resolutionOptions;
    }
    return videoResolutionOptions;
});

const currResolution = ref(getResolutionOptions.value[0].value);

const getResolutionSize = computed(() => {
    const [width, height] = currResolution.value.split("*");
    emit("update:resolution", {
        width: width,
        height: height,
        label: getResolutionOptions.value.find((item) => item.value === currResolution.value).label,
    });
    return {
        width: width,
        height: height,
    };
});

watch(
    () => props.model,
    () => {
        currResolution.value = getResolutionOptions.value[0].value;
    }
);
</script>

<style scoped></style>
