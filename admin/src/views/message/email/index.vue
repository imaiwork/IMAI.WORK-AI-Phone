<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-lg font-medium">发件邮箱设置</div>
            <el-form
                ref="formRef"
                class="mt-4"
                label-width="120px"
                :rules="rules"
                :model="formData"
            >
                <el-form-item label="发件邮箱账号" prop="form_address">
                    <el-input
                        v-model="formData.form_address"
                        class="w-[240px]"
                        placeholder="请输入发件邮箱账号"
                    />
                </el-form-item>
                <el-form-item label="邮箱授权码" prop="auth_password">
                    <el-input
                        v-model="formData.auth_password"
                        class="w-[240px]"
                        placeholder="请输入邮箱授权码"
                    />
                    <a
                        target="_blank"
                        href="https://zv6iglmyl7h.feishu.cn/docx/EhykdUgzyofjAax4TNbcvP1Rn5I"
                    >
                        <el-button class="ml-2" type="primary" link>如何获取授权码</el-button>
                    </a>
                </el-form-item>
                <el-form-item label="发件协议类型" class="is-required">
                    <el-input
                        value="SMTP"
                        class="w-[240px]"
                        placeholder="请输入发件协议类型"
                        disabled
                    />
                </el-form-item>
                <el-form-item label="服务器地址" prop="smtp_host">
                    <el-input
                        v-model="formData.smtp_host"
                        class="w-[240px]"
                        placeholder="请输入服务器地址"
                    />
                </el-form-item>
                <el-form-item label="SSL端口" prop="smtp_port">
                    <el-input
                        v-model="formData.smtp_port"
                        class="w-[240px]"
                        placeholder="请输入SSL端口"
                    />
                </el-form-item>
            </el-form>
        </el-card>
        <footer-btns>
            <el-button @click="submit" type="primary">保存</el-button>
        </footer-btns>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, shallowRef } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { getEmailConfig, setEmailConfig } from '@/api/message'

interface IFormData {
    form_address: string
    auth_password: string
    smtp_host: string
    smtp_port: string
}
//表单ref
const formRef = shallowRef<FormInstance>()

//表单数据
const formData: IFormData = reactive({
    form_address: '',
    auth_password: '',
    smtp_host: '',
    smtp_port: ''
})

const rules = reactive({
    form_address: [
        {
            required: true,
            message: '请输入发件邮箱账号',
            trigger: 'change'
        }
    ],
    auth_password: [
        {
            required: true,
            message: '请输入邮箱授权码',
            trigger: 'change'
        }
    ],
    smtp_host: [
        {
            required: true,
            message: '请输入服务器地址',
            trigger: 'change'
        }
    ],
    smtp_port: [
        {
            required: true,
            message: '请输入SSL端口',
            trigger: 'change'
        }
    ]
})

const getData = async () => {
    const res = await getEmailConfig()
    Object.keys(formData).map((item) => {
        formData[item] = res[item]
    })
}

const submit = async () => {
    await formRef.value?.validate()
    await setEmailConfig({ ...formData })
    getData()
}

onMounted(() => {
    getData()
})
</script>

<style scoped lang="scss"></style>
