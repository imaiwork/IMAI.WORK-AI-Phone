<template>
    <popup ref="popRef" title="调整会员等级" width="auto" async @confirm="submit">
        <el-form ref="formRef" :model="formData" :rules="rules" @submit.prevent>
            <div v-if="showMemberInfo" class="mb-4 rounded bg-page px-3 py-2.5 text-sm text-tx-regular leading-6">
                <div>绑定时间：{{ memberInfo.startTime || "-" }}</div>
                <div>到期时间：{{ memberInfo.endTime || "-" }}</div>
                <div>
                    剩余天数：
                    <span :class="remainingDaysTextClass">{{ remainingDaysText }}</span>
                </div>
            </div>
            <el-form-item label="会员等级">
                <el-select
                    v-model="formData.level_id"
                    class="!w-[280px]"
                    filterable
                    remote
                    clearable
                    reserve-keyword
                    :filter-method="() => {}"
                    :remote-method="remoteSearch"
                    :loading="searchLoading"
                    placeholder="请输入关键词搜索">
                    <el-option v-for="item in userLevelList" :key="item.id" :label="item.level_name" :value="item.id" />
                </el-select>
            </el-form-item>
            <el-form-item v-if="showDuration" label="会员期限" prop="day">
                <div>
                    <div class="flex items-center">
                        <el-input-number
                            v-model="formData.day"
                            class="!w-[280px]"
                            :min="1"
                            :max="3650"
                            :precision="0" />
                        <span class="ml-2 text-tx-regular">天</span>
                    </div>
                    <div class="form-tips mt-1">将以当前时间重新起算有效期</div>
                </div>
            </el-form-item>
        </el-form>
    </popup>
</template>

<script setup lang="ts">
import type { FormInstance, FormRules } from "element-plus";
import { shallowRef, ref, reactive, computed } from "vue";
import { getUserLevelList, changeLevel } from "@/api/consumer";
import { findDefaultUserLevel, isDefaultUserLevel, isUnsetUserLevelId, resolveUserLevelId } from "../userLevel";

// ---- 类型定义 ----
interface UserLevelItem {
    id: number;
    level_name: string;
    is_default?: number;
    [key: string]: any;
}

interface OpenOption {
    id: number;
    level_id: number;
    is_member?: boolean;
    member_start_time?: string;
    member_end_time?: string;
}

const MS_PER_DAY = 24 * 60 * 60 * 1000;

// ---- emits ----
const emit = defineEmits<{
    (e: "close"): void;
    (e: "success", value: number | null): void;
}>();

// ---- refs ----
const popRef = shallowRef();
const formRef = shallowRef<FormInstance>();
const userId = ref<number | null>(null);
const userLevelList = ref<UserLevelItem[]>([]);
const searchLoading = ref(false);
const memberInfo = reactive({
    isMember: false,
    startTime: "",
    endTime: "",
});

const formData = reactive({
    level_id: -1 as number | null,
    day: 30 as number | undefined,
});

const defaultLevelId = ref<number | null>(null);

const showMemberInfo = computed(() => {
    return memberInfo.isMember && !!(memberInfo.startTime || memberInfo.endTime);
});

const remainingDays = computed(() => {
    if (!memberInfo.endTime) return null;
    const end = new Date(memberInfo.endTime.replace(/-/g, "/")).getTime();
    if (Number.isNaN(end)) return null;
    return Math.ceil((end - Date.now()) / MS_PER_DAY);
});

const remainingDaysText = computed(() => {
    if (remainingDays.value === null) return "-";
    if (remainingDays.value <= 0) return "已过期";
    return `${remainingDays.value} 天`;
});

const remainingDaysTextClass = computed(() => {
    if (remainingDays.value === null) return "";
    if (remainingDays.value <= 0) return "text-danger";
    if (remainingDays.value <= 7) return "text-warning";
    return "text-tx-primary";
});

const selectedLevel = computed(() => {
    return userLevelList.value.find((item) => Number(item.id) === Number(formData.level_id));
});

const showDuration = computed(() => {
    if (isUnsetUserLevelId(formData.level_id)) return false;
    if (defaultLevelId.value != null && Number(formData.level_id) === Number(defaultLevelId.value)) {
        return false;
    }
    return !isDefaultUserLevel(selectedLevel.value);
});

const rules = computed<FormRules>(() =>
    showDuration.value ? { day: [{ required: true, message: "请输入会员期限", trigger: "blur" }] } : {}
);

// ---- 远程搜索 ----
const remoteSearch = async (query: string) => {
    // query 为空时不清空列表，保留当前回显项
    try {
        searchLoading.value = true;
        const { lists } = await getUserLevelList({ page_size: query ? 20 : 100, level_name: query });
        userLevelList.value = lists ?? [];
        const defaultLevel = findDefaultUserLevel(userLevelList.value);
        if (defaultLevel) {
            defaultLevelId.value = Number(defaultLevel.id);
        }
    } catch {
        userLevelList.value = [];
    } finally {
        searchLoading.value = false;
    }
};

// ---- 打开弹窗 ----
const open = async (option: OpenOption) => {
    // 重置状态
    userLevelList.value = [];
    userId.value = option.id;
    formData.day = 30;
    memberInfo.isMember = !!option.is_member;
    memberInfo.startTime = option.member_start_time || "";
    memberInfo.endTime = option.member_end_time || "";

    // 先拉回显数据，再开弹窗，保证打开时列表已就绪
    await remoteSearch("");
    formData.level_id = resolveUserLevelId(option.level_id, defaultLevelId.value);

    popRef.value.open();
};

// ---- 确认提交 ----
const submit = async () => {
    await formRef.value?.validate();
    const params: Record<string, any> = {
        id: userId.value,
        level_id: formData.level_id,
    };
    if (showDuration.value) {
        params.day = formData.day;
    }
    await changeLevel(params);
    emit("success", formData.level_id);
    close();
};

const close = () => {
    emit("close");
};

// ---- 暴露方法 ----
defineExpose({ open });
</script>

<style scoped></style>
