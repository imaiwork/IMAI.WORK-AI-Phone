<template>
    <popup ref="popupRef" :title="title" async width="500px" @confirm="handleSubmit" @close="close">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
            <el-form-item label="用户">
                <span class="font-medium">{{ userText }}</span>
            </el-form-item>
            <el-form-item label="目标等级" prop="level_id">
                <el-select v-model="formData.level_id" placeholder="请选择" class="!w-[300px]">
                    <el-option v-for="l in props.levels" :key="l.id" :label="l.name" :value="l.id" />
                </el-select>
            </el-form-item>
            <el-form-item v-if="showDuration" label="有效天数" prop="days">
                <el-input-number v-model="formData.days" :min="1" :max="3650" />
                <span class="text-xs text-amber-600 ml-2">已是会员将直接覆盖等级和到期时间</span>
            </el-form-item>
            <el-form-item label="备注">
                <el-input
                    v-model="formData.remark"
                    placeholder="例如:活动赠送 / 客服补偿"
                    maxlength="100"
                    class="!w-[300px]" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script setup lang="ts">
import { grantMember } from "@/api/consumer";
import { findDefaultUserLevel, isDefaultUserLevel, isUnsetUserLevelId, resolveUserLevelId } from "../userLevel";

const props = defineProps<{ levels: any[] }>();
const emit = defineEmits(["success", "close"]);

const popupRef = ref();
const formRef = ref();
const title = ref("开通 / 续期会员");
const userText = ref("");

const formData = reactive({
    user_id: 0,
    level_id: "" as any,
    days: 30,
    remark: "后台手动开通",
});

const selectedLevel = computed(() => {
    return props.levels.find((item) => Number(item.id) === Number(formData.level_id));
});

const showDuration = computed(() => {
    if (isUnsetUserLevelId(formData.level_id)) return false;
    return !isDefaultUserLevel(selectedLevel.value);
});

const rules = computed(() => ({
    level_id: [{ required: true, message: "请选择会员等级" }],
    ...(showDuration.value ? { days: [{ required: true, message: "请输入延长天数" }] } : {}),
}));

const handleSubmit = async () => {
    await formRef.value?.validate();
    const payload: Record<string, any> = {
        user_id: formData.user_id,
        level_id: formData.level_id,
        remark: formData.remark,
    };
    if (showDuration.value) {
        payload.days = formData.days;
    }
    await grantMember(payload);
    emit("success");
    close();
};
const close = () => emit("close");

const open = (row: any) => {
    formData.user_id = row.user_id;
    const defaultLevel = findDefaultUserLevel(props.levels);
    formData.level_id = resolveUserLevelId(row.level_id, defaultLevel?.id != null ? Number(defaultLevel.id) : null);
    formData.days = 30;
    formData.remark = row.level_id ? "续期" : "后台手动开通";
    userText.value = `${row.nickname || "—"} (ID: ${row.user_id}, ${row.mobile || "无手机号"})`;
    popupRef.value?.open();
};

defineExpose({ open });
</script>
