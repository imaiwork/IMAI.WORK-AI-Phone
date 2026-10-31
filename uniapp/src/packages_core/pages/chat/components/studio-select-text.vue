<template>
    <text
        class="studio-select-text"
        :class="customClass"
        selectable
        user-select
        @longpress.stop="onLongPress">
        <slot>{{ text }}</slot>
    </text>
</template>

<script setup lang="ts">
import { useCopy } from "@/hooks/useCopy";

const props = withDefaults(
    defineProps<{
        text?: string;
        customClass?: string;
        /** 长按整段复制（scroll-view 内系统选区不可靠时的兜底） */
        longPressCopy?: boolean;
    }>(),
    {
        text: "",
        customClass: "",
        longPressCopy: true,
    },
);

const { copy } = useCopy();

const onLongPress = () => {
    if (!props.longPressCopy) return;
    const val = String(props.text ?? "").trim();
    if (val) copy(val);
};
</script>

<style lang="scss" scoped>
.studio-select-text {
    user-select: text;
    -webkit-user-select: text;
}
</style>
