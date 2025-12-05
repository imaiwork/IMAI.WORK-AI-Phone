<template>
    <popup ref="popupRef" title="编辑驱动模型" width="500px" @close="close" @confirm="save">
        <el-form :model="formData">
            <el-form-item label="名称">
                <el-input v-model="formData.name" maxlength="4" show-word-limit />
            </el-form-item>
            <el-form-item label="描述">
                <el-input v-model="formData.described" type="textarea" :rows="4" maxlength="50" show-word-limit />
            </el-form-item>
            <el-form-item label="图标">
                <material-picker v-model="formData.icon" :limit="1" />
            </el-form-item>
            <el-form-item label="状态">
                <el-switch v-model="formData.status" active-value="1" inactive-value="0" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";

const props = defineProps({
    formData: {
        type: Object,
        default: () => ({}),
    },
});

const popupRef = ref<InstanceType<typeof Popup>>();

const emit = defineEmits(["close", "success"]);

const formData = reactive({
    id: "",
    name: "",
    described: "",
    icon: "",
    status: "1",
});

const save = () => {
    emit("success", formData);
};

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const setFormData = (data: any) => {
    formData.id = data.id;
    formData.name = data.name;
    formData.described = data.described;
    formData.icon = data.icon;
    formData.status = data.status;
};

defineExpose({
    open,
    setFormData,
});
</script>

<style scoped></style>
