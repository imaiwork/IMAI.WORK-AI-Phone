<template>
    <div class="pagination">
        <ElPagination
            v-bind="props"
            size="small"
            :pager-count="5"
            :background="true"
            v-model:currentPage="pager.page"
            v-model:pageSize="pager.size"
            :page-sizes="pageSizes"
            :layout="layout"
            :total="pager.count"
            :hide-on-single-page="hideOnSinglePage"
            @size-change="sizeChange"
            @current-change="pageChange"></ElPagination>
    </div>
</template>

<script lang="ts" setup>
import { ElPagination } from "element-plus";
interface Props {
    modelValue?: Record<string, any>;
    pageSizes?: number[];
    layout?: string;
    hideOnSinglePage?: boolean;
}
const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({}),
    pageSizes: () => [15, 20, 30, 40],
    layout: "total, sizes, prev, pager, next, jumper",
    hideOnSinglePage: false,
});

const emit = defineEmits<{
    (event: "change"): void;
    (event: "update:modelValue", value: any): void;
}>();

const pager = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});
const sizeChange = () => {
    pager.value.page = 1;
    emit("change");
};
const pageChange = () => {
    emit("change");
};
</script>
<style lang="scss" scoped></style>
