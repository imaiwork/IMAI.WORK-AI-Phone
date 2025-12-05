<template>
    <ElDatePicker
        v-model="content"
        type="daterange"
        :range-separator="rangeSeparator"
        :start-placeholder="startPlaceholder"
        :end-placeholder="endPlaceholder"
        :value-format="valueFormat"
        v-bind="$attrs"
        clearable></ElDatePicker>
</template>

<script lang="ts" setup>
import { withDefaults, computed } from "vue";

/* Props S */
const props = withDefaults(
    defineProps<{
        startTime?: string;
        endTime?: string;
        startPlaceholder?: string;
        endPlaceholder?: string;
        valueFormat?: string;
        rangeSeparator?: string;
    }>(),
    {
        startTime: "",
        endTime: "",
        startPlaceholder: "开始时间",
        endPlaceholder: "结束时间",
        valueFormat: "YYYY-MM-DD HH:mm:ss",
        rangeSeparator: "-",
    }
);
const emit = defineEmits(["update:startTime", "update:endTime"]);

const content = computed<any>({
    get: () => {
        return [props.startTime, props.endTime];
    },
    set: (value: Event | any) => {
        if (value === null) {
            emit("update:startTime", "");
            emit("update:endTime", "");
        } else {
            emit("update:startTime", value[0]);
            emit("update:endTime", value[1]);
        }
    },
});
</script>
