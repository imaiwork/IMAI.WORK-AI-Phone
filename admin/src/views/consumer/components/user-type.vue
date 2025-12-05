<template>
    <Popup ref="popRef" width="auto" async @confirm="submit">
        <el-form @submit.prevent>
            <el-form-item label="用户类型">
                <el-select v-model="userType" class="!w-[280px]">
                    <el-option
                        v-for="(item, index) in [
                            { name: '个人', value: 0 },
                            { name: '企业', value: 1 },
                        ]"
                        :key="index"
                        :label="item.name"
                        :value="item.value" />
                </el-select>
            </el-form-item>
        </el-form>
    </Popup>
</template>

<script setup lang="ts">
const emits = defineEmits(["close", "success"]);
const popRef = shallowRef();

const userType = ref<number>(0);

const open = (option: { userType: number }) => {
    popRef.value.open();
    userType.value = option.userType;
};

const submit = () => {
    emits("success", userType.value);
};
defineExpose({
    open,
});
</script>

<style scoped></style>
