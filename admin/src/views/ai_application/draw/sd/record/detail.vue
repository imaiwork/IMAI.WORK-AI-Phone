<template>
    <popup ref="popupRef" title="生成详情" width="700px" confirm-button-text="" cancel-button-text="" @close="close">
        <div class="flex flex-col gap-2">
            <div>
                {{ detail.create_time }}
            </div>
            <div class="flex gap-2">
                <el-tag v-if="detail.type_name">
                    {{ detail.type_name }}
                </el-tag>
                <el-tag v-if="detail.params?.aspect_ratio">
                    {{ detail.params?.aspect_ratio }}
                </el-tag>
                <el-tag v-if="detail.params?.height"> {{ detail.params?.height }}*{{ detail.params?.width }} </el-tag>
            </div>
            <div>
                {{ detail.params?.prompt }}
            </div>
            <div class="h-[500px]">
                <el-scrollbar>
                    <div v-for="(item, index) in detail.images" class="rounded-md border-[1px] border-[#f0f0f0] p-2">
                        <el-image :src="item.image" :preview-src-list="[item.image]" />
                    </div>
                </el-scrollbar>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const emit = defineEmits<{
    (event: "close"): void;
}>();

const detail = ref<any>();

const popupRef = ref();

const open = (row: any) => {
    detail.value = row;
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped></style>
