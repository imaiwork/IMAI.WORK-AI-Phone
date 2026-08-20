<template>
    <popup
        ref="popupRef"
        async
        width="640px"
        :title="mode === 'add' ? '新增媒体' : '编辑媒体'"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <el-form :model="formData" :rules="rules" ref="formRef" label-width="100px">
            <el-form-item label="媒体名称" prop="name">
                <el-input v-model="formData.name" placeholder="如:微信公众号 / 小红书" maxlength="100" />
            </el-form-item>
            <el-form-item label="渠道类型" prop="type">
                <el-select v-model="formData.type" placeholder="请选择渠道类型" class="w-full">
                    <el-option v-for="t in props.options.types" :key="t.value" :label="t.label" :value="t.value" />
                </el-select>
            </el-form-item>
            <el-form-item label="渠道标识" prop="provider_code">
                <el-select
                    v-if="formData.type === 'ai_phone'"
                    v-model="formData.provider_code"
                    placeholder="AI手机渠道标识"
                    clearable
                    class="w-full">
                    <el-option
                        v-for="p in props.options.phone_platforms"
                        :key="p.value"
                        :label="`${p.label}(${p.value})`"
                        :value="p.value" />
                </el-select>
                <el-input v-else v-model="formData.provider_code" placeholder="渠道唯一标识,留空表示无渠道映射" maxlength="50" />
                <div class="text-xs text-[#9ca3af]">AI手机平台映射的权威字段,全库唯一,重复会被拒绝</div>
            </el-form-item>
            <el-form-item label="授权平台" prop="platform_code">
                <el-select v-model="formData.platform_code" placeholder="命中已授权账号时免代发直发" clearable class="w-full">
                    <el-option
                        v-for="p in props.options.auth_platforms"
                        :key="p.value"
                        :label="`${p.label}(${p.value})`"
                        :value="p.value" />
                </el-select>
            </el-form-item>
            <el-form-item label="投稿类型" prop="content_form">
                <el-radio-group v-model="formData.content_form">
                    <el-radio v-for="f in props.options.content_forms" :key="f.value" :value="f.value">
                        {{ f.label }}
                    </el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="行业分类" prop="category">
                <el-input v-model="formData.category" placeholder="如:综合 / 科技" maxlength="50" />
            </el-form-item>
            <div class="grid grid-cols-2">
                <el-form-item label="PC权重" prop="pc_weight">
                    <el-input-number v-model="formData.pc_weight" :min="0" :max="10" />
                </el-form-item>
                <el-form-item label="移动权重" prop="mobile_weight">
                    <el-input-number v-model="formData.mobile_weight" :min="0" :max="10" />
                </el-form-item>
                <el-form-item label="成功率%" prop="success_rate">
                    <el-input-number v-model="formData.success_rate" :min="0" :max="100" />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="formData.sort" :min="0" />
                </el-form-item>
            </div>
            <el-form-item label="发布速度" prop="publish_speed">
                <el-input v-model="formData.publish_speed" placeholder="如:1-2天 / 即时" maxlength="30" />
            </el-form-item>
            <el-form-item label="收录情况" prop="include_status">
                <el-input v-model="formData.include_status" placeholder="如:百度秒收" maxlength="50" />
            </el-form-item>
            <div class="grid grid-cols-2">
                <el-form-item label="允许带链接">
                    <el-switch v-model="formData.allow_url" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="可做GEO排名">
                    <el-switch v-model="formData.can_geo_rank" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </div>
            <el-form-item label="备注" prop="remark">
                <el-input v-model="formData.remark" type="textarea" :rows="2" maxlength="500" show-word-limit />
            </el-form-item>
            <el-form-item label="上架状态" prop="status">
                <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" active-text="上架" inactive-text="下架" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script setup lang="ts">
import { addGeoMedia, editGeoMedia } from "@/api/marketing/geo";
import { useLockFn } from "@/hooks/useLockFn";
import { type FormInstance } from "element-plus";

const props = defineProps<{
    options: {
        types: any[];
        auth_platforms: any[];
        phone_platforms: any[];
        content_forms: any[];
    };
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const formRef = shallowRef<FormInstance>();
const popupRef = ref();
const mode = ref<"add" | "edit">("add");

const formData = reactive({
    id: "",
    name: "",
    type: "",
    provider_code: "",
    platform_code: "",
    content_form: "article",
    category: "",
    pc_weight: 0,
    mobile_weight: 0,
    success_rate: 100,
    sort: 0,
    publish_speed: "",
    include_status: "",
    allow_url: 1,
    can_geo_rank: 1,
    remark: "",
    status: 1,
});

const rules = {
    name: [{ required: true, message: "请输入媒体名称", trigger: "blur" }],
    type: [{ required: true, message: "请选择渠道类型", trigger: "change" }],
};

const submit = async () => {
    await formRef.value?.validate();
    const payload: any = { ...formData };
    if (mode.value === "add") {
        delete payload.id;
        await addGeoMedia(payload);
    } else {
        await editGeoMedia(payload);
    }
    close();
    emit("success");
};

const resetForm = () => {
    formData.id = "";
    formData.name = "";
    formData.type = "";
    formData.provider_code = "";
    formData.platform_code = "";
    formData.content_form = "article";
    formData.category = "";
    formData.pc_weight = 0;
    formData.mobile_weight = 0;
    formData.success_rate = 100;
    formData.sort = 0;
    formData.publish_speed = "";
    formData.include_status = "";
    formData.allow_url = 1;
    formData.can_geo_rank = 1;
    formData.remark = "";
    formData.status = 1;
    formRef.value?.clearValidate();
};

const open = (type: "add" | "edit") => {
    resetForm();
    popupRef.value?.open();
    mode.value = type;
};

const close = () => {
    emit("close");
};

const setFormData = (data: any) => {
    resetForm();
    for (const key in formData) {
        if (data[key] != null) {
            // @ts-ignore
            formData[key] = data[key];
        }
    }
};

const { lockFn, isLock } = useLockFn(submit);

defineExpose({ open, setFormData });
</script>

<style scoped></style>
