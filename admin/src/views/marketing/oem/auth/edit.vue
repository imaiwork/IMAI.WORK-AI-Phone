<template>
    <popup
        ref="popupRef"
        async
        width="500px"
        :title="mode === 'add' ? '添加授权' : '编辑授权'"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div>
            <el-form :model="formData" :rules="rules" ref="formRef" label-width="100px">
                <el-form-item label="所属用户" prop="user_id">
                    <el-select
                        v-model="formData.user_id"
                        placeholder="请输入用户信息"
                        filterable
                        remote
                        reserve-keyword
                        :remote-method="remoteMethodUser"
                        :loading="userLoading"
                        clearable>
                        <el-option
                            v-for="item in userList"
                            :key="item.id"
                            :label="`${item.nickname}（${item.account}）`"
                            :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="站点域名" prop="domain">
                    <el-input v-model="formData.domain" placeholder="请输入站点域名" />
                    <div class="form-tips">不要带上http或者https，例如：www.baidu.com</div>
                </el-form-item>
                <el-form-item label="站点标题" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入站点标题" maxlength="30" />
                </el-form-item>
                <el-form-item label="站点ICON" prop="logo_url">
                    <material-picker v-model="formData.logo_url" :limit="1" />
                    <div class="form-tips">建议尺寸：64*64像素，支持jpg，jpeg，png格式</div>
                </el-form-item>
                <el-form-item label="站点LOGO" prop="site_logo">
                    <material-picker v-model="formData.site_logo" :limit="1" />
                    <div class="form-tips">建议尺寸：128*128像素，支持jpg，jpeg，png格式</div>
                </el-form-item>
            </el-form>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { addOem, editOem } from "@/api/marketing/oem";
import { getUserList } from "@/api/consumer";
import { useLockFn } from "@/hooks/useLockFn";
import { type FormInstance } from "element-plus";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const formRef = shallowRef<FormInstance>();
const formData = reactive<any>({
    id: "",
    user_id: "",
    domain: "",
    logo_url: "",
    name: "",
});
const rules = {
    user_id: [{ required: true, message: "请输入用户" }],
    domain: [{ required: true, message: "请输入站点域名" }],
    logo_url: [{ required: true, message: "请上传站点ICON" }],
    name: [{ required: true, message: "请输入站点标题" }],
};

const popupRef = ref();

const mode = ref<"add" | "edit">("add");

const userList = ref<any[]>([]);
const userLoading = ref(false);

const remoteMethodUser = async (query: string) => {
    try {
        userLoading.value = true;
        const { lists } = await getUserList({ keyword: query });
        userList.value = lists;
    } finally {
        userLoading.value = false;
    }
};

const submit = async () => {
    await formRef.value?.validate();
    mode.value === "add" ? await addOem(formData) : await editOem(formData);
    close();
    emit("success");
};

const open = (type: "add" | "edit") => {
    popupRef.value?.open();
    mode.value = type;
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(submit);

const setFormData = async (data: any) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
    remoteMethodUser(data.username);
};

defineExpose({
    open,
    setFormData,
});
</script>

<style scoped></style>
