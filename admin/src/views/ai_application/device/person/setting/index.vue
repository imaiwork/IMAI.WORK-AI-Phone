<template>
    <div>
        <el-form label-width="140px" :model="formData" :rules="rules">
            <el-card shadow="never" class="!border-none">
                <div class="text-lg font-medium">人设设置</div>
                <div class="mt-4">
                    <el-form-item label="一键清理按钮">
                        <div>
                            <el-switch
                                v-model="formData.material_clean_switch"
                                :active-value="1"
                                :inactive-value="0" />
                            <div class="form-tips">
                                默认隐藏，开启后小程序趣味素材库「配置素材库」页展示一键清理按钮，用户可自行清理使用次数过多的素材
                            </div>
                        </div>
                    </el-form-item>
                    <el-form-item label="清理次数阈值" prop="material_clean_threshold">
                        <div>
                            <el-input-number
                                v-model="formData.material_clean_threshold"
                                :min="1"
                                :max="999"
                                :precision="0"
                                :controls="false"
                                class="w-[200px]"
                                placeholder="请输入清理次数阈值" />
                            <div class="form-tips">素材使用次数达到该值后可一键清理，默认3次</div>
                        </div>
                    </el-form-item>
                </div>
            </el-card>
        </el-form>
        <footer-btns>
            <el-button v-perms="['aiPersona.personaMaterialSetting/setConfig']" type="primary" :disabled="lockFn" @click="submit">
                保存
            </el-button>
        </footer-btns>
    </div>
</template>

<script setup lang="ts">
import { getPersonaMaterialConfig, setPersonaMaterialConfig } from "@/api/ai_application/device/person";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";
import type { FormRules } from "element-plus";

const formData = ref({
    material_clean_switch: 0, // 一键清理按钮显示/隐藏
    material_clean_threshold: 3, // 清理次数阈值
});

const rules = reactive<FormRules>({
    material_clean_threshold: [{ required: true, message: "请输入清理次数阈值", trigger: "blur" }],
});

//获取页面配置
const getPageConfig = async () => {
    const data = await getPersonaMaterialConfig();
    formData.value = {
        material_clean_switch: Number(data?.material_clean_switch ?? 0),
        material_clean_threshold: Number(data?.material_clean_threshold ?? 3),
    };
};

//保存
const { lockFn: submit } = useLockFn(async () => {
    await setPersonaMaterialConfig(formData.value);
    feedback.msgSuccess("保存成功");
    getPageConfig();
});

onMounted(() => {
    getPageConfig();
});
</script>

<style scoped lang="scss"></style>
