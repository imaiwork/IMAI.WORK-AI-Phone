<template>
    <ElIcon
        v-if="name.includes(EL_ICON_PREFIX)"
        :size="size"
        :color="color"
        :class="customClass"
        v-bind="attrs">
        <component :is="name" />
    </ElIcon>
    <span
        v-else-if="name.includes(LOCAL_ICON_PREFIX)"
        class="local-icon"
        :class="customClass"
        v-bind="attrs">
        <SvgIcon :name="name" :size="size" :color="color" :custom-class="customClass" />
    </span>
</template>

<script lang="ts" setup>
import { ElIcon } from "element-plus";
import { useAttrs } from "vue";
import { EL_ICON_PREFIX, LOCAL_ICON_PREFIX } from "@/plugins/icons";
import SvgIcon from "./svg-icon.vue";

defineOptions({ inheritAttrs: false });

defineProps({
    name: {
        type: String,
        default: "",
    },
    size: {
        type: [String, Number],
        default: "14px",
    },
    color: {
        type: String,
        default: "inherit",
    },
    customClass: {
        type: String,
    },
});

const attrs = useAttrs();
</script>
