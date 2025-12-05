<template>
    <Popup
        ref="popRef"
        title="创建用户"
        width="500px"
        async
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="$emit('close')">
        <div>
            <el-form label-width="90px" :model="formData" ref="formRef" :rules="rules" @submit.prevent>
                <el-form-item label="用户头像" prop="avatar">
                    <MaterialPicker v-model="formData.avatar" />
                </el-form-item>
                <el-form-item label="用户昵称" prop="nickname">
                    <el-input placeholder="请输入用户昵称" v-model="formData.nickname" />
                </el-form-item>
                <el-form-item label="手机号码" prop="mobile">
                    <el-input placeholder="请输入手机号码" v-model="formData.mobile" maxlength="11" />
                </el-form-item>
                <el-form-item label="登录密码" prop="password">
                    <el-input placeholder="请输入登录密码" type="password" v-model="formData.password" show-password />
                </el-form-item>
                <el-form-item label="确认密码" prop="password_confirm">
                    <el-input
                        placeholder="请输入确认密码"
                        type="password"
                        show-password
                        v-model="formData.password_confirm" />
                </el-form-item>
            </el-form>
        </div>
    </Popup>
</template>

<script setup lang="ts">
import { addUser } from "@/api/consumer";
import { getUserSetup } from "@/api/setting/user";
import { useLockFn } from "@/hooks/useLockFn";
import type { FormInstance, FormRules } from "element-plus";
import { validateMobile } from "@/utils/validate";

interface IFormData {
    avatar: string;
    nickname: string;
    mobile: string;
    password: string;
    password_confirm: string;
}

const emit = defineEmits(["close"]);

//弹框ref
const popRef = shallowRef();
//表单ref
const formRef = shallowRef<FormInstance>();

//表单数据
const formData: IFormData = reactive({
    avatar: "",
    nickname: "",
    mobile: "",
    password: "",
    password_confirm: "",
});

const rules = reactive<FormRules<IFormData>>({
    avatar: [
        {
            required: true,
            message: "请选择头像",
            trigger: "change",
        },
    ],
    nickname: [
        {
            required: true,
            message: "请填写昵称",
            trigger: "change",
        },
    ],
    mobile: [
        {
            required: true,
            message: "请填写手机号码",
            trigger: "change",
        },
        {
            validator: (rule: any, value: any, callback) => {
                if (!validateMobile(value)) {
                    callback(new Error("填写的手机号格式不正确"));
                }
                callback();
            },
            trigger: "blur",
        },
    ],
    password: [
        {
            required: true,
            message: "请填写密码",
            trigger: "change",
        },
    ],
    password_confirm: [
        {
            required: true,
            message: "请填写确认密码",
            trigger: "change",
        },
    ],
});

const getUserConfig = async () => {
    const { default_avatar } = await getUserSetup();
    formData.avatar = default_avatar;
};

//提交表单
const submit = async () => {
    await formRef.value?.validate();
    await addUser({ ...formData });
    popRef.value.close();
};

const { lockFn, isLock } = useLockFn(submit);

//打开弹框
const open = () => {
    popRef.value.open();
    getUserConfig();
};

defineExpose({ open });
</script>

<style scoped lang="scss"></style>
