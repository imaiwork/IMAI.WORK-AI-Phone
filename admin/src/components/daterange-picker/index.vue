<template>
    <el-date-picker
        v-model="content"
        :type="type"
        range-separator="-"
        start-placeholder="开始时间"
        end-placeholder="结束时间"
        :value-format="valueFormat"
        clearable></el-date-picker>
</template>

<script lang="ts" setup>
import { withDefaults, computed } from "vue";

/* Props S */
const props = withDefaults(
    defineProps<{
        startTime?: string;
        endTime?: string;
        type?: string;
        valueFormat?: string;
        second?: boolean;
    }>(),
    {
        startTime: "",
        endTime: "",
        type: "datetimerange",
        valueFormat: "YYYY-MM-DD HH:mm:ss",
        second: false,
    }
);
const emit = defineEmits(["update:startTime", "update:endTime"]);

const content = computed<any>({
    get: () => {
        if (props.second) {
            return [Number(props.startTime) * 1000, Number(props.endTime) * 1000];
        }
        return [props.startTime, props.endTime];
    },
    set: (value: Event | any) => {
        if (value === null) {
            emit("update:startTime", "");
            emit("update:endTime", "");
        } else {
            if (props.second) {
                emit("update:startTime", value[0] / 1000);
                emit("update:endTime", value[1] / 1000);
                return;
            }
            emit("update:startTime", value[0]);
            emit("update:endTime", value[1]);
        }
    },
});
</script>
