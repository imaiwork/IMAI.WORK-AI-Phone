<template>
    <ElDrawer v-model="visible" title="账号选择" :size="400">
        <div>
            <div>
                <div></div>
            </div>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
const emit = defineEmits<{
    (e: "close"): void;
}>();

const visible = ref(false);

const open = () => {
    visible.value = true;
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    close,
});
</script>

<style scoped></style>
