<template>
    <Popup ref="popRef" confirmButtonText="" cancelButtonText="">
        <el-progress :percentage="percentage" />
    </Popup>
</template>

<script lang="ts" setup>
const props = defineProps({
    percentage: {
        type: Number,
        default: 10
    }
})

const popRef = shallowRef()

const open = () => {
    popRef.value.open()
}

defineExpose({ open })
</script>

<style lang="scss" scoped></style>
