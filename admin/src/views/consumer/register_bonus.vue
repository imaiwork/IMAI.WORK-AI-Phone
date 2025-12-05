<template>
    <div>
        <el-form ref="formRef" :rules="rules" :model="formData" label-width="120px">
            <el-card shadow="never" class="!border-none">
                <div class="font-medium mb-7">注册奖励</div>
                <el-form-item label="功能状态" prop="status">
                    <div>
                        <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                        <div class="form-tips">关闭后，新用户注册将不赠送免费次数</div>
                    </div>
                </el-form-item>
                <el-form-item label="赠送算力值" prop="reward_chat">
                    <div>
                        <el-input class="w-[240px]" v-model="formData.reward_chat" type="number">
                            <template #append> 算力值 </template>
                        </el-input>
                        <div class="form-tips">新用户注册，免费赠送算力值数量；填写0或者为空则表示不赠送</div>
                    </div>
                </el-form-item>
                <el-form-item label="赠送机器人" prop="reward_robot">
                    <div>
                        <el-input class="w-[240px]" v-model="formData.reward_robot" type="number">
                            <template #append> 个 </template>
                        </el-input>
                        <div class="form-tips">新用户注册，免费赠送机器人应用；填写0或者为空则表示不赠送</div>
                    </div>
                </el-form-item>
                <!-- <el-form-item label="赠送形象时长" prop="reward_video">
                    <div>
                        <el-input v-model="formData.reward_video" class="w-[240px]" type="number">
                            <template #append> 分钟 </template>
                        </el-input>
                        <div class="form-tips">
                            新用户注册，免费赠送形象视频合成时长；填写0或者为空则表示不赠送
                        </div>
                    </div>
                </el-form-item> -->
            </el-card>
        </el-form>

        <footer-btns v-perms="['market.regReward/save']">
            <el-button type="primary" @click="handleSubmit">保存</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup name="registerReward">
import { getRegisterReward, setRegisterReward } from "@/api/consumer";
import type { FormInstance, FormRules } from "element-plus";
const formRef = ref<FormInstance>();

// 表单数据
const formData = ref({
    status: 0,
    reward_chat: "",
    reward_robot: "",
    reward_video: "",
});

// 表单验证
const rules = reactive<FormRules>({
    reward_chat: [
        {
            validator: (rule: any, value: any, callback: any) => {
                if (value < 0) {
                    callback(new Error("输入不能小于0"));
                } else {
                    callback();
                }
            },
            trigger: "blur",
        },
    ],
    reward_robot: [
        {
            validator: (rule: any, value: any, callback: any) => {
                if (value < 0) {
                    callback(new Error("输入不能小于0"));
                } else {
                    callback();
                }
            },
            trigger: "blur",
        },
    ],
    // reward_video: [
    //     {
    //         validator: (rule: any, value: any, callback: any) => {
    //             if (value < 0) {
    //                 callback(new Error('输入不能小于0'))
    //             } else {
    //                 callback()
    //             }
    //         },
    //         trigger: 'blur'
    //     }
    // ]
});

// 获取登录注册数据
const getData = async () => {
    try {
        const data = await getRegisterReward();
        formData.value = data;
    } catch (error) {
        console.log("获取=>", error);
    }
};

// 保存登录注册数据
const handleSubmit = async () => {
    await formRef.value?.validate();
    try {
        await setRegisterReward(formData.value);
        getData();
    } catch (error) {
        console.log("保存=>", error);
    }
};

getData();
</script>

<style lang="scss" scoped></style>
